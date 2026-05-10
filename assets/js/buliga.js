document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.alert.alert-dismissible').forEach(el => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        }, 4000);
    });

    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

// ── Enhanced Search with Result Counting ─────────────────────
document.querySelectorAll('[data-search-table]').forEach(input => {
    const tableId = input.dataset.searchTable;
    const table = document.querySelector(tableId);
    if (!table) return;

    input.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const tbody = table.querySelector('tbody');
        let resultCount = 0;

        tbody.querySelectorAll('tr').forEach(row => {
            // Remove previous highlights
            row.querySelectorAll('td').forEach(cell => {
                cell.innerHTML = cell.innerHTML.replace(/<mark class="search-highlight">([^<]+)<\/mark>/g, '$1');
            });

            if (!query) {
                row.style.display = '';
                row.classList.remove('highlight');
                return;
            }

            const text = row.innerText.toLowerCase();
            const match = text.includes(query);
            row.style.display = match ? '' : 'none';
            
            if (match) {
                resultCount++;
                row.classList.add('highlight');
                
                // Highlight matching text in cells
                if (query.length > 1) {  // Only highlight if query is not trivial
                    row.querySelectorAll('td').forEach(cell => {
                        const original = cell.innerText;
                        if (original.toLowerCase().includes(query)) {
                            const regex = new RegExp(`(${query})`, 'gi');
                            cell.innerHTML = original.replace(regex, '<mark class="search-highlight">$1</mark>');
                        }
                    });
                }
            } else {
                row.classList.remove('highlight');
            }
        });

        // Update result counter
        const counter = table.parentElement.querySelector('.search-counter');
        if (counter) {
            counter.textContent = resultCount ? `${resultCount} result${resultCount !== 1 ? 's' : ''}` : 'No matches';
        }
    });
});

// ── Sortable Tables with Visual Indicators ───────────────────
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
                ? va.localeCompare(vb, undefined, { numeric: true, sensitivity: 'base' })
                : vb.localeCompare(va, undefined, { numeric: true, sensitivity: 'base' });
        });

        rows.forEach(r => tbody.appendChild(r));
        
        // Toggle direction
        asc = !asc;
        
        // Update sort indicators (remove all first)
        table.querySelectorAll('th[data-sortable]').forEach(t => {
            t.classList.remove('sort-asc', 'sort-desc');
            delete t.dataset.sortDir;
        });
        
        // Add current indicator
        th.classList.add(asc ? 'sort-asc' : 'sort-desc');
        th.dataset.sortDir = asc ? 'asc' : 'desc';
    });
});

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