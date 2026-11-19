<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\QueueItem;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class QueueNumberService
{
    /**
     * Membuat antrean baru. Nomor SELALU dibuat di backend.
     *
     * @param  array{customer_name?: ?string, customer_phone?: ?string, notes?: ?string}  $customer
     *
     * @throws DomainException jika layanan nonaktif atau di luar jam operasional.
     */
    public function issue(QueueItem $item, array $customer = []): Queue
    {
        return DB::transaction(function () use ($item, $customer) {
            // Kunci baris layanan agar pengambilan bersamaan diserialisasi per layanan.
            $lockedItem = QueueItem::query()->lockForUpdate()->findOrFail($item->getKey());

            if (! $lockedItem->is_active) {
                throw new DomainException('Layanan "' . $lockedItem->name . '" sedang tidak aktif.');
            }

            $now = now();

            if (! $lockedItem->isOpenAt($now)) {
                throw new DomainException('Layanan "' . $lockedItem->name . '" sedang di luar jam operasional.');
            }

            $periodKey = $lockedItem->reset_period->periodKey($now);

            $lastSequence = Queue::query()
                ->where('queue_item_id', $lockedItem->id)
                ->where('period_key', $periodKey)
                ->max('sequence_number');

            $sequence = ((int) $lastSequence) + 1;

            $data = Arr::only($customer, ['customer_name', 'customer_phone', 'notes']);
            $data = array_map(fn ($v) => is_string($v) && trim($v) !== '' ? trim($v) : null, $data);

            $queue = Queue::create($data + [
                'queue_item_id' => $lockedItem->id,
                'queue_number' => $lockedItem->formatNumber($sequence, $now),
                'sequence_number' => $sequence,
                'period_key' => $periodKey,
                'status' => QueueStatus::Waiting,
                'issued_at' => $now,
            ]);

            $queue->setRelation('item', $lockedItem);

            return $queue;
        }, 3);
    }
}
