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
    <title>Employee ID Cards - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        /* ── Layout ──────────────────────────────────── */
        .id-layout { display:grid; grid-template-columns:320px 1fr; gap:1.5rem; }
        @media(max-width:900px){ .id-layout { grid-template-columns:1fr; } }

        /* ── Employee List ───────────────────────────── */
        .emp-row { display:flex; align-items:center; gap:0.9rem; padding:0.85rem 1rem; border-bottom:1px solid #f0f0f0; cursor:pointer; transition:background 0.15s; border-left:3px solid transparent; }
        .emp-row:hover { background:#f8fafc; }
        .emp-row.active { background:#eff6ff; border-left-color:#3D6B9F; }
        .emp-avatar-sm { width:38px; height:38px; border-radius:50%; background:#3D6B9F; color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.9rem; flex-shrink:0; overflow:hidden; }
        .emp-avatar-sm img { width:100%; height:100%; object-fit:cover; }
        .list-toolbar { padding:0.75rem 1rem; border-bottom:1px solid #f0f0f0; }
        .list-toolbar input { width:100%; padding:0.5rem 0.75rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-size:0.875rem; font-family:inherit; box-sizing:border-box; }
        .list-toolbar select { width:100%; margin-top:0.5rem; padding:0.5rem 0.75rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-size:0.875rem; font-family:inherit; box-sizing:border-box; }

        /* ── Preview Panel ───────────────────────────── */
        .preview-panel { background:white; border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); overflow:hidden; }
        .preview-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; height:420px; color:var(--text-secondary); gap:1rem; }

        /* ── Preview Controls ────────────────────────── */
        .preview-controls { padding:1.25rem 1.5rem; border-bottom:1px solid #f0f0f0; display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap; }
        .preview-controls label { font-size:0.82rem; font-weight:600; color:var(--text-secondary); }
        .preview-controls select { padding:0.45rem 0.75rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-size:0.82rem; font-family:inherit; }
        .preview-body { padding:2rem; display:flex; justify-content:center; background:#f0f4f8; min-height:380px; align-items:center; }

        /* ══════════════════════════════════════════════
           ID CARD STYLES
        ══════════════════════════════════════════════ */
        .id-card {
            width:340px;
            border-radius:16px;
            overflow:hidden;
            box-shadow:0 8px 32px rgba(0,0,0,0.18);
            font-family:'Segoe UI', Arial, sans-serif;
            position:relative;
            background:white;
            flex-shrink:0;
        }

        /* ── FRONT CARD ────────────────────────────── */
        .card-front { display:block; }
        .card-header-band {
            background:linear-gradient(135deg, #3D6B9F, #2a4f7a);
            padding:1rem 1.25rem 0.75rem;
            display:flex;
            align-items:center;
            gap:0.75rem;
        }
        .card-school-logo {
            width:42px; height:42px; border-radius:50%;
            background:rgba(255,255,255,0.25);
            border:2px solid rgba(255,255,255,0.5);
            display:flex; align-items:center; justify-content:center;
            font-weight:900; font-size:1rem; color:white; flex-shrink:0;
            overflow:hidden;
        }
        .card-school-logo img { width:100%; height:100%; object-fit:cover; }
        .card-school-name { color:white; }
        .card-school-name .school-title { font-size:0.82rem; font-weight:800; line-height:1.2; }
        .card-school-name .school-sub   { font-size:0.65rem; opacity:0.8; letter-spacing:0.3px; }
        .card-id-label {
            margin-left:auto; background:rgba(255,255,255,0.2);
            padding:0.2rem 0.6rem; border-radius:20px;
            font-size:0.65rem; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.5px;
        }

        .card-body {
            padding:1.25rem;
            display:flex;
            flex-direction:column;
            align-items:center;
            gap:0.85rem;
        }

        .card-photo-wrap {
            width:90px; height:90px; border-radius:50%;
            border:4px solid #3D6B9F;
            overflow:hidden;
            background:#e5e7eb;
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0;
        }
        .card-photo-wrap img { width:100%; height:100%; object-fit:cover; }
        .card-photo-initial {
            font-size:2rem; font-weight:800; color:#3D6B9F;
        }

        .card-name {
            font-size:1.05rem; font-weight:800; color:#1e293b;
            text-align:center; line-height:1.2;
        }
        .card-position {
            font-size:0.78rem; color:#3D6B9F; font-weight:700;
            text-align:center; text-transform:uppercase; letter-spacing:0.5px;
        }
        .card-dept {
            font-size:0.75rem; color:#64748b; text-align:center;
        }

        .card-info-grid {
            width:100%; border-top:1px solid #e5e7eb; padding-top:0.85rem;
            display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;
        }
        .card-info-item { text-align:center; }
        .card-info-label { font-size:0.6rem; color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; }
        .card-info-value { font-size:0.75rem; color:#1e293b; font-weight:600; margin-top:0.1rem; }

        .card-qr-section {
            border-top:1px solid #e5e7eb;
            padding:0.85rem 1.25rem;
            display:flex;
            align-items:center;
            gap:1rem;
            background:#f8fafc;
        }
        .card-qr-box { flex-shrink:0; }
        .card-qr-box canvas, .card-qr-box img { display:block; border-radius:6px; }
        .card-qr-info { flex:1; min-width:0; }
        .card-emp-id   { font-size:0.7rem; color:#64748b; font-weight:600; }
        .card-emp-id span { font-size:0.85rem; font-weight:800; color:#3D6B9F; font-family:monospace; display:block; }
        .card-qr-scan-hint { font-size:0.65rem; color:#94a3b8; margin-top:0.35rem; }

        .card-footer-band {
            background:linear-gradient(135deg, #3D6B9F, #2a4f7a);
            padding:0.5rem 1.25rem;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }
        .card-footer-band span { font-size:0.65rem; color:rgba(255,255,255,0.75); }
        .card-validity { font-size:0.65rem; color:rgba(255,255,255,0.75); }

        /* ── BACK CARD ─────────────────────────────── */
        .card-back { display:none; }

        .card-back-top {
            background:linear-gradient(135deg, #3D6B9F, #2a4f7a);
            padding:0.9rem 1.25rem;
            display:flex; align-items:center; gap:0.75rem;
        }
        .card-back-top .back-logo {
            width:36px; height:36px; border-radius:50%;
            background:rgba(255,255,255,0.2); border:2px solid rgba(255,255,255,0.45);
            display:flex; align-items:center; justify-content:center;
            font-weight:900; font-size:0.7rem; color:white; flex-shrink:0; overflow:hidden;
        }
        .card-back-top .back-logo img { width:100%; height:100%; object-fit:cover; }
        .card-back-top .back-school { color:white; }
        .card-back-top .back-school-name { font-size:0.8rem; font-weight:800; line-height:1.2; }
        .card-back-top .back-school-sub  { font-size:0.62rem; opacity:0.75; letter-spacing:0.3px; }

        /* Info grid */
        .card-back-body { padding:1rem 1.25rem 0.75rem; }
        .back-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.5rem 0.75rem; margin-bottom:0.85rem; }
        .back-field { }
        .back-field.full { grid-column:1/-1; }
        .back-field-label { font-size:0.58rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:0.15rem; }
        .back-field-value { font-size:0.78rem; font-weight:600; color:#1e293b; line-height:1.3; word-break:break-word; }
        .back-field-value.mono { font-family:monospace; font-size:0.8rem; color:#3D6B9F; font-weight:800; }

        /* Divider */
        .back-divider { height:1px; background:linear-gradient(to right, #3D6B9F33, transparent); margin:0.5rem 0 0.75rem; }

        /* Emergency contact */
        .back-emergency {
            background:linear-gradient(135deg, #fff7ed, #fef3c7);
            border:1px solid #fcd34d;
            border-radius:8px; padding:0.65rem 0.85rem; margin-bottom:0.85rem;
        }
        .back-emergency-title {
            font-size:0.62rem; font-weight:800; color:#b45309;
            text-transform:uppercase; letter-spacing:0.5px; margin-bottom:0.4rem;
            display:flex; align-items:center; gap:0.3rem;
        }
        .back-emergency-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.35rem 0.75rem; }
        .back-emergency-grid .back-field-label { color:#92400e; }
        .back-emergency-grid .back-field-value { font-size:0.75rem; }

        /* Signature line */
        .back-sig-row {
            display:grid; grid-template-columns:1fr 1fr; gap:1rem;
            padding-top:0.5rem;
        }
        .back-sig-box { text-align:center; }
        .back-sig-line { border-top:1.5px solid #cbd5e1; margin:0 0.5rem 0.25rem; }
        .back-sig-label { font-size:0.6rem; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:0.4px; }

        /* Footer stripe */
        .card-back-footer {
            background:linear-gradient(135deg, #3D6B9F, #2a4f7a);
            padding:0.5rem 1.25rem;
            text-align:center;
            font-size:0.62rem; color:rgba(255,255,255,0.75); line-height:1.6;
        }
        .card-back-footer strong { color:white; }

        /* ── Print Styles ────────────────────────────── */
        @media print {
            body * { visibility:hidden; }
            .print-area, .print-area * { visibility:visible; }
            .print-area { position:fixed; top:0; left:0; width:100%; display:flex; flex-wrap:wrap; gap:1rem; padding:1rem; justify-content:center; }
            .no-print { display:none !important; }
            .id-card { box-shadow:none; border:1px solid #ccc; }
        }
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
                    <a href="employees.php" class="nav-item"><span class="nav-icon">👤</span><span>Employee Profiles</span></a>
                    <a href="leaves.php" class="nav-item"><span class="nav-icon">📅</span><span>Leave Requests</span></a>
                    <a href="attendance.php" class="nav-item"><span class="nav-icon">🕐</span><span>Attendance</span></a>
                    <a href="id_cards.php" class="nav-item active"><span class="nav-icon">🪪</span><span>ID Cards</span></a>
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
                    <h1>Employee ID Cards</h1>
                <p class="page-subtitle">Generate and print employee ID cards with QR codes</p>
            </div>
            <div class="header-actions no-print">
                <button class="btn btn-primary" onclick="printAll()">🖨️ Print All</button>
            </div>
        </header>

        <div class="id-layout no-print">
            <!-- Left: Employee List -->
            <div class="content-card" style="padding:0;overflow:hidden;align-self:start;">
                <div class="list-toolbar">
                    <input type="text" id="searchInput" placeholder="🔍 Search employees..." oninput="filterEmployees()">
                    <select id="roleFilter" onchange="filterEmployees()" style="margin-top:0.5rem;">
                        <option value="">All Roles</option>
                        <option value="teacher">Teachers</option>
                        <option value="registrar">Registrars</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div id="employeeList">
                    <p style="text-align:center;color:var(--text-secondary);padding:2rem;">Loading...</p>
                </div>
            </div>

            <!-- Right: Preview Panel -->
            <div class="preview-panel" id="previewPanel">
                <div class="preview-controls">
                    <label>Card Side:</label>
                    <select id="cardSide" onchange="toggleSide()">
                        <option value="front">Front</option>
                        <option value="back">Back</option>
                    </select>
                    <div style="margin-left:auto;display:flex;gap:0.75rem;">
                        <button class="btn" onclick="printSingle()" id="btnPrintSingle" style="display:none;">🖨️ Print This Card</button>
                    </div>
                </div>
                <div class="preview-body" id="previewBody">
                    <div class="preview-empty">
                        <div style="font-size:3rem;opacity:0.4;">🪪</div>
                        <p style="font-weight:600;">Select an employee</p>
                        <p style="font-size:0.85rem;">Click any employee on the left to preview their ID card</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print area (hidden until print) -->
        <div class="print-area" id="printArea" style="display:none;"></div>
    </main>
</div>

<script>
let allEmployees = [];
let currentEmp   = null;
const SCHOOL_NAME = "<?= htmlspecialchars($school_name) ?>";
const CURRENT_YEAR = new Date().getFullYear();

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
        (!q || e.name.toLowerCase().includes(q) || (e.position||'').toLowerCase().includes(q))
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
        const init = (e.name||'?')[0].toUpperCase();
        const avatarHtml = e.avatar_url
            ? `<img src="${e.avatar_url}" alt="">`
            : init;
        const isActive = currentEmp && currentEmp.id == e.id;
        return `<div class="emp-row${isActive?' active':''}" onclick="selectEmployee(${e.id})" data-id="${e.id}">
            <div class="emp-avatar-sm">${avatarHtml}</div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:0.88rem;">${esc(e.name)}</div>
                <div style="font-size:0.75rem;color:var(--text-secondary);">${esc(e.position||e.role)} ${e.department_name?'· '+esc(e.department_name):''}</div>
            </div>
        </div>`;
    }).join('');
}

function selectEmployee(id) {
    currentEmp = allEmployees.find(e => e.id == id);
    if (!currentEmp) return;
    document.querySelectorAll('.emp-row').forEach(r => r.classList.remove('active'));
    const row = document.querySelector(`.emp-row[data-id="${id}"]`);
    if (row) row.classList.add('active');
    document.getElementById('btnPrintSingle').style.display = 'inline-block';
    renderCard();
}

function toggleSide() { renderCard(); }

function renderCard() {
    if (!currentEmp) return;
    const side = document.getElementById('cardSide').value;
    const body = document.getElementById('previewBody');
    body.innerHTML = '';
    const card = buildCard(currentEmp, side);
    body.appendChild(card);
    generateQR(currentEmp);
}

function buildCard(emp, side='front') {
    const wrap = document.createElement('div');
    wrap.className = 'id-card';

    const empId  = 'EMP-' + String(emp.id).padStart(5, '0');
    const hireYr = emp.hire_date ? emp.hire_date.substring(0,4) : CURRENT_YEAR;
    const validUntil = parseInt(hireYr) + 1;

    // QR data
    const qrData = JSON.stringify({
        id:         empId,
        name:       emp.name,
        role:       emp.role,
        position:   emp.position || '',
        department: emp.department_name || emp.department || '',
        email:      emp.email || '',
        school:     SCHOOL_NAME
    });

    // Avatar
    const avatarContent = emp.avatar_url
        ? `<img src="${emp.avatar_url}" alt="${esc(emp.name)}">`
        : `<span class="card-photo-initial">${(emp.name||'?')[0].toUpperCase()}</span>`;

    // Logo
    const logoContent = `<img src="../../images/logo.png" alt="Logo" onerror="this.style.display='none';this.parentNode.innerHTML='OL';">`;

    if (side === 'front') {
        wrap.innerHTML = `
        <div class="card-front">
            <div class="card-header-band">
                <div class="card-school-logo">${logoContent}</div>
                <div class="card-school-name">
                    <div class="school-title"><?= htmlspecialchars($school_name) ?></div>
                    <div class="school-sub"><?= htmlspecialchars($school_name) ?></div>
                </div>
                <div class="card-id-label">School ID</div>
            </div>
            <div class="card-body">
                <div class="card-photo-wrap">${avatarContent}</div>
                <div>
                    <div class="card-name">${esc(emp.name)}</div>
                    <div class="card-position" style="margin-top:0.25rem;">${esc(emp.position || roleLabel(emp.role))}</div>
                    <div class="card-dept">${esc(emp.department_name || emp.department || 'No Department')}</div>
                </div>
                <div class="card-info-grid">
                    <div class="card-info-item">
                        <div class="card-info-label">Role</div>
                        <div class="card-info-value">${esc(roleLabel(emp.role))}</div>
                    </div>
                    <div class="card-info-item">
                        <div class="card-info-label">Employee ID</div>
                        <div class="card-info-value" style="font-family:monospace;">${empId}</div>
                    </div>
                </div>
            </div>
            <div class="card-qr-section">
                <div class="card-qr-box" id="qr-${emp.id}"></div>
                <div class="card-qr-info">
                    <div class="card-emp-id">Employee ID<span>${empId}</span></div>
                    <div class="card-qr-scan-hint">Scan QR code to verify employee information</div>
                </div>
            </div>
            <div class="card-footer-band">
                <span>${SCHOOL_NAME}</span>
                <span class="card-validity">Valid: ${hireYr}–${validUntil}</span>
            </div>
        </div>`;
    } else {
        wrap.innerHTML = `
        <div class="card-back">
            <!-- Header with school branding -->
            <div class="card-back-top">
                <div class="back-logo"><img src="../../images/logo.png" alt="Logo" onerror="this.style.display='none';this.parentNode.innerHTML='SCC';"></div>
                <div class="back-school">
                    <div class="back-school-name">${SCHOOL_NAME}</div>
                    <div class="back-school-sub">Official Employee Identification Card</div>
                </div>
            </div>

            <!-- Employee Info Grid -->
            <div class="card-back-body">
                <div class="back-grid">
                    <div class="back-field full">
                        <div class="back-field-label">Full Name</div>
                        <div class="back-field-value">${esc(emp.name)}</div>
                    </div>
                    <div class="back-field">
                        <div class="back-field-label">Employee ID</div>
                        <div class="back-field-value mono">${empId}</div>
                    </div>
                    <div class="back-field">
                        <div class="back-field-label">Role</div>
                        <div class="back-field-value">${esc(roleLabel(emp.role))}</div>
                    </div>
                    <div class="back-field full">
                        <div class="back-field-label">Position</div>
                        <div class="back-field-value">${esc(emp.position || roleLabel(emp.role))}</div>
                    </div>
                    <div class="back-field full">
                        <div class="back-field-label">Department</div>
                        <div class="back-field-value">${esc(emp.department_name || emp.department || '—')}</div>
                    </div>
                    <div class="back-field">
                        <div class="back-field-label">Employment Type</div>
                        <div class="back-field-value">${esc((emp.employment_type||'—').replace('_',' '))}</div>
                    </div>
                    <div class="back-field">
                        <div class="back-field-label">Date Hired</div>
                        <div class="back-field-value">${esc(emp.hire_date || '—')}</div>
                    </div>
                    <div class="back-field full">
                        <div class="back-field-label">Email Address</div>
                        <div class="back-field-value">${esc(emp.email || '—')}</div>
                    </div>
                </div>

                <div class="back-divider"></div>

                <!-- Emergency Contact -->
                <div class="back-emergency">
                    <div class="back-emergency-title">⚠️ In Case of Emergency</div>
                    <div class="back-emergency-grid">
                        <div class="back-field">
                            <div class="back-field-label">Contact Name</div>
                            <div class="back-field-value">${esc(emp.emergency_contact_name || '—')}</div>
                        </div>
                        <div class="back-field">
                            <div class="back-field-label">Relationship</div>
                            <div class="back-field-value">${esc(emp.emergency_contact_relation || '—')}</div>
                        </div>
                        <div class="back-field full">
                            <div class="back-field-label">Contact Number</div>
                            <div class="back-field-value">${esc(emp.emergency_contact_phone || '—')}</div>
                        </div>
                    </div>
                </div>

                <!-- Signature lines -->
                <div class="back-sig-row">
                    <div class="back-sig-box">
                        <div class="back-sig-line"></div>
                        <div class="back-sig-label">Employee Signature</div>
                    </div>
                    <div class="back-sig-box">
                        <div class="back-sig-line"></div>
                        <div class="back-sig-label">HR Officer</div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="card-back-footer">
                If found, please return to <strong>${SCHOOL_NAME}</strong><br>
                This card is school property. Surrender upon separation.
            </div>
        </div>`;
    }

    // Store QR data on element for generation
    wrap.dataset.qrdata = qrData;
    wrap.dataset.empid  = emp.id;
    return wrap;
}

function generateQR(emp) {
    const qrEl = document.getElementById(`qr-${emp.id}`);
    if (!qrEl) return;
    qrEl.innerHTML = '';

    const empId = 'EMP-' + String(emp.id).padStart(5, '0');
    const qrData = [
        `ID: ${empId}`,
        `Name: ${emp.name}`,
        `Role: ${roleLabel(emp.role)}`,
        `Position: ${emp.position || roleLabel(emp.role)}`,
        `Dept: ${emp.department_name || emp.department || 'N/A'}`,
        `Email: ${emp.email || 'N/A'}`,
        `School: ${SCHOOL_NAME}`
    ].join('\n');

    new QRCode(qrEl, {
        text:         qrData,
        width:        72,
        height:       72,
        colorDark:    '#1e293b',
        colorLight:   '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });
}

function generateQRInto(container, emp) {
    container.innerHTML = '';
    const empId = 'EMP-' + String(emp.id).padStart(5, '0');
    const qrData = [
        `ID: ${empId}`,
        `Name: ${emp.name}`,
        `Role: ${roleLabel(emp.role)}`,
        `Position: ${emp.position || roleLabel(emp.role)}`,
        `Dept: ${emp.department_name || emp.department || 'N/A'}`,
        `Email: ${emp.email || 'N/A'}`,
        `School: ${SCHOOL_NAME}`
    ].join('\n');

    new QRCode(container, {
        text:         qrData,
        width:        72,
        height:       72,
        colorDark:    '#1e293b',
        colorLight:   '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });
}

function printSingle() {
    if (!currentEmp) return;
    const printArea = document.getElementById('printArea');
    printArea.innerHTML = '';
    printArea.style.display = 'flex';

    const cardFront = buildCard(currentEmp, 'front');
    const cardBack  = buildCard(currentEmp, 'back');
    printArea.appendChild(cardFront);
    printArea.appendChild(cardBack);

    // Generate QR in print card
    const qrEl = cardFront.querySelector(`#qr-${currentEmp.id}`);
    if (qrEl) generateQRInto(qrEl, currentEmp);

    setTimeout(() => {
        window.print();
        printArea.style.display = 'none';
    }, 400);
}

function printAll() {
    const printArea = document.getElementById('printArea');
    printArea.innerHTML = '';
    printArea.style.display = 'flex';

    const q    = document.getElementById('searchInput').value.toLowerCase();
    const role = document.getElementById('roleFilter').value;
    const list = allEmployees.filter(e =>
        (!q || e.name.toLowerCase().includes(q))
        && (!role || e.role === role)
    );

    list.forEach(emp => {
        const cardFront = buildCard(emp, 'front');
        printArea.appendChild(cardFront);
        const qrEl = cardFront.querySelector(`#qr-${emp.id}`);
        if (qrEl) generateQRInto(qrEl, emp);
    });

    setTimeout(() => {
        window.print();
        printArea.style.display = 'none';
    }, 600);
}

function roleLabel(role) {
    const map = { teacher:'Teacher', registrar:'Registrar', admin:'Administrator', hr:'HR Officer' };
    return map[role] || (role ? role.charAt(0).toUpperCase() + role.slice(1) : 'Staff');
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
