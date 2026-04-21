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
    <title>User Management - Admin Dashboard</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        .modal {
            display: none; position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1000;
            align-items: center; justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white; padding: 2rem;
            border-radius: var(--radius-lg);
            max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;
        }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: var(--radius-md);
        }
        .tab-bar { display: flex; border-bottom: 2px solid #e5e7eb; margin-bottom: 1.5rem; }
        .tab-btn {
            padding: 0.65rem 1.4rem; border: none; background: none;
            font-size: 0.95rem; font-weight: 600; color: var(--text-secondary, #6b7280);
            cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px;
            transition: color 0.2s, border-color 0.2s;
        }
        .tab-btn.active { color: var(--primary, #4f46e5); border-bottom-color: var(--primary, #4f46e5); }
        .tab-count {
            display: inline-block; background: #e5e7eb; color: #374151;
            font-size: 0.72rem; font-weight: 700; border-radius: 999px;
            padding: 0 0.5rem; margin-left: 0.35rem;
        }
        .tab-btn.active .tab-count { background: var(--primary, #4f46e5); color: white; }
        .badge-archived {
            display: inline-block; background: #f3f4f6; color: #6b7280;
            font-size: 0.7rem; font-weight: 700; border-radius: 999px;
            padding: 0.1rem 0.55rem; border: 1px solid #d1d5db; margin-left: 0.3rem;
        }
        .archived-row td { opacity: 0.8; }
        #toast {
            position: fixed; bottom: 2rem; right: 2rem;
            padding: 0.85rem 1.5rem; border-radius: var(--radius-md);
            color: white; font-weight: 600; font-size: 0.95rem;
            z-index: 9999; display: none; box-shadow: 0 4px 16px rgba(0,0,0,0.18);
        }
    
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
<div id="toast"></div>
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
                    <a href="users.php" class="nav-item active"><span class="nav-icon">👥</span><span>User Management</span></a>
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
                    <h1>User Management</h1>
                <p class="page-subtitle">Manage system users and accounts</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAddUserModal()">➕ Add New User</button>
            </div>
        </header>

        <div class="content-card">
            <div class="tab-bar">
                <button class="tab-btn active" id="tabActive" onclick="switchTab('active')">
                    👥 Active Users <span class="tab-count" id="countActive">—</span>
                </button>
                <button class="tab-btn" id="tabArchived" onclick="switchTab('archived')">
                    🗃️ Archived <span class="tab-count" id="countArchived">—</span>
                </button>
            </div>

            <!-- Active Users Panel -->
            <div id="panelActive">
                <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
                    <select id="roleFilter" onchange="loadUsers()" style="padding:0.5rem; border-radius:var(--radius-md); border:1px solid #ddd;">
                        <option value="">All Roles</option>
                        <option value="student">Students</option>
                        <option value="teacher">Teachers</option>
                        <option value="registrar">Registrars</option>
                        <option value="admin">Admins</option>
                        <option value="hr">HR Officers</option>
                    </select>
                    <select id="statusFilter" onchange="loadUsers()" style="padding:0.5rem; border-radius:var(--radius-md); border:1px solid #ddd;">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <input type="text" id="searchInput" placeholder="Search users..." onkeyup="loadUsers()"
                        style="padding:0.5rem; border-radius:var(--radius-md); border:1px solid #ddd; flex:1; min-width:200px;">
                </div>
                <div id="usersTable">Loading...</div>
            </div>

            <!-- Archived Users Panel -->
            <div id="panelArchived" style="display:none;">
                <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
                    <select id="archivedRoleFilter" onchange="loadArchivedUsers()" style="padding:0.5rem; border-radius:var(--radius-md); border:1px solid #ddd;">
                        <option value="">All Roles</option>
                        <option value="student">Students</option>
                        <option value="teacher">Teachers</option>
                        <option value="registrar">Registrars</option>
                        <option value="admin">Admins</option>
                        <option value="hr">HR Officers</option>
                    </select>
                    <input type="text" id="archivedSearchInput" placeholder="Search archived users..." onkeyup="loadArchivedUsers()"
                        style="padding:0.5rem; border-radius:var(--radius-md); border:1px solid #ddd; flex:1; min-width:200px;">
                </div>
                <div style="background:#fef9c3; border:1px solid #fde047; padding:0.75rem 1rem; border-radius:var(--radius-md); margin-bottom:1rem; font-size:0.875rem; color:#713f12;">
                    ⚠️ Archived users cannot log in. You can restore them to make them active again, or permanently delete them.
                </div>
                <div id="archivedTable">Loading...</div>
            </div>
        </div>
    </main>
</div>

<!-- Add/Edit User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle">Add New User</h2>
        <form id="userForm" onsubmit="saveUser(event)">
            <input type="hidden" id="userId">
            <div class="form-group"><label>Name *</label><input type="text" id="userName" required></div>
            <div class="form-group"><label>Email *</label><input type="email" id="userEmail" required></div>
            <div class="form-group">
                <label>Password * <small>(Leave empty to keep current when editing)</small></label>
                <div class="pw-eye-wrap"><input type="password" id="userPassword">
<button type="button" class="pw-eye-btn" onclick="togglePass('userPassword', this)" aria-label="Show password" tabindex="-1"><svg id="eye-userPassword" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select id="userRole" required onchange="toggleRoleFields()">
                    <option value="">Select Role</option>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                    <option value="registrar">Registrar</option>
                    <option value="admin">Admin</option>
                    <option value="hr">HR Officer</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select id="userStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                </select>
            </div>
            <div id="studentFields" style="display:none;">
                <div class="form-group"><label>Student ID</label><input type="text" id="studentId"></div>
                <div class="form-group"><label>Course</label><input type="text" id="course"></div>
                <div class="form-group"><label>Year Level</label><input type="text" id="yearLevel"></div>
            </div>
            <div id="teacherFields" style="display:none;">
                <div class="form-group">
                    <label>Department</label>
                    <select id="department"><option value="">Select Department</option></select>
                </div>
                <div class="form-group"><label>Office Location</label><input type="text" id="officeLocation"></div>
                <div class="form-group"><label>Office Hours</label><input type="text" id="officeHours"></div>
            </div>
            <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Save</button>
                <button type="button" class="btn" onclick="closeModal()" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Suspension Modal -->
<div id="suspensionModal" class="modal">
    <div class="modal-content">
        <h2>Suspend/Deactivate User</h2>
        <p style="color:var(--text-secondary); margin-bottom:1.5rem;">Suspending a user will prevent them from logging in.</p>
        <form id="suspensionForm" onsubmit="confirmSuspension(event)">
            <input type="hidden" id="suspendUserId">
            <input type="hidden" id="suspendUserName">
            <div class="form-group">
                <label>Suspension Type *</label>
                <select id="suspensionType" onchange="toggleSuspensionDuration()" required>
                    <option value="temporary">Temporary (Time-limited)</option>
                    <option value="permanent">Permanent (Indefinite)</option>
                </select>
            </div>
            <div class="form-group" id="suspensionDurationGroup">
                <label>Suspend Until *</label>
                <input type="datetime-local" id="suspensionEndDate">
                <small style="color:var(--text-secondary); display:block; margin-top:0.5rem;">User will be auto-reactivated after this date</small>
            </div>
            <div class="form-group">
                <label>Reason *</label>
                <textarea id="suspensionReason" rows="4" required placeholder="Enter reason for suspension..."></textarea>
            </div>
            <div style="background:#FFF3CD; border:1px solid #FFC107; padding:1rem; border-radius:var(--radius-md); margin-bottom:1rem;">
                <strong style="color:#856404;">⚠️ Warning:</strong>
                <p style="color:#856404; margin:0.5rem 0 0 0; font-size:0.875rem;">User will be unable to access any features until reactivated.</p>
            </div>
            <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1; background:var(--status-rejected);">Suspend User</button>
                <button type="button" class="btn" onclick="closeSuspensionModal()" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
let departments = [];

function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = type === 'success' ? '#22c55e' : '#ef4444';
    t.style.display = 'block';
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.style.display = 'none', 3500);
}

/* ── Tabs ── */
function switchTab(tab) {
    document.getElementById('panelActive').style.display   = tab === 'active'   ? 'block' : 'none';
    document.getElementById('panelArchived').style.display = tab === 'archived' ? 'block' : 'none';
    document.getElementById('tabActive').classList.toggle('active',   tab === 'active');
    document.getElementById('tabArchived').classList.toggle('active', tab === 'archived');
    if (tab === 'active')   loadUsers();
    if (tab === 'archived') loadArchivedUsers();
}

/* ── Departments ── */
async function loadDepartments() {
    try {
        const res  = await fetch('../../api/admin/get_departments.php');
        const data = await res.json();
        if (data.success) { departments = data.departments; }
    } catch(e) {}
}
function populateDepartmentSelect() {
    const select = document.getElementById('department');
    select.innerHTML = '<option value="">Select Department</option>';
    departments.forEach(d => select.innerHTML += `<option value="${d.department_name}">${d.department_name}</option>`);
}
function toggleRoleFields() {
    const role = document.getElementById('userRole').value;
    document.getElementById('studentFields').style.display = role === 'student' ? 'block' : 'none';
    const showT = role === 'teacher' || role === 'registrar';
    document.getElementById('teacherFields').style.display = showT ? 'block' : 'none';
    if (showT) populateDepartmentSelect();
}

/* ── Active Users ── */
async function loadUsers() {
    const role   = document.getElementById('roleFilter').value;
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value;
    const params = new URLSearchParams();
    if (role)   params.append('role', role);
    if (status) params.append('status', status);
    if (search) params.append('search', search);

    try {
        const res  = await fetch(`../../api/admin/get_users.php?${params}`);
        const data = await res.json();
        document.getElementById('countActive').textContent = data.success ? data.users.length : '!';

        if (!data.success) {
            document.getElementById('usersTable').innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">Failed to load users.</p>';
            return;
        }
        if (data.users.length === 0) {
            document.getElementById('usersTable').innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">No users found.</p>';
            return;
        }

        let html = `<table class="data-table"><thead><tr>
            <th>Name</th><th>Email</th><th>Role</th><th>Status</th>
            <th>Suspension Info</th><th>Created</th><th>Actions</th>
        </tr></thead><tbody>`;

        data.users.forEach(user => {
            const isActive   = user.status === 'active';
            const isInactive = user.status === 'inactive';

            let suspInfo = '—';
            if (isInactive && user.deactivated_until) {
                const until = new Date(user.deactivated_until);
                suspInfo = until > new Date()
                    ? `<span style="color:var(--status-rejected);font-size:0.75rem;">Until: ${until.toLocaleDateString()} ${until.toLocaleTimeString()}</span>`
                    : `<span style="color:var(--text-secondary);font-size:0.75rem;">Expired</span>`;
            } else if (isInactive) {
                suspInfo = `<span style="color:var(--status-rejected);font-size:0.75rem;">Permanent</span>`;
            }

            const suspBtn = isActive
                ? `<button class="btn btn-sm" onclick="openSuspensionModal(${user.id},'${user.name.replace(/'/g,"\\'")}')\" style="background:var(--status-pending);">Suspend</button>`
                : `<button class="btn btn-sm" onclick="activateUser(${user.id},'${user.name.replace(/'/g,"\\'")}')\" style="background:var(--status-approved);">Activate</button>`;

            html += `<tr>
                <td>${user.name}</td>
                <td>${user.email}</td>
                <td><span class="status-badge">${user.role}</span></td>
                <td><span class="status-badge status-${user.status}">${user.status}</span></td>
                <td>${suspInfo}</td>
                <td>${user.created_date}</td>
                <td style="display:flex;gap:0.3rem;flex-wrap:wrap;">
                    <button class="btn btn-sm" onclick='editUser(${JSON.stringify(user)})'>Edit</button>
                    ${suspBtn}
                    <button class="btn btn-sm" onclick="archiveUser(${user.id},'${user.name.replace(/'/g,"\\'")}')\" style="background:#6b7280;color:#fff;">🗃️ Archive</button>
                    <button class="btn btn-sm" onclick="deleteUser(${user.id},'${user.name.replace(/'/g,"\\'")}')\" style="background:var(--status-rejected);">Delete</button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('usersTable').innerHTML = html;
    } catch(e) {
        document.getElementById('usersTable').innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">Error loading users. Please refresh.</p>';
    }
}

/* ── Archived Users ── */
async function loadArchivedUsers() {
    const role   = document.getElementById('archivedRoleFilter').value;
    const search = document.getElementById('archivedSearchInput').value;
    const params = new URLSearchParams();
    if (role)   params.append('role', role);
    if (search) params.append('search', search);

    try {
        const res  = await fetch(`../../api/admin/get_archived_users.php?${params}`);
        const data = await res.json();
        document.getElementById('countArchived').textContent = data.success ? data.users.length : '!';

        if (!data.success) {
            document.getElementById('archivedTable').innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">Failed to load archived users.</p>';
            return;
        }
        if (data.users.length === 0) {
            document.getElementById('archivedTable').innerHTML = `
                <div style="text-align:center;padding:3rem;color:var(--text-secondary);">
                    <div style="font-size:3rem;margin-bottom:1rem;">🗃️</div>
                    <p>No archived users found.</p>
                </div>`;
            return;
        }

        let html = `<table class="data-table"><thead><tr>
            <th>Name</th><th>Email</th><th>Role</th><th>Course / Dept</th>
            <th>Created</th><th>Archived On</th><th>Actions</th>
        </tr></thead><tbody>`;

        data.users.forEach(user => {
            const info = user.course || user.department || '—';
            html += `<tr class="archived-row">
                <td>${user.name} <span class="badge-archived">archived</span></td>
                <td>${user.email}</td>
                <td><span class="status-badge">${user.role}</span></td>
                <td>${info}</td>
                <td>${user.created_date}</td>
                <td style="font-size:0.8rem;color:var(--text-secondary);">${user.archived_date}</td>
                <td style="display:flex;gap:0.3rem;flex-wrap:wrap;">
                    <button class="btn btn-sm" onclick="restoreUser(${user.id},'${user.name.replace(/'/g,"\\'")}')\" style="background:var(--status-approved);">♻️ Restore</button>
                    <button class="btn btn-sm" onclick="permanentlyDelete(${user.id},'${user.name.replace(/'/g,"\\'")}')\" style="background:var(--status-rejected);">🗑️ Delete Forever</button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('archivedTable').innerHTML = html;
    } catch(e) {
        document.getElementById('archivedTable').innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">Error loading archived users.</p>';
    }
}

/* ── Archive / Restore / Delete Actions ── */
async function archiveUser(userId, userName) {
    if (!confirm(`Archive "${userName}"?\n\nThey won't be able to log in, but their data is kept. You can restore them anytime.`)) return;
    try {
        const res    = await fetch('../../api/admin/archive_user.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({user_id: userId}) });
        const result = await res.json();
        if (result.success) { showToast(result.message); loadUsers(); }
        else showToast(result.message, 'error');
    } catch(e) { showToast('Failed to archive user.', 'error'); }
}

async function restoreUser(userId, userName) {
    if (!confirm(`Restore "${userName}" to active status?`)) return;
    try {
        const res    = await fetch('../../api/admin/restore_user.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({user_id: userId}) });
        const result = await res.json();
        if (result.success) { showToast(result.message); loadArchivedUsers(); }
        else showToast(result.message, 'error');
    } catch(e) { showToast('Failed to restore user.', 'error'); }
}

async function permanentlyDelete(userId, userName) {
    if (!confirm(`⚠️ PERMANENTLY DELETE "${userName}"?\n\nThis CANNOT be undone. All their data will be gone forever.`)) return;
    try {
        const res    = await fetch('../../api/admin/delete_user.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({user_id: userId}) });
        const result = await res.json();
        if (result.success) { showToast(result.message); loadArchivedUsers(); }
        else showToast(result.message, 'error');
    } catch(e) { showToast('Failed to delete user.', 'error'); }
}

async function activateUser(userId, userName) {
    if (!confirm(`Activate ${userName}?`)) return;
    try {
        const res    = await fetch('../../api/admin/toggle_user_status.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({user_id: userId, status: 'active'}) });
        const result = await res.json();
        if (result.success) { showToast(result.message); loadUsers(); }
        else showToast(result.message, 'error');
    } catch(e) { showToast('Failed to activate user.', 'error'); }
}

async function deleteUser(userId, userName) {
    if (!confirm(`Delete ${userName} permanently? This cannot be undone.\n\nTip: Consider using Archive instead.`)) return;
    try {
        const res    = await fetch('../../api/admin/delete_user.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({user_id: userId}) });
        const result = await res.json();
        if (result.success) { showToast(result.message); loadUsers(); }
        else showToast(result.message, 'error');
    } catch(e) { showToast('Failed to delete user.', 'error'); }
}

/* ── Add / Edit ── */
function openAddUserModal() {
    document.getElementById('modalTitle').textContent = 'Add New User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userPassword').required = true;
    document.getElementById('userModal').classList.add('active');
}
function editUser(user) {
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('userId').value           = user.id;
    document.getElementById('userName').value         = user.name;
    document.getElementById('userEmail').value        = user.email;
    document.getElementById('userPassword').required  = false;
    document.getElementById('userPassword').value     = '';
    document.getElementById('userRole').value         = user.role;
    document.getElementById('userStatus').value       = user.status;
    document.getElementById('studentId').value        = user.student_id || '';
    document.getElementById('course').value           = user.course || '';
    document.getElementById('yearLevel').value        = user.year_level || '';
    document.getElementById('department').value       = user.department || '';
    document.getElementById('officeLocation').value   = user.office_location || '';
    document.getElementById('officeHours').value      = user.office_hours || '';
    toggleRoleFields();
    document.getElementById('userModal').classList.add('active');
}
function closeModal() { document.getElementById('userModal').classList.remove('active'); }

async function saveUser(e) {
    e.preventDefault();
    const userId = document.getElementById('userId').value;
    const data   = {
        name: document.getElementById('userName').value,
        email: document.getElementById('userEmail').value,
        role: document.getElementById('userRole').value,
        status: document.getElementById('userStatus').value,
        student_id: document.getElementById('studentId').value,
        course: document.getElementById('course').value,
        year_level: document.getElementById('yearLevel').value,
        department: document.getElementById('department').value,
        office_location: document.getElementById('officeLocation').value,
        office_hours: document.getElementById('officeHours').value
    };
    const pw = document.getElementById('userPassword').value;
    if (pw) data.password = pw;
    if (userId) data.user_id = userId;

    const url = userId ? '../../api/admin/update_user.php' : '../../api/admin/add_user.php';
    try {
        const res    = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
        const result = await res.json();
        if (result.success) { showToast(result.message); closeModal(); loadUsers(); }
        else showToast(result.message, 'error');
    } catch(e) { showToast('Failed to save user.', 'error'); }
}

/* ── Suspension ── */
function openSuspensionModal(userId, userName) {
    document.getElementById('suspendUserId').value   = userId;
    document.getElementById('suspendUserName').value = userName;
    document.getElementById('suspensionForm').reset();
    document.getElementById('suspendUserId').value   = userId;
    document.getElementById('suspendUserName').value = userName;
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('suspensionEndDate').min = now.toISOString().slice(0,16);
    toggleSuspensionDuration();
    document.getElementById('suspensionModal').classList.add('active');
}
function closeSuspensionModal() { document.getElementById('suspensionModal').classList.remove('active'); }
function toggleSuspensionDuration() {
    const type  = document.getElementById('suspensionType').value;
    const group = document.getElementById('suspensionDurationGroup');
    const input = document.getElementById('suspensionEndDate');
    if (type === 'temporary') { group.style.display = 'block'; input.required = true; }
    else { group.style.display = 'none'; input.required = false; input.value = ''; }
}
async function confirmSuspension(e) {
    e.preventDefault();
    const userId  = document.getElementById('suspendUserId').value;
    const type    = document.getElementById('suspensionType').value;
    const endDate = document.getElementById('suspensionEndDate').value;
    const reason  = document.getElementById('suspensionReason').value;
    if (type === 'temporary' && !endDate) { showToast('Please select an end date.', 'error'); return; }
    const data = { user_id: userId, status: 'inactive', deactivation_reason: reason };
    if (type === 'temporary') data.deactivated_until = endDate;
    try {
        const res    = await fetch('../../api/admin/toggle_user_status.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
        const result = await res.json();
        if (result.success) { showToast(result.message); closeSuspensionModal(); loadUsers(); }
        else showToast(result.message, 'error');
    } catch(e) { showToast('Failed to suspend user.', 'error'); }
}

/* ── Init ── */
loadDepartments();
loadUsers();
</script>

<script>
(function() {
    var sidebar = document.querySelector('.sidebar');
    var saved = sessionStorage.getItem('sidebarScroll');
    if (saved) sidebar.scrollTop = parseInt(saved);
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
