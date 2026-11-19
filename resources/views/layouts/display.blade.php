<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="{{ $themeStyle }}">
<head>
    @include('layouts.partials.head', ['assets' => [
        'resources/css/app.css',
        'resources/css/display.css',
        'resources/js/ui.js',
    ]])
</head>
<body class="display-body">
    @yield('content')

    @stack('scripts')
</body>
</html>
