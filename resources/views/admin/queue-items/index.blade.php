@extends('layouts.admin')

@section('title', 'Item Antrean')

@section('breadcrumb')
    <x-breadcrumb :items="[['Master Antrean', null], ['Item Antrean', null]]" />
@endsection

@section('content')
    <x-page-header title="Item Antrean" subtitle="Kelola jenis layanan antrean, format nomor, dan urutan tampilan.">
        <x-slot:actions>
            <a href="{{ route('admin.queue-items.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Layanan
            </a>
        </x-slot:actions>
    </x-page-header>

    <section class="card">
        @if ($items->isEmpty())
            <div class="empty-state">
                <p>Belum ada layanan antrean.</p>
                <a href="{{ route('admin.queue-items.create') }}" class="btn btn-primary">Tambah Layanan Pertama</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Urutan</th>
                            <th>Layanan</th>
                            <th>Contoh Nomor</th>
                            <th>Reset</th>
                            <th>Jam</th>
                            <th>Hari Ini</th>
                            <th>Dipanggil</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>
                                    <div class="reorder">
                                        <form method="POST" action="{{ route('admin.queue-items.move', $item) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="up">
                                            <button type="submit" @disabled($loop->first) aria-label="Naikkan {{ $item->name }}">
                                                <i class="bi bi-chevron-up"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.queue-items.move', $item) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="down">
                                            <button type="submit" @disabled($loop->last) aria-label="Turunkan {{ $item->name }}">
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                <td>
                                    <div class="item-cell">
                                        <x-service-icon :service="$item" class="sm" />
                                        <div>
                                            <strong>{{ $item->name }}</strong>
                                            <small>Prefix {{ $item->prefix }} &middot; /{{ $item->slug }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td><span class="number-sample">{{ $item->formatNumber(1) }}</span></td>
                                <td>{{ $item->reset_period->label() }}</td>
                                <td>{{ $item->hoursLabel() }}</td>
                                <td>
                                    {{ $item->today_total }} dibuat<br>
                                    <small class="text-muted">{{ $item->waiting_count }} menunggu</small>
                                </td>
                                <td class="num">{{ $item->current_number ?: '-' }}</td>

                                <td>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.queue-items.toggle', $item) }}"
                                        class="status-cell"
                                        @if ($item->is_active && $item->waiting_count > 0)
                                            data-confirm="Layanan {{ $item->name }} masih memiliki {{ $item->waiting_count }} antrean menunggu. Nonaktifkan? Pengunjung tidak bisa mengambil nomor baru."
                                        @endif
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="switch {{ $item->is_active ? 'on' : '' }}"
                                            role="switch"
                                            aria-checked="{{ $item->is_active ? 'true' : 'false' }}"
                                            aria-label="Aktifkan atau nonaktifkan {{ $item->name }}"
                                        ><span></span></button>
                                        <span>{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </form>
                                </td>

                                <td>
                                    <div class="row-actions">
                                        @if (\Illuminate\Support\Facades\Route::has('admin.queue-items.show'))
                                            <a href="{{ route('admin.queue-items.show', $item) }}" class="btn btn-primary btn-sm">
                                                <i class="bi bi-sliders"></i> Kelola
                                            </a>
                                        @endif

                                        <a href="{{ route('admin.queue-items.edit', $item) }}" class="btn btn-outline btn-sm">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.queue-items.destroy', $item) }}"
                                            data-confirm="Hapus layanan {{ $item->name }}? Riwayat antrean tetap tersimpan."
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger-outline btn-sm">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
