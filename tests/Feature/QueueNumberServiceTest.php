<?php

namespace Tests\Feature;

use App\Models\Queue;
use App\Models\QueueItem;
use App\Services\QueueNumberService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeItem(array $overrides = []): QueueItem
    {
        return QueueItem::create(array_merge([
            'name' => 'Cireng Viral',
            'prefix' => 'CV',
            'number_padding' => 5,
            'reset_period' => 'daily',
            'is_active' => true,
        ], $overrides));
    }

    private function rawQueue(QueueItem $item, int $sequence, string $status = 'waiting', string $period = 'p1'): Queue
    {
        return Queue::create([
            'queue_item_id' => $item->id,
            'queue_number' => 'X' . $sequence,
            'sequence_number' => $sequence,
            'period_key' => $period,
            'status' => $status,
            'issued_at' => now(),
        ]);
    }

    public function test_numbers_are_sequential_and_formatted(): void
    {
        $item = $this->makeItem();
        $service = app(QueueNumberService::class);

        $this->assertSame('CV00001', $service->issue($item)->queue_number);
        $this->assertSame('CV00002', $service->issue($item)->queue_number);
        $this->assertSame('CV00003', $service->issue($item)->queue_number);
    }

    public function test_sequence_is_independent_per_item(): void
    {
        $service = app(QueueNumberService::class);
        $cv = $this->makeItem();
        $cs = $this->makeItem(['name' => 'Customer Service', 'prefix' => 'CS', 'number_padding' => 3]);

        $service->issue($cv);
        $service->issue($cv);

        $this->assertSame('CS001', $service->issue($cs)->queue_number);
    }

    public function test_daily_reset_restarts_sequence_but_keeps_history(): void
    {
        $service = app(QueueNumberService::class);
        $item = $this->makeItem();

        $this->travelTo(Carbon::now()->setTime(10, 0));
        $service->issue($item);
        $service->issue($item);

        $this->travelTo(Carbon::now()->addDay());
        $next = $service->issue($item);

        $this->assertSame(1, $next->sequence_number);
        $this->assertSame('CV00001', $next->queue_number);
        $this->assertSame(3, Queue::count());
    }

    public function test_never_reset_keeps_counting_across_days(): void
    {
        $service = app(QueueNumberService::class);
        $item = $this->makeItem(['reset_period' => 'never']);

        $this->travelTo(Carbon::now()->setTime(10, 0));
        $service->issue($item);

        $this->travelTo(Carbon::now()->addDays(3));

        $this->assertSame(2, $service->issue($item)->sequence_number);
    }

    public function test_custom_number_format_tokens(): void
    {
        $item = $this->makeItem(['number_format' => '{PREFIX}-{DD}{MM}-{NUMBER}', 'number_padding' => 5]);

        $this->assertSame('CV-1711-00007', $item->formatNumber(7, Carbon::create(2026, 11, 17)));
    }

    public function test_database_rejects_duplicate_sequence_in_same_period(): void
    {
        $item = $this->makeItem();
        $this->rawQueue($item, 1);

        $this->expectException(QueryException::class);
        $this->rawQueue($item, 1);
    }

    public function test_database_allows_only_one_called_queue_per_item(): void
    {
        $item = $this->makeItem();
        $this->rawQueue($item, 1, 'called');
        $second = $this->rawQueue($item, 2, 'waiting');

        $this->expectException(QueryException::class);
        $second->update(['status' => 'called']);
    }

    public function test_inactive_item_cannot_issue_numbers(): void
    {
        $item = $this->makeItem(['is_active' => false]);

        $this->expectException(DomainException::class);
        app(QueueNumberService::class)->issue($item);
    }

    public function test_suggest_prefix(): void
    {
        $this->assertSame('CV', QueueItem::suggestPrefix('Cireng Viral'));
        $this->assertSame('CS', QueueItem::suggestPrefix('Customer Service'));
        $this->assertSame('LP', QueueItem::suggestPrefix('Layanan Pembayaran'));
        $this->assertSame('KON', QueueItem::suggestPrefix('Konsultasi'));
    }
}
