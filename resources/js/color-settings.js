const form = document.getElementById('color-form');

if (form) {
    const KEYS = ['primary_color', 'secondary_color', 'accent_color', 'dark_color', 'background_color', 'text_color'];
    const HEX = /^#[0-9A-Fa-f]{6}$/;

    const cssVar = (key) => `--${key.replace(/_/g, '-')}`;
    const preview = document.getElementById('theme-preview');
    const contrastBox = document.getElementById('contrast-box');
    const contrastList = document.getElementById('contrast-list');
    const banner = document.getElementById('page-preview-banner');
    const toggleButton = document.getElementById('page-preview-toggle');
    const cancelButton = document.getElementById('page-preview-cancel');

    const inputs = {};
    const pickers = {};

    KEYS.forEach((key) => {
        inputs[key] = form.querySelector(`input[name="${key}"]`);
        pickers[key] = form.querySelector(`[data-picker="${key}"]`);
    });

    /* ---------- Kontras WCAG (sama dengan App\Support\Contrast) ---------- */
    const luminance = (hex) => {
        const [r, g, b] = [1, 3, 5].map((start) => {
            const value = parseInt(hex.slice(start, start + 2), 16) / 255;
            return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
        });

        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    };

    const ratio = (a, b) => {
        const [la, lb] = [luminance(a), luminance(b)];
        return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
    };

    const displayBackground = (dark) => {
        const part = (start) => Math.round(parseInt(dark.slice(start, start + 2), 16) * 0.4)
            .toString(16)
            .padStart(2, '0');

        return `#${part(1)}${part(3)}${part(5)}`;
    };

    const warningsFor = (c) => {
        const checks = [
            ['Teks putih pada Dark Color (sidebar dan footer)', '#FFFFFF', c.dark_color, 4.5],
            ['Teks pada Background Color', c.text_color, c.background_color, 4.5],
            ['Teks pada tombol Primary Color', c.text_color, c.primary_color, 4.5],
            ['Teks pada Accent Color (notifikasi)', c.text_color, c.accent_color, 4.5],
            ['Nomor Primary Color pada layar display', c.primary_color, displayBackground(c.dark_color), 3.0],
        ];

        return checks
            .map(([label, fg, bg, min]) => ({ label, value: ratio(fg, bg), min }))
            .filter((item) => item.value < item.min)
            .map((item) => `${item.label}: ${item.value.toFixed(1)}:1 (disarankan minimal ${item.min.toFixed(1)}:1)`);
    };

    /* ---------- Nilai saat ini (hanya heksadesimal valid) ---------- */
    const currentColors = () => {
        const colors = {};

        KEYS.forEach((key) => {
            const typed = inputs[key].value.trim().toUpperCase();
            colors[key] = HEX.test(typed) ? typed : inputs[key].dataset.saved;
        });

        return colors;
    };

    let pagePreview = false;

    const applyTo = (element, colors) => {
        KEYS.forEach((key) => element.style.setProperty(cssVar(key), colors[key]));
    };

    const update = () => {
        const colors = currentColors();

        applyTo(preview, colors);

        if (pagePreview) {
            applyTo(document.documentElement, colors);
        }

        const warnings = warningsFor(colors);

        contrastList.replaceChildren(...warnings.map((text) => {
            const li = document.createElement('li');
            li.textContent = text;
            return li;
        }));

        contrastBox.hidden = warnings.length === 0;
    };

    /* ---------- Sinkronisasi pemilih warna dan kolom heksadesimal ---------- */
    KEYS.forEach((key) => {
        pickers[key].addEventListener('input', () => {
            inputs[key].value = pickers[key].value.toUpperCase();
            update();
        });

        inputs[key].addEventListener('input', () => {
            const typed = inputs[key].value.trim();

            if (HEX.test(typed)) {
                pickers[key].value = typed.toLowerCase();
            }

            update();
        });
    });

    form.querySelectorAll('[data-use-default]').forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.useDefault;
            inputs[key].value = inputs[key].dataset.default;
            pickers[key].value = inputs[key].dataset.default.toLowerCase();
            update();
        });
    });

    /* ---------- Pratinjau pada seluruh halaman (belum disimpan) ---------- */
    const savedColors = () => {
        const colors = {};
        KEYS.forEach((key) => { colors[key] = inputs[key].dataset.saved; });
        return colors;
    };

    const setPagePreview = (on) => {
        pagePreview = on;
        banner.hidden = !on;
        toggleButton.classList.toggle('active', on);

        if (on) {
            applyTo(document.documentElement, currentColors());
        } else {
            applyTo(document.documentElement, savedColors());
        }
    };

    toggleButton.addEventListener('click', () => setPagePreview(!pagePreview));
    cancelButton.addEventListener('click', () => setPagePreview(false));

    update();
}
