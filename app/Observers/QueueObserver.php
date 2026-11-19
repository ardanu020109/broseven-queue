<?php

namespace App\Observers;

use App\Enums\QueueStatus;
use App\Events\QueueCalled;
use App\Events\QueueCompleted;
use App\Events\QueueCreated;
use App\Events\QueueRecalled;
use App\Events\QueueSkipped;
use App\Models\Queue;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Throwable;

/**
 * Menyiarkan perubahan antrean ke layar display.
 * Dijalankan setelah transaksi database berhasil (commit), sehingga display tidak
 * menerima kabar dari perubahan yang dibatalkan.
 */
class QueueObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Queue $queue): void
    {
        $this->broadcast(new QueueCreated($queue));
    }

    public function updated(Queue $queue): void
    {
        $event = null;

        if ($queue->wasChanged('status')) {
            $event = match ($queue->status) {
                QueueStatus::Called => new QueueCalled($queue),
                QueueStatus::Skipped => new QueueSkipped($queue),
                QueueStatus::Completed => new QueueCompleted($queue),
                default => null,
            };
        } elseif ($queue->wasChanged('recalled_at')) {
            $event = new QueueRecalled($queue);
        }

        if ($event !== null) {
            $this->broadcast($event);
        }
    }

    /** Kegagalan siaran (misalnya Reverb belum berjalan) tidak boleh menggagalkan aksi pengguna. */
    private function broadcast(object $event): void
    {
        try {
            event($event);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
