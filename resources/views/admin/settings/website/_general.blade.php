<div class="form-grid">
    <div class="full">
        <x-form.input name="site_name" label="Nama website" :value="$values['site_name'] ?? ''" maxlength="100" required />
    </div>

    <div class="full">
        <div class="form-group">
            <label for="site_description" class="form-label">Deskripsi</label>
            <textarea id="site_description" name="site_description" rows="2" maxlength="500" class="form-control {{ $errors->has('site_description') ? 'is-invalid' : '' }}">{{ $text('site_description') }}</textarea>
            @error('site_description') <small class="form-error">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="full">
        <x-form.input name="site_url" type="url" label="Alamat website" :value="$values['site_url'] ?? ''" maxlength="255" placeholder="https://contoh.com" />
    </div>

    <div class="full">
        <div class="logo-row">
            <div class="logo-box" id="logo-preview">
                <img @if ($fileUrl('site_logo')) src="{{ $fileUrl('site_logo') }}" @else hidden @endif alt="Logo">
                <span @if ($fileUrl('site_logo')) hidden @endif><i class="bi bi-image"></i></span>
            </div>
            <div style="flex:1">
                <x-form.input name="site_logo" type="file" label="Logo website" accept="image/png,image/jpeg,image/webp" data-preview-target="logo-preview" hint="PNG, JPG, atau WebP, maksimal 2 MB. Jika kosong, nama website dipakai sebagai logo." />
                @if ($fileUrl('site_logo'))
                    <label class="checkbox"><input type="checkbox" name="remove_site_logo" value="1"> Hapus logo saat ini</label>
                @endif
            </div>
        </div>
    </div>

    <div class="full">
        <div class="logo-row">
            <div class="logo-box" id="favicon-preview">
                <img @if ($fileUrl('site_favicon')) src="{{ $fileUrl('site_favicon') }}" @else hidden @endif alt="Favicon">
                <span @if ($fileUrl('site_favicon')) hidden @endif><i class="bi bi-app"></i></span>
            </div>
            <div style="flex:1">
                <x-form.input name="site_favicon" type="file" label="Favicon" accept="image/png,image/x-icon,image/jpeg,image/webp" data-preview-target="favicon-preview" hint="PNG, ICO, JPG, atau WebP, maksimal 512 KB (disarankan persegi)." />
                @if ($fileUrl('site_favicon'))
                    <label class="checkbox"><input type="checkbox" name="remove_site_favicon" value="1"> Hapus favicon saat ini</label>
                @endif
            </div>
        </div>
    </div>

    <div class="full">
        <div class="form-group">
            <label for="site_footer" class="form-label">Footer website</label>
            <textarea id="site_footer" name="site_footer" rows="2" maxlength="500" class="form-control {{ $errors->has('site_footer') ? 'is-invalid' : '' }}">{{ $text('site_footer') }}</textarea>
            @error('site_footer') <small class="form-error">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="full">
        <x-form.input name="site_copyright" label="Copyright" :value="$values['site_copyright'] ?? ''" maxlength="255" />
    </div>

    <div class="full">
        <div class="form-group">
            <label for="additional_info" class="form-label">Teks informasi tambahan</label>
            <textarea id="additional_info" name="additional_info" rows="3" maxlength="1000" class="form-control {{ $errors->has('additional_info') ? 'is-invalid' : '' }}">{{ $text('additional_info') }}</textarea>
            <small class="form-hint">Tampil di footer halaman publik.</small>
            @error('additional_info') <small class="form-error">{{ $message }}</small> @enderror
        </div>
    </div>
</div>
