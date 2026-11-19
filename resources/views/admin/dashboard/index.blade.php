@extends('layouts.admin')

@section('title', 'Dashboard')

@push('scripts')
    @vite('resources/js/dashboard.js')
@endpush

@section('content')
    <x-page-header title="Dashboard" :subtitle="'Ringkasan antrean hari ini, ' . now()->translatedFormat('l, d F Y') . '.'">
        <x-slot:actions>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">
                <i class="bi bi-arrow-clockwise"></i> Muat Ulang
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- ================= Statistik ================= --}}
    <div class="stat-grid">
        <x-stat-card label="Total Antrean Hari Ini" :value="$stats['total']" icon="bi-people" />
        <x-stat-card label="Menunggu" :value="$stats['waiting']" icon="bi-hourglass-split" tone="accent" />
        <x-stat-card label="Sedang Dipanggil" :value="$stats['called']" icon="bi-megaphone" tone="success" />
        <x-stat-card label="Selesai" :value="$stats['completed']" icon="bi-check2-circle" tone="dark" />
        <x-stat-card label="Di-skip" :value="$stats['skipped']" icon="bi-skip-forward" tone="danger" />
        <x-stat-card label="Jenis Layanan" :value="$stats['services']" icon="bi-collection" />
    </div>

    {{-- ================= Grafik + per layanan ================= --}}
    <div class="dash-grid">
        <section class="card">
            <div class="card-header chart-header">
                <div>
                    <h2>Statistik Antrean</h2>
                    <p>Jumlah antrean yang dibuat dan diselesaikan.</p>
                </div>

                <div class="segmented" role="group" aria-label="Rentang grafik">
                    <button type="button" class="segment active" data-chart-range="daily">Harian</button>
                    <button type="button" class="segment" data-chart-range="weekly">Mingguan</button>
                    <button type="button" class="segment" data-chart-range="monthly">Bulanan</button>
                </div>
            </div>

            <div class="card-body">
                <div class="chart-legend">
                    <span><i class="legend-swatch total"></i> Dibuat</span>
                    <span><i class="legend-swatch done"></i> Selesai</span>
                    <span class="chart-note" id="chart-note"></span>
                </div>

                <div class="chart" id="queue-chart">
                    <div class="chart-tooltip" id="chart-tooltip" hidden></div>
                    <p class="chart-empty" id="chart-empty" hidden>Belum ada data antrean pada rentang ini.</p>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2>Per Layanan Hari Ini</h2>
                <p>Jumlah antrean dibuat pada setiap layanan.</p>
            </div>

            <div class="card-body">
                @php $maxService = max(1, (int) $services->max(fn ($s) => $s->today_counts['total'])); @endphp

                @forelse ($services as $service)
                    @php $counts = $service->today_counts; @endphp
                    <div class="service-row">
                        <div class="service-row-top">
                            <span class="service-dot" style="background: {{ $service->safeColor() }}"></span>
                            <span>{{ $service->name }}</span>
                            @unless ($service->is_active)
                                <span class="badge">Nonaktif</span>
                            @endunless
                            <span class="count">{{ $counts['total'] }}</span>
                        </div>

                        <div class="service-bar">
                            <span
                                style="width: {{ round(($counts['total'] / $maxService) * 100) }}%; background: {{ $service->safeColor() }}"></span>
                        </div>

                        <small>
                            {{ $counts['waiting'] }} menunggu
                            &middot; {{ $counts['called'] }} dipanggil
                            &middot; {{ $counts['completed'] }} selesai
                            &middot; {{ $counts['skipped'] }} skip
                        </small>
                    </div>
                @empty
                    <p class="text-muted" style="margin:0">Belum ada layanan antrean.</p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- ================= Tabel + aktivitas ================= --}}
    <div class="dash-grid">
        <section class="card">
            <div class="card-header">
                <h2>Antrean Terbaru</h2>
                <p>10 antrean terakhir dari seluruh layanan.</p>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Layanan</th>
                            <th>Pelanggan</th>
                            <th>Waktu</th>
                            <th>Status</th>
                            <th>Loket</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentQueues as $queue)
                            <tr>
                                <td class="num">{{ $queue->queue_number }}</td>
                                <td>{{ $queue->item?->name ?? '-' }}</td>
                                <td>{{ $queue->customer_name ?: '-' }}</td>
                                <td>
                                    {{ $queue->issued_at->isToday()
                                        ? $queue->issued_at->format('H:i:s')
                                        : $queue->issued_at->translatedFormat('d M, H:i') }}
                                </td>
                                <td><x-status-badge :status="$queue->status" /></td>
                                <td>{{ $queue->counter ?: '-' }}</td>
                                <td>
                                    <div class="row-actions">
                                        <button type="button" class="btn btn-outline btn-sm"
                                            data-queue-detail="{{ json_encode($queue->detailPayload()) }}">
                                            <i class="bi bi-eye"></i> Detail
                                        </button>

                                        @if ($queue->item && !$queue->item->trashed() && \Illuminate\Support\Facades\Route::has('admin.queue-items.show'))
                                            <a href="{{ route('admin.queue-items.show', $queue->item) }}"
                                                class="btn btn-outline btn-sm">
                                                <i class="bi bi-sliders"></i> Kelola
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="table-empty">Belum ada antrean.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2>Aktivitas Terbaru</h2>
                <p>Riwayat tindakan pada sistem.</p>
            </div>

            <div class="card-body">
                @forelse ($activities as $activity)
                    <div class="activity-item">
                        <span class="stat-icon activity-icon tone-{{ $activity->tone() }}">
                            <i class="bi {{ $activity->icon() }}"></i>
                        </span>

                        <div>
                            <div class="activity-text">{{ $activity->description }}</div>
                            <small class="text-muted"
                                title="{{ $activity->created_at->translatedFormat('d F Y, H:i:s') }}">
                                {{ $activity->user?->name ?? 'Pengunjung' }}
                                &middot; {{ $activity->created_at->diffForHumans() }}
                            </small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted" style="margin:0">Belum ada aktivitas.</p>
                @endforelse
            </div>
        </section>
    </div>

    <x-queue-detail-dialog />

    <script type="application/json" id="dashboard-chart-data">@json($charts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
    <script type="application/json" id="display-config">@json($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
    <script type="application/json" id="display-initial">@json($snapshot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
@endsection
