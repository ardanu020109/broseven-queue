@extends('layouts.public')

@section('title', 'Tiket ' . $queue->queue_number)

@push('styles')
    {{-- Ukuran halaman awal sesuai pengaturan. JS memperbaruinya saat ukuran kertas diganti. --}}
    <style id="page-size-style">
        @page { size: {{ ['58mm' => '58mm auto', '80mm' => '80mm auto', 'a4' => 'A4'][$ticket['paper']] }}; margin: {{ $ticket['paper'] === 'a4' ? '12mm' : '0' }}; }
    </style>
    @vite('resources/css/print.css')
@endpush

@push('scripts')
    @vite('resources/js/queue.js')
@endpush

@section('content')
    @php
        $item = $queue->item;
        $logo = $ticket['logo_url'] ?? $site->logo_url;
    @endphp

    <div class="container ticket-page">
        <div class="ticket-heading no-print">
            <span class="ticket-success"><i class="bi bi-check-circle-fill"></i></span>
            <h1>Nomor antrean Anda sudah dibuat</h1>
            <p class="text-muted">Cetak atau simpan tiket ini, lalu tunggu hingga nomor Anda dipanggil.</p>
        </div>

        <div class="ticket-toolbar no-print">
            <label for="paper-size" class="form-label" style="margin:0">Ukuran kertas</label>
            <select id="paper-size" class="form-control" style="width:auto">
                @foreach (['80mm' => 'Thermal 80 mm', '58mm' => 'Thermal 58 mm', 'a4' => 'A4'] as $value => $label)
                    <option value="{{ $value }}" @selected($ticket['paper'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="ticket-stage">
            <article class="ticket ticket--{{ $ticket['paper'] }}" id="ticket" aria-label="Tiket antrean {{ $queue->queue_number }}">
                <header class="ticket-header">
                    @if ($ticket['show_logo'] && $logo)
                        <img class="ticket-logo" src="{{ $logo }}" alt="{{ $site->name }}">
                    @endif
                    <div class="ticket-site">{{ $site->name }}</div>
                    <div class="ticket-title">{{ $ticket['title'] }}</div>
                </header>

                <div class="ticket-divider"></div>

                <div class="ticket-service">{{ $item->name }}</div>
                <div class="ticket-number">{{ $queue->queue_number }}</div>

                <div class="ticket-divider"></div>

                <dl class="ticket-meta">
                    <div><dt>Tanggal</dt><dd>{{ $queue->issued_at->translatedFormat('d F Y') }}</dd></div>
                    <div><dt>Jam</dt><dd>{{ $queue->issued_at->format('H:i:s') }} {{ $queue->issued_at->format('T') }}</dd></div>
                    @if ($ticket['show_waiting'])
                        <div><dt>Antrean di depan Anda</dt><dd>{{ $ahead }}</dd></div>
                    @endif
                </dl>

                @if ($site->address)
                    <div class="ticket-location">{{ $site->address }}</div>
                @endif

                <div class="ticket-divider"></div>

                <p class="ticket-note">Harap menunggu hingga nomor Anda dipanggil.</p>

                @if ($ticket['footer'])
                    <p class="ticket-footer">{{ $ticket['footer'] }}</p>
                @endif

                @if ($ticket['extra'])
                    <p class="ticket-extra">{!! nl2br(e($ticket['extra'])) !!}</p>
                @endif
            </article>
        </div>

        <div class="ticket-status no-print">
            <span>Status saat ini:</span>
            <x-status-badge :status="$queue->status" />
            @if ($ticket['show_waiting'])
                <span class="text-muted">| {{ $ahead }} antrean di depan Anda</span>
            @endif
        </div>

        <div class="ticket-buttons no-print">
            <button type="button" id="print-ticket" class="btn btn-primary btn-lg">
                <i class="bi bi-printer"></i> Cetak Tiket
            </button>
            <a href="{{ route('queue.create') }}" class="btn btn-outline btn-lg">
                <i class="bi bi-plus-circle"></i> Ambil Antrean Baru
            </a>
            <a href="{{ route('home') }}" class="btn btn-outline btn-lg">
                <i class="bi bi-house"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
@endsection
