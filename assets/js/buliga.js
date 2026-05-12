// ============================================================
// buliga.js – Buliga Volunteer Platform – Client-side helpers
// ============================================================

// ── Chart helpers (global – callable from any inline script) ──

/**
 * Create a doughnut chart.
 * @param {string} canvasId
 * @param {string[]} labels
 * @param {number[]} data
 * @param {string[]} colors
 */
function makeDoughnut(canvasId, labels, data, colors) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data, backgroundColor: colors, borderWidth: 2 }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { font: { family: 'DM Sans' } } }
            },
            cutout: '65%'
        }
    });
}

/**
 * Create a bar chart.
 * @param {string} canvasId
 * @param {string[]} labels
 * @param {number[]} data
 * @param {string} label
 * @param {string} color
 */
function makeBar(canvasId, labels, data, label, color = '#2d9b5a') {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label,
                data,
                backgroundColor: color + 'cc',
                borderColor: color,
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    grid: { color: '#e8f7ef' }
                },
                x: { grid: { display: false } }
            }
        }
    });
}

/**
 * Create a line chart.
 */
function makeLine(canvasId, labels, data, label, color = '#2d9b5a') {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label,
                data,
                borderColor: color,
                backgroundColor: color + '22',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: color,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#e8f7ef' } },
                x: { grid: { display: false } }
            }
        }
    });
}

// ── DOM-ready helpers ─────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {

    // Auto-dismiss alerts after 4 s
    document.querySelectorAll('.alert.alert-dismissible').forEach(el => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        }, 4000);
    });

    // Confirm before delete
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // Live table search
    document.querySelectorAll('[data-search-table]').forEach(input => {
        const tableId = input.dataset.searchTable;
        const table = document.querySelector(tableId);
        if (!table) return;

        input.addEventListener('input', () => {
            const query = input.value.toLowerCase().trim();
            table.querySelectorAll('tbody tr').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });

    // Column sort for tables
    document.querySelectorAll('th[data-sortable]').forEach(th => {
        th.style.cursor = 'pointer';
        th.title = 'Click to sort';
        let asc = true;

        th.addEventListener('click', () => {
            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            const idx = [...th.parentElement.children].indexOf(th);
            const rows = [...tbody.querySelectorAll('tr')];

            rows.sort((a, b) => {
                const va = a.children[idx]?.innerText.trim() ?? '';
                const vb = b.children[idx]?.innerText.trim() ?? '';
                return asc
                    ? va.localeCompare(vb, undefined, { numeric: true })
                    : vb.localeCompare(va, undefined, { numeric: true });
            });

            rows.forEach(r => tbody.appendChild(r));
            asc = !asc;

            table.querySelectorAll('th[data-sortable]').forEach(t => t.dataset.sortDir = '');
            th.dataset.sortDir = asc ? 'desc' : 'asc';
        });
    });

    // Image preview before upload
    const imgInput = document.getElementById('event_image');
    const imgPreview = document.getElementById('image_preview');
    if (imgInput && imgPreview) {
        imgInput.addEventListener('change', () => {
            const file = imgInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    imgPreview.src = e.target.result;
                    imgPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

});