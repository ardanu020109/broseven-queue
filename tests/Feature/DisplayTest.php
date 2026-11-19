<?php

namespace Tests\Feature;

use App\Models\QueueItem;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\QueueManagementService;
use App\Services\QueueNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisplayTest extends TestCase
{
    use RefreshDatabase;

    private QueueNumberService $numbers;
    private QueueManagementService $manage;
    private User $admin;

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
    }

    private function item(string $name = 'Cireng Viral', string $prefix = 'CV', array $overrides = []): QueueItem
    {
        return QueueItem::create(array_merge([
            'name' => $name,
            'prefix' => $prefix,
            'number_padding' => 3,
            'reset_period' => 'daily',
            'is_active' => true,
        ], $overrides));
    }

    public function test_display_page_renders_with_config_and_initial_snapshot(): void
    {
        $this->item();

        $this->get(route('display.index'))
            ->assertOk()
            ->assertSee('display-config', false)
            ->assertSee('display-initial', false)
            ->assertSee('Display Antrean')
            ->assertSee('Mulai Layar Display')
            ->assertViewHas('config', fn (array $c) => $c['number_size'] === 'lg'
                && $c['sound_enabled'] === true
                && $c['lang'] === 'id-ID'
                && $c['list_limit'] === 5);
    }

    public function test_display_is_public_and_needs_no_login(): void
    {
        $this->get(route('display.index'))->assertOk();
        $this->getJson(route('display.data'))->assertOk();
    }

    public function test_unknown_service_slug_returns_404(): void
    {
        $this->get(route('display.index', ['layanan' => 'tidak-ada']))->assertNotFound();
        $this->getJson(route('display.data', ['layanan' => 'tidak-ada']))->assertNotFound();
    }

    public function test_service_filter_limits_the_snapshot(): void
    {
        $a = $this->item();
        $this->item('Customer Service', 'CS');

        $this->getJson(route('display.data'))->assertJsonCount(2, 'services');

        $this->getJson(route('display.data', ['layanan' => $a->slug]))
            ->assertJsonCount(1, 'services')
            ->assertJsonPath('services.0.name', 'Cireng Viral');
    }

    public function test_empty_snapshot_has_the_expected_shape(): void
    {
        $this->item();

        $this->getJson(route('display.data'))
            ->assertOk()
            ->assertJsonPath('now', null)
            ->assertJsonPath('services.0.current', null)
            ->assertJsonPath('services.0.waiting', 0)
            ->assertJsonPath('next', [])
            ->assertJsonPath('recent', [])
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_snapshot_shows_active_call_waiting_list_and_recent_history(): void
    {
        $item = $this->item();
        $this->numbers->issue($item);
        $this->numbers->issue($item);
        $this->numbers->issue($item);

        $this->manage->callNext($item, $this->admin, 'Loket 2');

        $this->getJson(route('display.data'))
            ->assertOk()
            ->assertJsonPath('now.number', 'CV001')
            ->assertJsonPath('now.service', 'Cireng Viral')
            ->assertJsonPath('now.counter', 'Loket 2')
            ->assertJsonPath('services.0.current', 'CV001')
            ->assertJsonPath('services.0.waiting', 2)
            ->assertJsonCount(2, 'next')
            ->assertJsonPath('next.0.number', 'CV002')
            ->assertJsonPath('next.1.number', 'CV003')
            ->assertJsonPath('recent.0.number', 'CV001')
            ->assertJsonPath('recent.0.action', 'called');
    }

    public function test_main_highlight_follows_the_most_recent_call_or_recall(): void
    {
        $a = $this->item();
        $b = $this->item('Customer Service', 'CS');

        $this->numbers->issue($a);
        $this->numbers->issue($b);

        $this->manage->callNext($a, $this->admin);
        $this->travel(5)->seconds();
        $this->manage->callNext($b, $this->admin);

        $this->getJson(route('display.data'))->assertJsonPath('now.number', 'CS001');

        $this->travel(5)->seconds();
        $this->manage->recall($a, $this->admin);

        $this->getJson(route('display.data'))
            ->assertJsonPath('now.number', 'CV001')
            ->assertJsonPath('recent.0.action', 'recalled');
    }

    public function test_snapshot_never_exposes_personal_data(): void
    {
        $item = $this->item();
        $this->numbers->issue($item, [
            'customer_name' => 'Budi Rahasia',
            'customer_phone' => '081234567890',
            'notes' => 'Catatan pribadi',
        ]);
        $this->numbers->issue($item, ['customer_name' => 'Siti Rahasia']);
        $this->manage->callNext($item, $this->admin);

        $content = $this->getJson(route('display.data'))->getContent();

        $this->assertStringNotContainsString('Budi Rahasia', $content);
        $this->assertStringNotContainsString('Siti Rahasia', $content);
        $this->assertStringNotContainsString('081234567890', $content);
        $this->assertStringNotContainsString('Catatan pribadi', $content);

        $page = $this->get(route('display.index'))->getContent();
        $this->assertStringNotContainsString('Budi Rahasia', $page);
        $this->assertStringNotContainsString('081234567890', $page);
    }

    public function test_next_list_follows_limit_setting_and_ignores_previous_periods(): void
    {
        $item = $this->item();

        WebsiteSetting::create(['group' => 'display', 'key' => 'display_list_limit', 'value' => '2', 'type' => 'number']);

        $this->travelTo(now()->subDay());
        $this->numbers->issue($item);
        $this->travelBack();

        for ($i = 0; $i < 4; $i++) {
            $this->numbers->issue($item);
        }

        $this->getJson(route('display.data'))
            ->assertJsonCount(2, 'next')
            ->assertJsonPath('next.0.number', 'CV001')
            ->assertJsonPath('next.1.number', 'CV002')
            ->assertJsonPath('services.0.waiting', 4);
    }

    public function test_inactive_service_is_hidden_unless_it_has_an_active_call(): void
    {
        $this->item('Aktif', 'AK');
        $this->item('Tersembunyi', 'TS', ['is_active' => false]);

        $withCall = $this->item('Dengan Panggilan', 'DP');
        $this->numbers->issue($withCall);
        $this->manage->callNext($withCall, $this->admin);
        $withCall->update(['is_active' => false]);

        $names = collect($this->getJson(route('display.data'))->json('services'))->pluck('name')->all();

        $this->assertEqualsCanonicalizing(['Aktif', 'Dengan Panggilan'], $names);
    }

    public function test_invalid_display_settings_fall_back_to_safe_values(): void
    {
        foreach ([
            'display_number_size' => 'huge',
            'display_voice_volume' => '9',
            'display_voice_rate' => '0.1',
            'display_marquee_speed' => '1',
            'display_list_limit' => '99',
            'display_voice_lang' => 'bahasa_ngawur',
        ] as $key => $value) {
            WebsiteSetting::create(['group' => 'display', 'key' => $key, 'value' => $value, 'type' => 'text']);
        }

        $this->get(route('display.index'))->assertViewHas('config', fn (array $c) => $c['number_size'] === 'lg'
            && $c['volume'] === 1.0
            && $c['rate'] === 0.5
            && $c['marquee_speed'] === 10.0
            && $c['list_limit'] === 12
            && $c['lang'] === 'id-ID');
    }

    public function test_navigation_links_to_the_display_now_that_it_exists(): void
    {
        $this->get(route('home'))->assertOk()->assertSee(route('display.index'), false);
    }
}
