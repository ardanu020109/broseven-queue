<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="{{ $themeStyle }}">
<head>
    @include('layouts.partials.head', ['assets' => ['resources/css/app.css', 'resources/css/auth.css', 'resources/js/ui.js']])
</head>
<body>
    @yield('content')
</body>
</html>
