<?php require_once '../../php/config.php'; requireRole('registrar');

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
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Study Loads - Registrar</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        .modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; }
        .modal.active { display:flex; }
        .modal-content { background:#fff; padding:2rem; border-radius:var(--radius-lg); width:90%; max-width:560px; max-height:90vh; overflow-y:auto; }
        .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; }
        .modal-header h2 { margin:0; }
        .modal-close { background:none; border:none; font-size:1.4rem; cursor:pointer; color:var(--text-secondary); }
        .form-group { margin-bottom:1rem; }
        .form-group label { display:block; margin-bottom:.4rem; font-weight:600; font-size:.9rem; }
        .form-group select, .form-group input { width:100%; padding:.65rem .85rem; border:1.5px solid var(--border-color); border-radius:var(--radius-md); font-size:.95rem; font-family:var(--font-main); }
        .form-group select:focus { outline:none; border-color:var(--primary-purple); }

        .two-col { display:grid; grid-template-columns:320px 1fr; gap:1.5rem; align-items:start; }

        /* student cards */
        .student-card { padding:1rem; margin-bottom:.5rem; background:var(--background-main); border-radius:var(--radius-md); cursor:pointer; border:2px solid transparent; transition:all .2s; }
        .student-card:hover { border-color:var(--primary-purple); background:#f5f3ff; }
        .student-card.selected { border-color:var(--primary-purple); background:#ede9fe; }
        .student-name { font-weight:700; }
        .student-meta { font-size:.82rem; color:var(--text-secondary); margin-top:.2rem; }

        /* badges */
        .sec-badge { display:inline-block; font-size:.72rem; font-weight:700; padding:.1rem .5rem; border-radius:.8rem; margin-left:.3rem; }
        .sec-badge.has  { background:#ede9fe; color:#5b21b6; }
        .sec-badge.none { background:#f3f4f6; color:#6b7280; }
        .status-pill { padding:.2rem .65rem; border-radius:1rem; font-size:.78rem; font-weight:700; }
        .status-draft     { background:#fef3c7; color:#92400e; }
        .status-finalized { background:#dcfce7; color:#166534; }

        /* section info panel */
        .section-info-box { background:linear-gradient(135deg,#ede9fe,#ddd6fe); border-radius:var(--radius-md); padding:1rem 1.25rem; margin-bottom:1rem; }
        .section-info-box h3 { margin:0 0 .25rem; font-size:1rem; color:#4c1d95; }
        .section-info-box p  { margin:.2rem 0 0; font-size:.82rem; color:#5b21b6; }

        /* load items */
        .load-item { display:flex; align-items:center; gap:1rem; padding:.85rem 1rem; background:var(--background-main); border-radius:var(--radius-md); margin-bottom:.5rem; }
        .load-item .sub-code { font-family:var(--font-mono); font-weight:700; font-size:.85rem; color:var(--primary-purple); min-width:72px; }
        .load-item .sub-info { flex:1; }
        .load-item .sub-name { font-weight:600; font-size:.9rem; }
        .load-item .sub-meta { font-size:.78rem; color:var(--text-secondary); }
        .load-item .sub-sched{ font-size:.75rem; color:var(--secondary-blue); margin-top:.15rem; }

        .empty-box { text-align:center; padding:2rem; color:var(--text-secondary); }
        .empty-box .ei { font-size:2.5rem; margin-bottom:.5rem; }

        .filter-bar { display:flex; flex-direction:column; gap:.5rem; margin-bottom:1rem; }
        .filter-bar select, .filter-bar input { width:100%; padding:.5rem .75rem; border:1.5px solid var(--border-color); border-radius:var(--radius-md); font-size:.88rem; box-sizing:border-box; }

        .btn-xs { padding:.3rem .75rem; font-size:.78rem; border-radius:var(--radius-sm); }
        .btn-danger  { background:#ef4444; color:#fff; }
        .btn-success { background:var(--secondary-green); color:#fff; }
        .units-total { font-size:.85rem; font-weight:600; color:var(--text-secondary); margin-top:.5rem; }

        @media(max-width:900px){ .two-col{ grid-template-columns:1fr; } }
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
                    <span>Registrar Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="applications.php" class="nav-item"><span class="nav-icon">📋</span><span>Applications</span></a>
                    <a href="manage_loads.php" class="nav-item active"><span class="nav-icon">📚</span><span>Study Loads</span></a>
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
                    <h1>Study Load Management</h1>
                <p class="page-subtitle">Assign sections and subjects to students</p>
            </div>
        </header>

        <div class="two-col">

            <!-- LEFT: Student list -->
            <div class="content-card">
                <div class="card-header"><h2 class="card-title">Students</h2></div>
                <div style="padding:1rem;">
                    <div class="filter-bar">
                        <input type="text" id="studentSearch" placeholder="🔍 Search name or ID…" oninput="filterStudents()">
                        <select id="filterCourse" onchange="filterStudents()">
                            <option value="">All Courses</option>
                        </select>
                        <select id="filterYear" onchange="filterStudents()">
                            <option value="">All Year Levels</option>
                            <option>1st Year</option>
                            <option>2nd Year</option>
                            <option>3rd Year</option>
                            <option>4th Year</option>
                        </select>
                    </div>
                    <div id="studentsList" style="max-height:500px;overflow-y:auto;"></div>
                </div>
            </div>

            <!-- RIGHT: Detail panels -->
            <div id="rightPanel">
                <!-- Section Assignment -->
                <div class="content-card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <h2 class="card-title">Section Assignment</h2>
                        <span id="selectedStudentName" style="font-size:.88rem;color:var(--text-secondary);">No student selected</span>
                    </div>
                    <div id="sectionPanel" style="padding:1rem;">
                        <div class="empty-box"><div class="ei">👆</div><p>Select a student from the left</p></div>
                    </div>
                </div>

                <!-- Subjects in Load -->
                <div class="content-card" id="subjectsCard" style="display:none;">
                    <div class="card-header">
                        <h2 class="card-title">Subjects in Load</h2>
                        <div style="display:flex;gap:.5rem;align-items:center;">
                            <span id="loadStatusBadge" class="status-pill status-draft">Draft</span>
                            <button class="btn btn-xs btn-success" onclick="finalizeLoad()" id="finalizeBtn">✔ Finalize</button>
                        </div>
                    </div>
                    <div style="padding:1rem;">
                        <div id="loadSubjectsList"></div>
                        <div class="units-total" id="unitsTotalRow"></div>
                        <div style="margin-top:1rem;">
                            <button class="btn btn-primary btn-xs" onclick="openAddSubjectModal()">➕ Add Subject</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- MODAL: Assign Section -->
<div id="sectionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="sectionModalTitle">Assign Section</h2>
            <button class="modal-close" onclick="closeSectionModal()">✕</button>
        </div>
        <div class="form-group">
            <label>Filter by Course</label>
            <select id="modalFilterCourse" onchange="filterSectionOptions()">
                <option value="">All Courses</option>
            </select>
        </div>
        <div class="form-group">
            <label>Filter by Year Level</label>
            <select id="modalFilterYear" onchange="filterSectionOptions()">
                <option value="">All Year Levels</option>
                <option>1st Year</option>
                <option>2nd Year</option>
                <option>3rd Year</option>
                <option>4th Year</option>
            </select>
        </div>
        <div class="form-group">
            <label>Select Section *</label>
            <select id="modalSectionId">
                <option value="">-- Choose a section --</option>
            </select>
        </div>
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                <input type="checkbox" id="autoLoadSubjects" checked style="width:auto;">
                Auto-load all section subjects into study load
            </label>
        </div>
        <div style="display:flex;gap:1rem;margin-top:1.5rem;">
            <button class="btn btn-primary" style="flex:1" onclick="assignSection()">Assign Section</button>
            <button class="btn" style="flex:1" onclick="closeSectionModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- MODAL: Add Subject -->
<div id="subjectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Subject to Load</h2>
            <button class="modal-close" onclick="closeSubjectModal()">✕</button>
        </div>
        <div class="form-group">
            <label>Subject *</label>
            <select id="addSubjectId">
                <option value="">-- Select a subject --</option>
            </select>
        </div>
        <div style="display:flex;gap:1rem;margin-top:1.5rem;">
            <button class="btn btn-primary" style="flex:1" onclick="addSingleSubject()">Add Subject</button>
            <button class="btn" style="flex:1" onclick="closeSubjectModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
/* ── State ── */
let allStudents    = [];
let allSections    = [];
let allSubjects    = [];
let selectedId     = null;
let currentLoad    = [];
let currentSection = null;

/* ════════════════════════════════
   BOOT
════════════════════════════════ */
async function boot() {
    try {
        const [sr, secR, subR] = await Promise.all([
            fetch('../../api/registrar/get_students.php'),
            fetch('../../api/registrar/get_sections.php?status=active'),
            fetch('../../api/registrar/get_subjects.php')
        ]);

        const sd   = await sr.json();
        const secd = await secR.json();
        const subd = await subR.json();

        allStudents = sd.students   || [];
        allSections = secd.sections || [];
        allSubjects = subd.subjects || [];

        // Populate course filters from courses API
        let courseNames = [];
        try {
            const cr = await fetch('../../api/registrar/get_courses.php');
            const cd = await cr.json();
            if (cd.success && cd.courses.length > 0) {
                courseNames = cd.courses.map(c => c.course_name);
            }
        } catch(e) {}
        if (!courseNames.length) {
            courseNames = [...new Set(allStudents.map(s => s.course).filter(Boolean))];
        }
        ['filterCourse', 'modalFilterCourse'].forEach(elId => {
            const sel = document.getElementById(elId);
            sel.innerHTML = '<option value="">All Courses</option>';
            courseNames.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c; opt.textContent = c;
                sel.appendChild(opt);
            });
        });

        // Populate subject modal dropdown
        const subSel = document.getElementById('addSubjectId');
        allSubjects.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.subject_code + ' – ' + s.subject_name + ' (' + s.units + ' units)';
            subSel.appendChild(opt);
        });

        renderStudents(allStudents);

    } catch (err) {
        console.error('Boot error:', err);
        document.getElementById('studentsList').innerHTML =
            '<div class="empty-box"><div class="ei">⚠️</div><p>Failed to load data. Check console.</p></div>';
    }
}

/* ════════════════════════════════
   STUDENT LIST
════════════════════════════════ */
function filterStudents() {
    const q   = document.getElementById('studentSearch').value.toLowerCase();
    const crs = document.getElementById('filterCourse').value;
    const yr  = document.getElementById('filterYear').value;
    const filtered = allStudents.filter(s =>
        (!q   || s.name.toLowerCase().includes(q) || (s.student_id || '').toLowerCase().includes(q)) &&
        (!crs || s.course === crs) &&
        (!yr  || s.year_level === yr)
    );
    renderStudents(filtered);
}

function renderStudents(list) {
    const container = document.getElementById('studentsList');
    if (!list.length) {
        container.innerHTML = '<div class="empty-box"><div class="ei">👥</div><p>No students found</p></div>';
        return;
    }
    container.innerHTML = list.map(s => {
        const badge = s.section_code
            ? '<span class="sec-badge has">' + s.section_code + '</span>'
            : '<span class="sec-badge none">No section</span>';
        const isSelected = (selectedId === s.id) ? ' selected' : '';
        return '<div class="student-card' + isSelected + '" id="sc-' + s.id + '" onclick="selectStudent(' + s.id + ')">' +
               '  <div class="student-name">' + escHtml(s.name) + ' ' + badge + '</div>' +
               '  <div class="student-meta">' + escHtml(s.student_id || '—') + ' &nbsp;·&nbsp; ' + escHtml(s.course || '—') + ' ' + escHtml(s.year_level || '') + '</div>' +
               '</div>';
    }).join('');
}

/* ════════════════════════════════
   SELECT STUDENT
════════════════════════════════ */
async function selectStudent(id) {
    selectedId = id;

    // Highlight the clicked card immediately (no event dependency)
    document.querySelectorAll('.student-card').forEach(c => c.classList.remove('selected'));
    const card = document.getElementById('sc-' + id);
    if (card) card.classList.add('selected');

    const st = allStudents.find(s => s.id === id);
    document.getElementById('selectedStudentName').textContent = st ? st.name : '';

    // Show loading state
    document.getElementById('sectionPanel').innerHTML =
        '<div class="empty-box"><div class="ei">⏳</div><p>Loading…</p></div>';
    document.getElementById('subjectsCard').style.display = 'block';
    document.getElementById('loadSubjectsList').innerHTML =
        '<div class="empty-box"><div class="ei">⏳</div><p>Loading…</p></div>';

    try {
        const res  = await fetch('../../api/registrar/get_student_load.php?student_id=' + id);
        const data = await res.json();
        currentLoad    = data.load    || [];
        currentSection = data.section || null;
        renderSectionPanel(st);
        renderLoadSubjects();
    } catch (err) {
        console.error('Load error:', err);
        document.getElementById('sectionPanel').innerHTML =
            '<div class="empty-box"><div class="ei">⚠️</div><p>Failed to load student data.</p></div>';
    }
}

/* ════════════════════════════════
   SECTION PANEL
════════════════════════════════ */
function renderSectionPanel(student) {
    const panel = document.getElementById('sectionPanel');
    if (currentSection) {
        const meta = [currentSection.course, currentSection.year_level, currentSection.semester, currentSection.school_year]
            .filter(Boolean).join(' · ');
        const roomLine = currentSection.room
            ? '<p style="margin-top:.3rem;">🚪 ' + escHtml(currentSection.room) + (currentSection.building ? ', ' + escHtml(currentSection.building) : '') + '</p>'
            : '';
        panel.innerHTML =
            '<div class="section-info-box">' +
            '  <h3>📂 ' + escHtml(currentSection.section_name) + ' <span style="font-size:.8rem;opacity:.8;">(' + escHtml(currentSection.section_code) + ')</span></h3>' +
            '  <p>' + escHtml(meta) + '</p>' + roomLine +
            '</div>' +
            '<div style="display:flex;gap:.75rem;flex-wrap:wrap;">' +
            '  <button class="btn btn-primary btn-xs" onclick="openSectionModal()">✏️ Change Section</button>' +
            '  <button class="btn btn-xs btn-danger" onclick="removeSection()">✕ Remove Section</button>' +
            '</div>';
    } else {
        panel.innerHTML =
            '<div class="empty-box" style="padding:1.25rem;">' +
            '  <div class="ei">📂</div>' +
            '  <p>No section assigned yet</p>' +
            '  <button class="btn btn-primary btn-xs" style="margin-top:.75rem;" onclick="openSectionModal()">+ Assign Section</button>' +
            '</div>';
    }
}

/* ════════════════════════════════
   SUBJECTS IN LOAD
════════════════════════════════ */
function renderLoadSubjects() {
    const list   = document.getElementById('loadSubjectsList');
    const pill   = document.getElementById('loadStatusBadge');
    const tot    = document.getElementById('unitsTotalRow');
    const finBtn = document.getElementById('finalizeBtn');

    if (!currentLoad.length) {
        list.innerHTML = '<div class="empty-box"><div class="ei">📚</div><p>No subjects in load yet</p></div>';
        tot.textContent = '';
        pill.textContent = 'No Load';
        pill.className = 'status-pill status-draft';
        finBtn.style.display = 'inline-block';
        return;
    }

    const status = currentLoad[0].status || 'draft';
    pill.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    pill.className = 'status-pill status-' + status;
    finBtn.style.display = (status === 'finalized') ? 'none' : 'inline-block';

    const totalUnits = currentLoad.reduce((sum, r) => sum + parseInt(r.units || 0), 0);
    tot.textContent = 'Total: ' + currentLoad.length + ' subject(s) · ' + totalUnits + ' units';

    list.innerHTML = currentLoad.map(s => {
        const schedLine = s.schedule
            ? '<div class="sub-sched">📅 ' + escHtml(s.schedule) + (s.room ? ' · 🚪 ' + escHtml(s.room) : '') + '</div>'
            : '';
        const removeBtn = (status !== 'finalized')
            ? '<button class="btn btn-xs btn-danger" onclick="removeSubject(' + s.subject_id + ')">✕</button>'
            : '';
        return '<div class="load-item">' +
               '  <div class="sub-code">' + escHtml(s.subject_code) + '</div>' +
               '  <div class="sub-info">' +
               '    <div class="sub-name">' + escHtml(s.subject_name) + '</div>' +
               '    <div class="sub-meta">' + s.units + ' units' + (s.teacher_name ? ' · 👨‍🏫 ' + escHtml(s.teacher_name) : '') + '</div>' +
               schedLine +
               '  </div>' +
               removeBtn +
               '</div>';
    }).join('');
}

/* ════════════════════════════════
   SECTION MODAL
════════════════════════════════ */
function openSectionModal() {
    if (!selectedId) return;
    const student = allStudents.find(s => s.id === selectedId);
    document.getElementById('sectionModalTitle').textContent = 'Assign Section — ' + (student ? student.name : '');
    document.getElementById('modalFilterCourse').value = (student && student.course)     || '';
    document.getElementById('modalFilterYear').value   = (student && student.year_level) || '';
    filterSectionOptions();
    document.getElementById('sectionModal').classList.add('active');
}
function closeSectionModal() { document.getElementById('sectionModal').classList.remove('active'); }

function filterSectionOptions() {
    const crs = document.getElementById('modalFilterCourse').value;
    const yr  = document.getElementById('modalFilterYear').value;
    const sel = document.getElementById('modalSectionId');
    sel.innerHTML = '<option value="">-- Choose a section --</option>';
    allSections
        .filter(s => (!crs || s.course === crs) && (!yr || s.year_level === yr))
        .forEach(s => {
            const extra = [s.semester, s.school_year].filter(Boolean).join(' · ');
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.section_name + ' (' + s.section_code + ')' + (extra ? ' · ' + extra : '') + '  [' + (s.subject_count || 0) + ' subjects]';
            sel.appendChild(opt);
        });
    if (currentSection) sel.value = currentSection.id;
}

async function assignSection() {
    const section_id = document.getElementById('modalSectionId').value;
    if (!section_id) { alert('Please select a section'); return; }
    const autoLoad = document.getElementById('autoLoadSubjects').checked;

    try {
        const res  = await fetch('../../api/registrar/assign_section_load.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ student_id: selectedId, section_id: parseInt(section_id), auto_load_subjects: autoLoad })
        });
        const data = await res.json();
        if (data.success) {
            closeSectionModal();
            // Refresh student list + selected student
            const sres = await fetch('../../api/registrar/get_students.php');
            allStudents = (await sres.json()).students || [];
            filterStudents();   // re-render list (keeps selected highlight via sc-{id})
            await refreshStudentLoad();
            alert(data.message || 'Section assigned!');
        } else {
            alert('Error: ' + data.message);
        }
    } catch (err) { alert('Request failed. Check console.'); console.error(err); }
}

async function removeSection() {
    if (!confirm('Remove section from this student? Their draft study load will be cleared.')) return;
    try {
        const res  = await fetch('../../api/registrar/assign_section_load.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ student_id: selectedId, section_id: null, auto_load_subjects: false })
        });
        const data = await res.json();
        if (data.success) {
            const sres = await fetch('../../api/registrar/get_students.php');
            allStudents = (await sres.json()).students || [];
            filterStudents();
            await refreshStudentLoad();
        } else { alert('Error: ' + data.message); }
    } catch (err) { alert('Request failed.'); console.error(err); }
}

/* ════════════════════════════════
   ADD / REMOVE SUBJECT
════════════════════════════════ */
function openAddSubjectModal() {
    document.getElementById('addSubjectId').value = '';
    document.getElementById('subjectModal').classList.add('active');
}
function closeSubjectModal() { document.getElementById('subjectModal').classList.remove('active'); }

async function addSingleSubject() {
    const subjectId = document.getElementById('addSubjectId').value;
    if (!subjectId) { alert('Please select a subject'); return; }
    try {
        const res  = await fetch('../../api/registrar/add_subject_to_load.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ student_id: selectedId, subject_id: parseInt(subjectId) })
        });
        const data = await res.json();
        if (data.success) { closeSubjectModal(); await refreshStudentLoad(); }
        else alert('Error: ' + data.message);
    } catch (err) { alert('Request failed.'); console.error(err); }
}

async function removeSubject(subjectId) {
    if (!confirm('Remove this subject from the load?')) return;
    try {
        const res  = await fetch('../../api/registrar/remove_subject_from_load.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ student_id: selectedId, subject_id: subjectId })
        });
        const data = await res.json();
        if (data.success) await refreshStudentLoad();
        else alert('Error: ' + data.message);
    } catch (err) { alert('Request failed.'); console.error(err); }
}

/* ════════════════════════════════
   FINALIZE
════════════════════════════════ */
async function finalizeLoad() {
    if (!confirm('Finalize this study load? The student will be notified and subjects cannot be removed.')) return;
    try {
        const res  = await fetch('../../api/registrar/finalize_load.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ student_id: selectedId })
        });
        const data = await res.json();
        if (data.success) { await refreshStudentLoad(); alert('Study load finalized!'); }
        else alert('Error: ' + data.message);
    } catch (err) { alert('Request failed.'); console.error(err); }
}

/* ════════════════════════════════
   HELPERS
════════════════════════════════ */
async function refreshStudentLoad() {
    try {
        const res  = await fetch('../../api/registrar/get_student_load.php?student_id=' + selectedId);
        const data = await res.json();
        currentLoad    = data.load    || [];
        currentSection = data.section || null;
        const st = allStudents.find(s => s.id === selectedId);
        renderSectionPanel(st);
        renderLoadSubjects();
    } catch (err) { console.error('Refresh error:', err); }
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/* ── Start ── */
boot();
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
<script>
/* mobile-fix: back button for split-layout pages */
(function(){
  var splitLayout = document.querySelector(".split-layout, .two-col, .id-layout");
  if (!splitLayout) return;
  var panels = splitLayout.children;
  if (panels.length < 2) return;
  var listPanel = panels[0], detailPanel = panels[1];
  var btn = document.createElement("button");
  btn.className = "mobile-back-btn";
  btn.innerHTML = "2190 Back to List";
  detailPanel.insertBefore(btn, detailPanel.firstChild);
  btn.addEventListener("click", function(){
    detailPanel.classList.remove("visible");
    listPanel.style.display = "";
  });
  window.addEventListener("resize", function(){
    if (window.innerWidth > 768) listPanel.style.display = "";
  });
})();
</script>

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