<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\ActivityLog;
use App\Models\Queue;
use App\Models\QueueItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DashboardStatsService
{
    /** Ringkasan antrean yang dibuat hari ini. @return array<string, int> */
    public function summary(): array
    {
        $byStatus = Queue::query()
            ->whereBetween('issued_at', [now()->startOfDay(), now()->endOfDay()])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($row) {
                $status = $row->status instanceof QueueStatus
                    ? $row->status->value
                    : $row->status;

                return [
                    $status => (int) $row->total,
                ];
            });

        return [
            'total' => (int) $byStatus->sum(),
            'waiting' => (int) ($byStatus[QueueStatus::Waiting->value] ?? 0),
            'called' => (int) ($byStatus[QueueStatus::Called->value] ?? 0),
            'completed' => (int) ($byStatus[QueueStatus::Completed->value] ?? 0),
            'skipped' => (int) ($byStatus[QueueStatus::Skipped->value] ?? 0),
            'services' => QueueItem::query()->count(),
        ];
    }

    /**
     * Rincian hari ini per layanan (atribut today_counts pada tiap layanan).
     * Menampilkan layanan aktif, serta layanan nonaktif yang masih punya antrean hari ini.
     *
     * @return Collection<int, QueueItem>
     */
    public function services(): Collection
    {
        $rows = Queue::query()
            ->whereBetween('issued_at', [now()->startOfDay(), now()->endOfDay()])
            ->selectRaw('queue_item_id, status, COUNT(*) as total')
            ->groupBy('queue_item_id', 'status')
            ->get()
            ->groupBy('queue_item_id');

        return QueueItem::query()
            ->ordered()
            ->get()
            ->map(function (QueueItem $item) use ($rows) {

                $byStatus = ($rows->get($item->id) ?? collect())
                    ->mapWithKeys(function ($row) {
                        $status = $row->status instanceof QueueStatus
                            ? $row->status->value
                            : $row->status;

                        return [
                            $status => (int) $row->total,
                        ];
                    });

                $counts = [
                    'waiting' => (int) ($byStatus[QueueStatus::Waiting->value] ?? 0),
                    'called' => (int) ($byStatus[QueueStatus::Called->value] ?? 0),
                    'completed' => (int) ($byStatus[QueueStatus::Completed->value] ?? 0),
                    'skipped' => (int) ($byStatus[QueueStatus::Skipped->value] ?? 0),
                ];

                $item->today_counts = $counts + [
                    'total' => array_sum($counts),
                ];

                return $item;
            })
            ->filter(
                fn(QueueItem $item) =>
                $item->is_active || $item->today_counts['total'] > 0
            )
            ->values();
    }

    /**
     * Data grafik: 14 hari, 8 minggu (mulai Senin), dan 12 bulan terakhir.
     * Setiap titik: label (sumbu), title (tooltip), total (dibuat), completed (selesai).
     *
     * @return array{daily: array<int, array<string, mixed>>, weekly: array<int, array<string, mixed>>, monthly: array<int, array<string, mixed>>}
     */
    public function charts(): array
    {
        // Satu query untuk seluruh rentang terjauh (12 bulan), dikelompokkan per hari.
        $perDay = Queue::query()
            ->where('issued_at', '>=', now()->startOfMonth()->subMonths(11))
            ->selectRaw("DATE(issued_at) as day, COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->groupByRaw('DATE(issued_at)')
            ->get()
            ->mapWithKeys(fn($row) => [(string) $row->day => [(int) $row->total, (int) $row->completed]]);

        $sum = function (CarbonInterface $from, CarbonInterface $to) use ($perDay): array {
            $total = 0;
            $completed = 0;

            for ($date = $from->copy()->startOfDay(); $date->lte($to); $date->addDay()) {
                [$t, $c] = $perDay[$date->toDateString()] ?? [0, 0];
                $total += $t;
                $completed += $c;
            }

            return [$total, $completed];
        };

        $daily = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            [$total, $completed] = $sum($day, $day);

            $daily[] = [
                'label' => $day->translatedFormat('d M'),
                'title' => $day->translatedFormat('l, d F Y'),
                'total' => $total,
                'completed' => $completed,
            ];
        }

        $weekly = [];
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = now()->startOfWeek()->subWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();
            [$total, $completed] = $sum($weekStart, $weekEnd);

            $weekly[] = [
                'label' => $weekStart->translatedFormat('d M'),
                'title' => 'Minggu ' . $weekStart->translatedFormat('d M') . ' - ' . $weekEnd->translatedFormat('d M Y'),
                'total' => $total,
                'completed' => $completed,
            ];
        }

        $monthly = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = now()->startOfMonth()->subMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            [$total, $completed] = $sum($monthStart, $monthEnd);

            $monthly[] = [
                'label' => $monthStart->translatedFormat('M y'),
                'title' => $monthStart->translatedFormat('F Y'),
                'total' => $total,
                'completed' => $completed,
            ];
        }

        return compact('daily', 'weekly', 'monthly');
    }

    /** 10 antrean terbaru (relasi item sudah dimuat). @return Collection<int, Queue> */
    public function recentQueues(int $limit = 10): Collection
    {
        return Queue::query()
            ->with('item')
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /** Aktivitas terbaru. @return Collection<int, ActivityLog> */
    public function recentActivities(int $limit = 10): Collection
    {
        return ActivityLog::query()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
