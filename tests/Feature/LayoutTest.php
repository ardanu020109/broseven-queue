<?php

namespace Tests\Feature;

use App\Models\ColorSetting;
use App\Models\QueueItem;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\QueueNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
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

    public function test_public_page_uses_site_name_and_theme_colors_from_database(): void
    {
        WebsiteSetting::create(['group' => 'general', 'key' => 'site_name', 'value' => 'Antrean Kita', 'type' => 'text']);
        ColorSetting::create(['key' => 'primary_color', 'label' => 'Primary Color', 'value' => '#112233', 'default_value' => '#F6EB61']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Antrean Kita')
            ->assertSee('--primary-color:#112233', false);
    }

    public function test_invalid_stored_color_falls_back_to_default(): void
    {
        ColorSetting::create(['key' => 'primary_color', 'label' => 'Primary Color', 'value' => '#GGGGGG', 'default_value' => '#F6EB61']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('--primary-color:#F6EB61', false)
            ->assertDontSee('#GGGGGG', false);
    }

    public function test_footer_only_renders_safe_http_social_links(): void
    {
        WebsiteSetting::create(['group' => 'social', 'key' => 'social_instagram', 'value' => 'https://instagram.com/broseven', 'type' => 'url']);
        WebsiteSetting::create(['group' => 'social', 'key' => 'social_facebook', 'value' => 'javascript:alert(1)', 'type' => 'url']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://instagram.com/broseven', false)
            ->assertDontSee('javascript:alert(1)', false);
    }

    public function test_whatsapp_number_is_converted_to_international_link(): void
    {
        WebsiteSetting::create(['group' => 'contact', 'key' => 'contact_whatsapp', 'value' => '0812-3456-7890', 'type' => 'text']);

        $this->get(route('home'))->assertSee('https://wa.me/6281234567890', false);
    }

    public function test_admin_layout_renders_menu_and_waiting_notification(): void
    {
        $item = QueueItem::create([
            'name' => 'Cireng Viral',
            'prefix' => 'CV',
            'number_padding' => 5,
            'reset_period' => 'daily',
            'is_active' => true,
        ]);

        app(QueueNumberService::class)->issue($item);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Master Antrean')
            ->assertSee('Pengaturan')
            ->assertSee('Cireng Viral');
    }

    public function test_dashboard_statistics_count_todays_queues(): void
    {
        $item = QueueItem::create([
            'name' => 'Customer Service',
            'prefix' => 'CS',
            'number_padding' => 3,
            'reset_period' => 'daily',
            'is_active' => true,
        ]);

        $service = app(QueueNumberService::class);
        $service->issue($item);
        $service->issue($item);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertViewHas('stats', fn (array $stats) => $stats['total'] === 2
                && $stats['waiting'] === 2
                && $stats['services'] === 1);
    }

    public function test_guest_cannot_see_admin_layout(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
