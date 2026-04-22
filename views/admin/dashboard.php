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
requireRole('admin');
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
    <link rel="manifest" href="../../manifest.json">
    <meta name="theme-color" content="#1E3352">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($school_name) ?> Portal">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <title>Admin Dashboard - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <link rel="stylesheet" href="../../css/enhancements.css">
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
                    <span>Admin Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item active"><span class="nav-icon">📊</span><span>Dashboard</span></a>
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
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="page-header">
                <div class="header-title">
                    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button>
                    <h1>System Administration</h1>
                </div>
                <div class="header-actions">
                    <div class="school-year-badge">
                        <span>⚙️</span>
                        <span>Admin Panel</span>
                    </div>
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
                            <div class="user-name" id="userName">Administrator</div>
                            <div class="user-role">Admin</div>
                        </div>
                    </div>
                    </a>
                </div>
            </header>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card purple">
                    <div class="stat-header">
                        <div class="stat-icon">👥</div>
                    </div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value" id="totalUsers">0</div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-header">
                        <div class="stat-icon">🎓</div>
                    </div>
                    <div class="stat-label">Students</div>
                    <div class="stat-value" id="totalStudents">0</div>
                </div>
                
                <div class="stat-card yellow">
                    <div class="stat-header">
                        <div class="stat-icon">👨‍🏫</div>
                    </div>
                    <div class="stat-label">Teachers</div>
                    <div class="stat-value" id="totalTeachers">0</div>
                </div>
                
                <div class="stat-card pink">
                    <div class="stat-header">
                        <div class="stat-icon">🏢</div>
                    </div>
                    <div class="stat-label">Buildings</div>
                    <div class="stat-value" id="totalBuildings">0</div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="content-card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">Quick Actions</h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; padding: 1rem;">
                    <button onclick="openModal('addUserModal')" class="btn btn-primary" style="justify-content: center; padding: 1.5rem; cursor: pointer;">
                        ➕ Add New User
                    </button>
                    <button onclick="openModal('addBuildingModal')" class="btn btn-primary" style="justify-content: center; padding: 1.5rem; cursor: pointer;">
                        🏢 Add Building
                    </button>
                    <button onclick="openModal('postAnnouncementModal')" class="btn btn-primary" style="justify-content: center; padding: 1.5rem; cursor: pointer;">
                        📢 Post Announcement
                    </button>
                    <a href="settings.php" class="btn btn-primary" style="justify-content: center; padding: 1.5rem; text-decoration: none;">
                        ⚙️ System Settings
                    </a>
                </div>
            </div>

            <!-- ── MODAL: Add New User ─────────────────────────────────── -->
            <div id="addUserModal" class="qa-modal-overlay" onclick="closeModalOutside(event,'addUserModal')">
                <div class="qa-modal">
                    <div class="qa-modal-header">
                        <h3>➕ Add New User</h3>
                        <button class="qa-modal-close" onclick="closeModal('addUserModal')">✕</button>
                    </div>
                    <div class="qa-modal-body">
                        <div id="addUserMsg" class="qa-message" style="display:none;"></div>
                        <div class="qa-form-grid">
                            <div class="qa-field">
                                <label>Full Name *</label>
                                <input type="text" id="au_name" placeholder="e.g. Juan dela Cruz">
                            </div>
                            <div class="qa-field">
                                <label>Email *</label>
                                <input type="email" id="au_email" placeholder="email@example.com">
                            </div>
                            <div class="qa-field">
                                <label>Password *</label>
                                <input type="password" id="au_password" placeholder="Min. 8 characters">
                            </div>
                            <div class="qa-field">
                                <label>Role *</label>
                                <select id="au_role" onchange="toggleUserRoleFields()">
                                    <option value="">-- Select Role --</option>
                                    <option value="student">Student</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="registrar">Registrar</option>
                                    <option value="hr">HR</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="qa-field">
                                <label>Status</label>
                                <select id="au_status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <!-- Student-specific fields -->
                        <div id="au_student_fields" style="display:none;" class="qa-form-grid qa-role-fields">
                            <div class="qa-field">
                                <label>Student ID</label>
                                <input type="text" id="au_student_id" placeholder="e.g. 2024-0001">
                            </div>
                            <div class="qa-field">
                                <label>Course</label>
                                <input type="text" id="au_course" placeholder="e.g. BSIT">
                            </div>
                            <div class="qa-field">
                                <label>Year Level</label>
                                <select id="au_year_level">
                                    <option value="">-- Select Year --</option>
                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                    <option value="4th Year">4th Year</option>
                                </select>
                            </div>
                        </div>
                        <!-- Teacher-specific fields -->
                        <div id="au_teacher_fields" style="display:none;" class="qa-form-grid qa-role-fields">
                            <div class="qa-field">
                                <label>Department</label>
                                <select id="au_department" onchange="autofillOfficeLocation()">
                                    <option value="">-- Loading departments... --</option>
                                </select>
                            </div>
                            <div class="qa-field">
                                <label>Office Location</label>
                                <select id="au_office_location" onchange="handleOfficeLocationChange()">
                                    <option value="">-- Select Department first --</option>
                                </select>
                                <input type="text" id="au_office_location_custom" placeholder="Type custom location..." style="display:none; margin-top:0.4rem;">
                            </div>
                            <div class="qa-field qa-field-full">
                                <label>Office Hours</label>
                                <input type="text" id="au_office_hours" placeholder="e.g. M/W/F 8AM-12PM">
                            </div>
                        </div>
                    </div>
                    <div class="qa-modal-footer">
                        <button onclick="closeModal('addUserModal')" class="btn btn-secondary">Cancel</button>
                        <button onclick="submitAddUser()" class="btn btn-primary" id="addUserBtn">Create User</button>
                    </div>
                </div>
            </div>

            <!-- ── MODAL: Add Building ─────────────────────────────────── -->
            <div id="addBuildingModal" class="qa-modal-overlay" onclick="closeModalOutside(event,'addBuildingModal')">
                <div class="qa-modal">
                    <div class="qa-modal-header">
                        <h3>🏢 Add Building</h3>
                        <button class="qa-modal-close" onclick="closeModal('addBuildingModal')">✕</button>
                    </div>
                    <div class="qa-modal-body">
                        <div id="addBuildingMsg" class="qa-message" style="display:none;"></div>
                        <div class="qa-form-grid">
                            <div class="qa-field">
                                <label>Building Name *</label>
                                <input type="text" id="ab_name" placeholder="e.g. Main Building">
                            </div>
                            <div class="qa-field">
                                <label>Building Code *</label>
                                <input type="text" id="ab_code" placeholder="e.g. MB" style="text-transform:uppercase;">
                            </div>
                            <div class="qa-field">
                                <label>Location</label>
                                <input type="text" id="ab_location" placeholder="e.g. North Campus">
                            </div>
                            <div class="qa-field qa-field-full">
                                <label>Description</label>
                                <textarea id="ab_description" rows="3" placeholder="Brief description of this building..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="qa-modal-footer">
                        <button onclick="closeModal('addBuildingModal')" class="btn btn-secondary">Cancel</button>
                        <button onclick="submitAddBuilding()" class="btn btn-primary" id="addBuildingBtn">Add Building</button>
                    </div>
                </div>
            </div>

            <!-- ── MODAL: Post Announcement ───────────────────────────── -->
            <div id="postAnnouncementModal" class="qa-modal-overlay" onclick="closeModalOutside(event,'postAnnouncementModal')">
                <div class="qa-modal">
                    <div class="qa-modal-header">
                        <h3>📢 Post Announcement</h3>
                        <button class="qa-modal-close" onclick="closeModal('postAnnouncementModal')">✕</button>
                    </div>
                    <div class="qa-modal-body">
                        <div id="postAnnouncementMsg" class="qa-message" style="display:none;"></div>
                        <div class="qa-form-grid">
                            <div class="qa-field qa-field-full">
                                <label>Title *</label>
                                <input type="text" id="pa_title" placeholder="Announcement title...">
                            </div>
                            <div class="qa-field qa-field-full">
                                <label>Content *</label>
                                <textarea id="pa_content" rows="5" placeholder="Write your announcement here..."></textarea>
                            </div>
                            <div class="qa-field">
                                <label>Target Audience</label>
                                <select id="pa_audience">
                                    <option value="all">Everyone</option>
                                    <option value="students">Students Only</option>
                                    <option value="teachers">Teachers Only</option>
                                    <option value="admin">Admin Only</option>
                                </select>
                            </div>
                            <div class="qa-field">
                                <label>Priority</label>
                                <select id="pa_priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="qa-modal-footer">
                        <button onclick="closeModal('postAnnouncementModal')" class="btn btn-secondary">Cancel</button>
                        <button onclick="submitPostAnnouncement()" class="btn btn-primary" id="postAnnouncementBtn">Post Now</button>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="content-grid" style="margin-top: 2rem;">
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">Recent System Activity</h2>
                        <a href="audit_logs.php" class="view-all-btn">View All</a>
                    </div>
                    <div id="recentActivity">
                        <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                            Loading activity...
                        </p>
                    </div>
                </div>
                
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">System Health</h2>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Database Status</span>
                            <span class="status-badge status-approved">Online</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Server Status</span>
                            <span class="status-badge status-approved">Healthy</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Last Backup</span>
                            <span style="font-size: 0.875rem; color: var(--text-secondary);">Today, 2:00 AM</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        async function loadDashboardData() {
            try {
                const response = await fetch('../../api/admin/get_dashboard_data.php');
                const data = await response.json();
                
                if (data.success) {
                    sccAnimateCount(document.getElementById('totalUsers'), data.stats.total_users);
                    if (data.user) {
                        document.getElementById('userName').textContent = data.user.name;
                        const avatarEl = document.getElementById('userAvatar');
                        if (data.user.avatar_url) {
                            avatarEl.innerHTML = `<img src="${data.user.avatar_url}?t=${Date.now()}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">`;
                        } else {
                            avatarEl.textContent = (data.user.name || 'A').charAt(0).toUpperCase();
                        }
                    }
                    sccAnimateCount(document.getElementById('totalStudents'), data.stats.total_students);
                    sccAnimateCount(document.getElementById('totalTeachers'), data.stats.total_teachers);
                    sccAnimateCount(document.getElementById('totalBuildings'), data.stats.total_buildings);
                    
                    loadRecentActivity(data.recent_activity);
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
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
                        <div style="font-size: 0.875rem; color: var(--text-secondary);">${activity.user_name}</div>
                        <div style="font-size: 0.75rem; color: var(--text-light);">${activity.date}</div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }
        
        loadDashboardData();
    </script>

    <!-- ── Quick Action Modal Styles ───────────────────────────────── -->
    <style>
        .qa-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .qa-modal-overlay.open {
            display: flex;
        }
        .qa-modal {
            background: var(--background-card, #fff);
            border-radius: var(--radius-lg, 12px);
            width: 100%;
            max-width: 560px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }
        .qa-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color, #e5e7eb);
        }
        .qa-modal-header h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }
        .qa-modal-close {
            background: none;
            border: none;
            font-size: 1.1rem;
            cursor: pointer;
            color: var(--text-secondary);
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm, 6px);
            transition: background 0.15s;
        }
        .qa-modal-close:hover { background: var(--background-main, #f3f4f6); }
        .qa-modal-body {
            padding: 1.5rem;
            flex: 1;
        }
        .qa-modal-footer {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color, #e5e7eb);
        }
        .qa-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        .qa-role-fields { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color, #e5e7eb); }
        .qa-field { display: flex; flex-direction: column; gap: 0.4rem; }
        .qa-field-full { grid-column: 1 / -1; }
        .qa-field label { font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.04em; }
        .qa-field input, .qa-field select, .qa-field textarea {
            padding: 0.6rem 0.875rem;
            border: 1px solid var(--border-color, #d1d5db);
            border-radius: var(--radius-md, 8px);
            background: var(--background-main, #f9fafb);
            color: var(--text-primary);
            font-size: 0.9rem;
            font-family: inherit;
            width: 100%;
            box-sizing: border-box;
            transition: border-color 0.15s;
        }
        .qa-field input:focus, .qa-field select:focus, .qa-field textarea:focus {
            outline: none;
            border-color: var(--primary-color, #1E3352);
            background: var(--background-card, #fff);
        }
        .qa-field textarea { resize: vertical; }
        .qa-message {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md, 8px);
            margin-bottom: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .qa-message.success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .qa-message.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        @media (max-width: 500px) {
            .qa-form-grid { grid-template-columns: 1fr; }
        }
    </style>

    <!-- ── Quick Action Modal Scripts ──────────────────────────────── -->
    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
            document.body.style.overflow = '';
        }
        function closeModalOutside(e, id) {
            if (e.target.id === id) closeModal(id);
        }
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') document.querySelectorAll('.qa-modal-overlay.open').forEach(m => { m.classList.remove('open'); document.body.style.overflow = ''; });
        });

        function showMsg(elId, msg, type) {
            const el = document.getElementById(elId);
            el.textContent = msg;
            el.className = 'qa-message ' + type;
            el.style.display = 'block';
        }
        function hideMsg(elId) { document.getElementById(elId).style.display = 'none'; }

        // ── Department loader ────────────────────────────────────────
        let _departments = []; // cache

        async function loadDepartments() {
            if (_departments.length > 0) return; // already loaded
            try {
                const res  = await fetch('../../api/admin/get_departments.php');
                const data = await res.json();
                if (data.success) {
                    _departments = data.departments;
                    const sel = document.getElementById('au_department');
                    sel.innerHTML = '<option value="">-- Select Department --</option>';
                    _departments.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.department_name;
                        opt.dataset.location = d.office_location || '';
                        opt.textContent = d.department_name + (d.department_code ? ' (' + d.department_code + ')' : '');
                        sel.appendChild(opt);
                    });
                    // also populate office location select with all unique locations
                    rebuildOfficeLocationSelect('');
                }
            } catch(e) {
                document.getElementById('au_department').innerHTML = '<option value="">-- Failed to load --</option>';
            }
        }

        function rebuildOfficeLocationSelect(autoValue) {
            const sel = document.getElementById('au_office_location');
            sel.innerHTML = '<option value="">-- Select Location --</option>';
            // Collect unique locations from all departments
            const locs = [...new Set(_departments.map(d => d.office_location).filter(Boolean))];
            locs.forEach(loc => {
                const opt = document.createElement('option');
                opt.value = loc;
                opt.textContent = loc;
                if (loc === autoValue) opt.selected = true;
                sel.appendChild(opt);
            });
            // Allow custom entry fallback
            const custom = document.createElement('option');
            custom.value = '__custom__';
            custom.textContent = '✏️ Type custom location...';
            sel.appendChild(custom);
        }

        function autofillOfficeLocation() {
            const deptSel  = document.getElementById('au_department');
            const selected = deptSel.options[deptSel.selectedIndex];
            const loc      = selected ? (selected.dataset.location || '') : '';
            rebuildOfficeLocationSelect(loc);
            // Show custom input if no location found
            document.getElementById('au_office_location_custom').style.display = 'none';
        }

        function handleOfficeLocationChange() {
            const sel = document.getElementById('au_office_location');
            const custom = document.getElementById('au_office_location_custom');
            custom.style.display = (sel.value === '__custom__') ? 'block' : 'none';
            if (sel.value === '__custom__') custom.focus();
        }

        function getOfficeLocationValue() {
            const sel = document.getElementById('au_office_location');
            if (sel.value === '__custom__') return document.getElementById('au_office_location_custom').value.trim();
            return sel.value;
        }

        function toggleUserRoleFields() {
            const role = document.getElementById('au_role').value;
            document.getElementById('au_student_fields').style.display = (role === 'student') ? 'grid' : 'none';
            document.getElementById('au_teacher_fields').style.display = (role === 'teacher') ? 'grid' : 'none';
            if (role === 'teacher') loadDepartments();
        }

        async function submitAddUser() {
            hideMsg('addUserMsg');
            const name     = document.getElementById('au_name').value.trim();
            const email    = document.getElementById('au_email').value.trim();
            const password = document.getElementById('au_password').value;
            const role     = document.getElementById('au_role').value;
            const status   = document.getElementById('au_status').value;

            if (!name || !email || !password || !role) {
                showMsg('addUserMsg', 'Please fill in all required fields.', 'error'); return;
            }
            if (password.length < 8) {
                showMsg('addUserMsg', 'Password must be at least 8 characters.', 'error'); return;
            }

            const payload = { name, email, password, role, status };
            if (role === 'student') {
                payload.student_id  = document.getElementById('au_student_id').value.trim();
                payload.course      = document.getElementById('au_course').value.trim();
                payload.year_level  = document.getElementById('au_year_level').value;
            } else if (role === 'teacher') {
                payload.department      = document.getElementById('au_department').value.trim();
                payload.office_location = getOfficeLocationValue();
                payload.office_hours    = document.getElementById('au_office_hours').value.trim();
            }

            const btn = document.getElementById('addUserBtn');
            btn.disabled = true; btn.textContent = 'Creating...';
            try {
                const res  = await fetch('../../api/admin/add_user.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) {
                    // Reset form
                    ['au_name','au_email','au_password','au_student_id','au_course','au_office_hours'].forEach(id => { const el = document.getElementById(id); if(el) el.value = ''; });
                    document.getElementById('au_role').value = '';
                    document.getElementById('au_year_level') && (document.getElementById('au_year_level').value = '');
                    document.getElementById('au_department').value = '';
                    rebuildOfficeLocationSelect('');
                    document.getElementById('au_office_location_custom').style.display = 'none';
                    document.getElementById('au_office_location_custom').value = '';
                    toggleUserRoleFields();
                    hideMsg('addUserMsg');
                    loadDashboardData(); // refresh stats
                    closeModal('addUserModal');
                } else {
                    showMsg('addUserMsg', '❌ ' + (data.message || 'Failed to create user.'), 'error');
                }
            } catch(err) {
                showMsg('addUserMsg', '❌ Network error. Please try again.', 'error');
            }
            btn.disabled = false; btn.textContent = 'Create User';
        }

        async function submitAddBuilding() {
            hideMsg('addBuildingMsg');
            const building_name = document.getElementById('ab_name').value.trim();
            const building_code = document.getElementById('ab_code').value.trim().toUpperCase();
            const location      = document.getElementById('ab_location').value.trim();
            const description   = document.getElementById('ab_description').value.trim();

            if (!building_name || !building_code) {
                showMsg('addBuildingMsg', 'Building name and code are required.', 'error'); return;
            }

            const btn = document.getElementById('addBuildingBtn');
            btn.disabled = true; btn.textContent = 'Saving...';
            try {
                const res  = await fetch('../../api/admin/save_building.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ building_name, building_code, location, description }) });
                const data = await res.json();
                if (data.success) {
                    showMsg('addBuildingMsg', '✅ Building added successfully!', 'success');
                    document.getElementById('ab_name').value = '';
                    document.getElementById('ab_code').value = '';
                    document.getElementById('ab_location').value = '';
                    document.getElementById('ab_description').value = '';
                    loadDashboardData();
                } else {
                    showMsg('addBuildingMsg', '❌ ' + (data.message || 'Failed to add building.'), 'error');
                }
            } catch(err) {
                showMsg('addBuildingMsg', '❌ Network error. Please try again.', 'error');
            }
            btn.disabled = false; btn.textContent = 'Add Building';
        }

        async function submitPostAnnouncement() {
            hideMsg('postAnnouncementMsg');
            const title           = document.getElementById('pa_title').value.trim();
            const content         = document.getElementById('pa_content').value.trim();
            const target_audience = document.getElementById('pa_audience').value;
            const priority        = document.getElementById('pa_priority').value;

            if (!title || !content) {
                showMsg('postAnnouncementMsg', 'Title and content are required.', 'error'); return;
            }

            const btn = document.getElementById('postAnnouncementBtn');
            btn.disabled = true; btn.textContent = 'Posting...';
            try {
                const res  = await fetch('../../api/admin/save_announcement.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ title, content, target_audience, priority }) });
                const data = await res.json();
                if (data.success) {
                    showMsg('postAnnouncementMsg', '✅ Announcement posted successfully!', 'success');
                    document.getElementById('pa_title').value = '';
                    document.getElementById('pa_content').value = '';
                    document.getElementById('pa_audience').value = 'all';
                    document.getElementById('pa_priority').value = 'medium';
                } else {
                    showMsg('postAnnouncementMsg', '❌ ' + (data.message || 'Failed to post announcement.'), 'error');
                }
            } catch(err) {
                showMsg('postAnnouncementMsg', '❌ Network error. Please try again.', 'error');
            }
            btn.disabled = false; btn.textContent = 'Post Now';
        }
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
