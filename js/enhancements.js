/* =================================================================
   SYSTEM ENHANCEMENTS JS — applies to all admin pages
   ================================================================= */

/* ── 1. PAGE LOADING BAR ─────────────────────────────────────────── */
(function () {
    // Create bar element
    const bar = document.createElement('div');
    bar.id = 'page-progress-bar';
    document.body.prepend(bar);

    function startBar() {
        bar.style.opacity = '1';
        bar.style.width = '70%';
    }
    function finishBar() {
        bar.style.width = '100%';
        setTimeout(() => { bar.style.opacity = '0'; bar.style.width = '0%'; }, 400);
    }

    // Trigger on all nav link clicks
    document.querySelectorAll('a.nav-item, a[href]').forEach(link => {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href && !href.startsWith('#') && !href.startsWith('javascript') && !this.target) {
                startBar();
            }
        });
    });
    window.addEventListener('pageshow', finishBar);
    finishBar();
})();

/* ── 2. TOAST NOTIFICATION SYSTEM ───────────────────────────────── */
(function () {
    // Create toast container
    const container = document.createElement('div');
    container.id = 'scc-toast-container';
    document.body.appendChild(container);

    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };

    window.sccToast = function (message, type = 'success', duration = 3500) {
        const toast = document.createElement('div');
        toast.className = `scc-toast ${type}`;
        toast.innerHTML = `
            <span class="scc-toast-icon">${icons[type] || '✅'}</span>
            <span class="scc-toast-msg">${message}</span>
            <button class="scc-toast-close" onclick="this.parentElement.remove()">×</button>
        `;
        container.appendChild(toast);

        // Auto remove
        setTimeout(() => {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 280);
        }, duration);
    };

    // Override the existing showToast function if present
    window.showToast = function (msg, type = 'success') {
        sccToast(msg, type === 'success' ? 'success' : 'error');
    };
})();

/* ── 3. STYLED CONFIRM DIALOG ────────────────────────────────────── */
window.sccConfirm = function ({ title, message, type = 'danger', confirmText = 'Confirm', cancelText = 'Cancel' }) {
    return new Promise((resolve) => {
        const icons   = { danger: '🗑️', warning: '⚠️', info: 'ℹ️' };
        const overlay = document.createElement('div');
        overlay.className = 'scc-confirm-overlay';
        overlay.innerHTML = `
            <div class="scc-confirm-box">
                <div class="scc-confirm-header">
                    <div class="scc-confirm-icon ${type}">${icons[type] || '⚠️'}</div>
                    <div class="scc-confirm-title">${title}</div>
                </div>
                <div class="scc-confirm-body">${message}</div>
                <div class="scc-confirm-footer">
                    <button class="scc-btn scc-btn-cancel" id="scc-cancel">${cancelText}</button>
                    <button class="scc-btn scc-btn-${type}" id="scc-confirm">${confirmText}</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        overlay.querySelector('#scc-confirm').addEventListener('click', () => {
            overlay.remove(); resolve(true);
        });
        overlay.querySelector('#scc-cancel').addEventListener('click', () => {
            overlay.remove(); resolve(false);
        });
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) { overlay.remove(); resolve(false); }
        });
    });
};

/* ── 4. LOADING SKELETON HELPER ──────────────────────────────────── */
window.sccSkeletonRows = function (count = 5, cols = 7) {
    const skeletonCol = `
        <div class="scc-skeleton scc-skeleton-badge"></div>
    `;
    const rows = Array.from({ length: count }, () => `
        <div class="scc-skeleton-row">
            <div class="scc-skeleton scc-skeleton-name"></div>
            <div class="scc-skeleton scc-skeleton-email"></div>
            ${Array.from({ length: Math.max(0, cols - 4) }, () => `<div class="scc-skeleton scc-skeleton-badge"></div>`).join('')}
            <div class="scc-skeleton scc-skeleton-date"></div>
            <div class="scc-skeleton scc-skeleton-btn"></div>
        </div>
    `).join('');
    return `<div class="scc-skeleton-wrap">${rows}</div>`;
};

/* ── 5. SORTABLE TABLE COLUMNS ───────────────────────────────────── */
window.sccMakeSortable = function (tableSelector) {
    const table = document.querySelector(tableSelector);
    if (!table) return;

    const headers = table.querySelectorAll('thead th');
    let sortCol = -1, sortAsc = true;

    headers.forEach((th, idx) => {
        // Skip action columns
        if (th.textContent.trim().toLowerCase().includes('action')) return;

        th.classList.add('sortable');
        th.innerHTML += ' <span class="sort-icon">▲</span>';

        th.addEventListener('click', () => {
            if (sortCol === idx) {
                sortAsc = !sortAsc;
            } else {
                sortCol = idx; sortAsc = true;
                headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
            }
            th.classList.toggle('sort-asc', sortAsc);
            th.classList.toggle('sort-desc', !sortAsc);

            const tbody = table.querySelector('tbody');
            const rows  = Array.from(tbody.querySelectorAll('tr'));

            rows.sort((a, b) => {
                const aText = (a.cells[idx]?.textContent || '').trim().toLowerCase();
                const bText = (b.cells[idx]?.textContent || '').trim().toLowerCase();
                return sortAsc ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });
            rows.forEach(row => tbody.appendChild(row));
        });
    });
};

/* ── 6. APPLY ROLE COLOR BADGES ──────────────────────────────────── */
window.sccRoleBadge = function (role) {
    const map = { student: 'role-student', teacher: 'role-teacher', admin: 'role-admin', registrar: 'role-registrar', hr: 'role-hr' };
    const cls = map[role] || 'role-student';
    return `<span class="role-badge ${cls}">${role}</span>`;
};

/* ── 7. ANIMATE STAT CARD NUMBERS ────────────────────────────────── */
window.sccAnimateCount = function (el, target, duration = 800) {
    const start = performance.now();
    const from  = parseInt(el.textContent) || 0;
    function step(now) {
        const progress = Math.min((now - start) / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        el.textContent = Math.round(from + (target - from) * ease);
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
};
