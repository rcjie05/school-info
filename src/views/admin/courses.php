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
    <title>Courses - Admin Dashboard</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        /* Modal */
        .modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; }
        .modal.active { display:flex; }
        .modal-content { background:#fff; padding:2rem; border-radius:var(--radius-lg); max-width:640px; width:90%; max-height:90vh; overflow-y:auto; }
        .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; }
        .modal-header h2 { margin:0; font-size:1.2rem; }
        .modal-close { background:none; border:none; font-size:1.4rem; cursor:pointer; color:var(--text-secondary); line-height:1; }

        .form-group { margin-bottom:1rem; }
        .form-group label { display:block; margin-bottom:.4rem; font-weight:600; font-size:.88rem; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width:100%; padding:.65rem .85rem;
            border:1.5px solid #d1d5db; border-radius:var(--radius-md);
            font-size:.92rem; font-family:inherit; box-sizing:border-box;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { outline:none; border-color:var(--primary-purple); }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:0 1rem; }
        .form-row-full { grid-column:1/-1; }

        /* Filter bar */
        .filter-bar { display:flex; gap:.75rem; flex-wrap:wrap; align-items:center; padding:1rem 1rem .5rem; }
        .filter-bar input,
        .filter-bar select { padding:.5rem .85rem; border:1.5px solid #d1d5db; border-radius:var(--radius-md); font-size:.88rem; }
        .filter-bar input { flex:1; min-width:180px; }

        /* Stats */
        .course-stats { display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; }
        .stat-chip {
            padding:.65rem 1.25rem; border-radius:var(--radius-md);
            background:var(--background-main); font-size:.88rem; font-weight:600;
            border:1.5px solid #e5e7eb; display:flex; align-items:center; gap:.5rem;
        }
        .stat-chip .num { font-size:1.4rem; font-weight:800; color:var(--primary-purple); }

        /* Course cards grid */
        .courses-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:1rem; padding:1rem; }

        .course-card {
            background:var(--background-main);
            border-radius:var(--radius-md);
            border:1.5px solid #e5e7eb;
            overflow:hidden;
            transition:box-shadow .2s, border-color .2s;
        }
        .course-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); border-color:var(--primary-purple); }
        .course-card.inactive { opacity:.7; }

        .card-top {
            padding:1.25rem 1.25rem .75rem;
            border-left:5px solid var(--primary-purple);
            display:flex; justify-content:space-between; align-items:flex-start; gap:.5rem;
        }
        .course-card.inactive .card-top { border-left-color:#9ca3af; }

        .course-code-badge {
            font-family:var(--font-mono, monospace);
            font-weight:800; font-size:.78rem;
            background:rgba(124,58,237,.1); color:var(--primary-purple);
            padding:.2rem .6rem; border-radius:6px; white-space:nowrap;
        }
        .course-card.inactive .course-code-badge { background:#f3f4f6; color:#6b7280; }

        .course-name { font-weight:700; font-size:1rem; margin-bottom:.25rem; line-height:1.3; }
        .course-desc { font-size:.8rem; color:var(--text-secondary); margin-top:.35rem; line-height:1.5;
                       display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

        .card-meta { display:flex; flex-wrap:wrap; gap:.5rem; padding:.5rem 1.25rem; border-top:1px solid #f3f4f6; }
        .meta-chip { font-size:.75rem; padding:.15rem .55rem; border-radius:10px; background:#f3f4f6; color:var(--text-secondary); font-weight:500; }

        .card-counts { display:grid; grid-template-columns:repeat(3,1fr); gap:.1rem; border-top:1px solid #f3f4f6; }
        .count-cell { padding:.6rem; text-align:center; }
        .count-cell .cv { font-weight:800; font-size:1.1rem; color:var(--primary-purple); }
        .count-cell .cl { font-size:.68rem; color:var(--text-secondary); margin-top:.1rem; }
        .course-card.inactive .count-cell .cv { color:#9ca3af; }

        .card-actions { display:flex; gap:.5rem; padding:.75rem 1.25rem; border-top:1px solid #f3f4f6; background:#fafafa; }
        .btn-edit   { flex:1; padding:.4rem; font-size:.8rem; border:1.5px solid var(--primary-purple); background:transparent; color:var(--primary-purple); border-radius:var(--radius-sm); cursor:pointer; font-weight:600; }
        .btn-toggle { flex:1; padding:.4rem; font-size:.8rem; border:1.5px solid #6b7280; background:transparent; color:#6b7280; border-radius:var(--radius-sm); cursor:pointer; font-weight:600; }
        .btn-delete { flex:1; padding:.4rem; font-size:.8rem; border:1.5px solid #ef4444; background:transparent; color:#ef4444; border-radius:var(--radius-sm); cursor:pointer; font-weight:600; }
        .btn-edit:hover   { background:var(--primary-purple); color:#fff; }
        .btn-toggle:hover { background:#6b7280; color:#fff; }
        .btn-delete:hover { background:#ef4444; color:#fff; }

        .status-pill { padding:.2rem .7rem; border-radius:1rem; font-size:.72rem; font-weight:700; white-space:nowrap; }
        .status-active   { background:#dcfce7; color:#166534; }
        .status-inactive { background:#f3f4f6; color:#6b7280; }

        .empty-state { text-align:center; padding:4rem 2rem; color:var(--text-secondary); }
        .empty-state .ei { font-size:3rem; margin-bottom:1rem; }

        /* Detail modal */
        .detail-section { margin-bottom:1.25rem; }
        .detail-section h4 { font-size:.82rem; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.04em; margin-bottom:.5rem; }
        .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:.5rem; }
        .detail-item .dl { font-size:.75rem; color:var(--text-secondary); }
        .detail-item .dv { font-weight:600; font-size:.92rem; }

        @media(max-width:640px) { .form-grid { grid-template-columns:1fr; } .detail-grid { grid-template-columns:1fr; } }
    </style>
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
                    <a href="courses.php" class="nav-item active"><span class="nav-icon">🎓</span><span>Courses</span></a>
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
                    <h1>Courses</h1>
                <p class="page-subtitle">Manage academic programs and courses offered</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAddModal()">➕ Add Course</button>
            </div>
        </header>

        <!-- Stats -->
        <div class="course-stats" id="courseStats"></div>

        <!-- Content Card -->
        <div class="content-card">
            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="🔍  Search course name or code…" oninput="renderCourses()">
                <select id="statusFilter" onchange="loadCourses()">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select id="deptFilter" onchange="renderCourses()">
                    <option value="">All Departments</option>
                </select>
            </div>
            <div id="coursesGrid" class="courses-grid">
                <p style="grid-column:1/-1;text-align:center;color:var(--text-secondary);padding:3rem;">Loading…</p>
            </div>
        </div>
    </main>
</div>

<!-- Add/Edit Modal -->
<div class="modal" id="courseModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Course</h2>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <form id="courseForm" onsubmit="saveCourse(event)">
            <input type="hidden" id="courseId">
            <div class="form-grid">
                <div class="form-group form-row-full">
                    <label>Course Name *</label>
                    <input type="text" id="courseName" placeholder="e.g., Bachelor of Science in Computer Science" required>
                </div>
                <div class="form-group">
                    <label>Course Code *</label>
                    <input type="text" id="courseCode" placeholder="e.g., BSCS" style="text-transform:uppercase;" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select id="courseDept">
                        <option value="">— Select Department —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Duration (Years)</label>
                    <input type="number" id="courseDuration" value="4" min="1" max="10">
                </div>
                <div class="form-group">
                    <label>Total Units</label>
                    <input type="number" id="courseTotalUnits" placeholder="e.g., 148" min="1">
                </div>
                <div class="form-group form-row-full">
                    <label>Description</label>
                    <textarea id="courseDescription" rows="3" placeholder="Brief description of this course…"></textarea>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="courseStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div style="display:flex; gap:1rem; margin-top:1.25rem;">
                <button type="submit" class="btn btn-primary" style="flex:2;" id="saveBtn">Save Course</button>
                <button type="button" class="btn" onclick="closeModal()" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Detail/View Modal -->
<div class="modal" id="detailModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="detailTitle">Course Details</h2>
            <button class="modal-close" onclick="closeDetailModal()">✕</button>
        </div>
        <div id="detailBody"></div>
        <div style="display:flex; gap:.75rem; margin-top:1.5rem;" id="detailActions"></div>
    </div>
</div>

<script>
let allCourses  = [];
let departments = [];
let currentDetail = null;

// ── Load & Render ─────────────────────────────────────────────────
async function loadCourses() {
    const status = document.getElementById('statusFilter').value;
    const params = status ? `?status=${status}` : '';
    try {
        const res  = await fetch(`../../api/admin/get_courses.php${params}`);
        const data = await res.json();
        allCourses = data.courses || [];

        // Stats
        document.getElementById('courseStats').innerHTML = `
            <div class="stat-chip"><div class="num">${data.total}</div><div>Total Courses</div></div>
            <div class="stat-chip"><div class="num" style="color:#22c55e;">${data.total_active}</div><div>Active</div></div>
            <div class="stat-chip"><div class="num" style="color:#9ca3af;">${data.total_inactive}</div><div>Inactive</div></div>
        `;

        // Populate dept filter
        const depts = [...new Set(allCourses.map(c => c.department_name).filter(Boolean))].sort();
        const df = document.getElementById('deptFilter');
        const curDept = df.value;
        df.innerHTML = '<option value="">All Departments</option>' + depts.map(d => `<option value="${d}" ${d===curDept?'selected':''}>${d}</option>`).join('');

        renderCourses();
    } catch(e) {
        document.getElementById('coursesGrid').innerHTML = '<p style="grid-column:1/-1;text-align:center;color:var(--text-secondary);padding:3rem;">Failed to load courses.</p>';
    }
}

function renderCourses() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const dept   = document.getElementById('deptFilter').value;

    let list = allCourses;
    if (search) list = list.filter(c => c.course_name.toLowerCase().includes(search) || c.course_code.toLowerCase().includes(search));
    if (dept)   list = list.filter(c => c.department_name === dept);

    const grid = document.getElementById('coursesGrid');
    if (list.length === 0) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><div class="ei">🎓</div><p>No courses found.</p><button class="btn btn-primary" onclick="openAddModal()">➕ Add First Course</button></div>`;
        return;
    }

    grid.innerHTML = list.map(c => `
    <div class="course-card ${c.status}" onclick="viewDetail(${c.id})">
        <div class="card-top">
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.35rem;">
                    <span class="course-code-badge">${c.course_code}</span>
                    <span class="status-pill status-${c.status}">${c.status.charAt(0).toUpperCase()+c.status.slice(1)}</span>
                </div>
                <div class="course-name">${c.course_name}</div>
                ${c.description ? `<div class="course-desc">${c.description}</div>` : ''}
            </div>
        </div>
        <div class="card-meta">
            ${c.department_name ? `<span class="meta-chip">🏛️ ${c.department_name}</span>` : ''}
            <span class="meta-chip">⏱️ ${c.duration_years} yr${c.duration_years>1?'s':''}</span>
            ${c.total_units ? `<span class="meta-chip">⚡ ${c.total_units} units</span>` : ''}
        </div>
        <div class="card-counts">
            <div class="count-cell"><div class="cv">${c.section_count}</div><div class="cl">Sections</div></div>
            <div class="count-cell"><div class="cv">${c.student_count}</div><div class="cl">Students</div></div>
            <div class="count-cell"><div class="cv">${c.subject_count}</div><div class="cl">Subjects</div></div>
        </div>
        <div class="card-actions" onclick="event.stopPropagation()">
            <button class="btn-edit"   onclick="openEditModal(${c.id})">✏️ Edit</button>
            <button class="btn-toggle" onclick="toggleStatus(${c.id},'${c.status}')">${c.status==='active'?'⏸ Deactivate':'▶ Activate'}</button>
            <button class="btn-delete" onclick="deleteCourse(${c.id},'${esc(c.course_name)}')">🗑️ Delete</button>
        </div>
    </div>`).join('');
}

// ── View Detail ───────────────────────────────────────────────────
function viewDetail(id) {
    const c = allCourses.find(x => x.id == id);
    if (!c) return;
    currentDetail = c;

    document.getElementById('detailTitle').textContent = c.course_name;
    document.getElementById('detailBody').innerHTML = `
        <div class="detail-section">
            <div style="display:flex;gap:.5rem;align-items:center;margin-bottom:1rem;">
                <span class="course-code-badge" style="font-size:.9rem;padding:.3rem .8rem;">${c.course_code}</span>
                <span class="status-pill status-${c.status}">${c.status.charAt(0).toUpperCase()+c.status.slice(1)}</span>
            </div>
            ${c.description ? `<p style="color:var(--text-secondary);font-size:.9rem;margin-bottom:1rem;">${c.description}</p>` : ''}
        </div>
        <div class="detail-section">
            <h4>Program Info</h4>
            <div class="detail-grid">
                <div class="detail-item"><div class="dl">Department</div><div class="dv">${c.department_name || '—'}</div></div>
                <div class="detail-item"><div class="dl">Duration</div><div class="dv">${c.duration_years} Year${c.duration_years>1?'s':''}</div></div>
                <div class="detail-item"><div class="dl">Total Units</div><div class="dv">${c.total_units || '—'}</div></div>
                <div class="detail-item"><div class="dl">Date Added</div><div class="dv">${c.created_date}</div></div>
            </div>
        </div>
        <div class="detail-section">
            <h4>Usage</h4>
            <div class="detail-grid">
                <div class="detail-item"><div class="dl">Sections</div><div class="dv">${c.section_count}</div></div>
                <div class="detail-item"><div class="dl">Students Enrolled</div><div class="dv">${c.student_count}</div></div>
                <div class="detail-item"><div class="dl">Subjects</div><div class="dv">${c.subject_count}</div></div>
            </div>
        </div>
    `;

    document.getElementById('detailActions').innerHTML = `
        <button class="btn btn-primary" style="flex:1;" onclick="closeDetailModal(); openEditModal(${c.id})">✏️ Edit Course</button>
        <button class="btn" style="flex:1;" onclick="closeDetailModal()">Close</button>
    `;

    document.getElementById('detailModal').classList.add('active');
}

function closeDetailModal() { document.getElementById('detailModal').classList.remove('active'); }

// ── Add / Edit Modal ──────────────────────────────────────────────
async function loadDepts() {
    if (departments.length > 0) return;
    try {
        const res  = await fetch('../../api/admin/get_departments.php');
        const data = await res.json();
        departments = data.departments || [];
        const sel = document.getElementById('courseDept');
        departments.forEach(d => {
            sel.innerHTML += `<option value="${d.id}">${d.department_name}</option>`;
        });
    } catch(e) {}
}

async function openAddModal() {
    await loadDepts();
    document.getElementById('modalTitle').textContent = 'Add Course';
    document.getElementById('courseForm').reset();
    document.getElementById('courseId').value = '';
    document.getElementById('courseDuration').value = 4;
    document.getElementById('courseStatus').value = 'active';
    document.getElementById('courseModal').classList.add('active');
}

async function openEditModal(id) {
    await loadDepts();
    const c = allCourses.find(x => x.id == id);
    if (!c) return;

    document.getElementById('modalTitle').textContent = 'Edit Course';
    document.getElementById('courseId').value           = c.id;
    document.getElementById('courseName').value         = c.course_name;
    document.getElementById('courseCode').value         = c.course_code;
    document.getElementById('courseDescription').value  = c.description || '';
    document.getElementById('courseDept').value         = c.department_id || '';
    document.getElementById('courseDuration').value     = c.duration_years || 4;
    document.getElementById('courseTotalUnits').value   = c.total_units || '';
    document.getElementById('courseStatus').value       = c.status;
    document.getElementById('courseModal').classList.add('active');
}

function closeModal() { document.getElementById('courseModal').classList.remove('active'); }

async function saveCourse(e) {
    e.preventDefault();
    const btn = document.getElementById('saveBtn');
    btn.disabled = true; btn.textContent = 'Saving…';

    const data = {
        course_id:      document.getElementById('courseId').value || null,
        course_name:    document.getElementById('courseName').value.trim(),
        course_code:    document.getElementById('courseCode').value.trim().toUpperCase(),
        description:    document.getElementById('courseDescription').value.trim(),
        department_id:  document.getElementById('courseDept').value || null,
        duration_years: document.getElementById('courseDuration').value || 4,
        total_units:    document.getElementById('courseTotalUnits').value || null,
        status:         document.getElementById('courseStatus').value
    };

    try {
        const res    = await fetch('../../api/admin/save_course.php', {
            method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            showToast(result.message, 'success');
            closeModal();
            loadCourses();
        } else {
            showToast(result.message, 'error');
        }
    } catch(err) {
        showToast('Failed to save course.', 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'Save Course';
    }
}

// ── Toggle Status ─────────────────────────────────────────────────
async function toggleStatus(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const c = allCourses.find(x => x.id == id);
    if (!c) return;
    if (!confirm(`Set "${c.course_name}" to ${newStatus}?`)) return;

    try {
        const res = await fetch('../../api/admin/save_course.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ ...c, course_id: c.id, department_id: c.department_id || null, status: newStatus })
        });
        const result = await res.json();
        if (result.success) { showToast(result.message, 'success'); loadCourses(); }
        else showToast(result.message, 'error');
    } catch(e) { showToast('Failed to update status.', 'error'); }
}

// ── Delete ────────────────────────────────────────────────────────
async function deleteCourse(id, name) {
    if (!confirm(`Delete "${name}"?\n\nThis cannot be undone. Courses in use by sections or students cannot be deleted.`)) return;
    try {
        const res    = await fetch('../../api/admin/delete_course.php', {
            method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({course_id: id})
        });
        const result = await res.json();
        if (result.success) { showToast(result.message, 'success'); loadCourses(); }
        else showToast(result.message, 'error');
    } catch(e) { showToast('Failed to delete.', 'error'); }
}

// ── Toast ─────────────────────────────────────────────────────────
function showToast(msg, type='success') {
    const t = document.createElement('div');
    t.textContent = msg;
    Object.assign(t.style, {
        position:'fixed', bottom:'1.5rem', right:'1.5rem', zIndex:9999,
        padding:'.85rem 1.4rem', borderRadius:'var(--radius-md)',
        background: type==='success' ? '#22c55e' : '#ef4444',
        color:'#fff', fontWeight:'600', fontSize:'.9rem',
        boxShadow:'0 4px 16px rgba(0,0,0,.18)',
        animation:'fadeIn .2s ease'
    });
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

function esc(s) { return (s||'').replace(/'/g,"\\'"); }

// Close modals on backdrop click
document.getElementById('courseModal').addEventListener('click',  function(e){ if(e.target===this) closeModal(); });
document.getElementById('detailModal').addEventListener('click', function(e){ if(e.target===this) closeDetailModal(); });

loadCourses();
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
