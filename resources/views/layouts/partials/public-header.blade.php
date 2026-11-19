@php
    $links = [
        ['label' => 'Beranda', 'route' => 'home', 'match' => ['home']],
        ['label' => 'Ambil Antrean', 'route' => 'queue.*', 'name' => 'queue.create', 'match' => ['queue.*']],
        ['label' => 'Display Antrean', 'route' => 'display.*', 'name' => 'display.index', 'match' => ['display.*']],
    ];
@endphp

<header class="public-header">
    <div class="container">
        <a href="{{ route('home') }}" class="public-brand">
            @if ($site->logo_url)
                <img src="{{ $site->logo_url }}" alt="{{ $site->name }}" class="brand-logo">
            @else
                <span class="brand-mark"><i class="bi bi-people-fill"></i></span>
                <span>{{ $site->name }}</span>
            @endif
        </a>

        <button type="button" class="icon-btn nav-toggle" data-nav-toggle aria-expanded="false" aria-label="Buka menu">
            <i class="bi bi-list"></i>
        </button>

        <nav class="public-nav" aria-label="Menu publik">
            @foreach ($links as $link)
                @php $name = $link['name'] ?? $link['route']; @endphp

                @if (\Illuminate\Support\Facades\Route::has($name))
                    <a href="{{ route($name) }}" class="public-link {{ request()->routeIs(...$link['match']) ? 'active' : '' }}">
                        {{ $link['label'] }}
                    </a>
                @endif
            @endforeach

            @auth
                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline btn-sm">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk Admin
                </a>
            @endauth
        </nav>
    </div>
</header>
