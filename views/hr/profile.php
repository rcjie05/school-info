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
requireRole('hr');

$conn = getDBConnection();

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
$initials  = strtoupper(substr($user['name'] ?? 'H', 0, 1));
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
    <title>My Profile - <?= htmlspecialchars($school_name) ?> Portal</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        <?php include '../../php/avatar_styles.php'; ?>
        .setting-section { padding: 1.5rem; background: var(--background-main); border-radius: var(--radius-md); margin-bottom: 1rem; }
        .setting-label { font-weight: 600; margin-bottom: 0.5rem; display: block; }
        .setting-input { width: 100%; padding: 0.75rem; border: 1.5px solid var(--border-color); border-radius: var(--radius-md); margin-bottom: 1rem; font-family: var(--font-main); font-size: 0.9rem; box-sizing: border-box; }
        .setting-input:focus { outline: none; border-color: var(--primary-purple); box-shadow: 0 0 0 3px rgba(61,107,159,0.1); }
    
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
                    <img src="../../images/logo2.jpg" alt="SCC Logo" id="sidebarLogoImg" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-md);">
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
                <p class="page-subtitle">Manage your profile picture and account settings</p>
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
                    <p class="avatar-role">HR Officer</p>
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

        <!-- Profile Info -->
        <div class="content-card" style="margin-bottom:2rem;">
            <div class="card-header"><h2 class="card-title">Profile Information</h2></div>
            <form id="profileForm" onsubmit="saveProfile(event)">
                <div class="setting-section">
                    <label class="setting-label">Full Name</label>
                    <input type="text" class="setting-input" id="fullName" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                    <label class="setting-label">Email</label>
                    <input type="email" class="setting-input" id="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="content-card" style="margin-bottom:2rem;">
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

        <!-- Appearance / Theme -->
        <div class="content-card" style="margin-bottom:2rem;" data-theme-picker-card>
            <div class="card-header"><h2 class="card-title">🎨 Appearance</h2></div>
            <div class="setting-section">
                <p style="font-size:0.9rem;color:var(--text-secondary);margin-bottom:1.5rem;">Choose a color theme for your account. Your selection is saved to your profile.</p>
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
            status.style.color = '#2E7A62';
        } else {
            status.textContent = '❌ ' + data.message;
            status.style.color = '#9A3A3A';
        }
    } catch(e) {
        status.textContent = '❌ Upload failed';
        status.style.color = '#9A3A3A';
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
        document.getElementById('avatarStatus').style.color = '#2E7A62';
    }
}

async function saveProfile(e) {
    e.preventDefault();
    const data = {
        name:  document.getElementById('fullName').value,
        email: document.getElementById('email').value
    };
    const res    = await fetch('../../api/hr/update_profile.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const result = await res.json();
    alert(result.success ? '✅ Profile updated!' : '❌ ' + result.message);
}

async function changePassword(e) {
    e.preventDefault();
    if (document.getElementById('newPassword').value !== document.getElementById('confirmPassword').value) {
        alert('Passwords do not match!'); return;
    }
    const data = {
        current_password: document.getElementById('currentPassword').value,
        new_password:     document.getElementById('newPassword').value
    };
    const res    = await fetch('../../api/hr/change_password.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
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
