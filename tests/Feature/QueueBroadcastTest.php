<?php

namespace Tests\Feature;

use App\Events\QueueCalled;
use App\Events\QueueCompleted;
use App\Events\QueueCreated;
use App\Events\QueueRecalled;
use App\Events\QueueSkipped;
use App\Models\Queue;
use App\Models\QueueItem;
use App\Models\User;
use App\Services\QueueManagementService;
use App\Services\QueueNumberService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class QueueBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private QueueNumberService $numbers;
    private QueueManagementService $manage;
    private User $admin;
    private QueueItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->numbers = app(QueueNumberService::class);
        $this->manage = app(QueueManagementService::class);

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

    public function test_each_queue_change_dispatches_the_matching_event(): void
    {
        Event::fake([
            QueueCreated::class,
            QueueCalled::class,
            QueueRecalled::class,
            QueueSkipped::class,
            QueueCompleted::class,
        ]);

        $queue = $this->numbers->issue($this->item);
        Event::assertDispatchedTimes(QueueCreated::class, 1);

        $this->manage->callNext($this->item, $this->admin);
        Event::assertDispatched(QueueCalled::class, fn (QueueCalled $e) => $e->queue->queue_number === 'CV00001');

        $this->manage->recall($this->item, $this->admin);
        Event::assertDispatchedTimes(QueueRecalled::class, 1);

        $this->manage->skip($queue, $this->admin);
        Event::assertDispatchedTimes(QueueSkipped::class, 1);

        // Panggil ulang dari daftar skip tampil sebagai panggilan baru di layar.
        $this->manage->restore($queue, $this->admin);
        Event::assertDispatchedTimes(QueueCalled::class, 2);

        $this->manage->complete($queue, $this->admin);
        Event::assertDispatchedTimes(QueueCompleted::class, 1);
    }

    public function test_recall_does_not_also_dispatch_a_called_event(): void
    {
        $this->numbers->issue($this->item);
        $this->manage->callNext($this->item, $this->admin);

        Event::fake([QueueCalled::class, QueueRecalled::class]);

        $this->manage->recall($this->item, $this->admin);

        Event::assertDispatched(QueueRecalled::class);
        Event::assertNotDispatched(QueueCalled::class);
    }

    public function test_event_uses_public_channel_and_exposes_no_personal_data(): void
    {
        $queue = $this->numbers->issue($this->item, [
            'customer_name' => 'Budi Rahasia',
            'customer_phone' => '081234567890',
            'notes' => 'Catatan pribadi',
        ]);
        $queue->update(['counter' => 'Loket 2']);

        $event = new QueueCalled($queue->fresh());
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertSame('queue-display', $channels[0]->name);
        $this->assertSame('queue.called', $event->broadcastAs());

        $payload = $event->broadcastWith();

        $this->assertSame(['type', 'number', 'service_id', 'service', 'counter', 'status', 'at'], array_keys($payload));
        $this->assertSame('CV00001', $payload['number']);
        $this->assertSame('Cireng Viral', $payload['service']);
        $this->assertSame('Loket 2', $payload['counter']);

        $json = json_encode($payload);
        $this->assertStringNotContainsString('Budi Rahasia', $json);
        $this->assertStringNotContainsString('081234567890', $json);
        $this->assertStringNotContainsString('Catatan pribadi', $json);
    }

    public function test_broadcast_failure_never_breaks_the_admin_action(): void
    {
        $this->numbers->issue($this->item);

        // Simulasi Reverb mati: pendengar event melempar kesalahan.
        Event::listen(QueueCalled::class, fn () => throw new RuntimeException('Reverb tidak aktif'));

        $called = $this->manage->callNext($this->item, $this->admin);

        $this->assertSame('called', $called->fresh()->status->value);
        $this->assertSame(1, Queue::where('status', 'called')->count());
    }

    public function test_manage_page_has_live_update_banner_for_its_service(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.queue-items.show', $this->item))
            ->assertOk()
            ->assertSee('live-banner', false)
            ->assertSee('data-item-id="' . $this->item->id . '"', false);
    }
}
