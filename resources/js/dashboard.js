/* ============================================================
   Grafik antrean (SVG buatan sendiri) dan dialog detail
   ============================================================ */
import './queue-detail.js';
const dataElement = document.getElementById('dashboard-chart-data');
const container = document.getElementById('queue-chart');

if (dataElement && container) {
    const datasets = JSON.parse(dataElement.textContent);
    const tooltip = document.getElementById('chart-tooltip');
    const emptyMessage = document.getElementById('chart-empty');
    const note = document.getElementById('chart-note');
    const SVG_NS = 'http://www.w3.org/2000/svg';

    const rangeText = {
        daily: '14 hari terakhir',
        weekly: '8 minggu terakhir',
        monthly: '12 bulan terakhir',
    };

    let range = 'daily';

    const svgElement = (name, attributes = {}, parent = null) => {
        const node = document.createElementNS(SVG_NS, name);

        Object.entries(attributes).forEach(([key, value]) => node.setAttribute(key, value));

        if (parent) {
            parent.appendChild(node);
        }

        return node;
    };

    // Langkah sumbu Y berupa bilangan bulat yang rapi (1, 2, 5, 10, 20, 25, 50, ...).
    const niceStep = (raw) => {
        const power = Math.pow(10, Math.floor(Math.log10(Math.max(raw, 1))));
        const candidates = [1, 2, 2.5, 5, 10];

        for (const candidate of candidates) {
            const step = candidate * power;

            if (step >= raw && Number.isInteger(step)) {
                return step;
            }
        }

        return 10 * power;
    };

    const showTooltip = (point, event) => {
        tooltip.innerHTML =
            `<strong>${point.title}</strong><br>` +
            `Dibuat: ${point.total}<br>Selesai: ${point.completed}`;
        tooltip.hidden = false;

        const bounds = container.getBoundingClientRect();
        const x = event.clientX - bounds.left;
        const y = event.clientY - bounds.top;
        const width = tooltip.offsetWidth;

        tooltip.style.left = `${Math.min(Math.max(x - width / 2, 0), bounds.width - width)}px`;
        tooltip.style.top = `${Math.max(y - tooltip.offsetHeight - 14, 0)}px`;
    };

    const render = () => {
        const series = datasets[range];

        container.querySelectorAll('svg').forEach((node) => node.remove());
        tooltip.hidden = true;

        const width = Math.max(container.clientWidth, 280);
        const height = 300;
        const margin = { top: 14, right: 10, bottom: 36, left: 36 };
        const plotWidth = width - margin.left - margin.right;
        const plotHeight = height - margin.top - margin.bottom;

        const maxValue = Math.max(1, ...series.map((point) => point.total));
        const step = niceStep(maxValue / 4);
        const axisMax = step * 4;

        const totalSum = series.reduce((sum, point) => sum + point.total, 0);
        const doneSum = series.reduce((sum, point) => sum + point.completed, 0);

        note.textContent = `${rangeText[range]}: ${totalSum} dibuat, ${doneSum} selesai`;
        emptyMessage.hidden = totalSum > 0;

        const svg = svgElement('svg', {
            viewBox: `0 0 ${width} ${height}`,
            width,
            height,
            role: 'img',
            'aria-label': `Grafik antrean ${rangeText[range]}`,
        });

        // Garis bantu dan label sumbu Y.
        for (let i = 0; i <= 4; i++) {
            const y = margin.top + plotHeight - (plotHeight * i) / 4;

            svgElement('line', {
                x1: margin.left,
                x2: width - margin.right,
                y1: y,
                y2: y,
                class: 'chart-grid',
            }, svg);

            const label = svgElement('text', {
                x: margin.left - 8,
                y: y + 4,
                'text-anchor': 'end',
                class: 'chart-axis-text',
            }, svg);
            label.textContent = String(step * i);
        }

        const groupWidth = plotWidth / series.length;
        const barWidth = Math.max(4, Math.min(22, groupWidth * 0.32));
        const labelEvery = Math.ceil(series.length / Math.max(1, Math.floor(plotWidth / 56)));

        series.forEach((point, index) => {
            const groupX = margin.left + groupWidth * index;
            const centerX = groupX + groupWidth / 2;

            // Area sentuh dan sorot saat hover (digambar paling bawah).
            const hit = svgElement('rect', {
                x: groupX,
                y: margin.top,
                width: groupWidth,
                height: plotHeight,
                class: 'chart-hit',
                tabindex: 0,
                'aria-label': `${point.title}: ${point.total} dibuat, ${point.completed} selesai`,
            }, svg);

            hit.addEventListener('mouseenter', (event) => showTooltip(point, event));
            hit.addEventListener('mousemove', (event) => showTooltip(point, event));
            hit.addEventListener('mouseleave', () => { tooltip.hidden = true; });
            hit.addEventListener('click', (event) => showTooltip(point, event));

            const drawBar = (value, x, className) => {
                if (value <= 0) {
                    return;
                }

                const barHeight = Math.max(2, (plotHeight * value) / axisMax);

                svgElement('rect', {
                    x,
                    y: margin.top + plotHeight - barHeight,
                    width: barWidth,
                    height: barHeight,
                    rx: 3,
                    class: className,
                }, svg);
            };

            drawBar(point.total, centerX - barWidth - 1, 'chart-bar-total');
            drawBar(point.completed, centerX + 1, 'chart-bar-done');

            if (index % labelEvery === 0) {
                const label = svgElement('text', {
                    x: centerX,
                    y: height - 12,
                    'text-anchor': 'middle',
                    class: 'chart-axis-text',
                }, svg);
                label.textContent = point.label;
            }
        });

        container.insertBefore(svg, container.firstChild);
    };

    document.querySelectorAll('[data-chart-range]').forEach((button) => {
        button.addEventListener('click', () => {
            range = button.dataset.chartRange;

            document.querySelectorAll('[data-chart-range]').forEach((other) => {
                other.classList.toggle('active', other === button);
            });

            render();
        });
    });

    let lastWidth = container.clientWidth;

    new ResizeObserver(() => {
        if (container.clientWidth !== lastWidth) {
            lastWidth = container.clientWidth;
            render();
        }
    }).observe(container);

    render();
}

/* ---------- Dialog detail antrean ---------- */
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
}a
