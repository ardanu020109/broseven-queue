<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Queue;
use App\Models\QueueItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

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
        return QueueItem::create(array_merge([
            'name' => 'Cireng Viral',
            'prefix' => 'CV',
            'number_padding' => 5,
            'reset_period' => 'daily',
            'is_active' => true,
        ], $overrides));
    }

    private function queueRow(QueueItem $item, array $overrides = []): Queue
    {
        $this->sequence++;

        return Queue::create(array_merge([
            'queue_item_id' => $item->id,
            'queue_number' => 'T' . $this->sequence,
            'sequence_number' => $this->sequence,
            'period_key' => 'test',
            'status' => 'waiting',
            'issued_at' => now(),
        ], $overrides));
    }

    private function dashboard()
    {
        return $this->actingAs($this->admin())->get(route('admin.dashboard'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_renders_all_sections(): void
    {
        $this->dashboard()
            ->assertOk()
            ->assertSee('Statistik Antrean')
            ->assertSee('Per Layanan Hari Ini')
            ->assertSee('Antrean Terbaru')
            ->assertSee('Aktivitas Terbaru')
            ->assertSee('Belum ada antrean.');
    }

    public function test_summary_counts_todays_queues_by_status(): void
    {
        $item = $this->item();

        $this->queueRow($item, ['status' => 'waiting']);
        $this->queueRow($item, ['status' => 'waiting']);
        $this->queueRow($item, ['status' => 'called']);
        $this->queueRow($item, ['status' => 'completed']);
        $this->queueRow($item, ['status' => 'completed']);
        $this->queueRow($item, ['status' => 'completed']);
        $this->queueRow($item, ['status' => 'skipped']);

        // Antrean kemarin tidak ikut dihitung.
        $this->queueRow($item, ['status' => 'completed', 'issued_at' => now()->subDay()]);

        $this->dashboard()->assertViewHas('stats', fn (array $s) => $s['total'] === 7
            && $s['waiting'] === 2
            && $s['called'] === 1
            && $s['completed'] === 3
            && $s['skipped'] === 1
            && $s['services'] === 1);
    }

    public function test_service_breakdown_hides_inactive_services_without_queues(): void
    {
        $active = $this->item();
        $this->item(['name' => 'Nonaktif Kosong', 'prefix' => 'NK', 'is_active' => false]);
        $inactiveWithQueue = $this->item(['name' => 'Nonaktif Ada', 'prefix' => 'NA', 'is_active' => false]);

        $this->queueRow($active, ['status' => 'waiting']);
        $this->queueRow($inactiveWithQueue, ['status' => 'completed']);

        $this->dashboard()->assertViewHas('services', function ($services) {
            return $services->count() === 2
                && $services->pluck('name')->contains('Cireng Viral')
                && $services->pluck('name')->contains('Nonaktif Ada')
                && ! $services->pluck('name')->contains('Nonaktif Kosong')
                && $services->firstWhere('name', 'Cireng Viral')->today_counts['waiting'] === 1;
        });
    }

    public function test_chart_series_have_expected_shape_and_totals(): void
    {
        $item = $this->item();

        $this->queueRow($item, ['status' => 'completed', 'issued_at' => now()]);
        $this->queueRow($item, ['status' => 'waiting', 'issued_at' => now()]);
        $this->queueRow($item, ['status' => 'completed', 'issued_at' => now()->subDays(3)]);
        $this->queueRow($item, ['status' => 'completed', 'issued_at' => now()->subDays(400)]);

        $charts = $this->dashboard()->viewData('charts');

        $this->assertCount(14, $charts['daily']);
        $this->assertCount(8, $charts['weekly']);
        $this->assertCount(12, $charts['monthly']);

        // Titik terakhir = hari ini; 3 hari lalu berada di indeks 10.
        $this->assertSame(2, $charts['daily'][13]['total']);
        $this->assertSame(1, $charts['daily'][13]['completed']);
        $this->assertSame(1, $charts['daily'][10]['total']);

        // Data 400 hari lalu berada di luar rentang manapun.
        $this->assertSame(3, array_sum(array_column($charts['weekly'], 'total')));
        $this->assertSame(3, array_sum(array_column($charts['monthly'], 'total')));
        $this->assertSame(2, array_sum(array_column($charts['weekly'], 'completed')));
    }

    public function test_recent_queues_are_limited_to_ten_and_newest_first(): void
    {
        $item = $this->item();

        for ($i = 1; $i <= 12; $i++) {
            $this->queueRow($item, ['queue_number' => 'N' . $i, 'issued_at' => now()->subMinutes(100 - $i)]);
        }

        $this->dashboard()->assertViewHas('recentQueues', fn ($queues) => $queues->count() === 10
            && $queues->first()->queue_number === 'N12'
            && $queues->last()->queue_number === 'N3');
    }

    public function test_recent_queues_table_exposes_detail_data(): void
    {
        $item = $this->item();
        $this->queueRow($item, ['queue_number' => 'CV00001', 'customer_name' => 'Budi', 'counter' => 'Loket 2']);

        $this->dashboard()
            ->assertSee('CV00001')
            ->assertSee('Budi')
            ->assertSee('data-queue-detail', false);
    }

    public function test_activity_feed_shows_actor_or_visitor(): void
    {
        $admin = $this->admin();

        ActivityLog::create(['user_id' => null, 'action' => 'queue_created', 'description' => 'Antrean CV00001 dibuat oleh pengunjung.']);
        ActivityLog::create(['user_id' => $admin->id, 'action' => 'queue_called', 'description' => 'Antrean CV00001 dipanggil.']);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Antrean CV00001 dibuat oleh pengunjung.')
            ->assertSee('Pengunjung')
            ->assertSee('Antrean CV00001 dipanggil.')
            ->assertSee('Admin Uji');
    }

    public function test_activity_log_icon_and_tone_mapping(): void
    {
        $log = new ActivityLog(['action' => 'queue_skipped']);
        $this->assertSame('bi-skip-forward', $log->icon());
        $this->assertSame('danger', $log->tone());

        $unknown = new ActivityLog(['action' => 'something_else']);
        $this->assertSame('bi-activity', $unknown->icon());
        $this->assertSame('primary', $unknown->tone());
    }
}
