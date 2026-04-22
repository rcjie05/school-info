<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('registrar');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests - Registrar</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        .leave-form-card { background:white; border-radius:var(--radius-lg); padding:2rem; box-shadow:var(--shadow-md); margin-bottom:2rem; }
        .form-group { margin-bottom:1.1rem; }
        .form-group label { display:block; font-weight:600; font-size:0.875rem; margin-bottom:0.4rem; color:var(--text-primary); }
        .form-group input, .form-group select, .form-group textarea { width:100%; padding:0.75rem 1rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-size:0.95rem; font-family:inherit; box-sizing:border-box; }
        .form-group textarea { resize:vertical; min-height:100px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline:none; border-color:var(--primary-purple); }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .btn-submit { background:linear-gradient(135deg, var(--primary-purple), var(--secondary-pink)); color:white; border:none; padding:0.875rem 2rem; border-radius:var(--radius-md); font-size:1rem; font-weight:600; cursor:pointer; width:100%; margin-top:0.5rem; }
        .btn-submit:disabled { opacity:0.6; cursor:not-allowed; }
        .badge { display:inline-block; padding:0.2rem 0.65rem; border-radius:999px; font-size:0.72rem; font-weight:700; text-transform:uppercase; }
        .badge-pending   { background:#fef3c7; color:#92400e; }
        .badge-approved  { background:#d1fae5; color:#065f46; }
        .badge-rejected  { background:#fee2e2; color:#991b1b; }
        .badge-cancelled { background:#f3f4f6; color:#6b7280; }
        .leave-card { background:var(--background-main); border-radius:var(--radius-md); padding:1.25rem 1.5rem; border-left:4px solid #e5e7eb; margin-bottom:0.85rem; }
        .leave-card.pending   { border-left-color:#f59e0b; }
        .leave-card.approved  { border-left-color:#10b981; }
        .leave-card.rejected  { border-left-color:#ef4444; }
        .leave-card.cancelled { border-left-color:#9ca3af; }
        .balance-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:0.75rem; margin-bottom:2rem; }
        .balance-card { background:white; border-radius:var(--radius-md); padding:1rem; box-shadow:var(--shadow-sm); text-align:center; }
        .balance-used  { font-size:1.5rem; font-weight:800; color:var(--primary-purple); }
        .balance-total { font-size:0.8rem; color:var(--text-secondary); }
        .balance-name  { font-size:0.78rem; font-weight:700; color:var(--text-primary); margin-bottom:0.3rem; }
        .leave-meta    { font-size:0.82rem; color:var(--text-secondary); display:flex; gap:1rem; flex-wrap:wrap; margin-top:0.35rem; }
        .review-note   { margin-top:0.75rem; padding:0.65rem 0.85rem; border-radius:var(--radius-md); font-size:0.85rem; }
        .review-note.approved { background:#f0fdf4; border-left:3px solid #10b981; color:#065f46; }
        .review-note.rejected { background:#fef2f2; border-left:3px solid #ef4444; color:#991b1b; }
        .toast { position:fixed; bottom:2rem; right:2rem; padding:1rem 1.5rem; border-radius:var(--radius-md); color:white; font-weight:600; z-index:9999; display:none; }
        .toast.success { background:#10b981; }
        .toast.error   { background:#ef4444; }
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
                    <span>Registrar Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="applications.php" class="nav-item"><span class="nav-icon">📋</span><span>Applications</span></a>
                    <a href="manage_loads.php" class="nav-item"><span class="nav-icon">📚</span><span>Study Loads</span></a>
                    <a href="grades.php" class="nav-item"><span class="nav-icon">🎓</span><span>Grades</span></a>
                    <a href="add_drop_requests.php" class="nav-item"><span class="nav-icon">🔄</span><span>Add/Drop Requests</span></a>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                    <a href="reports.php" class="nav-item"><span class="nav-icon">📈</span><span>Reports</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>Announcements</span></a>
                    <a href="feedback.php" class="nav-item"><span class="nav-icon">💬</span><span>Feedback</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">HR</div>
                    <a href="leave_requests.php" class="nav-item active"><span class="nav-icon">🏖️</span><span>Leave Requests</span></a>
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
                    <h1>Leave Requests</h1>
                <p class="page-subtitle">Submit and track your leave applications</p>
            </div>
        </header>

        <!-- Leave Balances -->
        <h3 style="margin-bottom:0.75rem;font-size:1rem;color:var(--text-secondary);">📊 My Leave Balances (<?= date('Y') ?>)</h3>
        <div class="balance-grid" id="balanceGrid">Loading...</div>

        <!-- Submit Form -->
        <div class="leave-form-card">
            <h2 style="margin:0 0 1.5rem;font-size:1.2rem;">🏖️ Submit Leave Request</h2>
            <div class="form-group">
                <label>Leave Type</label>
                <select id="leaveType"></select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" id="startDate" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" id="endDate" min="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="form-group" id="daysPreview" style="display:none;">
                <div style="padding:0.65rem 1rem;background:#eff6ff;border-radius:var(--radius-md);font-size:0.9rem;color:#1e40af;font-weight:600;">
                    📅 Working days: <span id="daysCount">—</span>
                </div>
            </div>
            <div class="form-group">
                <label>Reason</label>
                <textarea id="reason" placeholder="Briefly describe your reason for the leave..."></textarea>
            </div>
            <button class="btn-submit" id="submitBtn" onclick="submitLeave()">📤 Submit Request</button>
        </div>

        <!-- My Requests History -->
        <div class="content-card">
            <div class="card-header"><h2 class="card-title">📋 My Leave History</h2></div>
            <div id="leaveHistory" style="padding:1rem;">Loading...</div>
        </div>
    </main>
</div>

<div class="toast" id="toast"></div>

<script>
let leaveTypes = [];

async function loadMyLeaves() {
    const year = <?= date('Y') ?>;
    const res  = await fetch(`../../api/hr/get_my_leaves.php?year=${year}`);
    const data = await res.json();
    if (!data.success) return;

    leaveTypes = data.leave_types;

    // Populate leave type dropdown
    const sel = document.getElementById('leaveType');
    sel.innerHTML = data.leave_types.map(lt =>
        `<option value="${lt.id}">${esc(lt.name)} (max ${lt.max_days_per_year} days/yr)</option>`
    ).join('');

    // Balances
    const bg = document.getElementById('balanceGrid');
    if (data.balances.length) {
        bg.innerHTML = data.balances.map(b => {
            const remaining = b.max_days_per_year - b.used_days;
            const pct = Math.min(100, Math.round((b.used_days / b.max_days_per_year) * 100));
            return `<div class="balance-card">
                <div class="balance-name">${esc(b.name)}</div>
                <div class="balance-used">${remaining}</div>
                <div class="balance-total">of ${b.max_days_per_year} days left</div>
                <div style="background:#e5e7eb;border-radius:999px;height:4px;margin-top:0.5rem;">
                    <div style="background:var(--primary-purple);height:4px;border-radius:999px;width:${pct}%;"></div>
                </div>
            </div>`;
        }).join('');
    } else {
        bg.innerHTML = '<p style="color:var(--text-secondary);font-size:0.875rem;">No balance data yet.</p>';
    }

    // History
    const hist = document.getElementById('leaveHistory');
    if (!data.leaves.length) {
        hist.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">No leave requests submitted yet.</p>';
        return;
    }
    hist.innerHTML = data.leaves.map(l => {
        const noteHtml  = l.review_note ? `<div class="review-note ${l.status}">💬 ${esc(l.review_note)}</div>` : '';
        const cancelBtn = l.status === 'pending'
            ? `<button onclick="cancelLeave(${l.id})" style="background:none;border:1px solid #ef4444;color:#ef4444;padding:0.3rem 0.75rem;border-radius:var(--radius-md);cursor:pointer;font-size:0.8rem;font-weight:600;margin-top:0.5rem;">Cancel</button>`
            : '';
        return `<div class="leave-card ${l.status}">
            <div style="display:flex;justify-content:space-between;align-items:start;">
                <div>
                    <div style="font-weight:700;">${esc(l.leave_type)}</div>
                    <div class="leave-meta">
                        <span>📅 ${l.start_date} → ${l.end_date}</span>
                        <span>⏱️ ${l.total_days} day(s)</span>
                        <span>🕐 Submitted ${l.created_at.substring(0,10)}</span>
                    </div>
                    <p style="margin:0.5rem 0 0;font-size:0.875rem;color:var(--text-secondary);">${esc(l.reason)}</p>
                    ${noteHtml}
                    ${cancelBtn}
                </div>
                <span class="badge badge-${l.status}">${l.status}</span>
            </div>
        </div>`;
    }).join('');
}

function calcDays() {
    const s = document.getElementById('startDate').value;
    const e = document.getElementById('endDate').value;
    if (!s || !e || e < s) { document.getElementById('daysPreview').style.display='none'; return; }
    let days = 0, cur = new Date(s), end = new Date(e);
    while (cur <= end) { const d = cur.getDay(); if (d !== 0 && d !== 6) days++; cur.setDate(cur.getDate()+1); }
    document.getElementById('daysCount').textContent = days;
    document.getElementById('daysPreview').style.display = 'block';
}
document.getElementById('startDate').addEventListener('change', () => {
    const s = document.getElementById('startDate').value;
    document.getElementById('endDate').min = s;
    calcDays();
});
document.getElementById('endDate').addEventListener('change', calcDays);

async function submitLeave() {
    const leaveTypeId = document.getElementById('leaveType').value;
    const startDate   = document.getElementById('startDate').value;
    const endDate     = document.getElementById('endDate').value;
    const reason      = document.getElementById('reason').value.trim();
    const btn         = document.getElementById('submitBtn');

    if (!leaveTypeId || !startDate || !endDate || !reason) { showToast('Please fill in all fields.', 'error'); return; }
    if (endDate < startDate) { showToast('End date cannot be before start date.', 'error'); return; }

    btn.disabled = true; btn.textContent = 'Submitting...';
    const res  = await fetch('../../api/hr/submit_leave.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ leave_type_id: parseInt(leaveTypeId), start_date: startDate, end_date: endDate, reason })
    });
    const data = await res.json();
    if (data.success) {
        showToast(data.message, 'success');
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value   = '';
        document.getElementById('reason').value    = '';
        document.getElementById('daysPreview').style.display = 'none';
        loadMyLeaves();
    } else {
        showToast(data.message || 'Failed to submit.', 'error');
    }
    btn.disabled = false; btn.textContent = '📤 Submit Request';
}

async function cancelLeave(id) {
    if (!confirm('Cancel this leave request?')) return;
    const res  = await fetch('../../api/hr/cancel_leave.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({leave_id:id}) });
    const data = await res.json();
    if (data.success) { showToast(data.message, 'success'); loadMyLeaves(); }
    else showToast(data.message, 'error');
}

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.className = `toast ${type}`; t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3500);
}
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
loadMyLeaves();
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
    <script src="../../../public/js/session-monitor.js"></script>
    <script src="../../../public/js/apply-branding.js"></script>

    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
      <a href="dashboard.php" class="mobile-nav-item" data-page="dashboard">
        <span class="mobile-nav-icon">📊</span><span>Home</span>
      </a>
      <a href="applications.php" class="mobile-nav-item" data-page="applications">
        <span class="mobile-nav-icon">📋</span><span>Apps</span>
      </a>
      <a href="manage_loads.php" class="mobile-nav-item" data-page="manage_loads">
        <span class="mobile-nav-icon">📚</span><span>Loads</span>
      </a>
      <a href="announcements.php" class="mobile-nav-item" data-page="announcements">
        <span class="mobile-nav-icon">📢</span><span>Notices</span>
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
