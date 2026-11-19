<?php

namespace App\Services;

use App\Enums\QueueAction;
use App\Enums\QueueStatus;
use App\Models\ActivityLog;
use App\Models\Queue;
use App\Models\QueueCallHistory;
use App\Models\QueueItem;
use App\Models\User;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Seluruh perubahan status antrean oleh admin.
 *
 * Konkurensi: setiap aksi berjalan dalam transaksi yang lebih dulu mengunci baris
 * layanan (FOR UPDATE), sama seperti QueueNumberService. Akibatnya semua aksi pada
 * satu layanan berjalan berurutan. Unique index "active_slot" di database menjadi
 * pengaman terakhir agar tidak pernah ada dua antrean aktif.
 */
class QueueManagementService
{
    /** Panggil Berikutnya: antrean menunggu paling awal pada periode berjalan. */
    public function callNext(QueueItem $item, User $user, ?string $counter = null): Queue
    {
        return DB::transaction(function () use ($item, $user, $counter) {
            $locked = $this->lockItem($item);
            $this->assertNoActiveQueue($locked);

            $next = Queue::query()
                ->where('queue_item_id', $locked->id)
                ->where('status', QueueStatus::Waiting->value)
                ->where('period_key', $locked->currentPeriodKey())
                ->orderBy('sequence_number')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $next) {
                throw new DomainException('Tidak ada antrean yang sedang menunggu.');
            }

            return $this->markCalled($locked, $next, $user, $counter, QueueAction::Called, 'dipanggil', 'queue_called');
        }, 3);
    }

    /** Panggil antrean menunggu tertentu (dari daftar), tanpa mengikuti urutan. */
    public function callQueue(Queue $queue, User $user, ?string $counter = null): Queue
    {
        return DB::transaction(function () use ($queue, $user, $counter) {
            [$item, $locked] = $this->lockQueue($queue);

            if ($locked->status !== QueueStatus::Waiting) {
                throw new DomainException(
                    'Antrean ' . $locked->queue_number . ' berstatus "' . $locked->status->label() . '" dan tidak dapat dipanggil dari daftar menunggu.'
                );
            }

            if ($locked->period_key !== $item->currentPeriodKey()) {
                throw new DomainException(
                    'Antrean ' . $locked->queue_number . ' berasal dari periode sebelumnya dan sudah kedaluwarsa. Gunakan Skip untuk menutupnya.'
                );
            }

            $this->assertNoActiveQueue($item);

            return $this->markCalled($item, $locked, $user, $counter, QueueAction::Called, 'dipanggil', 'queue_called');
        }, 3);
    }

    /** Panggil Lagi: memanggil kembali antrean aktif tanpa mengubah status atau urutan. */
    public function recall(QueueItem $item, User $user): Queue
    {
        return DB::transaction(function () use ($item, $user) {
            $locked = $this->lockItem($item);

            $active = Queue::query()
                ->where('queue_item_id', $locked->id)
                ->where('status', QueueStatus::Called->value)
                ->lockForUpdate()
                ->first();

            if (! $active) {
                throw new DomainException('Tidak ada antrean aktif untuk dipanggil lagi.');
            }

            $active->forceFill(['recalled_at' => now()])->save();

            $this->record($locked, $active, $user, QueueAction::Recalled, 'Antrean ' . $active->queue_number . ' dipanggil lagi', 'queue_recalled');

            return $active->setRelation('item', $locked);
        }, 3);
    }

    /** Skip: antrean menunggu atau antrean aktif. Data tetap tersimpan. */
    public function skip(Queue $queue, User $user): Queue
    {
        return DB::transaction(function () use ($queue, $user) {
            [$item, $locked] = $this->lockQueue($queue);

            if (! in_array($locked->status, [QueueStatus::Waiting, QueueStatus::Called], true)) {
                throw new DomainException(
                    'Antrean ' . $locked->queue_number . ' berstatus "' . $locked->status->label() . '" dan tidak dapat di-skip.'
                );
            }

            // Jika antrean ini sedang aktif, perubahan status otomatis mengosongkan antrean aktif.
            $locked->forceFill([
                'status' => QueueStatus::Skipped,
                'skipped_at' => now(),
            ])->save();

            $this->record($item, $locked, $user, QueueAction::Skipped, 'Antrean ' . $locked->queue_number . ' di-skip', 'queue_skipped');

            return $locked->setRelation('item', $item);
        }, 3);
    }

    /** Panggil Ulang antrean yang di-skip: kembali menjadi aktif, tanpa nomor baru. */
    public function restore(Queue $queue, User $user, ?string $counter = null): Queue
    {
        return DB::transaction(function () use ($queue, $user, $counter) {
            [$item, $locked] = $this->lockQueue($queue);

            if ($locked->status !== QueueStatus::Skipped) {
                throw new DomainException(
                    'Antrean ' . $locked->queue_number . ' berstatus "' . $locked->status->label() . '" dan bukan antrean yang di-skip.'
                );
            }

            $this->assertNoActiveQueue($item);

            return $this->markCalled($item, $locked, $user, $counter, QueueAction::Restored, 'dipanggil kembali dari daftar skip', 'queue_restored');
        }, 3);
    }

    /** Selesaikan antrean aktif. */
    public function complete(Queue $queue, User $user): Queue
    {
        return DB::transaction(function () use ($queue, $user) {
            [$item, $locked] = $this->lockQueue($queue);

            if ($locked->status !== QueueStatus::Called) {
                throw new DomainException(
                    'Hanya antrean yang sedang dipanggil yang dapat diselesaikan. Antrean ' . $locked->queue_number
                    . ' berstatus "' . $locked->status->label() . '".'
                );
            }

            $locked->forceFill([
                'status' => QueueStatus::Completed,
                'completed_at' => now(),
            ])->save();

            $this->record($item, $locked, $user, QueueAction::Completed, 'Antrean ' . $locked->queue_number . ' diselesaikan', 'queue_completed');

            return $locked->setRelation('item', $item);
        }, 3);
    }

    /* ---------------------------------------------------------------
     | Internal
     * ------------------------------------------------------------- */

    /** Mengunci baris layanan (termasuk yang sudah di-soft-delete). */
    private function lockItem(QueueItem $item): QueueItem
    {
        return QueueItem::withTrashed()->lockForUpdate()->findOrFail($item->getKey());
    }

    /**
     * Mengunci layanan lebih dulu, baru baris antrean (urutan kunci selalu sama,
     * sehingga tidak terjadi deadlock), lalu memuat ulang antrean dalam kondisi terkunci.
     *
     * @return array{0: QueueItem, 1: Queue}
     */
    private function lockQueue(Queue $queue): array
    {
        $itemId = Queue::query()->whereKey($queue->getKey())->value('queue_item_id');

        if ($itemId === null) {
            throw new DomainException('Antrean tidak ditemukan.');
        }

        $item = QueueItem::withTrashed()->lockForUpdate()->findOrFail($itemId);
        $locked = Queue::query()->lockForUpdate()->findOrFail($queue->getKey());

        return [$item, $locked];
    }

    private function assertNoActiveQueue(QueueItem $item): void
    {
        $active = Queue::query()
            ->where('queue_item_id', $item->id)
            ->where('status', QueueStatus::Called->value)
            ->first(['id', 'queue_number']);

        if ($active) {
            throw new DomainException(
                'Antrean ' . $active->queue_number . ' masih sedang dipanggil. Selesaikan atau skip terlebih dahulu.'
            );
        }
    }

    private function markCalled(
        QueueItem $item,
        Queue $queue,
        User $user,
        ?string $counter,
        QueueAction $action,
        string $verb,
        string $activity
    ): Queue {
        try {
            $queue->forceFill([
                'status' => QueueStatus::Called,
                'called_at' => now(),
                'called_by' => $user->id,
                'counter' => $this->normalizeCounter($counter) ?? $queue->counter,
            ])->save();
        } catch (QueryException $e) {
            // Pengaman terakhir: unique index active_slot menolak antrean aktif ganda.
            if ($e->getCode() === '23000') {
                throw new DomainException('Sudah ada antrean lain yang sedang dipanggil pada layanan ini.');
            }

            throw $e;
        }

        $this->record($item, $queue, $user, $action, 'Antrean ' . $queue->queue_number . ' ' . $verb, $activity);

        return $queue->setRelation('item', $item);
    }

    private function record(QueueItem $item, Queue $queue, User $user, QueueAction $action, string $message, string $activity): void
    {
        QueueCallHistory::create([
            'queue_id' => $queue->id,
            'user_id' => $user->id,
            'action' => $action,
            'description' => $message . '.',
            'called_at' => now(),
        ]);

        ActivityLog::record(
            $activity,
            $message . ' pada layanan ' . $item->name . '.',
            $queue,
            ['queue_item_id' => $item->id, 'queue_number' => $queue->queue_number],
            $user->id
        );
    }

    private function normalizeCounter(?string $counter): ?string
    {
        $counter = trim((string) $counter);

        return $counter === '' ? null : mb_substr($counter, 0, 50);
    }
}
