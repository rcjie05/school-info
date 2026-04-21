/**
 * SCC Session Monitor - 20 minute inactivity timeout
 * Uses sessionStorage (clears when tab closes, no stale data)
 * Only runs on authenticated pages
 */
(function () {
    // Only run inside authenticated folders
    const path = window.location.pathname;
    // Check if on authenticated page (role folders OR php/ folder with session)
    const isAuthPage = /\/(admin|hr|student|teacher|registrar|php)\//i.test(path)
                    && !/login|register|index/i.test(path);
    if (!isAuthPage) return;

    const TIMEOUT_MS     = 20 * 60 * 1000; // 20 minutes
    const WARN_MS        = 2  * 60 * 1000; // warn at 2 minutes left
    const KEY            = 'scc_last_active';

    let warnTimer   = null;
    let logoutTimer = null;
    let warned      = false;

    // Always stamp activity on page load
    stamp();

    // Listen for activity
    ['click','keydown','mousedown','scroll','touchstart'].forEach(e =>
        document.addEventListener(e, onActivity, { passive: true })
    );

    function stamp() {
        sessionStorage.setItem(KEY, Date.now().toString());
    }

    function onActivity() {
        stamp();
        if (warned) hideWarning();
        resetTimers();
    }

    function resetTimers() {
        clearTimeout(warnTimer);
        clearTimeout(logoutTimer);

        // Show warning 2 min before logout
        warnTimer   = setTimeout(showWarning,  TIMEOUT_MS - WARN_MS);
        // Auto logout after full timeout
        logoutTimer = setTimeout(doLogout,     TIMEOUT_MS);
    }

    function showWarning() {
        warned = true;
        if (document.getElementById('scc-warn')) return;

        const div = document.createElement('div');
        div.id = 'scc-warn';
        div.innerHTML = `
        <div style="position:fixed;top:0;left:0;right:0;z-index:999999;
            background:#b45309;color:#fff;padding:.75rem 1.5rem;
            display:flex;align-items:center;justify-content:space-between;gap:1rem;
            font-family:inherit;font-size:.875rem;font-weight:600;
            box-shadow:0 4px 16px rgba(0,0,0,.3);">
            <span>⚠️ You've been inactive. Session expires in <strong id="scc-secs">2:00</strong>.</span>
            <div style="display:flex;gap:.5rem">
                <button onclick="SCCSession.extend()" style="background:#fff;color:#b45309;border:none;
                    padding:.4rem .85rem;border-radius:6px;font-weight:700;cursor:pointer;">
                    Stay Logged In
                </button>
                <button onclick="SCCSession.logout()" style="background:rgba(255,255,255,.15);
                    color:#fff;border:1px solid rgba(255,255,255,.4);
                    padding:.4rem .85rem;border-radius:6px;font-weight:700;cursor:pointer;">
                    Logout
                </button>
            </div>
        </div>`;
        document.body.prepend(div);

        // Countdown display
        let secs = Math.floor(WARN_MS / 1000);
        const el = document.getElementById('scc-secs');
        const tick = setInterval(() => {
            secs--;
            if (!document.getElementById('scc-secs')) { clearInterval(tick); return; }
            const m = Math.floor(secs / 60);
            const s = String(secs % 60).padStart(2, '0');
            if (el) el.textContent = `${m}:${s}`;
            if (secs <= 0) clearInterval(tick);
        }, 1000);
    }

    function hideWarning() {
        warned = false;
        const el = document.getElementById('scc-warn');
        if (el) el.remove();
    }

    function doLogout() {
        sessionStorage.removeItem(KEY);
        // Find project root by locating known folders
        const segments = path.replace(/\/+$/, '').split('/').filter(Boolean);
        const authIdx  = segments.findIndex(s => /^(admin|hr|student|teacher|registrar|php)$/i.test(s));
        const ups      = authIdx >= 0 ? segments.length - authIdx : 1;
        const base     = '../'.repeat(ups);
        window.location.href = base + 'login.html?error=session_expired';
    }

    // Public API
    window.SCCSession = {
        extend() {
            stamp();
            hideWarning();
            resetTimers();
            if (typeof showToast === 'function') showToast('✅ Session extended!', 'success');
        },
        logout() {
            sessionStorage.removeItem(KEY);
            const segments = path.replace(/\/+$/, '').split('/').filter(Boolean);
            const authIdx  = segments.findIndex(s => /^(admin|hr|student|teacher|registrar|php)$/i.test(s));
            const ups = authIdx >= 0 ? segments.length - authIdx : 1;
            const base = '../'.repeat(ups);
            window.location.href = base + 'php/logout.php';
        }
    };

    // Start timers
    resetTimers();

})();
