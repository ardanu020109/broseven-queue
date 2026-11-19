const dialog = document.getElementById('queue-detail');

if (dialog) {
    const statusBadge = document.getElementById('detail-status');

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-queue-detail]');

        if (trigger) {
            const detail = JSON.parse(trigger.dataset.queueDetail);

            dialog.querySelectorAll('[data-field]').forEach((node) => {
                const value = detail[node.dataset.field];
                node.textContent = value ? value : '-';
            });

            statusBadge.textContent = detail.status_label;
            statusBadge.className = `badge badge-${detail.status}`;

            dialog.showModal();
            return;
        }

        // Tombol tutup, atau klik pada latar gelap di luar kotak dialog.
        if (event.target.closest('[data-dialog-close]') || event.target === dialog) {
            dialog.close();
        }
    });
}
