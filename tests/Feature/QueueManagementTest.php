<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Queue;
use App\Models\QueueItem;
use App\Models\User;
use App\Services\QueueManagementService;
use App\Services\QueueNumberService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueManagementTest extends TestCase
{
    use RefreshDatabase;

    private QueueManagementService $service;
    private User $admin;
    private QueueItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->service = app(QueueManagementService::class);

        $this->admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'adminuji',
            'email' => 'admin@example.test',
            'password' => 'Secret123',
            'is_active' => true,
        ]);

        $this->item = QueueItem::create([
            'name' => 'Cireng Viral',
            'prefix' => 'CV',
            'number_padding' => 5,
            'reset_period' => 'daily',
            'is_active' => true,
        ]);
    }

    /** @return array<int, Queue> */
    private function issue(int $count = 1, ?QueueItem $item = null): array
    {
        $numbers = app(QueueNumberService::class);
        $queues = [];

        for ($i = 0; $i < $count; $i++) {
            $queues[] = $numbers->issue($item ?? $this->item);
        }

        return $queues;
    }

    private function assertRejected(callable $action, string $messagePart): void
    {
        try {
            $action();
            $this->fail('Aksi seharusnya ditolak.');
        } catch (DomainException $e) {
            $this->assertStringContainsString($messagePart, $e->getMessage());
        }
    }

    /* ------------------------- Service ------------------------- */

    public function test_call_next_takes_earliest_waiting_and_records_everything(): void
    {
        [$a, $b] = $this->issue(2);

        $called = $this->service->callNext($this->item, $this->admin, ' Loket 2 ');

        $this->assertSame($a->id, $called->id);
        $this->assertSame('called', $called->status->value);
        $this->assertNotNull($called->called_at);
        $this->assertSame($this->admin->id, $called->called_by);
        $this->assertSame('Loket 2', $called->counter);
        $this->assertSame('waiting', $b->fresh()->status->value);

        $this->assertDatabaseHas('queue_call_histories', ['queue_id' => $a->id, 'user_id' => $this->admin->id, 'action' => 'called']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'queue_called', 'user_id' => $this->admin->id]);
    }

    public function test_cannot_call_next_while_another_queue_is_active(): void
    {
        $this->issue(2);
        $this->service->callNext($this->item, $this->admin);

        $this->assertRejected(fn () => $this->service->callNext($this->item, $this->admin), 'masih sedang dipanggil');

        $this->assertSame(1, Queue::where('status', 'called')->count());
        $this->assertSame(1, Queue::where('status', 'waiting')->count());
    }

    public function test_call_next_without_waiting_queue_is_rejected(): void
    {
        $this->assertRejected(fn () => $this->service->callNext($this->item, $this->admin), 'Tidak ada antrean');
    }

    public function test_complete_then_call_next_moves_on_in_order(): void
    {
        [$a, $b] = $this->issue(2);

        $this->service->callNext($this->item, $this->admin);
        $done = $this->service->complete($a, $this->admin);

        $this->assertSame('completed', $done->status->value);
        $this->assertNotNull($done->completed_at);

        $next = $this->service->callNext($this->item, $this->admin);
        $this->assertSame($b->id, $next->id);

        $this->assertDatabaseHas('queue_call_histories', ['queue_id' => $a->id, 'action' => 'completed']);
    }

    public function test_recall_keeps_status_and_creates_no_new_number(): void
    {
        [$a] = $this->issue(1);
        $this->service->callNext($this->item, $this->admin);

        $recalled = $this->service->recall($this->item, $this->admin);

        $this->assertSame($a->id, $recalled->id);
        $this->assertSame('called', $recalled->status->value);
        $this->assertNotNull($recalled->recalled_at);
        $this->assertSame(1, Queue::count());
        $this->assertDatabaseHas('queue_call_histories', ['queue_id' => $a->id, 'action' => 'recalled']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'queue_recalled']);
    }

    public function test_recall_without_active_queue_is_rejected(): void
    {
        $this->issue(1);

        $this->assertRejected(fn () => $this->service->recall($this->item, $this->admin), 'Tidak ada antrean aktif');
    }

    public function test_skip_active_queue_frees_the_active_slot(): void
    {
        [$a, $b] = $this->issue(2);
        $this->service->callNext($this->item, $this->admin);

        $skipped = $this->service->skip($a, $this->admin);

        $this->assertSame('skipped', $skipped->status->value);
        $this->assertNotNull($skipped->skipped_at);
        $this->assertSame(0, Queue::where('status', 'called')->count());

        $this->assertSame($b->id, $this->service->callNext($this->item, $this->admin)->id);
        $this->assertDatabaseHas('queue_call_histories', ['queue_id' => $a->id, 'action' => 'skipped']);
    }

    public function test_skip_works_on_waiting_but_not_on_completed_or_skipped(): void
    {
        [$a, $b] = $this->issue(2);

        $this->assertSame('skipped', $this->service->skip($b, $this->admin)->status->value);
        $this->assertRejected(fn () => $this->service->skip($b, $this->admin), 'tidak dapat di-skip');

        $this->service->callNext($this->item, $this->admin);
        $this->service->complete($a, $this->admin);
        $this->assertRejected(fn () => $this->service->skip($a, $this->admin), 'tidak dapat di-skip');
    }

    public function test_restore_skipped_queue_becomes_active_without_a_new_number(): void
    {
        [$a, $b] = $this->issue(2);
        $this->service->callNext($this->item, $this->admin);
        $this->service->skip($a, $this->admin);

        $restored = $this->service->restore($a, $this->admin, 'Loket 3');

        $this->assertSame($a->id, $restored->id);
        $this->assertSame('called', $restored->status->value);
        $this->assertSame('Loket 3', $restored->counter);
        $this->assertSame(2, Queue::count());
        $this->assertDatabaseHas('queue_call_histories', ['queue_id' => $a->id, 'action' => 'restored']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'queue_restored']);

        // Antrean berikutnya tetap menunggu dan tidak terpengaruh.
        $this->assertSame('waiting', $b->fresh()->status->value);
    }

    public function test_restore_is_rejected_when_another_queue_is_active_or_status_is_wrong(): void
    {
        [$a, $b] = $this->issue(2);

        $this->service->callNext($this->item, $this->admin);
        $this->service->skip($a, $this->admin);
        $this->service->callNext($this->item, $this->admin); // B aktif

        $this->assertRejected(fn () => $this->service->restore($a, $this->admin), 'masih sedang dipanggil');
        $this->assertRejected(fn () => $this->service->restore($b, $this->admin), 'bukan antrean yang di-skip');
    }

    public function test_completed_queue_cannot_be_reactivated(): void
    {
        [$a] = $this->issue(1);
        $this->service->callNext($this->item, $this->admin);
        $this->service->complete($a, $this->admin);

        $this->assertRejected(fn () => $this->service->callQueue($a, $this->admin), 'tidak dapat dipanggil');
        $this->assertRejected(fn () => $this->service->restore($a, $this->admin), 'bukan antrean yang di-skip');
        $this->assertRejected(fn () => $this->service->skip($a, $this->admin), 'tidak dapat di-skip');
        $this->assertRejected(fn () => $this->service->recall($this->item, $this->admin), 'Tidak ada antrean aktif');
        $this->assertRejected(fn () => $this->service->complete($a, $this->admin), 'Hanya antrean yang sedang dipanggil');
    }

    public function test_complete_requires_an_active_queue(): void
    {
        [$a] = $this->issue(1);

        $this->assertRejected(fn () => $this->service->complete($a, $this->admin), 'Hanya antrean yang sedang dipanggil');
    }

    public function test_call_specific_queue_ignores_order_but_respects_single_active_rule(): void
    {
        [$a, $b, $c] = $this->issue(3);

        $called = $this->service->callQueue($c, $this->admin, 'Loket 1');
        $this->assertSame($c->id, $called->id);

        $this->assertRejected(fn () => $this->service->callQueue($a, $this->admin), 'masih sedang dipanggil');
        $this->assertSame('waiting', $a->fresh()->status->value);
    }

    public function test_stale_waiting_queue_is_not_called_automatically_but_can_be_skipped(): void
    {
        $this->travelTo(now()->subDay());
        [$old] = $this->issue(1);
        $this->travelBack();

        [$today] = $this->issue(1);

        // Hanya antrean periode berjalan yang terpanggil.
        $this->assertSame($today->id, $this->service->callNext($this->item, $this->admin)->id);
        $this->service->complete($today, $this->admin);

        $this->assertRejected(fn () => $this->service->callNext($this->item, $this->admin), 'Tidak ada antrean');
        $this->assertRejected(fn () => $this->service->callQueue($old, $this->admin), 'kedaluwarsa');

        $this->assertSame('skipped', $this->service->skip($old, $this->admin)->status->value);
    }

    public function test_sequential_calls_never_hand_out_the_same_number_twice(): void
    {
        $this->issue(3);
        $handedOut = [];

        for ($i = 0; $i < 3; $i++) {
            $called = $this->service->callNext($this->item, $this->admin);
            $handedOut[] = $called->queue_number;
            $this->service->complete($called, $this->admin);
        }

        $this->assertCount(3, array_unique($handedOut));
        $this->assertSame(['CV00001', 'CV00002', 'CV00003'], $handedOut);
    }

    /* ------------------------- HTTP ------------------------- */

    public function test_guests_cannot_use_queue_actions(): void
    {
        [$a] = $this->issue(1);

        $this->get(route('admin.queue-items.show', $this->item))->assertRedirect(route('login'));
        $this->post(route('admin.queue-items.call-next', $this->item))->assertRedirect(route('login'));
        $this->post(route('admin.queues.skip', $a))->assertRedirect(route('login'));
        $this->post(route('admin.queues.complete', $a))->assertRedirect(route('login'));

        $this->assertSame('waiting', $a->fresh()->status->value);
    }

    public function test_manage_page_shows_big_current_number_and_lists(): void
    {
        $this->issue(3);
        $this->service->callNext($this->item, $this->admin, 'Loket 1');

        $this->actingAs($this->admin)
            ->get(route('admin.queue-items.show', $this->item))
            ->assertOk()
            ->assertSee('Nomor Sekarang')
            ->assertSee('now-number', false)
            ->assertSee('CV00001')
            ->assertSee('Panggil Lagi')
            ->assertSee('Selesaikan')
            ->assertViewHas('waitingCount', 2)
            ->assertViewHas('next', fn (Queue $next) => $next->queue_number === 'CV00002');
    }

    public function test_call_next_via_http_records_counter_and_flashes_success(): void
    {
        $this->issue(2);

        $this->actingAs($this->admin)
            ->from(route('admin.queue-items.show', $this->item))
            ->post(route('admin.queue-items.call-next', $this->item), ['counter' => 'Loket 2'])
            ->assertRedirect(route('admin.queue-items.show', $this->item))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('queues', [
            'queue_number' => 'CV00001',
            'status' => 'called',
            'counter' => 'Loket 2',
            'called_by' => $this->admin->id,
        ]);
    }

    public function test_business_rule_violations_are_shown_as_messages_not_errors(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.queue-items.show', $this->item))
            ->post(route('admin.queue-items.call-next', $this->item))
            ->assertRedirect(route('admin.queue-items.show', $this->item))
            ->assertSessionHas('error');

        $this->issue(2);
        $this->service->callNext($this->item, $this->admin);

        $this->actingAs($this->admin)
            ->post(route('admin.queue-items.call-next', $this->item))
            ->assertSessionHas('error');

        $this->assertSame(1, Queue::where('status', 'called')->count());
    }

    public function test_counter_is_validated(): void
    {
        $this->issue(1);

        $this->actingAs($this->admin)
            ->post(route('admin.queue-items.call-next', $this->item), ['counter' => str_repeat('x', 51)])
            ->assertSessionHasErrors('counter');

        $this->assertSame(0, Queue::where('status', 'called')->count());
    }

    public function test_full_flow_through_http_routes(): void
    {
        [$a, $b] = $this->issue(2);
        $admin = $this->admin;

        $this->actingAs($admin)->post(route('admin.queue-items.call-next', $this->item))->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.queue-items.recall', $this->item))->assertSessionHas('success');
        $this->assertNotNull($a->fresh()->recalled_at);

        $this->actingAs($admin)->post(route('admin.queues.skip', $a))->assertSessionHas('success');
        $this->assertSame('skipped', $a->fresh()->status->value);

        $this->actingAs($admin)->post(route('admin.queues.call', $b))->assertSessionHas('success');
        $this->assertSame('called', $b->fresh()->status->value);

        $this->actingAs($admin)->post(route('admin.queues.complete', $b))->assertSessionHas('success');
        $this->assertSame('completed', $b->fresh()->status->value);

        $this->actingAs($admin)->post(route('admin.queues.restore', $a))->assertSessionHas('success');
        $this->assertSame('called', $a->fresh()->status->value);

        $this->assertSame(2, Queue::count());
        $this->assertGreaterThanOrEqual(5, ActivityLog::where('user_id', $admin->id)->count());
    }

    public function test_tabs_filter_the_list_and_report_counts(): void
    {
        [$a, $b, $c] = $this->issue(3);
        $this->service->callNext($this->item, $this->admin);
        $this->service->skip($a, $this->admin);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.queue-items.show', ['queue_item' => $this->item, 'status' => 'skipped']))
            ->assertOk()
            ->assertViewHas('tabCounts', fn (array $t) => $t['all'] === 3 && $t['skipped'] === 1 && $t['waiting'] === 2);

        $this->assertTrue($response->viewData('queues')->getCollection()->every(fn (Queue $q) => $q->status->value === 'skipped'));
        $this->assertCount(1, $response->viewData('queues')->items());
    }

    public function test_older_open_queues_remain_visible_today_and_invalid_dates_fall_back(): void
    {
        $this->travelTo(now()->subDays(2));
        [$old] = $this->issue(1);
        $this->travelBack();

        $this->actingAs($this->admin)
            ->get(route('admin.queue-items.show', $this->item))
            ->assertOk()
            ->assertSee('Kedaluwarsa')
            ->assertViewHas('staleCount', 1)
            ->assertViewHas('queues', fn ($queues) => $queues->getCollection()->contains('id', $old->id));

        $this->actingAs($this->admin)->get(route('admin.queue-items.show', ['queue_item' => $this->item, 'date' => 'abc']))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.queue-items.show', ['queue_item' => $this->item, 'date' => '2999-01-01']))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.queue-items.show', ['queue_item' => $this->item, 'date' => '2026-02-31']))->assertOk();
    }

    public function test_manage_page_works_for_inactive_service(): void
    {
        $this->item->update(['is_active' => false]);
        $this->issue(0);

        $this->actingAs($this->admin)
            ->get(route('admin.queue-items.show', $this->item))
            ->assertOk()
            ->assertSee('nonaktif');
    }
}
