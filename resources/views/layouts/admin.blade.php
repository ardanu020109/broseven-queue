<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="{{ $themeStyle }}">
<head>
    @include('layouts.partials.head', ['assets' => [
        'resources/css/app.css',
        'resources/css/admin.css',
        'resources/js/ui.js',
        'resources/js/admin.js',
    ]])

    {{-- Terapkan status sidebar sebelum render pertama agar tidak berkedip. --}}
    <script>
        (function () {
            try {
                if (window.innerWidth >= 992 && localStorage.getItem('bq.sidebar') === 'collapsed') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (error) {}
        })();
    </script>

    @stack('styles')
</head>
<body class="admin-body">
    <div class="admin-shell">
        @include('layouts.partials.admin-sidebar')

        <div class="sidebar-backdrop" data-sidebar-close></div>

        <div class="admin-main">
            @include('layouts.partials.admin-topbar')

            <div class="admin-content">
                @hasSection('breadcrumb')
                    @yield('breadcrumb')
                @else
                    <x-breadcrumb :items="[[trim($__env->yieldContent('title')), null]]" />
                @endif

                @if (session('must_change_password'))
                    <x-alert type="warning" :dismissible="false">
                        Anda masih memakai password default. Demi keamanan,
                        <a href="{{ route('admin.profile.edit') }}#password">ganti password sekarang</a>.
                    </x-alert>
                @endif

                <x-flash />

                @yield('content')
            </div>

            <footer class="admin-footer">{{ $site->copyright }}</footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
