<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('hr');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="../../../public/manifest.json">
    <meta name="theme-color" content="#1E3352">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($school_name) ?> Portal">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <title>Attendance Tracking - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        /* ── Toolbar ─────────────────────────────────── */
        .att-toolbar { display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1.25rem; align-items:center; }
        .att-toolbar input[type="date"],
        .att-toolbar select { padding:0.55rem 1rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-family:inherit; font-size:0.88rem; background:white; }
        .att-toolbar label { font-size:0.82rem; font-weight:600; color:var(--text-secondary); white-space:nowrap; }
        .att-date-group { display:flex; align-items:center; gap:0.5rem; }

        /* ── Stats Bar ────────────────────────────────── */
        .att-stats { display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.25rem; }
        .att-stat  { background:white; border-radius:var(--radius-md); padding:0.85rem 1.25rem; box-shadow:var(--shadow-sm); border-top:3px solid #e5e7eb; flex:1; min-width:110px; text-align:center; }
        .att-stat.present { border-top-color:#10b981; }
        .att-stat.absent  { border-top-color:#ef4444; }
        .att-stat.late    { border-top-color:#f59e0b; }
        .att-stat.half    { border-top-color:#3b82f6; }
        .att-stat.leave   { border-top-color:#8b5cf6; }
        .att-stat-val   { font-size:1.6rem; font-weight:800; }
        .att-stat-label { font-size:0.72rem; color:var(--text-secondary); font-weight:600; text-transform:uppercase; letter-spacing:0.4px; }

        /* ── Table ───────────────────────────────────── */
        .att-table { width:100%; border-collapse:collapse; font-size:0.875rem; }
        .att-table th { background:#f8fafc; font-size:0.75rem; font-weight:700; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; padding:0.75rem 1rem; text-align:left; border-bottom:1px solid #e5e7eb; }
        .att-table td { padding:0.75rem 1rem; border-bottom:1px solid #f5f5f5; vertical-align:middle; }
        .att-table tr:hover td { background:#f8fafc; }
        .att-table tr:last-child td { border-bottom:none; }

        /* ── Employee cell ───────────────────────────── */
        .emp-cell { display:flex; align-items:center; gap:0.65rem; }
        .emp-avatar-sm { width:34px; height:34px; border-radius:50%; background:var(--primary-purple); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.82rem; flex-shrink:0; overflow:hidden; }
        .emp-avatar-sm img { width:100%; height:100%; object-fit:cover; }

        /* ── Status badges ───────────────────────────── */
        .badge { display:inline-block; padding:0.2rem 0.65rem; border-radius:999px; font-size:0.72rem; font-weight:700; text-transform:uppercase; }
        .badge-present  { background:#d1fae5; color:#065f46; }
        .badge-absent   { background:#fee2e2; color:#991b1b; }
        .badge-late     { background:#fef3c7; color:#92400e; }
        .badge-half_day { background:#dbeafe; color:#1e40af; }
        .badge-on_leave { background:#ede9fe; color:#5b21b6; }

        /* ── Inline status select ────────────────────── */
        .status-select { padding:0.25rem 0.5rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-size:0.8rem; font-family:inherit; cursor:pointer; }
        .status-select:focus { outline:none; border-color:var(--primary-purple); }
        .time-input { padding:0.25rem 0.5rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-size:0.8rem; font-family:inherit; width:90px; }
        .time-input:focus { outline:none; border-color:var(--primary-purple); }

        /* ── Toast ────────────────────────────────────── */
        .toast { position:fixed; bottom:2rem; right:2rem; padding:1rem 1.5rem; border-radius:var(--radius-md); color:white; font-weight:600; z-index:9999; display:none; }
        .toast.success { background:#10b981; }
        .toast.error   { background:#ef4444; }

        /* ── Bulk save bar ───────────────────────────── */
        .save-bar { position:sticky; bottom:0; background:white; border-top:2px solid var(--primary-purple); padding:1rem 1.25rem; display:flex; justify-content:space-between; align-items:center; margin-top:1rem; border-radius:0 0 var(--radius-lg) var(--radius-lg); }
        .save-bar span { font-size:0.88rem; color:var(--text-secondary); }

        /* ── Monthly view toggle ─────────────────────── */
        .view-toggle { display:flex; gap:0; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); overflow:hidden; }
        .view-toggle button { padding:0.5rem 1rem; border:none; background:white; font-size:0.82rem; font-weight:600; cursor:pointer; color:var(--text-secondary); }
        .view-toggle button.active { background:var(--primary-purple); color:white; }

        /* ── Summary Table ───────────────────────────── */
        .summary-table { width:100%; border-collapse:collapse; font-size:0.875rem; }
        .summary-table th { background:#f8fafc; font-size:0.75rem; font-weight:700; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; padding:0.75rem 1rem; text-align:left; border-bottom:1px solid #e5e7eb; }
        .summary-table td { padding:0.75rem 1rem; border-bottom:1px solid #f5f5f5; }
        .summary-table tr:hover td { background:#f8fafc; }
        .progress-bar { height:6px; background:#e5e7eb; border-radius:3px; overflow:hidden; margin-top:4px; }
        .progress-fill { height:100%; background:var(--primary-purple); border-radius:3px; }
    </style>
</head>
<body>
<div class="page-wrapper">
                <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <img src="../../../public/images/logo2.jpg" alt="SCC Logo" id="sidebarLogoImg" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-md);">
                </div>
                <div class="logo-text">
                    <span id="sidebarSchoolName"><?= htmlspecialchars($school_name) ?></span>
                    <span>HR Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">HR Management</div>
                    <a href="employees.php" class="nav-item"><span class="nav-icon">👤</span><span>Employee Profiles</span></a>
                    <a href="leaves.php" class="nav-item"><span class="nav-icon">📅</span><span>Leave Requests</span></a>
                    <a href="attendance.php" class="nav-item active"><span class="nav-icon">🕐</span><span>Attendance</span></a>
                    <a href="id_cards.php" class="nav-item"><span class="nav-icon">🪪</span><span>ID Cards</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Resources</div>
                    <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>Announcements</span></a>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Account</div>
                    <a href="profile.php" class="nav-item"><span class="nav-icon">👤</span><span>My Profile</span></a>
                    <a href="../../php/logout.php" class="nav-item"><span class="nav-icon">🚪</span><span>Logout</span></a>
                </div>
            </nav>
        </aside>

    <main class="main-content">
        <header class="page-header">
            <div class="header-title">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button>
                    <h1>Attendance Tracking</h1>
                <p class="page-subtitle">Record and monitor daily staff attendance</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="exportCSV()">📥 Export CSV</button>
            </div>
        </header>

        <!-- Toolbar -->
        <div class="att-toolbar">
            <div class="att-date-group">
                <label>Date:</label>
                <input type="date" id="attDate" value="" onchange="handleViewChange()">
            </div>
            <div class="att-date-group">
                <label>Month:</label>
                <input type="month" id="attMonth" value="" onchange="handleViewChange()">
            </div>
            <div class="view-toggle">
                <button id="btnDaily" class="active" onclick="switchView('daily')">Daily Log</button>
                <button id="btnMonthly" onclick="switchView('monthly')">Monthly Summary</button>
            </div>
            <select id="roleFilter" onchange="loadData()">
                <option value="">All Roles</option>
                <option value="teacher">Teachers</option>
                <option value="registrar">Registrars</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <!-- Stats -->
        <div class="att-stats" id="attStats" style="display:none;">
            <div class="att-stat present"><div class="att-stat-val" id="sPresent">0</div><div class="att-stat-label">Present</div></div>
            <div class="att-stat absent"><div class="att-stat-val" id="sAbsent">0</div><div class="att-stat-label">Absent</div></div>
            <div class="att-stat late"><div class="att-stat-val" id="sLate">0</div><div class="att-stat-label">Late</div></div>
            <div class="att-stat half"><div class="att-stat-val" id="sHalf">0</div><div class="att-stat-label">Half Day</div></div>
            <div class="att-stat leave"><div class="att-stat-val" id="sLeave">0</div><div class="att-stat-label">On Leave</div></div>
        </div>

        <!-- Daily View -->
        <div id="dailyView" class="content-card" style="padding:0;overflow:hidden;">
            <div id="dailyBody" style="overflow-x:auto;">
                <table class="att-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="attTableBody">
                        <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-secondary);">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="save-bar">
                <span id="saveBarLabel">Select a date to start logging attendance.</span>
                <button class="btn btn-primary" onclick="saveAttendance()" id="btnSave" style="display:none;">💾 Save Attendance</button>
            </div>
        </div>

        <!-- Monthly Summary View -->
        <div id="monthlyView" class="content-card" style="display:none;padding:0;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Role</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Half Day</th>
                            <th>On Leave</th>
                            <th>Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody id="summaryTableBody">
                        <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-secondary);">Select a month to view summary.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="toast" class="toast"></div>

<script>
let currentView = 'daily';
let pendingChanges = {};
let allEmployees = [];
let attendanceData = {};

// Init with today's date
(function init() {
    const today = new Date();
    document.getElementById('attDate').value = today.toISOString().split('T')[0];
    const mm = today.toISOString().substring(0, 7);
    document.getElementById('attMonth').value = mm;
    loadData();
})();

function switchView(view) {
    currentView = view;
    document.getElementById('btnDaily').classList.toggle('active', view === 'daily');
    document.getElementById('btnMonthly').classList.toggle('active', view === 'monthly');
    document.getElementById('dailyView').style.display   = view === 'daily'   ? 'block' : 'none';
    document.getElementById('monthlyView').style.display = view === 'monthly' ? 'block' : 'none';
    document.getElementById('attStats').style.display    = view === 'daily'   ? 'flex'  : 'none';
    loadData();
}

function handleViewChange() { loadData(); }

async function loadData() {
    if (currentView === 'daily') loadDailyAttendance();
    else loadMonthlySummary();
}

async function loadDailyAttendance() {
    const date = document.getElementById('attDate').value;
    const role = document.getElementById('roleFilter').value;
    if (!date) return;

    document.getElementById('saveBarLabel').textContent = 'Loading...';
    document.getElementById('btnSave').style.display = 'none';
    document.getElementById('attStats').style.display = 'flex';
    pendingChanges = {};

    const res  = await fetch(`../../api/hr/get_attendance.php?date=${date}&role=${role}`);
    const data = await res.json();
    if (!data.success) { showToast('Failed to load attendance', 'error'); return; }

    allEmployees  = data.employees;
    attendanceData = {};
    data.employees.forEach(e => { attendanceData[e.id] = e; });

    renderDailyTable(data.employees);
    updateStats(data.employees);
    document.getElementById('saveBarLabel').textContent = `Attendance for ${formatDate(date)} — ${data.employees.length} staff`;
    document.getElementById('btnSave').style.display = 'inline-block';
}

function renderDailyTable(employees) {
    const tbody = document.getElementById('attTableBody');
    if (!employees.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-secondary);">No employees found.</td></tr>';
        return;
    }
    tbody.innerHTML = employees.map(e => {
        const init = (e.name||'?')[0].toUpperCase();
        const avatarHtml = e.avatar_url
            ? `<img src="${e.avatar_url}" alt="">`
            : init;
        const status  = e.att_status  || 'present';
        const timeIn  = e.time_in  || '';
        const timeOut = e.time_out || '';
        const remarks = e.remarks  || '';
        return `
        <tr>
            <td>
                <div class="emp-cell">
                    <div class="emp-avatar-sm">${avatarHtml}</div>
                    <div>
                        <div style="font-weight:600;font-size:0.88rem;">${esc(e.name)}</div>
                        <div style="font-size:0.75rem;color:var(--text-secondary);">${esc(e.email||'')}</div>
                    </div>
                </div>
            </td>
            <td style="text-transform:capitalize;">${esc(e.role)}</td>
            <td>
                <select class="status-select" data-uid="${e.id}" data-field="status" onchange="markChanged(${e.id}, 'status', this.value)">
                    <option value="present"  ${status==='present'  ? 'selected':''}>✅ Present</option>
                    <option value="absent"   ${status==='absent'   ? 'selected':''}>❌ Absent</option>
                    <option value="late"     ${status==='late'     ? 'selected':''}>⏰ Late</option>
                    <option value="half_day" ${status==='half_day' ? 'selected':''}>🌓 Half Day</option>
                    <option value="on_leave" ${status==='on_leave' ? 'selected':''}>🏖️ On Leave</option>
                </select>
            </td>
            <td><input type="time" class="time-input" value="${timeIn}" onchange="markChanged(${e.id}, 'time_in', this.value)"></td>
            <td><input type="time" class="time-input" value="${timeOut}" onchange="markChanged(${e.id}, 'time_out', this.value)"></td>
            <td><input type="text" class="time-input" style="width:130px;" placeholder="Remarks..." value="${esc(remarks)}" onchange="markChanged(${e.id}, 'remarks', this.value)"></td>
        </tr>`;
    }).join('');
}

function markChanged(uid, field, value) {
    if (!pendingChanges[uid]) pendingChanges[uid] = {};
    pendingChanges[uid][field] = value;
}

function updateStats(employees) {
    const counts = { present:0, absent:0, late:0, half_day:0, on_leave:0 };
    employees.forEach(e => { const s = e.att_status||'present'; if(counts[s]!==undefined) counts[s]++; });
    document.getElementById('sPresent').textContent = counts.present;
    document.getElementById('sAbsent').textContent  = counts.absent;
    document.getElementById('sLate').textContent    = counts.late;
    document.getElementById('sHalf').textContent    = counts.half_day;
    document.getElementById('sLeave').textContent   = counts.on_leave;
}

async function saveAttendance() {
    const date = document.getElementById('attDate').value;
    if (!date) return;

    // Collect ALL rows (including unchanged)
    const rows = [];
    document.querySelectorAll('#attTableBody tr').forEach(tr => {
        const statusEl  = tr.querySelector('.status-select');
        const timeInEl  = tr.querySelectorAll('.time-input')[0];
        const timeOutEl = tr.querySelectorAll('.time-input')[1];
        const remarksEl = tr.querySelectorAll('.time-input')[2];
        if (!statusEl) return;
        const uid = parseInt(statusEl.dataset.uid);
        rows.push({
            user_id:  uid,
            date:     date,
            status:   statusEl.value,
            time_in:  timeInEl?.value  || null,
            time_out: timeOutEl?.value || null,
            remarks:  remarksEl?.value || null
        });
    });

    const res  = await fetch('../../api/hr/save_attendance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ records: rows })
    });
    const data = await res.json();
    if (data.success) {
        showToast('Attendance saved successfully!', 'success');
        pendingChanges = {};
        loadDailyAttendance();
    } else {
        showToast('Error: ' + data.message, 'error');
    }
}

async function loadMonthlySummary() {
    const month = document.getElementById('attMonth').value;
    const role  = document.getElementById('roleFilter').value;
    if (!month) return;

    const res  = await fetch(`../../api/hr/get_attendance.php?month=${month}&role=${role}&summary=1`);
    const data = await res.json();
    if (!data.success) { showToast('Failed to load summary', 'error'); return; }

    const tbody = document.getElementById('summaryTableBody');
    if (!data.summary.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-secondary);">No attendance data for this month.</td></tr>';
        return;
    }

    tbody.innerHTML = data.summary.map(e => {
        const total = (e.present||0) + (e.absent||0) + (e.late||0) + (e.half_day||0) + (e.on_leave||0);
        const rate  = total ? Math.round(((e.present||0) + (e.late||0) + (e.half_day||0)*.5) / total * 100) : 0;
        const init  = (e.name||'?')[0].toUpperCase();
        const avatarHtml = e.avatar_url ? `<img src="${e.avatar_url}" alt="">` : init;
        return `
        <tr>
            <td>
                <div class="emp-cell">
                    <div class="emp-avatar-sm">${avatarHtml}</div>
                    <div>
                        <div style="font-weight:600;font-size:0.88rem;">${esc(e.name)}</div>
                        <div style="font-size:0.75rem;color:var(--text-secondary);">${esc(e.position||e.role)}</div>
                    </div>
                </div>
            </td>
            <td style="text-transform:capitalize;">${esc(e.role)}</td>
            <td><strong style="color:#10b981;">${e.present||0}</strong></td>
            <td><strong style="color:#ef4444;">${e.absent||0}</strong></td>
            <td><strong style="color:#f59e0b;">${e.late||0}</strong></td>
            <td><strong style="color:#3b82f6;">${e.half_day||0}</strong></td>
            <td><strong style="color:#8b5cf6;">${e.on_leave||0}</strong></td>
            <td>
                <div style="font-weight:700;font-size:0.88rem;">${rate}%</div>
                <div class="progress-bar"><div class="progress-fill" style="width:${rate}%;"></div></div>
            </td>
        </tr>`;
    }).join('');
}

function exportCSV() {
    const date  = document.getElementById('attDate').value;
    const month = document.getElementById('attMonth').value;
    const role  = document.getElementById('roleFilter').value;
    if (currentView === 'daily' && date) {
        window.location.href = `../../api/hr/export_attendance.php?date=${date}&role=${role}`;
    } else if (currentView === 'monthly' && month) {
        window.location.href = `../../api/hr/export_attendance.php?month=${month}&role=${role}&summary=1`;
    }
}

function formatDate(d) {
    const dt = new Date(d + 'T00:00:00');
    return dt.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
}

function showToast(msg, type='success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `toast ${type}`;
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3000);
}

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
<script>
(function() {
    var sidebar = document.querySelector('.sidebar');
    var saved = sessionStorage.getItem('sidebarScroll');
    if (saved) sidebar.scrollTop = parseInt(saved);
    document.querySelectorAll('.nav-item').forEach(function(link) {
        link.addEventListener('click', function() { sessionStorage.setItem('sidebarScroll', sidebar.scrollTop); });
    });
})();
</script>
<script src="../../../public/js/theme-switcher.js"></script>
    <script>
    (function() {
        var toggle   = document.getElementById('sidebarToggle');
        var sidebar  = document.querySelector('.sidebar');
        var overlay  = document.getElementById('sidebarOverlay');
        if (!toggle || !sidebar) return;

        function openSidebar() {
            sidebar.classList.add('active');
            overlay && overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            sidebar.classList.remove('active');
            overlay && overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        toggle.addEventListener('click', function() {
            sidebar.classList.contains('active') ? closeSidebar() : openSidebar();
        });
        overlay && overlay.addEventListener('click', closeSidebar);

        // Close sidebar when a nav link is clicked (mobile UX)
        document.querySelectorAll('.nav-item').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 1024) closeSidebar();
            });
        });
    })();
    </script>
<script src="../../../public/js/pwa.js"></script>

<!-- Mobile Bottom Navigation -->
    <script src="../../../public/js/session-monitor.js"></script>
    <script src="../../../public/js/apply-branding.js"></script>

    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
      <a href="dashboard.php" class="mobile-nav-item" data-page="dashboard">
        <span class="mobile-nav-icon">📊</span><span>Home</span>
      </a>
      <a href="employees.php" class="mobile-nav-item" data-page="employees">
        <span class="mobile-nav-icon">👤</span><span>Staff</span>
      </a>
      <a href="attendance.php" class="mobile-nav-item" data-page="attendance">
        <span class="mobile-nav-icon">🕐</span><span>Attend.</span>
      </a>
      <a href="leaves.php" class="mobile-nav-item" data-page="leaves">
        <span class="mobile-nav-icon">📅</span><span>Leaves</span>
      </a>
      <a href="profile.php" class="mobile-nav-item" data-page="profile">
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
