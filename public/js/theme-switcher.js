/**
 * School Management System – Theme Switcher (Per-User, Server-Persisted)
 *
 * How it works:
 * 1. Reads localStorage instantly to avoid any colour flash on page load
 * 2. After DOM ready, calls the server to get THIS user's saved theme (may differ from localStorage if they logged in on another device)
 * 3. When user picks a colour, saves to both localStorage (instant) AND the server DB (persistent)
 *
 * API path: The <script> tag that loads this file should have a data-api attribute:
 *   <script src="../js/theme-switcher.js" data-api="../php/api/save_theme.php"></script>
 * If data-api is missing, we fall back to deriving it from document.currentScript.src.
 */
(function () {
    'use strict';

    var STORAGE_KEY   = 'sms_theme';
    var DEFAULT_THEME = 'ocean';

    /* ── Resolve the API URL ──────────────────────────────────────────── */
    var API_URL = (function () {
        // Preferred: explicit data-api attribute on the <script> tag
        var scripts = document.querySelectorAll('script[data-api]');
        if (scripts.length > 0) {
            return scripts[scripts.length - 1].getAttribute('data-api');
        }
        // Fallback 1: document.currentScript (works when script runs synchronously)
        if (document.currentScript && document.currentScript.src) {
            return document.currentScript.src.replace(/\/js\/theme-switcher\.js(\?.*)?$/, '/php/api/save_theme.php');
        }
        // Fallback 2: find any loaded theme-switcher script tag
        var all = document.querySelectorAll('script[src*="theme-switcher"]');
        if (all.length > 0) {
            return all[all.length - 1].src.replace(/\/js\/theme-switcher\.js(\?.*)?$/, '/php/api/save_theme.php');
        }
        // Fallback 3: relative path (assumes page is one level deep, e.g. /admin/ /student/)
        return '../php/api/save_theme.php';
    })();

    var themes = [
        { id: 'ocean',    name: 'Ocean Blue',    sub: 'Default · Steel & Navy',   left: 'linear-gradient(160deg,#3FA9F5,#5C58ED)', right: '#122D70' },
        { id: 'cyan',     name: 'Cyan Indigo',   sub: 'Electric · Vivid Blue',    left: 'linear-gradient(160deg,#56E1E8,#3FA9F5)', right: '#5C58ED' },
        { id: 'rose',     name: 'Rose & Cream',  sub: 'Warm · Crimson Pink',      left: 'linear-gradient(160deg,#850e35,#ee6983)', right: '#ffc4c4' },
        { id: 'jade',     name: 'Forest Jade',   sub: 'Natural · Earthy Green',   left: 'linear-gradient(160deg,#1A4A38,#2D7D62)', right: '#5AB896' },
        { id: 'amethyst', name: 'Amethyst Dusk', sub: 'Rich · Violet Purple',     left: 'linear-gradient(160deg,#2E1065,#7C3AED)', right: '#A78BFA' },
    ];

    /* ── Apply theme to DOM ───────────────────────────────────────────── */
    function applyTheme(id, skipSave) {
        document.documentElement.setAttribute('data-theme', id);
        localStorage.setItem(STORAGE_KEY, id);
        document.querySelectorAll('[data-theme-id]').forEach(function (el) {
            el.classList.toggle('active-theme', el.dataset.themeId === id);
        });
        if (!skipSave) {
            saveToServer(id);
        }
    }

    /* ── Save to server DB ────────────────────────────────────────────── */
    function saveToServer(id) {
        fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ theme: id })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.success) {
                console.log('[Theme] Saved "' + id + '" to your account (affected rows: ' + data.affected + ')');
            } else {
                console.warn('[Theme] Server save failed:', data);
            }
        })
        .catch(function (e) {
            console.warn('[Theme] Could not reach save_theme.php:', e.message, '| API_URL:', API_URL);
        });
    }

    /* ── Load from server (on page load, sync with DB) ───────────────── */
    function loadFromServer() {
        fetch(API_URL, { credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (data) {
                if (data && data.success && data.theme) {
                    var local = localStorage.getItem(STORAGE_KEY);
                    if (data.theme !== local) {
                        console.log('[Theme] Server theme "' + data.theme + '" differs from local "' + local + '", applying server theme.');
                        applyTheme(data.theme, true); // apply server theme without re-saving
                        var picker = document.getElementById('inlineThemePicker');
                        if (picker) buildPicker(picker);
                    } else {
                        console.log('[Theme] Server theme matches local: "' + data.theme + '"');
                    }
                } else if (data && !data.theme) {
                    // No theme saved yet on server — save current local to server
                    var local = localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME;
                    console.log('[Theme] No server theme yet, pushing local "' + local + '" to server.');
                    saveToServer(local);
                }
            })
            .catch(function (e) {
                console.warn('[Theme] Could not load theme from server (using localStorage):', e.message);
            });
    }

    /* ── Build the inline colour picker ──────────────────────────────── */
    function buildPicker(container) {
        var current = localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME;
        container.innerHTML = '';
        themes.forEach(function (t) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'inline-theme-option' + (t.id === current ? ' active-theme' : '');
            btn.dataset.themeId = t.id;
            btn.title = t.name;
            btn.innerHTML =
                '<div class="inline-swatch-preview">' +
                    '<div class="swatch-left"  style="background:' + t.left  + ';"></div>' +
                    '<div class="swatch-right" style="background:' + t.right + ';"></div>' +
                '</div>' +
                '<div class="inline-theme-name">' + t.name + '</div>' +
                '<div class="inline-theme-sub">'  + t.sub  + '</div>' +
                '<div class="theme-account-label">Saved to your account</div>';
            btn.addEventListener('click', function () {
                applyTheme(t.id, false);   // apply + save to server
                container.querySelectorAll('[data-theme-id]').forEach(function (b) {
                    b.classList.toggle('active-theme', b.dataset.themeId === t.id);
                });
                showToast(t.name);
            });
            container.appendChild(btn);
        });
    }

    /* ── Toast notification ───────────────────────────────────────────── */
    function showToast(name) {
        var old = document.getElementById('_themeToast');
        if (old) old.remove();
        var t = document.createElement('div');
        t.id = '_themeToast';
        t.style.cssText = [
            'position:fixed', 'bottom:24px', 'right:24px', 'z-index:99999',
            'background:var(--primary-purple,#5C58ED)', 'color:#fff',
            'padding:10px 20px', 'border-radius:10px', 'font-size:0.87rem',
            'font-weight:600', 'box-shadow:0 4px 20px rgba(0,0,0,0.22)',
            'opacity:0', 'transition:opacity 0.25s', 'pointer-events:none'
        ].join(';');
        t.textContent = '\uD83C\uDFA8 \u201C' + name + '\u201D saved to your account!';
        document.body.appendChild(t);
        requestAnimationFrame(function () { t.style.opacity = '1'; });
        setTimeout(function () {
            t.style.opacity = '0';
            setTimeout(function () { if (t.parentNode) t.remove(); }, 300);
        }, 2800);
    }

    /* ── Boot ─────────────────────────────────────────────────────────── */

    // INSTANT: apply localStorage theme RIGHT NOW (before DOMContentLoaded) to prevent flash
    var _fast = localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME;
    document.documentElement.setAttribute('data-theme', _fast);

    function init() {
        // Re-apply (also updates any data-theme-id elements that are now in DOM)
        applyTheme(localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME, true);

        // Build inline picker if the page has one
        var picker = document.getElementById('inlineThemePicker');
        if (picker) buildPicker(picker);

        // Async sync with server
        loadFromServer();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
