@php $authUser = auth()->user(); @endphp

<header class="topbar">
    <button type="button" class="icon-btn" data-sidebar-toggle aria-label="Buka atau tutup menu">
        <i class="bi bi-list"></i>
    </button>

    <div class="topbar-spacer"></div>

    <div class="clock" data-clock data-timezone="{{ config('app.timezone') }}">
        <div class="clock-time" data-clock-time>{{ now()->format('H:i:s') }}</div>
        <div class="clock-date" data-clock-date>{{ now()->translatedFormat('l, d F Y') }}</div>
    </div>

    {{-- Notifikasi: antrean menunggu hari ini --}}
    <div class="dropdown">
        <button type="button" class="icon-btn" data-dropdown-toggle aria-expanded="false" aria-label="Notifikasi">
            <i class="bi bi-bell"></i>
            @if ($navWaitingTotal > 0)
                <span class="count-dot">{{ $navWaitingTotal > 99 ? '99+' : $navWaitingTotal }}</span>
            @endif
        </button>

        <div class="dropdown-menu">
            <div class="dropdown-header">
                <strong>Antrean Menunggu</strong>
                <small>Hari ini, {{ now()->translatedFormat('d F Y') }}</small>
            </div>
            <div class="dropdown-divider"></div>

            @forelse ($navWaitingServices as $service)
                @php
                    $serviceUrl = \Illuminate\Support\Facades\Route::has('admin.queue-items.show')
                        ? route('admin.queue-items.show', $service)
                        : null;
                @endphp

                @if ($serviceUrl)
                    <a href="{{ $serviceUrl }}" class="dropdown-item">
                @else
                    <div class="dropdown-item" style="cursor:default">
                @endif
                        <span class="service-dot" style="background: {{ $service->color }}"></span>
                        <span class="grow">{{ $service->name }}</span>
                        <span class="badge badge-waiting">{{ $service->waiting_count }}</span>
                @if ($serviceUrl)
                    </a>
                @else
                    </div>
                @endif
            @empty
                <div class="dropdown-empty">Tidak ada antrean yang menunggu.</div>
            @endforelse
        </div>
    </div>

    {{-- Profil --}}
    <div class="dropdown">
        <button type="button" class="profile-btn" data-dropdown-toggle aria-expanded="false">
            <span class="avatar">
                @if ($authUser->profile_photo_url)
                    <img src="{{ $authUser->profile_photo_url }}" alt="{{ $authUser->name }}">
                @else
                    {{ $authUser->initials }}
                @endif
            </span>
            <span class="meta">
                <strong>{{ $authUser->name }}</strong>
                <small>Administrator</small>
            </span>
            <i class="bi bi-chevron-down" style="font-size:.75rem"></i>
        </button>

        <div class="dropdown-menu">
            <div class="dropdown-header">
                <strong>{{ $authUser->name }}</strong>
                <small>{{ $authUser->email }}</small>
            </div>
            <div class="dropdown-divider"></div>

            <a href="{{ route('admin.profile.edit') }}" class="dropdown-item">
                <i class="bi bi-person-circle"></i> Profil Admin
            </a>

            <div class="dropdown-divider"></div>

            <form method="POST" action="{{ route('logout') }}" data-confirm="Keluar dari akun ini?">
                @csrf
                <button type="submit" class="dropdown-item danger">
                    <i class="bi bi-box-arrow-right"></i> Keluar
                </button>
            </form>
        </div>
    </div>
</header>
