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
    <title>Payroll - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        /* ── Layout ──────────────────────────────────── */
        .split-layout { display:grid; grid-template-columns:360px 1fr; gap:1.5rem; }
        @media(max-width:900px){ .split-layout{ grid-template-columns:1fr; } }

        /* ── Toolbar ─────────────────────────────────── */
        .pay-toolbar { display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1.25rem; align-items:center; }
        .pay-toolbar input[type="month"],
        .pay-toolbar select { padding:0.55rem 1rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-family:inherit; font-size:0.88rem; background:white; }
        .pay-toolbar label { font-size:0.82rem; font-weight:600; color:var(--text-secondary); white-space:nowrap; }

        /* ── Employee List ───────────────────────────── */
        .emp-row { display:flex; align-items:center; gap:0.9rem; padding:0.85rem 1rem; border-bottom:1px solid #f0f0f0; cursor:pointer; transition:background 0.15s; border-left:3px solid transparent; }
        .emp-row:hover { background:#f8fafc; }
        .emp-row.active { background:#eff6ff; border-left-color:var(--primary-purple); }
        .emp-avatar { width:40px; height:40px; border-radius:50%; background:var(--primary-purple); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.95rem; flex-shrink:0; overflow:hidden; }
        .emp-avatar img { width:100%; height:100%; object-fit:cover; }

        /* ── Payslip Panel ───────────────────────────── */
        .payslip-panel { background:white; border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); overflow:hidden; }
        .payslip-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; height:400px; color:var(--text-secondary); gap:1rem; }

        /* ── Payslip Header ──────────────────────────── */
        .slip-header { background:linear-gradient(135deg, var(--primary-purple), #6366f1); padding:1.75rem 2rem; color:white; }
        .slip-school { font-size:0.8rem; opacity:0.8; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:0.25rem; }
        .slip-title  { font-size:1.3rem; font-weight:800; margin-bottom:0.5rem; }
        .slip-period { font-size:0.88rem; opacity:0.85; }

        /* ── Payslip Body ────────────────────────────── */
        .slip-body { padding:1.5rem; }
        .slip-section { margin-bottom:1.5rem; }
        .slip-section-title { font-size:0.72rem; font-weight:800; color:var(--primary-purple); text-transform:uppercase; letter-spacing:0.8px; margin-bottom:0.75rem; padding-bottom:0.35rem; border-bottom:2px solid #eef2f7; }
        .slip-row { display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid #f5f5f5; font-size:0.88rem; }
        .slip-row:last-child { border-bottom:none; }
        .slip-row.total { font-weight:800; font-size:0.95rem; border-top:2px solid #e5e7eb; margin-top:0.25rem; padding-top:0.75rem; border-bottom:none; }
        .slip-row.net   { font-weight:800; font-size:1.1rem; color:var(--primary-purple); }
        .slip-label { color:var(--text-secondary); }
        .slip-amount { font-family:monospace; text-align:right; }
        .slip-amount.positive { color:#10b981; }
        .slip-amount.negative { color:#ef4444; }

        /* ── Badge ───────────────────────────────────── */
        .badge { display:inline-block; padding:0.2rem 0.65rem; border-radius:999px; font-size:0.72rem; font-weight:700; text-transform:uppercase; }
        .badge-draft    { background:#fef3c7; color:#92400e; }
        .badge-released { background:#d1fae5; color:#065f46; }

        /* ── Modal ───────────────────────────────────── */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; }
        .modal.active { display:flex; }
        .modal-content { background:white; padding:2rem; border-radius:var(--radius-lg); max-width:700px; width:90%; max-height:92vh; overflow-y:auto; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .form-grid .full { grid-column:1/-1; }
        .form-group label { display:block; font-weight:600; font-size:0.8rem; margin-bottom:0.35rem; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.4px; }
        .form-group input, .form-group select, .form-group textarea { width:100%; padding:0.65rem 0.9rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-size:0.9rem; font-family:inherit; box-sizing:border-box; }
        .form-group input:focus, .form-group select:focus { outline:none; border-color:var(--primary-purple); }
        .section-title { font-size:0.78rem; font-weight:700; color:var(--primary-purple); text-transform:uppercase; letter-spacing:0.6px; margin:1.25rem 0 0.75rem; padding-bottom:0.4rem; border-bottom:2px solid #eef2f7; grid-column:1/-1; }
        .computed-box { background:#f8fafc; border-radius:var(--radius-md); padding:1rem 1.25rem; grid-column:1/-1; font-size:0.88rem; }
        .computed-row { display:flex; justify-content:space-between; padding:0.3rem 0; border-bottom:1px solid #eee; }
        .computed-row:last-child { border-bottom:none; font-weight:800; color:var(--primary-purple); font-size:1rem; padding-top:0.5rem; margin-top:0.25rem; border-top:2px solid #e5e7eb; }

        /* ── Toast ───────────────────────────────────── */
        .toast { position:fixed; bottom:2rem; right:2rem; padding:1rem 1.5rem; border-radius:var(--radius-md); color:white; font-weight:600; z-index:9999; display:none; }
        .toast.success { background:#10b981; }
        .toast.error   { background:#ef4444; }

        /* ── List toolbar ────────────────────────────── */
        .list-toolbar { padding:0.75rem 1rem; border-bottom:1px solid #f0f0f0; }
        .list-toolbar input { width:100%; padding:0.5rem 0.75rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-size:0.875rem; font-family:inherit; box-sizing:border-box; }
        .payroll-badge-row { font-size:0.72rem; margin-top:0.2rem; display:flex; gap:0.4rem; align-items:center; }
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
                    <h1>Payroll</h1>
                <p class="page-subtitle">Generate and manage monthly payslips</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openBulkGenerate()">⚡ Generate All Payslips</button>
            </div>
        </header>

        <!-- Toolbar -->
        <div class="pay-toolbar">
            <label>Payroll Period:</label>
            <input type="month" id="payMonth" onchange="loadPayroll()">
            <select id="roleFilter" onchange="loadPayroll()">
                <option value="">All Roles</option>
                <option value="teacher">Teachers</option>
                <option value="registrar">Registrars</option>
                <option value="admin">Admin</option>
            </select>
            <select id="statusFilter" onchange="filterList()">
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="released">Released</option>
                <option value="none">No Payslip</option>
            </select>
        </div>

        <div class="split-layout">
            <!-- Left: Employee List -->
            <div class="content-card" style="padding:0;overflow:hidden;">
                <div class="list-toolbar">
                    <input type="text" id="searchInput" placeholder="🔍 Search employees..." oninput="filterList()">
                </div>
                <div id="employeeList">
                    <p style="text-align:center;color:var(--text-secondary);padding:2rem;">Select a payroll period.</p>
                </div>
            </div>

            <!-- Right: Payslip Panel -->
            <div class="payslip-panel" id="payslipPanel">
                <div class="payslip-empty">
                    <div style="font-size:3rem;opacity:0.4;">💰</div>
                    <p style="font-weight:600;">Select an employee</p>
                    <p style="font-size:0.85rem;">Click any employee on the left to view or generate their payslip</p>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Edit Payslip Modal -->
<div id="payModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle" style="margin:0 0 1.5rem;">Generate Payslip</h2>
        <input type="hidden" id="editUserId">
        <input type="hidden" id="editPayrollId">
        <div class="form-grid">
            <div class="section-title">Earnings</div>
            <div class="form-group">
                <label>Basic Salary (₱)</label>
                <input type="number" id="fBasic" step="0.01" placeholder="0.00" oninput="computePayslip()">
            </div>
            <div class="form-group">
                <label>Days Worked</label>
                <input type="number" id="fDaysWorked" step="0.5" placeholder="e.g. 22" oninput="computePayslip()">
            </div>
            <div class="form-group">
                <label>Days Absent</label>
                <input type="number" id="fDaysAbsent" step="0.5" placeholder="e.g. 0" oninput="computePayslip()">
            </div>
            <div class="form-group">
                <label>Overtime Hours</label>
                <input type="number" id="fOTHours" step="0.5" placeholder="e.g. 0" oninput="computePayslip()">
            </div>
            <div class="form-group">
                <label>Overtime Pay (₱)</label>
                <input type="number" id="fOTPay" step="0.01" placeholder="0.00" oninput="computePayslip()">
            </div>
            <div class="form-group">
                <label>Allowances (₱)</label>
                <input type="number" id="fAllowances" step="0.01" placeholder="0.00" oninput="computePayslip()">
            </div>

            <div class="section-title">Deductions</div>
            <div class="form-group">
                <label>SSS (₱)</label>
                <input type="number" id="fSSS" step="0.01" placeholder="0.00" oninput="computePayslip()">
            </div>
            <div class="form-group">
                <label>PhilHealth (₱)</label>
                <input type="number" id="fPhilHealth" step="0.01" placeholder="0.00" oninput="computePayslip()">
            </div>
            <div class="form-group">
                <label>Pag-IBIG (₱)</label>
                <input type="number" id="fPagIBIG" step="0.01" placeholder="0.00" oninput="computePayslip()">
            </div>
            <div class="form-group">
                <label>Tax Withholding (₱)</label>
                <input type="number" id="fTax" step="0.01" placeholder="0.00" oninput="computePayslip()">
            </div>
            <div class="form-group">
                <label>Other Deductions (₱)</label>
                <input type="number" id="fOtherDed" step="0.01" placeholder="0.00" oninput="computePayslip()">
            </div>

            <div class="section-title">Summary</div>
            <div class="computed-box">
                <div class="computed-row"><span>Gross Pay</span><span id="cGross">₱0.00</span></div>
                <div class="computed-row"><span>Total Deductions</span><span id="cDeductions" style="color:#ef4444;">-₱0.00</span></div>
                <div class="computed-row"><span>💵 NET PAY</span><span id="cNet">₱0.00</span></div>
            </div>

            <div class="form-group full">
                <label>Remarks</label>
                <textarea id="fRemarks" rows="2" placeholder="Optional notes..."></textarea>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select id="fStatus">
                    <option value="draft">Draft</option>
                    <option value="released">Released</option>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:1rem;margin-top:1.75rem;">
            <button class="btn btn-primary" onclick="savePayroll()" style="flex:1;">💾 Save Payslip</button>
            <button class="btn" onclick="document.getElementById('payModal').classList.remove('active')" style="flex:1;">Cancel</button>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
let allEmployees = [];
let currentEmp   = null;

(function init() {
    const now = new Date();
    const mm  = now.toISOString().substring(0, 7);
    document.getElementById('payMonth').value = mm;
    loadPayroll();
})();

async function loadPayroll() {
    const month = document.getElementById('payMonth').value;
    const role  = document.getElementById('roleFilter').value;
    if (!month) return;

    const res  = await fetch(`../../api/hr/get_payroll.php?month=${month}&role=${role}`);
    const data = await res.json();
    if (!data.success) { showToast('Failed to load payroll', 'error'); return; }
    allEmployees = data.employees;
    filterList();
}

function filterList() {
    const q      = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const list   = allEmployees.filter(e => {
        const matchQ = !q || e.name.toLowerCase().includes(q) || (e.position||'').toLowerCase().includes(q);
        const empStatus = e.payroll_status || 'none';
        const matchS = !status || empStatus === status;
        return matchQ && matchS;
    });
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
        const avatarHtml = e.avatar_url ? `<img src="${e.avatar_url}" alt="">` : init;
        const ps = e.payroll_status || 'none';
        const badgeHtml = ps === 'none'
            ? '<span style="font-size:0.72rem;color:#9ca3af;">+ Generate Payslip</span>'
            : `<span class="badge badge-${ps}">${ps}</span>`;
        const netPay = ps !== 'none' ? `<span style="font-size:0.75rem;color:#10b981;font-weight:700;">₱${parseFloat(e.net_pay||0).toLocaleString('en-PH',{minimumFractionDigits:2})}</span>` : '';
        const isActive = currentEmp && currentEmp.id == e.id;
        return `<div class="emp-row${isActive?' active':''}" onclick="showPayslip(${e.id})" data-id="${e.id}">
            <div class="emp-avatar">${avatarHtml}</div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:0.9rem;">${esc(e.name)}</div>
                <div style="font-size:0.78rem;color:var(--text-secondary);">${esc(e.position||e.role)}</div>
                <div class="payroll-badge-row">${badgeHtml} ${netPay}</div>
            </div>
        </div>`;
    }).join('');
}

function showPayslip(id) {
    currentEmp = allEmployees.find(e => e.id == id);
    if (!currentEmp) return;

    document.querySelectorAll('.emp-row').forEach(r => r.classList.remove('active'));
    const row = document.querySelector(`.emp-row[data-id="${id}"]`);
    if (row) row.classList.add('active');

    const e   = currentEmp;
    const has = !!e.payroll_id;
    const month = document.getElementById('payMonth').value;
    const [yr, mo] = month.split('-');
    const monthName = new Date(parseInt(yr), parseInt(mo)-1, 1).toLocaleDateString('en-US',{month:'long',year:'numeric'});
    const init = (e.name||'?')[0].toUpperCase();
    const avatarHtml = e.avatar_url ? `<img src="${e.avatar_url}" alt="">` : init;

    const statusBadge = has ? `<span class="badge badge-${e.payroll_status}">${e.payroll_status}</span>` : '';

    const payslipHtml = has ? `
        <div class="slip-header">
            <div class="slip-school"><?= htmlspecialchars($school_name) ?></div>
            <div class="slip-title">Payslip — ${esc(e.name)}</div>
            <div class="slip-period">Period: ${monthName} &nbsp;${statusBadge}</div>
        </div>
        <div class="slip-body">
            <div class="slip-section">
                <div class="slip-section-title">Earnings</div>
                ${slipRow('Basic Salary', e.basic_salary, true)}
                ${slipRow('Days Worked', e.days_worked + ' days')}
                ${slipRow('Days Absent', e.days_absent + ' days')}
                ${slipRow('Overtime Hours', e.overtime_hours + ' hrs')}
                ${slipRow('Overtime Pay', e.overtime_pay, true)}
                ${slipRow('Allowances', e.allowances, true)}
                <div class="slip-row total"><span class="slip-label">Gross Pay</span><span class="slip-amount positive">₱${fmt(e.gross_pay)}</span></div>
            </div>
            <div class="slip-section">
                <div class="slip-section-title">Deductions</div>
                ${slipRow('SSS', e.sss_deduction, true, true)}
                ${slipRow('PhilHealth', e.philhealth_deduction, true, true)}
                ${slipRow('Pag-IBIG', e.pagibig_deduction, true, true)}
                ${slipRow('Withholding Tax', e.tax_deduction, true, true)}
                ${slipRow('Other Deductions', e.other_deductions, true, true)}
                <div class="slip-row total"><span class="slip-label">Total Deductions</span><span class="slip-amount negative">-₱${fmt(e.total_deductions)}</span></div>
            </div>
            <div class="slip-row net"><span>NET PAY</span><span>₱${fmt(e.net_pay)}</span></div>
            ${e.remarks ? `<p style="margin-top:1rem;font-size:0.85rem;color:var(--text-secondary);">Remarks: ${esc(e.remarks)}</p>` : ''}
            <div style="display:flex;gap:0.75rem;margin-top:1.5rem;">
                <button class="btn btn-primary" onclick="openEditModal()" style="flex:1;">✏️ Edit Payslip</button>
                <button class="btn" onclick="printPayslip()" style="flex:1;">🖨️ Print</button>
            </div>
        </div>` :
        `<div class="payslip-empty">
            <div style="font-size:3rem;opacity:0.4;">💰</div>
            <p style="font-weight:600;">${esc(e.name)}</p>
            <p style="font-size:0.85rem;color:var(--text-secondary);">No payslip for ${monthName} yet.</p>
            <button class="btn btn-primary" onclick="openEditModal()">+ Generate Payslip</button>
        </div>`;

    document.getElementById('payslipPanel').innerHTML = payslipHtml;
}

function slipRow(label, val, isMoney=false, negative=false) {
    const display = isMoney ? (negative ? `-₱${fmt(val)}` : `₱${fmt(val)}`) : esc(String(val||'0'));
    const cls = isMoney ? (negative ? 'negative' : '') : '';
    return `<div class="slip-row"><span class="slip-label">${label}</span><span class="slip-amount ${cls}">${display}</span></div>`;
}

function fmt(v) { return parseFloat(v||0).toLocaleString('en-PH',{minimumFractionDigits:2}); }

function openEditModal() {
    if (!currentEmp) return;
    const e = currentEmp;
    const month = document.getElementById('payMonth').value;
    const [yr, mo] = month.split('-');
    const monthName = new Date(parseInt(yr), parseInt(mo)-1, 1).toLocaleDateString('en-US',{month:'long',year:'numeric'});

    document.getElementById('modalTitle').textContent = `Payslip — ${e.name} (${monthName})`;
    document.getElementById('editUserId').value   = e.id;
    document.getElementById('editPayrollId').value = e.payroll_id || '';
    document.getElementById('fBasic').value        = e.basic_salary     || e.monthly_salary || '';
    document.getElementById('fDaysWorked').value   = e.days_worked      || '';
    document.getElementById('fDaysAbsent').value   = e.days_absent      || '';
    document.getElementById('fOTHours').value      = e.overtime_hours   || '';
    document.getElementById('fOTPay').value        = e.overtime_pay     || '';
    document.getElementById('fAllowances').value   = e.allowances       || '';
    document.getElementById('fSSS').value          = e.sss_deduction    || '';
    document.getElementById('fPhilHealth').value   = e.philhealth_deduction || '';
    document.getElementById('fPagIBIG').value      = e.pagibig_deduction || '';
    document.getElementById('fTax').value          = e.tax_deduction    || '';
    document.getElementById('fOtherDed').value     = e.other_deductions  || '';
    document.getElementById('fRemarks').value      = e.remarks          || '';
    document.getElementById('fStatus').value       = e.payroll_status   || 'draft';
    computePayslip();
    document.getElementById('payModal').classList.add('active');
}

function computePayslip() {
    const basic    = parseFloat(document.getElementById('fBasic').value)      || 0;
    const otPay    = parseFloat(document.getElementById('fOTPay').value)       || 0;
    const allow    = parseFloat(document.getElementById('fAllowances').value)  || 0;
    const sss      = parseFloat(document.getElementById('fSSS').value)         || 0;
    const ph       = parseFloat(document.getElementById('fPhilHealth').value)  || 0;
    const pi       = parseFloat(document.getElementById('fPagIBIG').value)     || 0;
    const tax      = parseFloat(document.getElementById('fTax').value)         || 0;
    const otherD   = parseFloat(document.getElementById('fOtherDed').value)    || 0;
    const gross    = basic + otPay + allow;
    const totalDed = sss + ph + pi + tax + otherD;
    const net      = gross - totalDed;
    document.getElementById('cGross').textContent      = `₱${fmt(gross)}`;
    document.getElementById('cDeductions').textContent = `-₱${fmt(totalDed)}`;
    document.getElementById('cNet').textContent        = `₱${fmt(net)}`;
}

async function savePayroll() {
    const month  = document.getElementById('payMonth').value;
    if (!month) return;
    const [yr, mo] = month.split('-');
    const payrollMonth = `${yr}-${mo}-01`;

    const basic    = parseFloat(document.getElementById('fBasic').value)      || 0;
    const otPay    = parseFloat(document.getElementById('fOTPay').value)       || 0;
    const allow    = parseFloat(document.getElementById('fAllowances').value)  || 0;
    const sss      = parseFloat(document.getElementById('fSSS').value)         || 0;
    const ph       = parseFloat(document.getElementById('fPhilHealth').value)  || 0;
    const pi       = parseFloat(document.getElementById('fPagIBIG').value)     || 0;
    const tax      = parseFloat(document.getElementById('fTax').value)         || 0;
    const otherD   = parseFloat(document.getElementById('fOtherDed').value)    || 0;
    const gross    = basic + otPay + allow;
    const totalDed = sss + ph + pi + tax + otherD;
    const net      = gross - totalDed;

    const payload = {
        user_id:              parseInt(document.getElementById('editUserId').value),
        payroll_id:           document.getElementById('editPayrollId').value || null,
        payroll_month:        payrollMonth,
        basic_salary:         basic,
        days_worked:          parseFloat(document.getElementById('fDaysWorked').value) || 0,
        days_absent:          parseFloat(document.getElementById('fDaysAbsent').value) || 0,
        overtime_hours:       parseFloat(document.getElementById('fOTHours').value)    || 0,
        overtime_pay:         otPay,
        allowances:           allow,
        gross_pay:            gross,
        sss_deduction:        sss,
        philhealth_deduction: ph,
        pagibig_deduction:    pi,
        tax_deduction:        tax,
        other_deductions:     otherD,
        total_deductions:     totalDed,
        net_pay:              net,
        status:               document.getElementById('fStatus').value,
        remarks:              document.getElementById('fRemarks').value
    };

    const res  = await fetch('../../api/hr/save_payroll.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
        showToast('Payslip saved!', 'success');
        document.getElementById('payModal').classList.remove('active');
        const prevId = currentEmp.id;
        await loadPayroll();
        showPayslip(prevId);
    } else {
        showToast('Error: ' + data.message, 'error');
    }
}

function openBulkGenerate() {
    showToast('Select individual employees to generate payslips.', 'success');
}

function printPayslip() {
    window.print();
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
