<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg" id="faviconLink">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="../../manifest.json">
    <meta name="theme-color" content="#1E3352">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($school_name) ?> Portal">
    <title>System Settings — Admin</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }
        @media (max-width: 900px) { .settings-grid { grid-template-columns: 1fr; } }

        .settings-section {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        .settings-section.full-width { grid-column: 1 / -1; }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1.1rem 1.5rem;
            background: var(--background-main);
            border-bottom: 1px solid var(--border-color);
        }
        .section-icon { font-size: 1.25rem; }
        .section-title { font-weight: 700; font-size: 0.95rem; color: var(--text-primary); }
        .section-body { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 1.1rem; }

        .setting-row { display: flex; flex-direction: column; gap: 0.3rem; }
        .setting-row label { font-weight: 600; font-size: 0.875rem; color: var(--text-primary); }
        .setting-desc { font-size: 0.78rem; color: var(--text-secondary); margin-bottom: 0.15rem; }
        .setting-input {
            width: 100%;
            padding: 0.65rem 0.9rem;
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            color: var(--text-primary);
            background: white;
            transition: border-color 0.2s;
            font-family: inherit;
        }
        .setting-input:focus {
            outline: none;
            border-color: #3D6B9F;
            box-shadow: 0 0 0 3px rgba(61,107,159,0.12);
        }

        /* Toggle */
        .toggle-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .toggle-info { flex: 1; }
        .toggle-info .setting-label { font-weight: 600; font-size: 0.875rem; color: var(--text-primary); }
        .toggle-switch { position: relative; width: 52px; height: 28px; flex-shrink: 0; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; inset: 0;
            background: #ccc; border-radius: 28px; cursor: pointer; transition: background 0.25s;
        }
        .toggle-slider::before {
            content: ''; position: absolute;
            width: 20px; height: 20px; left: 4px; top: 4px;
            background: white; border-radius: 50%;
            transition: transform 0.25s; box-shadow: 0 1px 4px rgba(0,0,0,0.2);
        }
        .toggle-switch input:checked + .toggle-slider { background: #22c55e; }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(24px); }
        .toggle-badge { font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 999px; margin-left: 0.5rem; }
        .badge-open   { background: #dcfce7; color: #166534; }
        .badge-closed { background: #fee2e2; color: #991b1b; }

        /* Logo uploader */
        .logo-upload-area {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
        }
        .logo-preview-wrap {
            position: relative;
            width: 80px; height: 80px;
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 2px solid var(--border-color);
            flex-shrink: 0;
            background: var(--background-main);
        }
        .logo-preview-wrap img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .logo-preview-overlay {
            position: absolute; inset: 0;
            background: rgba(0,0,0,0.45);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.2s;
            cursor: pointer; color: white; font-size: 1.1rem;
        }
        .logo-preview-wrap:hover .logo-preview-overlay { opacity: 1; }
        .logo-upload-controls { display: flex; flex-direction: column; gap: 0.5rem; }
        .logo-upload-controls .setting-desc { margin: 0; }

        .logo-upload-btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.5rem 1rem;
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-md);
            background: white; cursor: pointer;
            font-size: 0.85rem; font-weight: 600;
            color: var(--text-primary);
            transition: border-color 0.2s, background 0.2s;
        }
        .logo-upload-btn:hover { border-color: #3D6B9F; background: #f0f5ff; }
        .logo-uploading { opacity: 0.6; pointer-events: none; }

        /* Save bar */
        .save-bar {
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            padding: 1rem 1.5rem; background: white;
            border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color); margin-bottom: 1.25rem;
        }
        .save-bar-info { font-size: 0.85rem; color: var(--text-secondary); }
        .save-bar-actions { display: flex; gap: 0.75rem; align-items: center; }

        /* Toast */
        .toast-container { position: fixed; top: 1.25rem; right: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem; z-index: 99999; }
        .toast {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.85rem 1.25rem; border-radius: var(--radius-md);
            background: white; box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border-left: 4px solid #22c55e; font-size: 0.875rem; font-weight: 600;
            color: var(--text-primary); animation: slideInToast 0.3s ease;
            min-width: 260px; max-width: 360px;
        }
        .toast.error { border-left-color: #ef4444; }
        .toast.info  { border-left-color: #3b82f6; }
        @keyframes slideInToast {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* Skeleton */
        .skeleton {
            background: linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);
            background-size: 200% 100%; animation: shimmer 1.4s infinite;
            border-radius: var(--radius-md); height: 2.5rem;
        }
        .skeleton-label { height: 0.9rem; width: 40%; margin-bottom: 0.5rem; }
        @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

        .dirty-dot {
            display: none; width: 8px; height: 8px; border-radius: 50%;
            background: #f59e0b; margin-left: 0.4rem; vertical-align: middle;
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">
                <img src="../../images/logo2.jpg" alt="SCC Logo" id="sidebarLogoImg"
                     style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-md);">
            </div>
            <div class="logo-text">
                <span id="sidebarSchoolName"><?= htmlspecialchars($school_name) ?></span>
                <span>Admin Portal</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                <a href="users.php" class="nav-item"><span class="nav-icon">👥</span><span>User Management</span></a>
                <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                <a href="buildings.php" class="nav-item"><span class="nav-icon">🏢</span><span>Buildings & Rooms</span></a>
                <a href="departments.php" class="nav-item"><span class="nav-icon">🏛️</span><span>Departments</span></a>
                <a href="courses.php" class="nav-item"><span class="nav-icon">🎓</span><span>Courses</span></a>
                <a href="faculty.php" class="nav-item"><span class="nav-icon">👨‍🏫</span><span>Faculty Directory</span></a>
                <a href="grades.php" class="nav-item"><span class="nav-icon">📝</span><span>Grades</span></a>
                <a href="subjects.php" class="nav-item"><span class="nav-icon">📚</span><span>Subjects</span></a>
                <a href="sections.php" class="nav-item"><span class="nav-icon">📁</span><span>Sections</span></a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">System</div>
                <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>Announcements</span></a>
                <a href="audit_logs.php" class="nav-item"><span class="nav-icon">📋</span><span>Audit Logs</span></a>
                <a href="recycle_bin.php" class="nav-item"><span class="nav-icon">🗑️</span><span>Recycle Bin</span></a>
                <a href="feedback.php" class="nav-item"><span class="nav-icon">💬</span><span>Feedback</span></a>
                <a href="account_settings.php" class="nav-item"><span class="nav-icon">👤</span><span>Profile Settings</span></a>
                <a href="settings.php" class="nav-item active"><span class="nav-icon">⚙️</span><span>System Settings</span></a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">Account</div>
                <a href="../../php/logout.php" class="nav-item"><span class="nav-icon">🚪</span><span>Logout</span></a>
            </div>
        </nav>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <div class="header-title">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button>
                <h1>System Settings <span class="dirty-dot" id="dirtyDot"></span></h1>
                <p class="page-subtitle">Configure system-wide settings</p>
            </div>
        </header>

        <!-- Save bar -->
        <div class="save-bar">
            <span class="save-bar-info" id="lastSaved">Loading settings…</span>
            <div class="save-bar-actions">
                <button class="btn btn-secondary" onclick="discardChanges()" id="discardBtn" style="display:none">↩ Discard</button>
                <button class="btn btn-primary" onclick="saveSettings()" id="saveBtn" disabled>
                    <span id="saveBtnText">💾 Save Changes</span>
                </button>
            </div>
        </div>

        <div class="settings-grid" id="settingsGrid">
            <!-- Skeletons -->
            <div class="settings-section">
                <div class="section-header"><span class="section-icon">⏳</span><span class="section-title">Loading…</span></div>
                <div class="section-body">
                    <div><div class="skeleton skeleton-label"></div><div class="skeleton"></div></div>
                    <div><div class="skeleton skeleton-label"></div><div class="skeleton" style="height:80px"></div></div>
                </div>
            </div>
            <div class="settings-section">
                <div class="section-header"><span class="section-icon">⏳</span><span class="section-title">Loading…</span></div>
                <div class="section-body">
                    <div><div class="skeleton skeleton-label"></div><div class="skeleton"></div></div>
                    <div><div class="skeleton skeleton-label"></div><div class="skeleton"></div></div>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="toast-container" id="toastContainer"></div>
<!-- Hidden file input for logo upload -->
<input type="file" id="logoFileInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">

<script>
var originalSettings = {};
var isDirty = false;

// ── Toast ────────────────────────────────────────────────────────────────────
function showToast(message, type) {
    type = type || 'success';
    var icons = { success: '✅', error: '❌', info: 'ℹ️' };
    var container = document.getElementById('toastContainer');
    var toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerHTML = '<span>' + (icons[type] || '📢') + '</span><span>' + message + '</span>';
    container.appendChild(toast);
    setTimeout(function() {
        toast.style.transition = 'all 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(40px)';
        setTimeout(function() { toast.remove(); }, 300);
    }, 3500);
}

// ── Dirty tracking ───────────────────────────────────────────────────────────
function markDirty() {
    isDirty = true;
    document.getElementById('dirtyDot').style.display = 'inline-block';
    document.getElementById('saveBtn').disabled = false;
    document.getElementById('discardBtn').style.display = '';
}
function markClean() {
    isDirty = false;
    document.getElementById('dirtyDot').style.display = 'none';
    document.getElementById('saveBtn').disabled = true;
    document.getElementById('discardBtn').style.display = 'none';
}

function escHtml(str) {
    return String(str == null ? '' : str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Load settings ────────────────────────────────────────────────────────────
async function loadSettings() {
    try {
        var res = await fetch('../../api/admin/system_settings.php');
        if (!res.ok) throw new Error('HTTP ' + res.status);
        var data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load');

        originalSettings = JSON.parse(JSON.stringify(data.settings));
        renderSettings(data.settings);
        document.getElementById('lastSaved').textContent = 'Settings loaded successfully.';
        markClean();
    } catch(err) {
        document.getElementById('settingsGrid').innerHTML =
            '<div class="settings-section full-width"><div class="section-body" style="color:#ef4444;font-weight:600;">' +
            '❌ Failed to load settings: ' + err.message +
            '<br><button class="btn btn-secondary" style="margin-top:1rem" onclick="loadSettings()">↻ Retry</button>' +
            '</div></div>';
        document.getElementById('lastSaved').textContent = 'Error loading settings.';
        showToast('Failed to load settings: ' + err.message, 'error');
    }
}

// ── Render ───────────────────────────────────────────────────────────────────
function renderSettings(settings) {
    function get(key) { return settings[key] ? settings[key].value : ''; }

    var regOpen  = get('registration_open') == '1';
    var semester = get('current_semester');
    var semesters = ['First Semester', 'Second Semester', 'Summer'];
    var semOptions = semesters.map(function(s) {
        return '<option value="' + s + '"' + (semester === s ? ' selected' : '') + '>' + s + '</option>';
    }).join('');

    // Logo: use school_logo from settings if present, else default
    var logoVal = get('school_logo');
    var logoSrc = logoVal ? '../' + logoVal + '?v=' + Date.now() : '../images/logo2.jpg';

    document.getElementById('settingsGrid').innerHTML =

        // ── School Identity (full width) ──────────────────────────────────
        '<div class="settings-section full-width">' +
            '<div class="section-header"><span class="section-icon">🏫</span><span class="section-title">School Identity</span></div>' +
            '<div class="section-body">' +

                // School Name
                '<div class="setting-row">' +
                    '<label for="school_name">School Name</label>' +
                    '<div class="setting-desc">Displayed in the sidebar and throughout the portal. Changes apply when you click Save.</div>' +
                    '<input type="text" id="school_name" class="setting-input" data-key="school_name"' +
                           ' value="' + escHtml(get('school_name')) + '"' +
                           ' placeholder="e.g. <?= htmlspecialchars($school_name) ?> Cebu Inc."' +
                           ' oninput="markDirty()">' +
                '</div>' +

                // School Address
                '<div class="setting-row">' +
                    '<label for="school_address">School Address</label>' +
                    '<div class="setting-desc">Full address shown on reports, ID cards, and the contact section.</div>' +
                    '<input type="text" id="school_address" class="setting-input" data-key="school_address"' +
                           ' value="' + escHtml(get('school_address')) + '"' +
                           ' placeholder="e.g. Poblacion Ward II, Minglanilla, Cebu Philippines 6046"' +
                           ' oninput="markDirty()">' +
                '</div>' +

                // Logo
                '<div class="setting-row">' +
                    '<label>School Logo</label>' +
                    '<div class="setting-desc">Shown in the sidebar. Recommended: square image, min 200×200px. JPG/PNG/WebP.</div>' +
                    '<div class="logo-upload-area">' +
                        '<div class="logo-preview-wrap" onclick="triggerLogoUpload()" title="Click to change logo">' +
                            '<img src="' + logoSrc + '" alt="School Logo" id="logoPreview">' +
                            '<div class="logo-preview-overlay">✏️</div>' +
                        '</div>' +
                        '<div class="logo-upload-controls">' +
                            '<label class="logo-upload-btn" onclick="triggerLogoUpload()">' +
                                '📂 Choose New Logo' +
                            '</label>' +
                            '<div class="setting-desc" id="logoStatus">Click the image or button to upload a new logo.</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +

            '</div>' +
        '</div>' +

        // ── Contact Information ───────────────────────────────────────────
        '<div class=\"settings-section full-width\">' +
            '<div class=\"section-header\"><span class=\"section-icon\">📞</span><span class=\"section-title\">Contact Information</span></div>' +
            '<div class=\"section-body\">' +

                // Email
                '<div class=\"setting-row\">' +
                    '<label for=\"school_email\">School Email</label>' +
                    '<div class=\"setting-desc\">Main contact email shown on login, index, and outgoing emails.</div>' +
                    '<input type=\"email\" id=\"school_email\" class=\"setting-input\" data-key=\"school_email\"' +
                           ' value=\"' + escHtml(get('school_email')) + '\"' +
                           ' placeholder=\"e.g. info@school.edu.ph\"' +
                           ' oninput=\"markDirty()\">' +
                '</div>' +

                // Phone
                '<div class=\"setting-row\">' +
                    '<label for=\"school_phone\">Contact Number</label>' +
                    '<div class=\"setting-desc\">Primary phone number shown on the login and index pages.</div>' +
                    '<input type=\"text\" id=\"school_phone\" class=\"setting-input\" data-key=\"school_phone\"' +
                           ' value=\"' + escHtml(get('school_phone')) + '\"' +
                           ' placeholder=\"e.g. (032) 268-4746\"' +
                           ' oninput=\"markDirty()\">' +
                '</div>' +

                // Registrar Phone
                '<div class=\"setting-row\">' +
                    '<label for=\"registrar_phone\">Registrar\'s Office Number</label>' +
                    '<div class=\"setting-desc\">Shown in the login help box and on student-facing pages.</div>' +
                    '<input type=\"text\" id=\"registrar_phone\" class=\"setting-input\" data-key=\"registrar_phone\"' +
                           ' value=\"' + escHtml(get('registrar_phone')) + '\"' +
                           ' placeholder=\"e.g. (032) 326-3677\"' +
                           ' oninput=\"markDirty()\">' +
                '</div>' +

                // Website
                '<div class=\"setting-row\">' +
                    '<label for=\"school_website\">Official Website</label>' +
                    '<div class=\"setting-desc\">Link shown in the footer of public pages.</div>' +
                    '<input type=\"text\" id=\"school_website\" class=\"setting-input\" data-key=\"school_website\"' +
                           ' value=\"' + escHtml(get('school_website')) + '\"' +
                           ' placeholder=\"e.g. https://www.school.edu.ph\"' +
                           ' oninput=\"markDirty()\">' +
                '</div>' +
                // Facebook
                '<div class=\"setting-row\">' +
                    '<label for=\"school_facebook\">Facebook Page URL</label>' +
                    '<div class=\"setting-desc\">Link to the official Facebook page shown in Quick Links.</div>' +
                    '<input type=\"text\" id=\"school_facebook\" class=\"setting-input\" data-key=\"school_facebook\"' +
                           ' value=\"' + escHtml(get('school_facebook')) + '\"' +
                           ' placeholder=\"e.g. https://www.facebook.com/YourSchool\"' +
                           ' oninput=\"markDirty()\">' +
                '</div>' +

            '</div>' +
        '</div>' +

        // ── Academic Period ───────────────────────────────────────────────
        '<div class="settings-section">' +
            '<div class="section-header"><span class="section-icon">📅</span><span class="section-title">Academic Period</span></div>' +
            '<div class="section-body">' +
                '<div class="setting-row">' +
                    '<label for="current_school_year">Current School Year</label>' +
                    '<div class="setting-desc">Format: YYYY-YYYY (e.g. 2024-2025)</div>' +
                    '<input type="text" id="current_school_year" class="setting-input" data-key="current_school_year"' +
                           ' value="' + escHtml(get('current_school_year')) + '" placeholder="2024-2025">' +
                '</div>' +
                '<div class="setting-row">' +
                    '<label for="current_semester">Current Semester</label>' +
                    '<div class="setting-desc">The active semester for enrollment and grading</div>' +
                    '<select id="current_semester" class="setting-input" data-key="current_semester">' + semOptions + '</select>' +
                '</div>' +
            '</div>' +
        '</div>' +

        // ── Enrollment Control ────────────────────────────────────────────
        '<div class="settings-section">' +
            '<div class="section-header"><span class="section-icon">🎓</span><span class="section-title">Enrollment Control</span></div>' +
            '<div class="section-body">' +
                '<div class="toggle-row">' +
                    '<div class="toggle-info">' +
                        '<div class="setting-label">Student Registration / Enrollment ' +
                            '<span class="toggle-badge ' + (regOpen ? 'badge-open' : 'badge-closed') + '" id="regBadge">' +
                                (regOpen ? 'OPEN' : 'CLOSED') +
                            '</span>' +
                        '</div>' +
                        '<div class="setting-desc">When ON, students can submit enrollment applications. When OFF, enrollment form is locked.</div>' +
                    '</div>' +
                    '<label class="toggle-switch">' +
                        '<input type="checkbox" id="registration_open" data-key="registration_open"' +
                               (regOpen ? ' checked' : '') + ' onchange="onToggleChange(this)">' +
                        '<span class="toggle-slider"></span>' +
                    '</label>' +
                '</div>' +
            '</div>' +
        '</div>';

    // Sync sidebar with current loaded name
    updateSidebarName(get('school_name'));

    // Attach change listeners
    document.querySelectorAll('.setting-input').forEach(function(el) {
        el.addEventListener('input', markDirty);
        el.addEventListener('change', markDirty);
    });
}

// ── Update sidebar name (called only on save/load) ────────────────────────────
function updateSidebarName(name) {
    var el = document.getElementById('sidebarSchoolName');
    if (el) el.textContent = name || 'School Name';
}

// ── Toggle ───────────────────────────────────────────────────────────────────
function onToggleChange(checkbox) {
    var badge = document.getElementById('regBadge');
    if (checkbox.checked) {
        badge.textContent = 'OPEN';
        badge.className = 'toggle-badge badge-open';
    } else {
        badge.textContent = 'CLOSED';
        badge.className = 'toggle-badge badge-closed';
    }
    markDirty();
}

// ── Logo upload ───────────────────────────────────────────────────────────────
function triggerLogoUpload() {
    document.getElementById('logoFileInput').click();
}

document.getElementById('logoFileInput').addEventListener('change', async function() {
    var file = this.files[0];
    if (!file) return;

    var status  = document.getElementById('logoStatus');
    var preview = document.getElementById('logoPreview');
    var wrap    = preview.parentElement;

    // Show local preview immediately
    var reader = new FileReader();
    reader.onload = function(e) { preview.src = e.target.result; };
    reader.readAsDataURL(file);

    status.textContent = '⏳ Uploading…';
    wrap.classList.add('logo-uploading');

    var formData = new FormData();
    formData.append('logo', file);

    try {
        var res = await fetch('../../api/admin/upload_school_logo.php', {
            method: 'POST',
            body: formData
        });
        var data = await res.json();

        if (data.success) {
            // Store new logo path for when Save is clicked — do NOT update sidebar/favicon yet
            var logoInput = document.querySelector('.setting-input[data-key="school_logo"]');
            if (logoInput) logoInput.value = data.logo_relative || '';
            status.textContent = '✅ Logo selected. Click Save Changes to apply.';
            showToast('Logo ready — click Save Changes to apply.', 'info');
            markDirty();
        } else {
            preview.src = preview.dataset.original || '../images/logo2.jpg';
            status.textContent = '❌ ' + (data.message || 'Upload failed');
            showToast(data.message || 'Logo upload failed', 'error');
        }
    } catch(err) {
        status.textContent = '❌ Upload error: ' + err.message;
        showToast('Upload error: ' + err.message, 'error');
    } finally {
        wrap.classList.remove('logo-uploading');
        this.value = '';
    }
});

// ── Save settings ─────────────────────────────────────────────────────────────
async function saveSettings() {
    if (!isDirty) return;

    var saveBtn     = document.getElementById('saveBtn');
    var saveBtnText = document.getElementById('saveBtnText');
    saveBtn.disabled = true;
    saveBtnText.textContent = '⏳ Saving…';

    // Start with originalSettings so keys not on this form are never wiped
    var settingsData = JSON.parse(JSON.stringify(originalSettings));
    // Flatten: originalSettings values are objects {value, description}, we need raw strings
    Object.keys(settingsData).forEach(function(k) {
        if (settingsData[k] && typeof settingsData[k] === 'object' && 'value' in settingsData[k]) {
            settingsData[k] = settingsData[k].value;
        }
    });
    // Overlay with current form values — but never save an empty string over an existing value
    document.querySelectorAll('.setting-input[data-key]').forEach(function(el) {
        var key = el.dataset.key;
        var val = el.value.trim();
        // Only update if the field has a value, OR if it was already empty in DB
        if (val !== '' || !settingsData[key]) {
            settingsData[key] = el.value;
        }
    });
    var regToggle = document.getElementById('registration_open');
    if (regToggle) settingsData['registration_open'] = regToggle.checked ? '1' : '0';

    // Validate school year format
    var sy = settingsData['current_school_year'] || '';
    if (sy && !/^\d{4}-\d{4}$/.test(sy)) {
        showToast('School year must be YYYY-YYYY (e.g. 2024-2025)', 'error');
        saveBtn.disabled = false;
        saveBtnText.textContent = '💾 Save Changes';
        return;
    }

    try {
        var res = await fetch('../../api/admin/system_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ settings: settingsData })
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        var result = await res.json();

        if (result.success) {
            var now = new Date().toLocaleTimeString();
            document.getElementById('lastSaved').textContent = 'Last saved at ' + now;
            originalSettings = JSON.parse(JSON.stringify(settingsData));
            markClean();
            showToast(result.message || 'Settings saved!', 'success');

            // Apply branding to THIS page immediately on save
            var newName = settingsData['school_name'] || '';
            var newLogo = settingsData['school_logo'] || '';
            if (newName) {
                var nameEl = document.getElementById('sidebarSchoolName');
                if (nameEl) nameEl.textContent = newName;
            }
            if (newLogo) {
                var logoEl = document.getElementById('sidebarLogoImg');
                if (logoEl) logoEl.src = '../' + newLogo + '?v=' + Date.now();
                var faviconEl = document.getElementById('faviconLink');
                if (faviconEl) faviconEl.href = '../' + newLogo + '?v=' + Date.now();
            }

            // Broadcast to all other open tabs instantly
            try {
                localStorage.setItem('branding_updated', JSON.stringify({
                    school_name: newName,
                    school_logo: newLogo,
                    ts: Date.now()
                }));
            } catch(e) {}
        } else {
            throw new Error(result.message || 'Save failed');
        }
    } catch(err) {
        showToast('Error saving: ' + err.message, 'error');
    } finally {
        saveBtnText.textContent = '💾 Save Changes';
        saveBtn.disabled = !isDirty;
    }
}

// ── Discard ───────────────────────────────────────────────────────────────────
function discardChanges() {
    if (!isDirty) return;
    if (!confirm('Discard all unsaved changes?')) return;
    renderSettings(originalSettings);
    markClean();
    showToast('Changes discarded.', 'info');
}

window.addEventListener('beforeunload', function(e) {
    if (isDirty) { e.preventDefault(); e.returnValue = ''; }
});

loadSettings();
</script>

<script>
(function() {
    var sidebar = document.querySelector('.sidebar');
    var saved = sessionStorage.getItem('sidebarScroll');
    if (saved) sidebar.scrollTop = parseInt(saved);
    document.querySelectorAll('.nav-item').forEach(function(link) {
        link.addEventListener('click', function() {
            sessionStorage.setItem('sidebarScroll', sidebar.scrollTop);
        });
    });
})();
</script>
<script src="../../js/theme-switcher.js"></script>
<script>
(function() {
    var toggle  = document.getElementById('sidebarToggle');
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (!toggle || !sidebar) return;
    function openSidebar()  { sidebar.classList.add('active');    overlay && overlay.classList.add('active');    document.body.style.overflow = 'hidden'; }
    function closeSidebar() { sidebar.classList.remove('active'); overlay && overlay.classList.remove('active'); document.body.style.overflow = ''; }
    toggle.addEventListener('click', function() { sidebar.classList.contains('active') ? closeSidebar() : openSidebar(); });
    overlay && overlay.addEventListener('click', closeSidebar);
    document.querySelectorAll('.nav-item').forEach(function(link) {
        link.addEventListener('click', function() { if (window.innerWidth <= 1024) closeSidebar(); });
    });
})();
</script>
<script src="../../js/pwa.js"></script>
<script src="../../js/session-monitor.js"></script>

    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
      <a href="dashboard.php" class="mobile-nav-item" data-page="dashboard">
        <span class="mobile-nav-icon">📊</span><span>Home</span>
      </a>
      <a href="users.php" class="mobile-nav-item" data-page="users">
        <span class="mobile-nav-icon">👥</span><span>Users</span>
      </a>
      <a href="sections.php" class="mobile-nav-item" data-page="sections">
        <span class="mobile-nav-icon">📁</span><span>Sections</span>
      </a>
      <a href="announcements.php" class="mobile-nav-item" data-page="announcements">
        <span class="mobile-nav-icon">📢</span><span>Notices</span>
      </a>
      <a href="account_settings.php" class="mobile-nav-item" data-page="account_settings">
        <span class="mobile-nav-icon">👤</span><span>Profile</span>
      </a>
    </nav>

    <script>
    // Auto-highlight mobile bottom nav item
    (function() {
      var page = location.pathname.split('/').pop().replace('.php','');
      document.querySelectorAll('.mobile-nav-item').forEach(function(el) {
        if (el.dataset.page === page) el.classList.add('active');
      });
    })();
    </script>

</body>
</html>
