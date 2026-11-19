<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.profile.edit', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'username', 'email']);

        if ($request->hasFile('photo')) {
            $newPath = $request->file('photo')->store('profile-photos', 'public');

            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $data['profile_photo'] = $newPath;
        }

        $user->update($data);

        ActivityLog::record('profile_updated', 'Admin memperbarui profil.', $user);

        return redirect()->route('admin.profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Cast "hashed" pada model User mengenkripsi password otomatis.
        $user->update(['password' => $request->validated('password')]);

        $request->session()->forget('must_change_password');

        ActivityLog::record('password_changed', 'Admin mengganti password.', $user);

        return redirect()->route('admin.profile.edit')->with('success', 'Password berhasil diganti.');
    }

    public function destroyPhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
            $user->update(['profile_photo' => null]);

            ActivityLog::record('profile_updated', 'Admin menghapus foto profil.', $user);
        }

        return redirect()->route('admin.profile.edit')->with('success', 'Foto profil dihapus.');
    }
}
