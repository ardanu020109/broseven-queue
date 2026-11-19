@extends('layouts.public')

@section('title', 'Ambil Antrean')

@section('content')
    <div class="container">
        <div class="section-head" style="margin-top:0">
            <h1>Ambil Nomor Antrean</h1>
            <p class="text-muted">Pilih layanan, lalu tekan tombol di bagian bawah. Data diri bersifat opsional.</p>
        </div>

        @if ($services->isEmpty())
            <div class="card">
                <div class="card-body text-center text-muted">Belum ada layanan antrean yang tersedia saat ini.</div>
            </div>
        @else
            @php $selected = (string) old('queue_item_id', $preselected); @endphp

            <form method="POST" action="{{ route('queue.store') }}" data-loading>
                @csrf

                @error('queue_item_id')
                    <x-alert type="danger">{{ $message }}</x-alert>
                @enderror

                <fieldset class="service-picker">
                    <legend class="visually-hidden">Pilih layanan</legend>

                    @foreach ($services as $service)
                        <label class="service-option {{ $service->is_open ? '' : 'is-closed' }}">
                            <input
                                type="radio"
                                name="queue_item_id"
                                value="{{ $service->id }}"
                                @checked($selected === (string) $service->id)
                                @disabled(! $service->is_open)
                                required
                            >

                            <span class="service-option-body">
                                <span class="service-option-top">
                                    <x-service-icon :service="$service" />
                                    <span class="service-check"><i class="bi bi-check-lg"></i></span>
                                </span>

                                <strong>{{ $service->name }}</strong>

                                @if ($service->description)
                                    <span class="text-muted small">{{ $service->description }}</span>
                                @endif

                                <span class="service-meta">
                                    <span><i class="bi bi-hourglass-split"></i> {{ $service->waiting_count }} menunggu</span>
                                    <span><i class="bi bi-clock"></i> {{ $service->hoursLabel() }}</span>
                                </span>

                                @unless ($service->is_open)
                                    <span class="badge badge-skipped" style="align-self:flex-start">Sedang tutup</span>
                                @endunless
                            </span>
                        </label>
                    @endforeach
                </fieldset>

                <section class="card optional-card">
                    <div class="card-header">
                        <h2>Data opsional</h2>
                        <p>Tidak wajib diisi. Isi hanya jika Anda ingin dicatat pada antrean.</p>
                    </div>

                    <div class="card-body">
                        <div class="form-grid">
                            <x-form.input name="customer_name" label="Nama" placeholder="Nama Anda" maxlength="100" autocomplete="name" />
                            <x-form.input name="customer_phone" type="tel" label="Nomor telepon" placeholder="0812xxxxxxx" maxlength="20" autocomplete="tel" />

                            <div class="full">
                                <div class="form-group">
                                    <label for="notes" class="form-label">Catatan</label>
                                    <textarea
                                        id="notes"
                                        name="notes"
                                        rows="2"
                                        maxlength="255"
                                        class="form-control {{ $errors->has('notes') ? 'is-invalid' : '' }}"
                                        placeholder="Keperluan atau catatan singkat"
                                    >{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <small class="form-error">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="queue-submit">
                    <a href="{{ route('home') }}" class="btn btn-outline btn-lg">Kembali</a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-ticket-perforated"></i> Ambil Nomor Antrean
                    </button>
                </div>
            </form>
        @endif
    </div>
@endsection
