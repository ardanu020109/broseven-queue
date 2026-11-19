<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Queue;
use App\Models\QueueItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoQueueSeeder extends Seeder
{
    public function run(): void
    {
        $items = QueueItem::query()->active()->ordered()->get();

        if ($items->isEmpty()) {
            $this->command?->warn('Belum ada layanan. Jalankan: php artisan db:seed');

            return;
        }

        if (Queue::query()->exists()) {
            $this->command?->warn('Tabel queues sudah berisi data. Jalankan migrate:fresh --seed lebih dulu.');

            return;
        }

        $names = ['Budi', 'Siti', 'Andi', 'Dewi', 'Rudi', 'Lina', 'Agus', 'Nur'];

        for ($offset = 45; $offset >= 0; $offset--) {
            $day = now()->subDays($offset)->startOfDay();
            $isToday = $offset === 0;
            $rows = [];

            foreach ($items as $item) {
                $count = $isToday ? random_int(5, 9) : random_int(3, 24);

                for ($seq = 1; $seq <= $count; $seq++) {
                    $issued = $isToday
                        ? now()->subMinutes(($count - $seq) * 4 + 2)
                        : $day->copy()->setTime(8, 0)->addMinutes($seq * random_int(5, 15));

                    $status = 'completed';
                    $calledAt = null;
                    $skippedAt = null;
                    $completedAt = null;
                    $counter = null;

                    if ($isToday) {
                        $status = match (true) {
                            $seq <= $count - 3 => 'completed',
                            $seq === $count - 2 => 'skipped',
                            $seq === $count - 1 => 'called',
                            default => 'waiting',
                        };
                    } elseif (random_int(1, 100) <= 10) {
                        $status = 'skipped';
                    }

                    if ($status !== 'waiting') {
                        $calledAt = $issued->copy()->addMinutes(random_int(2, 15));
                        $counter = 'Loket ' . random_int(1, 3);
                    }

                    if ($status === 'skipped') {
                        $skippedAt = $calledAt->copy()->addMinutes(1);
                    }

                    if ($status === 'completed') {
                        $completedAt = $calledAt->copy()->addMinutes(random_int(3, 10));
                    }

                    $rows[] = [
                        'queue_item_id' => $item->id,
                        'queue_number' => $item->formatNumber($seq, $issued),
                        'sequence_number' => $seq,
                        'period_key' => $item->currentPeriodKey($issued),
                        'customer_name' => random_int(1, 3) === 1 ? $names[array_rand($names)] : null,
                        'customer_phone' => null,
                        'notes' => null,
                        'status' => $status,
                        'counter' => $counter,
                        'issued_at' => $issued->format('Y-m-d H:i:s'),
                        'called_at' => $calledAt?->format('Y-m-d H:i:s'),
                        'recalled_at' => null,
                        'skipped_at' => $skippedAt?->format('Y-m-d H:i:s'),
                        'completed_at' => $completedAt?->format('Y-m-d H:i:s'),
                        'called_by' => null,
                        'created_at' => $issued->format('Y-m-d H:i:s'),
                        'updated_at' => $issued->format('Y-m-d H:i:s'),
                    ];
                }
            }

            Queue::insert($rows);
        }

        // Beberapa aktivitas contoh dari antrean terbaru.
        $admin = User::query()->first();

        Queue::query()->with('item')->orderByDesc('id')->limit(6)->get()->reverse()->each(
            function (Queue $queue) use ($admin) {
                ActivityLog::create([
                    'user_id' => $queue->status->value === 'waiting' ? null : $admin?->id,
                    'action' => match ($queue->status->value) {
                        'waiting' => 'queue_created',
                        'called' => 'queue_called',
                        'skipped' => 'queue_skipped',
                        default => 'queue_completed',
                    },
                    'description' => 'Antrean ' . $queue->queue_number . ' (' . $queue->item->name . ') berstatus ' . $queue->status->label() . '.',
                    'subject_type' => $queue->getMorphClass(),
                    'subject_id' => $queue->id,
                ]);
            }
        );

        $this->command?->info('Data demo antrean dibuat.');
    }
}
