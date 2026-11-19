<div class="form-grid">
    <x-form.input name="social_instagram" type="url" label="Instagram" :value="$values['social_instagram'] ?? ''" placeholder="https://instagram.com/akun" />
    <x-form.input name="social_facebook" type="url" label="Facebook" :value="$values['social_facebook'] ?? ''" placeholder="https://facebook.com/halaman" />
    <x-form.input name="social_tiktok" type="url" label="TikTok" :value="$values['social_tiktok'] ?? ''" placeholder="https://tiktok.com/@akun" />
    <x-form.input name="social_youtube" type="url" label="YouTube" :value="$values['social_youtube'] ?? ''" placeholder="https://youtube.com/@kanal" />
    <x-form.input name="social_linkedin" type="url" label="LinkedIn" :value="$values['social_linkedin'] ?? ''" placeholder="https://linkedin.com/company/nama" />
</div>

<p class="form-hint">Hanya tautan <code>http://</code> atau <code>https://</code>. Kolom yang kosong tidak ditampilkan di footer.</p>
