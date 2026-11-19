<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\QueueCallHistory;
use App\Models\QueueItem;
use Illuminate\Database\Eloquent\Builder;

class DisplayDataService
{
    public function __construct(private readonly QueueOverviewService $overview)
    {
    }

    /**
     * Gambaran lengkap yang ditampilkan di layar. Hanya data publik (tanpa data pribadi).
     *
     * @return array<string, mixed>
     */
    public function snapshot(?QueueItem $only = null, int $limit = 5): array
    {
        // Layanan aktif, ditambah layanan nonaktif yang masih punya antrean sedang dipanggil.
        $items = QueueItem::query()
            ->where(function (Builder $query) {
                $query->where('is_active', true)
                    ->orWhereHas('queues', fn (Builder $q) => $q->where('status', QueueStatus::Called->value));
            })
            ->when($only, fn (Builder $query) => $query->whereKey($only->id))
            ->ordered()
            ->get();

        $empty = [
            'generated_at' => now()->toIso8601String(),
            'now' => null,
            'services' => [],
            'next' => [],
            'recent' => [],
        ];

        if ($items->isEmpty()) {
            return $empty;
        }

        $this->overview->decorate($items);
        $ids = $items->pluck('id');

        // Antrean yang paling baru dipanggil atau dipanggil ulang menjadi sorotan utama.
        $latest = Queue::query()
            ->with('item:id,name')
            ->whereIn('queue_item_id', $ids)
            ->where('status', QueueStatus::Called->value)
            ->get()
            ->sortByDesc(fn (Queue $queue) => max(
                $queue->called_at?->getTimestamp() ?? 0,
                $queue->recalled_at?->getTimestamp() ?? 0
            ))
            ->first();

        $next = Queue::query()
            ->with('item:id,name,color')
            ->where('status', QueueStatus::Waiting->value)
            ->where(function (Builder $query) use ($items) {
                foreach ($items as $item) {
                    $query->orWhere(function (Builder $group) use ($item) {
                        $group->where('queue_item_id', $item->id)
                            ->where('period_key', $item->currentPeriodKey());
                    });
                }
            })
            ->orderBy('issued_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $recent = QueueCallHistory::query()
            ->with('queue.item:id,name')
            ->whereIn('action', ['called', 'recalled', 'restored'])
            ->whereHas('queue', fn (Builder $query) => $query->whereIn('queue_item_id', $ids))
            ->orderByDesc('called_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        return [
            'generated_at' => now()->toIso8601String(),
            'now' => $latest ? [
                'number' => $latest->queue_number,
                'service' => $latest->item?->name,
                'service_id' => $latest->queue_item_id,
                'counter' => $latest->counter,
                'called_at' => $latest->called_at?->toIso8601String(),
                'recalled_at' => $latest->recalled_at?->toIso8601String(),
            ] : null,
            'services' => $items->map(fn (QueueItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'color' => $item->safeColor(),
                'current' => $item->current_number,
                'waiting' => $item->waiting_count,
                'is_open' => $item->is_open,
            ])->values()->all(),
            'next' => $next->map(fn (Queue $queue) => [
                'number' => $queue->queue_number,
                'service' => $queue->item?->name,
                'color' => $queue->item?->safeColor(),
            ])->values()->all(),
            'recent' => $recent->map(fn (QueueCallHistory $history) => [
                'number' => $history->queue?->queue_number,
                'service' => $history->queue?->item?->name,
                'counter' => $history->queue?->counter,
                'action' => $history->action->value,
                'at' => $history->called_at->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
