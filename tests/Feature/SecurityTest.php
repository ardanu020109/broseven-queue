<?php

namespace Tests\Feature;

use App\Models\Queue;
use App\Models\QueueItem;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\QueueNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private const XSS = '<script>alert(1)</script>';

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

    private function png(): string
    {
        // PNG 1x1 piksel yang valid (tanpa butuh ekstensi GD).
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    }

    private function image(string $name = 'logo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->png());
    }

    /* ------------------------- Header ------------------------- */

    public function test_security_headers_are_sent_on_every_response(): void
    {
        $response = $this->get(route('home'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=()', $response->headers->get('Permissions-Policy'));
        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_hsts_is_sent_only_over_https(): void
    {
        $this->withServerVariables(['HTTPS' => 'on'])
            ->get(route('home'))
            ->assertHeader('Strict-Transport-Security');
    }

    public function test_admin_pages_are_not_cacheable(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    /* ------------------------- XSS ------------------------- */

    public function test_user_supplied_text_is_escaped_on_every_page(): void
    {
        WebsiteSetting::create(['group' => 'general', 'key' => 'site_name', 'value' => 'Nama ' . self::XSS, 'type' => 'text']);

        $item = QueueItem::create([
            'name' => 'Layanan ' . self::XSS,
            'prefix' => 'XS',
            'description' => self::XSS,
            'number_padding' => 3,
            'reset_period' => 'daily',
            'is_active' => true,
        ]);

        app(QueueNumberService::class)->issue($item, [
            'customer_name' => self::XSS,
            'notes' => self::XSS,
        ]);

        User::create([
            'name' => 'Eve ' . self::XSS,
            'username' => 'eve',
            'email' => 'eve@example.test',
            'password' => 'Secret123',
            'is_active' => true,
        ]);

        $pages = [
            route('home'),
            route('queue.create'),
            route('display.index'),
            route('admin.dashboard'),
            route('admin.queue-items.index'),
            route('admin.queue-items.show', $item),
            route('admin.users.index'),
        ];

        foreach ($pages as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk()->assertDontSee(self::XSS, false);
        }
    }

    public function test_textarea_and_script_breakouts_are_neutralised(): void
    {
        WebsiteSetting::create(['group' => 'general', 'key' => 'site_description', 'value' => '</textarea>' . self::XSS, 'type' => 'textarea']);

        $this->actingAs($this->admin)
            ->get(route('admin.settings.website.edit', ['tab' => 'general']))
            ->assertOk()
            ->assertDontSee('</textarea>' . self::XSS, false);

        QueueItem::create([
            'name' => '</script>' . self::XSS,
            'prefix' => 'JS',
            'number_padding' => 3,
            'reset_period' => 'daily',
            'is_active' => true,
        ]);

        // Data JSON di halaman display tidak boleh dapat menutup tag <script>.
        $this->get(route('display.index'))
            ->assertOk()
            ->assertDontSee('</script>' . self::XSS, false)
            ->assertDontSee('</script><script>', false);
    }

    /* ------------------------- Mass assignment ------------------------- */

    public function test_profile_update_cannot_change_protected_fields(): void
    {
        $oldHash = $this->admin->password;

        $this->actingAs($this->admin)->put(route('admin.profile.update'), [
            'name' => 'Nama Baru',
            'username' => 'adminuji',
            'email' => 'admin@example.test',
            'is_active' => '0',
            'password' => 'Diretas123',
            'email_verified_at' => null,
            'id' => 999,
        ]);

        $user = $this->admin->fresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertSame($oldHash, $user->password);
        $this->assertTrue(Hash::check('Secret123', $user->password));
        $this->assertSame($this->admin->id, $user->id);
    }

    /* ------------------------- Unggahan ------------------------- */

    public function test_website_logo_upload_is_stored_with_random_name_and_replaced_safely(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->put(route('admin.settings.website.update', 'general'), [
            'site_name' => 'Broseven Queue',
            'site_logo' => $this->image('logo-asli.png'),
        ])->assertSessionHasNoErrors();

        $first = WebsiteSetting::getValue('site_logo');
        $this->assertStringStartsWith('branding/', $first);
        $this->assertStringNotContainsString('logo-asli', $first);
        Storage::disk('public')->assertExists($first);

        $this->actingAs($this->admin)->put(route('admin.settings.website.update', 'general'), [
            'site_name' => 'Broseven Queue',
            'site_logo' => $this->image('baru.png'),
        ]);

        $second = WebsiteSetting::getValue('site_logo');
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_php_file_disguised_as_image_is_rejected(): void
    {
        Storage::fake('public');

        $evil = UploadedFile::fake()->createWithContent('evil.png', '<?php echo "pwned"; ?>');

        $this->actingAs($this->admin)->put(route('admin.settings.website.update', 'general'), [
            'site_name' => 'X',
            'site_logo' => $evil,
        ])->assertSessionHasErrors('site_logo');

        $this->actingAs($this->admin)->put(route('admin.profile.update'), [
            'name' => 'X', 'username' => 'adminuji', 'email' => 'admin@example.test', 'photo' => $evil,
        ])->assertSessionHasErrors('photo');

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_profile_user_and_queue_item_images_are_uploaded_to_their_folders(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->put(route('admin.profile.update'), [
            'name' => 'Admin Uji', 'username' => 'adminuji', 'email' => 'admin@example.test', 'photo' => $this->image('foto.png'),
        ])->assertSessionHasNoErrors();
        $this->assertStringStartsWith('profile-photos/', $this->admin->fresh()->profile_photo);

        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Budi', 'username' => 'budi', 'email' => 'budi@example.test',
            'password' => 'Rahasia123', 'password_confirmation' => 'Rahasia123', 'is_active' => '1',
            'photo' => $this->image('budi.png'),
        ])->assertSessionHasNoErrors();
        $this->assertStringStartsWith('profile-photos/', User::where('username', 'budi')->value('profile_photo'));

        $this->actingAs($this->admin)->post(route('admin.queue-items.store'), [
            'name' => 'Cireng Viral', 'prefix' => 'CV', 'icon' => 'bi-cup-hot', 'color' => '#F9C74F',
            'number_format' => '{PREFIX}{NUMBER}', 'number_padding' => 5, 'reset_period' => 'daily',
            'hours_enabled' => '0', 'hours_open' => '08:00', 'hours_close' => '17:00', 'is_active' => '1',
            'logo' => $this->image('cv.png'),
        ])->assertSessionHasNoErrors();
        $this->assertStringStartsWith('queue-items/', QueueItem::where('prefix', 'CV')->value('logo'));

        $this->assertCount(3, Storage::disk('public')->allFiles());
    }

    /* ------------------------- Halaman error ------------------------- */

    public function test_custom_error_pages_are_in_indonesian_and_do_not_leak_internals(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan');

        Route::get('/_uji/403', fn () => abort(403));
        Route::get('/_uji/500', fn () => throw new \RuntimeException('rahasia-internal'));

        config(['app.debug' => false]);

        $this->get('/_uji/403')->assertForbidden()->assertSee('Akses ditolak');

        $this->get('/_uji/500')
            ->assertStatus(500)
            ->assertSee('Terjadi kesalahan')
            ->assertDontSee('rahasia-internal')
            ->assertDontSee('RuntimeException');
    }

    public function test_public_ticket_endpoint_never_exposes_other_visitors_data(): void
    {
        $item = QueueItem::create(['name' => 'Cireng Viral', 'prefix' => 'CV', 'number_padding' => 3, 'reset_period' => 'daily', 'is_active' => true]);
        $numbers = app(QueueNumberService::class);

        $mine = $numbers->issue($item, ['customer_name' => 'Milik Saya']);
        $other = $numbers->issue($item, ['customer_name' => 'Milik Orang Lain', 'customer_phone' => '081234567890']);

        $page = $this->get(\Illuminate\Support\Facades\URL::signedRoute('queue.ticket', ['queue' => $mine->id]))->assertOk()->getContent();

        $this->assertStringNotContainsString('Milik Orang Lain', $page);
        $this->assertStringNotContainsString('081234567890', $page);
        $this->assertSame(2, Queue::count());
        $this->assertNotSame($mine->id, $other->id);
    }
}
