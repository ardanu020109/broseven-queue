<?php

namespace App\Console\Commands;

use App\Models\QueueItem;
use App\Models\User;
use App\Services\QueueManagementService;
use App\Services\QueueNumberService;
use DomainException;
use Illuminate\Console\Command;
use Throwable;

/** Pekerja uji beban. Dijalankan oleh queue:stress-test, bukan untuk dipakai langsung. */
class QueueStressWorker extends Command
{
    protected $signature = 'queue:stress-worker
        {scenario : issue|drain|chase}
        {item : ID layanan}
        {count : Jumlah operasi}
        {--start-at=0 : Waktu mulai (detik UNIX, desimal)}';

    protected $description = 'Pekerja uji beban (dipanggil oleh queue:stress-test).';

    protected $hidden = true;

    public function handle(QueueNumberService $numbers, QueueManagementService $manage): int
    {
        // Penghalang: semua pekerja menunggu waktu yang sama agar benar-benar serentak.
        $startAt = (float) $this->option('start-at');
        while (microtime(true) < $startAt) {
            usleep(1000);
        }

        $item = QueueItem::query()->findOrFail((int) $this->argument('item'));
        $user = User::query()->firstOrFail();
        $count = (int) $this->argument('count');

        $result = ['ok' => 0, 'rejected' => 0, 'errors' => 0, 'first_error' => null];

        match ($this->argument('scenario')) {
            'issue' => $this->issue($numbers, $item, $count, $result),
            'drain' => $this->drain($manage, $item, $user, $result),
            'chase' => $this->chase($manage, $item, $user, $count, $result),
            default => $result['errors']++,
        };

        $this->line('RESULT:' . json_encode($result));

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $result */
    private function issue(QueueNumberService $numbers, QueueItem $item, int $count, array &$result): void
    {
        for ($i = 0; $i < $count; $i++) {
            try {
                $numbers->issue($item);
                $result['ok']++;
            } catch (DomainException) {
                $result['rejected']++;
            } catch (Throwable $e) {
                $this->failure($result, $e);
            }
        }
    }

    /** Memanggil dan menyelesaikan antrean sampai tidak ada yang menunggu. */
    private function drain(QueueManagementService $manage, QueueItem $item, User $user, array &$result): void
    {
        $deadline = microtime(true) + 90;

        while (microtime(true) < $deadline) {
            try {
                $queue = $manage->callNext($item, $user, 'Loket ' . (getmypid() % 5));
                $result['ok']++;

                usleep(random_int(0, 3000));

                $manage->complete($queue, $user);
            } catch (DomainException $e) {
                if (str_contains($e->getMessage(), 'Tidak ada antrean yang sedang menunggu')) {
                    break;
                }

                // Ditolak karena antrean lain sedang aktif: perilaku yang benar.
                $result['rejected']++;
                usleep(random_int(500, 4000));
            } catch (Throwable $e) {
                $this->failure($result, $e);
                usleep(1000);
            }
        }
    }

    /** Seperti drain, tetapi menunggu antrean baru (dipakai saat penerbitan masih berlangsung). */
    private function chase(QueueManagementService $manage, QueueItem $item, User $user, int $count, array &$result): void
    {
        $deadline = microtime(true) + 60;

        while ($result['ok'] < $count && microtime(true) < $deadline) {
            try {
                $queue = $manage->callNext($item, $user, 'Loket ' . (getmypid() % 5));
                $result['ok']++;

                usleep(random_int(0, 3000));

                $manage->complete($queue, $user);
            } catch (DomainException) {
                $result['rejected']++;
                usleep(random_int(1000, 5000));
            } catch (Throwable $e) {
                $this->failure($result, $e);
                usleep(1000);
            }
        }
    }

    /** @param array<string, mixed> $result */
    private function failure(array &$result, Throwable $e): void
    {
        $result['errors']++;
        $result['first_error'] ??= $e::class . ': ' . $e->getMessage();
    }
}
