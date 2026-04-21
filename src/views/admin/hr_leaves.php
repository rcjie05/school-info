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
    <title>Leave Requests - HR</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; }
        .modal.active { display:flex; }
        .modal-content { background:white; padding:2rem; border-radius:var(--radius-lg); max-width:560px; width:90%; max-height:90vh; overflow-y:auto; }
        .badge { display:inline-block; padding:0.2rem 0.65rem; border-radius:999px; font-size:0.72rem; font-weight:700; text-transform:uppercase; }
        .badge-pending   { background:#fef3c7; color:#92400e; }
        .badge-approved  { background:#d1fae5; color:#065f46; }
        .badge-rejected  { background:#fee2e2; color:#991b1b; }
        .badge-cancelled { background:#f3f4f6; color:#6b7280; }
        .leave-card { background:var(--background-main); border-radius:var(--radius-md); padding:1.25rem 1.5rem; border-left:4px solid #e5e7eb; margin-bottom:1rem; }
        .leave-card.pending   { border-left-color:#f59e0b; }
        .leave-card.approved  { border-left-color:#10b981; }
        .leave-card.rejected  { border-left-color:#ef4444; }
        .leave-card.cancelled { border-left-color:#9ca3af; }
        .leave-meta { display:flex; gap:1rem; flex-wrap:wrap; font-size:0.82rem; color:var(--text-secondary); margin-top:0.4rem; }
        .detail-box { background:#f8fafc; border-radius:var(--radius-md); padding:1rem; margin-bottom:1rem; font-size:0.9rem; }
        .detail-box strong { display:inline-block; min-width:120px; color:var(--text-secondary); font-size:0.8rem; text-transform:uppercase; }
        .form-group { margin-bottom:1rem; }
        .form-group label { display:block; font-weight:600; font-size:0.85rem; margin-bottom:0.4rem; }
        .form-group textarea { width:100%; padding:0.75rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-family:inherit; resize:vertical; min-height:80px; box-sizing:border-box; }
        .form-group textarea:focus { outline:none; border-color:var(--primary-purple); }
        .action-btns { display:flex; gap:0.75rem; margin-top:1.25rem; }
        .btn-approve { background:#10b981; color:white; border:none; padding:0.65rem 1.5rem; border-radius:var(--radius-md); font-weight:600; cursor:pointer; flex:1; }
        .btn-reject  { background:#ef4444; color:white; border:none; padding:0.65rem 1.5rem; border-radius:var(--radius-md); font-weight:600; cursor:pointer; flex:1; }
        .btn-close   { background:#f3f4f6; color:var(--text-primary); border:none; padding:0.65rem 1.5rem; border-radius:var(--radius-md); font-weight:600; cursor:pointer; flex:1; }
        .filter-bar  { display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1rem; }
        .filter-bar select, .filter-bar input { padding:0.55rem 1rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-family:inherit; background:white; }
        .filter-bar input { flex:1; min-width:160px; }
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
                    <a href="settings.php" class="nav-item"><span class="nav-icon">⚙️</span><span>System Settings</span></a>
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
                    <h1>Leave Requests</h1>
                <p class="page-subtitle">Review and approve staff leave applications</p>
            </div>
        </header>

        <div class="content-card">
            <div class="card-header">
                <div class="filter-bar">
                    <input type="text" id="searchInput" placeholder="🔍 Search employee..." oninput="filterLeaves()">
                    <select id="statusFilter" onchange="filterLeaves()">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select id="yearFilter" onchange="loadLeaves()">
                        <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                        <option value="<?= date('Y')-1 ?>"><?= date('Y')-1 ?></option>
                    </select>
                </div>
            </div>
            <div id="leaveList" style="padding:1rem;">Loading...</div>
        </div>
    </main>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="modal">
    <div class="modal-content">
        <h2 style="margin:0 0 1.25rem;">📋 Leave Request Details</h2>
        <div id="leaveDetails"></div>
        <div class="form-group" id="noteGroup">
            <label>Note / Reason (optional)</label>
            <textarea id="reviewNote" placeholder="Add a note for the employee..."></textarea>
        </div>
        <div class="action-btns" id="actionBtns"></div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
let allLeaves = [], currentLeaveId = null;

async function loadLeaves() {
    const year = document.getElementById('yearFilter').value;
    const res  = await fetch(`../../api/hr/get_leave_requests.php?year=${year}`);
    const data = await res.json();
    if (!data.success) return;
    allLeaves = data.requests;
    filterLeaves();
}

function filterLeaves() {
    const q      = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const list   = allLeaves.filter(l =>
        (!q || l.employee_name.toLowerCase().includes(q))
        && (!status || l.status === status)
    );
    renderLeaves(list);
}

function renderLeaves(list) {
    const c = document.getElementById('leaveList');
    if (!list.length) { c.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">No leave requests found.</p>'; return; }
    c.innerHTML = list.map(l => `
        <div class="leave-card ${l.status}" onclick="openReview(${l.id})" style="cursor:pointer;">
            <div style="display:flex;justify-content:space-between;align-items:start;">
                <div>
                    <div style="font-weight:700;font-size:1rem;">${esc(l.employee_name)}</div>
                    <div class="leave-meta">
                        <span>🏷️ ${esc(l.leave_type)}</span>
                        <span>📅 ${l.start_date} → ${l.end_date}</span>
                        <span>⏱️ ${l.total_days} day(s)</span>
                        <span>👤 ${esc(l.employee_role)}</span>
                    </div>
                </div>
                <span class="badge badge-${l.status}">${l.status}</span>
            </div>
            <p style="margin:0.5rem 0 0;font-size:0.875rem;color:var(--text-secondary);">${esc(l.reason.substring(0,120))}${l.reason.length>120?'...':''}</p>
        </div>`).join('');
}

function openReview(id) {
    const l = allLeaves.find(x => x.id === id);
    if (!l) return;
    currentLeaveId = id;

    document.getElementById('leaveDetails').innerHTML = `
        <div class="detail-box">
            <div><strong>Employee:</strong> ${esc(l.employee_name)} (${esc(l.employee_role)})</div>
            <div style="margin-top:0.5rem;"><strong>Leave Type:</strong> ${esc(l.leave_type)}</div>
            <div style="margin-top:0.5rem;"><strong>Duration:</strong> ${l.start_date} → ${l.end_date} (${l.total_days} working day(s))</div>
            <div style="margin-top:0.5rem;"><strong>Submitted:</strong> ${l.created_at}</div>
            <div style="margin-top:0.5rem;"><strong>Status:</strong> <span class="badge badge-${l.status}">${l.status}</span></div>
            <div style="margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid #e5e7eb;"><strong>Reason:</strong><br><span style="font-size:0.9rem;">${esc(l.reason)}</span></div>
            ${l.review_note ? `<div style="margin-top:0.5rem;color:#6b7280;font-size:0.85rem;"><strong>Review Note:</strong> ${esc(l.review_note)}</div>` : ''}
            ${l.reviewed_by_name ? `<div style="margin-top:0.25rem;color:#6b7280;font-size:0.8rem;">Reviewed by ${esc(l.reviewed_by_name)} on ${l.reviewed_at}</div>` : ''}
        </div>`;

    document.getElementById('reviewNote').value = '';

    const btns = document.getElementById('actionBtns');
    if (l.status === 'pending') {
        document.getElementById('noteGroup').style.display = 'block';
        btns.innerHTML = `
            <button class="btn-approve" onclick="reviewLeave('approved')">✅ Approve</button>
            <button class="btn-reject"  onclick="reviewLeave('rejected')">❌ Reject</button>
            <button class="btn-close"   onclick="closeReview()">Close</button>`;
    } else {
        document.getElementById('noteGroup').style.display = 'none';
        btns.innerHTML = `<button class="btn-close" onclick="closeReview()" style="flex:none;width:100%;">Close</button>`;
    }
    document.getElementById('reviewModal').classList.add('active');
}

function closeReview() { document.getElementById('reviewModal').classList.remove('active'); }

async function reviewLeave(action) {
    const note = document.getElementById('reviewNote').value.trim();
    const res  = await fetch('../../api/hr/review_leave.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ leave_id: currentLeaveId, action, review_note: note })
    });
    const data = await res.json();
    if (data.success) { showToast(data.message, 'success'); closeReview(); loadLeaves(); }
    else showToast('Error: ' + data.message, 'error');
}

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.className = `toast ${type}`; t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3500);
}
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
loadLeaves();
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
