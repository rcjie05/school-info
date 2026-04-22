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
    <title>HR Dashboard - Admin</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        .hr-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .hr-stat { background: white; border-radius: var(--radius-md); padding: 1.25rem 1.5rem; box-shadow: var(--shadow-sm); border-top: 4px solid var(--primary-purple); text-align: center; }
        .hr-stat.orange { border-top-color: #f59e0b; }
        .hr-stat.green  { border-top-color: #10b981; }
        .hr-stat.red    { border-top-color: #ef4444; }
        .hr-stat.blue   { border-top-color: #3b82f6; }
        .hr-stat-value  { font-size: 2rem; font-weight: 800; color: var(--text-primary); }
        .hr-stat-label  { font-size: 0.8rem; color: var(--text-secondary); font-weight: 600; margin-top: 0.25rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        @media(max-width:900px) { .two-col { grid-template-columns: 1fr; } }
        .leave-row { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid #f0f0f0; font-size: 0.875rem; }
        .leave-row:last-child { border-bottom: none; }
        .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
        .badge-pending  { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-cancelled{ background: #f3f4f6; color: #6b7280; }
        .upcoming-item  { display: flex; justify-content: space-between; align-items: center; padding: 0.65rem 0; border-bottom: 1px solid #f0f0f0; font-size: 0.875rem; }
        .upcoming-item:last-child { border-bottom: none; }
    </style>
    <link rel="stylesheet" href="../../css/enhancements.css">
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
                    <h1>HR Dashboard</h1>
                <p class="page-subtitle">Human Resources overview and quick actions</p>
            </div>
            <div class="header-actions">
                <a href="hr_leaves.php" class="btn btn-primary">📅 Manage Leaves</a>
            </div>
        </header>

        <div class="hr-stats" id="hrStats">
            <div class="hr-stat"><div class="hr-stat-value" id="statStaff">—</div><div class="hr-stat-label">Total Staff</div></div>
            <div class="hr-stat blue"><div class="hr-stat-value" id="statEmployees">—</div><div class="hr-stat-label">HR Profiles</div></div>
            <div class="hr-stat orange"><div class="hr-stat-value" id="statPending">—</div><div class="hr-stat-label">Pending Leaves</div></div>
            <div class="hr-stat green"><div class="hr-stat-value" id="statApproved">—</div><div class="hr-stat-label">Approved This Year</div></div>
            <div class="hr-stat red"><div class="hr-stat-value" id="statOnLeave">—</div><div class="hr-stat-label">On Leave Today</div></div>
        </div>

        <div class="two-col">
            <div class="content-card">
                <div class="card-header"><h2 class="card-title">📋 Recent Leave Requests</h2></div>
                <div id="recentLeaves" style="padding:1rem;">Loading...</div>
            </div>
            <div class="content-card">
                <div class="card-header"><h2 class="card-title">📆 Upcoming Approved Leaves</h2></div>
                <div id="upcomingLeaves" style="padding:1rem;">Loading...</div>
            </div>
        </div>
    </main>
</div>

<script>
async function loadDashboard() {
    const res  = await fetch('../../api/hr/get_dashboard_data.php');
    const data = await res.json();
    if (!data.success) return;

    document.getElementById('statStaff').textContent     = data.stats.total_staff;
    document.getElementById('statEmployees').textContent = data.stats.total_employees;
    document.getElementById('statPending').textContent   = data.stats.pending_leaves;
    document.getElementById('statApproved').textContent  = data.stats.approved_leaves;
    document.getElementById('statOnLeave').textContent   = data.stats.on_leave_today;

    // Recent leaves
    const rl = document.getElementById('recentLeaves');
    if (!data.recent_leaves.length) {
        rl.innerHTML = '<p style="color:var(--text-secondary);text-align:center;padding:1rem;">No leave requests yet.</p>';
    } else {
        rl.innerHTML = data.recent_leaves.map(l => `
            <div class="leave-row">
                <div style="flex:1;">
                    <div style="font-weight:600;">${esc(l.employee_name)}</div>
                    <div style="color:var(--text-secondary);font-size:0.8rem;">${esc(l.leave_type)} · ${l.total_days} day(s)</div>
                    <div style="color:var(--text-secondary);font-size:0.78rem;">${l.start_date} → ${l.end_date}</div>
                </div>
                <span class="badge badge-${l.status}">${l.status}</span>
            </div>`).join('');
    }

    // Upcoming leaves
    const ul = document.getElementById('upcomingLeaves');
    if (!data.upcoming_leaves.length) {
        ul.innerHTML = '<p style="color:var(--text-secondary);text-align:center;padding:1rem;">No upcoming leaves in the next 30 days.</p>';
    } else {
        ul.innerHTML = data.upcoming_leaves.map(l => `
            <div class="upcoming-item">
                <div>
                    <div style="font-weight:600;">${esc(l.employee_name)}</div>
                    <div style="color:var(--text-secondary);font-size:0.8rem;">${esc(l.leave_type)}</div>
                </div>
                <div style="text-align:right;font-size:0.82rem;">
                    <div>${l.start_date}</div>
                    <div style="color:var(--text-secondary);">${l.total_days} day(s)</div>
                </div>
            </div>`).join('');
    }
}

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
loadDashboard();
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

<script src="../../js/enhancements.js"></script>
</body>
</html>
