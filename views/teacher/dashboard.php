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
requireRole('teacher');
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
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
</head>
<body>
    <div class="page-wrapper">
        <!-- Sidebar -->
                <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <img src="../../images/logo2.jpg" alt="SCC Logo" id="sidebarLogoImg" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-md);">
                </div>
                <div class="logo-text">
                    <span id="sidebarSchoolName"><?= htmlspecialchars($school_name) ?></span>
                    <span>Teacher Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item active"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="schedule.php" class="nav-item"><span class="nav-icon">📅</span><span>My Schedule</span></a>
                    <a href="classes.php" class="nav-item"><span class="nav-icon">📚</span><span>My Classes</span></a>
                    <a href="specialties.php" class="nav-item"><span class="nav-icon">🎯</span><span>My Subjects</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Resources</div>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
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
                    <h1>Teacher Dashboard</h1>
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
                            <div class="user-name" id="userName">Teacher Name</div>
                            <div class="user-role">Teacher</div>
                        </div>
                    </div>
                    </a>
                </div>
            </header>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card purple">
                    <div class="stat-header">
                        <div class="stat-icon">📚</div>
                    </div>
                    <div class="stat-label">Total Classes</div>
                    <div class="stat-value" id="totalClasses">0</div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-header">
                        <div class="stat-icon">👥</div>
                    </div>
                    <div class="stat-label">Total Students</div>
                    <div class="stat-value" id="totalStudents">0</div>
                </div>
                
                <div class="stat-card yellow">
                    <div class="stat-header">
                        <div class="stat-icon">⏰</div>
                    </div>
                    <div class="stat-label">Today's Classes</div>
                    <div class="stat-value" id="todayClasses">0</div>
                </div>
                
                <div class="stat-card pink">
                    <div class="stat-header">
                        <div class="stat-icon">🏢</div>
                    </div>
                    <div class="stat-label">Office</div>
                    <div class="stat-value" id="office" style="font-size: 1.25rem;">—</div>
                </div>
            </div>
            
            <!-- Today's Schedule -->
            <div class="content-card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">Today's Schedule</h2>
                    <a href="schedule.php" class="view-all-btn">View Full Schedule</a>
                </div>
                <div id="todaySchedule">
                    <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                        Loading schedule...
                    </p>
                </div>
            </div>
            
            <!-- Content Grid -->
            <div class="content-grid" style="margin-top: 2rem;">
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">My Classes</h2>
                        <a href="classes.php" class="view-all-btn">View All</a>
                    </div>
                    <div id="classesList">
                        <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                            Loading classes...
                        </p>
                    </div>
                </div>
                
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">Announcements</h2>
                    </div>
                    <div id="announcements">
                        <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                            Loading announcements...
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        async function loadDashboardData() {
            try {
                const response = await fetch('../../api/teacher/get_dashboard_data.php');
                const data = await response.json();
                
                if (data.success) {
                    // Update user info
                    document.getElementById('userName').textContent = data.user.name;
                    const avatarEl = document.getElementById('userAvatar');
                    if (data.user && data.user.avatar_url) {
                        avatarEl.innerHTML = `<img src="${data.user.avatar_url}?t=${Date.now()}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">`;
                    } else {
                        avatarEl.textContent = (data.user.name || 'T').charAt(0).toUpperCase();
                    }
                    
                    // Update stats
                    document.getElementById('totalClasses').textContent = data.stats.total_classes;
                    document.getElementById('totalStudents').textContent = data.stats.total_students;
                    document.getElementById('todayClasses').textContent = data.stats.today_classes;
                    document.getElementById('office').textContent = data.stats.office;
                    
                    // Load today's schedule
                    const scheduleDiv = document.getElementById('todaySchedule');
                    if (data.today_schedule.length > 0) {
                        scheduleDiv.innerHTML = '<div style="display: flex; flex-direction: column; gap: 1rem;">' +
                            data.today_schedule.map(item => `
                                <div style="padding: 1rem; background: var(--background-main); border-radius: var(--radius-md); border-left: 4px solid var(--primary-purple);">
                                    <div style="display: flex; justify-content: space-between;">
                                        <div>
                                            <div style="font-weight: 700;">${item.subject_code} - ${item.subject_name}</div>
                                            <div style="font-size: 0.875rem; color: var(--text-secondary);">Section ${item.section} • ${item.student_count} students</div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-weight: 600; color: var(--primary-purple);">${item.time}</div>
                                            <div style="font-size: 0.875rem; color: var(--text-secondary);">${item.room}</div>
                                        </div>
                                    </div>
                                </div>
                            `).join('') +
                            '</div>';
                    } else {
                        scheduleDiv.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No classes scheduled for today</p>';
                    }
                    
                    // Load classes list
                    const classesDiv = document.getElementById('classesList');
                    if (data.classes.length > 0) {
                        classesDiv.innerHTML = '<div style="display: flex; flex-direction: column; gap: 0.75rem;">' +
                            data.classes.slice(0, 5).map(cls => `
                                <div style="padding: 0.75rem; background: var(--background-main); border-radius: var(--radius-sm);">
                                    <div style="font-weight: 600;">${cls.subject_code} - ${cls.subject_name}</div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">Section ${cls.section} • ${cls.student_count} students • ${cls.units} units</div>
                                </div>
                            `).join('') +
                            '</div>';
                    } else {
                        classesDiv.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No classes assigned</p>';
                    }
                    
                    // Load announcements
                    const announcementsDiv = document.getElementById('announcements');
                    if (data.announcements.length > 0) {
                        announcementsDiv.innerHTML = '<div style="display: flex; flex-direction: column; gap: 1rem;">' +
                            data.announcements.map(ann => `
                                <div style="padding: 1rem; background: var(--background-main); border-radius: var(--radius-md);">
                                    <div style="font-weight: 600; margin-bottom: 0.5rem;">${ann.title}</div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">${ann.content}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-tertiary);">${ann.date}</div>
                                </div>
                            `).join('') +
                            '</div>';
                    } else {
                        announcementsDiv.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No announcements</p>';
                    }
                } else {
                    console.error('Failed to load dashboard data:', data.message);
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }
        
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
      <a href="schedule.php" class="mobile-nav-item" data-page="schedule">
        <span class="mobile-nav-icon">📅</span><span>Schedule</span>
      </a>
      <a href="classes.php" class="mobile-nav-item" data-page="classes">
        <span class="mobile-nav-icon">📚</span><span>Classes</span>
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
