@extends('layouts.display')

@section('title', $config['title'])

@push('scripts')
    @vite('resources/js/display.js')
@endpush

@section('content')
    <div id="display" class="display size-{{ $config['number_size'] }}" data-endpoint="{{ $dataUrl }}">

        {{-- ================= Atas ================= --}}
        <header class="display-header">
            <div class="display-brand">
                @if ($site->logo_url)
                    <img src="{{ $site->logo_url }}" alt="{{ $site->name }}">
                @else
                    <span class="brand-mark"><i class="bi bi-people-fill"></i></span>
                    <span>{{ $site->name }}</span>
                @endif
            </div>

            <div class="display-title">
                <h1>{{ $config['title'] }}</h1>
                @if ($only)
                    <span class="display-service">{{ $only->name }}</span>
                @endif
            </div>

            <div class="display-clock">
                <div id="display-time">{{ now()->format('H:i:s') }}</div>
                <div id="display-date">{{ now()->translatedFormat('l, d F Y') }}</div>
            </div>
        </header>

        {{-- ================= Utama ================= --}}
        <main class="display-main">
            <section class="now-panel" id="now-panel" aria-live="polite">
                <div class="now-heading">Sedang Dipanggil</div>
                <div class="now-number is-empty" id="now-number">-</div>
                <div class="now-service" id="now-service">Menunggu panggilan</div>
                <div class="now-counter" id="now-counter"></div>
                <div class="recall-banner" id="recall-banner" hidden>
                    <i class="bi bi-megaphone-fill"></i> Panggilan ulang
                </div>
            </section>

            <section class="recent-panel">
                <h2>Panggilan Terbaru</h2>
                <ul class="display-list recent-list" id="recent-list"></ul>
            </section>

            <aside class="side-panel">
                <section class="side-card">
                    <h2>Status Layanan</h2>
                    <ul class="display-list" id="service-list"></ul>
                </section>

                <section class="side-card">
                    <h2>Antrean Berikutnya</h2>
                    <ul class="display-list" id="next-list"></ul>
                </section>
            </aside>
        </main>

        {{-- ================= Bawah ================= --}}
        <footer class="display-footer">
            <div class="footer-info">
                <strong>{{ $site->name }}</strong>
                @if ($site->url) <span>{{ $site->url }}</span> @endif
                @if ($site->phone) <span><i class="bi bi-telephone"></i> {{ $site->phone }}</span> @endif
            </div>

            <div class="marquee" aria-label="Pesan pelayanan">
                @if ($config['marquee'] !== '')
                    <div class="marquee-track" style="animation-duration: {{ $config['marquee_speed'] }}s">{{ $config['marquee'] }}</div>
                @endif
            </div>

            <div class="connection is-connecting" id="connection-status">
                <span class="status-dot"></span>
                <span id="connection-text">Menghubungkan...</span>
            </div>
        </footer>

        {{-- ================= Kontrol (muncul saat mouse bergerak) ================= --}}
        <div class="display-toolbar">
            <button type="button" class="tool-btn" id="settings-btn" aria-label="Pengaturan suara" title="Pengaturan suara (S)">
                <i class="bi bi-volume-up"></i>
            </button>
            <button type="button" class="tool-btn" id="fullscreen-btn" aria-label="Layar penuh" title="Layar penuh (F)">
                <i class="bi bi-fullscreen"></i>
            </button>
        </div>

        <div class="settings-panel" id="settings-panel" hidden>
            <div class="settings-head">
                <strong>Pengaturan Suara</strong>
                <button type="button" class="tool-btn small" id="settings-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
            </div>

            <p class="settings-note" id="voice-unsupported" hidden>Browser ini tidak mendukung suara (Web Speech API).</p>

            <label class="settings-row">
                <span>Suara panggilan</span>
                <input type="checkbox" id="voice-enabled">
            </label>

            <label class="settings-row stacked">
                <span>Volume <output id="voice-volume-value"></output></span>
                <input type="range" id="voice-volume" min="0" max="1" step="0.1">
            </label>

            <label class="settings-row stacked">
                <span>Kecepatan bicara <output id="voice-rate-value"></output></span>
                <input type="range" id="voice-rate" min="0.5" max="1.5" step="0.1">
            </label>

            <label class="settings-row stacked">
                <span>Bahasa</span>
                <select id="voice-lang">
                    <option value="id-ID">Indonesia (id-ID)</option>
                    <option value="en-US">English US (en-US)</option>
                    <option value="en-GB">English UK (en-GB)</option>
                    <option value="ms-MY">Melayu (ms-MY)</option>
                </select>
            </label>

            <button type="button" class="btn btn-primary btn-block" id="voice-test">
                <i class="bi bi-play-circle"></i> Tes Suara
            </button>
        </div>

        {{-- ================= Layar mulai ================= --}}
        <div class="start-overlay" id="start-overlay">
            <div class="start-card">
                <h2>{{ $config['title'] }}</h2>
                @if ($only) <p class="start-service">{{ $only->name }}</p> @endif
                <p>Klik tombol di bawah untuk memulai. Langkah ini diperlukan agar browser mengizinkan suara panggilan dan layar penuh.</p>
                <button type="button" class="btn btn-primary btn-lg" id="start-btn">
                    <i class="bi bi-play-fill"></i> Mulai Layar Display
                </button>
            </div>
        </div>
    </div>

    <script type="application/json" id="display-config">@json($config)</script>
    <script type="application/json" id="display-initial">@json($snapshot)</script>
@endsection
