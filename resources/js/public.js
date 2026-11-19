const root = document.documentElement;

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-nav-toggle]');

    if (toggle) {
        const open = root.classList.toggle('nav-open');
        toggle.setAttribute('aria-expanded', String(open));
        return;
    }

    if (root.classList.contains('nav-open') && event.target.closest('.public-nav a')) {
        root.classList.remove('nav-open');
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        root.classList.remove('nav-open');
    }
});
