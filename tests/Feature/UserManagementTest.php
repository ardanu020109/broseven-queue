<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->admin = $this->makeUser(['name' => 'Admin Utama', 'username' => 'utama', 'email' => 'utama@example.test']);
    }

    private function makeUser(array $overrides = []): User
    {
        static $counter = 0;
        $counter++;

        return User::create(array_merge([
            'name' => 'User ' . $counter,
            'username' => 'user' . $counter,
            'email' => 'user' . $counter . '@example.test',
            'password' => 'Secret123',
            'is_active' => true,
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'username' => 'budi',
            'email' => 'budi@example.test',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_guests_cannot_manage_users(): void
    {
        $user = $this->makeUser();

        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->post(route('admin.users.store'), $this->payload())->assertRedirect(route('login'));
        $this->delete(route('admin.users.destroy', $user))->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_index_lists_and_searches_users(): void
    {
        $this->makeUser(['name' => 'Siti Aminah', 'username' => 'siti', 'email' => 'siti@example.test']);
        $this->makeUser(['name' => 'Rudi Hartono', 'username' => 'rudi', 'email' => 'rudi@example.test']);

        $this->actingAs($this->admin)->get(route('admin.users.index'))
            ->assertOk()->assertSee('Siti Aminah')->assertSee('Rudi Hartono')->assertSee('Anda');

        $this->actingAs($this->admin)->get(route('admin.users.index', ['q' => 'siti']))
            ->assertOk()->assertSee('Siti Aminah')->assertDontSee('Rudi Hartono');
    }

    public function test_create_and_edit_pages_render(): void
    {
        $user = $this->makeUser();

        $this->actingAs($this->admin)->get(route('admin.users.create'))->assertOk()->assertSee('Tambah User');
        $this->actingAs($this->admin)->get(route('admin.users.edit', $user))->assertOk()->assertSee($user->name);
    }

    public function test_store_creates_active_user_with_hashed_password(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), $this->payload())
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $user = User::where('username', 'budi')->firstOrFail();

        $this->assertTrue($user->is_active);
        $this->assertNotSame('Rahasia123', $user->password);
        $this->assertTrue(Hash::check('Rahasia123', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user_created', 'user_id' => $this->admin->id]);
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function invalidUserProvider(): array
    {
        return [
            'nama kosong' => [['name' => ''], 'name'],
            'username berisi spasi' => [['username' => 'a b'], 'username'],
            'username terlalu pendek' => [['username' => 'ab'], 'username'],
            'email tidak valid' => [['email' => 'bukan-email'], 'email'],
            'password terlalu pendek' => [['password' => 'Ab1', 'password_confirmation' => 'Ab1'], 'password'],
            'password tanpa angka' => [['password' => 'HanyaHuruf', 'password_confirmation' => 'HanyaHuruf'], 'password'],
            'konfirmasi tidak cocok' => [['password_confirmation' => 'Berbeda123'], 'password'],
            'password kosong' => [['password' => '', 'password_confirmation' => ''], 'password'],
        ];
    }

    #[DataProvider('invalidUserProvider')]
    public function test_store_validates_input(array $override, string $field): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), $this->payload($override))
            ->assertSessionHasErrors($field);

        $this->assertSame(1, User::count());
    }

    public function test_store_rejects_duplicate_username_email_and_non_image_photo(): void
    {
        $this->makeUser(['username' => 'dipakai', 'email' => 'dipakai@example.test']);

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), $this->payload(['username' => 'dipakai', 'email' => 'dipakai@example.test']))
            ->assertSessionHasErrors(['username', 'email']);

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), $this->payload(['photo' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php')]))
            ->assertSessionHasErrors('photo');
    }

    public function test_update_without_password_keeps_the_old_one(): void
    {
        $user = $this->makeUser(['password' => 'Lama12345']);
        $oldHash = $user->password;

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Nama Baru',
                'username' => $user->username,
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame($oldHash, $user->password);
    }

    public function test_update_can_change_the_password(): void
    {
        $user = $this->makeUser(['password' => 'Lama12345']);

        $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'password' => 'Baru123456',
            'password_confirmation' => 'Baru123456',
            'is_active' => '1',
        ]);

        $this->assertTrue(Hash::check('Baru123456', $user->fresh()->password));
        $this->assertFalse(Hash::check('Lama12345', $user->fresh()->password));
    }

    public function test_update_keeps_own_unique_values_but_rejects_others(): void
    {
        $other = $this->makeUser(['username' => 'lain', 'email' => 'lain@example.test']);

        $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => 'Admin Utama',
            'username' => $this->admin->username,
            'email' => $this->admin->email,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => 'Admin Utama',
            'username' => 'lain',
            'email' => $this->admin->email,
            'is_active' => '1',
        ])->assertSessionHasErrors('username');
    }

    public function test_admin_cannot_deactivate_self_through_the_edit_form(): void
    {
        $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'username' => $this->admin->username,
            'email' => $this->admin->email,
            'is_active' => '0',
        ]);

        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_toggle_flips_other_users_and_blocks_self(): void
    {
        $user = $this->makeUser();

        $this->actingAs($this->admin)->patch(route('admin.users.toggle', $user))->assertSessionHas('success');
        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($this->admin)->patch(route('admin.users.toggle', $user));
        $this->assertTrue($user->fresh()->is_active);

        $this->actingAs($this->admin)->patch(route('admin.users.toggle', $this->admin))->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_deactivated_user_can_no_longer_log_in(): void
    {
        $user = $this->makeUser(['email' => 'nonaktif@example.test', 'password' => 'Secret123']);

        $this->actingAs($this->admin)->patch(route('admin.users.toggle', $user));
        auth()->logout();

        $this->post(route('login.store'), ['email' => 'nonaktif@example.test', 'password' => 'Secret123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deleting_another_user_keeps_their_activity_history(): void
    {
        $user = $this->makeUser();
        $log = ActivityLog::create(['user_id' => $user->id, 'action' => 'queue_called', 'description' => 'Antrean CV001 dipanggil.']);

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $log->id, 'user_id' => null]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user_deleted']);
    }

    public function test_last_active_admin_cannot_be_deleted(): void
    {
        $this->makeUser(['is_active' => false]);

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $this->admin))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_admin_can_delete_own_account_when_another_active_admin_exists(): void
    {
        $this->makeUser();

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $this->admin))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['id' => $this->admin->id]);
        $this->assertGuest();
    }

    public function test_inactive_user_can_be_deleted(): void
    {
        $inactive = $this->makeUser(['is_active' => false]);

        $this->actingAs($this->admin)->delete(route('admin.users.destroy', $inactive))->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $inactive->id]);
    }
}
