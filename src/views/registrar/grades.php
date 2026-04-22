<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('registrar');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades Management - Registrar Dashboard</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        /* Tabs */
        .tab-bar { display:flex; gap:0.25rem; border-bottom:2px solid #e5e7eb; margin-bottom:1.5rem; }
        .tab-btn {
            padding:0.65rem 1.25rem; background:none; border:none; border-bottom:3px solid transparent;
            font-weight:700; font-size:0.875rem; cursor:pointer; color:var(--text-secondary);
            margin-bottom:-2px; transition:color 0.15s, border-color 0.15s;
        }
        .tab-btn.active { color:var(--primary-purple); border-bottom-color:var(--primary-purple); }
        .tab-btn .badge {
            display:inline-flex; align-items:center; justify-content:center;
            background:var(--primary-purple); color:#fff;
            border-radius:999px; font-size:0.7rem; font-weight:800;
            padding:0 6px; min-width:18px; height:18px; margin-left:6px;
        }
        .tab-panel { display:none; }
        .tab-panel.active { display:block; }

        /* Submission cards */
        .sub-card {
            border:1.5px solid #e5e7eb; border-radius:var(--radius-md);
            padding:1.25rem 1.5rem; margin-bottom:1rem;
            transition:box-shadow 0.15s;
        }
        .sub-card:hover { box-shadow:0 2px 12px rgba(0,0,0,0.08); }
        .sub-card-header { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.75rem; }
        .sub-title { font-weight:800; font-size:1rem; color:var(--text-primary); }
        .sub-meta  { font-size:0.82rem; color:var(--text-secondary); margin-top:0.2rem; }
        .sub-chips { display:flex; gap:0.5rem; flex-wrap:wrap; margin:0.75rem 0; }
        .sub-chip  {
            padding:0.25rem 0.7rem; border-radius:20px; font-size:0.78rem; font-weight:600;
            background:rgba(124,58,237,0.08); color:var(--primary-purple);
        }
        .status-badge {
            display:inline-flex; align-items:center; gap:0.3rem;
            padding:0.35rem 0.85rem; border-radius:20px; font-size:0.8rem; font-weight:700;
        }
        .badge-pending  { background:#fef3c7; color:#92400e; }
        .badge-approved { background:#d1fae5; color:#065f46; }
        .badge-rejected { background:#fee2e2; color:#991b1b; }
        .sub-note { font-size:0.85rem; color:var(--text-secondary); background:var(--background-main); padding:0.65rem 0.9rem; border-radius:var(--radius-md); margin:0.75rem 0 0; font-style:italic; }
        .sub-actions { display:flex; gap:0.6rem; flex-wrap:wrap; margin-top:1rem; align-items:center; }
        .btn-approve {
            padding:0.5rem 1.1rem; background:#065f46; color:#fff; border:none;
            border-radius:var(--radius-md); font-weight:700; font-size:0.85rem; cursor:pointer;
        }
        .btn-approve:hover { background:#047857; }
        .btn-reject {
            padding:0.5rem 1.1rem; background:#fff; color:#dc2626; border:1.5px solid #dc2626;
            border-radius:var(--radius-md); font-weight:700; font-size:0.85rem; cursor:pointer;
        }
        .btn-reject:hover { background:#fee2e2; }
        .registrar-note-input {
            width:100%; padding:0.6rem 0.85rem; border:1.5px solid #e5e7eb;
            border-radius:var(--radius-md); font-size:0.85rem; font-family:inherit;
            margin-top:0.75rem; box-sizing:border-box;
        }
        .registrar-note-input:focus { outline:none; border-color:var(--primary-purple); }
        .reviewed-info { font-size:0.8rem; color:var(--text-secondary); margin-top:0.5rem; }
        .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-secondary); }
        .empty-icon  { font-size:2.5rem; display:block; margin-bottom:0.75rem; }
        .btn-download-file {
            display:inline-flex; align-items:center; gap:0.4rem;
            padding:0.45rem 1rem; background:#1d6f42; color:#fff;
            border:none; border-radius:var(--radius-md); font-size:0.82rem;
            font-weight:700; cursor:pointer; text-decoration:none;
            transition:background 0.15s;
        }
        .btn-download-file:hover { background:#155231; }
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
                    <a href="manage_loads.php" class="nav-item"><span class="nav-icon">📚</span><span>Study Loads</span></a>
                    <a href="grades.php" class="nav-item active"><span class="nav-icon">🎓</span><span>Grades</span></a>
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
                    <h1>Grades Management</h1>
                    <p class="page-subtitle">View all grades and review teacher submissions</p>
                </div>
            </header>

            <!-- Tab bar -->
            <div class="content-card">
                <div class="tab-bar">
                    <button class="tab-btn active" onclick="switchTab('submissions')">
                        📋 Grade Submissions
                        <span class="badge" id="pendingBadge" style="display:none;">0</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('all')">📊 All Grades</button>
                </div>

                <!-- ── Tab: Submissions ── -->
                <div class="tab-panel active" id="tab-submissions">
                    <div style="display:flex;gap:0.6rem;flex-wrap:wrap;margin-bottom:1.25rem;">
                        <button class="tab-btn active" id="filter-pending"  onclick="filterSubmissions('pending')"  style="border:1.5px solid #e5e7eb;border-radius:var(--radius-md);padding:0.4rem 1rem;">⏳ Pending</button>
                        <button class="tab-btn"        id="filter-approved" onclick="filterSubmissions('approved')" style="border:1.5px solid #e5e7eb;border-radius:var(--radius-md);padding:0.4rem 1rem;">✅ Approved</button>
                        <button class="tab-btn"        id="filter-rejected" onclick="filterSubmissions('rejected')" style="border:1.5px solid #e5e7eb;border-radius:var(--radius-md);padding:0.4rem 1rem;">❌ Rejected</button>
                        <button class="tab-btn"        id="filter-all"      onclick="filterSubmissions('')"         style="border:1.5px solid #e5e7eb;border-radius:var(--radius-md);padding:0.4rem 1rem;">All</button>
                    </div>
                    <div id="submissionsList">
                        <p style="text-align:center;color:var(--text-secondary);padding:2rem;">Loading submissions...</p>
                    </div>
                </div>

                <!-- ── Tab: All Grades ── -->
                <div class="tab-panel" id="tab-all">
                    <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
                        <select id="semesterFilter" onchange="loadGrades()" style="padding:0.5rem;border-radius:var(--radius-md);border:1px solid #ddd;">
                            <option value="">All Semesters</option>
                            <option value="First Semester">First Semester</option>
                            <option value="Second Semester">Second Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                        <select id="yearFilter" onchange="loadGrades()" style="padding:0.5rem;border-radius:var(--radius-md);border:1px solid #ddd;">
                            <option value="">All School Years</option>
                            <option value="2024-2025">2024-2025</option>
                            <option value="2023-2024">2023-2024</option>
                            <option value="2022-2023">2022-2023</option>
                        </select>
                    </div>
                    <div id="gradesTable">Loading...</div>
                </div>
            </div>
        </main>
    </div>

    <script>
        /* ── Tab switching ──────────────────────────────────────────────── */
        function switchTab(name) {
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.tab-bar .tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + name).classList.add('active');
            event.currentTarget.classList.add('active');
            if (name === 'all') loadGrades();
        }

        /* ── Submissions ────────────────────────────────────────────────── */
        let currentFilter = 'pending';

        function filterSubmissions(status) {
            currentFilter = status;
            document.querySelectorAll('[id^="filter-"]').forEach(b => b.classList.remove('active'));
            const id = 'filter-' + (status || 'all');
            const el = document.getElementById(id);
            if (el) el.classList.add('active');
            loadSubmissions(status);
        }

        async function loadSubmissions(status) {
            status = (status === undefined) ? currentFilter : status;
            const url = '../../api/registrar/get_grade_submissions.php' + (status ? '?status=' + status : '');
            const list = document.getElementById('submissionsList');
            list.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">Loading...</p>';
            try {
                const r = await fetch(url);
                const d = await r.json();
                if (!d.success) { list.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">Could not load submissions.</p>'; return; }

                // Update pending badge
                const pendingCount = d.counts.pending || 0;
                const badge = document.getElementById('pendingBadge');
                badge.textContent = pendingCount;
                badge.style.display = pendingCount > 0 ? 'inline-flex' : 'none';

                if (d.submissions.length === 0) {
                    list.innerHTML = '<div class="empty-state"><span class="empty-icon">📭</span>No ' + (status || '') + ' submissions found.</div>';
                    return;
                }

                list.innerHTML = d.submissions.map(sub => buildSubmissionCard(sub)).join('');
            } catch(e) {
                list.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">Error loading submissions.</p>';
            }
        }

        function buildSubmissionCard(sub) {
            const statusClass = { pending: 'badge-pending', approved: 'badge-approved', rejected: 'badge-rejected' }[sub.status] || '';
            const statusLabel = { pending: '⏳ Pending Review', approved: '✅ Approved', rejected: '❌ Rejected' }[sub.status] || sub.status;

            let actionsHtml = '';
            if (sub.status === 'pending') {
                actionsHtml = `
                    <div class="sub-actions">
                        <input type="text" class="registrar-note-input" id="note-${sub.id}"
                               placeholder="Optional note to teacher..." style="flex:1;min-width:200px;">
                        <button class="btn-approve" onclick="reviewSubmission(${sub.id},'approve')">✅ Approve</button>
                        <button class="btn-reject"  onclick="reviewSubmission(${sub.id},'reject')">❌ Reject</button>
                    </div>`;
            }

            const noteHtml = sub.teacher_note
                ? `<div class="sub-note">💬 Teacher note: ${escHtml(sub.teacher_note)}</div>` : '';
            const registrarNoteHtml = sub.registrar_note
                ? `<div class="sub-note">📝 Registrar note: ${escHtml(sub.registrar_note)}</div>` : '';
            const reviewedHtml = sub.reviewed_at
                ? `<div class="reviewed-info">Reviewed by ${escHtml(sub.reviewed_by_name || '—')} on ${escHtml(sub.reviewed_at)}</div>` : '';

            return `
            <div class="sub-card" id="sub-${sub.id}">
                <div class="sub-card-header">
                    <div>
                        <div class="sub-title">${escHtml(sub.subject_code)} — ${escHtml(sub.subject_name)}</div>
                        <div class="sub-meta">Submitted by <strong>${escHtml(sub.teacher_name)}</strong> on ${escHtml(sub.submitted_at)}</div>
                    </div>
                    <span class="status-badge ${statusClass}">${statusLabel}</span>
                </div>
                <div class="sub-chips">
                    <span class="sub-chip">🏫 ${escHtml(sub.section_name)}</span>
                    <span class="sub-chip">📘 ${escHtml(sub.course || '—')}</span>
                    <span class="sub-chip">Year ${escHtml(sub.year_level || '—')}</span>
                    <span class="sub-chip">📅 ${escHtml(sub.semester || '—')} | SY ${escHtml(sub.school_year || '—')}</span>
                    <span class="sub-chip">👥 ${sub.graded_count}/${sub.student_count} graded</span>
                    <span class="sub-chip">⚡ ${escHtml(sub.units)} units</span>
                </div>
                ${(sub.file_path || sub.has_file_data)
                    ? '<a class="btn-download-file" href="../../api/registrar/download_grade_sheet.php?id=' + sub.id + '" download>📥 Download Grade Sheet (Excel)</a>'
                    : '<span style="font-size:0.82rem;color:var(--text-secondary);">⚠️ No file attached</span>'
                }
                ${noteHtml}
                ${registrarNoteHtml}
                ${reviewedHtml}
                ${actionsHtml}
            </div>`;
        }

        async function reviewSubmission(id, action) {
            const note = (document.getElementById('note-' + id) || {}).value || '';
            const label = action === 'approve' ? 'approve' : 'reject';
            if (!confirm('Are you sure you want to ' + label + ' this submission?' +
                (action === 'approve' ? '\n\nApproving will automatically extract and record all student grades from the uploaded Excel file.' : ''))) return;

            try {
                const r = await fetch('../../api/registrar/review_grade_submission.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    credentials: 'same-origin',
                    body: JSON.stringify({ submission_id: id, action, note })
                });
                const d = await r.json();
                if (d.success) {
                    loadSubmissions(currentFilter);
                    let msg = d.message;
                    if (action === 'approve' && d.grades_extracted !== undefined) {
                        msg += ' ' + d.grades_extracted + ' grade(s) extracted and recorded.';
                    }
                    if (d.extract_warnings && d.extract_warnings.length > 0) {
                        msg += ' ⚠️ ' + d.extract_warnings.join('; ');
                        showToast(msg, '#92400e', '#fef3c7');
                    } else {
                        showToast(msg, action === 'approve' ? '#065f46' : '#991b1b', action === 'approve' ? '#d1fae5' : '#fee2e2');
                    }
                    if (d.debug) console.log('Grade extraction debug:', JSON.stringify(d.debug, null, 2));
                    if (d.extract_warnings) console.warn('Warnings:', d.extract_warnings);
                } else {
                    showToast('Error: ' + (d.message || 'Failed'), '#991b1b', '#fee2e2');
                }
            } catch(e) {
                showToast('Network error. Please try again.', '#991b1b', '#fee2e2');
            }
        }

        /* ── All Grades ─────────────────────────────────────────────────── */
        async function loadGrades() {
            const semester   = document.getElementById('semesterFilter').value;
            const schoolYear = document.getElementById('yearFilter').value;
            const params = new URLSearchParams();
            if (semester)   params.append('semester', semester);
            if (schoolYear) params.append('school_year', schoolYear);

            const container = document.getElementById('gradesTable');
            container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">Loading...</p>';

            try {
                const r = await fetch('../../api/registrar/get_grades.php?' + params);
                const d = await r.json();
                if (d.success && d.grades && d.grades.length > 0) {
                    let html = '<div style="overflow-x:auto;"><table class="data-table"><thead><tr>' +
                        '<th>Student ID</th><th>Student Name</th><th>Course</th><th>Year</th>' +
                        '<th>Subject</th><th>Units</th><th>Midterm</th><th>Final</th><th>Remarks</th>' +
                        '<th>Semester</th><th>SY</th></tr></thead><tbody>';
                    d.grades.forEach(g => {
                        const fmtGrade = v => (v != null && v !== '' && !isNaN(parseFloat(v))) ? parseFloat(v).toFixed(2) : '—';
                        const mid = fmtGrade(g.midterm_grade);
                        const fin = fmtGrade(g.final_grade);
                        const rc  = g.remarks === 'Passed' ? 'var(--status-approved)' : g.remarks === 'Failed' ? 'var(--status-rejected)' : 'var(--text-secondary)';
                        html += `<tr>
                            <td>${escHtml(g.student_number || g.student_id || '—')}</td>
                            <td>${escHtml(g.student_name  || '—')}</td>
                            <td>${escHtml(g.course        || '—')}</td>
                            <td>${escHtml(g.year_level    || '—')}</td>
                            <td>${escHtml((g.subject_code || '') + (g.subject_name ? ' - ' + g.subject_name : ''))}</td>
                            <td>${escHtml(String(g.units  || '—'))}</td>
                            <td>${mid}</td>
                            <td>${fin}</td>
                            <td><span style="color:${rc};font-weight:600;">${escHtml(g.remarks || '—')}</span></td>
                            <td>${escHtml(g.semester    || '—')}</td>
                            <td>${escHtml(g.school_year || '—')}</td>
                        </tr>`;
                    });
                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">No grades found.</p>';
                }
            } catch(e) {
                container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">Failed to load grades.</p>';
            }
        }

        /* ── Utilities ──────────────────────────────────────────────────── */
        function escHtml(str) {
            return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        function showToast(msg, color, bg) {
            var old = document.getElementById('_toast');
            if (old) old.remove();
            var t = document.createElement('div');
            t.id = '_toast';
            t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 20px;border-radius:12px;font-weight:700;font-size:0.9rem;box-shadow:0 4px 20px rgba(0,0,0,0.15);opacity:0;transition:opacity 0.25s;pointer-events:none;';
            t.style.color = color || '#065f46';
            t.style.background = bg || '#d1fae5';
            t.textContent = msg;
            document.body.appendChild(t);
            requestAnimationFrame(function() { t.style.opacity = '1'; });
            setTimeout(function() { t.style.opacity = '0'; setTimeout(function() { t.remove(); }, 300); }, 3500);
        }

        // Load pending submissions on page load
        loadSubmissions('pending');
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
