<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QueueActionRequest;
use App\Models\Queue;
use App\Models\QueueCallHistory;
use App\Models\QueueItem;
use App\Services\QueueManagementService;
use Carbon\Carbon;
use Closure;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueManagementController extends Controller
{
    private const STATUS_TABS = ['all', 'waiting', 'called', 'skipped', 'completed'];

    public function __construct(private readonly QueueManagementService $queues)
    {
    }

    public function show(Request $request, QueueItem $queueItem): View
    {
        $status = (string) $request->query('status', 'all');
        if (! in_array($status, self::STATUS_TABS, true)) {
            $status = 'all';
        }

        $date = $this->resolveDate($request->query('date'));
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $includeOlderOpen = $date->isToday();
        $periodKey = $queueItem->currentPeriodKey();

        // Antrean pada tanggal terpilih. Untuk hari ini, antrean terbuka dari hari sebelumnya ikut tampil.
        $scope = function () use ($queueItem, $start, $end, $includeOlderOpen) {
            return Queue::query()
                ->where('queue_item_id', $queueItem->id)
                ->where(function ($query) use ($start, $end, $includeOlderOpen) {
                    $query->whereBetween('issued_at', [$start, $end]);

                    if ($includeOlderOpen) {
                        $query->orWhere(function ($old) use ($start) {
                            $old->where('issued_at', '<', $start)
                                ->whereIn('status', [QueueStatus::Waiting->value, QueueStatus::Called->value]);
                        });
                    }
                });
        };

        $byStatus = $scope()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $tabCounts = [
            'all' => (int) $byStatus->sum(),
            'waiting' => (int) ($byStatus[QueueStatus::Waiting->value] ?? 0),
            'called' => (int) ($byStatus[QueueStatus::Called->value] ?? 0),
            'skipped' => (int) ($byStatus[QueueStatus::Skipped->value] ?? 0),
            'completed' => (int) ($byStatus[QueueStatus::Completed->value] ?? 0),
        ];

        $queues = $scope()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when(
                $status === 'waiting',
                fn ($query) => $query->orderBy('issued_at')->orderBy('id'),
                fn ($query) => $query->orderByDesc('issued_at')->orderByDesc('id')
            )
            ->paginate(15)
            ->withQueryString();

        $queues->getCollection()->each(fn (Queue $queue) => $queue->setRelation('item', $queueItem));

        // Ringkasan hari ini (tidak terpengaruh filter tanggal).
        $todayByStatus = Queue::query()
            ->where('queue_item_id', $queueItem->id)
            ->whereBetween('issued_at', [now()->startOfDay(), now()->endOfDay()])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $today = [
            'total' => (int) $todayByStatus->sum(),
            'completed' => (int) ($todayByStatus[QueueStatus::Completed->value] ?? 0),
            'skipped' => (int) ($todayByStatus[QueueStatus::Skipped->value] ?? 0),
        ];

        $active = Queue::query()
            ->with('caller:id,name')
            ->where('queue_item_id', $queueItem->id)
            ->where('status', QueueStatus::Called->value)
            ->first();

        $waitingQuery = fn () => Queue::query()
            ->where('queue_item_id', $queueItem->id)
            ->where('status', QueueStatus::Waiting->value);

        $next = $waitingQuery()
            ->where('period_key', $periodKey)
            ->orderBy('sequence_number')
            ->orderBy('id')
            ->first();

        $waitingCount = $waitingQuery()->where('period_key', $periodKey)->count();
        $staleCount = $waitingQuery()->where('period_key', '!=', $periodKey)->count();

        $skipped = Queue::query()
            ->where('queue_item_id', $queueItem->id)
            ->where('status', QueueStatus::Skipped->value)
            ->orderByDesc('skipped_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $histories = QueueCallHistory::query()
            ->with(['queue:id,queue_number', 'user:id,name'])
            ->whereHas('queue', fn ($query) => $query->where('queue_item_id', $queueItem->id))
            ->orderByDesc('called_at')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        return view('admin.queue-items.show', [
            'item' => $queueItem,
            'queues' => $queues,
            'status' => $status,
            'date' => $date,
            'tabCounts' => $tabCounts,
            'today' => $today,
            'active' => $active,
            'next' => $next,
            'waitingCount' => $waitingCount,
            'staleCount' => $staleCount,
            'periodKey' => $periodKey,
            'skipped' => $skipped,
            'histories' => $histories,
        ]);
    }

    public function callNext(QueueActionRequest $request, QueueItem $queueItem): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->queues->callNext($queueItem, $request->user(), $request->input('counter')),
            fn (Queue $queue) => 'Antrean ' . $queue->queue_number . ' dipanggil.'
        );
    }

    public function recall(QueueActionRequest $request, QueueItem $queueItem): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->queues->recall($queueItem, $request->user()),
            fn (Queue $queue) => 'Antrean ' . $queue->queue_number . ' dipanggil lagi.'
        );
    }

    public function call(QueueActionRequest $request, Queue $queue): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->queues->callQueue($queue, $request->user(), $request->input('counter')),
            fn (Queue $called) => 'Antrean ' . $called->queue_number . ' dipanggil.'
        );
    }

    public function skip(QueueActionRequest $request, Queue $queue): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->queues->skip($queue, $request->user()),
            fn (Queue $skipped) => 'Antrean ' . $skipped->queue_number . ' di-skip.'
        );
    }

    public function restore(QueueActionRequest $request, Queue $queue): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->queues->restore($queue, $request->user(), $request->input('counter')),
            fn (Queue $restored) => 'Antrean ' . $restored->queue_number . ' dipanggil kembali.'
        );
    }

    public function complete(QueueActionRequest $request, Queue $queue): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->queues->complete($queue, $request->user()),
            fn (Queue $completed) => 'Antrean ' . $completed->queue_number . ' diselesaikan.'
        );
    }

    /** Menjalankan aksi; aturan bisnis yang dilanggar ditampilkan sebagai pesan, bukan error 500. */
    private function attempt(Closure $action, Closure $message): RedirectResponse
    {
        try {
            $queue = $action();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message($queue));
    }

    /** Tanggal filter (Y-m-d). Nilai tidak valid atau di masa depan menjadi hari ini. */
    private function resolveDate(mixed $value): Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return now()->startOfDay();
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return now()->startOfDay();
        }

        if (! $date || $date->format('Y-m-d') !== $value || $date->isFuture()) {
            return now()->startOfDay();
        }

        return $date;
    }
}
