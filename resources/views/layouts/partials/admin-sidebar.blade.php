@php
    $routeExists = fn (string $name) => \Illuminate\Support\Facades\Route::has($name);

    $menu = [
        [
            'type' => 'link',
            'label' => 'Dashboard',
            'icon' => 'bi-speedometer2',
            'route' => 'admin.dashboard',
            'match' => ['admin.dashboard'],
        ],
        ['type' => 'section', 'label' => 'Manajemen'],
        [
            'type' => 'group',
            'label' => 'Master Antrean',
            'icon' => 'bi-collection',
            'children' => [
                ['label' => 'Item Antrean', 'route' => 'admin.queue-items.index', 'match' => ['admin.queue-items.*', 'admin.queues.*']],
            ],
        ],
        [
            'type' => 'group',
            'label' => 'Pengaturan',
            'icon' => 'bi-gear',
            'children' => [
                ['label' => 'Manajemen User', 'route' => 'admin.users.index', 'match' => ['admin.users.*']],
                ['label' => 'Pengaturan Website', 'route' => 'admin.settings.website.edit', 'match' => ['admin.settings.website.*']],
                ['label' => 'Pengaturan Warna', 'route' => 'admin.settings.colors.edit', 'match' => ['admin.settings.colors.*']],
                ['label' => 'Profil Admin', 'route' => 'admin.profile.edit', 'match' => ['admin.profile.*']],
            ],
        ],
    ];
@endphp

<aside class="sidebar" id="sidebar" aria-label="Menu utama">
    <div class="sidebar-brand">
        @if ($site->logo_url)
            <img src="{{ $site->logo_url }}" alt="{{ $site->name }}" class="brand-logo">
        @else
            <span class="brand-mark"><i class="bi bi-people-fill"></i></span>
            <span class="brand-text">{{ $site->name }}</span>
        @endif
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            @foreach ($menu as $item)
                @if ($item['type'] === 'section')
                    <li class="nav-section">{{ $item['label'] }}</li>

                @elseif ($item['type'] === 'link')
                    @php $active = request()->routeIs(...$item['match']); @endphp
                    <li>
                        @if ($routeExists($item['route']))
                            <a href="{{ route($item['route']) }}" class="nav-link {{ $active ? 'active' : '' }}" title="{{ $item['label'] }}">
                                <i class="bi {{ $item['icon'] }}"></i>
                                <span class="nav-label">{{ $item['label'] }}</span>
                            </a>
                        @else
                            <span class="nav-link is-disabled" title="{{ $item['label'] }}">
                                <i class="bi {{ $item['icon'] }}"></i>
                                <span class="nav-label">{{ $item['label'] }}</span>
                                <span class="nav-soon">Segera</span>
                            </span>
                        @endif
                    </li>

                @else
                    @php
                        $groupActive = collect($item['children'])->contains(
                            fn ($child) => request()->routeIs(...$child['match'])
                        );
                    @endphp
                    <li class="nav-group {{ $groupActive ? 'open' : '' }}">
                        <button type="button" class="nav-link" data-submenu-toggle aria-expanded="{{ $groupActive ? 'true' : 'false' }}" title="{{ $item['label'] }}">
                            <i class="bi {{ $item['icon'] }}"></i>
                            <span class="nav-label">{{ $item['label'] }}</span>
                            <i class="bi bi-chevron-down nav-caret"></i>
                        </button>

                        <ul class="nav-sub">
                            @foreach ($item['children'] as $child)
                                @if ($routeExists($child['route']))
                                    <li>
                                        <a href="{{ route($child['route']) }}" class="nav-sublink {{ request()->routeIs(...$child['match']) ? 'active' : '' }}">
                                            {{ $child['label'] }}
                                        </a>
                                    </li>
                                @else
                                    <li>
                                        <span class="nav-sublink is-disabled">
                                            {{ $child['label'] }}
                                            <span class="nav-soon">Segera</span>
                                        </span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </li>
                @endif
            @endforeach
        </ul>
    </nav>

    <div class="sidebar-footer">{{ $site->name }}</div>
</aside>
