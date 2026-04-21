/**
 * apply-branding.js — v3
 *
 * Two update mechanisms:
 *  1. INSTANT  — listens for a localStorage 'branding_updated' event that
 *                admin/settings.php fires when the admin saves changes.
 *                This updates ALL open tabs in the same browser immediately.
 *  2. FALLBACK — fetches from the API on page load, and polls every 30 s
 *                to catch changes from other browsers / devices.
 *
 * Targets on every page:
 *   #sidebarSchoolName  — school name span
 *   #sidebarLogoImg     — sidebar logo img
 *   #faviconLink        — favicon link (optional)
 */
(function () {
    var API_URL      = '../php/api/get_system_branding.php';
    var DEFAULT_LOGO = '../images/logo2.jpg';
    var POLL_MS      = 30000;

    // ── Apply branding to the DOM ────────────────────────────────────────────
    function applyBranding(name, logoRel) {
        // School name
        var nameEl = document.getElementById('sidebarSchoolName');
        if (nameEl && name) {
            nameEl.textContent = name;
        }

        // School logo
        var logoEl = document.getElementById('sidebarLogoImg');
        if (logoEl) {
            var logoSrc = logoRel ? '../' + logoRel + '?v=' + Date.now() : DEFAULT_LOGO;
            var current = (logoEl.getAttribute('src') || '').split('?')[0];
            var next    = logoSrc.split('?')[0];
            if (current !== next) {
                logoEl.src = logoSrc;
            }
            if (name) logoEl.alt = name + ' Logo';
        }

        // Favicon
        if (logoRel) {
            var favicon = document.getElementById('faviconLink');
            if (favicon) {
                favicon.href = '../' + logoRel + '?v=' + Date.now();
            }
        }
    }

    // ── Fetch from API and apply ─────────────────────────────────────────────
    function fetchAndApply() {
        fetch(API_URL, { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.success) {
                    applyBranding(data.school_name || '', data.school_logo || '');
                }
            })
            .catch(function () { /* silent fail — keep hardcoded fallback */ });
    }

    // ── Instant cross-tab update via localStorage ────────────────────────────
    // admin/settings.php writes to localStorage key 'branding_updated' on save.
    // All other open tabs receive the 'storage' event instantly.
    window.addEventListener('storage', function (e) {
        if (e.key === 'branding_updated' && e.newValue) {
            try {
                var data = JSON.parse(e.newValue);
                applyBranding(data.school_name || '', data.school_logo || '');
            } catch (err) {}
        }
    });

    // Also check localStorage on load in case this tab was opened after a save
    // (the 'storage' event only fires in OTHER tabs, not the one that wrote it)
    try {
        var stored = localStorage.getItem('branding_updated');
        if (stored) {
            var cached = JSON.parse(stored);
            // Only use cache if it's less than 10 minutes old
            if (cached && cached.ts && (Date.now() - cached.ts) < 600000) {
                applyBranding(cached.school_name || '', cached.school_logo || '');
            }
        }
    } catch (err) {}

    // ── Fetch from API on load (source of truth) ─────────────────────────────
    fetchAndApply();

    // ── Poll every 30 s (catches changes from other devices/browsers) ────────
    setInterval(fetchAndApply, POLL_MS);
})();
