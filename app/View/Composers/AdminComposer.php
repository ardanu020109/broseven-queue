<?php

namespace App\View\Composers;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\QueueItem;
use Illuminate\View\View;

class AdminComposer
{
    public function compose(View $view): void
    {
        $counts = Queue::query()
            ->where('status', QueueStatus::Waiting->value)
            ->whereDate('issued_at', now()->toDateString())
            ->selectRaw('queue_item_id, COUNT(*) as total')
            ->groupBy('queue_item_id')
            ->pluck('total', 'queue_item_id');

        $services = collect();

        if ($counts->isNotEmpty()) {
            $services = QueueItem::query()
                ->whereIn('id', $counts->keys())
                ->ordered()
                ->get(['id', 'name', 'prefix', 'color'])
                ->each(fn (QueueItem $item) => $item->waiting_count = (int) $counts[$item->id]);
        }

        $view->with('navWaitingTotal', (int) $counts->sum())
            ->with('navWaitingServices', $services);
    }
}
