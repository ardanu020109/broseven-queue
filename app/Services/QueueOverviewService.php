<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\QueueItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class QueueOverviewService
{
    /**
     * Menambahkan atribut waiting_count, current_number, dan is_open pada setiap layanan.
     *
     * @param  Collection<int, QueueItem>  $items
     * @return Collection<int, QueueItem>
     */
    public function decorate(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $now = now();

        // Antrean menunggu dihitung hanya pada periode reset yang sedang berlaku.
        $waiting = Queue::query()
            ->selectRaw('queue_item_id, COUNT(*) as total')
            ->where('status', QueueStatus::Waiting->value)
            ->where(function (Builder $query) use ($items, $now) {
                foreach ($items as $item) {
                    $query->orWhere(function (Builder $group) use ($item, $now) {
                        $group->where('queue_item_id', $item->id)
                            ->where('period_key', $item->currentPeriodKey($now));
                    });
                }
            })
            ->groupBy('queue_item_id')
            ->pluck('total', 'queue_item_id');

        // Antrean yang sedang dipanggil (maksimal satu per layanan, dijamin database).
        $called = Queue::query()
            ->where('status', QueueStatus::Called->value)
            ->whereIn('queue_item_id', $items->pluck('id'))
            ->pluck('queue_number', 'queue_item_id');

        return $items->each(function (QueueItem $item) use ($waiting, $called, $now) {
            $item->waiting_count = (int) ($waiting[$item->id] ?? 0);
            $item->current_number = $called[$item->id] ?? null;
            $item->is_open = $item->isOpenAt($now);
        });
    }
}
