const form = document.getElementById('queue-item-form');

if (form) {
    const nameInput = form.querySelector('#name');
    const prefixInput = form.querySelector('#prefix');
    const formatInput = form.querySelector('#number_format');
    const paddingInput = form.querySelector('#number_padding');
    const preview = document.getElementById('number-preview');
    const previewWarning = document.getElementById('number-preview-warning');
    const suggestBox = document.getElementById('prefix-suggest');
    const suggestValue = document.getElementById('prefix-suggest-value');
    const suggestApply = document.getElementById('prefix-suggest-apply');
    const colorInput = document.getElementById('color');
    const colorHex = document.getElementById('color-hex');
    const hoursToggle = document.getElementById('hours_enabled');
    const hoursFields = document.getElementById('hours-fields');

    const pad = (value, width) => String(value).padStart(width, '0');

    /* ---------- Saran prefix dari inisial nama ---------- */
    const suggestPrefix = (name) => {
        const words = name
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^A-Za-z0-9\s]/g, '')
            .trim()
            .split(/\s+/)
            .filter(Boolean);

        if (words.length === 0) {
            return '';
        }

        if (words.length === 1) {
            return words[0].slice(0, 3).toUpperCase();
        }

        return words.slice(0, 4).map((word) => word[0]).join('').toUpperCase();
    };

    const sanitizePrefix = (value) => value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10);

    /* ---------- Pratinjau nomor ---------- */
    const renderPreview = () => {
        const now = new Date();
        const year = String(now.getFullYear());
        const width = Math.min(8, Math.max(1, parseInt(paddingInput.value, 10) || 1));
        const prefix = sanitizePrefix(prefixInput.value) || 'XX';
        const format = formatInput.value || '{PREFIX}{NUMBER}';

        const tokens = {
            '{PREFIX}': prefix,
            '{YYYY}': year,
            '{YY}': year.slice(-2),
            '{MM}': pad(now.getMonth() + 1, 2),
            '{DD}': pad(now.getDate(), 2),
        };

        const build = (sequence) => format.replace(
            /\{(PREFIX|NUMBER|YYYY|YY|MM|DD)\}/g,
            (token) => (token === '{NUMBER}' ? pad(sequence, width) : tokens[token])
        );

        preview.textContent = [1, 2, 3].map(build).join('  ·  ');
        previewWarning.hidden = format.includes('{NUMBER}');
    };

    const renderSuggestion = () => {
        const suggestion = suggestPrefix(nameInput.value);
        const current = sanitizePrefix(prefixInput.value);
        const show = prefixInput.dataset.auto === '0' && suggestion !== '' && suggestion !== current;

        suggestValue.textContent = suggestion;
        suggestBox.hidden = !show;
    };

    nameInput.addEventListener('input', () => {
        if (prefixInput.dataset.auto === '1') {
            prefixInput.value = suggestPrefix(nameInput.value);
        }

        renderSuggestion();
        renderPreview();
    });

    prefixInput.addEventListener('input', () => {
        // Setelah admin mengetik sendiri, prefix tidak lagi diganti otomatis.
        prefixInput.dataset.auto = '0';
        prefixInput.value = sanitizePrefix(prefixInput.value);
        renderSuggestion();
        renderPreview();
    });

    suggestApply.addEventListener('click', () => {
        prefixInput.value = suggestPrefix(nameInput.value);
        renderSuggestion();
        renderPreview();
    });

    formatInput.addEventListener('input', renderPreview);
    paddingInput.addEventListener('change', renderPreview);

    form.querySelectorAll('[data-format-chip]').forEach((chip) => {
        chip.addEventListener('click', () => {
            formatInput.value = chip.dataset.formatChip;
            renderPreview();
        });
    });

    /* ---------- Warna ---------- */
    colorInput.addEventListener('input', () => {
        colorHex.textContent = colorInput.value.toUpperCase();
    });

    /* ---------- Jam operasional ---------- */
    hoursToggle.addEventListener('change', () => {
        hoursFields.classList.toggle('is-off', !hoursToggle.checked);
    });

    renderSuggestion();
    renderPreview();
}
