<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function admin(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Admin Uji',
            'username' => 'adminuji',
            'email' => 'admin@example.test',
            'password' => 'Secret123',
            'is_active' => true,
        ], $overrides));
    }

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Masuk Admin');
    }

    public function test_admin_can_login_and_is_redirected_to_dashboard(): void
    {
        $user = $this->admin();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Secret123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->admin();

        $this->from(route('login'))
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'salah'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->admin(['is_active' => false]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Secret123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $user = $this->admin();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'salah']);
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Secret123'])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString('Terlalu banyak', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_default_password_login_sets_warning_flag(): void
    {
        $user = $this->admin(['password' => 'password']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $this->assertTrue(session('must_change_password'));
    }

    public function test_guest_is_redirected_from_admin_pages(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.profile.edit'))->assertRedirect(route('login'));
    }

    public function test_logged_in_user_who_gets_deactivated_is_kicked_out(): void
    {
        $user = $this->admin(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_active_admin_can_open_dashboard_and_profile(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('admin.profile.edit'))->assertOk()->assertSee('Informasi Profil');
    }

    public function test_logout_ends_the_session(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_profile_can_be_updated(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->put(route('admin.profile.update'), [
                'name' => 'Nama Baru',
                'username' => 'namabaru',
                'email' => 'baru@example.test',
            ])
            ->assertRedirect(route('admin.profile.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'namabaru', 'email' => 'baru@example.test']);
    }

    public function test_profile_rejects_duplicate_username_and_non_image_upload(): void
    {
        $this->admin(['username' => 'dipakai', 'email' => 'lain@example.test']);
        $user = $this->admin();

        $this->actingAs($user)
            ->put(route('admin.profile.update'), [
                'name' => 'Admin Uji',
                'username' => 'dipakai',
                'email' => $user->email,
                'photo' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            ])
            ->assertSessionHasErrors(['username', 'photo']);
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->put(route('admin.profile.password'), [
                'current_password' => 'keliru',
                'password' => 'NewPass123',
                'password_confirmation' => 'NewPass123',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');
    }

    public function test_password_can_be_changed_and_clears_default_password_warning(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->withSession(['must_change_password' => true])
            ->put(route('admin.profile.password'), [
                'current_password' => 'Secret123',
                'password' => 'NewPass123',
                'password_confirmation' => 'NewPass123',
            ])
            ->assertRedirect(route('admin.profile.edit'))
            ->assertSessionHas('success')
            ->assertSessionMissing('must_change_password');

        $this->assertTrue(Hash::check('NewPass123', $user->fresh()->password));
    }
}
