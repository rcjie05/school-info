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
    <title>Sections - Admin Dashboard</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        /* ── Modal base ── */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #fff; padding: 2rem; border-radius: var(--radius-lg); width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-content.wide { max-width: 860px; }
        .modal-content.narrow { max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h2 { margin: 0; }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); }
        .modal-close:hover { color: var(--text-primary); }

        /* ── Forms ── */
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.65rem 0.85rem; border: 1.5px solid var(--border-color);
            border-radius: var(--radius-md); font-size: 0.95rem; font-family: var(--font-main);
            transition: border-color var(--transition-fast);
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary-purple); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }

        /* ── Filters ── */
        .filter-bar { display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; align-items: center; }
        .filter-bar select, .filter-bar input { padding: 0.5rem 0.75rem; border: 1.5px solid var(--border-color); border-radius: var(--radius-md); font-size: 0.9rem; }
        .filter-bar input[type=text] { flex: 1; min-width: 180px; }

        /* ── Tabs ── */
        .tab-container { display: flex; gap: 0; margin-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; }
        .tab { padding: 0.75rem 1.5rem; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; font-weight: 600; color: var(--text-secondary); transition: all var(--transition-fast); }
        .tab:hover { color: var(--primary-purple); }
        .tab.active { color: var(--primary-purple); border-bottom-color: var(--primary-purple); }

        /* ── Status badges ── */
        .badge { padding: 0.2rem 0.65rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 600; display: inline-block; }
        .badge-active   { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .badge-archived { background: #f3f4f6; color: #374151; }
        .badge-info     { background: #dbeafe; color: #1e40af; }
        .badge-purple   { background: #ede9fe; color: #5b21b6; }

        /* ── Section cards / table ── */
        .sections-grid { display: grid; gap: 1rem; }
        .section-row { background: #fff; border: 1.5px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 1.25rem; transition: box-shadow var(--transition-fast); }
        .section-row:hover { box-shadow: var(--shadow-md); }
        .section-code { font-family: var(--font-mono); font-weight: 700; font-size: 0.95rem; color: var(--primary-purple); background: #ede9fe; padding: 0.3rem 0.65rem; border-radius: var(--radius-sm); min-width: 72px; text-align: center; }
        .section-info { flex: 1; }
        .section-name { font-weight: 700; color: var(--text-primary); }
        .section-meta { font-size: 0.82rem; color: var(--text-secondary); margin-top: 0.2rem; }
        .section-stats { display: flex; gap: 0.5rem; }
        .section-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }

        /* ── Timetable grid ── */
        .timetable-wrap { overflow-x: auto; margin-top: 1rem; }
        .timetable { border-collapse: collapse; width: 100%; min-width: 700px; font-size: 0.85rem; }
        .timetable th, .timetable td { border: 1px solid #e5e7eb; padding: 0.5rem 0.75rem; text-align: center; }
        .timetable th { background: var(--primary-purple); color: #fff; font-weight: 600; }
        .timetable td.time-col { background: #f8f7ff; font-weight: 600; color: var(--text-secondary); white-space: nowrap; }
        .sched-cell { background: linear-gradient(135deg, #ede9fe, #ddd6fe); border-radius: 6px; padding: 0.35rem 0.5rem; cursor: pointer; }
        .sched-cell .sc-sub { font-weight: 700; color: var(--primary-purple); font-size: 0.8rem; }
        .sched-cell .sc-room { font-size: 0.73rem; color: var(--text-secondary); }
        .empty-day { color: #d1d5db; font-size: 0.8rem; }

        /* ── Subject list in detail panel ── */
        .subject-list { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem; }
        .subject-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.7rem 1rem; background: #f8f7ff; border-radius: var(--radius-md); }
        .subject-item .sub-code { font-family: var(--font-mono); font-size: 0.82rem; font-weight: 700; color: var(--primary-purple); min-width: 70px; }
        .subject-item .sub-info { flex: 1; }
        .subject-item .sub-name { font-weight: 600; font-size: 0.9rem; }
        .subject-item .sub-teacher { font-size: 0.78rem; color: var(--text-secondary); }

        /* ── Schedule list ── */
        .schedule-list { display: flex; flex-direction: column; gap: 0.4rem; margin-top: 0.5rem; }
        .schedule-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 1rem; background: #fff; border: 1.5px solid #e5e7eb; border-radius: var(--radius-md); }
        .schedule-item .day-badge { font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 0.4rem; min-width: 60px; text-align: center; }
        .day-Mon,.day-Fri { background:#dbeafe;color:#1e40af; }
        .day-Tue,.day-Thu { background:#fce7f3;color:#9d174d; }
        .day-Wed          { background:#d1fae5;color:#065f46; }
        .day-Sat,.day-Sun { background:#fef3c7;color:#92400e; }
        .schedule-item .sched-info { flex: 1; font-size: 0.88rem; }
        .schedule-item .sched-time { font-weight: 700; }
        .schedule-item .sched-room { font-size: 0.78rem; color: var(--text-secondary); }

        /* ── Panel layout ── */
        .detail-panel { display: none; margin-top: 1.5rem; background: #fff; border: 1.5px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.5rem; }
        .detail-panel.active { display: block; }
        .detail-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
        .panel-tabs { display: flex; gap: 0.5rem; }
        .panel-tab { padding: 0.45rem 1rem; border-radius: var(--radius-md); border: 1.5px solid var(--border-color); cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--text-secondary); transition: all var(--transition-fast); }
        .panel-tab.active, .panel-tab:hover { background: var(--primary-purple); color: #fff; border-color: var(--primary-purple); }

        /* ── btn sizes ── */
        .btn-xs { padding: 0.3rem 0.7rem; font-size: 0.78rem; border-radius: var(--radius-sm); }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        .btn-success { background: var(--secondary-green); color: #fff; }
        .btn-info { background: var(--secondary-blue); color: #fff; }

        /* ── Empty state ── */
        .empty-state { text-align: center; padding: 3rem; color: var(--text-secondary); }
        .empty-state .empty-icon { font-size: 3rem; margin-bottom: 0.5rem; }

        /* ── Conflict warning ── */
        .warning-badge { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; padding: 0.35rem 0.75rem; border-radius: var(--radius-md); font-size: 0.82rem; font-weight: 600; }

        /* section count pill */
        .count-pill { background: #ede9fe; color: var(--primary-purple); padding: 0.1rem 0.5rem; border-radius: 0.9rem; font-size: 0.75rem; font-weight: 700; }
    </style>
    <link rel="stylesheet" href="../../css/enhancements.css">
</head>
<body>
<div class="page-wrapper">
    <!-- ── Sidebar ── -->
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
                    <a href="departments.php" class="nav-item"><span class="nav-icon">🏛️</span><span>Departments</span></a>
                    <a href="courses.php" class="nav-item"><span class="nav-icon">🎓</span><span>Courses</span></a>
                    <a href="faculty.php" class="nav-item"><span class="nav-icon">👨‍🏫</span><span>Faculty Directory</span></a>
                    <a href="grades.php" class="nav-item"><span class="nav-icon">📝</span><span>Grades</span></a>
                    <a href="subjects.php" class="nav-item"><span class="nav-icon">📚</span><span>Subjects</span></a>
                    <a href="sections.php" class="nav-item active"><span class="nav-icon">📁</span><span>Sections</span></a>
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

    <!-- ── Main Content ── -->
    <main class="main-content">
        <header class="page-header">
            <div class="header-title">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button>
                    <h1>Section Management</h1>
                <p class="page-subtitle">Create sections, assign subjects &amp; build schedules</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAddSectionModal()">➕ Add Section</button>
            </div>
        </header>

        <!-- Filters -->
        <div class="content-card">
            <div class="filter-bar">
                <select id="filterCourse" onchange="loadSections()">
                    <option value="">All Courses</option>
                </select>
                <select id="filterYear" onchange="loadSections()">
                    <option value="">All Year Levels</option>
                    <option>1st Year</option>
                    <option>2nd Year</option>
                    <option>3rd Year</option>
                    <option>4th Year</option>
                </select>
                <select id="filterSemester" onchange="loadSections()">
                    <option value="">All Semesters</option>
                    <option>1st Semester</option>
                    <option>2nd Semester</option>
                    <option>Summer</option>
                </select>
                <select id="filterStatus" onchange="loadSections()">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="archived">Archived</option>
                </select>
                <input type="text" id="searchSection" placeholder="🔍 Search name or code…" oninput="loadSections()">
            </div>

            <div id="sectionsList">
                <div class="empty-state"><div class="empty-icon">⏳</div><p>Loading sections…</p></div>
            </div>
        </div>

        <!-- Detail panel (subjects + schedule) -->
        <div class="detail-panel" id="detailPanel">
            <div class="detail-header">
                <div>
                    <h2 id="detailTitle" style="margin:0">Section Detail</h2>
                    <p id="detailSubtitle" style="color:var(--text-secondary);font-size:0.9rem;margin-top:0.25rem"></p>
                </div>
                <button class="btn" onclick="closeDetailPanel()">✕ Close</button>
            </div>

            <div class="panel-tabs">
                <div class="panel-tab active" onclick="switchPanelTab('subjects', this)">📚 Subjects <span id="subjectCountBadge" class="count-pill">0</span></div>
                <div class="panel-tab" onclick="switchPanelTab('schedule', this)">📅 Schedule <span id="scheduleCountBadge" class="count-pill">0</span></div>
                <div class="panel-tab" onclick="switchPanelTab('timetable', this)">🗓️ Timetable</div>
            </div>

            <!-- Subjects panel -->
            <div id="panelSubjects" class="panel-content" style="margin-top:1rem">
                <div style="display:flex;justify-content:flex-end;margin-bottom:0.75rem">
                    <button class="btn btn-primary btn-xs" onclick="openAssignSubjectModal()">➕ Assign Subject</button>
                </div>
                <div id="subjectListContent">Loading…</div>
            </div>

            <!-- Schedule panel -->
            <div id="panelSchedule" class="panel-content" style="display:none;margin-top:1rem">
                <div style="display:flex;justify-content:flex-end;margin-bottom:0.75rem">
                    <button class="btn btn-primary btn-xs" onclick="openAddScheduleModal()">➕ Add Schedule Slot</button>
                </div>
                <div id="scheduleListContent">Loading…</div>
            </div>

            <!-- Timetable panel -->
            <div id="panelTimetable" class="panel-content" style="display:none;margin-top:1rem">
                <div id="timetableContent">Loading…</div>
            </div>
        </div>
    </main>
</div>

<!-- ══════════════════════════════════════
     MODAL: Add / Edit Section
══════════════════════════════════════ -->
<div id="sectionModal" class="modal">
    <div class="modal-content wide">
        <div class="modal-header">
            <h2 id="sectionModalTitle">Add Section</h2>
            <button class="modal-close" onclick="closeSectionModal()">✕</button>
        </div>
        <form id="sectionForm" onsubmit="saveSection(event)">
            <input type="hidden" id="sectionId">

            <div class="form-row">
                <div class="form-group">
                    <label>Section Name *</label>
                    <input type="text" id="sectionName" required placeholder="e.g., Computer Science 1-A">
                </div>
                <div class="form-group">
                    <label>Section Code *</label>
                    <input type="text" id="sectionCode" required placeholder="e.g., CS1A">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Course</label>
                    <select id="sectionCourse" onchange="onSectionCourseChange()">
                        <option value="">— Select Course —</option>
                    </select>
                    <div id="courseHintBox" style="font-size:.78rem;margin-top:.35rem;color:#5b21b6;display:none;"></div>
                </div>
                <div class="form-group">
                    <label>Year Level</label>
                    <select id="sectionYear">
                        <option value="">Select Year Level</option>
                        <option>1st Year</option><option>2nd Year</option>
                        <option>3rd Year</option><option>4th Year</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Semester</label>
                    <select id="sectionSemester">
                        <option value="">Select Semester</option>
                        <option>1st Semester</option>
                        <option>2nd Semester</option>
                        <option>Summer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>School Year</label>
                    <input type="text" id="sectionSchoolYear" placeholder="e.g., 2025-2026">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Max Students</label>
                    <input type="number" id="sectionMaxStudents" value="40" min="1" max="200">
                </div>
                <div class="form-group">
                    <label>Adviser</label>
                    <select id="sectionAdviser">
                        <option value="">No Adviser</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Home Room</label>
                    <input type="text" id="sectionRoom" placeholder="e.g., Room 201">
                </div>
                <div class="form-group">
                    <label>Building</label>
                    <input type="text" id="sectionBuilding" placeholder="e.g., Main Building">
                </div>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select id="sectionStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            <div style="display:flex;gap:1rem;margin-top:1.5rem">
                <button type="submit" class="btn btn-primary" style="flex:1">💾 Save Section</button>
                <button type="button" class="btn" onclick="closeSectionModal()" style="flex:1">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════
     MODAL: Assign Subject to Section
══════════════════════════════════════ -->
<div id="assignSubjectModal" class="modal">
    <div class="modal-content narrow">
        <div class="modal-header">
            <h2>Assign Subject</h2>
            <button class="modal-close" onclick="closeAssignSubjectModal()">✕</button>
        </div>

        <!-- Context banner showing section's course + year -->
        <div id="assignContextBanner" style="
            background: linear-gradient(135deg,#ede9fe,#ddd6fe);
            border-radius: var(--radius-md);
            padding: .65rem 1rem;
            margin-bottom: 1rem;
            font-size: .82rem;
            color: #4c1d95;
            display: none;
        "></div>

        <form id="assignSubjectForm" onsubmit="saveAssignedSubject(event)">

            <!-- Filter row inside modal -->
            <div style="display:flex; gap:.5rem; margin-bottom:.75rem; flex-wrap:wrap;">
                <select id="assignFilterYear" onchange="refreshAssignSubjectList()" style="flex:1; padding:.45rem .65rem; border:1.5px solid #d1d5db; border-radius:var(--radius-md); font-size:.82rem;">
                    <option value="">All Year Levels</option>
                    <option>1st Year</option>
                    <option>2nd Year</option>
                    <option>3rd Year</option>
                    <option>4th Year</option>
                </select>
                <select id="assignFilterScope" onchange="refreshAssignSubjectList()" style="flex:1; padding:.45rem .65rem; border:1.5px solid #d1d5db; border-radius:var(--radius-md); font-size:.82rem;">
                    <option value="section">This Course Only</option>
                    <option value="all">All Subjects</option>
                </select>
            </div>

            <div class="form-group">
                <label>Subject * <span id="subjectCountTag" style="font-size:.75rem; color:var(--text-secondary); font-weight:400;"></span></label>
                <select id="assignSubjectId" required>
                    <option value="">Loading subjects…</option>
                </select>
                <div id="assignSubjectHint" style="font-size:.78rem; margin-top:.35rem; color:var(--text-secondary);"></div>
            </div>

            <div class="form-group">
                <label>Assign Teacher</label>
                <select id="assignTeacherId">
                    <option value="">No Teacher Yet</option>
                </select>
            </div>

            <div style="display:flex;gap:1rem;margin-top:1.5rem">
                <button type="submit" class="btn btn-primary" style="flex:1">Assign</button>
                <button type="button" class="btn" onclick="closeAssignSubjectModal()" style="flex:1">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════
     MODAL: Add / Edit Schedule Slot
══════════════════════════════════════ -->
<div id="scheduleModal" class="modal">
    <div class="modal-content narrow">
        <div class="modal-header">
            <h2 id="scheduleModalTitle">Add Schedule Slot</h2>
            <button class="modal-close" onclick="closeScheduleModal()">✕</button>
        </div>
        <form id="scheduleForm" onsubmit="saveScheduleSlot(event)">
            <input type="hidden" id="scheduleId">

            <div class="form-group">
                <label>Subject *</label>
                <select id="schedSectionSubjectId" required onchange="onSchedSubjectChange()">
                    <option value="">Select Subject</option>
                </select>
                <div id="schedSubjectTypeInfo" style="margin-top:.4rem;font-size:.8rem;display:none;"></div>
            </div>
            <div class="form-group">
                <label>Class Type *</label>
                <div style="display:flex;gap:.5rem;margin-top:.25rem;">
                    <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;font-weight:400;
                        flex:1;padding:.5rem .75rem;border:1.5px solid #d1d5db;border-radius:var(--radius-md);
                        transition:all .15s;" id="classTypeLECLabel">
                        <input type="radio" name="classType" value="LEC" id="classTypeLEC"
                            style="width:auto;accent-color:var(--primary-purple);" checked>
                        📖 LEC
                    </label>
                    <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;font-weight:400;
                        flex:1;padding:.5rem .75rem;border:1.5px solid #d1d5db;border-radius:var(--radius-md);
                        transition:all .15s;" id="classTypeLABLabel">
                        <input type="radio" name="classType" value="LAB" id="classTypeLAB"
                            style="width:auto;accent-color:var(--primary-purple);">
                        🧪 LAB
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label>Day of Week *</label>
                <select id="schedDay" required>
                    <option value="">Select Day</option>
                    <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
                    <option>Thursday</option><option>Friday</option><option>Saturday</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Start Time *</label>
                    <input type="time" id="schedStart" required>
                </div>
                <div class="form-group">
                    <label>End Time *</label>
                    <input type="time" id="schedEnd" required>
                </div>
            </div>
            <div class="form-group">
                <label>Building</label>
                <select id="schedBuilding" onchange="onBuildingChange()">
                    <option value="">— Select Building —</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Room</label>
                    <select id="schedRoom" onchange="onRoomChange()">
                        <option value="">— Select Building First —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Floor</label>
                    <input type="text" id="schedFloor" placeholder="Auto-filled from room" readonly style="background:#f9fafb;color:#6b7280;">
                </div>
            </div>

            <div style="display:flex;gap:1rem;margin-top:1.5rem">
                <button type="submit" class="btn btn-primary" style="flex:1">💾 Save</button>
                <button type="button" class="btn" onclick="closeScheduleModal()" style="flex:1">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════
     JavaScript
══════════════════════════════════════ -->
<script>
/* ── State ── */
let currentSectionId   = null;
let currentSection     = null;   // full section object
let allSubjects        = [];
let allTeachers      = [];
let sectionSubjects  = [];   // cached for schedule modal dropdown
let currentPanelTab  = 'subjects';

const DAYS = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const DAY_SHORT = {Monday:'Mon',Tuesday:'Tue',Wednesday:'Wed',Thursday:'Thu',Friday:'Fri',Saturday:'Sat',Sunday:'Sun'};
const DAY_CLASS = {Monday:'day-Mon',Tuesday:'day-Tue',Wednesday:'day-Wed',Thursday:'day-Thu',Friday:'day-Fri',Saturday:'day-Sat',Sunday:'day-Sun'};

/* ══════════ LOAD SECTIONS ══════════ */
async function loadSections() {
    const params = new URLSearchParams({
        course:     document.getElementById('filterCourse').value,
        year_level: document.getElementById('filterYear').value,
        semester:   document.getElementById('filterSemester').value,
        status:     document.getElementById('filterStatus').value,
        search:     document.getElementById('searchSection').value
    });

    const res  = await fetch(`../../api/admin/get_sections.php?${params}`);
    const data = await res.json();

    const container = document.getElementById('sectionsList');
    if (!data.success || data.sections.length === 0) {
        container.innerHTML = `<div class="empty-state"><div class="empty-icon">🗂️</div><p>No sections found. <a href="#" onclick="openAddSectionModal();return false">Add one?</a></p></div>`;
        return;
    }

    const statusBadge = s => {
        const map = { active:'badge-active', inactive:'badge-inactive', archived:'badge-archived' };
        return `<span class="badge ${map[s]||'badge-info'}">${s}</span>`;
    };

    container.innerHTML = `<div class="sections-grid">` +
        data.sections.map(s => `
        <div class="section-row" id="sr-${s.id}">
            <div class="section-code">${s.section_code}</div>
            <div class="section-info">
                <div class="section-name">${s.section_name}</div>
                <div class="section-meta">
                    ${s.course || 'No Course'} &nbsp;•&nbsp;
                    ${s.year_level || '—'} &nbsp;•&nbsp;
                    ${s.semester || '—'} &nbsp;•&nbsp;
                    ${s.school_year || '—'}
                    ${s.adviser_name ? `&nbsp;•&nbsp; 👨‍🏫 ${s.adviser_name}` : ''}
                    ${s.room ? `&nbsp;•&nbsp; 🚪 ${s.room}` : ''}
                </div>
            </div>
            <div class="section-stats">
                <span class="badge badge-purple">📚 ${s.subject_count} subj</span>
                <span class="badge badge-info">🕐 ${s.schedule_count} slots</span>
                ${statusBadge(s.status)}
            </div>
            <div class="section-actions">
                <button class="btn btn-xs btn-info" onclick='openDetailPanel(${JSON.stringify(s)})'>📂 Manage</button>
                <button class="btn btn-xs" onclick='editSection(${JSON.stringify(s)})'>✏️ Edit</button>
                <button class="btn btn-xs btn-danger" onclick="deleteSection(${s.id}, '${s.section_code}')">🗑️</button>
            </div>
        </div>`).join('') +
    `</div>`;
}

/* ══════════ LOAD GLOBAL FILTERS ══════════ */
let allCourses = [];

async function loadFilters() {
    // Load courses from courses table
    try {
        const cr = await fetch('../../api/admin/get_courses.php');
        const cd = await cr.json();
        if (cd.success && cd.courses.length > 0) {
            allCourses = cd.courses;

            // Filter bar dropdown
            const filterCourse = document.getElementById('filterCourse');
            filterCourse.innerHTML = '<option value="">All Courses</option>';
            allCourses.forEach(c => {
                filterCourse.innerHTML += `<option value="${c.course_name}">${c.course_name} (${c.course_code})</option>`;
            });

            // Section form course dropdown
            populateCourseDropdown();
        } else {
            // Fallback: populate filter from subjects if courses table empty
            const sr = await fetch('../../api/admin/get_subjects.php');
            const sd = await sr.json();
            if (sd.success) {
                const courses = [...new Set(sd.subjects.map(s => s.course).filter(Boolean))];
                const filterCourse = document.getElementById('filterCourse');
                filterCourse.innerHTML = '<option value="">All Courses</option>';
                courses.forEach(c => filterCourse.innerHTML += `<option value="${c}">${c}</option>`);

                // Fallback for section form too
                const sel = document.getElementById('sectionCourse');
                sel.innerHTML = '<option value="">— Select Course —</option>';
                courses.forEach(c => sel.innerHTML += `<option value="${c}">${c}</option>`);
            }
        }
    } catch(e) {
        console.warn('Could not load courses:', e);
    }

    // Load subjects — just cache them, don't pre-populate assign dropdown
    const sr = await fetch('../../api/admin/get_subjects.php');
    const sd = await sr.json();
    if (sd.success) {
        allSubjects = sd.subjects;
        // assignSubjectId is now populated dynamically in refreshAssignSubjectList()
    }

    // Load teachers
    const tr = await fetch('../../api/admin/get_users.php?role=teacher');
    const td = await tr.json();
    if (td.success) {
        allTeachers = td.users;
        const adviserSel = document.getElementById('sectionAdviser');
        const teacherSel = document.getElementById('assignTeacherId');
        td.users.forEach(t => {
            adviserSel.innerHTML += `<option value="${t.id}">${t.name}</option>`;
            teacherSel.innerHTML += `<option value="${t.id}">${t.name}</option>`;
        });
    }
}

function populateCourseDropdown(selectedValue = '') {
    const sel = document.getElementById('sectionCourse');
    sel.innerHTML = '<option value="">— Select Course —</option>';
    allCourses.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.course_name;
        opt.dataset.code     = c.course_code;
        opt.dataset.duration = c.duration_years || 4;
        opt.textContent = `${c.course_name} (${c.course_code})`;
        if (c.course_name === selectedValue) opt.selected = true;
        sel.appendChild(opt);
    });
    // Trigger hint update if a value is pre-selected
    if (selectedValue) onSectionCourseChange();
}

function onSectionCourseChange() {
    const sel  = document.getElementById('sectionCourse');
    const hint = document.getElementById('courseHintBox');
    const yearSel = document.getElementById('sectionYear');

    if (!sel.value) {
        hint.style.display = 'none';
        return;
    }

    const opt      = sel.options[sel.selectedIndex];
    const code     = opt.dataset.code     || '';
    const duration = parseInt(opt.dataset.duration) || 4;

    hint.style.display = 'block';
    hint.innerHTML = `🎓 <strong>${opt.value}</strong> (${code}) · ${duration}-year program`;

    // Adjust year level options based on duration
    const curYear = yearSel.value;
    const labels  = ['1st Year','2nd Year','3rd Year','4th Year','5th Year','6th Year'];
    yearSel.innerHTML = '<option value="">Select Year Level</option>';
    for (let i = 0; i < Math.min(duration, labels.length); i++) {
        yearSel.innerHTML += `<option value="${labels[i]}" ${labels[i]===curYear?'selected':''}>${labels[i]}</option>`;
    }
}

/* ══════════ SECTION MODAL ══════════ */
function openAddSectionModal() {
    document.getElementById('sectionModalTitle').textContent = 'Add Section';
    document.getElementById('sectionForm').reset();
    document.getElementById('sectionId').value = '';
    document.getElementById('sectionStatus').value = 'active';
    populateCourseDropdown();
    document.getElementById('courseHintBox').style.display = 'none';
    document.getElementById('sectionModal').classList.add('active');
}

function editSection(s) {
    document.getElementById('sectionModalTitle').textContent = 'Edit Section';
    document.getElementById('sectionId').value         = s.id;
    document.getElementById('sectionName').value       = s.section_name;
    document.getElementById('sectionCode').value       = s.section_code;
    document.getElementById('sectionYear').value       = s.year_level   || '';
    document.getElementById('sectionSemester').value   = s.semester     || '';
    document.getElementById('sectionSchoolYear').value = s.school_year  || '';
    document.getElementById('sectionMaxStudents').value= s.max_students || 40;
    document.getElementById('sectionRoom').value       = s.room         || '';
    document.getElementById('sectionBuilding').value   = s.building     || '';
    document.getElementById('sectionAdviser').value    = s.adviser_id   || '';
    document.getElementById('sectionStatus').value     = s.status       || 'active';
    populateCourseDropdown(s.course || '');
    document.getElementById('sectionModal').classList.add('active');
}

function closeSectionModal() { document.getElementById('sectionModal').classList.remove('active'); }

async function saveSection(e) {
    e.preventDefault();
    const payload = {
        section_id:   document.getElementById('sectionId').value || null,
        section_name: document.getElementById('sectionName').value,
        section_code: document.getElementById('sectionCode').value,
        course:       document.getElementById('sectionCourse').value,
        year_level:   document.getElementById('sectionYear').value,
        semester:     document.getElementById('sectionSemester').value,
        school_year:  document.getElementById('sectionSchoolYear').value,
        max_students: document.getElementById('sectionMaxStudents').value,
        room:         document.getElementById('sectionRoom').value,
        building:     document.getElementById('sectionBuilding').value,
        adviser_id:   document.getElementById('sectionAdviser').value || null,
        status:       document.getElementById('sectionStatus').value
    };

    const res  = await fetch('../../api/admin/save_section.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    const data = await res.json();
    if (data.success) { closeSectionModal(); loadSections(); }
    else alert('Error: ' + data.message);
}

async function deleteSection(id, code) {
    if (!confirm(`Delete section "${code}"? This will also remove its subjects and schedules.`)) return;
    const res  = await fetch('../../api/admin/delete_section.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({section_id:id}) });
    const data = await res.json();
    if (data.success) { closeDetailPanel(); loadSections(); }
    else alert('Error: ' + data.message);
}

/* ══════════ DETAIL PANEL ══════════ */
function openDetailPanel(s) {
    currentSectionId = s.id;
    currentSection   = s;
    document.getElementById('detailTitle').textContent    = s.section_name;
    document.getElementById('detailSubtitle').textContent = `${s.section_code} — ${s.course||''} ${s.year_level||''} ${s.semester||''}`.trim();
    document.getElementById('detailPanel').classList.add('active');
    switchPanelTab('subjects');
    document.getElementById('detailPanel').scrollIntoView({behavior:'smooth', block:'start'});
}

function closeDetailPanel() {
    document.getElementById('detailPanel').classList.remove('active');
    currentSectionId = null;
}

function switchPanelTab(tab, el) {
    currentPanelTab = tab;
    document.querySelectorAll('.panel-tab').forEach(t => t.classList.remove('active'));
    if (el) el.classList.add('active');
    else document.querySelectorAll('.panel-tab')[['subjects','schedule','timetable'].indexOf(tab)].classList.add('active');

    document.getElementById('panelSubjects').style.display  = tab === 'subjects'   ? 'block' : 'none';
    document.getElementById('panelSchedule').style.display  = tab === 'schedule'   ? 'block' : 'none';
    document.getElementById('panelTimetable').style.display = tab === 'timetable'  ? 'block' : 'none';

    if (tab === 'subjects')  loadSectionSubjects();
    if (tab === 'schedule')  loadSectionSchedules();
    if (tab === 'timetable') loadTimetable();
}

/* ══════════ SUBJECTS IN SECTION ══════════ */
async function loadSectionSubjects() {
    if (!currentSectionId) return;
    const res  = await fetch(`../../api/admin/get_section_subjects.php?section_id=${currentSectionId}`);
    const data = await res.json();
    const cnt  = document.getElementById('subjectCountBadge');

    if (!data.success) { document.getElementById('subjectListContent').innerHTML = '❌ Failed to load'; return; }

    sectionSubjects = data.subjects;
    cnt.textContent = data.subjects.length;

    if (data.subjects.length === 0) {
        document.getElementById('subjectListContent').innerHTML = `<div class="empty-state"><div class="empty-icon">📚</div><p>No subjects assigned yet.</p></div>`;
        return;
    }

    document.getElementById('subjectListContent').innerHTML =
        `<div class="subject-list">` +
        data.subjects.map(s => {
            const maxMinutes  = (s.subject_type === 'minor') ? 180 : 360;
            const usedMinutes = parseInt(s.total_scheduled_minutes || 0);
            const isFull      = usedMinutes >= maxMinutes;
            const usedH = Math.floor(usedMinutes / 60), usedM = usedMinutes % 60;
            const usedStr = (usedH > 0 ? `${usedH}h ` : '') + (usedM > 0 ? `${usedM}m` : '0m');
            const maxH = maxMinutes / 60;
            const fullBadge = isFull
                ? `<span style="background:#d1fae5;color:#065f46;font-size:.72rem;font-weight:700;padding:.15rem .5rem;border-radius:.8rem;margin-left:.35rem;">✅ Fully Scheduled</span>`
                : '';
            const typeBadge = s.subject_type === 'minor'
                ? `<span style="background:#dbeafe;color:#1e40af;font-size:.72rem;font-weight:700;padding:.15rem .5rem;border-radius:.8rem;">📖 Minor</span>`
                : `<span style="background:#f3e8ff;color:#6d28d9;font-size:.72rem;font-weight:700;padding:.15rem .5rem;border-radius:.8rem;">🎓 Major</span>`;
            return `
        <div class="subject-item" style="${isFull ? 'opacity:.65;' : ''}">
            <div class="sub-code">${s.subject_code}</div>
            <div class="sub-info">
                <div class="sub-name">${s.subject_name} ${typeBadge} ${fullBadge}</div>
                <div class="sub-teacher">
                    ${s.units} units &nbsp;|&nbsp;
                    ${s.teacher_name ? `👨‍🏫 ${s.teacher_name}` : '<em>No teacher assigned</em>'}
                    &nbsp;|&nbsp; ⏱ ${usedStr.trim()} / ${maxH}h used
                </div>
            </div>
            <button class="btn btn-xs btn-danger" onclick="removeSubjectFromSection(${s.id}, '${s.subject_name}')">✕ Remove</button>
        </div>`;
        }).join('') +
        `</div>`;

    // Also refresh schedule subject dropdown
    refreshSchedSubjectDropdown();
}

async function removeSubjectFromSection(id, name) {
    if (!confirm(`Remove "${name}" from this section? Its schedule slots will also be deleted.`)) return;
    const res  = await fetch('../../api/admin/remove_section_subject.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({section_subject_id:id}) });
    const data = await res.json();
    if (data.success) { loadSectionSubjects(); loadSections(); }
    else alert('Error: ' + data.message);
}

/* ══════════ ASSIGN SUBJECT MODAL ══════════ */
function openAssignSubjectModal() {
    if (!currentSection) return;

    // Show context banner
    const banner = document.getElementById('assignContextBanner');
    const course    = currentSection.course    || '';
    const yearLevel = currentSection.year_level || '';
    if (course || yearLevel) {
        banner.style.display = 'block';
        banner.innerHTML = `🏫 <strong>${currentSection.section_name}</strong> &nbsp;·&nbsp;
            ${course ? `🎓 ${course}` : ''}
            ${yearLevel ? ` &nbsp;·&nbsp; 📅 ${yearLevel}` : ''}`;
    } else {
        banner.style.display = 'none';
    }

    // Pre-set year filter to the section's year level
    const yearFilter = document.getElementById('assignFilterYear');
    yearFilter.value = yearLevel || '';

    // Default scope: section's course only
    document.getElementById('assignFilterScope').value = 'section';

    document.getElementById('assignSubjectForm').reset();
    document.getElementById('assignSubjectId').innerHTML = '<option value="">Loading…</option>';
    document.getElementById('assignSubjectHint').textContent = '';
    document.getElementById('assignSubjectModal').classList.add('active');

    // Load filtered subjects
    refreshAssignSubjectList();
}

async function refreshAssignSubjectList() {
    if (!currentSection) return;

    const yearFilter  = document.getElementById('assignFilterYear').value;
    const scope       = document.getElementById('assignFilterScope').value;
    const sel         = document.getElementById('assignSubjectId');
    const hint        = document.getElementById('assignSubjectHint');
    const countTag    = document.getElementById('subjectCountTag');

    sel.innerHTML = '<option value="">Loading…</option>';
    sel.disabled  = true;
    hint.textContent = '';

    // Already-assigned subject IDs
    const assignedIds = new Set(sectionSubjects.map(s => parseInt(s.subject_id)));

    try {
        // Always fetch two sets in parallel:
        // 1. Course-specific subjects (filtered by course + year)
        // 2. All-Courses subjects (no course set, same year filter)
        const baseParams = new URLSearchParams({ status: 'active' });
        if (yearFilter) baseParams.set('year_level', yearFilter);

        const specificParams = new URLSearchParams(baseParams);
        if (scope === 'section' && currentSection.course) {
            specificParams.set('course', currentSection.course);
        }

        // All-courses subjects = no course filter but match year
        const allCoursesParams = new URLSearchParams({ status: 'active', course: '__none__' });
        if (yearFilter) allCoursesParams.set('year_level', yearFilter);

        const [specRes, allRes] = await Promise.all([
            fetch(`../../api/admin/get_subjects.php?${specificParams}`),
            fetch(`../../api/admin/get_subjects.php?${allCoursesParams}`)
        ]);
        const [specData, allData] = await Promise.all([specRes.json(), allRes.json()]);

        // Course-specific subjects
        let specificSubjects = (specData.subjects || [])
            .filter(s => s.course)   // must have a course assigned
            .filter(s => !assignedIds.has(s.id));

        // All-courses subjects (no course value = shared)
        let sharedSubjects = (allData.subjects || [])
            .filter(s => !s.course)  // no course = shared
            .filter(s => !assignedIds.has(s.id));

        const total = specificSubjects.length + sharedSubjects.length;

        sel.innerHTML = '<option value="">— Select Subject —</option>';

        if (total === 0) {
            sel.innerHTML = '<option value="">No subjects available</option>';
            countTag.textContent = '(0 available)';
            const course    = currentSection.course || '';
            const yearLabel = yearFilter || currentSection.year_level || '';

            if (scope === 'section') {
                hint.innerHTML = `⚠️ No unassigned subjects found for <strong>${yearLabel || 'this year'}</strong>
                    ${course ? ` in <strong>${course}</strong>` : ''}.
                    Try switching to <em>"All Subjects"</em> or
                    add subjects via <a href="subjects.php" style="color:var(--primary-purple)">Subjects page</a>.`;
            } else {
                hint.textContent = 'All available subjects are already assigned to this section.';
            }
        } else {
            // Group 1: Course-specific subjects
            if (specificSubjects.length > 0) {
                const grp = document.createElement('optgroup');
                grp.label = `🎓 ${currentSection.course || 'Course'} Subjects (${specificSubjects.length})`;
                specificSubjects.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value       = s.id;
                    opt.textContent = `${s.subject_code} — ${s.subject_name} (${s.units} units)`;
                    grp.appendChild(opt);
                });
                sel.appendChild(grp);
            }

            // Group 2: Shared / All-Courses subjects
            if (sharedSubjects.length > 0) {
                const grp = document.createElement('optgroup');
                grp.label = `🌐 General / All-Courses Subjects (${sharedSubjects.length})`;
                sharedSubjects.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value       = s.id;
                    opt.textContent = `${s.subject_code} — ${s.subject_name} (${s.units} units)`;
                    grp.appendChild(opt);
                });
                sel.appendChild(grp);
            }

            countTag.textContent = `(${total} available)`;
            const yearLabel  = yearFilter || 'all year levels';
            const scopeLabel = scope === 'section' && currentSection.course
                ? currentSection.course : 'all courses';
            hint.textContent = `${specificSubjects.length} course-specific + ${sharedSubjects.length} general subject(s) for ${yearLabel}. Already-assigned subjects are hidden.`;
        }

        sel.disabled = false;

    } catch (e) {
        sel.innerHTML = '<option value="">Error loading subjects</option>';
        hint.textContent = 'Failed to load subjects. Please try again.';
        sel.disabled = false;
    }
}

function closeAssignSubjectModal() {
    document.getElementById('assignSubjectModal').classList.remove('active');
}

async function saveAssignedSubject(e) {
    e.preventDefault();
    const subjectId = document.getElementById('assignSubjectId').value;
    if (!subjectId) { alert('Please select a subject.'); return; }

    const payload = {
        section_id: currentSectionId,
        subject_id: subjectId,
        teacher_id: document.getElementById('assignTeacherId').value || null
    };
    const res  = await fetch('../../api/admin/save_section_subject.php', {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
        closeAssignSubjectModal();
        document.getElementById('assignSubjectForm').reset();
        loadSectionSubjects();
        loadSections();
    } else alert('Error: ' + data.message);
}

/* ══════════ SCHEDULES ══════════ */
async function loadSectionSchedules() {
    if (!currentSectionId) return;
    const res  = await fetch(`../../api/admin/get_section_schedules.php?section_id=${currentSectionId}`);
    const data = await res.json();
    const cnt  = document.getElementById('scheduleCountBadge');

    if (!data.success) { document.getElementById('scheduleListContent').innerHTML = '❌ Failed to load'; return; }
    cnt.textContent = data.schedules.length;

    if (data.schedules.length === 0) {
        document.getElementById('scheduleListContent').innerHTML = `<div class="empty-state"><div class="empty-icon">📅</div><p>No schedule slots yet.</p></div>`;
        return;
    }

    document.getElementById('scheduleListContent').innerHTML =
        `<div class="schedule-list">` +
        data.schedules.map(sc => {
            const ctBg    = sc.class_type === 'LAB' ? '#d1fae5' : '#fef3c7';
            const ctColor = sc.class_type === 'LAB' ? '#065f46' : '#92400e';
            const ctEmoji = sc.class_type === 'LAB' ? '🧪' : '📖';
            return `
        <div class="schedule-item">
            <span class="day-badge ${DAY_CLASS[sc.day_of_week]}">${DAY_SHORT[sc.day_of_week]}</span>
            <div class="sched-info">
                <div class="sched-time">${sc.start_time_fmt} – ${sc.end_time_fmt}
                    <span style="background:${ctBg};color:${ctColor};font-size:.72rem;font-weight:700;padding:.1rem .45rem;border-radius:.5rem;margin-left:.35rem;">${ctEmoji} ${sc.class_type || 'LEC'}</span>
                </div>
                <div class="sched-room">
                    📚 ${sc.subject_code} – ${sc.subject_name}
                    ${sc.teacher_name ? `&nbsp;|&nbsp; 👨‍🏫 ${sc.teacher_name}` : ''}
                    ${sc.room ? `&nbsp;|&nbsp; 🚪 ${sc.room}` : ''}
                    ${sc.building ? `, ${sc.building}` : ''}
                </div>
            </div>
            <button class="btn btn-xs btn-danger" onclick="deleteScheduleSlot(${sc.id})">✕</button>
        </div>`;
        }).join('') +
        `</div>`;
}

function refreshSchedSubjectDropdown() {
    const sel = document.getElementById('schedSectionSubjectId');
    sel.innerHTML = '<option value="">Select Subject</option>';
    let availableCount = 0;
    sectionSubjects.forEach(s => {
        const maxMinutes = (s.subject_type === 'minor') ? 180 : 360;
        const usedMinutes = parseInt(s.total_scheduled_minutes || 0);
        if (usedMinutes >= maxMinutes) return; // skip fully-scheduled subjects
        availableCount++;
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = `${s.subject_code} – ${s.subject_name}`;
        opt.dataset.subjectType = s.subject_type || 'major';
        opt.dataset.usedMinutes = usedMinutes;
        opt.dataset.maxMinutes  = maxMinutes;
        sel.appendChild(opt);
    });
    if (availableCount === 0) {
        sel.innerHTML = '<option value="">— All subjects fully scheduled —</option>';
    }
}

function onSchedSubjectChange() {
    const sel  = document.getElementById('schedSectionSubjectId');
    const opt  = sel.options[sel.selectedIndex];
    const info = document.getElementById('schedSubjectTypeInfo');
    if (!opt || !opt.value) { info.style.display = 'none'; return; }
    const type       = opt.dataset.subjectType || 'major';
    const maxMinutes = parseInt(opt.dataset.maxMinutes || (type === 'major' ? 360 : 180));
    const usedMinutes= parseInt(opt.dataset.usedMinutes || 0);
    const remMinutes = maxMinutes - usedMinutes;
    const maxH       = maxMinutes / 60;
    const remH       = Math.floor(remMinutes / 60);
    const remM       = remMinutes % 60;
    const remStr     = (remH > 0 ? `${remH}h ` : '') + (remM > 0 ? `${remM}m` : '');
    const color      = type === 'major' ? '#6d28d9' : '#1e40af';
    const bg         = type === 'major' ? '#f3e8ff' : '#dbeafe';
    const emoji      = type === 'major' ? '🎓' : '📖';
    const usedH      = Math.floor(usedMinutes / 60);
    const usedM      = usedMinutes % 60;
    const usedStr    = (usedH > 0 ? `${usedH}h ` : '') + (usedM > 0 ? `${usedM}m` : '0m');
    info.innerHTML = `
        <span style="background:${bg};color:${color};padding:.2rem .6rem;border-radius:.8rem;font-weight:700;">${emoji} ${type.charAt(0).toUpperCase()+type.slice(1)}</span>
        &nbsp; max <strong>${maxH}h</strong> total &nbsp;|&nbsp;
        used <strong>${usedStr.trim()}</strong> &nbsp;|&nbsp;
        <span style="color:#059669;font-weight:700;">⏱ ${remStr.trim() || '0m'} remaining</span>
    `;
    info.style.display = 'block';
}

/* ── Building / Room dropdowns for schedule modal ── */
let _allRooms = [];

async function loadBuildingsAndRooms() {
    // Load buildings
    try {
        const bRes  = await fetch('../../api/admin/get_buildings.php');
        const bData = await bRes.json();
        const bSel  = document.getElementById('schedBuilding');
        const curB  = bSel.value;
        bSel.innerHTML = '<option value="">— Select Building —</option>';
        if (bData.success) {
            bData.buildings.forEach(function(b) {
                const o = document.createElement('option');
                o.value       = b.building_name;
                o.textContent = b.building_name + (b.room_count > 0 ? ' (' + b.room_count + ' rooms)' : '');
                bSel.appendChild(o);
            });
        }
        if (curB) bSel.value = curB;
    } catch(e) { console.error('Failed to load buildings', e); }

    // Load all rooms
    try {
        const rRes  = await fetch('../../api/admin/get_rooms.php');
        const rData = await rRes.json();
        if (rData.success) _allRooms = rData.rooms;
    } catch(e) { console.error('Failed to load rooms', e); }

    // Populate room dropdown based on current building selection
    onBuildingChange();
}

function onBuildingChange() {
    const building = document.getElementById('schedBuilding').value;
    const rSel     = document.getElementById('schedRoom');
    const curR     = rSel.value;
    rSel.innerHTML = '';

    if (!building) {
        rSel.innerHTML = '<option value="">— Select Building First —</option>';
        document.getElementById('schedFloor').value = '';
        return;
    }

    const filtered = _allRooms.filter(function(r) { return r.building_name === building; });
    if (filtered.length === 0) {
        rSel.innerHTML = '<option value="">No rooms in this building</option>';
        document.getElementById('schedFloor').value = '';
        return;
    }

    rSel.innerHTML = '<option value="">— Select Room —</option>';
    filtered.forEach(function(r) {
        const o = document.createElement('option');
        o.value          = r.room_number;
        o.dataset.floor  = r.floor || '';
        o.textContent    = r.room_number + (r.floor ? ' (Floor ' + r.floor + ')' : '') + (r.room_type ? ' - ' + r.room_type : '');
        rSel.appendChild(o);
    });

    if (curR) rSel.value = curR;
    onRoomChange();
}

function onRoomChange() {
    const rSel = document.getElementById('schedRoom');
    const opt  = rSel.options[rSel.selectedIndex];
    document.getElementById('schedFloor').value = (opt && opt.dataset.floor) ? opt.dataset.floor : '';
}

async function openAddScheduleModal() {
    // Always fetch fresh subject data so hour totals are up-to-date
    const res  = await fetch(`../../api/admin/get_section_subjects.php?section_id=${currentSectionId}`);
    const data = await res.json();
    if (data.success) sectionSubjects = data.subjects;

    const available = sectionSubjects.filter(s => {
        const maxMin  = (s.subject_type === 'minor') ? 180 : 360;
        const usedMin = parseInt(s.total_scheduled_minutes || 0);
        return usedMin < maxMin;
    });

    if (sectionSubjects.length === 0) {
        alert('Please assign subjects to this section first.');
        return;
    }
    if (available.length === 0) {
        alert('All subjects in this section have already reached their maximum scheduled hours.');
        return;
    }

    document.getElementById('scheduleModalTitle').textContent = 'Add Schedule Slot';
    document.getElementById('scheduleForm').reset();
    document.getElementById('scheduleId').value = '';
    document.getElementById('schedFloor').value = '';
    document.getElementById('classTypeLEC').checked = true;
    document.getElementById('schedSubjectTypeInfo').style.display = 'none';
    refreshSchedSubjectDropdown();
    loadBuildingsAndRooms();
    document.getElementById('scheduleModal').classList.add('active');
}

function closeScheduleModal() { document.getElementById('scheduleModal').classList.remove('active'); }

async function saveScheduleSlot(e) {
    e.preventDefault();
    const payload = {
        schedule_id:        document.getElementById('scheduleId').value || null,
        section_id:         currentSectionId,
        section_subject_id: document.getElementById('schedSectionSubjectId').value,
        class_type:         document.querySelector('input[name="classType"]:checked')?.value || 'LEC',
        day_of_week:        document.getElementById('schedDay').value,
        start_time:         document.getElementById('schedStart').value,
        end_time:           document.getElementById('schedEnd').value,
        room:               document.getElementById('schedRoom').value,
        building:           document.getElementById('schedBuilding').value,
        floor:              document.getElementById('schedFloor').value
    };

    const res  = await fetch('../../api/admin/save_section_schedule.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    const data = await res.json();
    if (data.success) { closeScheduleModal(); loadSectionSchedules(); loadSectionSubjects(); loadSections(); }
    else alert('⚠️ ' + data.message);
}

async function deleteScheduleSlot(id) {
    if (!confirm('Remove this schedule slot?')) return;
    const res  = await fetch('../../api/admin/delete_section_schedule.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({schedule_id:id}) });
    const data = await res.json();
    if (data.success) { loadSectionSchedules(); loadSectionSubjects(); loadSections(); }
    else alert('Error: ' + data.message);
}

/* ══════════ TIMETABLE ══════════ */
async function loadTimetable() {
    if (!currentSectionId) return;
    const res  = await fetch(`../../api/admin/get_section_schedules.php?section_id=${currentSectionId}`);
    const data = await res.json();

    if (!data.success || data.schedules.length === 0) {
        document.getElementById('timetableContent').innerHTML = `<div class="empty-state"><div class="empty-icon">🗓️</div><p>No schedules yet. Add slots in the Schedule tab.</p></div>`;
        return;
    }

    // Gather unique time slots
    const timePairs = [...new Set(data.schedules.map(s => `${s.start_time}|${s.end_time}`))].sort();

    // Map: day → timeSlot → schedule
    const grid = {};
    DAYS.forEach(d => { grid[d] = {}; });
    data.schedules.forEach(s => {
        const key = `${s.start_time}|${s.end_time}`;
        if (!grid[s.day_of_week]) grid[s.day_of_week] = {};
        grid[s.day_of_week][key] = s;
    });

    let html = `<div class="timetable-wrap"><table class="timetable">
        <thead><tr><th>Time</th>${DAYS.map(d=>`<th>${d}</th>`).join('')}</tr></thead><tbody>`;

    timePairs.forEach(tp => {
        const [st, en] = tp.split('|');
        // format time
        const fmt = t => { const [h,m] = t.split(':'); const hh=parseInt(h); return `${hh>12?hh-12:hh||12}:${m} ${hh>=12?'PM':'AM'}`; };
        html += `<tr><td class="time-col">${fmt(st)}<br><small>${fmt(en)}</small></td>`;
        DAYS.forEach(d => {
            const cell = grid[d] && grid[d][tp];
            if (cell) {
                html += `<td><div class="sched-cell">
                    <div class="sc-sub">${cell.subject_code}</div>
                    <div class="sc-room">${cell.room||''}</div>
                </div></td>`;
            } else {
                html += `<td><span class="empty-day">—</span></td>`;
            }
        });
        html += '</tr>';
    });

    html += `</tbody></table></div>`;
    document.getElementById('timetableContent').innerHTML = html;
}

/* ══════════ INIT ══════════ */
loadFilters();
loadSections();
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

<script src="../../js/enhancements.js"></script>
</body>
</html>
