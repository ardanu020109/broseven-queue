@extends('layouts.admin')

@section('title', 'Pengaturan Warna')

@section('breadcrumb')
    <x-breadcrumb :items="[['Pengaturan', null], ['Pengaturan Warna', null]]" />
@endsection

@push('scripts')
    @vite('resources/js/color-settings.js')
@endpush

@section('content')
    @php
        $help = [
            'primary_color' => 'Tombol utama, sorotan, dan elemen merek.',
            'secondary_color' => 'Elemen sekunder dan pembatas.',
            'accent_color' => 'Notifikasi dan aksen penting.',
            'dark_color' => 'Sidebar, footer, dan latar layar display.',
            'background_color' => 'Latar belakang utama halaman.',
            'text_color' => 'Warna teks utama.',
        ];
    @endphp

    <x-page-header title="Pengaturan Warna" subtitle="Perubahan langsung berlaku di beranda, dashboard, halaman antrean, dan display." />

    <div class="live-banner" id="page-preview-banner" hidden>
        <i class="bi bi-eye"></i>
        <span>Mode pratinjau aktif: warna di halaman ini belum disimpan dan hilang saat dimuat ulang.</span>
        <button type="button" class="link-btn" id="page-preview-cancel">Batalkan pratinjau</button>
    </div>

    <div class="colors-layout">
        <section class="card">
            <div class="card-header">
                <h2>Palet Warna</h2>
                <p>Pilih warna atau ketik kode heksadesimal (#RRGGBB).</p>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('admin.settings.colors.update') }}" id="color-form" data-loading novalidate>
                    @csrf
                    @method('PUT')

                    @foreach (\App\Models\ColorSetting::KEYS as $key)
                        @php
                            $color = $colors[$key];
                            $current = strtoupper(old($key, $color->value));
                        @endphp

                        <div class="color-item">
                            <div class="color-item-info">
                                <label for="{{ $key }}" class="form-label" style="margin:0">{{ $color->label }}</label>
                                <small class="form-hint">{{ $help[$key] }}</small>
                            </div>

                            <div class="color-item-controls">
                                <input type="color" value="{{ $current }}" data-picker="{{ $key }}" aria-label="Pilih {{ $color->label }}">
                                <input
                                    type="text"
                                    id="{{ $key }}"
                                    name="{{ $key }}"
                                    value="{{ $current }}"
                                    maxlength="7"
                                    spellcheck="false"
                                    autocomplete="off"
                                    class="form-control hex-input {{ $errors->has($key) ? 'is-invalid' : '' }}"
                                    data-saved="{{ strtoupper($color->value) }}"
                                    data-default="{{ strtoupper($color->default_value) }}"
                                >
                                <button type="button" class="btn btn-outline btn-sm" data-use-default="{{ $key }}" title="Pakai warna default {{ strtoupper($color->default_value) }}">
                                    Default
                                </button>
                            </div>

                            @error($key)
                                <small class="form-error">{{ $message }}</small>
                            @enderror
                        </div>
                    @endforeach

                    <div class="alert alert-warning" id="contrast-box" hidden>
                        <div class="alert-body">
                            <strong>Peringatan kontras</strong>
                            <ul id="contrast-list" class="contrast-list"></ul>
                        </div>
                    </div>

                    <div class="form-actions" style="justify-content:flex-start">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Simpan Warna</button>
                        <button type="button" class="btn btn-outline" id="page-preview-toggle"><i class="bi bi-eye"></i> Preview Perubahan</button>
                    </div>
                </form>

                <form
                    method="POST"
                    action="{{ route('admin.settings.colors.reset') }}"
                    data-confirm="Kembalikan seluruh warna ke default?"
                    style="margin-top:1rem"
                >
                    @csrf
                    <button type="submit" class="btn btn-danger-outline"><i class="bi bi-arrow-counterclockwise"></i> Reset ke Warna Default</button>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2>Pratinjau</h2>
                <p>Contoh tampilan dengan warna di atas.</p>
            </div>

            <div class="card-body">
                <div class="theme-preview" id="theme-preview">
                    <div class="tp-sidebar">
                        <span class="tp-brand"><i class="bi bi-people-fill"></i> Brand</span>
                        <span class="tp-link active">Dashboard</span>
                        <span class="tp-link">Master Antrean</span>
                        <span class="tp-link">Pengaturan</span>
                    </div>

                    <div class="tp-main">
                        <div class="tp-card">
                            <strong>Judul Kartu</strong>
                            <p>Teks contoh untuk memeriksa keterbacaan pada latar halaman.</p>
                            <div class="tp-row">
                                <span class="tp-btn">Tombol Utama</span>
                                <span class="tp-notice">Notifikasi</span>
                                <span class="tp-secondary">Sekunder</span>
                            </div>
                        </div>

                        <div class="tp-display">
                            <small>Display antrean</small>
                            <b>CV00001</b>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
