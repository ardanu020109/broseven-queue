<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="{{ $themeStyle }}">
<head>
    @include('layouts.partials.head', ['assets' => [
        'resources/css/app.css',
        'resources/css/public.css',
        'resources/js/ui.js',
        'resources/js/public.js',
    ]])
    @stack('styles')
</head>
<body class="public-body">
    @include('layouts.partials.public-header')

    <main class="public-main">
        <div class="container"><x-flash /></div>

        @yield('content')
    </main>

    @include('layouts.partials.public-footer')

    @stack('scripts')
</body>
</html>
