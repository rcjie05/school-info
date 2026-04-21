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
$conn = getDBConnection();
$depts = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$deptsArr = [];
while ($d = $depts->fetch_assoc()) $deptsArr[] = $d;
$conn->close();
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
    <title>Employee Profiles - HR</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; }
        .modal.active { display:flex; }
        .modal-content { background:white; padding:2rem; border-radius:var(--radius-lg); max-width:720px; width:90%; max-height:92vh; overflow-y:auto; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .form-grid .full { grid-column: 1/-1; }
        .form-group { margin-bottom:0; }
        .form-group label { display:block; font-weight:600; font-size:0.82rem; margin-bottom:0.35rem; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.4px; }
        .form-group input, .form-group select { width:100%; padding:0.65rem 0.9rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-size:0.9rem; font-family:inherit; }
        .form-group input:focus, .form-group select:focus { outline:none; border-color:var(--primary-purple); }
        .section-title { font-size:0.78rem; font-weight:700; color:var(--primary-purple); text-transform:uppercase; letter-spacing:0.6px; margin:1.25rem 0 0.75rem; padding-bottom:0.4rem; border-bottom:2px solid #eef2f7; grid-column:1/-1; }
        .badge { display:inline-block; padding:0.2rem 0.6rem; border-radius:999px; font-size:0.72rem; font-weight:700; text-transform:uppercase; }
        .badge-full_time    { background:#dbeafe; color:#1e40af; }
        .badge-part_time    { background:#fef3c7; color:#92400e; }
        .badge-contractual  { background:#f3e8ff; color:#6b21a8; }
        .badge-probationary { background:#dcfce7; color:#166534; }
        .emp-row { display:flex; align-items:center; gap:1rem; padding:0.85rem 1rem; border-bottom:1px solid #f0f0f0; cursor:pointer; transition:background 0.15s; }
        .emp-row:hover { background:#f8fafc; }
        .emp-avatar { width:40px; height:40px; border-radius:50%; background:var(--primary-purple); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1rem; flex-shrink:0; }
        .no-profile { font-size:0.75rem; color:#ef4444; font-weight:600; }
        .has-profile { font-size:0.75rem; color:#10b981; font-weight:600; }
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
                    <h1>Employee Profiles</h1>
                <p class="page-subtitle">Manage HR information for all staff</p>
            </div>
        </header>

        <div class="content-card">
            <div class="card-header" style="display:flex;gap:1rem;align-items:center;">
                <input type="text" id="searchInput" placeholder="🔍 Search employees..." oninput="filterEmployees()" style="padding:0.5rem 1rem;border:1.5px solid #e5e7eb;border-radius:var(--radius-md);flex:1;">
                <select id="roleFilter" onchange="filterEmployees()" style="padding:0.5rem 1rem;border:1.5px solid #e5e7eb;border-radius:var(--radius-md);">
                    <option value="">All Roles</option>
                    <option value="teacher">Teachers</option>
                    <option value="registrar">Registrars</option>
                    <option value="admin">Admins</option>
                </select>
            </div>
            <div id="employeeList" style="padding:0.5rem 1rem;">Loading...</div>
        </div>
    </main>
</div>

<!-- Edit Modal -->
<div id="empModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle" style="margin:0 0 1.5rem;">Edit HR Profile</h2>
        <input type="hidden" id="editUserId">
        <div class="form-grid">
            <div class="section-title">Employment Information</div>
            <div class="form-group">
                <label>Employment Type</label>
                <select id="empType">
                    <option value="full_time">Full Time</option>
                    <option value="part_time">Part Time</option>
                    <option value="contractual">Contractual</option>
                    <option value="probationary">Probationary</option>
                </select>
            </div>
            <div class="form-group">
                <label>HR Status</label>
                <select id="hrStatus">
                    <option value="active">Active</option>
                    <option value="on_leave">On Leave</option>
                    <option value="resigned">Resigned</option>
                    <option value="terminated">Terminated</option>
                </select>
            </div>
            <div class="form-group">
                <label>Hire Date</label>
                <input type="date" id="hireDate">
            </div>
            <div class="form-group">
                <label>Position / Job Title</label>
                <input type="text" id="position" placeholder="e.g. Senior Teacher">
            </div>
            <div class="form-group">
                <label>Department</label>
                <select id="deptId">
                    <option value="">-- None --</option>
                    <?php foreach ($deptsArr as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Salary Grade</label>
                <input type="text" id="salaryGrade" placeholder="e.g. SG-15">
            </div>
            <div class="form-group">
                <label>Monthly Salary (₱)</label>
                <input type="number" id="monthlySalary" placeholder="0.00" step="0.01">
            </div>

            <div class="section-title">Government Numbers</div>
            <div class="form-group">
                <label>SSS Number</label>
                <input type="text" id="sssNumber" placeholder="XX-XXXXXXX-X">
            </div>
            <div class="form-group">
                <label>PhilHealth Number</label>
                <input type="text" id="philhealthNumber" placeholder="XXXX-XXXX-XXXX">
            </div>
            <div class="form-group">
                <label>Pag-IBIG Number</label>
                <input type="text" id="pagibigNumber" placeholder="XXXX-XXXX-XXXX">
            </div>
            <div class="form-group">
                <label>TIN Number</label>
                <input type="text" id="tinNumber" placeholder="XXX-XXX-XXX-XXX">
            </div>

            <div class="section-title">Emergency Contact</div>
            <div class="form-group">
                <label>Contact Name</label>
                <input type="text" id="ecName" placeholder="Full name">
            </div>
            <div class="form-group">
                <label>Relationship</label>
                <input type="text" id="ecRelation" placeholder="e.g. Spouse, Parent">
            </div>
            <div class="form-group full">
                <label>Contact Phone</label>
                <input type="text" id="ecPhone" placeholder="09XX-XXX-XXXX">
            </div>
        </div>

        <div style="display:flex;gap:1rem;margin-top:1.75rem;">
            <button class="btn btn-primary" onclick="saveEmployee()" style="flex:1;">💾 Save Profile</button>
            <button class="btn" onclick="closeModal()" style="flex:1;">Cancel</button>
        </div>
    </div>
</div>

<script>
let allEmployees = [];

async function loadEmployees() {
    const res  = await fetch('../../api/hr/get_employees.php');
    const data = await res.json();
    if (!data.success) return;
    allEmployees = data.employees;
    filterEmployees();
}

function filterEmployees() {
    const q    = document.getElementById('searchInput').value.toLowerCase();
    const role = document.getElementById('roleFilter').value;
    const list = allEmployees.filter(e =>
        (!q || e.name.toLowerCase().includes(q) || (e.email||'').toLowerCase().includes(q))
        && (!role || e.role === role)
    );
    renderEmployees(list);
}

function renderEmployees(list) {
    const container = document.getElementById('employeeList');
    if (!list.length) { container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">No employees found.</p>'; return; }
    container.innerHTML = list.map(e => {
        const initial = (e.name||'?')[0].toUpperCase();
        const hasHR   = !!e.hr_id;
        const empBadge = hasHR ? `<span class="badge badge-${e.employment_type}">${e.employment_type.replace('_',' ')}</span>` : '';
        return `<div class="emp-row" onclick="openModal(${JSON.stringify(e).replace(/'/g,'&#39;')})">
            <div class="emp-avatar">${initial}</div>
            <div style="flex:1;">
                <div style="font-weight:700;">${esc(e.name)}</div>
                <div style="font-size:0.8rem;color:var(--text-secondary);">${esc(e.email||'')} · ${esc(e.role)}</div>
                ${hasHR && e.position ? `<div style="font-size:0.78rem;color:var(--text-secondary);">${esc(e.position)}</div>` : ''}
            </div>
            <div style="text-align:right;">
                ${empBadge}
                <div class="${hasHR ? 'has-profile' : 'no-profile'}">${hasHR ? '✓ HR Profile' : '+ Add Profile'}</div>
            </div>
        </div>`;
    }).join('');
}

function openModal(emp) {
    document.getElementById('modalTitle').textContent = `HR Profile — ${emp.name}`;
    document.getElementById('editUserId').value      = emp.id;
    document.getElementById('empType').value         = emp.employment_type    || 'full_time';
    document.getElementById('hrStatus').value        = emp.hr_status          || 'active';
    document.getElementById('hireDate').value        = emp.hire_date          || '';
    document.getElementById('position').value        = emp.position           || '';
    document.getElementById('deptId').value          = emp.department_id      || '';
    document.getElementById('salaryGrade').value     = emp.salary_grade       || '';
    document.getElementById('monthlySalary').value   = emp.monthly_salary     || '';
    document.getElementById('sssNumber').value       = emp.sss_number         || '';
    document.getElementById('philhealthNumber').value= emp.philhealth_number  || '';
    document.getElementById('pagibigNumber').value   = emp.pagibig_number     || '';
    document.getElementById('tinNumber').value       = emp.tin_number         || '';
    document.getElementById('ecName').value          = emp.emergency_contact_name     || '';
    document.getElementById('ecRelation').value      = emp.emergency_contact_relation || '';
    document.getElementById('ecPhone').value         = emp.emergency_contact_phone    || '';
    document.getElementById('empModal').classList.add('active');
}

function closeModal() { document.getElementById('empModal').classList.remove('active'); }

async function saveEmployee() {
    const payload = {
        user_id:                    parseInt(document.getElementById('editUserId').value),
        employment_type:            document.getElementById('empType').value,
        hr_status:                  document.getElementById('hrStatus').value,
        hire_date:                  document.getElementById('hireDate').value || null,
        position:                   document.getElementById('position').value,
        department_id:              document.getElementById('deptId').value || null,
        salary_grade:               document.getElementById('salaryGrade').value,
        monthly_salary:             document.getElementById('monthlySalary').value || null,
        sss_number:                 document.getElementById('sssNumber').value,
        philhealth_number:          document.getElementById('philhealthNumber').value,
        pagibig_number:             document.getElementById('pagibigNumber').value,
        tin_number:                 document.getElementById('tinNumber').value,
        emergency_contact_name:     document.getElementById('ecName').value,
        emergency_contact_relation: document.getElementById('ecRelation').value,
        emergency_contact_phone:    document.getElementById('ecPhone').value,
    };
    const res  = await fetch('../../api/hr/save_employee.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    const data = await res.json();
    if (data.success) { alert(data.message); closeModal(); loadEmployees(); }
    else              alert('Error: ' + data.message);
}

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
loadEmployees();
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

</body>
</html>
