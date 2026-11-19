@extends('layouts.admin')

@section('title', 'Kelola ' . $item->name)

@section('breadcrumb')
    <x-breadcrumb :items="[['Master Antrean', null], ['Item Antrean', route('admin.queue-items.index')], [$item->name, null]]" />
@endsection

@push('scripts')
    @vite('resources/js/queue-manage.js')
@endpush

@section('content')
    @php
        $statusEnum = \App\Enums\QueueStatus::class;
        $tabs = [
            'all' => 'Semua',
            'waiting' => 'Menunggu',
            'called' => 'Sedang Dipanggil',
            'skipped' => 'Di-skip',
            'completed' => 'Selesai',
        ];
        $noWaiting = !$next;
    @endphp

    <x-page-header :title="$item->name" :subtitle="'Prefix ' . $item->prefix . ' · ' . $item->reset_period->label() . ' · Jam ' . $item->hoursLabel()">
        <x-slot:actions>
            <a href="{{ route('admin.queue-items.edit', $item) }}" class="btn btn-outline"><i class="bi bi-pencil"></i> Edit
                Layanan</a>
            <a href="{{ route('admin.queue-items.index') }}" class="btn btn-outline"><i class="bi bi-arrow-left"></i>
                Kembali</a>
        </x-slot:actions>
    </x-page-header>

    @unless ($item->is_active)
        <x-alert type="warning" :dismissible="false">
            Layanan ini <strong>nonaktif</strong>: pengunjung tidak dapat mengambil nomor baru, tetapi antrean yang sudah ada
            tetap dapat dilayani.
        </x-alert>
    @endunless

    @if ($staleCount > 0)
        <x-alert type="warning" :dismissible="false">
            Ada <strong>{{ $staleCount }}</strong> antrean menunggu dari periode sebelumnya (kedaluwarsa). Antrean itu
            tidak ikut
            terpanggil otomatis. Tutup dengan tombol <strong>Skip</strong> pada daftar di bawah.
        </x-alert>
    @endif

    <div class="live-banner" id="live-banner" data-item-id="{{ $item->id }}" hidden>
        <i class="bi bi-broadcast"></i>
        <span>Ada pembaruan antrean pada layanan ini.</span>
        <button type="button" class="link-btn" data-reload>Muat ulang</button>
    </div>

    {{-- ================= Panel utama ================= --}}
    <section class="control-panel">
        <div class="now-card">
            <span class="now-label">Nomor Sekarang</span>
            <div class="now-number {{ $active ? '' : 'is-empty' }}" id="now-number">{{ $active?->queue_number ?? '-' }}
            </div>
            <div class="now-service">{{ $item->name }}</div>

            @if ($active)
                <div class="now-meta">
                    Dipanggil {{ $active->called_at->format('H:i:s') }}
                    @if ($active->counter)
                        &middot; {{ $active->counter }}
                    @endif
                    @if ($active->caller)
                        &middot; oleh {{ $active->caller->name }}
                    @endif
                    @if ($active->recalled_at)
                        &middot; dipanggil lagi {{ $active->recalled_at->format('H:i:s') }}
                    @endif
                </div>
            @else
                <div class="now-meta">Belum ada antrean yang sedang dipanggil.</div>
            @endif
        </div>

        <div class="mini-stats">
            <div class="mini-stat"><strong>{{ $waitingCount }}</strong><span>Menunggu</span></div>
            <div class="mini-stat"><strong>{{ $next?->queue_number ?? '-' }}</strong><span>Berikutnya</span></div>
            <div class="mini-stat"><strong>{{ $today['total'] }}</strong><span>Total hari ini</span></div>
            <div class="mini-stat"><strong>{{ $today['completed'] }}</strong><span>Selesai</span></div>
            <div class="mini-stat"><strong>{{ $today['skipped'] }}</strong><span>Di-skip</span></div>
            <div class="mini-stat">
                <strong>{{ $item->is_open_now ?? ($item->isOpenAt() ? 'Buka' : 'Tutup') }}</strong><span>Status
                    layanan</span></div>
        </div>
    </section>

    {{-- ================= Tombol aksi ================= --}}
    <section class="card action-bar">
        <div class="card-body">
            @error('counter')
                <x-alert type="danger">{{ $message }}</x-alert>
            @enderror

            <div class="action-layout">
                <div class="action-counter">
                    <label for="counter-input" class="form-label">Loket</label>
                    <input id="counter-input" type="text" class="form-control" maxlength="50" value="Loket 1"
                        autocomplete="off">
                </div>

                <div class="action-buttons">
                    <form method="POST" action="{{ route('admin.queue-items.call-next', $item) }}" data-loading>
                        @csrf
                        <input type="hidden" name="counter" data-counter-sync>
                        <button type="submit" class="btn btn-primary btn-lg" @disabled($active || $noWaiting)
                            title="{{ $active ? 'Selesaikan atau skip antrean aktif dahulu' : ($noWaiting ? 'Tidak ada antrean menunggu' : '') }}">
                            <i class="bi bi-megaphone"></i> Panggil Berikutnya
                        </button>
                    </form>

                    @if ($active)
                        <form method="POST" action="{{ route('admin.queue-items.recall', $item) }}" data-loading>
                            @csrf
                            <button type="submit" class="btn btn-outline btn-lg"><i class="bi bi-arrow-repeat"></i> Panggil
                                Lagi</button>
                        </form>

                        <form method="POST" action="{{ route('admin.queues.skip', $active) }}"
                            data-confirm="Skip antrean {{ $active->queue_number }}? Antrean tetap tersimpan dan dapat dipanggil ulang.">
                            @csrf
                            <button type="submit" class="btn btn-danger-outline btn-lg"><i class="bi bi-skip-forward"></i>
                                Skip</button>
                        </form>

                        <form method="POST" action="{{ route('admin.queues.complete', $active) }}"
                            data-confirm="Selesaikan antrean {{ $active->queue_number }}?">
                            @csrf
                            <button type="submit" class="btn btn-dark btn-lg"><i class="bi bi-check2-circle"></i>
                                Selesaikan</button>
                        </form>
                    @else
                        <button type="button" class="btn btn-outline btn-lg" disabled><i class="bi bi-arrow-repeat"></i>
                            Panggil Lagi</button>
                        <button type="button" class="btn btn-danger-outline btn-lg" disabled><i
                                class="bi bi-skip-forward"></i> Skip</button>
                        <button type="button" class="btn btn-dark btn-lg" disabled><i class="bi bi-check2-circle"></i>
                            Selesaikan</button>
                    @endif
                </div>
            </div>

            <p class="text-muted small" style="margin:.9rem 0 0">
                Panggil Berikutnya aktif jika tidak ada antrean yang sedang dipanggil. Nama loket tersimpan di browser ini.
            </p>
        </div>
    </section>

    {{-- ================= Daftar + samping ================= --}}
    <div class="dash-grid">
        <section class="card">
            <div class="card-header table-head">
                <div>
                    <h2>Daftar Antrean</h2>
                    <p>{{ $date->translatedFormat('l, d F Y') }}{{ $date->isToday() ? ' (hari ini)' : '' }}</p>
                </div>

                <form method="GET" action="{{ route('admin.queue-items.show', $item) }}" class="date-filter">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="date" name="date" class="form-control" value="{{ $date->toDateString() }}"
                        max="{{ now()->toDateString() }}" data-autosubmit aria-label="Tanggal">
                </form>
            </div>

            <div class="tabs" role="tablist">
                @foreach ($tabs as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}"
                        class="tab {{ $status === $key ? 'active' : '' }}">
                        {{ $label }} <span class="tab-count">{{ $tabCounts[$key] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Layanan</th>
                            <th>Waktu</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($queues as $queue)
                            @php
                                $isWaiting = $queue->status === $statusEnum::Waiting;
                                $isStale = $isWaiting && $queue->period_key !== $periodKey;
                            @endphp
                            <tr>
                                <td class="num">
                                    {{ $queue->queue_number }}
                                    @if ($queue->customer_name)
                                        <br><small class="text-muted"
                                            style="font-weight:400">{{ $queue->customer_name }}</small>
                                    @endif
                                </td>
                                <td>{{ $item->name }}</td>
                                <td>
                                    {{ $queue->issued_at->isToday() ? $queue->issued_at->format('H:i:s') : $queue->issued_at->translatedFormat('d M, H:i') }}
                                    @if ($queue->called_at)
                                        <br><small class="text-muted">Dipanggil {{ $queue->called_at->format('H:i') }}
                                            @if ($queue->counter)
                                                &middot; {{ $queue->counter }}
                                            @endif
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <x-status-badge :status="$queue->status" />
                                    @if ($isStale)
                                        <span class="badge badge-skipped">Kedaluwarsa</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <button type="button" class="btn btn-outline btn-sm"
                                            data-queue-detail="{{ json_encode($queue->detailPayload()) }}">
                                            <i class="bi bi-eye"></i> Detail
                                        </button>

                                        @if ($isWaiting)
                                            @unless ($isStale)
                                                <form method="POST" action="{{ route('admin.queues.call', $queue) }}"
                                                    data-confirm="Panggil antrean {{ $queue->queue_number }} sekarang?">
                                                    @csrf
                                                    <input type="hidden" name="counter" data-counter-sync>
                                                    <button type="submit" class="btn btn-primary btn-sm"
                                                        @disabled($active)
                                                        title="{{ $active ? 'Selesaikan atau skip antrean aktif dahulu' : '' }}">
                                                        <i class="bi bi-megaphone"></i> Panggil
                                                    </button>
                                                </form>
                                            @endunless

                                            <form method="POST" action="{{ route('admin.queues.skip', $queue) }}"
                                                data-confirm="Skip antrean {{ $queue->queue_number }}?">
                                                @csrf
                                                <button type="submit" class="btn btn-danger-outline btn-sm"><i
                                                        class="bi bi-skip-forward"></i> Skip</button>
                                            </form>
                                        @elseif ($queue->status === $statusEnum::Called)
                                            <form method="POST" action="{{ route('admin.queue-items.recall', $item) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-outline btn-sm"><i
                                                        class="bi bi-arrow-repeat"></i> Panggil Lagi</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.queues.skip', $queue) }}"
                                                data-confirm="Skip antrean {{ $queue->queue_number }}?">
                                                @csrf
                                                <button type="submit" class="btn btn-danger-outline btn-sm"><i
                                                        class="bi bi-skip-forward"></i> Skip</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.queues.complete', $queue) }}"
                                                data-confirm="Selesaikan antrean {{ $queue->queue_number }}?">
                                                @csrf
                                                <button type="submit" class="btn btn-dark btn-sm"><i
                                                        class="bi bi-check2-circle"></i> Selesaikan</button>
                                            </form>
                                        @elseif ($queue->status === $statusEnum::Skipped)
                                            <form method="POST" action="{{ route('admin.queues.restore', $queue) }}"
                                                data-confirm="Panggil ulang antrean {{ $queue->queue_number }}?">
                                                @csrf
                                                <input type="hidden" name="counter" data-counter-sync>
                                                <button type="submit" class="btn btn-primary btn-sm"
                                                    @disabled($active)
                                                    title="{{ $active ? 'Selesaikan atau skip antrean aktif dahulu' : '' }}">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Panggil Ulang
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="table-empty">Tidak ada antrean pada filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($queues->hasPages())
                <div class="card-footer">{{ $queues->links('pagination.admin') }}</div>
            @endif
        </section>

        <div class="stack">
            <section class="card">
                <div class="card-header">
                    <h2>Antrean Di-skip</h2>
                    <p>Pilih antrean untuk dipanggil ulang.</p>
                </div>
                <div class="card-body">
                    @forelse ($skipped as $row)
                        <div class="side-row">
                            <div>
                                <strong>{{ $row->queue_number }}</strong><br>
                                <small class="text-muted">di-skip {{ $row->skipped_at?->diffForHumans() }}</small>
                            </div>
                            <form method="POST" action="{{ route('admin.queues.restore', $row) }}"
                                data-confirm="Panggil ulang antrean {{ $row->queue_number }}?">
                                @csrf
                                <input type="hidden" name="counter" data-counter-sync>
                                <button type="submit" class="btn btn-outline btn-sm" @disabled($active)
                                    title="{{ $active ? 'Selesaikan atau skip antrean aktif dahulu' : '' }}">
                                    <i class="bi bi-arrow-counterclockwise"></i> Panggil Ulang
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-muted" style="margin:0">Tidak ada antrean yang di-skip.</p>
                    @endforelse
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <h2>Riwayat Panggilan</h2>
                    <p>12 tindakan terakhir pada layanan ini.</p>
                </div>
                <div class="card-body">
                    @forelse ($histories as $history)
                        <div class="side-row history-row">
                            <div>
                                <strong>{{ $history->queue?->queue_number }}</strong>
                                <span class="badge">{{ $history->action->label() }}</span><br>
                                <small class="text-muted">{{ $history->user?->name ?? 'Sistem' }}</small>
                            </div>
                            <small class="text-muted"
                                title="{{ $history->called_at->translatedFormat('d F Y, H:i:s') }}">
                                {{ $history->called_at->isToday() ? $history->called_at->format('H:i:s') : $history->called_at->translatedFormat('d M, H:i') }}
                            </small>
                        </div>
                    @empty
                        <p class="text-muted" style="margin:0">Belum ada riwayat panggilan.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <x-queue-detail-dialog />
@endsection
