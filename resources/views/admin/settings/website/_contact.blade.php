<div class="form-grid">
    <div class="full">
        <div class="form-group">
            <label for="contact_address" class="form-label">Alamat kantor atau lokasi pelayanan</label>
            <textarea id="contact_address" name="contact_address" rows="2" maxlength="500" class="form-control {{ $errors->has('contact_address') ? 'is-invalid' : '' }}">{{ $text('contact_address') }}</textarea>
            <small class="form-hint">Tampil di footer dan pada tiket antrean.</small>
            @error('contact_address') <small class="form-error">{{ $message }}</small> @enderror
        </div>
    </div>

    <x-form.input name="contact_email" type="email" label="Email" :value="$values['contact_email'] ?? ''" maxlength="255" />
    <x-form.input name="contact_phone" type="tel" label="Nomor telepon" :value="$values['contact_phone'] ?? ''" maxlength="20" />
    <x-form.input name="contact_whatsapp" type="tel" label="WhatsApp" :value="$values['contact_whatsapp'] ?? ''" maxlength="20" hint="Contoh: 0812xxxxxxx. Otomatis menjadi tautan wa.me." />
</div>
