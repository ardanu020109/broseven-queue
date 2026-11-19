<dialog id="queue-detail" class="dialog" aria-labelledby="queue-detail-title">
    <header class="dialog-header">
        <h2 id="queue-detail-title">Detail Antrean <span data-field="number"></span></h2>
        <button type="button" class="icon-btn" data-dialog-close aria-label="Tutup">
            <i class="bi bi-x-lg"></i>
        </button>
    </header>

    <dl class="detail-list">
        <div><dt>Layanan</dt><dd data-field="service"></dd></div>
        <div><dt>Status</dt><dd><span class="badge" id="detail-status"></span></dd></div>
        <div><dt>Loket</dt><dd data-field="counter"></dd></div>
        <div><dt>Nama</dt><dd data-field="name"></dd></div>
        <div><dt>Telepon</dt><dd data-field="phone"></dd></div>
        <div><dt>Catatan</dt><dd data-field="notes"></dd></div>
        <div><dt>Diambil</dt><dd data-field="issued_at"></dd></div>
        <div><dt>Dipanggil</dt><dd data-field="called_at"></dd></div>
        <div><dt>Di-skip</dt><dd data-field="skipped_at"></dd></div>
        <div><dt>Selesai</dt><dd data-field="completed_at"></dd></div>
    </dl>
</dialog>
