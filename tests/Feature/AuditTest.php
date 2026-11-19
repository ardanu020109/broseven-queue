<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Models\ColorSetting;
use App\Models\Queue;
use App\Models\QueueItem;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\QueueManagementService;
use App\Services\QueueNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'adminuji',
            'email' => 'admin@example.test',
            'password' => 'Secret123',
            'is_active' => true,
        ]);
    }

    /* ------------------------- Kesesuaian dengan spesifikasi ------------------------- */

    public function test_seeders_match_the_specification_defaults(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@brosevenqueue.com')->firstOrFail();
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue($admin->is_active);

        $palette = ColorSetting::palette();
        $this->assertSame('#F6EB61', $palette['primary_color']['value']);
        $this->assertSame('#A8A8A8', $palette['secondary_color']['value']);
        $this->assertSame('#F9C74F', $palette['accent_color']['value']);
        $this->assertSame('#6D6D6D', $palette['dark_color']['value']);
        $this->assertSame('#F9F9F9', $palette['background_color']['value']);

        $this->assertSame('Broseven Queue', WebsiteSetting::getValue('site_name'));
    }

    public function test_queue_status_labels_match_the_specification(): void
    {
        $this->assertSame('Menunggu', QueueStatus::Waiting->label());
        $this->assertSame('Sedang Dipanggil', QueueStatus::Called->label());
        $this->assertSame('Di-skip', QueueStatus::Skipped->label());
        $this->assertSame('Selesai', QueueStatus::Completed->label());
    }

    public function test_timezone_is_makassar_everywhere(): void
    {
        $this->assertSame('Asia/Makassar', config('app.timezone'));
        $this->assertSame('+08:00', config('database.connections.mysql.timezone'));
        $this->assertSame('Asia/Makassar', now()->timezoneName);
    }

    /* ------------------------- Cetak ------------------------- */

    public function test_ticket_print_markup_is_correct_for_every_paper_size(): void
    {
        $item = QueueItem::create(['name' => 'Cireng Viral', 'prefix' => 'CV', 'number_padding' => 3, 'reset_period' => 'daily', 'is_active' => true]);
        $queue = app(QueueNumberService::class)->issue($item);
        $url = URL::signedRoute('queue.ticket', ['queue' => $queue->id]);

        foreach (['58mm' => '58mm auto', '80mm' => '80mm auto', 'a4' => 'A4'] as $paper => $pageSize) {
            WebsiteSetting::setValue('ticket_paper_size', $paper);

            $this->get($url)
                ->assertOk()
                ->assertSee('size: ' . $pageSize, false)
                ->assertSee('ticket--' . $paper, false)
                ->assertSee('id="ticket"', false)
                ->assertSee('CV001')
                ->assertSee('class="ticket-heading no-print"', false)
                ->assertSee('class="ticket-toolbar no-print"', false)
                ->assertSee('class="ticket-buttons no-print"', false)
                ->assertSee('Harap menunggu hingga nomor Anda dipanggil.');
        }
    }

    /* ------------------------- Responsif ------------------------- */

    public function test_every_layout_declares_a_responsive_viewport(): void
    {
        QueueItem::create(['name' => 'Cireng Viral', 'prefix' => 'CV', 'number_padding' => 3, 'reset_period' => 'daily', 'is_active' => true]);

        $this->get(route('home'))->assertSee('name="viewport" content="width=device-width, initial-scale=1"', false);
        $this->get(route('queue.create'))->assertSee('name="viewport"', false);
        $this->get(route('display.index'))->assertSee('name="viewport"', false);
        $this->get(route('login'))->assertSee('name="viewport"', false);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertSee('name="viewport"', false);
    }

    /* ------------------------- Performa (N+1) ------------------------- */

    private function populate(int $items, int $waiting, int $offset): void
    {
        $numbers = app(QueueNumberService::class);
        $manage = app(QueueManagementService::class);

        for ($i = 0; $i < $items; $i++) {
            $number = $offset + $i;

            $item = QueueItem::create([
                'name' => 'Layanan ' . $number,
                'prefix' => 'P' . $number,
                'number_padding' => 3,
                'reset_period' => 'daily',
                'is_active' => true,
                'sort_order' => $number,
            ]);

            for ($n = 0; $n < $waiting + 3; $n++) {
                $numbers->issue($item);
            }

            // Satu selesai, satu di-skip, satu tetap aktif, sisanya menunggu.
            $manage->complete($manage->callNext($item, $this->admin), $this->admin);
            $manage->skip($manage->callNext($item, $this->admin), $this->admin);
            $manage->callNext($item, $this->admin);
        }
    }

    /** @return array<string, int> */
    private function measureQueries(QueueItem $showItem): array
    {
        $pages = [
            'beranda' => fn () => $this->get(route('home')),
            'ambil antrean' => fn () => $this->get(route('queue.create')),
            'display' => fn () => $this->get(route('display.index')),
            'data display' => fn () => $this->getJson(route('display.data')),
            'dashboard' => fn () => $this->get(route('admin.dashboard')),
            'daftar layanan' => fn () => $this->get(route('admin.queue-items.index')),
            'kelola antrean' => fn () => $this->get(route('admin.queue-items.show', $showItem)),
            'daftar user' => fn () => $this->get(route('admin.users.index')),
        ];

        // Pemanasan agar cache pengaturan tidak ikut terhitung.
        foreach ($pages as $page) {
            $page();
        }

        $counts = [];

        foreach ($pages as $name => $page) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $page()->assertOk();

            $counts[$name] = count(DB::getQueryLog());
            DB::disableQueryLog();
        }

        return $counts;
    }

    public function test_query_count_does_not_grow_with_data_volume(): void
    {
        $this->actingAs($this->admin);

        $this->populate(items: 2, waiting: 3, offset: 1);
        $showItem = QueueItem::query()->ordered()->firstOrFail();
        $small = $this->measureQueries($showItem);

        $this->populate(items: 12, waiting: 10, offset: 100);
        $this->assertGreaterThan(100, Queue::count());
        $large = $this->measureQueries($showItem);

        foreach ($small as $page => $count) {
            $this->assertLessThanOrEqual(
                $count + 1,
                $large[$page],
                "Halaman \"{$page}\": jumlah query naik dari {$count} menjadi {$large[$page]} saat data membesar (indikasi N+1)."
            );
        }
    }
}
