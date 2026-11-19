<?php

namespace Tests\Feature;

use App\Models\Queue;
use App\Models\QueueItem;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\QueueNumberService;
use Database\Seeders\WebsiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class WebsiteSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(WebsiteSettingSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'adminuji',
            'email' => 'admin@example.test',
            'password' => 'Secret123',
            'is_active' => true,
        ]);
    }

    private function save(string $group, array $data)
    {
        return $this->actingAs($this->admin)->put(route('admin.settings.website.update', $group), $data);
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('admin.settings.website.edit'))->assertRedirect(route('login'));
        $this->put(route('admin.settings.website.update', 'general'), [])->assertRedirect(route('login'));
    }

    public function test_every_tab_renders(): void
    {
        foreach (['general', 'contact', 'social', 'display', 'ticket'] as $tab) {
            $this->actingAs($this->admin)
                ->get(route('admin.settings.website.edit', ['tab' => $tab]))
                ->assertOk()
                ->assertSee('Pengaturan Website');
        }

        // Tab tidak dikenal kembali ke General.
        $this->actingAs($this->admin)->get(route('admin.settings.website.edit', ['tab' => 'aneh']))->assertOk()->assertSee('Nama website');
    }

    public function test_unknown_group_returns_404(): void
    {
        $this->actingAs($this->admin)->put('/admin/settings/website/aneh', [])->assertNotFound();
    }

    public function test_general_settings_are_saved_and_applied_site_wide(): void
    {
        $this->save('general', [
            'site_name' => 'Antrean Kita',
            'site_description' => 'Deskripsi baru',
            'site_url' => 'https://antrean.example.test',
            'site_footer' => 'Footer baru',
            'site_copyright' => '© 2026 Antrean Kita',
            'additional_info' => 'Buka Senin sampai Jumat',
        ])->assertRedirect(route('admin.settings.website.edit', ['tab' => 'general']))->assertSessionHas('success');

        $this->assertSame('Antrean Kita', WebsiteSetting::getValue('site_name'));

        $this->get(route('home'))->assertOk()->assertSee('Antrean Kita')->assertSee('Footer baru')->assertSee('Buka Senin sampai Jumat');
        $this->assertDatabaseHas('activity_logs', ['action' => 'settings_updated']);
    }

    public function test_general_validation(): void
    {
        $this->save('general', ['site_name' => ''])->assertSessionHasErrors('site_name');
        $this->save('general', ['site_name' => 'X', 'site_url' => 'bukan url'])->assertSessionHasErrors('site_url');
        $this->save('general', ['site_name' => 'X', 'site_url' => 'javascript:alert(1)'])->assertSessionHasErrors('site_url');

        $this->assertSame('Broseven Queue', WebsiteSetting::getValue('site_name'));
    }

    public function test_logo_can_be_removed_and_its_file_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/logo.png', 'x');
        WebsiteSetting::setValue('site_logo', 'branding/logo.png');

        $this->save('general', ['site_name' => 'Broseven Queue', 'remove_site_logo' => '1'])->assertSessionHasNoErrors();

        $this->assertNull(WebsiteSetting::getValue('site_logo'));
        Storage::disk('public')->assertMissing('branding/logo.png');
    }

    public function test_non_image_logo_and_favicon_are_rejected(): void
    {
        $this->save('general', [
            'site_name' => 'X',
            'site_logo' => \Illuminate\Http\UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            'site_favicon' => \Illuminate\Http\UploadedFile::fake()->create('script.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors(['site_logo', 'site_favicon']);
    }

    public function test_contact_settings_are_saved_and_validated(): void
    {
        $this->save('contact', [
            'contact_address' => 'Jl. Contoh No. 1',
            'contact_email' => 'halo@example.test',
            'contact_phone' => '0411-123456',
            'contact_whatsapp' => '0812-3456-7890',
        ])->assertSessionHas('success');

        $this->get(route('home'))->assertSee('Jl. Contoh No. 1')->assertSee('https://wa.me/6281234567890', false);

        $this->save('contact', ['contact_email' => 'salah', 'contact_phone' => 'abc'])
            ->assertSessionHasErrors(['contact_email', 'contact_phone']);
    }

    public function test_social_links_accept_only_http_and_https(): void
    {
        $this->save('social', ['social_instagram' => 'https://instagram.com/broseven', 'social_facebook' => ''])->assertSessionHas('success');
        $this->get(route('home'))->assertSee('https://instagram.com/broseven', false);

        foreach (['javascript:alert(1)', 'ftp://example.test/x', 'instagram.com/tanpa-skema'] as $bad) {
            $this->save('social', ['social_instagram' => $bad])->assertSessionHasErrors('social_instagram');
        }
    }

    public function test_display_settings_are_saved_and_applied(): void
    {
        $this->save('display', [
            'display_title' => 'Layar Pelayanan',
            'display_marquee' => 'Selamat datang',
            'display_marquee_speed' => 30,
            'display_sound_enabled' => '0',
            'display_number_size' => 'xl',
            'display_list_limit' => 8,
            'display_voice_volume' => '0.8',
            'display_voice_rate' => '1.0',
            'display_voice_lang' => 'en-US',
            'display_call_template' => 'Number {number}, please go to {counter}.',
        ])->assertSessionHas('success');

        $response = $this->get(route('display.index'));

        $response->assertOk();
        $response->assertViewHas('config', fn(array $c) => $c['title'] === 'Layar Pelayanan'
            && $c['number_size'] === 'xl'
            && $c['sound_enabled'] === false
            && $c['list_limit'] === 8
            && $c['lang'] === 'en-US'
            && $c['marquee_speed'] === 30.0);
    }

    public function test_display_validation(): void
    {
        $valid = [
            'display_title' => 'X',
            'display_marquee_speed' => 40,
            'display_number_size' => 'lg',
            'display_list_limit' => 5,
            'display_voice_volume' => 1,
            'display_voice_rate' => 0.9,
            'display_voice_lang' => 'id-ID',
            'display_call_template' => 'Nomor {number}',
        ];

        $cases = [
            'display_marquee_speed' => 5,
            'display_number_size' => 'raksasa',
            'display_list_limit' => 0,
            'display_voice_volume' => 2,
            'display_voice_rate' => 3,
            'display_voice_lang' => 'xx-XX',
            'display_call_template' => 'Tanpa token nomor',
        ];

        foreach ($cases as $field => $bad) {
            $this->save('display', array_merge($valid, [$field => $bad]))->assertSessionHasErrors($field);
        }
    }

    public function test_ticket_settings_are_saved_and_used_by_the_ticket_page(): void
    {
        $this->save('ticket', [
            'ticket_paper_size' => 'a4',
            'ticket_title' => 'KARTU ANTREAN',
            'ticket_footer' => 'Sampai jumpa',
            'ticket_extra_info' => 'Bawa KTP',
            'ticket_show_logo' => '0',
            'ticket_show_waiting_count' => '0',
        ])->assertSessionHas('success');

        $item = QueueItem::create(['name' => 'Cireng Viral', 'prefix' => 'CV', 'number_padding' => 3, 'reset_period' => 'daily', 'is_active' => true]);
        $queue = app(QueueNumberService::class)->issue($item);

        $this->get(URL::signedRoute('queue.ticket', ['queue' => $queue->id]))
            ->assertOk()
            ->assertViewHas('ticket', fn(array $t) => $t['paper'] === 'a4'
                && $t['title'] === 'KARTU ANTREAN'
                && $t['show_logo'] === false
                && $t['show_waiting'] === false);

        $this->save('ticket', ['ticket_paper_size' => 'poster', 'ticket_title' => 'X'])->assertSessionHasErrors('ticket_paper_size');
    }

    public function test_saving_one_group_ignores_fields_of_other_groups(): void
    {
        $this->save('contact', ['contact_email' => 'halo@example.test', 'site_name' => 'Diretas', 'ticket_title' => 'Diretas'])
            ->assertSessionHas('success');

        $this->assertSame('Broseven Queue', WebsiteSetting::getValue('site_name'));
        $this->assertSame('TIKET ANTREAN', WebsiteSetting::getValue('ticket_title'));
        $this->assertSame('halo@example.test', WebsiteSetting::getValue('contact_email'));
    }
}
