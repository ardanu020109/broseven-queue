const root = document.documentElement;
const STORAGE_KEY = 'bq.sidebar';
const mobileQuery = window.matchMedia('(max-width: 991.98px)');

function saveSidebarState(collapsed) {
    try {
        localStorage.setItem(STORAGE_KEY, collapsed ? 'collapsed' : 'expanded');
    } catch (error) {
        // localStorage tidak tersedia: abaikan, state hanya berlaku selama halaman terbuka.
    }
}

function closeDropdowns(except = null) {
    document.querySelectorAll('.dropdown.open').forEach((dropdown) => {
        if (dropdown !== except) {
            dropdown.classList.remove('open');
            const trigger = dropdown.querySelector('[data-dropdown-toggle]');
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }
        }
    });
}

document.addEventListener('click', (event) => {
    // Tombol hamburger: drawer di mobile/tablet, collapse di desktop.
    if (event.target.closest('[data-sidebar-toggle]')) {
        closeDropdowns();

        if (mobileQuery.matches) {
            root.classList.toggle('sidebar-open');
        } else {
            saveSidebarState(root.classList.toggle('sidebar-collapsed'));
        }

        return;
    }

    if (event.target.closest('[data-sidebar-close]')) {
        root.classList.remove('sidebar-open');
        return;
    }

    // Submenu sidebar.
    const submenuToggle = event.target.closest('[data-submenu-toggle]');

    if (submenuToggle) {
        const group = submenuToggle.closest('.nav-group');

        // Saat sidebar diciutkan, klik grup membuka sidebar agar submenu terlihat.
        if (!mobileQuery.matches && root.classList.contains('sidebar-collapsed')) {
            root.classList.remove('sidebar-collapsed');
            saveSidebarState(false);
            group.classList.add('open');
            submenuToggle.setAttribute('aria-expanded', 'true');
            return;
        }

        const open = group.classList.toggle('open');
        submenuToggle.setAttribute('aria-expanded', String(open));
        return;
    }

    // Dropdown navbar.
    const dropdownToggle = event.target.closest('[data-dropdown-toggle]');

    if (dropdownToggle) {
        const dropdown = dropdownToggle.closest('.dropdown');
        closeDropdowns(dropdown);
        const open = dropdown.classList.toggle('open');
        dropdownToggle.setAttribute('aria-expanded', String(open));
        return;
    }

    if (!event.target.closest('.dropdown')) {
        closeDropdowns();
    }

    // Menutup drawer setelah memilih menu di mobile.
    if (mobileQuery.matches && event.target.closest('.sidebar a')) {
        root.classList.remove('sidebar-open');
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeDropdowns();
        root.classList.remove('sidebar-open');
    }
});

mobileQuery.addEventListener('change', () => {
    root.classList.remove('sidebar-open');
});

// Jam dan tanggal real-time memakai zona waktu aplikasi (bukan zona browser).
const clock = document.querySelector('[data-clock]');

if (clock) {
    const zone = clock.dataset.timezone || 'Asia/Makassar';
    const timeTarget = clock.querySelector('[data-clock-time]');
    const dateTarget = clock.querySelector('[data-clock-date]');

    const timeFormat = new Intl.DateTimeFormat('id-ID', {
        timeZone: zone,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });

    const dateFormat = new Intl.DateTimeFormat('id-ID', {
        timeZone: zone,
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    const tick = () => {
        const now = new Date();
        timeTarget.textContent = timeFormat.format(now).replace(/\./g, ':');
        dateTarget.textContent = dateFormat.format(now);
    };

    tick();
    setInterval(tick, 1000);
}
