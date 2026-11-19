@php
    $isActive = filter_var(old('is_active', ($user->exists ? $user->is_active : true) ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="queue-item-form" data-loading novalidate>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <section class="card">
        <div class="card-header">
            <h2>Data Akun</h2>
        </div>

        <div class="card-body">
            <div class="profile-photo-row">
                <div class="avatar avatar-lg" id="avatar-preview">
                    <img @if ($user->profile_photo_url) src="{{ $user->profile_photo_url }}" @else hidden @endif alt="Foto profil">
                    <span @if ($user->profile_photo_url) hidden @endif>{{ $user->exists ? $user->initials : 'A' }}</span>
                </div>

                <div style="flex:1">
                    <x-form.input
                        name="photo"
                        type="file"
                        label="Foto profil"
                        accept="image/png,image/jpeg,image/webp"
                        data-preview-target="avatar-preview"
                        hint="JPG, PNG, atau WebP, maksimal 2 MB."
                    />

                    @if ($user->profile_photo)
                        <label class="checkbox">
                            <input type="checkbox" name="remove_photo" value="1" @checked(old('remove_photo'))>
                            Hapus foto saat ini
                        </label>
                    @endif
                </div>
            </div>

            <div class="form-grid">
                <div class="full">
                    <x-form.input name="name" label="Nama" :value="$user->name" maxlength="100" required />
                </div>

                <x-form.input name="username" label="Username" :value="$user->username" maxlength="50" autocomplete="off" required />
                <x-form.input name="email" type="email" label="Email" :value="$user->email" maxlength="255" autocomplete="off" required />
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <h2>Password</h2>
            <p>Minimal 8 karakter dengan kombinasi huruf dan angka.@if ($user->exists) Kosongkan jika tidak diubah.@endif</p>
        </div>

        <div class="card-body">
            <div class="form-grid">
                <x-form.input
                    name="password"
                    type="password"
                    label="{{ $user->exists ? 'Password baru' : 'Password' }}"
                    autocomplete="new-password"
                    :toggle="true"
                    :required="! $user->exists"
                />

                <x-form.input
                    name="password_confirmation"
                    type="password"
                    label="Konfirmasi password"
                    autocomplete="new-password"
                    :toggle="true"
                    :required="! $user->exists"
                />
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-body">
            <label class="checkbox">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($isActive) @disabled($isSelf)>
                Akun aktif (dapat login)
            </label>

            @if ($isSelf)
                <p class="form-hint" style="margin:.5rem 0 0">Anda tidak dapat menonaktifkan akun Anda sendiri.</p>
            @endif
        </div>
    </section>

    <div class="form-actions">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> {{ $submitLabel }}</button>
    </div>
</form>
