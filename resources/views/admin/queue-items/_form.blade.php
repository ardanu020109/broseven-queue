@php
    $hours = $item->operating_hours ?? [];
    $currentIcon = old('icon', $item->exists ? $item->icon : 'bi-ticket-perforated');
    $currentColor = old('color', $item->color ?? '#F9C74F');
    $currentPeriod = old('reset_period', $item->reset_period?->value ?? 'daily');
    $currentFormat = old('number_format', $item->number_format ?? '{PREFIX}{NUMBER}');
    $currentPadding = (int) old('number_padding', $item->number_padding ?? 3);
    $hoursOn = filter_var(old('hours_enabled', ! empty($hours['enabled']) ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
    $isActive = filter_var(old('is_active', ($item->exists ? $item->is_active : true) ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
@endphp

<form
    id="queue-item-form"
    class="queue-item-form"
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    data-loading
    novalidate
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    {{-- ============ Informasi dasar ============ --}}
    <section class="card">
        <div class="card-header">
            <h2>Informasi Dasar</h2>
            <p>Nama dan prefix yang tampil pada nomor antrean.</p>
        </div>

        <div class="card-body">
            <div class="form-grid">
                <x-form.input name="name" label="Nama layanan" :value="$item->name" maxlength="100" required />

                <div>
                    <x-form.input
                        name="prefix"
                        label="Kode prefix"
                        :value="$item->prefix"
                        maxlength="10"
                        autocomplete="off"
                        style="text-transform: uppercase"
                        data-auto="{{ $item->exists ? '0' : '1' }}"
                        hint="Huruf kapital dan angka, maksimal 10. Disarankan otomatis dari nama, tetap bisa diubah."
                        required
                    />
                    <div class="prefix-suggest" id="prefix-suggest" hidden>
                        Saran: <strong id="prefix-suggest-value"></strong>
                        <button type="button" class="link-btn" id="prefix-suggest-apply">Gunakan saran</button>
                    </div>
                </div>

                <div class="full">
                    <div class="form-group">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="2"
                            maxlength="500"
                            class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}"
                            placeholder="Keterangan singkat yang tampil bagi pengunjung"
                        >{{ old('description', $item->description) }}</textarea>
                        @error('description')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Tampilan ============ --}}
    <section class="card">
        <div class="card-header">
            <h2>Tampilan</h2>
            <p>Ikon, logo, dan warna layanan di halaman publik.</p>
        </div>

        <div class="card-body">
            <div class="form-group">
                <span class="form-label">Ikon</span>
                <div class="icon-picker">
                    @foreach (\App\Models\QueueItem::ICON_CHOICES as $icon)
                        <label class="icon-option" title="{{ $icon }}">
                            <input type="radio" name="icon" value="{{ $icon }}" @checked($currentIcon === $icon)>
                            <span><i class="bi {{ $icon }}"></i></span>
                        </label>
                    @endforeach
                </div>
                @error('icon')
                    <small class="form-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <span class="form-label">Logo (opsional)</span>
                <div class="logo-row">
                    <div class="logo-box" id="logo-preview">
                        <img
                            @if ($item->logoUrl()) src="{{ $item->logoUrl() }}" @else hidden @endif
                            alt="Logo layanan"
                        >
                        <span @if ($item->logoUrl()) hidden @endif><i class="bi bi-image"></i></span>
                    </div>

                    <div style="flex:1">
                        <x-form.input
                            name="logo"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            data-preview-target="logo-preview"
                            hint="JPG, PNG, atau WebP, maksimal 2 MB. Jika diisi, logo menggantikan ikon."
                        />

                        @if ($item->logo)
                            <label class="checkbox">
                                <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))>
                                Hapus logo saat ini
                            </label>
                        @endif
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="color" class="form-label">Warna layanan</label>
                <div class="color-row">
                    <input type="color" id="color" name="color" value="{{ $currentColor }}">
                    <code id="color-hex">{{ strtoupper($currentColor) }}</code>
                </div>
                @error('color')
                    <small class="form-error">{{ $message }}</small>
                @enderror
            </div>
        </div>
    </section>

    {{-- ============ Penomoran ============ --}}
    <section class="card">
        <div class="card-header">
            <h2>Penomoran</h2>
            <p>Format nomor, jumlah digit, dan kapan urutan dimulai kembali dari 1.</p>
        </div>

        <div class="card-body">
            <div class="form-group">
                <x-form.input
                    name="number_format"
                    label="Format nomor"
                    :value="$currentFormat"
                    maxlength="50"
                    autocomplete="off"
                    hint="Token: {PREFIX} {NUMBER} {YYYY} {YY} {MM} {DD}. Wajib memuat {NUMBER}."
                    required
                />

                <div class="chips">
                    <button type="button" class="chip" data-format-chip="{PREFIX}{NUMBER}">CV00001</button>
                    <button type="button" class="chip" data-format-chip="{PREFIX}-{NUMBER}">CV-00001</button>
                    <button type="button" class="chip" data-format-chip="{PREFIX}{YY}{MM}{DD}-{NUMBER}">CV261118-00001</button>
                </div>

                <div class="number-preview">
                    Pratinjau: <strong id="number-preview">-</strong>
                    <div id="number-preview-warning" class="form-error" hidden>Format harus memuat token {NUMBER}.</div>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="number_padding" class="form-label">Jumlah digit nomor <span class="required">*</span></label>
                    <select id="number_padding" name="number_padding" class="form-control {{ $errors->has('number_padding') ? 'is-invalid' : '' }}">
                        @foreach (range(1, 8) as $digits)
                            <option value="{{ $digits }}" @selected($currentPadding === $digits)>{{ $digits }} digit ({{ str_pad('1', $digits, '0', STR_PAD_LEFT) }})</option>
                        @endforeach
                    </select>
                    @error('number_padding')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="reset_period" class="form-label">Reset nomor antrean <span class="required">*</span></label>
                    <select id="reset_period" name="reset_period" class="form-control {{ $errors->has('reset_period') ? 'is-invalid' : '' }}">
                        @foreach (\App\Enums\ResetPeriod::cases() as $period)
                            <option value="{{ $period->value }}" @selected($currentPeriod === $period->value)>{{ $period->label() }}</option>
                        @endforeach
                    </select>
                    @error('reset_period')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Jam operasional ============ --}}
    <section class="card">
        <div class="card-header">
            <h2>Jam Operasional</h2>
            <p>Di luar jam ini, pengunjung tidak dapat mengambil nomor baru. Zona waktu {{ config('app.timezone') }}.</p>
        </div>

        <div class="card-body">
            <label class="checkbox" style="margin-bottom:1rem">
                <input type="hidden" name="hours_enabled" value="0">
                <input type="checkbox" id="hours_enabled" name="hours_enabled" value="1" @checked($hoursOn)>
                Batasi dengan jam operasional
            </label>

            <div class="form-grid hours-fields {{ $hoursOn ? '' : 'is-off' }}" id="hours-fields">
                <x-form.input name="hours_open" type="time" label="Jam buka" :value="$hours['open'] ?? '08:00'" />
                <x-form.input name="hours_close" type="time" label="Jam tutup" :value="$hours['close'] ?? '17:00'" />
            </div>
        </div>
    </section>

    {{-- ============ Status + tombol ============ --}}
    <section class="card">
        <div class="card-body">
            <label class="checkbox">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($isActive)>
                Layanan aktif (tampil di halaman publik dan dapat diambil nomornya)
            </label>
        </div>
    </section>

    <div class="form-actions">
        <a href="{{ route('admin.queue-items.index') }}" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle"></i> {{ $submitLabel }}
        </button>
    </div>
</form>
