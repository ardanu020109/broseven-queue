<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Queue;
use App\Models\QueueItem;
use App\Models\WebsiteSetting;
use App\Services\QueueNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function item(array $overrides = []): QueueItem
    {
        return QueueItem::create(array_merge([
            'name' => 'Cireng Viral',
            'prefix' => 'CV',
            'number_padding' => 5,
            'reset_period' => 'daily',
            'is_active' => true,
        ], $overrides));
    }

    public function test_home_lists_active_services_with_waiting_counts(): void
    {
        $item = $this->item();
        $this->item(['name' => 'Layanan Nonaktif', 'prefix' => 'NX', 'is_active' => false]);

        $service = app(QueueNumberService::class);
        $service->issue($item);
        $service->issue($item);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Cireng Viral')
            ->assertDontSee('Layanan Nonaktif')
            ->assertViewHas('totals', fn (array $t) => $t['waiting'] === 2 && $t['services'] === 1);
    }

    public function test_queue_page_lists_only_active_services(): void
    {
        $this->item();
        $this->item(['name' => 'Layanan Nonaktif', 'prefix' => 'NX', 'is_active' => false]);

        $this->get(route('queue.create'))
            ->assertOk()
            ->assertSee('Cireng Viral')
            ->assertDontSee('Layanan Nonaktif');
    }

    public function test_queue_page_preselects_service_from_slug(): void
    {
        $item = $this->item();

        $this->get(route('queue.create', ['layanan' => $item->slug]))
            ->assertOk()
            ->assertViewHas('preselected', $item->id);
    }

    public function test_visitor_can_take_a_number_without_personal_data(): void
    {
        $item = $this->item();

        $response = $this->post(route('queue.store'), ['queue_item_id' => $item->id]);

        $queue = Queue::firstOrFail();

        $this->assertSame('CV00001', $queue->queue_number);
        $this->assertSame('waiting', $queue->status->value);
        $this->assertNull($queue->customer_name);

        $response->assertRedirect(URL::signedRoute('queue.ticket', ['queue' => $queue->id]));
    }

    public function test_client_cannot_choose_the_queue_number(): void
    {
        $item = $this->item();

        $this->post(route('queue.store'), [
            'queue_item_id' => $item->id,
            'queue_number' => 'ZZ99999',
            'sequence_number' => 999,
            'status' => 'completed',
        ]);

        $queue = Queue::firstOrFail();

        $this->assertSame('CV00001', $queue->queue_number);
        $this->assertSame(1, $queue->sequence_number);
        $this->assertSame('waiting', $queue->status->value);
    }

    public function test_optional_customer_data_is_saved(): void
    {
        $item = $this->item();

        $this->post(route('queue.store'), [
            'queue_item_id' => $item->id,
            'customer_name' => 'Budi',
            'customer_phone' => '0812-3456-7890',
            'notes' => 'Pesanan atas nama Budi',
        ]);

        $this->assertDatabaseHas('queues', [
            'customer_name' => 'Budi',
            'customer_phone' => '0812-3456-7890',
        ]);
    }

    public function test_store_validates_service_and_phone(): void
    {
        $inactive = $this->item(['is_active' => false]);

        $this->post(route('queue.store'), [])->assertSessionHasErrors('queue_item_id');
        $this->post(route('queue.store'), ['queue_item_id' => 9999])->assertSessionHasErrors('queue_item_id');
        $this->post(route('queue.store'), ['queue_item_id' => $inactive->id])->assertSessionHasErrors('queue_item_id');

        $active = $this->item(['name' => 'Customer Service', 'prefix' => 'CS']);
        $this->post(route('queue.store'), ['queue_item_id' => $active->id, 'customer_phone' => 'abc'])
            ->assertSessionHasErrors('customer_phone');

        $this->assertSame(0, Queue::count());
    }

    public function test_closed_service_rejects_new_numbers(): void
    {
        $item = $this->item([
            'operating_hours' => ['enabled' => true, 'open' => '00:00', 'close' => '00:01'],
        ]);

        $this->travelTo(now()->setTime(12, 0));

        $this->post(route('queue.store'), ['queue_item_id' => $item->id])
            ->assertSessionHasErrors('queue_item_id');

        $this->assertSame(0, Queue::count());
    }

    public function test_taking_a_number_is_written_to_the_activity_log_without_a_user(): void
    {
        $item = $this->item();

        $this->post(route('queue.store'), ['queue_item_id' => $item->id]);

        $log = ActivityLog::firstOrFail();

        $this->assertSame('queue_created', $log->action);
        $this->assertNull($log->user_id);
        $this->assertStringContainsString('CV00001', $log->description);
    }

    public function test_ticket_requires_a_valid_signature(): void
    {
        $item = $this->item();
        $queue = app(QueueNumberService::class)->issue($item);

        $this->get(route('queue.ticket', ['queue' => $queue->id]))->assertForbidden();

        $this->get(URL::signedRoute('queue.ticket', ['queue' => $queue->id]))
            ->assertOk()
            ->assertSee('CV00001')
            ->assertSee('Cetak Tiket')
            ->assertSee('Ambil Antrean Baru')
            ->assertSee('Kembali ke Beranda');
    }

    public function test_ticket_id_cannot_be_tampered_with(): void
    {
        $item = $this->item();
        $service = app(QueueNumberService::class);
        $first = $service->issue($item);
        $second = $service->issue($item);

        $url = URL::signedRoute('queue.ticket', ['queue' => $first->id]);
        $tampered = str_replace('/tiket/' . $first->id, '/tiket/' . $second->id, $url);

        $this->get($tampered)->assertForbidden();
    }

    public function test_ticket_shows_number_of_people_ahead(): void
    {
        $item = $this->item();
        $service = app(QueueNumberService::class);
        $service->issue($item);
        $service->issue($item);
        $third = $service->issue($item);

        $this->get(URL::signedRoute('queue.ticket', ['queue' => $third->id]))
            ->assertOk()
            ->assertViewHas('ahead', 2);
    }

    public function test_ticket_paper_size_follows_setting_and_falls_back_safely(): void
    {
        $queue = app(QueueNumberService::class)->issue($this->item());
        $url = URL::signedRoute('queue.ticket', ['queue' => $queue->id]);

        $this->get($url)->assertViewHas('ticket', fn (array $t) => $t['paper'] === '80mm');

        WebsiteSetting::create(['group' => 'ticket', 'key' => 'ticket_paper_size', 'value' => 'A4', 'type' => 'text']);
        $this->get($url)->assertViewHas('ticket', fn (array $t) => $t['paper'] === 'a4');

        WebsiteSetting::setValue('ticket_paper_size', 'poster');
        $this->get($url)->assertViewHas('ticket', fn (array $t) => $t['paper'] === '80mm');
    }

    public function test_taking_numbers_is_rate_limited(): void
    {
        $item = $this->item();

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('queue.store'), ['queue_item_id' => $item->id])->assertRedirect();
        }

        $this->from(route('queue.create'))
            ->post(route('queue.store'), ['queue_item_id' => $item->id])
            ->assertRedirect(route('queue.create'))
            ->assertSessionHasErrors('queue_item_id');

        $this->assertSame(10, Queue::count());
    }
}
