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
requireRole('student');

$conn    = getDBConnection();

// Ensure avatar_url column exists
$_col = $conn->query("SHOW COLUMNS FROM `users` LIKE 'avatar_url'");
if ($_col && $_col->num_rows === 0) {
    $conn->query("ALTER TABLE `users` ADD COLUMN `avatar_url` VARCHAR(500) NULL DEFAULT NULL AFTER `status`");
}
$user_id = $_SESSION['user_id'];
$stmt    = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

$avatarUrl = !empty($user['avatar_url']) ? htmlspecialchars(getAvatarUrl($user['avatar_url'])) : null;
$initials  = strtoupper(substr($user['name'] ?? 'S', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Portal</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        <?php include '../../php/avatar_styles.php'; ?>
        .setting-section { padding: 1.5rem; background: var(--background-main); border-radius: var(--radius-md); margin-bottom: 1rem; }
        .setting-label { font-weight: 600; margin-bottom: 0.5rem; display: block; }
        .setting-input { width: 100%; padding: 0.75rem; border: 1.5px solid var(--border-color); border-radius: var(--radius-md); margin-bottom: 1rem; font-family: var(--font-main); font-size: 0.9rem; }
        .setting-input:focus { outline: none; border-color: var(--primary-purple); box-shadow: 0 0 0 3px rgba(61,107,159,0.1); }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .info-item strong { display: block; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); margin-bottom: 0.2rem; }
        .info-item span { font-size: 0.95rem; color: var(--text-primary); font-weight: 500; }
    
        /* ── Show/Hide Password ── */
        .pw-eye-wrap { position: relative; }
        .pw-eye-wrap input { padding-right: 2.6rem !important; }
        .pw-eye-btn {
            position: absolute; right: .65rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; padding: 4px;
            color: #aaa; display: flex; align-items: center; line-height: 1;
            transition: color .2s;
        }
        .pw-eye-btn:hover { color: var(--primary-purple, #8b0000); }

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
                    <span>Student Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="schedule.php" class="nav-item"><span class="nav-icon">📅</span><span>My Schedule</span></a>
                    <a href="subjects.php" class="nav-item"><span class="nav-icon">📚</span><span>Study Load</span></a>
                    <a href="grades.php" class="nav-item"><span class="nav-icon">🎓</span><span>Grades</span></a>
                    <a href="calendar.php" class="nav-item"><span class="nav-icon">🗓️</span><span>Calendar</span></a>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                    <a href="faculty.php" class="nav-item"><span class="nav-icon">👨‍🏫</span><span>Faculty Directory</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Support</div>
                    <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>Announcements</span></a>
                    <a href="feedback.php" class="nav-item"><span class="nav-icon">💬</span><span>Feedback</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Account</div>
                    <a href="profile.php" class="nav-item active"><span class="nav-icon">👤</span><span>My Profile</span></a>
                    <a href="../../php/logout.php" class="nav-item"><span class="nav-icon">🚪</span><span>Logout</span></a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <div class="header-title">
                    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button>
                    <h1>My Profile</h1>
                    <p class="page-subtitle">Manage your profile and account settings</p>
                </div>
            </header>

            <!-- Profile Picture -->
            <div class="content-card" style="margin-bottom:2rem;">
                <div class="card-header"><h2 class="card-title">Profile Picture</h2></div>
                <div class="avatar-upload-section">
                    <div class="avatar-preview-wrap">
                        <div class="avatar-preview" id="avatarPreview">
                            <?php if ($avatarUrl): ?>
                                <img src="<?= $avatarUrl ?>?t=<?= time() ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <span class="avatar-initials"><?= $initials ?></span>
                            <?php endif; ?>
                        </div>
                        <label class="avatar-edit-btn" for="avatarFileInput" title="Change photo">✏️</label>
                    </div>
                    <div class="avatar-upload-info">
                        <p class="avatar-name"><?= htmlspecialchars($user['name']) ?></p>
                        <p class="avatar-role">Student<?= $user['course'] ? ' · ' . htmlspecialchars($user['course']) : '' ?></p>
                        <p class="avatar-hint">JPG, PNG, GIF or WEBP · Max 5MB</p>
                        <div class="avatar-actions">
                            <label for="avatarFileInput" class="btn btn-primary" style="cursor:pointer;">📷 Upload Photo</label>
                            <button class="btn btn-secondary" onclick="removeAvatar()" id="removeBtn" <?= $avatarUrl ? '' : 'style="display:none;"' ?>>🗑️ Remove</button>
                        </div>
                        <input type="file" id="avatarFileInput" accept="image/*" style="display:none;" onchange="uploadAvatar(this)">
                        <p class="avatar-status" id="avatarStatus"></p>
                    </div>
                </div>
            </div>

            <!-- Student Info (read-only) -->
            <div class="content-card" style="margin-bottom:2rem;">
                <div class="card-header"><h2 class="card-title">Academic Information</h2></div>
                <div class="setting-section">
                    <div class="info-grid">
                        <div class="info-item"><strong>Full Name</strong><span><?= htmlspecialchars($user['name'] ?? '—') ?></span></div>
                        <div class="info-item"><strong>Student ID</strong><span><?= htmlspecialchars($user['student_id'] ?? '—') ?></span></div>
                        <div class="info-item"><strong>Email</strong><span><?= htmlspecialchars($user['email'] ?? '—') ?></span></div>
                        <div class="info-item"><strong>Course</strong><span><?= htmlspecialchars($user['course'] ?? '—') ?></span></div>
                        <div class="info-item"><strong>Year Level</strong><span><?= htmlspecialchars($user['year_level'] ?? '—') ?></span></div>
                        <div class="info-item"><strong>Status</strong><span><?= ucfirst($user['status'] ?? '—') ?></span></div>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="content-card">
                <div class="card-header"><h2 class="card-title">Change Password</h2></div>
                <form id="passwordForm" onsubmit="changePassword(event)">
                    <div class="setting-section">
                        <label class="setting-label">Current Password</label>
                        <div class="pw-eye-wrap"><input type="password" class="setting-input" id="currentPassword" required>
<button type="button" class="pw-eye-btn" onclick="togglePass('currentPassword', this)" aria-label="Show password" tabindex="-1"><svg id="eye-currentPassword" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
                        <label class="setting-label">New Password</label>
                        <div class="pw-eye-wrap"><input type="password" class="setting-input" id="newPassword" required minlength="6">
<button type="button" class="pw-eye-btn" onclick="togglePass('newPassword', this)" aria-label="Show password" tabindex="-1"><svg id="eye-newPassword" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
                        <label class="setting-label">Confirm New Password</label>
                        <div class="pw-eye-wrap"><input type="password" class="setting-input" id="confirmPassword" required minlength="6">
<button type="button" class="pw-eye-btn" onclick="togglePass('confirmPassword', this)" aria-label="Show password" tabindex="-1"><svg id="eye-confirmPassword" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
                        <button type="submit" class="btn btn-primary">🔒 Update Password</button>
                    </div>
                </form>
            </div>

            <!-- Appearance / Theme Picker -->
            <div class="content-card" style="margin-top:2rem;" data-theme-picker-card>
                <div class="card-header">
                    <h2 class="card-title">🎨 Appearance</h2>
                </div>
                <div class="setting-section">
                    <p style="font-size:0.9rem;color:var(--text-secondary);margin-bottom:1.5rem;">Choose a color theme for your account. Your selection is saved to your profile — only you will see this theme.</p>
                    <div class="inline-theme-grid" id="inlineThemePicker"></div>
                </div>
            </div>
        </main>
    </div>

    <script>
    async function uploadAvatar(input) {
        const file = input.files[0];
        if (!file) return;
        const status = document.getElementById('avatarStatus');
        status.textContent = '⏳ Uploading...';
        status.style.color = 'var(--text-secondary)';
        const formData = new FormData();
        formData.append('avatar', file);
        try {
            const res  = await fetch('../../api/shared/upload_avatar.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                document.getElementById('avatarPreview').innerHTML = `<img src="${data.avatar_url}?t=${Date.now()}" style="width:100%;height:100%;object-fit:cover;" alt="">`;
                document.getElementById('removeBtn').style.display = '';
                status.textContent = '✅ Photo updated!';
                status.style.color = 'var(--secondary-green)';
            } else {
                status.textContent = '❌ ' + data.message;
                status.style.color = 'var(--secondary-pink)';
            }
        } catch(e) {
            status.textContent = '❌ Upload failed';
            status.style.color = 'var(--secondary-pink)';
        }
        input.value = '';
    }

    async function removeAvatar() {
        if (!confirm('Remove your profile picture?')) return;
        const res  = await fetch('../../api/shared/remove_avatar.php', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            document.getElementById('avatarPreview').innerHTML = `<span class="avatar-initials"><?= $initials ?></span>`;
            document.getElementById('removeBtn').style.display = 'none';
            document.getElementById('avatarStatus').textContent = '✅ Photo removed';
            document.getElementById('avatarStatus').style.color = 'var(--secondary-green)';
        }
    }

    async function changePassword(e) {
        e.preventDefault();
        if (document.getElementById('newPassword').value !== document.getElementById('confirmPassword').value) {
            alert('Passwords do not match!'); return;
        }
        const data = { current_password: document.getElementById('currentPassword').value, new_password: document.getElementById('newPassword').value };
        const res  = await fetch('../../api/student/change_password.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
        const result = await res.json();
        if (result.success) { alert('✅ Password changed!'); document.getElementById('passwordForm').reset(); }
        else alert('❌ ' + (result.message || 'Failed'));
    }
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
<?php include 'chatbot-widget.php'; ?>
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

<!-- ── Global Search Overlay ─────────────────────────────────────── -->
<style>
.global-search-btn {
    background: var(--background-card, #fff);
    border: 1.5px solid var(--border-color, #e2e8f0);
    border-radius: var(--radius-md, 8px);
    width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: var(--text-secondary, #64748b);
    transition: all .2s; flex-shrink: 0;
}
.global-search-btn:hover {
    background: var(--primary-purple, #3D6B9F);
    color: #fff; border-color: var(--primary-purple, #3D6B9F);
    transform: scale(1.05);
}
.gs-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(10,20,40,0.55); backdrop-filter: blur(6px);
    z-index: 99999; align-items: flex-start; justify-content: center;
    padding-top: clamp(3rem, 10vh, 6rem);
}
.gs-overlay.open { display: flex; animation: gsFadeIn .18s ease; }
@keyframes gsFadeIn { from { opacity:0; } to { opacity:1; } }
.gs-box {
    background: var(--background-card, #fff);
    border-radius: 16px;
    box-shadow: 0 24px 80px rgba(0,0,0,0.28);
    width: min(640px, calc(100vw - 2rem));
    max-height: 70vh; display: flex; flex-direction: column;
    overflow: hidden; animation: gsSlideIn .2s ease;
}
@keyframes gsSlideIn { from { opacity:0; transform:translateY(-16px) scale(.97); } to { opacity:1; transform:translateY(0) scale(1); } }
.gs-input-wrap {
    display: flex; align-items: center; gap: .75rem;
    padding: 1rem 1.25rem; border-bottom: 1.5px solid var(--border-color, #e2e8f0);
    flex-shrink: 0;
}
.gs-input-wrap svg { color: var(--text-secondary, #64748b); flex-shrink:0; }
.gs-input {
    flex: 1; border: none; outline: none; background: transparent;
    font-size: 1.05rem; color: var(--text-primary, #1C2C42);
    font-family: inherit;
}
.gs-input::placeholder { color: var(--text-secondary, #94a3b8); }
.gs-close {
    background: var(--background-page, #f8fafc); border: 1.5px solid var(--border-color, #e2e8f0);
    border-radius: 6px; padding: .2rem .5rem; font-size: .72rem;
    color: var(--text-secondary, #64748b); cursor: pointer; flex-shrink:0;
    font-family: inherit; transition: all .15s;
}
.gs-close:hover { background: var(--border-color, #e2e8f0); }
.gs-results {
    overflow-y: auto; flex: 1; padding: .5rem 0;
    scrollbar-width: thin;
}
.gs-section-label {
    font-size: .65rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 1px; color: var(--text-secondary, #94a3b8);
    padding: .6rem 1.25rem .3rem; margin-top: .25rem;
}
.gs-item {
    display: flex; align-items: center; gap: .85rem;
    padding: .7rem 1.25rem; cursor: pointer; text-decoration: none;
    transition: background .13s; border-radius: 0;
}
.gs-item:hover, .gs-item.active {
    background: var(--background-hover, #f1f5f9);
}
.gs-icon {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; background: var(--background-page, #f8fafc);
}
.gs-item-text { flex: 1; min-width: 0; }
.gs-item-title { font-size: .88rem; font-weight: 600; color: var(--text-primary, #1C2C42); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gs-item-sub { font-size: .75rem; color: var(--text-secondary, #64748b); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gs-arrow { color: var(--text-secondary, #cbd5e1); flex-shrink: 0; }
.gs-empty { text-align: center; padding: 2.5rem 1rem; color: var(--text-secondary, #94a3b8); font-size: .9rem; }
.gs-footer {
    border-top: 1.5px solid var(--border-color, #e2e8f0);
    padding: .6rem 1.25rem; display: flex; gap: 1rem; flex-shrink: 0;
    align-items: center;
}
.gs-hint { font-size: .68rem; color: var(--text-secondary, #94a3b8); display: flex; align-items: center; gap: .3rem; }
.gs-hint kbd {
    background: var(--background-page, #f1f5f9); border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 4px; padding: .1rem .35rem; font-size: .65rem;
    font-family: inherit; color: var(--text-secondary, #64748b);
}
mark.gs-hl { background: rgba(61,107,159,.15); color: var(--primary-purple, #3D6B9F); border-radius: 3px; padding: 0 2px; font-style: normal; }
</style>

<!-- Search Overlay HTML -->
<div class="gs-overlay" id="gsOverlay" role="dialog" aria-modal="true" aria-label="Global Search">
    <div class="gs-box" id="gsBox">
        <div class="gs-input-wrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input class="gs-input" id="gsInput" type="text" placeholder="Search pages, subjects, grades, announcements…" autocomplete="off" spellcheck="false">
            <button class="gs-close" id="gsCloseBtn">ESC</button>
        </div>
        <div class="gs-results" id="gsResults"></div>
        <div class="gs-footer">
            <span class="gs-hint"><kbd>↑</kbd><kbd>↓</kbd> navigate</span>
            <span class="gs-hint"><kbd>↵</kbd> open</span>
            <span class="gs-hint"><kbd>ESC</kbd> close</span>
        </div>
    </div>
</div>

<script>
(function() {
    // ── Static page index ──────────────────────────────────────────────
    const PAGES = [
        { title: 'Dashboard',        url: 'dashboard.php',      icon: '🏠', sub: 'Home overview' },
        { title: 'My Schedule',      url: 'schedule.php',       icon: '📅', sub: 'Class timetable' },
        { title: 'Study Load',       url: 'subjects.php',       icon: '📚', sub: 'Enrolled subjects' },
        { title: 'Grades',           url: 'grades.php',         icon: '📊', sub: 'Academic performance' },
        { title: 'Calendar',         url: 'calendar.php',       icon: '🗓️', sub: 'Academic calendar & events' },
        { title: 'Floor Plan',       url: 'floorplan.php',      icon: '🗺️', sub: 'Campus map & rooms' },
        { title: 'Faculty Directory',url: 'faculty.php',        icon: '👩‍🏫', sub: 'Teachers & staff' },
        { title: 'Announcements',    url: 'announcements.php',  icon: '📢', sub: 'School announcements' },
        { title: 'Feedback',         url: 'feedback.php',       icon: '💬', sub: 'Submit feedback' },
        { title: 'Profile',          url: 'profile.php',        icon: '👤', sub: 'My account & settings' },
        { title: 'Chatbot',          url: 'chatbot.php',        icon: '🤖', sub: 'AI assistant' },
    ];

    // ── Dynamic data cache ─────────────────────────────────────────────
    let dynData = [];
    let dynLoaded = false;

    async function loadDynamic() {
        if (dynLoaded) return;
        dynLoaded = true;
        try {
            const [gradesRes, subjectsRes, announcementsRes] = await Promise.allSettled([
                fetch('../../api/student/get_grades.php').then(r => r.json()),
                fetch('../../api/student/get_study_load.php').then(r => r.json()),
                fetch('../../api/student/get_announcements.php').then(r => r.json()),
            ]);

            if (gradesRes.status === 'fulfilled' && gradesRes.value?.grades) {
                gradesRes.value.grades.forEach(g => {
                    dynData.push({
                        icon: '📊', section: 'Grades',
                        title: g.subject_name || g.subject_code,
                        sub: `Grade: ${g.final_grade ?? g.midterm_grade ?? 'No grade yet'} · ${g.subject_code || ''}`,
                        url: 'grades.php'
                    });
                });
            }
            if (subjectsRes.status === 'fulfilled' && subjectsRes.value?.subjects) {
                subjectsRes.value.subjects.forEach(s => {
                    dynData.push({
                        icon: '📚', section: 'Subjects',
                        title: s.subject_name || s.name,
                        sub: `${s.subject_code || ''} · ${s.units || ''} units · ${s.teacher_name || ''}`,
                        url: 'subjects.php'
                    });
                });
            }
            if (announcementsRes.status === 'fulfilled' && announcementsRes.value?.announcements) {
                announcementsRes.value.announcements.forEach(a => {
                    dynData.push({
                        icon: '📢', section: 'Announcements',
                        title: a.title,
                        sub: a.date || '',
                        url: 'announcements.php'
                    });
                });
            }
        } catch(e) {}
    }

    // ── Search logic ───────────────────────────────────────────────────
    function highlight(text, query) {
        if (!query) return escHtml(text);
        const escaped = escHtml(text);
        const re = new RegExp('(' + escRegex(query) + ')', 'gi');
        return escaped.replace(re, '<mark class="gs-hl">$1</mark>');
    }
    function escHtml(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function escRegex(s) {
        return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function search(query) {
        const q = query.trim().toLowerCase();
        const results = [];

        // Pages
        const pageMatches = PAGES.filter(p =>
            p.title.toLowerCase().includes(q) ||
            p.sub.toLowerCase().includes(q)
        );
        if (pageMatches.length) {
            results.push({ type: 'section', label: 'Pages' });
            pageMatches.forEach(p => results.push({ type: 'item', ...p, section: 'Pages' }));
        }

        // Dynamic data
        if (q.length >= 2) {
            const groups = {};
            dynData.forEach(d => {
                if (d.title.toLowerCase().includes(q) || d.sub.toLowerCase().includes(q)) {
                    if (!groups[d.section]) groups[d.section] = [];
                    groups[d.section].push(d);
                }
            });
            Object.entries(groups).forEach(([sec, items]) => {
                results.push({ type: 'section', label: sec });
                items.slice(0, 5).forEach(i => results.push({ type: 'item', ...i }));
            });
        }

        return results;
    }

    function renderResults(query) {
        const results = query ? search(query) : getDefaults();
        const container = document.getElementById('gsResults');

        if (!results.length) {
            container.innerHTML = '<div class="gs-empty">No results for <strong>"' + escHtml(query) + '"</strong></div>';
            activeIdx = -1;
            return;
        }

        container.innerHTML = results.map((r, i) => {
            if (r.type === 'section') {
                return `<div class="gs-section-label">${escHtml(r.label)}</div>`;
            }
            const q = query.trim();
            return `<a class="gs-item" href="${escHtml(r.url)}" data-idx="${i}">
                <div class="gs-icon">${r.icon}</div>
                <div class="gs-item-text">
                    <div class="gs-item-title">${highlight(r.title, q)}</div>
                    ${r.sub ? `<div class="gs-item-sub">${highlight(r.sub, q)}</div>` : ''}
                </div>
                <svg class="gs-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </a>`;
        }).join('');

        activeIdx = -1;
    }

    function getDefaults() {
        const defaults = [];
        defaults.push({ type: 'section', label: 'Quick Access' });
        PAGES.slice(0, 6).forEach(p => defaults.push({ type: 'item', ...p }));
        return defaults;
    }

    // ── Keyboard navigation ────────────────────────────────────────────
    let activeIdx = -1;

    function getItems() {
        return Array.from(document.querySelectorAll('#gsResults .gs-item'));
    }

    function setActive(idx) {
        const items = getItems();
        if (!items.length) return;
        items.forEach(i => i.classList.remove('active'));
        activeIdx = Math.max(0, Math.min(idx, items.length - 1));
        items[activeIdx].classList.add('active');
        items[activeIdx].scrollIntoView({ block: 'nearest' });
    }

    // ── Open / Close ───────────────────────────────────────────────────
    const overlay = document.getElementById('gsOverlay');
    const input   = document.getElementById('gsInput');

    function openSearch() {
        overlay.classList.add('open');
        input.value = '';
        renderResults('');
        setTimeout(() => input.focus(), 50);
        loadDynamic();
    }

    function closeSearch() {
        overlay.classList.remove('open');
        activeIdx = -1;
    }

    // Trigger button
    const btn = document.getElementById('globalSearchBtn');
    if (btn) btn.addEventListener('click', openSearch);

    // Close button
    document.getElementById('gsCloseBtn').addEventListener('click', closeSearch);

    // Click outside to close
    overlay.addEventListener('click', e => { if (e.target === overlay) closeSearch(); });

    // Keyboard shortcut: Ctrl+K or /
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); openSearch(); }
        if (e.key === 'Escape' && overlay.classList.contains('open')) closeSearch();
        if (overlay.classList.contains('open')) {
            if (e.key === 'ArrowDown') { e.preventDefault(); setActive(activeIdx + 1); }
            if (e.key === 'ArrowUp')   { e.preventDefault(); setActive(activeIdx - 1); }
            if (e.key === 'Enter') {
                const items = getItems();
                if (activeIdx >= 0 && items[activeIdx]) {
                    items[activeIdx].click();
                }
            }
        }
    });

    // Input handler
    input.addEventListener('input', e => {
        renderResults(e.target.value);
    });

})();
</script>


    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
      <a href="dashboard.php" class="mobile-nav-item" data-page="dashboard">
        <span class="mobile-nav-icon">📊</span><span>Home</span>
      </a>
      <a href="schedule.php" class="mobile-nav-item" data-page="schedule">
        <span class="mobile-nav-icon">📅</span><span>Schedule</span>
      </a>
      <a href="grades.php" class="mobile-nav-item" data-page="grades">
        <span class="mobile-nav-icon">🎓</span><span>Grades</span>
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

<script>
function togglePass(fieldId, btn) {
    var input = document.getElementById(fieldId);
    var svg = document.getElementById('eye-' + fieldId);
    var isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    svg.innerHTML = isHidden
        ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1=\'1\' y1=\'1\' x2=\'23\' y2=\'23\'/>'
        : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    btn.style.color = isHidden ? 'var(--primary-purple, #8b0000)' : '';
}
</script>
</body>
</html>
