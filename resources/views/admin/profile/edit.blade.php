@extends('layouts.admin')

@section('title', 'Profil Admin')

@section('content')
    <x-page-header title="Profil Admin" subtitle="Kelola informasi akun dan keamanan Anda." />

    <div class="profile-grid">
        {{-- ============ Data profil ============ --}}
        <section class="card">
            <div class="card-header">
                <h2>Informasi Profil</h2>
                <p>Perbarui nama, username, email, dan foto profil Anda.</p>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" data-loading novalidate>
                    @csrf
                    @method('PUT')

                    <div class="profile-photo-row">
                        <div class="avatar avatar-lg" id="avatar-preview">
                            <img
                                @if ($user->profile_photo_url) src="{{ $user->profile_photo_url }}" @else hidden @endif
                                alt="Foto profil"
                            >
                            <span @if ($user->profile_photo_url) hidden @endif>{{ $user->initials }}</span>
                        </div>

                        <div style="flex:1">
                            <x-form.input
                                name="photo"
                                type="file"
                                label="Foto profil"
                                accept="image/png,image/jpeg,image/webp"
                                data-preview-target="avatar-preview"
                                hint="JPG, PNG, atau WebP. Maksimal 2 MB."
                            />
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="full">
                            <x-form.input name="name" label="Nama" :value="$user->name" required />
                        </div>

                        <x-form.input name="username" label="Username" :value="$user->username" required />
                        <x-form.input name="email" type="email" label="Email" :value="$user->email" required />
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle"></i> Simpan Profil
                    </button>
                </form>

                @if ($user->profile_photo)
                    <form
                        method="POST"
                        action="{{ route('admin.profile.photo.destroy') }}"
                        data-confirm="Hapus foto profil?"
                        style="margin-top:1rem"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger-outline btn-sm">
                            <i class="bi bi-trash"></i> Hapus Foto Profil
                        </button>
                    </form>
                @endif
            </div>
        </section>

        {{-- ============ Ganti password ============ --}}
        <section class="card" id="password">
            <div class="card-header">
                <h2>Ganti Password</h2>
                <p>Gunakan minimal 8 karakter dengan kombinasi huruf dan angka.</p>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('admin.profile.password') }}" data-loading novalidate>
                    @csrf
                    @method('PUT')

                    <x-form.input
                        name="current_password"
                        type="password"
                        label="Password saat ini"
                        bag="updatePassword"
                        autocomplete="current-password"
                        :toggle="true"
                        required
                    />

                    <div class="form-grid">
                        <x-form.input
                            name="password"
                            type="password"
                            label="Password baru"
                            bag="updatePassword"
                            autocomplete="new-password"
                            :toggle="true"
                            required
                        />

                        <x-form.input
                            name="password_confirmation"
                            type="password"
                            label="Konfirmasi password baru"
                            bag="updatePassword"
                            autocomplete="new-password"
                            :toggle="true"
                            required
                        />
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-shield-lock"></i> Ganti Password
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection
