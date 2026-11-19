<div class="form-grid">
    <div class="form-group">
        <label for="ticket_paper_size" class="form-label">Ukuran kertas bawaan <span class="required">*</span></label>
        <select id="ticket_paper_size" name="ticket_paper_size" class="form-control {{ $errors->has('ticket_paper_size') ? 'is-invalid' : '' }}">
            @foreach (['80mm' => 'Thermal 80 mm', '58mm' => 'Thermal 58 mm', 'a4' => 'A4'] as $value => $label)
                <option value="{{ $value }}" @selected(strtolower($text('ticket_paper_size', '80mm')) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <small class="form-hint">Pengunjung tetap bisa mengganti ukuran sebelum mencetak.</small>
        @error('ticket_paper_size') <small class="form-error">{{ $message }}</small> @enderror
    </div>

    <x-form.input name="ticket_title" label="Judul tiket" :value="$values['ticket_title'] ?? 'TIKET ANTREAN'" maxlength="60" required />

    <div class="full">
        <div class="logo-row">
            <div class="logo-box" id="ticket-logo-preview">
                <img @if ($fileUrl('ticket_logo')) src="{{ $fileUrl('ticket_logo') }}" @else hidden @endif alt="Logo tiket">
                <span @if ($fileUrl('ticket_logo')) hidden @endif><i class="bi bi-image"></i></span>
            </div>
            <div style="flex:1">
                <x-form.input name="ticket_logo" type="file" label="Logo tiket" accept="image/png,image/jpeg,image/webp" data-preview-target="ticket-logo-preview" hint="Disarankan hitam putih agar tajam pada printer thermal. Jika kosong, logo website dipakai." />
                @if ($fileUrl('ticket_logo'))
                    <label class="checkbox"><input type="checkbox" name="remove_ticket_logo" value="1"> Hapus logo tiket saat ini</label>
                @endif
            </div>
        </div>
    </div>

    <div class="full">
        <div class="form-group">
            <label for="ticket_footer" class="form-label">Footer tiket</label>
            <textarea id="ticket_footer" name="ticket_footer" rows="2" maxlength="300" class="form-control {{ $errors->has('ticket_footer') ? 'is-invalid' : '' }}">{{ $text('ticket_footer') }}</textarea>
            @error('ticket_footer') <small class="form-error">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="full">
        <div class="form-group">
            <label for="ticket_extra_info" class="form-label">Informasi tambahan</label>
            <textarea id="ticket_extra_info" name="ticket_extra_info" rows="2" maxlength="500" class="form-control {{ $errors->has('ticket_extra_info') ? 'is-invalid' : '' }}">{{ $text('ticket_extra_info') }}</textarea>
            @error('ticket_extra_info') <small class="form-error">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="full">
        <span class="form-label">Format cetak</span>
        <div class="check-stack">
            <label class="checkbox">
                <input type="hidden" name="ticket_show_logo" value="0">
                <input type="checkbox" name="ticket_show_logo" value="1" @checked($bool('ticket_show_logo'))>
                Tampilkan logo pada tiket
            </label>
            <label class="checkbox">
                <input type="hidden" name="ticket_show_waiting_count" value="0">
                <input type="checkbox" name="ticket_show_waiting_count" value="1" @checked($bool('ticket_show_waiting_count'))>
                Tampilkan jumlah antrean di depan pengunjung
            </label>
        </div>
    </div>
</div>
