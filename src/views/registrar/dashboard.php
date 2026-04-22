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
requireRole('registrar');
$_avatar_conn = getDBConnection();
$_col = $_avatar_conn->query("SHOW COLUMNS FROM `users` LIKE 'avatar_url'");
if ($_col && $_col->num_rows === 0) {
    $_avatar_conn->query("ALTER TABLE `users` ADD COLUMN `avatar_url` VARCHAR(500) NULL DEFAULT NULL AFTER `status`");
}
$_avatar_stmt = $_avatar_conn->prepare("SELECT name, avatar_url FROM users WHERE id = ?");
$_avatar_stmt->bind_param("i", $_SESSION['user_id']);
$_avatar_stmt->execute();
$_avatar_user = $_avatar_stmt->get_result()->fetch_assoc();
$_avatar_stmt->close();
$_avatar_conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
</head>
<body>
    <div class="page-wrapper">
        <!-- Sidebar -->
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
                    <a href="dashboard.php" class="nav-item active"><span class="nav-icon">📊</span><span>Dashboard</span></a>
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
                    <a href="leave_requests.php" class="nav-item"><span class="nav-icon">🏖️</span><span>Leave Requests</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Account</div>
                    <a href="profile.php" class="nav-item"><span class="nav-icon">👤</span><span>My Profile</span></a>
                    <a href="../../php/logout.php" class="nav-item"><span class="nav-icon">🚪</span><span>Logout</span></a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="page-header">
                <div class="header-title">
                    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button>
                    <h1>Registrar Dashboard</h1>
                </div>
                <div class="header-actions">
                    <div class="school-year-badge">
                        <span>📚</span>
                        <span>School Year <?= htmlspecialchars($current_school_year) ?></span>
                    </div>
                    <a href="profile.php" style="text-decoration:none;">
                    <div class="user-profile">
                        <div class="user-avatar" id="userAvatar"><?php
$_avatar_url = !empty($_avatar_user['avatar_url']) ? htmlspecialchars(getAvatarUrl($_avatar_user['avatar_url'])) : null;
$_initials   = strtoupper(substr($_avatar_user['name'] ?? '?', 0, 1));
?>
<?php if ($_avatar_url): ?>
<img src="<?= $_avatar_url ?>?t=<?= time() ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" alt="">
<?php else: ?>
<?= $_initials ?>
<?php endif; ?></div>
                        <div class="user-info">
                            <div class="user-name" id="userName">Registrar</div>
                            <div class="user-role">Registrar</div>
                        </div>
                    </div>
                    </a>
                </div>
            </header>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card purple">
                    <div class="stat-header">
                        <div class="stat-icon">⏳</div>
                    </div>
                    <div class="stat-label">Pending Applications</div>
                    <div class="stat-value" id="pendingCount">0</div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-header">
                        <div class="stat-icon">✅</div>
                    </div>
                    <div class="stat-label">Approved Today</div>
                    <div class="stat-value" id="approvedToday">0</div>
                </div>
                
                <div class="stat-card yellow">
                    <div class="stat-header">
                        <div class="stat-icon">👥</div>
                    </div>
                    <div class="stat-label">Total Students</div>
                    <div class="stat-value" id="totalStudents">0</div>
                </div>
                
                <div class="stat-card pink">
                    <div class="stat-header">
                        <div class="stat-icon">🎓</div>
                    </div>
                    <div class="stat-label">Fully Enrolled</div>
                    <div class="stat-value" id="enrolledCount">0</div>
                </div>
            </div>
            
            <!-- Pending Applications Table -->
            <div class="content-card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">Pending Student Applications</h2>
                    <a href="applications.php" class="view-all-btn">View All</a>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Year Level</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="applicationsTable">
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem;">Loading applications...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="content-grid" style="margin-top: 2rem;">
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">Recent Activity</h2>
                    </div>
                    <div id="recentActivity">
                        <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                            Loading activity...
                        </p>
                    </div>
                </div>
                
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">Quick Actions</h2>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <a href="applications.php" class="btn btn-primary" style="justify-content: center;">
                            Review Applications
                        </a>
                        <a href="manage_loads.php" class="btn btn-secondary" style="justify-content: center;">
                            Assign Study Loads
                        </a>
                        <a href="manage_schedules.php" class="btn btn-secondary" style="justify-content: center;">
                            Create Schedules
                        </a>
                        <a href="announcements.php" class="btn btn-secondary" style="justify-content: center;">
                            Post Announcement
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Application Review Modal -->
    <div class="modal" id="reviewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Review Application</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalBody">
                <p>Loading application details...</p>
            </div>
        </div>
    </div>

    <script>
        let currentApplicationId = null;
        
        async function loadDashboardData() {
            try {
                const response = await fetch('../../api/registrar/get_dashboard_data.php');
                const data = await response.json();
                
                if (data.success) {
                    // Update user avatar
                    if (data.user) {
                        document.getElementById('userName').textContent = data.user.name;
                        const avatarEl = document.getElementById('userAvatar');
                        if (data.user.avatar_url) {
                            avatarEl.innerHTML = `<img src="${data.user.avatar_url}?t=${Date.now()}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">`;
                        } else {
                            avatarEl.textContent = (data.user.name || 'R').charAt(0).toUpperCase();
                        }
                    }
                    // Update stats
                    document.getElementById('pendingCount').textContent = data.stats.pending_count;
                    document.getElementById('approvedToday').textContent = data.stats.approved_today;
                    document.getElementById('totalStudents').textContent = data.stats.total_students;
                    document.getElementById('enrolledCount').textContent = data.stats.enrolled_count;
                    
                    // Load applications table
                    loadApplicationsTable(data.applications);
                    
                    // Load recent activity
                    loadRecentActivity(data.recent_activity);
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }
        
        function loadApplicationsTable(applications) {
            const tbody = document.getElementById('applicationsTable');
            
            if (!applications || applications.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No pending applications</td></tr>';
                return;
            }
            
            let html = '';
            applications.forEach(app => {
                html += `
                    <tr>
                        <td>${app.student_id}</td>
                        <td><strong>${app.name}</strong></td>
                        <td>${app.email}</td>
                        <td>${app.course}</td>
                        <td>${app.year_level}</td>
                        <td><span class="status-badge status-pending">${app.status}</span></td>
                        <td>
                            <button class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.75rem;" onclick="reviewApplication(${app.id})">
                                Review
                            </button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
        
        function loadRecentActivity(activities) {
            const container = document.getElementById('recentActivity');
            
            if (!activities || activities.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No recent activity</p>';
                return;
            }
            
            let html = '<div style="display: flex; flex-direction: column; gap: 1rem;">';
            activities.forEach(activity => {
                html += `
                    <div style="padding: 1rem; background: var(--background-main); border-radius: var(--radius-md);">
                        <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">${activity.action}</div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary);">${activity.date}</div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }
        
        async function reviewApplication(applicationId) {
            currentApplicationId = applicationId;
            
            try {
                const response = await fetch(`../../api/registrar/get_application.php?id=${applicationId}`);
                const data = await response.json();
                
                if (data.success) {
                    const app = data.application;
                    document.getElementById('modalBody').innerHTML = `
                        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                            <div>
                                <h3 style="margin-bottom: 1rem;">Student Information</h3>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <strong>Name:</strong> ${app.name}
                                    </div>
                                    <div>
                                        <strong>Student ID:</strong> ${app.student_id}
                                    </div>
                                    <div>
                                        <strong>Email:</strong> ${app.email}
                                    </div>
                                    <div>
                                        <strong>Course:</strong> ${app.course}
                                    </div>
                                    <div>
                                        <strong>Year Level:</strong> ${app.year_level}
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 1rem;">
                                <button class="btn btn-primary" style="flex: 1;" onclick="approveApplication()">
                                    ✓ Approve
                                </button>
                                <button class="btn btn-secondary" style="flex: 1;" onclick="rejectApplication()">
                                    ✗ Reject
                                </button>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('reviewModal').classList.add('active');
                }
            } catch (error) {
                console.error('Error loading application:', error);
            }
        }
        
        async function approveApplication() {
            if (!currentApplicationId) return;
            
            try {
                const response = await fetch('../../api/registrar/approve_application.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ application_id: currentApplicationId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Application approved successfully!');
                    closeModal();
                    loadDashboardData();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error approving application:', error);
                alert('An error occurred');
            }
        }
        
        async function rejectApplication() {
            const reason = prompt('Please enter rejection reason:');
            if (!reason || !currentApplicationId) return;
            
            try {
                const response = await fetch('../../api/registrar/reject_application.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        application_id: currentApplicationId,
                        reason: reason
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Application rejected');
                    closeModal();
                    loadDashboardData();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error rejecting application:', error);
                alert('An error occurred');
            }
        }
        
        function closeModal() {
            document.getElementById('reviewModal').classList.remove('active');
            currentApplicationId = null;
        }
        
        // Initialize
        loadDashboardData();
    </script>

    <script>
        (function() {
            var sidebar = document.querySelector('.sidebar');
            // Always reset scroll to top on dashboard (entry point after login)
            sessionStorage.removeItem('sidebarScroll');
            sidebar.scrollTop = 0;
            // Save scroll position before navigating away
            document.querySelectorAll('.nav-item').forEach(function(link) {
                link.addEventListener('click', function() {
                    sessionStorage.setItem('sidebarScroll', sidebar.scrollTop);
                });
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
