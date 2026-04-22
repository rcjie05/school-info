<?php
require_once '../../php/config.php';
requireRole('registrar');

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Applications - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
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
                    <a href="applications.php" class="nav-item active"><span class="nav-icon">📋</span><span>Applications</span></a>
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
                    <h1>Student Applications</h1>
                </div>
                <div class="header-actions">
                    <select id="statusFilter" class="form-select" style="width: 180px;" onchange="filterApplications()">
                        <option value="all">All Applications</option>
                        <option value="pending" selected>Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </header>
            
            <div class="content-card">
                <div class="card-header"><h2 class="card-title">Applications List</h2></div>
                <div class="table-container">
                    <table class="data-table">
                        <thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Course</th><th>Year</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="applicationsTable"><tr><td colspan="7" style="text-align:center;padding:2rem;">Loading...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .modal-content { max-width: 780px; }
        .enroll-section { margin-bottom: 1.5rem; }
        .enroll-section-title {
            font-size: 0.72rem; font-weight: 800; color: var(--primary-purple);
            text-transform: uppercase; letter-spacing: 0.7px;
            border-bottom: 2px solid #eef2f7; padding-bottom: 0.4rem; margin-bottom: 0.75rem;
        }
        .enroll-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.25rem 1rem; }
        .enroll-row { display: flex; flex-direction: column; padding: 0.45rem 0; border-bottom: 1px solid #f5f5f5; }
        .enroll-row:last-child { border-bottom: none; }
        .enroll-label { font-size: 0.72rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; }
        .enroll-value { font-size: 0.88rem; color: var(--text-primary); font-weight: 500; margin-top: 0.1rem; }
        .enroll-value.empty { color: #9ca3af; font-style: italic; font-weight: 400; }
        .student-header { display: flex; align-items: center; gap: 1rem; padding: 1.25rem; background: var(--background-main); border-radius: var(--radius-md); margin-bottom: 1.5rem; }
        .student-avatar { width: 60px; height: 60px; border-radius: 50%; background: var(--primary-purple); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 800; flex-shrink: 0; overflow: hidden; }
        .student-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .reject-area { margin-top: 0.75rem; display: none; }
        .reject-area textarea { width: 100%; padding: 0.75rem; border: 1.5px solid #e5e7eb; border-radius: var(--radius-md); font-family: inherit; font-size: 0.9rem; box-sizing: border-box; resize: vertical; min-height: 80px; }
    </style>

    <div class="modal" id="reviewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Review Application</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalBody" style="padding: 1.5rem; max-height: 75vh; overflow-y: auto;"></div>
            <div style="padding: 1rem 1.5rem; border-top: 1px solid #f0f0f0;" id="modalActions"></div>
        </div>
    </div>

    <script>
        let currentApplicationId = null;
        let allApplications = [];

        async function loadApplications() {
            const response = await fetch('../../api/registrar/get_all_applications.php');
            const data = await response.json();
            if (data.success) {
                allApplications = data.applications;
                filterApplications();
            }
        }

        function filterApplications() {
            const filter = document.getElementById('statusFilter').value;
            const filtered = filter === 'all' ? allApplications : allApplications.filter(a => a.status === filter);

            if (!filtered.length) {
                document.getElementById('applicationsTable').innerHTML =
                    '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-secondary);">No applications found</td></tr>';
                return;
            }
            document.getElementById('applicationsTable').innerHTML = filtered.map(app => {
                const sc = app.status === 'pending' ? 'status-pending' : app.status === 'approved' ? 'status-approved' : 'status-rejected';
                return `<tr>
                    <td>${esc(app.student_id||'—')}</td>
                    <td><strong>${esc(app.name)}</strong></td>
                    <td>${esc(app.email)}</td>
                    <td>${esc(app.course)}</td>
                    <td>${esc(app.year_level)}</td>
                    <td><span class="status-badge ${sc}">${app.status.toUpperCase()}</span></td>
                    <td>
                        <button class="btn btn-primary" style="padding:0.4rem 0.85rem;font-size:0.75rem;" onclick="reviewApplication(${app.id})">
                            ${app.status === 'pending' ? '📋 Review' : '👁 View'}
                        </button>
                    </td>
                </tr>`;
            }).join('');
        }

        async function reviewApplication(id) {
            currentApplicationId = id;
            document.getElementById('modalBody').innerHTML = '<p style="text-align:center;padding:2rem;color:var(--text-secondary);">Loading…</p>';
            document.getElementById('modalActions').innerHTML = '';
            document.getElementById('reviewModal').classList.add('active');

            const res  = await fetch('../../api/registrar/get_application.php?id=' + id);
            const data = await res.json();
            if (!data.success) {
                document.getElementById('modalBody').innerHTML = '<p style="color:red;padding:1rem;">Failed to load application.</p>';
                return;
            }
            const a = data.application;
            document.getElementById('modalTitle').textContent = 'Enrollment Application — ' + a.name;

            const v = val => val ? esc(val) : '<span class="enroll-value empty">Not provided</span>';
            const section = a.section_name ? `${a.section_name} (${a.section_code})` : null;

            document.getElementById('modalBody').innerHTML = `
                <div class="student-header">
                    <div class="student-avatar">
                        ${a.avatar_url ? `<img src="${esc(a.avatar_url)}" alt="">` : esc((a.name||'?')[0].toUpperCase())}
                    </div>
                    <div>
                        <div style="font-size:1.1rem;font-weight:800;">${esc(a.name)}</div>
                        <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:0.15rem;">${esc(a.course)} · ${esc(a.year_level)}</div>
                        <div style="font-size:0.78rem;color:var(--text-secondary);">Applied: ${a.created_at ? a.created_at.substring(0,10) : '—'}</div>
                    </div>
                    <div style="margin-left:auto;text-align:right;">
                        <span class="status-badge ${a.status === 'pending' ? 'status-pending' : a.status === 'approved' ? 'status-approved' : 'status-rejected'}" style="font-size:0.8rem;">
                            ${a.status.toUpperCase()}
                        </span>
                        ${a.enrollment_type ? `<div style="margin-top:0.4rem;font-size:0.75rem;background:#ede9fe;color:#5b21b6;padding:0.2rem 0.6rem;border-radius:999px;font-weight:700;">${esc(a.enrollment_type)}</div>` : ''}
                    </div>
                </div>

                <div class="enroll-section">
                    <div class="enroll-section-title">Personal Information</div>
                    <div class="enroll-grid">
                        ${er('LRN / Student ID', v(a.student_id))}
                        ${er('Email', v(a.email))}
                        ${er('Date of Birth', v(a.dob))}
                        ${er('Sex', v(a.sex))}
                        ${er('Civil Status', v(a.civil_status))}
                        ${er('Nationality', v(a.nationality))}
                        ${er('Place of Birth', v(a.place_of_birth))}
                        ${er('Mobile Number', v(a.mobile_number))}
                    </div>
                    ${a.home_address ? `<div class="enroll-row" style="margin-top:0.25rem;"><span class="enroll-label">Home Address</span><span class="enroll-value">${esc(a.home_address)}</span></div>` : ''}
                </div>

                <div class="enroll-section">
                    <div class="enroll-section-title">Academic Details</div>
                    <div class="enroll-grid">
                        ${er('Course', v(a.course))}
                        ${er('Year Level', v(a.year_level))}
                        ${er('Section', v(section))}
                        ${er('Semester', v(a.semester))}
                        ${er('School Year', v(a.school_year))}
                        ${er('Previous School', v(a.prev_school))}
                    </div>
                </div>

                <div class="enroll-section">
                    <div class="enroll-section-title">Family Background</div>
                    <div class="enroll-grid">
                        ${er("Father's Name", v(a.father_name))}
                        ${er("Mother's Name", v(a.mother_name))}
                        ${er('Guardian', v(a.guardian_name))}
                    </div>
                </div>

                <div class="enroll-section">
                    <div class="enroll-section-title">Emergency Contact</div>
                    <div class="enroll-grid">
                        ${er('Name', v(a.emergency_contact_name))}
                        ${er('Relationship', v(a.emergency_contact_relation))}
                        ${er('Phone', v(a.emergency_contact_phone))}
                    </div>
                </div>

                ${a.status === 'pending' ? `
                <div id="rejectArea" class="reject-area">
                    <label style="font-size:0.82rem;font-weight:700;display:block;margin-bottom:0.4rem;">Rejection Reason:</label>
                    <textarea id="rejectReason" placeholder="Explain why the application is being rejected…"></textarea>
                </div>` : ''}
            `;

            if (a.status === 'pending') {
                document.getElementById('modalActions').innerHTML = `
                    <div style="display:flex;gap:0.75rem;">
                        <button class="btn btn-primary" style="flex:1;" onclick="approveApplication()">✓ Approve</button>
                        <button class="btn btn-secondary" style="flex:1;border-color:#ef4444;color:#ef4444;" onclick="toggleReject()">✗ Reject</button>
                        <button id="confirmRejectBtn" class="btn" style="display:none;flex:1;background:#ef4444;color:white;" onclick="confirmReject()">Confirm Rejection</button>
                    </div>`;
            } else {
                document.getElementById('modalActions').innerHTML =
                    `<button class="btn btn-secondary" onclick="closeModal()" style="width:100%;">Close</button>`;
            }
        }

        function er(label, value) {
            return `<div class="enroll-row"><span class="enroll-label">${label}</span><span class="enroll-value">${value}</span></div>`;
        }
        function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        function toggleReject() {
            const area = document.getElementById('rejectArea');
            const btn  = document.getElementById('confirmRejectBtn');
            const show = area.style.display !== 'block';
            area.style.display = show ? 'block' : 'none';
            btn.style.display  = show ? 'inline-flex' : 'none';
        }

        async function approveApplication() {
            if (!confirm('Approve this enrollment application?')) return;
            const res  = await fetch('../../api/registrar/approve_application.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ application_id: currentApplicationId })
            });
            const data = await res.json();
            if (data.success) { closeModal(); loadApplications(); }
            else alert('Error: ' + data.message);
        }

        async function confirmReject() {
            const reason = document.getElementById('rejectReason').value.trim();
            if (!reason) { alert('Please enter a rejection reason.'); return; }
            const res  = await fetch('../../api/registrar/reject_application.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ application_id: currentApplicationId, reason })
            });
            const data = await res.json();
            if (data.success) { closeModal(); loadApplications(); }
            else alert('Error: ' + data.message);
        }

        function closeModal() {
            document.getElementById('reviewModal').classList.remove('active');
            currentApplicationId = null;
        }

        loadApplications();
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
