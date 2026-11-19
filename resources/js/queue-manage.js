import './echo';
import './queue-detail.js';

/* ---------- Nama loket: tersimpan di browser, disalin ke semua form aksi ---------- */
const counterInput = document.getElementById('counter-input');

if (counterInput) {
    const STORAGE_KEY = 'bq.counter';

    try {
        const saved = localStorage.getItem(STORAGE_KEY);

        if (saved) {
            counterInput.value = saved;
        }
    } catch (error) {
        // localStorage tidak tersedia: gunakan nilai bawaan.
    }

    const sync = () => {
        const value = counterInput.value.trim();

        document.querySelectorAll('input[data-counter-sync]').forEach((hidden) => {
            hidden.value = value;
        });

        try {
            localStorage.setItem(STORAGE_KEY, value);
        } catch (error) {
            // abaikan
        }
    };

    counterInput.addEventListener('input', sync);
    sync();
}

/* ---------- Filter tanggal: kirim otomatis saat berubah ---------- */
document.querySelectorAll('[data-autosubmit]').forEach((element) => {
    element.addEventListener('change', () => {
        if (element.form) {
            element.form.requestSubmit();
        }
    });
});

/* ---------- Pemberitahuan: ada perubahan dari admin lain atau pengunjung ---------- */
const liveBanner = document.getElementById('live-banner');

if (liveBanner && window.Echo) {
    const itemId = Number(liveBanner.dataset.itemId);
    const notify = (payload) => {
        if (Number(payload.service_id) === itemId) {
            liveBanner.hidden = false;
        }
    };

    window.Echo.channel('queue-display')
        .listen('.queue.created', notify)
        .listen('.queue.called', notify)
        .listen('.queue.recalled', notify)
        .listen('.queue.skipped', notify)
        .listen('.queue.completed', notify);

    const reload = liveBanner.querySelector('[data-reload]');
    if (reload) {
        reload.addEventListener('click', () => window.location.reload());
    }
}
