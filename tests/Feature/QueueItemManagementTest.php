<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Queue;
use App\Models\QueueItem;
use App\Models\User;
use App\Services\QueueNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class QueueItemManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Uji',
            'username' => 'adminuji',
            'email' => 'admin@example.test',
            'password' => 'Secret123',
            'is_active' => true,
        ]);
    }

    private function item(array $overrides = []): QueueItem
    {
        static $counter = 0;
        $counter++;

        return QueueItem::create(array_merge([
            'name' => 'Layanan ' . $counter,
            'prefix' => 'L' . $counter,
            'number_padding' => 3,
            'reset_period' => 'daily',
            'is_active' => true,
            'sort_order' => $counter,
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Cireng Viral',
            'prefix' => 'cv',
            'description' => 'Antrean cireng',
            'icon' => 'bi-cup-hot',
            'color' => '#F9C74F',
            'number_format' => '{PREFIX}{NUMBER}',
            'number_padding' => 5,
            'reset_period' => 'daily',
            'hours_enabled' => '0',
            'hours_open' => '08:00',
            'hours_close' => '17:00',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_guest_cannot_access_item_pages(): void
    {
        $this->get(route('admin.queue-items.index'))->assertRedirect(route('login'));
        $this->post(route('admin.queue-items.store'), $this->payload())->assertRedirect(route('login'));
    }

    public function test_index_lists_services_with_sample_number_and_waiting_count(): void
    {
        $item = $this->item(['name' => 'Cireng Viral', 'prefix' => 'CV', 'number_padding' => 5]);
        $service = app(QueueNumberService::class);
        $service->issue($item);
        $service->issue($item);

        $this->actingAs($this->admin())
            ->get(route('admin.queue-items.index'))
            ->assertOk()
            ->assertSee('Cireng Viral')
            ->assertSee('CV00001')
            ->assertSee('2 menunggu');
    }

    public function test_create_and_edit_pages_render(): void
    {
        $item = $this->item();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.queue-items.create'))->assertOk()->assertSee('Tambah Layanan');
        $this->actingAs($admin)->get(route('admin.queue-items.edit', $item))->assertOk()->assertSee($item->name);
    }

    public function test_store_creates_service_with_normalized_data(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.queue-items.store'), $this->payload())
            ->assertRedirect(route('admin.queue-items.index'))
            ->assertSessionHas('success');

        $item = QueueItem::firstOrFail();

        $this->assertSame('CV', $item->prefix);
        $this->assertSame('cireng-viral', $item->slug);
        $this->assertSame(1, $item->sort_order);
        $this->assertSame('daily', $item->reset_period->value);
        $this->assertSame(['enabled' => false, 'open' => '08:00', 'close' => '17:00'], $item->operating_hours);
        $this->assertTrue($item->is_active);
        $this->assertSame('CV00001', $item->formatNumber(1));

        $this->assertDatabaseHas('activity_logs', ['action' => 'queue_item_created', 'user_id' => User::first()->id]);
    }

    public function test_new_service_is_placed_last_in_order(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.queue-items.store'), $this->payload());
        $this->actingAs($admin)->post(route('admin.queue-items.store'), $this->payload(['name' => 'Customer Service', 'prefix' => 'CS']));

        $this->assertSame(['Cireng Viral', 'Customer Service'], QueueItem::ordered()->pluck('name')->all());
        $this->assertSame(2, QueueItem::where('prefix', 'CS')->value('sort_order'));
    }

    public function test_prefix_must_be_unique_among_existing_services_but_reusable_after_delete(): void
    {
        $admin = $this->admin();
        $first = $this->item(['prefix' => 'CV']);

        $this->actingAs($admin)
            ->post(route('admin.queue-items.store'), $this->payload(['name' => 'Lain', 'prefix' => 'cv']))
            ->assertSessionHasErrors('prefix');

        $first->delete();

        $this->actingAs($admin)
            ->post(route('admin.queue-items.store'), $this->payload(['name' => 'Lain', 'prefix' => 'cv']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, QueueItem::where('prefix', 'CV')->count());
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function invalidInputProvider(): array
    {
        return [
            'prefix berisi simbol' => [['prefix' => 'C-V'], 'prefix'],
            'prefix terlalu panjang' => [['prefix' => 'ABCDEFGHIJK'], 'prefix'],
            'format tanpa NUMBER' => [['number_format' => 'ABC'], 'number_format'],
            'format dengan token tidak dikenal' => [['number_format' => '{NUMBER}{FOO}'], 'number_format'],
            'format dengan karakter terlarang' => [['number_format' => '{NUMBER}<b>'], 'number_format'],
            'hasil nomor terlalu panjang' => [['prefix' => 'ABCDEFGHIJ', 'number_format' => '{PREFIX}{PREFIX}{PREFIX}{PREFIX}{NUMBER}'], 'number_format'],
            'digit nol' => [['number_padding' => 0], 'number_padding'],
            'digit sembilan' => [['number_padding' => 9], 'number_padding'],
            'periode reset tidak valid' => [['reset_period' => 'hourly'], 'reset_period'],
            'ikon di luar daftar' => [['icon' => 'bi-<script>'], 'icon'],
            'warna tidak valid' => [['color' => 'red'], 'color'],
            'jam kosong saat diaktifkan' => [['hours_enabled' => '1', 'hours_open' => '', 'hours_close' => ''], 'hours_open'],
            'nama kosong' => [['name' => ''], 'name'],
        ];
    }

    #[DataProvider('invalidInputProvider')]
    public function test_validation_rejects_bad_input(array $override, string $field): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.queue-items.store'), $this->payload($override))
            ->assertSessionHasErrors($field);

        $this->assertSame(0, QueueItem::count());
    }

    public function test_logo_upload_rejects_non_image_files(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.queue-items.store'), $this->payload([
                'logo' => \Illuminate\Http\UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            ]))
            ->assertSessionHasErrors('logo');
    }

    public function test_update_keeps_slug_and_allows_keeping_own_prefix(): void
    {
        $item = $this->item(['name' => 'Cireng Viral', 'prefix' => 'CV']);
        $slug = $item->slug;

        $this->actingAs($this->admin())
            ->put(route('admin.queue-items.update', $item), $this->payload(['name' => 'Cireng Super', 'prefix' => 'CV']))
            ->assertRedirect(route('admin.queue-items.index'))
            ->assertSessionHas('success');

        $item->refresh();

        $this->assertSame('Cireng Super', $item->name);
        $this->assertSame($slug, $item->slug);
        $this->assertSame('CV', $item->prefix);
    }

    public function test_update_cannot_take_another_services_prefix(): void
    {
        $this->item(['prefix' => 'CS']);
        $item = $this->item(['prefix' => 'CV']);

        $this->actingAs($this->admin())
            ->put(route('admin.queue-items.update', $item), $this->payload(['prefix' => 'cs']))
            ->assertSessionHasErrors('prefix');
    }

    public function test_update_can_remove_existing_logo_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('queue-items/old.png', 'x');

        $item = $this->item(['logo' => 'queue-items/old.png']);

        $this->actingAs($this->admin())
            ->put(route('admin.queue-items.update', $item), $this->payload(['prefix' => $item->prefix, 'remove_logo' => '1']))
            ->assertSessionHasNoErrors();

        $this->assertNull($item->fresh()->logo);
        Storage::disk('public')->assertMissing('queue-items/old.png');
    }

    public function test_toggle_flips_active_state_and_is_logged(): void
    {
        $item = $this->item();
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.queue-items.toggle', $item));
        $this->assertFalse($item->fresh()->is_active);

        $this->actingAs($admin)->patch(route('admin.queue-items.toggle', $item));
        $this->assertTrue($item->fresh()->is_active);

        $this->assertSame(2, ActivityLog::where('action', 'queue_item_toggled')->count());
    }

    public function test_reorder_swaps_neighbours_and_ignores_boundaries(): void
    {
        $a = $this->item(['name' => 'A', 'sort_order' => 1]);
        $b = $this->item(['name' => 'B', 'sort_order' => 2]);
        $c = $this->item(['name' => 'C', 'sort_order' => 3]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.queue-items.move', $c), ['direction' => 'up']);
        $this->assertSame(['A', 'C', 'B'], QueueItem::ordered()->pluck('name')->all());

        $this->actingAs($admin)->post(route('admin.queue-items.move', $a), ['direction' => 'up']);
        $this->assertSame(['A', 'C', 'B'], QueueItem::ordered()->pluck('name')->all());

        $this->actingAs($admin)->post(route('admin.queue-items.move', $a), ['direction' => 'down']);
        $this->assertSame(['C', 'A', 'B'], QueueItem::ordered()->pluck('name')->all());

        $this->actingAs($admin)->post(route('admin.queue-items.move', $b), ['direction' => 'sideways'])
            ->assertSessionHasErrors('direction');
    }

    public function test_reorder_normalizes_duplicate_sort_orders(): void
    {
        $a = $this->item(['name' => 'A', 'sort_order' => 0]);
        $b = $this->item(['name' => 'B', 'sort_order' => 0]);
        $c = $this->item(['name' => 'C', 'sort_order' => 0]);

        $this->actingAs($this->admin())->post(route('admin.queue-items.move', $c), ['direction' => 'up']);

        $this->assertSame(['A', 'C', 'B'], QueueItem::ordered()->pluck('name')->all());
        $this->assertSame([1, 2, 3], QueueItem::ordered()->pluck('sort_order')->all());
    }

    public function test_cannot_delete_service_with_waiting_queues(): void
    {
        $item = $this->item();
        app(QueueNumberService::class)->issue($item);

        $this->actingAs($this->admin())
            ->delete(route('admin.queue-items.destroy', $item))
            ->assertRedirect(route('admin.queue-items.index'))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('queue_items', ['id' => $item->id]);
    }

    public function test_service_without_open_queues_is_soft_deleted_and_history_is_kept(): void
    {
        $item = $this->item();
        $queue = app(QueueNumberService::class)->issue($item);
        $queue->update(['status' => 'completed']);

        $this->actingAs($this->admin())
            ->delete(route('admin.queue-items.destroy', $item))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('queue_items', ['id' => $item->id]);
        $this->assertSame(1, Queue::count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'queue_item_deleted']);

        $this->get(route('home'))->assertViewHas('services', fn($services) => $services->isEmpty());
    }

    public function test_deleted_service_cannot_be_edited(): void
    {
        $item = $this->item();
        $item->delete();

        $this->actingAs($this->admin())
            ->get(route('admin.queue-items.edit', $item))
            ->assertNotFound();
    }
}
