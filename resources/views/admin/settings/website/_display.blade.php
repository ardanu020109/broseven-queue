<div class="form-grid">
    <div class="full">
        <x-form.input name="display_title" label="Judul display" :value="$values['display_title'] ?? 'Display Antrean'" maxlength="100" required />
    </div>

    <div class="full">
        <div class="form-group">
            <label for="display_marquee" class="form-label">Pesan berjalan</label>
            <textarea id="display_marquee" name="display_marquee" rows="2" maxlength="500" class="form-control {{ $errors->has('display_marquee') ? 'is-invalid' : '' }}">{{ $text('display_marquee') }}</textarea>
            @error('display_marquee') <small class="form-error">{{ $message }}</small> @enderror
        </div>
    </div>

    <x-form.input name="display_marquee_speed" type="number" label="Kecepatan teks berjalan (detik per putaran)" :value="$values['display_marquee_speed'] ?? 40" min="10" max="120" hint="Makin kecil makin cepat (10 sampai 120)." required />

    <div class="form-group">
        <label for="display_number_size" class="form-label">Ukuran nomor antrean <span class="required">*</span></label>
        <select id="display_number_size" name="display_number_size" class="form-control {{ $errors->has('display_number_size') ? 'is-invalid' : '' }}">
            @foreach (['sm' => 'Kecil', 'md' => 'Sedang', 'lg' => 'Besar', 'xl' => 'Sangat besar'] as $value => $label)
                <option value="{{ $value }}" @selected($text('display_number_size', 'lg') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('display_number_size') <small class="form-error">{{ $message }}</small> @enderror
    </div>

    <x-form.input name="display_list_limit" type="number" label="Jumlah antrean berikutnya yang ditampilkan" :value="$values['display_list_limit'] ?? 5" min="1" max="12" required />

    <div class="form-group">
        <span class="form-label">Suara panggilan</span>
        <label class="checkbox">
            <input type="hidden" name="display_sound_enabled" value="0">
            <input type="checkbox" name="display_sound_enabled" value="1" @checked($bool('display_sound_enabled'))>
            Aktifkan suara (bawaan layar display)
        </label>
    </div>
</div>

<h3 style="margin:1.4rem 0 .8rem;font-size:1rem">Suara bawaan</h3>
<p class="form-hint" style="margin-top:-.4rem">Setiap layar display dapat menyesuaikan sendiri lewat panel pengaturan suara di layar tersebut.</p>

<div class="form-grid">
    <x-form.input name="display_voice_volume" type="number" label="Volume (0 sampai 1)" :value="$values['display_voice_volume'] ?? 1" min="0" max="1" step="0.1" required />
    <x-form.input name="display_voice_rate" type="number" label="Kecepatan bicara (0,5 sampai 1,5)" :value="$values['display_voice_rate'] ?? 0.9" min="0.5" max="1.5" step="0.1" required />

    <div class="form-group">
        <label for="display_voice_lang" class="form-label">Bahasa <span class="required">*</span></label>
        <select id="display_voice_lang" name="display_voice_lang" class="form-control {{ $errors->has('display_voice_lang') ? 'is-invalid' : '' }}">
            @foreach (['id-ID' => 'Indonesia (id-ID)', 'en-US' => 'English US (en-US)', 'en-GB' => 'English UK (en-GB)', 'ms-MY' => 'Melayu (ms-MY)'] as $value => $label)
                <option value="{{ $value }}" @selected($text('display_voice_lang', 'id-ID') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('display_voice_lang') <small class="form-error">{{ $message }}</small> @enderror
    </div>

    <div class="full">
        <x-form.input
            name="display_call_template"
            label="Templat kalimat panggilan"
            :value="$values['display_call_template'] ?? 'Nomor antrean {number}, silakan menuju loket pelayanan.'"
            maxlength="200"
            hint="Token: {number}, {service}, {counter}. Wajib memuat {number}."
            required
        />
    </div>
</div>
