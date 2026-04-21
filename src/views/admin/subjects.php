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
    <title>Subjects - Admin Dashboard</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: var(--radius-lg); max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: var(--radius-md); }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.85rem; font-weight: 600; }
        .status-active { background: var(--status-approved); color: white; }
        .status-inactive { background: var(--status-rejected); color: white; }
        .tab-container { display: flex; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; }
        .tab { padding: 0.75rem 1.5rem; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; font-weight: 500; color: #6b7280; }
        .tab.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
        .filter-bar { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; align-items: center; }
        .filter-bar select, .filter-bar input { padding: 0.5rem; border: 1px solid #ddd; border-radius: var(--radius-md); }
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
                    <a href="subjects.php" class="nav-item active"><span class="nav-icon">📚</span><span>Subjects</span></a>
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
                    <h1>Subject Management</h1>
                    <p class="page-subtitle">Manage subjects and teacher specialties</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openAddSubjectModal()">➕ Add Subject</button>
                </div>
            </header>
            
            <div class="tab-container">
                <div class="tab active" onclick="switchTab('subjects')">📚 Subjects</div>
                <div class="tab" onclick="switchTab('specialties')">👨‍🏫 Teacher Specialties</div>
            </div>

            <div id="subjectsTab" class="content-card">
                <div class="filter-bar">
                    <select id="filterCourse" onchange="loadSubjects()">
                        <option value="">All Courses</option>
                    </select>
                    <select id="filterYear" onchange="loadSubjects()">
                        <option value="">All Year Levels</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                    <select id="filterStatus" onchange="loadSubjects()">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <input type="text" id="searchSubject" placeholder="Search by code or name..." style="flex: 1; min-width: 200px;" oninput="loadSubjects()">
                </div>
                <div id="subjectsTable">Loading...</div>
            </div>

            <div id="specialtiesTab" class="content-card" style="display: none;">
                <div class="filter-bar">
                    <select id="filterTeacher" onchange="loadSpecialties()">
                        <option value="">All Teachers</option>
                    </select>
                    <select id="filterSubject" onchange="loadSpecialties()">
                        <option value="">All Subjects</option>
                    </select>
                    <button class="btn btn-primary" onclick="openAssignSpecialtyModal()">➕ Assign Specialty</button>
                </div>
                <div id="specialtiesTable">Loading...</div>
            </div>
        </main>
    </div>

    <!-- Subject Modal -->
    <div id="subjectModal" class="modal">
        <div class="modal-content">
            <h2 id="subjectModalTitle">Add Subject</h2>
            <form id="subjectForm" onsubmit="saveSubject(event)">
                <input type="hidden" id="subjectId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Subject Code *</label>
                        <input type="text" id="subjectCode" required placeholder="e.g., CS101">
                    </div>
                    <div class="form-group">
                        <label>Units *</label>
                        <input type="number" id="units" required min="1" max="6" value="3">
                    </div>
                </div>

                <div class="form-group">
                    <label>Subject Type *</label>
                    <div style="display:flex; gap:.5rem; margin-top:.25rem;">
                        <label style="display:flex; align-items:center; gap:.4rem; cursor:pointer; font-weight:400;
                            flex:1; padding:.55rem .75rem; border:1.5px solid #d1d5db; border-radius:var(--radius-md);
                            transition:all .15s;" id="typeMajorLabel">
                            <input type="radio" name="subjectType" value="major" id="typeMajor" onchange="onTypeChange()"
                                style="width:auto; accent-color:var(--primary-purple);" checked>
                            🎓 Major
                            <span style="font-size:.75rem; color:#6b7280; margin-left:.25rem;">(6h LAB+LEC total)</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:.4rem; cursor:pointer; font-weight:400;
                            flex:1; padding:.55rem .75rem; border:1.5px solid #d1d5db; border-radius:var(--radius-md);
                            transition:all .15s;" id="typeMinorLabel">
                            <input type="radio" name="subjectType" value="minor" id="typeMinor" onchange="onTypeChange()"
                                style="width:auto; accent-color:var(--primary-purple);">
                            📖 Minor
                            <span style="font-size:.75rem; color:#6b7280; margin-left:.25rem;">(3h LAB+LEC total)</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Subject Name *</label>
                    <input type="text" id="subjectName" required placeholder="e.g., Introduction to Programming">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea id="description" placeholder="Brief description of the subject"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Course Scope</label>
                        <div style="display:flex; gap:.5rem; margin-top:.25rem;">
                            <label style="display:flex; align-items:center; gap:.4rem; cursor:pointer; font-weight:400;
                                flex:1; padding:.55rem .75rem; border:1.5px solid #d1d5db; border-radius:var(--radius-md);
                                transition:all .15s;" id="scopeAllLabel">
                                <input type="radio" name="courseScope" value="all" id="scopeAll" onchange="onScopeChange()"
                                    style="width:auto; accent-color:var(--primary-purple);">
                                🌐 All Courses
                            </label>
                            <label style="display:flex; align-items:center; gap:.4rem; cursor:pointer; font-weight:400;
                                flex:1; padding:.55rem .75rem; border:1.5px solid #d1d5db; border-radius:var(--radius-md);
                                transition:all .15s;" id="scopeSpecificLabel">
                                <input type="radio" name="courseScope" value="specific" id="scopeSpecific" onchange="onScopeChange()"
                                    style="width:auto; accent-color:var(--primary-purple);">
                                🎓 Specific Course
                            </label>
                        </div>
                        <div id="scopeHint" style="font-size:.76rem; margin-top:.35rem; color:var(--text-secondary);"></div>
                    </div>
                    <div class="form-group">
                        <label>Year Level</label>
                        <select id="yearLevel">
                            <option value="">Select Year Level</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                </div>

                <!-- Course dropdown — shown only when Specific Course is selected -->
                <div class="form-group" id="courseFieldWrap" style="display:none;">
                    <label>Course *</label>
                    <select id="course">
                        <option value="">— Select Course —</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Prerequisites</label>
                    <input type="text" id="prerequisites" placeholder="e.g., MATH101, CS100">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select id="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Subject</button>
                    <button type="button" class="btn" onclick="closeSubjectModal()" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Specialty Modal -->
    <div id="specialtyModal" class="modal">
        <div class="modal-content">
            <h2>Assign Teacher Specialty</h2>
            <form id="specialtyForm" onsubmit="saveSpecialty(event)">
                <input type="hidden" id="specialtyId">
                
                <div class="form-group">
                    <label>Teacher *</label>
                    <select id="teacherId" required>
                        <option value="">Select Teacher</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Subject *</label>
                    <select id="specialtySubjectId" required>
                        <option value="">Select Subject</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Proficiency Level</label>
                    <select id="proficiencyLevel">
                        <option value="beginner">Beginner</option>
                        <option value="intermediate" selected>Intermediate</option>
                        <option value="advanced">Advanced</option>
                        <option value="expert">Expert</option>
                    </select>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="isPrimary" style="width: auto;">
                        <span>Set as Primary Specialty</span>
                    </label>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Assign Specialty</button>
                    <button type="button" class="btn" onclick="closeSpecialtyModal()" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentTab = 'subjects';

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            if (tab === 'subjects') {
                document.getElementById('subjectsTab').style.display = 'block';
                document.getElementById('specialtiesTab').style.display = 'none';
                loadSubjects();
            } else {
                document.getElementById('subjectsTab').style.display = 'none';
                document.getElementById('specialtiesTab').style.display = 'block';
                loadSpecialties();
            }
        }

        async function loadSubjects() {
            const course = document.getElementById('filterCourse').value;
            const year = document.getElementById('filterYear').value;
            const status = document.getElementById('filterStatus').value;
            const search = document.getElementById('searchSubject').value;

            const params = new URLSearchParams({course, year_level: year, status, search});
            const response = await fetch(`../../api/admin/get_subjects.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                let html = '<table class="data-table"><thead><tr><th>Code</th><th>Subject Name</th><th>Type</th><th>Units</th><th>Course</th><th>Year</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
                
                if (data.subjects.length === 0) {
                    html += '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No subjects found</td></tr>';
                } else {
                    data.subjects.forEach(s => {
                        const statusClass = s.status === 'active' ? 'status-active' : 'status-inactive';
                        const typeBadge = s.subject_type === 'minor'
                            ? `<span style="background:#dbeafe;color:#1e40af;font-size:.75rem;font-weight:700;padding:.2rem .6rem;border-radius:.8rem;">📖 Minor</span>`
                            : `<span style="background:#f3e8ff;color:#6d28d9;font-size:.75rem;font-weight:700;padding:.2rem .6rem;border-radius:.8rem;">🎓 Major</span>`;
                        html += `<tr>
                            <td><strong>${s.subject_code}</strong></td>
                            <td>${s.subject_name}</td>
                            <td>${typeBadge}</td>
                            <td>${s.units}</td>
                            <td>${s.course ? `<span style="font-size:.8rem;">${s.course}</span>` : `<span style="background:#dbeafe;color:#1e40af;font-size:.72rem;font-weight:700;padding:.15rem .5rem;border-radius:.8rem;">🌐 All Courses</span>`}</td>
                            <td>${s.year_level || '—'}</td>
                            <td><span class="status-badge ${statusClass}">${s.status}</span></td>
                            <td>
                                <button class="btn btn-sm" onclick='editSubject(${JSON.stringify(s)})'>Edit</button>
                                <button class="btn btn-sm" onclick="deleteSubject(${s.id}, '${s.subject_code}')" style="background: var(--status-rejected);">Delete</button>
                            </td>
                        </tr>`;
                    });
                }
                
                html += '</tbody></table>';
                document.getElementById('subjectsTable').innerHTML = html;
            }
        }

        async function loadSpecialties() {
            const teacher = document.getElementById('filterTeacher').value;
            const subject = document.getElementById('filterSubject').value;

            const params = new URLSearchParams({teacher_id: teacher, subject_id: subject});
            const response = await fetch(`../../api/admin/get_specialties.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                let html = '<table class="data-table"><thead><tr><th>Teacher</th><th>Subject</th><th>Subject Code</th><th>Proficiency</th><th>Primary</th><th>Assigned Date</th><th>Actions</th></tr></thead><tbody>';
                
                if (data.specialties.length === 0) {
                    html += '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No specialties assigned</td></tr>';
                } else {
                    data.specialties.forEach(sp => {
                        const proficiencyColors = {
                            'beginner': '#94a3b8',
                            'intermediate': '#3b82f6',
                            'advanced': '#8b5cf6',
                            'expert': '#f59e0b'
                        };
                        html += `<tr>
                            <td>${sp.teacher_name}</td>
                            <td>${sp.subject_name}</td>
                            <td><strong>${sp.subject_code}</strong></td>
                            <td><span style="background: ${proficiencyColors[sp.proficiency_level]}; color: white; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.85rem;">${sp.proficiency_level}</span></td>
                            <td>${sp.is_primary ? '⭐ Yes' : 'No'}</td>
                            <td>${new Date(sp.assigned_date).toLocaleDateString()}</td>
                            <td>
                                <button class="btn btn-sm" onclick="deleteSpecialty(${sp.id}, '${sp.teacher_name}', '${sp.subject_code}')" style="background: var(--status-rejected);">Remove</button>
                            </td>
                        </tr>`;
                    });
                }
                
                html += '</tbody></table>';
                document.getElementById('specialtiesTable').innerHTML = html;
            }
        }

        async function loadFilters() {
            // Load courses from courses table for both filter bar and subject modal
            let courseNames = [];
            try {
                const cr = await fetch('../../api/admin/get_courses.php');
                const cd = await cr.json();
                if (cd.success && cd.courses.length > 0) {
                    courseNames = cd.courses.map(c => ({ name: c.course_name, code: c.course_code }));
                }
            } catch(e) {}

            // Populate filter bar course dropdown
            const courseSelect = document.getElementById('filterCourse');
            courseSelect.innerHTML = '<option value="">All Courses</option><option value="__none__">🌐 GE / Shared (No Specific Course)</option>';
            if (courseNames.length > 0) {
                courseNames.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.name;
                    opt.textContent = `${c.name} (${c.code})`;
                    courseSelect.appendChild(opt);
                });
            } else {
                // Fallback: pull unique course values from existing subjects
                const fallback = await fetch('../../api/admin/get_subjects.php');
                const fd = await fallback.json();
                if (fd.success) {
                    const unique = [...new Set(fd.subjects.map(s => s.course).filter(Boolean))];
                    unique.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c; opt.textContent = c;
                        courseSelect.appendChild(opt);
                    });
                }
            }

            // Populate course dropdown inside the subject add/edit modal
            const modalCourseSelect = document.getElementById('course');
            modalCourseSelect.innerHTML = '<option value="">— Select Course —</option>';
            if (courseNames.length > 0) {
                courseNames.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.name;
                    opt.textContent = `${c.name} (${c.code})`;
                    modalCourseSelect.appendChild(opt);
                });
            }

            // Load subjects for specialty filter
            const response = await fetch('../../api/admin/get_subjects.php');
            const data = await response.json();
            if (data.success) {
                const subjectSelect = document.getElementById('filterSubject');
                data.subjects.forEach(s => {
                    const option = document.createElement('option');
                    option.value = s.id;
                    option.textContent = `${s.subject_code} - ${s.subject_name}`;
                    subjectSelect.appendChild(option);
                });

                const modalSubjectSelect = document.getElementById('specialtySubjectId');
                data.subjects.forEach(s => {
                    const option = document.createElement('option');
                    option.value = s.id;
                    option.textContent = `${s.subject_code} - ${s.subject_name}`;
                    modalSubjectSelect.appendChild(option);
                });
            }

            // Load teachers
            const teacherResp = await fetch('../../api/admin/get_users.php?role=teacher');
            const teacherData = await teacherResp.json();
            if (teacherData.success) {
                const teacherFilter = document.getElementById('filterTeacher');
                const teacherModal = document.getElementById('teacherId');
                
                teacherData.users.forEach(t => {
                    const option1 = document.createElement('option');
                    option1.value = t.id;
                    option1.textContent = t.name;
                    teacherFilter.appendChild(option1);

                    const option2 = document.createElement('option');
                    option2.value = t.id;
                    option2.textContent = t.name;
                    teacherModal.appendChild(option2);
                });
            }
        }

        function onScopeChange() {
            const isSpecific = document.getElementById('scopeSpecific').checked;
            const wrap  = document.getElementById('courseFieldWrap');
            const hint  = document.getElementById('scopeHint');
            const allLbl  = document.getElementById('scopeAllLabel');
            const specLbl = document.getElementById('scopeSpecificLabel');

            wrap.style.display = isSpecific ? 'block' : 'none';

            if (isSpecific) {
                hint.textContent = 'This subject will only appear for the selected course.';
                allLbl.style.borderColor  = '#d1d5db';
                specLbl.style.borderColor = 'var(--primary-purple)';
                specLbl.style.background  = '#f5f3ff';
                allLbl.style.background   = '';
                document.getElementById('course').required = true;
            } else {
                hint.textContent = 'This subject will be available across all courses (e.g. GE subjects, electives).';
                specLbl.style.borderColor = '#d1d5db';
                allLbl.style.borderColor  = 'var(--primary-purple)';
                allLbl.style.background   = '#f5f3ff';
                specLbl.style.background  = '';
                document.getElementById('course').required = false;
                document.getElementById('course').value    = '';
            }
        }

        function setScopeUI(courseValue) {
            if (courseValue) {
                document.getElementById('scopeSpecific').checked = true;
            } else {
                document.getElementById('scopeAll').checked = true;
            }
            onScopeChange();
        }

        function onTypeChange() {
            const isMajor = document.getElementById('typeMajor').checked;
            const majorLbl = document.getElementById('typeMajorLabel');
            const minorLbl = document.getElementById('typeMinorLabel');
            if (isMajor) {
                majorLbl.style.borderColor = 'var(--primary-purple)';
                majorLbl.style.background  = '#f5f3ff';
                minorLbl.style.borderColor = '#d1d5db';
                minorLbl.style.background  = '';
            } else {
                minorLbl.style.borderColor = 'var(--primary-purple)';
                minorLbl.style.background  = '#f5f3ff';
                majorLbl.style.borderColor = '#d1d5db';
                majorLbl.style.background  = '';
            }
        }

        function openAddSubjectModal() {
            document.getElementById('subjectModalTitle').textContent = 'Add Subject';
            document.getElementById('subjectForm').reset();
            document.getElementById('subjectId').value = '';
            document.getElementById('status').value = 'active';
            // Default to Major + All Courses
            document.getElementById('typeMajor').checked = true;
            onTypeChange();
            document.getElementById('scopeAll').checked = true;
            onScopeChange();
            document.getElementById('subjectModal').classList.add('active');
        }

        function editSubject(subject) {
            document.getElementById('subjectModalTitle').textContent = 'Edit Subject';
            document.getElementById('subjectId').value      = subject.id;
            document.getElementById('subjectCode').value    = subject.subject_code;
            document.getElementById('subjectName').value    = subject.subject_name;
            document.getElementById('description').value    = subject.description || '';
            document.getElementById('units').value          = subject.units;
            document.getElementById('yearLevel').value      = subject.year_level || '';
            document.getElementById('prerequisites').value  = subject.prerequisites || '';
            document.getElementById('status').value         = subject.status || 'active';
            // Set subject type
            const isMajor = !subject.subject_type || subject.subject_type === 'major';
            document.getElementById('typeMajor').checked = isMajor;
            document.getElementById('typeMinor').checked = !isMajor;
            onTypeChange();
            // Set scope and course
            document.getElementById('course').value = subject.course || '';
            setScopeUI(subject.course);
            document.getElementById('subjectModal').classList.add('active');
        }

        function closeSubjectModal() {
            document.getElementById('subjectModal').classList.remove('active');
        }

        async function saveSubject(e) {
            e.preventDefault();
            const isSpecific = document.getElementById('scopeSpecific').checked;
            const courseVal  = isSpecific ? document.getElementById('course').value : null;

            if (isSpecific && !courseVal) {
                alert('Please select a course, or switch to "All Courses".');
                return;
            }

            const data = {
                subject_id:    document.getElementById('subjectId').value || null,
                subject_code:  document.getElementById('subjectCode').value,
                subject_name:  document.getElementById('subjectName').value,
                description:   document.getElementById('description').value,
                units:         document.getElementById('units').value,
                subject_type:  document.getElementById('typeMajor').checked ? 'major' : 'minor',
                course:        courseVal,
                year_level:    document.getElementById('yearLevel').value,
                prerequisites: document.getElementById('prerequisites').value,
                status:        document.getElementById('status').value
            };
            
            const response = await fetch('../../api/admin/save_subject.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            if (result.success) {
                alert(result.message);
                closeSubjectModal();
                loadSubjects();
                loadFilters(); // Refresh filters
            } else {
                alert('Error: ' + result.message);
            }
        }

        async function deleteSubject(id, code) {
            if (!confirm(`Delete subject ${code}? This will also remove all teacher specialties for this subject.`)) return;
            
            const response = await fetch('../../api/admin/delete_subject.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({subject_id: id})
            });
            
            const result = await response.json();
            if (result.success) {
                alert(result.message);
                loadSubjects();
            } else {
                alert('Error: ' + result.message);
            }
        }

        function openAssignSpecialtyModal() {
            document.getElementById('specialtyForm').reset();
            document.getElementById('specialtyId').value = '';
            document.getElementById('specialtyModal').classList.add('active');
        }

        function closeSpecialtyModal() {
            document.getElementById('specialtyModal').classList.remove('active');
        }

        async function saveSpecialty(e) {
            e.preventDefault();
            const data = {
                teacher_id: document.getElementById('teacherId').value,
                subject_id: document.getElementById('specialtySubjectId').value,
                proficiency_level: document.getElementById('proficiencyLevel').value,
                is_primary: document.getElementById('isPrimary').checked ? 1 : 0
            };
            
            const response = await fetch('../../api/admin/save_specialty.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            if (result.success) {
                alert(result.message);
                closeSpecialtyModal();
                loadSpecialties();
            } else {
                alert('Error: ' + result.message);
            }
        }

        async function deleteSpecialty(id, teacher, subject) {
            if (!confirm(`Remove ${teacher}'s specialty in ${subject}?`)) return;
            
            const response = await fetch('../../api/admin/delete_specialty.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({specialty_id: id})
            });
            
            const result = await response.json();
            if (result.success) {
                alert(result.message);
                loadSpecialties();
            } else {
                alert('Error: ' + result.message);
            }
        }

        // Initialize
        loadFilters();
        loadSubjects();
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