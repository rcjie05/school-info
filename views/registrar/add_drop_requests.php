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
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add/Drop Requests - Registrar</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        .filter-row { display:flex; gap:.75rem; flex-wrap:wrap; margin-bottom:1.25rem; align-items:center; }
        .filter-row select { padding:.5rem .85rem; border:1.5px solid #d1d5db; border-radius:var(--radius-md); font-size:.88rem; }

        .req-card {
            background: var(--background-main);
            border-radius: var(--radius-md);
            padding: 1.25rem 1.5rem;
            margin-bottom: .75rem;
            border-left: 4px solid #d1d5db;
            transition: box-shadow .2s;
        }
        .req-card.pending  { border-left-color: #f59e0b; }
        .req-card.approved { border-left-color: #22c55e; }
        .req-card.rejected { border-left-color: #ef4444; }

        .req-top { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:.75rem; margin-bottom:.6rem; }
        .req-student { font-weight:700; font-size:1rem; }
        .req-student span { font-weight:400; font-size:.82rem; color:var(--text-secondary); margin-left:.35rem; }
        .req-meta { font-size:.82rem; color:var(--text-secondary); display:flex; flex-wrap:wrap; gap:.6rem; margin-bottom:.5rem; }
        .req-meta b { color:var(--text-primary); }
        .req-reason { font-size:.87rem; padding:.5rem .75rem; background:#f9fafb; border-radius:var(--radius-sm); margin-bottom:.75rem; }

        .badge { padding:.2rem .65rem; border-radius:1rem; font-size:.75rem; font-weight:700; }
        .badge-add  { background:#dbeafe; color:#1e40af; }
        .badge-drop { background:#fce7f3; color:#9d174d; }
        .badge-pending  { background:#fef3c7; color:#92400e; }
        .badge-approved { background:#dcfce7; color:#166534; }
        .badge-rejected { background:#fee2e2; color:#991b1b; }

        .action-row { display:flex; gap:.6rem; align-items:center; flex-wrap:wrap; }
        .note-input {
            flex:1; min-width:200px; padding:.45rem .75rem; border:1.5px solid #d1d5db;
            border-radius:var(--radius-md); font-size:.85rem; font-family:inherit;
        }
        .note-input:focus { outline:none; border-color:var(--primary-purple); }
        .btn-approve { background:#22c55e; color:white; border:none; padding:.42rem 1rem; border-radius:var(--radius-sm); font-weight:700; font-size:.82rem; cursor:pointer; }
        .btn-reject  { background:#ef4444; color:white; border:none; padding:.42rem 1rem; border-radius:var(--radius-sm); font-weight:700; font-size:.82rem; cursor:pointer; }
        .btn-approve:hover { background:#16a34a; }
        .btn-reject:hover  { background:#dc2626; }
        .btn-approve:disabled, .btn-reject:disabled { opacity:.5; cursor:not-allowed; }

        .reviewed-info { font-size:.78rem; color:var(--text-secondary); margin-top:.4rem; }
        .reviewed-note { margin-top:.3rem; font-size:.82rem; padding:.4rem .65rem; background:#f3f4f6; border-radius:var(--radius-sm); }

        .pending-banner {
            background: linear-gradient(135deg,#fef3c7,#fde68a);
            border-radius:var(--radius-md);
            padding:1rem 1.5rem;
            margin-bottom:1.25rem;
            display:flex;
            align-items:center;
            gap:1rem;
            font-weight:600;
            color:#92400e;
        }
        .pending-banner .big { font-size:1.8rem; font-weight:800; }

        .empty { text-align:center; color:var(--text-secondary); padding:3rem; }
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
                    <a href="add_drop_requests.php" class="nav-item active"><span class="nav-icon">🔄</span><span>Add/Drop Requests</span></a>
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
                    <a href="leave_requests.php" class="nav-item"><span class="nav-icon">🏖️</span><span>Leave Requests</span></a>
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
                    <h1>Add/Drop Requests</h1>
                <p class="page-subtitle">Review and process student subject add/drop requests</p>
            </div>
        </header>

        <div id="pendingBanner" style="display:none;"></div>

        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">All Requests</h2>
            </div>
            <div style="padding:1rem 1rem 0;">
                <div class="filter-row">
                    <select id="statusFilter" onchange="loadRequests()">
                        <option value="">All Requests</option>
                        <option value="pending" selected>Pending Only</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <select id="typeFilter" onchange="renderFiltered()">
                        <option value="">All Types</option>
                        <option value="add">Add Requests</option>
                        <option value="drop">Drop Requests</option>
                    </select>
                </div>
            </div>
            <div id="requestsList" style="padding:0 1rem 1rem;">Loading…</div>
        </div>
    </main>
</div>

<script>
let allRequests = [];

async function loadRequests() {
    const status = document.getElementById('statusFilter').value;
    const params = status ? `?status=${status}` : '';
    document.getElementById('requestsList').innerHTML = 'Loading…';

    try {
        const res  = await fetch(`../../api/registrar/get_add_drop_requests.php${params}`);
        const data = await res.json();

        // Pending banner
        const banner = document.getElementById('pendingBanner');
        if (data.pending_count > 0) {
            banner.style.display = 'flex';
            banner.innerHTML = `<div class="pending-banner"><span class="big">${data.pending_count}</span> pending add/drop request${data.pending_count !== 1 ? 's' : ''} awaiting your review.</div>`;
        } else {
            banner.style.display = 'none';
        }

        allRequests = data.requests || [];
        renderFiltered();
    } catch(e) {
        document.getElementById('requestsList').innerHTML = '<p class="empty">Failed to load requests.</p>';
    }
}

function renderFiltered() {
    const typeFilter = document.getElementById('typeFilter').value;
    const filtered   = typeFilter ? allRequests.filter(r => r.request_type === typeFilter) : allRequests;

    if (filtered.length === 0) {
        document.getElementById('requestsList').innerHTML = '<p class="empty">No requests found.</p>';
        return;
    }

    document.getElementById('requestsList').innerHTML = filtered.map(r => {
        const isPending = r.status === 'pending';
        const actionBlock = isPending ? `
            <div class="action-row">
                <input class="note-input" id="note-${r.id}" placeholder="Optional note to student…">
                <button class="btn-approve" onclick="review(${r.id},'approve')">✓ Approve</button>
                <button class="btn-reject"  onclick="review(${r.id},'reject')">✕ Reject</button>
            </div>` : `
            <div class="reviewed-info">
                Reviewed ${r.reviewed_at}${r.reviewed_by_name ? ' by ' + r.reviewed_by_name : ''}
                ${r.registrar_note ? `<div class="reviewed-note">Note: ${r.registrar_note}</div>` : ''}
            </div>`;

        return `
        <div class="req-card ${r.status}" id="card-${r.id}">
            <div class="req-top">
                <div>
                    <div class="req-student">${r.student_name} <span>${r.student_no || ''}</span></div>
                    <div class="req-meta">
                        ${r.section_name ? `<span>🏫 ${r.section_name} (${r.section_code})</span>` : ''}
                        <span>${r.course || ''} ${r.year_level || ''}</span>
                        <span>📅 ${r.created_at}</span>
                    </div>
                </div>
                <div style="display:flex;gap:.4rem;align-items:center;flex-shrink:0;">
                    <span class="badge badge-${r.request_type}">${r.request_type.toUpperCase()}</span>
                    <span class="badge badge-${r.status}">${r.status.charAt(0).toUpperCase()+r.status.slice(1)}</span>
                </div>
            </div>
            <div class="req-meta" style="margin-bottom:.4rem;">
                <b>Subject:</b> ${r.subject_code} — ${r.subject_name} (${r.units} units)
            </div>
            <div class="req-reason"><b>Reason:</b> ${r.reason}</div>
            ${actionBlock}
        </div>`;
    }).join('');
}

async function review(id, action) {
    const note = document.getElementById('note-' + id)?.value.trim() || '';
    const label = action === 'approve' ? 'approve' : 'reject';

    if (!confirm(`Are you sure you want to ${label} this request?`)) return;

    // Disable buttons
    const card = document.getElementById('card-' + id);
    card.querySelectorAll('button').forEach(b => b.disabled = true);

    try {
        const res  = await fetch('../../api/registrar/review_add_drop.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: id, action, note })
        });
        const data = await res.json();
        alert(data.message);
        if (data.success) loadRequests();
        else card.querySelectorAll('button').forEach(b => b.disabled = false);
    } catch(e) {
        alert('Error processing request.');
        card.querySelectorAll('button').forEach(b => b.disabled = false);
    }
}

loadRequests();
</script>

    <script>
        (function() {
            var sidebar = document.querySelector('.sidebar');
            var saved = sessionStorage.getItem('sidebarScroll');
            if (saved) sidebar.scrollTop = parseInt(saved);
            // Save scroll position before navigating away
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
    <script src="../../js/session-monitor.js"></script>
    <script src="../../js/apply-branding.js"></script>

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
