<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $like = '%' . addcslashes($search, '%_\\') . '%';

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($group) => $group->where('name', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('email', 'like', $like)
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function create(): View
    {
        return view('admin.users.create', ['user' => new User()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->userData();

        if ($request->hasFile('photo')) {
            $data['profile_photo'] = $request->file('photo')->store('profile-photos', 'public');
        }

        $user = User::create($data);
        $user->forceFill(['email_verified_at' => now()])->save();

        ActivityLog::record('user_created', 'User ' . $user->name . ' (@' . $user->username . ') ditambahkan.', $user);

        return redirect()->route('admin.users.index')
            ->with('success', 'User "' . $user->name . '" berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->userData();

        // Admin tidak dapat menonaktifkan akunnya sendiri.
        if ($user->is($request->user())) {
            $data['is_active'] = true;
        }

        if ($request->hasFile('photo')) {
            $newPath = $request->file('photo')->store('profile-photos', 'public');
            $this->deletePhoto($user);
            $data['profile_photo'] = $newPath;
        } elseif ($request->boolean('remove_photo')) {
            $this->deletePhoto($user);
            $data['profile_photo'] = null;
        }

        $user->update($data);

        ActivityLog::record(
            'user_updated',
            'User ' . $user->name . ' (@' . $user->username . ') diperbarui' . (isset($data['password']) ? ', password diubah.' : '.'),
            $user
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User "' . $user->name . '" berhasil diperbarui.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return redirect()->route('admin.users.index')->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        ActivityLog::record(
            'user_toggled',
            'User ' . $user->name . ($user->is_active ? ' diaktifkan.' : ' dinonaktifkan.'),
            $user
        );

        return redirect()->route('admin.users.index')->with(
            'success',
            'User "' . $user->name . '" ' . ($user->is_active ? 'diaktifkan.' : 'dinonaktifkan.')
        );
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Sistem tidak boleh kehilangan seluruh akses administrator.
        $otherActiveAdmins = User::query()->active()->whereKeyNot($user->id)->exists();

        if ($user->is_active && ! $otherActiveAdmins) {
            return redirect()->route('admin.users.index')->with(
                'error',
                'Tidak dapat menghapus admin aktif terakhir. Tambahkan atau aktifkan admin lain terlebih dahulu.'
            );
        }

        $isSelf = $user->is($request->user());

        ActivityLog::record(
            'user_deleted',
            'User ' . $user->name . ' (@' . $user->username . ') dihapus.',
            null,
            ['deleted_user_id' => $user->id]
        );

        $this->deletePhoto($user);
        $user->delete();

        if ($isSelf) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Akun Anda telah dihapus.');
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User "' . $user->name . '" dihapus.');
    }

    private function deletePhoto(User $user): void
    {
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }
    }
}
