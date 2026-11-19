function resetSubmitButtons() {
    document.querySelectorAll('button[type="submit"].is-loading').forEach((button) => {
        button.disabled = false;
        button.classList.remove('is-loading');
    });
}

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-toggle-password]');

    if (toggle) {
        const input = document.getElementById(toggle.dataset.togglePassword);

        if (!input) {
            return;
        }

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';

        const icon = toggle.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-eye', !show);
            icon.classList.toggle('bi-eye-slash', show);
        }

        toggle.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        return;
    }

    const dismiss = event.target.closest('[data-dismiss="alert"]');

    if (dismiss) {
        const alert = dismiss.closest('.alert');
        if (alert) {
            alert.remove();
        }
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const message = form.dataset.confirm;

    if (message && !window.confirm(message)) {
        event.preventDefault();
        return;
    }

    if (form.hasAttribute('data-loading')) {
        setTimeout(() => {
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = true;
                button.classList.add('is-loading');
            });
        }, 0);
    }
});

document.addEventListener('change', (event) => {
    const input = event.target;

    if (!(input instanceof HTMLInputElement) || !input.dataset.previewTarget) {
        return;
    }

    const target = document.getElementById(input.dataset.previewTarget);
    const file = input.files && input.files[0];

    if (!target || !file || !file.type.startsWith('image/')) {
        return;
    }

    const image = target.querySelector('img');
    const fallback = target.querySelector('span');

    if (image) {
        image.src = URL.createObjectURL(file);
        image.hidden = false;
    }

    if (fallback) {
        fallback.hidden = true;
    }
});

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        resetSubmitButtons();
    }
});
