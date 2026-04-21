<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('student');
$student_course = strtolower($_SESSION['course'] ?? '');
$show_bsit_bg = (strpos($student_course, 'bsit') !== false || strpos($student_course, 'information technology') !== false);
$show_bshtm_bg = (strpos($student_course, 'bshtm') !== false || strpos($student_course, 'hospitality') !== false || strpos($student_course, 'tourism') !== false || strpos($student_course, 'htm') !== false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Load - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        .section-banner {
            background: linear-gradient(135deg, var(--background-sidebar), var(--primary-purple));
            border-radius: var(--radius-lg);
            padding: 1.5rem 2rem;
            color: var(--text-white);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .section-banner .sb-icon { font-size: 2.5rem; }
        .section-banner .sb-name { font-size: 1.4rem; font-weight: 800; }
        .section-banner .sb-meta { font-size: .88rem; opacity: .85; margin-top: .3rem; }
        .section-banner.no-section { background: linear-gradient(135deg, var(--text-secondary), var(--text-light)); }

        /* Tabs */
        .tab-bar { display: flex; gap: 0; border-bottom: 2px solid var(--border-color); margin-bottom: 1.5rem; }
        .tab-btn {
            padding: .65rem 1.4rem;
            font-size: .92rem;
            font-weight: 600;
            border: none;
            background: none;
            cursor: pointer;
            color: var(--text-secondary);
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all .2s;
            position: relative;
        }
        .tab-btn.active { color: var(--primary-purple); border-bottom-color: var(--primary-purple); }
        .tab-btn:hover:not(.active) { color: var(--text-primary); background: var(--background-main); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        .badge-count {
            display: inline-block;
            background: var(--secondary-pink);
            color: var(--text-white);
            font-size: .68rem;
            font-weight: 700;
            padding: .1rem .42rem;
            border-radius: 10px;
            margin-left: .35rem;
            vertical-align: middle;
        }

        /* Request status badges */
        .req-pending  { background: rgba(212,169,106,0.2); color: var(--text-primary); }
        .req-approved { background: rgba(90,158,138,0.2); color: var(--secondary-green); }
        .req-rejected { background: rgba(184,92,92,0.15); color: var(--secondary-pink); }
        .req-add  { background: rgba(61,107,159,0.15); color: var(--primary-purple-dark); }
        .req-drop { background: rgba(184,92,92,0.12); color: var(--secondary-pink); }
        .req-badge { padding: .2rem .6rem; border-radius: 1rem; font-size: .75rem; font-weight: 700; }

        /* Subject row actions */
        .drop-btn {
            padding: .25rem .7rem;
            font-size: .78rem;
            border: 1.5px solid var(--secondary-pink);
            background: transparent;
            color: var(--secondary-pink);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            transition: all .2s;
        }
        .drop-btn:hover { background: var(--secondary-pink); color: var(--text-white); }
        .pending-badge {
            display: inline-flex; align-items: center; gap: .35rem;
            font-size: .72rem; font-weight: 700;
            color: #92400e;
            background: #fef3c7;
            border: 1.5px solid #fbbf24;
            padding: .25rem .65rem;
            border-radius: 999px;
            white-space: nowrap;
        }
        .pending-badge::before {
            content: '';
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #f59e0b;
            display: inline-block;
            animation: pendingPulse 1.4s ease-in-out infinite;
        }
        @keyframes pendingPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: .4; transform: scale(.7); }
        }

        /* Add subject card */
        .add-subject-card {
            padding: 1rem 1.25rem;
            background: var(--background-main);
            border-radius: var(--radius-md);
            border: 1.5px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: .5rem;
            transition: border-color .2s;
        }
        .add-subject-card:hover { border-color: var(--primary-purple); }
        .add-btn {
            padding: .3rem .85rem;
            font-size: .78rem;
            background: var(--primary-purple);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .add-btn:hover { opacity: .85; }
        .add-btn:disabled { opacity: .5; cursor: not-allowed; }

        /* Modal */
        .modal {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,0.45);
            backdrop-filter: blur(4px);
            z-index:1000; align-items:center; justify-content:center;
            padding: 1rem;
        }
        .modal.active { display:flex; animation: modalFadeIn .2s ease; }
        @keyframes modalFadeIn { from { opacity:0; } to { opacity:1; } }
        .modal-content {
            background:var(--background-card);
            border-radius:var(--radius-lg);
            width:90%; max-width:480px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: modalSlideUp .22s ease;
        }
        @keyframes modalSlideUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
        .modal-header {
            display: flex; align-items: center; gap: .85rem;
            padding: 1.4rem 1.5rem 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .modal-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem; flex-shrink: 0;
        }
        .modal-icon-add  { background: rgba(61,107,159,0.12); }
        .modal-icon-drop { background: rgba(184,92,92,0.12); }
        .modal-title { font-size:1.05rem; font-weight:700; color:var(--text-primary); }
        .modal-subtitle { font-size:.8rem; color:var(--text-secondary); margin-top:.1rem; }
        .modal-body { padding: 1.25rem 1.5rem; }
        .modal-subject {
            background:var(--background-main);
            border-radius:var(--radius-md);
            padding:.8rem 1rem;
            margin-bottom:1.1rem;
            border: 1px solid var(--border-color);
        }
        .modal-subject-label { font-size:.72rem; font-weight:600; letter-spacing:.6px; text-transform:uppercase; color:var(--text-secondary); margin-bottom:.3rem; }
        .modal-subject .code { font-weight:700; color:var(--primary-purple); font-size:.95rem; }
        .modal-subject .name { font-size:.85rem; color:var(--text-secondary); margin-top:.15rem; }
        .reason-label { font-weight:600; font-size:.84rem; margin-bottom:.45rem; display:block; color:var(--text-primary); }
        .reason-input {
            width:100%; padding:.7rem .9rem;
            border:1.5px solid var(--border-color);
            border-radius:var(--radius-md);
            font-size:.9rem; resize:vertical; min-height:90px;
            font-family:inherit; box-sizing:border-box;
            background: var(--background-card);
            color: var(--text-primary);
            transition: border-color .18s, box-shadow .18s;
        }
        .reason-input:focus { outline:none; border-color:var(--primary-purple); box-shadow: 0 0 0 3px rgba(61,107,159,0.1); }
        .reason-input::placeholder { color: var(--text-light); }
        .modal-footer {
            display:flex; gap:.75rem;
            padding: 1rem 1.5rem 1.4rem;
        }
        .modal-footer button {
            flex:1; padding:.7rem;
            border:none; border-radius:var(--radius-md);
            font-weight:700; cursor:pointer; font-size:.9rem;
            transition: opacity .15s, transform .15s;
        }
        .modal-footer button:hover { opacity:.88; transform:translateY(-1px); }
        .modal-footer button:active { transform:translateY(0); }
        .btn-cancel { background:var(--background-main); color:var(--text-primary); border: 1px solid var(--border-color) !important; }
        .btn-submit-add  { background:var(--btn-primary-gradient); color:white; box-shadow: 0 2px 8px var(--btn-primary-shadow); }
        .btn-submit-drop { background:var(--secondary-pink); color:var(--text-white); box-shadow: 0 2px 8px rgba(184,92,92,0.25); }

        .req-row { padding:.75rem 1rem; border-radius:var(--radius-md); background:var(--background-main); margin-bottom:.5rem; }
        .req-row-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; margin-bottom:.3rem; }
        .req-note { font-size:.8rem; color:var(--text-secondary); margin-top:.25rem; padding:.35rem .6rem; background:var(--background-main); border-radius:var(--radius-sm); }
        .btn-cancel-request {
            padding: .22rem .7rem;
            font-size: .75rem;
            border: 1.5px solid var(--secondary-pink);
            background: transparent;
            color: var(--secondary-pink);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            transition: all .2s;
            white-space: nowrap;
        }
        .btn-cancel-request:hover { background: var(--secondary-pink); color: var(--text-white); }
        .btn-cancel-request:disabled { opacity: .5; cursor: not-allowed; }
        /* Toast notification */
        #scc-toast {
            position: fixed; top: 1.5rem; right: 1.5rem; z-index: 9999;
            display: flex; align-items: flex-start; gap: .85rem;
            background: var(--background-card); border-radius: var(--radius-lg);
            box-shadow: 0 8px 32px rgba(0,0,0,0.15); padding: 1rem 1.25rem;
            max-width: 360px; min-width: 260px;
            transform: translateX(120%); transition: transform .35s cubic-bezier(.4,0,.2,1);
            border-left: 4px solid var(--primary-purple);
        }
        #scc-toast.show { transform: translateX(0); }
        #scc-toast.toast-success { border-left-color: #22c55e; }
        #scc-toast.toast-error   { border-left-color: #ef4444; }
        #scc-toast.toast-warning { border-left-color: #f59e0b; }
        .toast-icon { font-size: 1.4rem; flex-shrink: 0; margin-top: .05rem; }
        .toast-body { flex: 1; }
        .toast-title { font-weight: 700; font-size: .92rem; color: var(--text-primary); margin-bottom: .15rem; }
        .toast-msg   { font-size: .83rem; color: var(--text-secondary); line-height: 1.45; }
        .toast-close { background: none; border: none; font-size: 1.1rem; color: var(--text-secondary); cursor: pointer; padding: 0; flex-shrink: 0; }
        .toast-close:hover { color: var(--text-primary); }
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
                    <span>Student Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="schedule.php" class="nav-item"><span class="nav-icon">📅</span><span>My Schedule</span></a>
                    <a href="subjects.php" class="nav-item active"><span class="nav-icon">📚</span><span>Study Load</span></a>
                    <a href="grades.php" class="nav-item"><span class="nav-icon">🎓</span><span>Grades</span></a>
                    <a href="calendar.php" class="nav-item"><span class="nav-icon">🗓️</span><span>Calendar</span></a>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                    <a href="faculty.php" class="nav-item"><span class="nav-icon">👨‍🏫</span><span>Faculty Directory</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Support</div>
                    <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>Announcements</span></a>
                    <a href="feedback.php" class="nav-item"><span class="nav-icon">💬</span><span>Feedback</span></a>
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
                    <h1>My Study Load</h1>
                <p class="page-subtitle">Your enrolled subjects and add/drop requests</p>
            </div>
        </header>

        <!-- Section Banner -->
        <div id="sectionBanner"></div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-icon">📚</div>
                <div class="stat-label">Total Subjects</div>
                <div class="stat-value" id="totalSubjects">0</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">⚡</div>
                <div class="stat-label">Total Units</div>
                <div class="stat-value" id="totalUnits">0</div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-icon">📊</div>
                <div class="stat-label">Load Status</div>
                <div class="stat-value" id="loadStatus" style="font-size:1.1rem;">—</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tab-bar" style="margin-top:1.5rem;">
            <button class="tab-btn active" onclick="switchTab('enrolled')">📋 Enrolled Subjects</button>
            <button class="tab-btn" onclick="switchTab('add')">➕ Add Subject</button>
            <button class="tab-btn" id="requestsTab" onclick="switchTab('requests')">📝 My Requests <span id="pendingBadge" class="badge-count" style="display:none;"></span></button>
        </div>

        <!-- Tab: Enrolled Subjects -->
        <div class="tab-panel active" id="panel-enrolled">
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">Enrolled Subjects</h2>
                    <p style="font-size:.82rem;color:var(--text-secondary);margin-top:.25rem;">Click "Drop" to submit a drop request for any subject.</p>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Subject Name</th>
                                <th>Units</th>
                                <th>Teacher</th>
                                <th>Schedule</th>
                                <th>Room</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="subjectsTable">
                            <tr><td colspan="7" style="text-align:center;padding:2rem;">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Add Subject -->
        <div class="tab-panel" id="panel-add">
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">Available Subjects to Add</h2>
                    <p style="font-size:.82rem;color:var(--text-secondary);margin-top:.25rem;">Subjects in your section not yet in your study load. Requests are reviewed by the registrar.</p>
                </div>
                <div id="availableSubjects" style="padding:1rem;">Loading…</div>
            </div>
        </div>

        <!-- Tab: My Requests -->
        <div class="tab-panel" id="panel-requests">
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">My Add/Drop Requests</h2>
                </div>
                <div id="requestsList" style="padding:1rem;">Loading…</div>
            </div>
        </div>
    </main>
</div>

<!-- Reason Modal -->
<!-- Toast Notification -->
<div id="scc-toast">
    <div class="toast-icon" id="toast-icon">ℹ️</div>
    <div class="toast-body">
        <div class="toast-title" id="toast-title">Notice</div>
        <div class="toast-msg"   id="toast-msg"></div>
    </div>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="modal" id="reasonModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon" id="modalIcon">📋</div>
            <div>
                <div class="modal-title" id="modalTitle">Submit Request</div>
                <div class="modal-subtitle" id="modalSubtitle">Fill in your reason and submit for registrar review.</div>
            </div>
        </div>
        <div class="modal-body">
            <div class="modal-subject">
                <div class="modal-subject-label">Subject</div>
                <div class="code" id="modalSubjectCode"></div>
                <div class="name" id="modalSubjectName"></div>
            </div>
            <label class="reason-label">Reason <span style="color:var(--secondary-pink);">*</span></label>
            <textarea class="reason-input" id="reasonInput" placeholder="Please explain your reason for this request…"></textarea>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button id="submitBtn" onclick="submitRequest()">Submit</button>
        </div>
    </div>
</div>

<!-- Cancel Request Confirmation Modal -->
<div class="modal" id="cancelConfirmModal">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <div class="modal-icon modal-icon-drop">🗑️</div>
            <div>
                <div class="modal-title">Cancel Request</div>
                <div class="modal-subtitle">This action cannot be undone.</div>
            </div>
        </div>
        <div class="modal-body">
            <div class="modal-subject">
                <div class="modal-subject-label">Request to cancel</div>
                <div class="code" id="cancelModalCode"></div>
                <div class="name" id="cancelModalName"></div>
                <div id="cancelModalType" style="margin-top:.4rem;"></div>
            </div>
            <p style="font-size:.87rem;color:var(--text-secondary);line-height:1.55;">
                Are you sure you want to cancel this pending request? It will be removed and you can submit a new one later.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeCancelModal()">Keep Request</button>
            <button id="confirmCancelBtn" onclick="confirmCancel()" class="btn-submit-drop">Yes, Cancel It</button>
        </div>
    </div>
</div>

<script>
let pendingRequest = null;  // { subject_id, request_type, subject_code, subject_name }
let pendingSubjectIds = new Set(); // subject IDs with pending drop requests

function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
    if (tab === 'requests') loadRequests();
    if (tab === 'add') loadAvailable();
}

// ── Load Enrolled Subjects ──────────────────────────────
async function loadStudyLoad() {
    try {
        const res  = await fetch('../../api/student/get_study_load.php');
        const data = await res.json();
        if (!data.success) return;

        // Section banner
        const banner = document.getElementById('sectionBanner');
        if (data.section) {
            const s = data.section;
            banner.innerHTML = `
                <div class="section-banner">
                    <div class="sb-icon">📁</div>
                    <div>
                        <div class="sb-name">${s.section_name} <span style="opacity:.7;font-size:.9rem;">(${s.section_code})</span></div>
                        <div class="sb-meta">${s.course || ''} · ${s.year_level || ''} · ${s.semester || ''} · ${s.school_year || ''}</div>
                    </div>
                </div>`;
        } else {
            banner.innerHTML = `
                <div class="section-banner no-section">
                    <div class="sb-icon">📁</div>
                    <div>
                        <div class="sb-name">No Section Assigned</div>
                        <div class="sb-meta">Please contact the registrar to have a section assigned to you.</div>
                    </div>
                </div>`;
        }

        document.getElementById('totalSubjects').textContent = data.stats.total_subjects;
        document.getElementById('totalUnits').textContent    = data.stats.total_units;
        document.getElementById('loadStatus').textContent    = data.stats.status;

        // Load pending drop requests first to mark them
        await loadPendingDropIds();

        if (!data.subjects.length) {
            document.getElementById('subjectsTable').innerHTML =
                '<tr><td colspan="7" style="text-align:center;padding:2rem;">No subjects assigned yet</td></tr>';
            return;
        }

        document.getElementById('subjectsTable').innerHTML = data.subjects.map(s => {
            const hasPending = pendingSubjectIds.has(String(s.subject_id));
            const actionCell = hasPending
                ? `<span class="pending-badge">Drop Pending</span>`
                : `<button class="drop-btn" data-subject-id="${s.subject_id}" onclick="openModal(${s.subject_id},'drop','${esc(s.subject_code)}','${esc(s.subject_name)}')">Drop</button>`;
            return `
            <tr>
                <td><strong>${s.subject_code}</strong></td>
                <td>${s.subject_name}</td>
                <td>${s.units}</td>
                <td>${s.teacher || '<em style="color:var(--text-light)">TBA</em>'}</td>
                <td>${s.schedule || '<em style="color:var(--text-light)">TBA</em>'}</td>
                <td>${s.room || '<em style="color:var(--text-light)">TBA</em>'}</td>
                <td>${actionCell}</td>
            </tr>`;
        }).join('');

    } catch (err) { console.error(err); }
}

async function loadPendingDropIds() {
    try {
        const res  = await fetch('../../api/student/get_add_drop_requests.php');
        const data = await res.json();
        if (!data.success) return;
        pendingSubjectIds.clear();
        let pendingCount = 0;
        data.requests.forEach(r => {
            if (r.status === 'pending') {
                pendingCount++;
                // Store as both string and int to avoid type mismatch
                if (r.request_type === 'drop' && r.subject_id != null) {
                    pendingSubjectIds.add(String(r.subject_id));
                }
            }
        });
        const badge = document.getElementById('pendingBadge');
        if (pendingCount > 0) { badge.style.display = ''; badge.textContent = pendingCount; }
        else badge.style.display = 'none';
    } catch(e) { console.error('loadPendingDropIds error:', e); }
}

// ── Load Available Subjects ────────────────────────────
async function loadAvailable() {
    const container = document.getElementById('availableSubjects');
    container.innerHTML = 'Loading…';
    try {
        const res  = await fetch('../../api/student/get_available_subjects.php');
        const data = await res.json();
        if (!data.subjects || data.subjects.length === 0) {
            container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">No additional subjects available in your section, or all section subjects are already in your load.</p>';
            return;
        }
        container.innerHTML = data.subjects.map(s => `
            <div class="add-subject-card">
                <div>
                    <strong>${s.subject_code}</strong> — ${s.subject_name}
                    <div style="font-size:.8rem;color:var(--text-secondary);margin-top:.2rem;">
                        ⚡ ${s.units} units
                        ${s.teacher ? ' · 👤 ' + s.teacher : ''}
                        ${s.schedule ? ' · 📅 ' + s.schedule : ''}
                    </div>
                </div>
                <button class="add-btn" onclick="openModal(${s.id},'add','${esc(s.subject_code)}','${esc(s.subject_name)}')">➕ Add</button>
            </div>`).join('');
    } catch(e) {
        container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">Failed to load subjects.</p>';
    }
}

// ── Load My Requests ───────────────────────────────────
async function loadRequests() {
    const container = document.getElementById('requestsList');
    container.innerHTML = 'Loading…';
    try {
        const res  = await fetch('../../api/student/get_add_drop_requests.php');
        const data = await res.json();

        if (!data.requests || data.requests.length === 0) {
            container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">No requests yet. Use the tabs above to add or drop subjects.</p>';
            return;
        }

        container.innerHTML = data.requests.map(r => `
            <div class="req-row" id="req-row-${r.id}">
                <div class="req-row-header">
                    <div>
                        <strong>${r.subject_code}</strong> — ${r.subject_name}
                        <span class="req-badge req-${r.request_type}" style="margin-left:.4rem;">${r.request_type.toUpperCase()}</span>
                    </div>
                    <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
                        <span class="req-badge req-${r.status}">${r.status.charAt(0).toUpperCase()+r.status.slice(1)}</span>
                        <span style="font-size:.75rem;color:var(--text-secondary);">${r.created_at}</span>
                        ${r.status === 'pending' ? `<button class="btn-cancel-request" onclick="cancelRequest(${r.id}, '${r.request_type}', this)">✕ Cancel</button>` : ''}
                    </div>
                </div>
                <div style="font-size:.82rem;color:var(--text-secondary);">Reason: ${r.reason}</div>
                ${r.registrar_note ? `<div class="req-note">📝 Registrar note: ${r.registrar_note}</div>` : ''}
                ${r.reviewed_at ? `<div style="font-size:.75rem;color:var(--text-secondary);margin-top:.25rem;">Reviewed ${r.reviewed_at}${r.reviewed_by_name ? ' by ' + r.reviewed_by_name : ''}</div>` : ''}
            </div>`).join('');

    } catch(e) {
        container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">Failed to load requests.</p>';
    }
}

// ── Modal ──────────────────────────────────────────────
function openModal(subject_id, request_type, subject_code, subject_name) {
    pendingRequest = { subject_id, request_type, subject_code, subject_name };
    const isAdd = request_type === 'add';
    document.getElementById('modalIcon').textContent     = isAdd ? '➕' : '🗑️';
    document.getElementById('modalIcon').className       = 'modal-icon ' + (isAdd ? 'modal-icon-add' : 'modal-icon-drop');
    document.getElementById('modalTitle').textContent    = isAdd ? 'Request to Add Subject' : 'Request to Drop Subject';
    document.getElementById('modalSubtitle').textContent = isAdd ? 'Submit a request to add this subject to your load.' : 'Submit a request to drop this subject from your load.';
    document.getElementById('modalSubjectCode').textContent = subject_code;
    document.getElementById('modalSubjectName').textContent = subject_name;
    document.getElementById('reasonInput').value = '';
    const btn = document.getElementById('submitBtn');
    btn.textContent = isAdd ? 'Submit Add Request' : 'Submit Drop Request';
    btn.className   = isAdd ? 'btn-submit-add' : 'btn-submit-drop';
    document.getElementById('reasonModal').classList.add('active');
    document.getElementById('reasonInput').focus();
}

function closeModal() {
    document.getElementById('reasonModal').classList.remove('active');
    pendingRequest = null;
}

// ── Cancel a pending Add/Drop Request ──────────────────
let pendingCancelId   = null;
let pendingCancelType = null;
let pendingCancelBtn  = null;

function cancelRequest(requestId, requestType, btn) {
    pendingCancelId   = requestId;
    pendingCancelType = requestType;
    pendingCancelBtn  = btn;

    // Populate the confirmation modal
    const row = document.getElementById('req-row-' + requestId);
    const codeEl = row ? row.querySelector('strong') : null;
    const nameEl = row ? row.querySelector('strong + span, strong') : null;
    const rowText = row ? row.querySelector('.req-row-header strong') : null;

    // Parse subject info from the row
    const headerDiv = row ? row.querySelector('.req-row-header > div:first-child') : null;
    if (headerDiv) {
        const strong = headerDiv.querySelector('strong');
        const full   = headerDiv.textContent;
        const code   = strong ? strong.textContent.trim() : '';
        const name   = full.replace(code, '').replace(/^\s*—\s*/, '').split('\n')[0].trim().replace(/\s+[A-Z]+\s*$/, '').trim();
        document.getElementById('cancelModalCode').textContent = code;
        document.getElementById('cancelModalName').textContent = name;
    }

    const typeLabel = requestType === 'drop' ? 'DROP' : 'ADD';
    const typeColor = requestType === 'drop' ? 'req-drop' : 'req-add';
    document.getElementById('cancelModalType').innerHTML =
        `<span class="req-badge ${typeColor}">${typeLabel} Request</span>`;

    document.getElementById('cancelConfirmModal').classList.add('active');
}

function closeCancelModal() {
    document.getElementById('cancelConfirmModal').classList.remove('active');
    pendingCancelId   = null;
    pendingCancelType = null;
    pendingCancelBtn  = null;
}

async function confirmCancel() {
    if (!pendingCancelId) return;

    const btn = document.getElementById('confirmCancelBtn');
    btn.disabled    = true;
    btn.textContent = 'Cancelling…';

    try {
        const res  = await fetch('../../api/student/cancel_add_drop.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: pendingCancelId })
        });
        const data = await res.json();

        if (data.success) {
            closeCancelModal();
            showToast(data.message, 'success');
            const row = document.getElementById('req-row-' + pendingCancelId);
            if (row) {
                row.style.transition = 'opacity .3s, transform .3s';
                row.style.opacity    = '0';
                row.style.transform  = 'translateX(10px)';
                setTimeout(() => { row.remove(); }, 310);
            }
            if (pendingCancelType === 'drop') {
                pendingSubjectIds.clear();
                await loadStudyLoad();
                await loadPendingDropIds();
            }
            // Refresh request count badge
            await loadPendingDropIds();
        } else {
            showToast(data.message, 'error');
        }
    } catch(e) {
        showToast('Failed to cancel request. Please try again.', 'error');
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Yes, Cancel It';
    }
}

async function submitRequest() {
    if (!pendingRequest) return;
    const reason = document.getElementById('reasonInput').value.trim();
    if (!reason) { showToast('Please enter a reason before submitting.', 'warning'); return; }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = 'Submitting…';

    try {
        const res  = await fetch('../../api/student/submit_add_drop.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ subject_id: pendingRequest.subject_id, request_type: pendingRequest.request_type, reason })
        });
        const data = await res.json();
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            // Instantly swap the Drop button to pending badge without reload
            const subjectId = pendingRequest.subject_id;
            if (pendingRequest.request_type === 'drop') {
                const dropBtn = document.querySelector(`.drop-btn[data-subject-id="${subjectId}"]`);
                if (dropBtn) {
                    const badge = document.createElement('span');
                    badge.className = 'pending-badge';
                    badge.textContent = 'Drop Pending';
                    dropBtn.replaceWith(badge);
                }
                pendingSubjectIds.add(String(subjectId));
            }
            closeModal();
            loadAvailable();
            // Update the pending count badge on the tab
            await loadPendingDropIds();
        }
    } catch(e) {
        showToast('Failed to submit request. Please try again.', 'error');
    } finally {
        btn.disabled = false;
    }
}

function esc(str) { return (str || '').replace(/'/g, "\'").replace(/"/g, '&quot;'); }

let _toastTimer;
function showToast(msg, type = 'info') {
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const titles = { success: 'Success', error: 'Error', warning: 'Warning', info: 'Notice' };
    const el = document.getElementById('scc-toast');
    document.getElementById('toast-icon').textContent  = icons[type]  || icons.info;
    document.getElementById('toast-title').textContent = titles[type] || titles.info;
    document.getElementById('toast-msg').textContent   = msg;
    el.className = `show toast-${type}`;
    clearTimeout(_toastTimer);
    _toastTimer = setTimeout(hideToast, 4500);
}
function hideToast() {
    document.getElementById('scc-toast').classList.remove('show');
}

// Close modal on backdrop click
document.getElementById('reasonModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('cancelConfirmModal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});

loadStudyLoad();
</script>
    <script>
        (function() {
            var sidebar = document.querySelector('.sidebar');
            var activeItem = sidebar.querySelector('.nav-item.active');
            if (activeItem) {
                // Scroll only within the sidebar, not the whole page
                const itemTop = activeItem.offsetTop;
                const sidebarHeight = sidebar.clientHeight;
                const itemHeight = activeItem.clientHeight;
                sidebar.scrollTop = itemTop - (sidebarHeight / 2) + (itemHeight / 2);
            } else {
                var saved = sessionStorage.getItem('sidebarScroll');
                if (saved) sidebar.scrollTop = parseInt(saved);
            }
            // Save scroll position before navigating away
            document.querySelectorAll('.nav-item').forEach(function(link) {
                link.addEventListener('click', function() {
                    sessionStorage.setItem('sidebarScroll', sidebar.scrollTop);
                });
            });
        })();
    </script>
<?php include 'chatbot-widget.php'; ?>
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

<!-- ── Global Search Overlay ─────────────────────────────────────── -->
<style>
.global-search-btn {
    background: var(--background-card, #fff);
    border: 1.5px solid var(--border-color, #e2e8f0);
    border-radius: var(--radius-md, 8px);
    width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: var(--text-secondary, #64748b);
    transition: all .2s; flex-shrink: 0;
}
.global-search-btn:hover {
    background: var(--primary-purple, #3D6B9F);
    color: #fff; border-color: var(--primary-purple, #3D6B9F);
    transform: scale(1.05);
}
.gs-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(10,20,40,0.55); backdrop-filter: blur(6px);
    z-index: 99999; align-items: flex-start; justify-content: center;
    padding-top: clamp(3rem, 10vh, 6rem);
}
.gs-overlay.open { display: flex; animation: gsFadeIn .18s ease; }
@keyframes gsFadeIn { from { opacity:0; } to { opacity:1; } }
.gs-box {
    background: var(--background-card, #fff);
    border-radius: 16px;
    box-shadow: 0 24px 80px rgba(0,0,0,0.28);
    width: min(640px, calc(100vw - 2rem));
    max-height: 70vh; display: flex; flex-direction: column;
    overflow: hidden; animation: gsSlideIn .2s ease;
}
@keyframes gsSlideIn { from { opacity:0; transform:translateY(-16px) scale(.97); } to { opacity:1; transform:translateY(0) scale(1); } }
.gs-input-wrap {
    display: flex; align-items: center; gap: .75rem;
    padding: 1rem 1.25rem; border-bottom: 1.5px solid var(--border-color, #e2e8f0);
    flex-shrink: 0;
}
.gs-input-wrap svg { color: var(--text-secondary, #64748b); flex-shrink:0; }
.gs-input {
    flex: 1; border: none; outline: none; background: transparent;
    font-size: 1.05rem; color: var(--text-primary, #1C2C42);
    font-family: inherit;
}
.gs-input::placeholder { color: var(--text-secondary, #94a3b8); }
.gs-close {
    background: var(--background-page, #f8fafc); border: 1.5px solid var(--border-color, #e2e8f0);
    border-radius: 6px; padding: .2rem .5rem; font-size: .72rem;
    color: var(--text-secondary, #64748b); cursor: pointer; flex-shrink:0;
    font-family: inherit; transition: all .15s;
}
.gs-close:hover { background: var(--border-color, #e2e8f0); }
.gs-results {
    overflow-y: auto; flex: 1; padding: .5rem 0;
    scrollbar-width: thin;
}
.gs-section-label {
    font-size: .65rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 1px; color: var(--text-secondary, #94a3b8);
    padding: .6rem 1.25rem .3rem; margin-top: .25rem;
}
.gs-item {
    display: flex; align-items: center; gap: .85rem;
    padding: .7rem 1.25rem; cursor: pointer; text-decoration: none;
    transition: background .13s; border-radius: 0;
}
.gs-item:hover, .gs-item.active {
    background: var(--background-hover, #f1f5f9);
}
.gs-icon {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; background: var(--background-page, #f8fafc);
}
.gs-item-text { flex: 1; min-width: 0; }
.gs-item-title { font-size: .88rem; font-weight: 600; color: var(--text-primary, #1C2C42); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gs-item-sub { font-size: .75rem; color: var(--text-secondary, #64748b); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gs-arrow { color: var(--text-secondary, #cbd5e1); flex-shrink: 0; }
.gs-empty { text-align: center; padding: 2.5rem 1rem; color: var(--text-secondary, #94a3b8); font-size: .9rem; }
.gs-footer {
    border-top: 1.5px solid var(--border-color, #e2e8f0);
    padding: .6rem 1.25rem; display: flex; gap: 1rem; flex-shrink: 0;
    align-items: center;
}
.gs-hint { font-size: .68rem; color: var(--text-secondary, #94a3b8); display: flex; align-items: center; gap: .3rem; }
.gs-hint kbd {
    background: var(--background-page, #f1f5f9); border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 4px; padding: .1rem .35rem; font-size: .65rem;
    font-family: inherit; color: var(--text-secondary, #64748b);
}
mark.gs-hl { background: rgba(61,107,159,.15); color: var(--primary-purple, #3D6B9F); border-radius: 3px; padding: 0 2px; font-style: normal; }
</style>

<!-- Search Overlay HTML -->
<div class="gs-overlay" id="gsOverlay" role="dialog" aria-modal="true" aria-label="Global Search">
    <div class="gs-box" id="gsBox">
        <div class="gs-input-wrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input class="gs-input" id="gsInput" type="text" placeholder="Search pages, subjects, grades, announcements…" autocomplete="off" spellcheck="false">
            <button class="gs-close" id="gsCloseBtn">ESC</button>
        </div>
        <div class="gs-results" id="gsResults"></div>
        <div class="gs-footer">
            <span class="gs-hint"><kbd>↑</kbd><kbd>↓</kbd> navigate</span>
            <span class="gs-hint"><kbd>↵</kbd> open</span>
            <span class="gs-hint"><kbd>ESC</kbd> close</span>
        </div>
    </div>
</div>

<script>
(function() {
    // ── Static page index ──────────────────────────────────────────────
    const PAGES = [
        { title: 'Dashboard',        url: 'dashboard.php',      icon: '🏠', sub: 'Home overview' },
        { title: 'My Schedule',      url: 'schedule.php',       icon: '📅', sub: 'Class timetable' },
        { title: 'Study Load',       url: 'subjects.php',       icon: '📚', sub: 'Enrolled subjects' },
        { title: 'Grades',           url: 'grades.php',         icon: '📊', sub: 'Academic performance' },
        { title: 'Calendar',         url: 'calendar.php',       icon: '🗓️', sub: 'Academic calendar & events' },
        { title: 'Floor Plan',       url: 'floorplan.php',      icon: '🗺️', sub: 'Campus map & rooms' },
        { title: 'Faculty Directory',url: 'faculty.php',        icon: '👩‍🏫', sub: 'Teachers & staff' },
        { title: 'Announcements',    url: 'announcements.php',  icon: '📢', sub: 'School announcements' },
        { title: 'Feedback',         url: 'feedback.php',       icon: '💬', sub: 'Submit feedback' },
        { title: 'Profile',          url: 'profile.php',        icon: '👤', sub: 'My account & settings' },
        { title: 'Chatbot',          url: 'chatbot.php',        icon: '🤖', sub: 'AI assistant' },
    ];

    // ── Dynamic data cache ─────────────────────────────────────────────
    let dynData = [];
    let dynLoaded = false;

    async function loadDynamic() {
        if (dynLoaded) return;
        dynLoaded = true;
        try {
            const [gradesRes, subjectsRes, announcementsRes] = await Promise.allSettled([
                fetch('../../api/student/get_grades.php').then(r => r.json()),
                fetch('../../api/student/get_study_load.php').then(r => r.json()),
                fetch('../../api/student/get_announcements.php').then(r => r.json()),
            ]);

            if (gradesRes.status === 'fulfilled' && gradesRes.value?.grades) {
                gradesRes.value.grades.forEach(g => {
                    dynData.push({
                        icon: '📊', section: 'Grades',
                        title: g.subject_name || g.subject_code,
                        sub: `Grade: ${g.final_grade ?? g.midterm_grade ?? 'No grade yet'} · ${g.subject_code || ''}`,
                        url: 'grades.php'
                    });
                });
            }
            if (subjectsRes.status === 'fulfilled' && subjectsRes.value?.subjects) {
                subjectsRes.value.subjects.forEach(s => {
                    dynData.push({
                        icon: '📚', section: 'Subjects',
                        title: s.subject_name || s.name,
                        sub: `${s.subject_code || ''} · ${s.units || ''} units · ${s.teacher_name || ''}`,
                        url: 'subjects.php'
                    });
                });
            }
            if (announcementsRes.status === 'fulfilled' && announcementsRes.value?.announcements) {
                announcementsRes.value.announcements.forEach(a => {
                    dynData.push({
                        icon: '📢', section: 'Announcements',
                        title: a.title,
                        sub: a.date || '',
                        url: 'announcements.php'
                    });
                });
            }
        } catch(e) {}
    }

    // ── Search logic ───────────────────────────────────────────────────
    function highlight(text, query) {
        if (!query) return escHtml(text);
        const escaped = escHtml(text);
        const re = new RegExp('(' + escRegex(query) + ')', 'gi');
        return escaped.replace(re, '<mark class="gs-hl">$1</mark>');
    }
    function escHtml(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function escRegex(s) {
        return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function search(query) {
        const q = query.trim().toLowerCase();
        const results = [];

        // Pages
        const pageMatches = PAGES.filter(p =>
            p.title.toLowerCase().includes(q) ||
            p.sub.toLowerCase().includes(q)
        );
        if (pageMatches.length) {
            results.push({ type: 'section', label: 'Pages' });
            pageMatches.forEach(p => results.push({ type: 'item', ...p, section: 'Pages' }));
        }

        // Dynamic data
        if (q.length >= 2) {
            const groups = {};
            dynData.forEach(d => {
                if (d.title.toLowerCase().includes(q) || d.sub.toLowerCase().includes(q)) {
                    if (!groups[d.section]) groups[d.section] = [];
                    groups[d.section].push(d);
                }
            });
            Object.entries(groups).forEach(([sec, items]) => {
                results.push({ type: 'section', label: sec });
                items.slice(0, 5).forEach(i => results.push({ type: 'item', ...i }));
            });
        }

        return results;
    }

    function renderResults(query) {
        const results = query ? search(query) : getDefaults();
        const container = document.getElementById('gsResults');

        if (!results.length) {
            container.innerHTML = '<div class="gs-empty">No results for <strong>"' + escHtml(query) + '"</strong></div>';
            activeIdx = -1;
            return;
        }

        container.innerHTML = results.map((r, i) => {
            if (r.type === 'section') {
                return `<div class="gs-section-label">${escHtml(r.label)}</div>`;
            }
            const q = query.trim();
            return `<a class="gs-item" href="${escHtml(r.url)}" data-idx="${i}">
                <div class="gs-icon">${r.icon}</div>
                <div class="gs-item-text">
                    <div class="gs-item-title">${highlight(r.title, q)}</div>
                    ${r.sub ? `<div class="gs-item-sub">${highlight(r.sub, q)}</div>` : ''}
                </div>
                <svg class="gs-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </a>`;
        }).join('');

        activeIdx = -1;
    }

    function getDefaults() {
        const defaults = [];
        defaults.push({ type: 'section', label: 'Quick Access' });
        PAGES.slice(0, 6).forEach(p => defaults.push({ type: 'item', ...p }));
        return defaults;
    }

    // ── Keyboard navigation ────────────────────────────────────────────
    let activeIdx = -1;

    function getItems() {
        return Array.from(document.querySelectorAll('#gsResults .gs-item'));
    }

    function setActive(idx) {
        const items = getItems();
        if (!items.length) return;
        items.forEach(i => i.classList.remove('active'));
        activeIdx = Math.max(0, Math.min(idx, items.length - 1));
        items[activeIdx].classList.add('active');
        items[activeIdx].scrollIntoView({ block: 'nearest' });
    }

    // ── Open / Close ───────────────────────────────────────────────────
    const overlay = document.getElementById('gsOverlay');
    const input   = document.getElementById('gsInput');

    function openSearch() {
        overlay.classList.add('open');
        input.value = '';
        renderResults('');
        setTimeout(() => input.focus(), 50);
        loadDynamic();
    }

    function closeSearch() {
        overlay.classList.remove('open');
        activeIdx = -1;
    }

    // Trigger button
    const btn = document.getElementById('globalSearchBtn');
    if (btn) btn.addEventListener('click', openSearch);

    // Close button
    document.getElementById('gsCloseBtn').addEventListener('click', closeSearch);

    // Click outside to close
    overlay.addEventListener('click', e => { if (e.target === overlay) closeSearch(); });

    // Keyboard shortcut: Ctrl+K or /
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); openSearch(); }
        if (e.key === 'Escape' && overlay.classList.contains('open')) closeSearch();
        if (overlay.classList.contains('open')) {
            if (e.key === 'ArrowDown') { e.preventDefault(); setActive(activeIdx + 1); }
            if (e.key === 'ArrowUp')   { e.preventDefault(); setActive(activeIdx - 1); }
            if (e.key === 'Enter') {
                const items = getItems();
                if (activeIdx >= 0 && items[activeIdx]) {
                    items[activeIdx].click();
                }
            }
        }
    });

    // Input handler
    input.addEventListener('input', e => {
        renderResults(e.target.value);
    });

})();
</script>


    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
      <a href="dashboard.php" class="mobile-nav-item" data-page="dashboard">
        <span class="mobile-nav-icon">📊</span><span>Home</span>
      </a>
      <a href="schedule.php" class="mobile-nav-item" data-page="schedule">
        <span class="mobile-nav-icon">📅</span><span>Schedule</span>
      </a>
      <a href="grades.php" class="mobile-nav-item" data-page="grades">
        <span class="mobile-nav-icon">🎓</span><span>Grades</span>
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