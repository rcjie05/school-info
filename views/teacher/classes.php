<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('teacher');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes - Teacher Dashboard</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        .class-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .section-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(124,58,237,0.1);
            color: var(--primary-purple);
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 0.75rem;
            padding: 1rem;
            background: var(--background-main);
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }
        .info-item { font-size: 0.875rem; }
        .info-item .label { font-weight: 600; color: var(--text-secondary); font-size: 0.78rem; margin-bottom: 0.15rem; }
        .info-item .value { color: var(--text-primary); }
        .summary-chips { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        .chip {
            padding: 0.5rem 1rem;
            background: var(--background-main);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid rgba(124,58,237,0.15);
        }
        .collapsible-btn {
            background: none;
            border: 1px solid rgba(124,58,237,0.25);
            border-radius: var(--radius-md);
            padding: 0.4rem 0.9rem;
            font-size: 0.82rem;
            cursor: pointer;
            color: var(--primary-purple);
            font-weight: 600;
        }
        .collapsible-btn:hover { background: rgba(124,58,237,0.07); }
        .student-table-wrap { display: none; overflow-x: auto; margin-top: 0.5rem; }
        .student-table-wrap.open { display: block; }

        /* Download Excel button */
        .btn-download-excel {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 1rem;
            background: #1d6f42;
            color: #fff;
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
        }
        .btn-download-excel:hover  { background: #155231; }
        .btn-download-excel:active { transform: scale(0.97); }
        .class-actions { display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap; }
        .grade-pass { color: #15803d; font-weight: 700; }
        .grade-fail { color: #dc2626; font-weight: 700; }
        .grade-none { color: var(--text-secondary); }
        .grade-input {
            width: 80px; padding: 0.3rem 0.5rem;
            border: 1.5px solid #e5e7eb; border-radius: 6px;
            font-size: 0.85rem; text-align: center;
            transition: border-color 0.15s;
        }
        .grade-input:focus { outline: none; border-color: var(--primary-purple); }
        .btn-save-grade {
            padding: 0.3rem 0.75rem;
            background: var(--primary-purple, #5C58ED);
            color: #fff; border: none; border-radius: 6px;
            font-size: 0.78rem; font-weight: 700; cursor: pointer;
            transition: background 0.15s, opacity 0.15s;
            white-space: nowrap;
        }
        .btn-save-grade:hover   { opacity: 0.85; }
        .btn-save-grade.saved   { background: #059669; }
        .btn-save-grade.saving  { opacity: 0.6; cursor: not-allowed; }

        /* Submit to Registrar */
        .btn-submit-grades {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 1rem;
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
        }
        .btn-submit-grades:active { transform: scale(0.97); }
        .submit-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .badge-pending  { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: modalIn 0.2s ease;
        }
        @keyframes modalIn { from { transform: scale(0.92); opacity:0; } to { transform: scale(1); opacity:1; } }
        .modal-title { font-size: 1.1rem; font-weight: 800; margin-bottom: 0.4rem; color: var(--text-primary); }
        .modal-subtitle { font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1.25rem; }
        .modal-info-row { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .modal-info-item { flex: 1; min-width: 140px; }
        .modal-info-item label { font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); display: block; margin-bottom: 0.25rem; }
        .modal-info-item span  { font-size: 0.9rem; font-weight: 600; color: var(--text-primary); }
        .modal-textarea {
            width: 100%; padding: 0.75rem; border: 1.5px solid #e5e7eb;
            border-radius: var(--radius-md); font-size: 0.875rem;
            resize: vertical; min-height: 90px; box-sizing: border-box;
            font-family: inherit;
        }
        .modal-textarea:focus { outline: none; border-color: var(--primary-purple); }
        .modal-actions { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.25rem; }
        .btn-cancel { padding: 0.6rem 1.25rem; background: #f3f4f6; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; font-size: 0.875rem; }
        .btn-cancel:hover { background: #e5e7eb; }
        .btn-confirm-submit { padding: 0.6rem 1.5rem; background: var(--primary-purple, #5C58ED); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.875rem; }
        .btn-confirm-submit:hover { opacity: 0.9; }
        .btn-confirm-submit:disabled { opacity: 0.6; cursor: not-allowed; }
    </style>

    <!-- Submit to Registrar Modal -->
    <div class="modal-overlay" id="submitModal">
        <div class="modal-box">
            <div class="modal-title">📋 Submit Grade Sheet to Registrar</div>
            <div class="modal-subtitle">Attach your Excel grade sheet file. The registrar will be able to download and review it.</div>

            <div class="modal-info-row">
                <div class="modal-info-item">
                    <label>SUBJECT</label>
                    <span id="modalSubject">—</span>
                </div>
                <div class="modal-info-item">
                    <label>SECTION</label>
                    <span id="modalSection">—</span>
                </div>
            </div>
            <div class="modal-info-row">
                <div class="modal-info-item">
                    <label>STUDENTS GRADED</label>
                    <span id="modalGraded">—</span>
                </div>
                <div class="modal-info-item">
                    <label>SEMESTER / SY</label>
                    <span id="modalSem">—</span>
                </div>
            </div>

            <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary);display:block;margin-bottom:0.4rem;">
                ATTACH GRADE SHEET <span style="color:#dc2626;">*</span>
            </label>
            <div id="fileDropZone" style="
                border:2px dashed #d1d5db; border-radius:10px; padding:1.5rem; text-align:center;
                cursor:pointer; transition:border-color 0.2s, background 0.2s; margin-bottom:1rem;
                background:#fafafa;" 
                onclick="document.getElementById('gradeFileInput').click()"
                ondragover="event.preventDefault();this.style.borderColor='var(--primary-purple)';this.style.background='rgba(92,88,237,0.04)'"
                ondragleave="this.style.borderColor='#d1d5db';this.style.background='#fafafa'"
                ondrop="handleFileDrop(event)">
                <div id="fileDropText">
                    <div style="font-size:1.8rem;margin-bottom:0.4rem;">📂</div>
                    <div style="font-weight:700;color:var(--text-primary);font-size:0.9rem;">Click to browse or drag & drop</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:0.25rem;">Excel files only (.xlsx, .xls) · Max 10MB</div>
                </div>
            </div>
            <input type="file" id="gradeFileInput" accept=".xlsx,.xls" style="display:none;" onchange="handleFileSelect(this.files[0])">

            <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary);display:block;margin-bottom:0.4rem;">
                NOTE TO REGISTRAR (optional)
            </label>
            <textarea class="modal-textarea" id="teacherNote" placeholder="e.g. All grades are final and verified..."></textarea>

            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn-confirm-submit" id="confirmSubmitBtn" onclick="confirmSubmit()" disabled
                    style="opacity:0.5;cursor:not-allowed;">
                    Attach a file first
                </button>
            </div>
        </div>
    </div>
    <!-- SheetJS for client-side Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
                    <span>Teacher Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="schedule.php" class="nav-item"><span class="nav-icon">📅</span><span>My Schedule</span></a>
                    <a href="classes.php" class="nav-item active"><span class="nav-icon">📚</span><span>My Classes</span></a>
                    <a href="specialties.php" class="nav-item"><span class="nav-icon">🎯</span><span>My Subjects</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Resources</div>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
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
                    <h1>My Classes</h1>
                    <p class="page-subtitle">Classes assigned to you via sections</p>
                </div>
                <div class="header-actions">
                    <div class="school-year-badge"><span>📚</span><span id="schoolYearLabel">School Year</span></div>
                </div>
            </header>

            <div id="summaryChips" class="summary-chips" style="display:none;"></div>
            <div id="classesList">
                <p style="text-align:center;color:var(--text-secondary);padding:3rem;">Loading classes...</p>
            </div>
        </main>
    </div>

    <script>
        // Store all loaded class data so downloadGrades() can access it
        let allClassesData = [];

        /* ── Excel Download ──────────────────────────────────────────────── */
        function downloadGrades(idx) {
            const cls = allClassesData[idx];
            if (!cls) return;

            const filename = `Grades_${cls.subject_code}_${cls.section}_${cls.school_year || 'SY'}.xlsx`.replace(/[\/:*?"<>|]/g, '_');

            // ── Sheet 1: Grade Sheet ──────────────────────────────────────
            const gradeRows = [
                // Title block
                [`GRADE SHEET`],
                [`Subject: ${cls.subject_name} (${cls.subject_code})`],
                [`Section: ${cls.section} (${cls.section_code})`],
                [`Course: ${cls.course}  |  Year Level: ${cls.year_level}  |  Semester: ${cls.semester}`],
                [`School Year: ${cls.school_year || ''}  |  Units: ${cls.units}`],
                [], // blank
                // Column headers
                ['No.', 'Student ID', 'Student Name', 'Course', 'Year Level', 'Midterm Grade', 'Final Grade', 'Remarks']
            ];

            cls.students.forEach((s, i) => {
                const midterm = (s.midterm_grade !== null && s.midterm_grade !== undefined) ? Number(s.midterm_grade) : '';
                const final_g = (s.final_grade   !== null && s.final_grade   !== undefined) ? Number(s.final_grade)   : '';
                let remarks = '';
                if (final_g !== '') remarks = final_g >= 75 ? 'PASSED' : 'FAILED';
                gradeRows.push([i + 1, s.student_id, s.name, s.course || '', s.year_level || '', midterm, final_g, remarks]);
            });

            // Summary rows
            const dataStart = 8; // row index of first student data (1-based: row 9)
            const dataEnd   = dataStart + cls.students.length - 1;
            const midCol = 'F', finalCol = 'G';

            gradeRows.push([]); // blank
            if (cls.students.length > 0) {
                gradeRows.push(['', '', '', '', 'Average:',
                    { f: `IFERROR(AVERAGE(${midCol}${dataStart}:${midCol}${dataEnd}),"—")` },
                    { f: `IFERROR(AVERAGE(${finalCol}${dataStart}:${finalCol}${dataEnd}),"—")` },
                    ''
                ]);
                gradeRows.push(['', '', '', '', 'Passed:',
                    '',
                    { f: `COUNTIF(${finalCol}${dataStart}:${finalCol}${dataEnd},">=75")` },
                    ''
                ]);
                gradeRows.push(['', '', '', '', 'Failed:',
                    '',
                    { f: `COUNTIF(${finalCol}${dataStart}:${finalCol}${dataEnd},"<75")` },
                    ''
                ]);
            }

            const ws = XLSX.utils.aoa_to_sheet(gradeRows);

            // Column widths
            ws['!cols'] = [
                { wch: 5  },  // No.
                { wch: 14 },  // Student ID
                { wch: 28 },  // Name
                { wch: 22 },  // Course
                { wch: 12 },  // Year Level
                { wch: 14 },  // Midterm
                { wch: 14 },  // Final
                { wch: 10 },  // Remarks
            ];

            // Merge title cells A1:H5
            ws['!merges'] = [
                { s: { r: 0, c: 0 }, e: { r: 0, c: 7 } },
                { s: { r: 1, c: 0 }, e: { r: 1, c: 7 } },
                { s: { r: 2, c: 0 }, e: { r: 2, c: 7 } },
                { s: { r: 3, c: 0 }, e: { r: 3, c: 7 } },
                { s: { r: 4, c: 0 }, e: { r: 4, c: 7 } },
            ];

            // ── Sheet 2: Student List (plain) ─────────────────────────────
            const listRows = [
                ['Student ID', 'Student Name', 'Course', 'Year Level', 'Email', 'Midterm Grade', 'Final Grade', 'Remarks']
            ];
            cls.students.forEach(s => {
                const midterm = (s.midterm_grade !== null && s.midterm_grade !== undefined) ? Number(s.midterm_grade) : '';
                const final_g = (s.final_grade   !== null && s.final_grade   !== undefined) ? Number(s.final_grade)   : '';
                let remarks = '';
                if (final_g !== '') remarks = final_g >= 75 ? 'PASSED' : 'FAILED';
                listRows.push([s.student_id, s.name, s.course || '', s.year_level || '', s.email || '', midterm, final_g, remarks]);
            });
            const ws2 = XLSX.utils.aoa_to_sheet(listRows);
            ws2['!cols'] = [
                { wch: 14 }, { wch: 28 }, { wch: 22 }, { wch: 12 },
                { wch: 26 }, { wch: 14 }, { wch: 14 }, { wch: 10 }
            ];

            // ── Build workbook ────────────────────────────────────────────
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws,  'Grade Sheet');
            XLSX.utils.book_append_sheet(wb, ws2, 'Student List');

            XLSX.writeFile(wb, filename);
        }

        function toggleStudents(id) {
            const wrap = document.getElementById('students-' + id);
            const btn  = document.getElementById('btn-' + id);
            wrap.classList.toggle('open');
            btn.textContent = wrap.classList.contains('open') ? '▲ Hide Students' : '▼ Show Students';
        }

        async function loadClasses() {
            try {
                const res  = await fetch('../../api/teacher/get_classes.php');
                const data = await res.json();
                const container = document.getElementById('classesList');

                if (!data.success) {
                    container.innerHTML = '<div class="content-card"><p style="text-align:center;color:var(--text-secondary);padding:2rem;">Could not load classes.</p></div>';
                    return;
                }

                // Store for use by downloadGrades()
                allClassesData = data.classes;

                if (data.classes.length === 0) {
                    container.innerHTML = '<div class="content-card"><p style="text-align:center;color:var(--text-secondary);padding:3rem;">No classes assigned yet. The registrar needs to assign you as a teacher to section subjects.</p></div>';
                    return;
                }

                // Summary
                const chips = document.getElementById('summaryChips');
                chips.style.display = 'flex';
                let schoolYear = data.classes[0].school_year || '';
                if (schoolYear) document.getElementById('schoolYearLabel').textContent = 'SY ' + schoolYear;
                chips.innerHTML = `
                    <div class="chip">📚 ${data.total_classes} Class${data.total_classes !== 1 ? 'es' : ''}</div>
                    <div class="chip">👥 ${data.total_students} Total Students</div>
                `;

                let html = '<div style="display:grid;gap:1.5rem;">';

                data.classes.forEach((cls, idx) => {
                    const grade = (g) => (g !== null && g !== undefined) ? g : '—';

                    let studentRows = '';
                    if (cls.students.length > 0) {
                        cls.students.forEach(s => {
                            const mid = (s.midterm_grade !== null && s.midterm_grade !== undefined) ? s.midterm_grade : '';
                            const fin = (s.final_grade   !== null && s.final_grade   !== undefined) ? s.final_grade   : '';
                            studentRows += `
                                <tr id="row-${idx}-${s.id}">
                                    <td>${s.student_id}</td>
                                    <td>${s.name}</td>
                                    <td>${s.course || '—'}</td>
                                    <td>${s.year_level || '—'}</td>
                                    <td>
                                        <input type="number" class="grade-input" step="0.01" min="1" max="5"
                                            id="mid-${idx}-${s.id}"
                                            value="${mid}"
                                            placeholder="e.g. 1.5"
                                            style="width:80px;padding:0.3rem 0.5rem;border:1.5px solid #e5e7eb;border-radius:6px;font-size:0.85rem;text-align:center;">
                                    </td>
                                    <td>
                                        <input type="number" class="grade-input" step="0.01" min="1" max="5"
                                            id="fin-${idx}-${s.id}"
                                            value="${fin}"
                                            placeholder="e.g. 2.0"
                                            style="width:80px;padding:0.3rem 0.5rem;border:1.5px solid #e5e7eb;border-radius:6px;font-size:0.85rem;text-align:center;">
                                    </td>
                                    <td>
                                        <span id="remarks-${idx}-${s.id}" class="${fin <= 3.0 && fin !== '' ? 'grade-pass' : fin > 3.0 && fin !== '' ? 'grade-fail' : 'grade-none'}">
                                            ${fin !== '' ? (parseFloat(fin) <= 3.0 ? 'Passed' : 'Failed') : '—'}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-save-grade"
                                            onclick="saveGrade(${idx}, ${s.id}, ${cls.subject_id}, '${cls.semester}', '${cls.school_year}')"
                                            id="save-btn-${idx}-${s.id}">
                                            💾 Save
                                        </button>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        studentRows = '<tr><td colspan="8" style="text-align:center;color:var(--text-secondary);">No students enrolled in this section</td></tr>';
                    }

                    html += `
                    <div class="content-card">
                        <div class="card-header">
                            <div class="class-header-bar">
                                <div>
                                    <h2 class="card-title" style="margin-bottom:0.4rem;">${cls.subject_code} — ${cls.subject_name}</h2>
                                    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">
                                        <span class="section-badge">🏫 ${cls.section} (${cls.section_code})</span>
                                        <span class="section-badge">📘 ${cls.course}</span>
                                        <span class="section-badge">Year ${cls.year_level} • Sem ${cls.semester}</span>
                                        <span class="section-badge">⚡ ${cls.units} units</span>
                                    </div>
                                </div>
                                <div class="class-actions">
                                    <button class="btn-download-excel" onclick="downloadGrades(${idx})">
                                        📥 Download Excel
                                    </button>
                                    <button class="btn-submit-grades" id="submit-btn-${idx}"
                                        onclick="openSubmitModal(${idx})"
                                        style="background: var(--primary-purple,#5C58ED); color: white;">
                                        📋 Submit to Registrar
                                    </button>
                                    <span id="submit-status-${idx}" class="submit-status-badge" style="display:none;"></span>
                                    <button class="collapsible-btn" id="btn-${idx}" onclick="toggleStudents(${idx})">▼ Show Students (${cls.student_count})</button>
                                </div>
                            </div>
                        </div>

                        <div class="info-grid">
                            <div class="info-item">
                                <div class="label">📅 Schedule</div>
                                <div class="value">${cls.schedule}</div>
                            </div>
                            <div class="info-item">
                                <div class="label">📍 Room</div>
                                <div class="value">${cls.rooms}</div>
                            </div>
                            <div class="info-item">
                                <div class="label">👥 Students</div>
                                <div class="value">${cls.student_count} enrolled</div>
                            </div>
                            <div class="info-item">
                                <div class="label">🎓 School Year</div>
                                <div class="value">${cls.school_year}</div>
                            </div>
                        </div>

                        <div class="student-table-wrap" id="students-${idx}">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Course</th>
                                        <th>Year Level</th>
                                        <th>Midterm Grade</th>
                                        <th>Final Grade</th>
                                        <th>Remarks</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>${studentRows}</tbody>
                            </table>
                        </div>
                    </div>`;
                });

                html += '</div>';
                container.innerHTML = html;

                // Load existing submission statuses for each class
                checkSubmissionStatuses();

            } catch (err) {
                document.getElementById('classesList').innerHTML =
                    '<div class="content-card"><p style="text-align:center;color:var(--text-secondary);padding:2rem;">Failed to load classes. Please try again.</p></div>';
            }
        }

        loadClasses();

        /* ── Inline Grade Saving ─────────────────────────────────────── */
        async function saveGrade(classIdx, studentUserId, subjectId, semester, schoolYear) {
            const midInput = document.getElementById('mid-' + classIdx + '-' + studentUserId);
            const finInput = document.getElementById('fin-' + classIdx + '-' + studentUserId);
            const saveBtn  = document.getElementById('save-btn-' + classIdx + '-' + studentUserId);
            const remarks  = document.getElementById('remarks-' + classIdx + '-' + studentUserId);

            const midVal = midInput.value.trim();
            const finVal = finInput.value.trim();

            if (midVal === '' && finVal === '') {
                showToast('❌ Please enter at least one grade before saving.', '#991b1b', '#fee2e2');
                return;
            }

            // Validate Philippine grade range 1.0–5.0
            const validateGrade = (v, label) => {
                if (v === '') return true;
                const n = parseFloat(v);
                if (isNaN(n) || n < 1.0 || n > 5.0) {
                    showToast('❌ ' + label + ' must be between 1.0 and 5.0 (Philippine grading scale).', '#991b1b', '#fee2e2');
                    return false;
                }
                return true;
            };
            if (!validateGrade(midVal, 'Midterm grade')) return;
            if (!validateGrade(finVal, 'Final grade'))   return;

            saveBtn.textContent = 'Saving...';
            saveBtn.classList.add('saving');
            saveBtn.disabled = true;

            try {
                const body = {
                    student_id:    studentUserId,
                    subject_id:    subjectId,
                    semester:      semester,
                    school_year:   schoolYear,
                };
                if (midVal !== '') body.midterm_grade = parseFloat(midVal);
                if (finVal !== '') body.final_grade   = parseFloat(finVal);

                const r = await fetch('../../api/teacher/submit_grades.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(body)
                });
                const d = await r.json();

                if (d.success) {
                    // Update remarks display
                    if (finVal !== '') {
                        const passed = parseFloat(finVal) <= 3.0;
                        remarks.textContent  = passed ? 'Passed' : 'Failed';
                        remarks.className    = passed ? 'grade-pass' : 'grade-fail';
                    }
                    // Update local cache
                    const student = allClassesData[classIdx].students.find(s => s.id === studentUserId);
                    if (student) {
                        if (midVal !== '') student.midterm_grade = parseFloat(midVal);
                        if (finVal !== '') student.final_grade   = parseFloat(finVal);
                    }
                    saveBtn.textContent = '✅ Saved';
                    saveBtn.classList.remove('saving');
                    saveBtn.classList.add('saved');
                    saveBtn.disabled = false;
                    // Reset button after 2s
                    setTimeout(() => {
                        saveBtn.textContent = '💾 Save';
                        saveBtn.classList.remove('saved');
                    }, 2000);
                    showToast('✅ Grade saved successfully!', '#065f46', '#d1fae5');
                } else {
                    saveBtn.textContent = '💾 Save';
                    saveBtn.classList.remove('saving');
                    saveBtn.disabled = false;
                    showToast('❌ ' + (d.message || 'Failed to save grade'), '#991b1b', '#fee2e2');
                }
            } catch (e) {
                saveBtn.textContent = '💾 Save';
                saveBtn.classList.remove('saving');
                saveBtn.disabled = false;
                showToast('❌ Network error: ' + e.message, '#991b1b', '#fee2e2');
            }
        }

        /* ── Submit to Registrar ─────────────────────────────────────── */
        let pendingSubmitIdx = null;

        async function checkSubmissionStatuses() {
            for (let i = 0; i < allClassesData.length; i++) {
                const cls = allClassesData[i];
                if (!cls.section_id || !cls.subject_id) continue;
                try {
                    const r = await fetch('../../api/teacher/submit_grade_sheet.php?subject_id=' + cls.subject_id + '&section_id=' + cls.section_id);
                    const d = await r.json();
                    if (d.success && d.submission) updateSubmitUI(i, d.submission.status, d.submission);
                } catch(e) {}
            }
        }

        function updateSubmitUI(idx, status, submission) {
            const btn   = document.getElementById('submit-btn-' + idx);
            const badge = document.getElementById('submit-status-' + idx);
            if (!btn || !badge) return;

            // Reset
            btn.style.display = 'inline-flex';
            badge.style.display = 'none';

            if (status === 'pending') {
                // Hide button — can't resubmit while under review
                btn.style.display = 'none';
                badge.style.display = 'inline-flex';
                badge.className = 'submit-status-badge badge-pending';
                badge.textContent = '\u23F3 Pending Review';

            } else if (status === 'approved') {
                // Show resubmit button + approved badge
                btn.style.background = '#059669';
                btn.style.color = '#fff';
                btn.innerHTML = '\uD83D\uDD04 Resubmit Grades';
                btn.dataset.mode = 'resubmit';
                badge.style.display = 'inline-flex';
                badge.className = 'submit-status-badge badge-approved';
                badge.textContent = '\u2705 Approved';

            } else if (status === 'rejected') {
                // Show resubmit button + rejected badge with reason
                btn.style.background = '#dc2626';
                btn.style.color = '#fff';
                btn.innerHTML = '\uD83D\uDD04 Resubmit Grades';
                btn.dataset.mode = 'resubmit';
                badge.style.display = 'inline-flex';
                badge.className = 'submit-status-badge badge-rejected';
                badge.textContent = '\u274C Rejected' + (submission && submission.registrar_note ? ': ' + submission.registrar_note : '');
            }
        }

        function openSubmitModal(idx) {
            const cls = allClassesData[idx];
            if (!cls) return;
            pendingSubmitIdx = idx;

            // Detect resubmission via data attribute set by updateSubmitUI
            const submitBtn = document.getElementById('submit-btn-' + idx);
            const isResubmit = submitBtn && submitBtn.dataset.mode === 'resubmit';

            const graded = cls.students.filter(s => s.final_grade !== null && s.final_grade !== undefined).length;
            const total  = cls.students.length;

            // Update modal title based on context
            const modalTitle = document.querySelector('#submitModal .modal-title');
            const modalSubtitle = document.querySelector('#submitModal .modal-subtitle');
            if (isResubmit) {
                modalTitle.textContent = '\uD83D\uDD04 Resubmit Updated Grade Sheet';
                modalSubtitle.textContent = 'Upload a corrected Excel file. The registrar will review your updated submission and the previous grades will be overwritten upon approval.';
            } else {
                modalTitle.textContent = '\uD83D\uDCCB Submit Grade Sheet to Registrar';
                modalSubtitle.textContent = 'Attach your Excel grade sheet file. The registrar will be able to download and review it.';
            }

            document.getElementById('modalSubject').textContent = cls.subject_code + ' — ' + cls.subject_name;
            document.getElementById('modalSection').textContent = cls.section + ' (' + (cls.section_code || '') + ')';
            document.getElementById('modalGraded').textContent  = graded + ' / ' + total + ' students have final grades';
            document.getElementById('modalSem').textContent     = (cls.semester || '—') + ' | ' + (cls.school_year || '—');
            document.getElementById('teacherNote').value = '';

            // Reset file picker
            selectedGradeFile = null;
            document.getElementById('gradeFileInput').value = '';
            document.getElementById('fileDropText').innerHTML =
                '<div style="font-size:1.8rem;margin-bottom:0.4rem;">\uD83D\uDCC2</div>' +
                '<div style="font-weight:700;color:var(--text-primary);font-size:0.9rem;">Click to browse or drag & drop</div>' +
                '<div style="font-size:0.78rem;color:var(--text-secondary);margin-top:0.25rem;">Excel files only (.xlsx, .xls) \u00B7 Max 10MB</div>';
            var zone = document.getElementById('fileDropZone');
            zone.style.borderColor = '#d1d5db';
            zone.style.background  = '#fafafa';
            var btn = document.getElementById('confirmSubmitBtn');
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor  = 'not-allowed';
            btn.textContent   = 'Attach a file first';
            document.getElementById('submitModal').classList.add('open');
        }

        function closeModal() {
            document.getElementById('submitModal').classList.remove('open');
            pendingSubmitIdx = null;
        }

        // Holds the file the teacher selected
        let selectedGradeFile = null;

        function handleFileSelect(file) {
            if (!file) return;
            setSelectedFile(file);
        }

        function handleFileDrop(event) {
            event.preventDefault();
            var zone = document.getElementById('fileDropZone');
            zone.style.borderColor = '#d1d5db';
            zone.style.background  = '#fafafa';
            var file = event.dataTransfer.files[0];
            if (file) setSelectedFile(file);
        }

        function setSelectedFile(file) {
            var allowed = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                           'application/vnd.ms-excel'];
            var ext = file.name.split('.').pop().toLowerCase();
            if (!allowed.includes(file.type) && ext !== 'xlsx' && ext !== 'xls') {
                showToast('\u274C Please attach an Excel file (.xlsx or .xls)', '#991b1b', '#fee2e2');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                showToast('\u274C File is too large. Max 10MB.', '#991b1b', '#fee2e2');
                return;
            }
            selectedGradeFile = file;

            // Update drop zone UI
            var zone = document.getElementById('fileDropZone');
            zone.style.borderColor = '#16a34a';
            zone.style.background  = '#f0fdf4';
            document.getElementById('fileDropText').innerHTML =
                '<div style="font-size:1.6rem;margin-bottom:0.3rem;">✅</div>' +
                '<div style="font-weight:700;color:#15803d;font-size:0.9rem;">' + escHtmlModal(file.name) + '</div>' +
                '<div style="font-size:0.78rem;color:#6b7280;margin-top:0.2rem;">' + (file.size / 1024).toFixed(1) + ' KB &nbsp;·&nbsp; <span style="cursor:pointer;color:var(--primary-purple);text-decoration:underline;" onclick="clearFile(event)">Remove</span></div>';

            // Enable submit button
            var btn = document.getElementById('confirmSubmitBtn');
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor  = 'pointer';
            btn.textContent   = 'Submit Grades';
        }

        function clearFile(e) {
            e.stopPropagation();
            selectedGradeFile = null;
            document.getElementById('gradeFileInput').value = '';
            var zone = document.getElementById('fileDropZone');
            zone.style.borderColor = '#d1d5db';
            zone.style.background  = '#fafafa';
            document.getElementById('fileDropText').innerHTML =
                '<div style="font-size:1.8rem;margin-bottom:0.4rem;">\uD83D\uDCC2</div>' +
                '<div style="font-weight:700;color:var(--text-primary);font-size:0.9rem;">Click to browse or drag & drop</div>' +
                '<div style="font-size:0.78rem;color:var(--text-secondary);margin-top:0.25rem;">Excel files only (.xlsx, .xls) \u00B7 Max 10MB</div>';
            var btn = document.getElementById('confirmSubmitBtn');
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor  = 'not-allowed';
            btn.textContent   = 'Attach a file first';
        }

        function escHtmlModal(str) {
            return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        async function confirmSubmit() {
            if (pendingSubmitIdx === null) return;
            if (!selectedGradeFile) {
                showToast('\u274C Please attach a grade sheet file first.', '#991b1b', '#fee2e2');
                return;
            }

            const cls  = allClassesData[pendingSubmitIdx];
            const note = document.getElementById('teacherNote').value.trim();
            const btn  = document.getElementById('confirmSubmitBtn');

            btn.disabled = true;
            btn.style.opacity = '0.7';
            btn.textContent = 'Submitting...';

            try {
                const form = new FormData();
                form.append('subject_id',   cls.subject_id);
                form.append('section_id',   cls.section_id);
                form.append('semester',     cls.semester    || '');
                form.append('school_year',  cls.school_year || '');
                form.append('teacher_note', note);
                form.append('grade_file',   selectedGradeFile, selectedGradeFile.name);

                const r = await fetch('../../api/teacher/submit_grade_sheet.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: form
                });

                const ct = r.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    const txt = await r.text();
                    throw new Error('Server error: ' + txt.substring(0, 120));
                }

                const d = await r.json();
                if (d.success) {
                    updateSubmitUI(pendingSubmitIdx, 'pending', {});
                    closeModal();
                    showToast('\u2705 Grade sheet submitted to registrar!', '#065f46', '#d1fae5');
                } else {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.textContent = 'Submit Grades';
                    showToast('\u274C ' + (d.message || 'Submission failed'), '#991b1b', '#fee2e2');
                }
            } catch(e) {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.textContent = 'Submit Grades';
                showToast('\u274C ' + e.message, '#991b1b', '#fee2e2');
            }
        }

        document.getElementById('submitModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
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
    <script src="../../js/session-monitor.js"></script>
    <script src="../../js/apply-branding.js"></script>

    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
      <a href="dashboard.php" class="mobile-nav-item" data-page="dashboard">
        <span class="mobile-nav-icon">📊</span><span>Home</span>
      </a>
      <a href="schedule.php" class="mobile-nav-item" data-page="schedule">
        <span class="mobile-nav-icon">📅</span><span>Schedule</span>
      </a>
      <a href="classes.php" class="mobile-nav-item" data-page="classes">
        <span class="mobile-nav-icon">📚</span><span>Classes</span>
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
