@extends('layouts.auth')

@section('title', 'Masuk Admin')

@section('content')
    @php
        $siteName = \App\Models\WebsiteSetting::getValue('site_name', config('app.name'));
        $logo = \App\Models\WebsiteSetting::getValue('site_logo');
        $siteDescription = \App\Models\WebsiteSetting::getValue('site_description');
    @endphp

    <div class="auth-shell">
        <aside class="auth-brand">
            <div class="auth-logo">
                @if ($site->logo_url)
                    <img src="{{ $site->logo_url }}" alt="{{ $site->name }}">
                @else
                    <span class="auth-logo-mark"><i class="bi bi-people-fill"></i></span>
                    <span>{{ $site->name }}</span>
                @endif
            </div>

            <div class="auth-headline">
                <h2>Kelola antrean dengan tertib dan real-time.</h2>
                <p>{{ $site->description }}</p>
            </div>

            <small>&copy; {{ date('Y') }} {{ $site->name }}</small>
        </aside>

        <main class="auth-panel">
            <div class="auth-card">
                <h1>Masuk Admin</h1>
                <p class="lead">Gunakan akun administrator untuk mengelola antrean.</p>

                <x-flash />

                <form method="POST" action="{{ route('login.store') }}" data-loading novalidate>
                    @csrf

                    <x-form.input name="email" type="email" label="Email" placeholder="nama@email.com"
                        autocomplete="username" autofocus required />

                    <x-form.input name="password" type="password" label="Password" autocomplete="current-password"
                        :toggle="true" required />

                    <div class="auth-row">
                        <label class="checkbox">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            Ingat saya
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk
                    </button>
                </form>
            </div>
        </main>
    </div>
@endsection
