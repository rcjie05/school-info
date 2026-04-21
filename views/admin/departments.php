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
    <title>Departments - Admin Dashboard</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: var(--radius-lg); max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: var(--radius-md); }
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
                    <a href="departments.php" class="nav-item active"><span class="nav-icon">🏛️</span><span>Departments</span></a>
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
                    <h1>Departments</h1>
                    <p class="page-subtitle">Manage academic departments</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openAddModal()">➕ Add Department</button>
                </div>
            </header>
            
            <div class="content-card">
                <div id="deptsTable">Loading...</div>
            </div>
        </main>
    </div>

    <div id="deptModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add Department</h2>
            <form id="deptForm" onsubmit="saveDept(event)">
                <input type="hidden" id="deptId">
                <div class="form-group"><label>Department Name *</label><input type="text" id="deptName" required></div>
                <div class="form-group"><label>Department Code *</label><input type="text" id="deptCode" required></div>
                <div class="form-group"><label>Head of Department</label><input type="text" id="headOfDept"></div>
                <div class="form-group"><label>Office Location</label><input type="text" id="officeLocation"></div>
                <div class="form-group"><label>Contact Email</label><input type="email" id="contactEmail"></div>
                <div class="form-group"><label>Contact Phone</label><input type="text" id="contactPhone"></div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save</button>
                    <button type="button" class="btn" onclick="closeModal()" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function loadDepts() {
            const response = await fetch('../../api/admin/get_departments.php');
            const data = await response.json();
            if (data.success) {
                let html = '<table class="data-table"><thead><tr><th>Department</th><th>Code</th><th>Head</th><th>Office</th><th>Contact</th><th>Actions</th></tr></thead><tbody>';
                data.departments.forEach(d => {
                    html += `<tr><td>${d.department_name}</td><td>${d.department_code}</td><td>${d.head_of_department || 'N/A'}</td><td>${d.office_location || 'N/A'}</td><td>${d.contact_email || 'N/A'}</td>
                        <td><button class="btn btn-sm" onclick='editDept(${JSON.stringify(d)})'>Edit</button>
                        <button class="btn btn-sm" onclick="deleteDept(${d.id}, '${d.department_name}')" style="background: var(--status-rejected);">Delete</button></td></tr>`;
                });
                html += '</tbody></table>';
                document.getElementById('deptsTable').innerHTML = html;
            }
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Department';
            document.getElementById('deptForm').reset();
            document.getElementById('deptId').value = '';
            document.getElementById('deptModal').classList.add('active');
        }

        function editDept(dept) {
            document.getElementById('modalTitle').textContent = 'Edit Department';
            document.getElementById('deptId').value = dept.id;
            document.getElementById('deptName').value = dept.department_name;
            document.getElementById('deptCode').value = dept.department_code;
            document.getElementById('headOfDept').value = dept.head_of_department || '';
            document.getElementById('officeLocation').value = dept.office_location || '';
            document.getElementById('contactEmail').value = dept.contact_email || '';
            document.getElementById('contactPhone').value = dept.contact_phone || '';
            document.getElementById('deptModal').classList.add('active');
        }

        function closeModal() { document.getElementById('deptModal').classList.remove('active'); }

        async function saveDept(e) {
            e.preventDefault();
            const data = {
                department_id: document.getElementById('deptId').value || null,
                department_name: document.getElementById('deptName').value,
                department_code: document.getElementById('deptCode').value,
                head_of_department: document.getElementById('headOfDept').value,
                office_location: document.getElementById('officeLocation').value,
                contact_email: document.getElementById('contactEmail').value,
                contact_phone: document.getElementById('contactPhone').value
            };
            
            const response = await fetch('../../api/admin/save_department.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) { alert(result.message); closeModal(); loadDepts(); } 
            else { alert('Error: ' + result.message); }
        }

        async function deleteDept(id, name) {
            if (!confirm(`Delete ${name}?`)) return;
            const response = await fetch('../../api/admin/delete_department.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({department_id: id})
            });
            const result = await response.json();
            if (result.success) { alert(result.message); loadDepts(); } 
            else { alert('Error: ' + result.message); }
        }

        loadDepts();
    </script>

    <script>
        (function() {
            var sidebar = document.querySelector('.sidebar');
            // Scroll active nav item into view
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

</body>
</html>
