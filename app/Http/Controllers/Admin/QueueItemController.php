<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQueueItemRequest;
use App\Http\Requests\Admin\UpdateQueueItemRequest;
use App\Models\ActivityLog;
use App\Models\Queue;
use App\Models\QueueItem;
use App\Services\QueueOverviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class QueueItemController extends Controller
{
    public function index(QueueOverviewService $overview): View
    {
        $items = $overview->decorate(QueueItem::query()->ordered()->get());

        $totals = Queue::query()
            ->whereBetween('issued_at', [now()->startOfDay(), now()->endOfDay()])
            ->selectRaw('queue_item_id, COUNT(*) as total')
            ->groupBy('queue_item_id')
            ->pluck('total', 'queue_item_id');

        $items->each(fn (QueueItem $item) => $item->today_total = (int) ($totals[$item->id] ?? 0));

        return view('admin.queue-items.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.queue-items.create', ['item' => new QueueItem()]);
    }

    public function store(StoreQueueItemRequest $request): RedirectResponse
    {
        $data = $request->itemData();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('queue-items', 'public');
        }

        // Layanan baru ditempatkan di urutan terakhir.
        $data['sort_order'] = ((int) QueueItem::withTrashed()->max('sort_order')) + 1;

        $item = QueueItem::create($data);

        ActivityLog::record(
            'queue_item_created',
            'Layanan ' . $item->name . ' (' . $item->prefix . ') ditambahkan.',
            $item
        );

        return redirect()->route('admin.queue-items.index')
            ->with('success', 'Layanan "' . $item->name . '" berhasil ditambahkan.');
    }

    public function edit(QueueItem $queueItem): View
    {
        return view('admin.queue-items.edit', ['item' => $queueItem]);
    }

    public function update(UpdateQueueItemRequest $request, QueueItem $queueItem): RedirectResponse
    {
        $data = $request->itemData();

        if ($request->hasFile('logo')) {
            $newPath = $request->file('logo')->store('queue-items', 'public');
            $this->deleteLogo($queueItem);
            $data['logo'] = $newPath;
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogo($queueItem);
            $data['logo'] = null;
        }

        // Slug tidak berubah agar tautan publik (?layanan=slug) tetap berlaku.
        $queueItem->update($data);

        ActivityLog::record(
            'queue_item_updated',
            'Layanan ' . $queueItem->name . ' (' . $queueItem->prefix . ') diperbarui.',
            $queueItem
        );

        return redirect()->route('admin.queue-items.index')
            ->with('success', 'Layanan "' . $queueItem->name . '" berhasil diperbarui.');
    }

    public function destroy(QueueItem $queueItem): RedirectResponse
    {
        $hasOpenQueues = $queueItem->queues()
            ->whereIn('status', [QueueStatus::Waiting->value, QueueStatus::Called->value])
            ->exists();

        if ($hasOpenQueues) {
            return redirect()->route('admin.queue-items.index')->with(
                'error',
                'Layanan "' . $queueItem->name . '" masih memiliki antrean menunggu atau sedang dipanggil. '
                . 'Selesaikan atau skip antrean tersebut terlebih dahulu, atau cukup nonaktifkan layanan.'
            );
        }

        $queueItem->delete();

        ActivityLog::record(
            'queue_item_deleted',
            'Layanan ' . $queueItem->name . ' (' . $queueItem->prefix . ') dihapus. Riwayat antrean tetap tersimpan.',
            $queueItem
        );

        return redirect()->route('admin.queue-items.index')
            ->with('success', 'Layanan "' . $queueItem->name . '" dihapus. Riwayat antreannya tetap tersimpan.');
    }

    public function toggle(QueueItem $queueItem): RedirectResponse
    {
        $queueItem->update(['is_active' => ! $queueItem->is_active]);

        ActivityLog::record(
            'queue_item_toggled',
            'Layanan ' . $queueItem->name . ($queueItem->is_active ? ' diaktifkan.' : ' dinonaktifkan.'),
            $queueItem
        );

        return redirect()->route('admin.queue-items.index')->with(
            'success',
            'Layanan "' . $queueItem->name . '" ' . ($queueItem->is_active ? 'diaktifkan.' : 'dinonaktifkan.')
        );
    }

    public function move(Request $request, QueueItem $queueItem): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($queueItem, $direction) {
            // Normalisasi urutan menjadi 1..n sambil menukar dengan tetangganya.
            $ids = QueueItem::query()->ordered()->lockForUpdate()->pluck('id')->values()->all();

            $index = array_search($queueItem->id, $ids, true);

            if ($index === false) {
                return;
            }

            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($target >= 0 && $target < count($ids)) {
                [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
            }

            foreach ($ids as $position => $id) {
                QueueItem::query()->whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        ActivityLog::record(
            'queue_item_reordered',
            'Urutan layanan ' . $queueItem->name . ' dipindah ke ' . ($direction === 'up' ? 'atas.' : 'bawah.'),
            $queueItem
        );

        return redirect()->route('admin.queue-items.index');
    }

    private function deleteLogo(QueueItem $item): void
    {
        if ($item->logo) {
            Storage::disk('public')->delete($item->logo);
        }
    }
}
