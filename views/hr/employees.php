<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────

// ── Dynamic school name from system_settings ──────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'school_name' LIMIT 1") : false;
$school_name = ($_sn_res && $_sn_row = $_sn_res->fetch_assoc()) ? $_sn_row['setting_value'] : 'My School';
$_sn_conn && $_sn_conn->close();
// ──────────────────────────────────────────────────────────────────────
requireRole('hr');
$conn = getDBConnection();
$depts = $conn->query("SELECT id, department_name AS name FROM departments ORDER BY department_name ASC");
$deptsArr = [];
while ($d = $depts->fetch_assoc()) $deptsArr[] = $d;
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="../../manifest.json">
    <meta name="theme-color" content="#1E3352">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($school_name) ?> Portal">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <title>Employee Profiles - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        /* ── Layout ─────────────────────────────────── */
        .split-layout { display:grid; grid-template-columns:360px 1fr; gap:1.5rem; }
        @media(max-width:900px){ .split-layout{ grid-template-columns:1fr; } .profile-panel{ display:none; } .profile-panel.visible{ display:block; } }

        /* ── Employee List ───────────────────────────── */
        .emp-row { display:flex; align-items:center; gap:0.9rem; padding:0.85rem 1rem; border-bottom:1px solid #f0f0f0; cursor:pointer; transition:background 0.15s; border-left:3px solid transparent; }
        .emp-row:hover { background:#f8fafc; }
        .emp-row.active { background:#eff6ff; border-left-color:var(--primary-purple); }
        .emp-avatar { width:42px; height:42px; border-radius:50%; background:var(--primary-purple); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1rem; flex-shrink:0; overflow:hidden; }
        .emp-avatar img { width:100%; height:100%; object-fit:cover; }
        .emp-name { font-weight:700; font-size:0.92rem; }
        .emp-sub  { font-size:0.78rem; color:var(--text-secondary); margin-top:0.1rem; }
        .no-profile  { font-size:0.72rem; color:#ef4444; font-weight:600; }
        .has-profile { font-size:0.72rem; color:#10b981; font-weight:600; }

        /* ── Profile Panel ───────────────────────────── */
        .profile-panel { background:white; border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); overflow:hidden; position:sticky; top:1rem; max-height:calc(100vh - 6rem); overflow-y:auto; }
        .profile-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; height:400px; color:var(--text-secondary); gap:1rem; }
        .profile-empty .icon { font-size:3rem; opacity:0.4; }

        /* Profile header */
        .prof-header { background:linear-gradient(135deg, var(--primary-purple), #6366f1); padding:2rem; text-align:center; color:white; }
        .prof-avatar { width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,0.25); margin:0 auto 0.75rem; display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:800; overflow:hidden; border:3px solid rgba(255,255,255,0.5); }
        .prof-avatar img { width:100%; height:100%; object-fit:cover; }
        .prof-name { font-size:1.2rem; font-weight:800; margin-bottom:0.2rem; }
        .prof-role { font-size:0.82rem; opacity:0.85; margin-bottom:0.5rem; }
        .prof-status { display:inline-block; padding:0.2rem 0.75rem; border-radius:999px; font-size:0.72rem; font-weight:700; text-transform:uppercase; background:rgba(255,255,255,0.2); }

        /* Profile sections */
        .prof-body { padding:1.25rem; }
        .prof-section { margin-bottom:1.25rem; }
        .prof-section-title { font-size:0.72rem; font-weight:800; color:var(--primary-purple); text-transform:uppercase; letter-spacing:0.8px; margin-bottom:0.6rem; padding-bottom:0.35rem; border-bottom:2px solid #eef2f7; }
        .prof-row { display:flex; justify-content:space-between; align-items:flex-start; padding:0.45rem 0; border-bottom:1px solid #f5f5f5; font-size:0.85rem; gap:0.5rem; }
        .prof-row:last-child { border-bottom:none; }
        .prof-label { color:var(--text-secondary); font-weight:600; flex-shrink:0; min-width:110px; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.3px; }
        .prof-value { color:var(--text-primary); text-align:right; word-break:break-word; }
        .prof-value.mono { font-family:monospace; font-size:0.82rem; }

        /* Leave history badges */
        .badge { display:inline-block; padding:0.15rem 0.5rem; border-radius:999px; font-size:0.68rem; font-weight:700; text-transform:uppercase; }
        .badge-pending   { background:#fef3c7; color:#92400e; }
        .badge-approved  { background:#d1fae5; color:#065f46; }
        .badge-rejected  { background:#fee2e2; color:#991b1b; }
        .badge-cancelled { background:#f3f4f6; color:#6b7280; }
        .leave-item { display:flex; justify-content:space-between; align-items:center; padding:0.5rem 0; border-bottom:1px solid #f5f5f5; font-size:0.82rem; gap:0.5rem; }
        .leave-item:last-child { border-bottom:none; }

        /* Employment badge */
        .badge-full_time    { background:#dbeafe; color:#1e40af; }
        .badge-part_time    { background:#fef3c7; color:#92400e; }
        .badge-contractual  { background:#f3e8ff; color:#6b21a8; }
        .badge-probationary { background:#dcfce7; color:#166534; }

        /* Edit button */
        .edit-btn { display:block; width:100%; padding:0.75rem; background:var(--primary-purple); color:white; border:none; border-radius:var(--radius-md); font-size:0.9rem; font-weight:600; cursor:pointer; margin-top:0.5rem; text-align:center; }
        .edit-btn:hover { opacity:0.9; }

        /* Edit Modal */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; }
        .modal.active { display:flex; }
        .modal-content { background:white; padding:2rem; border-radius:var(--radius-lg); max-width:720px; width:90%; max-height:92vh; overflow-y:auto; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .form-grid .full { grid-column:1/-1; }
        .form-group { margin-bottom:0; }
        .form-group label { display:block; font-weight:600; font-size:0.82rem; margin-bottom:0.35rem; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.4px; }
        .form-group input, .form-group select { width:100%; padding:0.65rem 0.9rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-size:0.9rem; font-family:inherit; box-sizing:border-box; }
        .form-group input:focus, .form-group select:focus { outline:none; border-color:var(--primary-purple); }
        .section-title { font-size:0.78rem; font-weight:700; color:var(--primary-purple); text-transform:uppercase; letter-spacing:0.6px; margin:1.25rem 0 0.75rem; padding-bottom:0.4rem; border-bottom:2px solid #eef2f7; grid-column:1/-1; }

        /* Search/filter */
        .list-toolbar { padding:0.75rem 1rem; border-bottom:1px solid #f0f0f0; display:flex; gap:0.75rem; }
        .list-toolbar input, .list-toolbar select { padding:0.5rem 0.75rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-size:0.875rem; font-family:inherit; }
        .list-toolbar input { flex:1; }
    </style>
</head>
<body>
<div class="page-wrapper">
                <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <img src="../../images/logo2.jpg" alt="SCC Logo" id="sidebarLogoImg" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-md);">
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
                    <a href="employees.php" class="nav-item active"><span class="nav-icon">👤</span><span>Employee Profiles</span></a>
                    <a href="leaves.php" class="nav-item"><span class="nav-icon">📅</span><span>Leave Requests</span></a>
                    <a href="attendance.php" class="nav-item"><span class="nav-icon">🕐</span><span>Attendance</span></a>
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
                    <h1>Employee Profiles</h1>
                <p class="page-subtitle">Click an employee to view their full profile</p>
            </div>
        </header>

        <div class="split-layout">
            <!-- Left: Employee List -->
            <div class="content-card" style="padding:0; overflow:hidden;">
                <div class="list-toolbar">
                    <input type="text" id="searchInput" placeholder="🔍 Search employees..." oninput="filterEmployees()">
                    <select id="roleFilter" onchange="filterEmployees()">
                        <option value="">All Roles</option>
                        <option value="teacher">Teachers</option>
                        <option value="registrar">Registrars</option>
                        <option value="admin">Admins</option>
                    </select>
                </div>
                <div id="employeeList">Loading...</div>
            </div>

            <!-- Right: Profile Panel -->
            <div class="profile-panel" id="profilePanel">
                <div class="profile-empty">
                    <div class="icon">👤</div>
                    <p style="font-weight:600;">Select an employee</p>
                    <p style="font-size:0.85rem;">Click any employee on the left to view their full profile</p>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Edit HR Profile Modal -->
<div id="empModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle" style="margin:0 0 1.5rem;">Edit HR Profile</h2>
        <input type="hidden" id="editUserId">
        <div class="form-grid">
            <div class="section-title">Employment Information</div>
            <div class="form-group">
                <label>Employment Type</label>
                <select id="empType">
                    <option value="full_time">Full Time</option>
                    <option value="part_time">Part Time</option>
                    <option value="contractual">Contractual</option>
                    <option value="probationary">Probationary</option>
                </select>
            </div>
            <div class="form-group">
                <label>HR Status</label>
                <select id="hrStatus">
                    <option value="active">Active</option>
                    <option value="on_leave">On Leave</option>
                    <option value="resigned">Resigned</option>
                    <option value="terminated">Terminated</option>
                </select>
            </div>
            <div class="form-group">
                <label>Hire Date</label>
                <input type="date" id="hireDate">
            </div>
            <div class="form-group">
                <label>Position / Job Title</label>
                <input type="text" id="position" placeholder="e.g. Senior Teacher">
            </div>
            <div class="form-group">
                <label>Department</label>
                <select id="deptId">
                    <option value="">-- None --</option>
                    <?php foreach ($deptsArr as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Salary Grade</label>
                <input type="text" id="salaryGrade" placeholder="e.g. SG-15">
            </div>
            <div class="form-group">
                <label>Monthly Salary (₱)</label>
                <input type="number" id="monthlySalary" placeholder="0.00" step="0.01">
            </div>

            <div class="section-title">Government Numbers</div>
            <div class="form-group">
                <label>SSS Number</label>
                <input type="text" id="sssNumber" placeholder="XX-XXXXXXX-X">
            </div>
            <div class="form-group">
                <label>PhilHealth Number</label>
                <input type="text" id="philhealthNumber" placeholder="XXXX-XXXX-XXXX">
            </div>
            <div class="form-group">
                <label>Pag-IBIG Number</label>
                <input type="text" id="pagibigNumber" placeholder="XXXX-XXXX-XXXX">
            </div>
            <div class="form-group">
                <label>TIN Number</label>
                <input type="text" id="tinNumber" placeholder="XXX-XXX-XXX-XXX">
            </div>

            <div class="section-title">Emergency Contact</div>
            <div class="form-group">
                <label>Contact Name</label>
                <input type="text" id="ecName" placeholder="Full name">
            </div>
            <div class="form-group">
                <label>Relationship</label>
                <input type="text" id="ecRelation" placeholder="e.g. Spouse, Parent">
            </div>
            <div class="form-group full">
                <label>Contact Phone</label>
                <input type="text" id="ecPhone" placeholder="09XX-XXX-XXXX">
            </div>
        </div>
        <div style="display:flex;gap:1rem;margin-top:1.75rem;">
            <button class="btn btn-primary" onclick="saveEmployee()" style="flex:1;">💾 Save Profile</button>
            <button class="btn" onclick="closeModal()" style="flex:1;">Cancel</button>
        </div>
    </div>
</div>

<script>
let allEmployees = [];
let currentEmp   = null;

async function loadEmployees() {
    const res  = await fetch('../../api/hr/get_employees.php');
    const data = await res.json();
    if (!data.success) return;
    allEmployees = data.employees;
    filterEmployees();
}

function filterEmployees() {
    const q    = document.getElementById('searchInput').value.toLowerCase();
    const role = document.getElementById('roleFilter').value;
    const list = allEmployees.filter(e =>
        (!q || e.name.toLowerCase().includes(q) || (e.email||'').toLowerCase().includes(q) || (e.position||'').toLowerCase().includes(q))
        && (!role || e.role === role)
    );
    renderList(list);
}

function renderList(list) {
    const container = document.getElementById('employeeList');
    if (!list.length) {
        container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">No employees found.</p>';
        return;
    }
    container.innerHTML = list.map(e => {
        const initial  = (e.name||'?')[0].toUpperCase();
        const hasHR    = !!e.hr_id;
        const avatarHtml = e.avatar_url
            ? '<img src="' + e.avatar_url + '" alt="">'
            : initial;
        const isActive = currentEmp && currentEmp.id == e.id;
        return '<div class="emp-row' + (isActive ? ' active' : '') + '" onclick="showProfile(' + e.id + ')" data-id="' + e.id + '">' +
            '<div class="emp-avatar">' + avatarHtml + '</div>' +
            '<div style="flex:1;min-width:0;">' +
                '<div class="emp-name">' + esc(e.name) + '</div>' +
                '<div class="emp-sub">' + esc(e.role) + (e.position ? ' · ' + esc(e.position) : '') + '</div>' +
            '</div>' +
            '<div style="text-align:right;flex-shrink:0;">' +
                '<div class="' + (hasHR ? 'has-profile' : 'no-profile') + '">' + (hasHR ? '✓ HR Profile' : '+ Add Profile') + '</div>' +
            '</div>' +
        '</div>';
    }).join('');
}

function showProfile(id) {
    currentEmp = allEmployees.find(e => e.id == id);
    if (!currentEmp) return;

    // Highlight active row
    document.querySelectorAll('.emp-row').forEach(r => r.classList.remove('active'));
    const row = document.querySelector('.emp-row[data-id="' + id + '"]');
    if (row) row.classList.add('active');

    const e = currentEmp;
    const initial   = (e.name||'?')[0].toUpperCase();
    const avatarHtml = e.avatar_url
        ? '<img src="' + e.avatar_url + '" alt="">'
        : initial;

    const hasHR = !!e.hr_id;
    const statusLabel = { active:'Active', on_leave:'On Leave', resigned:'Resigned', terminated:'Terminated' }[e.hr_status] || 'Not Set';
    const empTypeLabel = e.employment_type ? e.employment_type.replace('_',' ') : null;

    // Build leave history
    let leaveHtml = '';
    if (e.recent_leaves && e.recent_leaves.length) {
        leaveHtml = e.recent_leaves.map(l =>
            '<div class="leave-item">' +
                '<div><div style="font-weight:600;">' + esc(l.leave_type) + '</div>' +
                '<div style="font-size:0.75rem;color:var(--text-secondary);">' + l.start_date + ' → ' + l.end_date + ' (' + l.total_days + ' day(s))</div></div>' +
                '<span class="badge badge-' + l.status + '">' + l.status + '</span>' +
            '</div>'
        ).join('');
    } else {
        leaveHtml = '<p style="color:var(--text-secondary);font-size:0.85rem;text-align:center;padding:0.5rem 0;">No leave requests this year.</p>';
    }

    document.getElementById('profilePanel').innerHTML =
        '<div class="prof-header">' +
            '<div class="prof-avatar">' + avatarHtml + '</div>' +
            '<div class="prof-name">' + esc(e.name) + '</div>' +
            '<div class="prof-role">' + esc(e.role.charAt(0).toUpperCase() + e.role.slice(1)) + (e.position ? ' · ' + esc(e.position) : '') + '</div>' +
            '<span class="prof-status">' + (hasHR ? statusLabel : 'No HR Profile') + '</span>' +
        '</div>' +
        '<div class="prof-body">' +

            // Basic Info
            '<div class="prof-section">' +
                '<div class="prof-section-title">Basic Information</div>' +
                profRow('Email', esc(e.email || '—')) +
                profRow('Role', esc(e.role || '—')) +
                profRow('System Status', esc(e.user_status || '—')) +
                profRow('Department', esc(e.department_name || e.department || '—')) +
                profRow('Office', esc(e.office_location || '—')) +
                profRow('Office Hours', esc(e.office_hours || '—')) +
                profRow('Joined', e.joined_at ? e.joined_at.substring(0,10) : '—') +
            '</div>' +

            // Employment
            '<div class="prof-section">' +
                '<div class="prof-section-title">Employment Details</div>' +
                (hasHR ? (
                    profRow('Employment Type', empTypeLabel ? '<span class="badge badge-' + e.employment_type + '">' + empTypeLabel + '</span>' : '—') +
                    profRow('HR Status', statusLabel) +
                    profRow('Hire Date', e.hire_date || '—') +
                    profRow('Salary Grade', esc(e.salary_grade || '—')) +
                    profRow('Monthly Salary', e.monthly_salary ? '₱' + parseFloat(e.monthly_salary).toLocaleString('en-PH', {minimumFractionDigits:2}) : '—')
                ) : '<p style="color:var(--text-secondary);font-size:0.85rem;text-align:center;padding:0.5rem 0;">No employment details yet.</p>') +
            '</div>' +

            // Government Numbers
            (hasHR ? (
                '<div class="prof-section">' +
                    '<div class="prof-section-title">Government Numbers</div>' +
                    profRow('SSS', '<span class="prof-value mono">' + esc(e.sss_number || '—') + '</span>') +
                    profRow('PhilHealth', '<span class="prof-value mono">' + esc(e.philhealth_number || '—') + '</span>') +
                    profRow('Pag-IBIG', '<span class="prof-value mono">' + esc(e.pagibig_number || '—') + '</span>') +
                    profRow('TIN', '<span class="prof-value mono">' + esc(e.tin_number || '—') + '</span>') +
                '</div>'
            ) : '') +

            // Emergency Contact
            (hasHR && e.emergency_contact_name ? (
                '<div class="prof-section">' +
                    '<div class="prof-section-title">Emergency Contact</div>' +
                    profRow('Name', esc(e.emergency_contact_name || '—')) +
                    profRow('Relationship', esc(e.emergency_contact_relation || '—')) +
                    profRow('Phone', esc(e.emergency_contact_phone || '—')) +
                '</div>'
            ) : '') +

            // Leave History
            '<div class="prof-section">' +
                '<div class="prof-section-title">Recent Leave Requests (This Year)</div>' +
                leaveHtml +
            '</div>' +

            // Edit button
            '<button class="edit-btn" onclick="openEditModal()">✏️ Edit HR Profile</button>' +
        '</div>';
}

function profRow(label, value) {
    return '<div class="prof-row"><span class="prof-label">' + label + '</span><span class="prof-value">' + value + '</span></div>';
}

function openEditModal() {
    if (!currentEmp) return;
    const e = currentEmp;
    document.getElementById('modalTitle').textContent       = 'HR Profile — ' + e.name;
    document.getElementById('editUserId').value             = e.id;
    document.getElementById('empType').value                = e.employment_type    || 'full_time';
    document.getElementById('hrStatus').value               = e.hr_status          || 'active';
    document.getElementById('hireDate').value               = e.hire_date          || '';
    document.getElementById('position').value               = e.position           || '';
    document.getElementById('deptId').value                 = e.department_id      || '';
    document.getElementById('salaryGrade').value            = e.salary_grade       || '';
    document.getElementById('monthlySalary').value          = e.monthly_salary     || '';
    document.getElementById('sssNumber').value              = e.sss_number         || '';
    document.getElementById('philhealthNumber').value       = e.philhealth_number  || '';
    document.getElementById('pagibigNumber').value          = e.pagibig_number     || '';
    document.getElementById('tinNumber').value              = e.tin_number         || '';
    document.getElementById('ecName').value                 = e.emergency_contact_name     || '';
    document.getElementById('ecRelation').value             = e.emergency_contact_relation || '';
    document.getElementById('ecPhone').value                = e.emergency_contact_phone    || '';
    document.getElementById('empModal').classList.add('active');
}

function closeModal() { document.getElementById('empModal').classList.remove('active'); }

async function saveEmployee() {
    const payload = {
        user_id:                    parseInt(document.getElementById('editUserId').value),
        employment_type:            document.getElementById('empType').value,
        hr_status:                  document.getElementById('hrStatus').value,
        hire_date:                  document.getElementById('hireDate').value || null,
        position:                   document.getElementById('position').value,
        department_id:              document.getElementById('deptId').value || null,
        salary_grade:               document.getElementById('salaryGrade').value,
        monthly_salary:             document.getElementById('monthlySalary').value || null,
        sss_number:                 document.getElementById('sssNumber').value,
        philhealth_number:          document.getElementById('philhealthNumber').value,
        pagibig_number:             document.getElementById('pagibigNumber').value,
        tin_number:                 document.getElementById('tinNumber').value,
        emergency_contact_name:     document.getElementById('ecName').value,
        emergency_contact_relation: document.getElementById('ecRelation').value,
        emergency_contact_phone:    document.getElementById('ecPhone').value,
    };
    const res  = await fetch('../../api/hr/save_employee.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    const data = await res.json();
    if (data.success) {
        alert(data.message);
        closeModal();
        const prevId = currentEmp.id;
        await loadEmployees();
        showProfile(prevId);
    } else {
        alert('Error: ' + data.message);
    }
}

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
loadEmployees();
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
<script src="../../js/theme-switcher.js"></script>
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
<script src="../../js/pwa.js"></script>

<!-- Mobile Bottom Navigation -->
    <script src="../../js/session-monitor.js"></script>
    <script src="../../js/apply-branding.js"></script>
<script>
/* mobile-fix: back button for split-layout pages */
(function(){
  var splitLayout = document.querySelector(".split-layout, .two-col, .id-layout");
  if (!splitLayout) return;
  var panels = splitLayout.children;
  if (panels.length < 2) return;
  var listPanel = panels[0], detailPanel = panels[1];
  var btn = document.createElement("button");
  btn.className = "mobile-back-btn";
  btn.innerHTML = "2190 Back to List";
  detailPanel.insertBefore(btn, detailPanel.firstChild);
  btn.addEventListener("click", function(){
    detailPanel.classList.remove("visible");
    listPanel.style.display = "";
  });
  window.addEventListener("resize", function(){
    if (window.innerWidth > 768) listPanel.style.display = "";
  });
})();
</script>

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
