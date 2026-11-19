@extends('layouts.public')

@section('title', 'Beranda')

@section('content')
    {{-- ================= Hero ================= --}}
    <section class="hero">
        <div class="container hero-grid">
            <div>
                <span class="hero-eyebrow"><i class="bi bi-lightning-charge-fill"></i> Sistem antrean modern</span>

                <h1>Antre tanpa <mark>berdesakan</mark>, layanan jadi lebih tertib.</h1>

                <p class="lead">
                    {{ $site->description ?: 'Ambil nomor antrean dari perangkat Anda, pantau panggilan di layar, dan dilayani sesuai urutan.' }}
                </p>

                <div class="hero-actions">
                    <a href="{{ route('queue.create') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-ticket-perforated"></i> Ambil Nomor Antrean
                    </a>

                    @if (\Illuminate\Support\Facades\Route::has('display.index'))
                        <a href="{{ route('display.index') }}" class="btn btn-outline btn-lg">
                            <i class="bi bi-tv"></i> Lihat Display Antrean
                        </a>
                    @endif
                </div>

                <div class="hero-stats">
                    <div class="hero-stat">
                        <strong>{{ $totals['waiting'] }}</strong>
                        <span>Antrean menunggu</span>
                    </div>
                    <div class="hero-stat">
                        <strong>{{ $totals['called'] }}</strong>
                        <span>Sedang dipanggil</span>
                    </div>
                    <div class="hero-stat">
                        <strong>{{ $totals['services'] }}</strong>
                        <span>Layanan tersedia</span>
                    </div>
                </div>
            </div>

            <aside class="live-card" aria-label="Antrean yang sedang dipanggil">
                <div class="live-card-title">
                    <span>Sedang Dipanggil</span>
                    <span class="live-dot" title="Data dari database"></span>
                </div>

                <div class="live-list">
                    @forelse ($services->take(5) as $service)
                        <div class="live-row">
                            <span class="service-dot" style="background: {{ $service->safeColor() }}"></span>
                            <span>{{ $service->name }}</span>
                            <span class="live-number {{ $service->current_number ? '' : 'is-empty' }}">
                                {{ $service->current_number ?: '-' }}
                            </span>
                        </div>
                    @empty
                        <div class="live-row">Belum ada layanan aktif.</div>
                    @endforelse
                </div>
            </aside>
        </div>
    </section>

    {{-- ================= Daftar layanan ================= --}}
    <section class="section" id="layanan">
        <div class="container">
            <div class="section-head">
                <h2>Layanan Antrean</h2>
                <p class="text-muted">Pilih layanan yang Anda butuhkan dan ambil nomor antrean dalam hitungan detik.</p>
            </div>

            @if ($services->isEmpty())
                <div class="card">
                    <div class="card-body text-center text-muted">Belum ada layanan antrean yang tersedia.</div>
                </div>
            @else
                <div class="service-grid">
                    @foreach ($services as $service)
                        <article class="service-card">
                            <div class="service-card-top">
                                <span class="service-icon" style="background: color-mix(in srgb, {{ $service->safeColor() }} 30%, #fff)">
                                    <i class="bi {{ $service->icon ?: 'bi-ticket-perforated' }}"></i>
                                </span>

                                @if ($service->is_open)
                                    <span class="badge badge-called">Buka</span>
                                @else
                                    <span class="badge badge-skipped">Tutup</span>
                                @endif
                            </div>

                            <div>
                                <h3>{{ $service->name }}</h3>
                                @if ($service->description)
                                    <p class="text-muted small" style="margin:0">{{ $service->description }}</p>
                                @endif
                            </div>

                            <div class="service-meta">
                                <span><i class="bi bi-hourglass-split"></i> {{ $service->waiting_count }} menunggu</span>
                                <span><i class="bi bi-megaphone"></i> {{ $service->current_number ?: 'Belum ada panggilan' }}</span>
                                <span><i class="bi bi-clock"></i> {{ $service->hoursLabel() }}</span>
                            </div>

                            @if ($service->is_open)
                                <a href="{{ route('queue.create', ['layanan' => $service->slug]) }}" class="btn btn-primary btn-block">
                                    Ambil Nomor
                                </a>
                            @else
                                <span class="btn btn-outline btn-block is-loading" aria-disabled="true">Sedang tutup</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ================= Cara kerja ================= --}}
    <section class="section">
        <div class="container">
            <div class="section-head">
                <h2>Cara Kerjanya</h2>
            </div>

            <div class="steps">
                <div class="card step">
                    <span class="step-number">1</span>
                    <h3>Pilih layanan</h3>
                    <p class="text-muted small" style="margin:0">Tentukan layanan yang ingin Anda gunakan.</p>
                </div>
                <div class="card step">
                    <span class="step-number">2</span>
                    <h3>Ambil nomor</h3>
                    <p class="text-muted small" style="margin:0">Nomor antrean dibuat otomatis. Tiket bisa dicetak.</p>
                </div>
                <div class="card step">
                    <span class="step-number">3</span>
                    <h3>Tunggu panggilan</h3>
                    <p class="text-muted small" style="margin:0">Perhatikan layar atau dengarkan panggilan nomor Anda.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
