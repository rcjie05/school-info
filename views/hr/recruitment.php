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
requireRole('hr');
$conn  = getDBConnection();
$depts = $conn->query("SELECT id, department_name AS name FROM departments ORDER BY department_name ASC");
$deptsArr = [];
while ($d = $depts->fetch_assoc()) $deptsArr[] = $d;
$conn->close();
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
    <title>Recruitment & Onboarding - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        /* ── Tabs ────────────────────────────────────── */
        .tabs { display:flex; gap:0; border-bottom:2px solid #e5e7eb; margin-bottom:1.5rem; }
        .tab-btn { padding:0.75rem 1.5rem; border:none; background:none; font-size:0.9rem; font-weight:600; color:var(--text-secondary); cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; transition:all 0.15s; }
        .tab-btn.active { color:var(--primary-purple); border-bottom-color:var(--primary-purple); }
        .tab-btn:hover:not(.active) { color:var(--text-primary); }
        .tab-pane { display:none; }
        .tab-pane.active { display:block; }

        /* ── Stats ───────────────────────────────────── */
        .rec-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:1rem; margin-bottom:1.5rem; }
        .rec-stat  { background:white; border-radius:var(--radius-md); padding:1rem 1.25rem; box-shadow:var(--shadow-sm); border-top:3px solid #e5e7eb; text-align:center; }
        .rec-stat.open     { border-top-color:#3D6B9F; }
        .rec-stat.applied  { border-top-color:#f59e0b; }
        .rec-stat.interview{ border-top-color:#8b5cf6; }
        .rec-stat.hired    { border-top-color:#10b981; }
        .rec-stat-val   { font-size:1.6rem; font-weight:800; }
        .rec-stat-label { font-size:0.72rem; color:var(--text-secondary); font-weight:600; text-transform:uppercase; letter-spacing:0.4px; }

        /* ── Job Cards ───────────────────────────────── */
        .job-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1.25rem; }
        .job-card { background:white; border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); overflow:hidden; border-top:4px solid var(--primary-purple); transition:box-shadow 0.15s; }
        .job-card:hover { box-shadow:var(--shadow-md); }
        .job-card.closed    { border-top-color:#9ca3af; opacity:0.8; }
        .job-card.cancelled { border-top-color:#ef4444; opacity:0.7; }
        .job-card-body { padding:1.25rem; }
        .job-title   { font-size:1rem; font-weight:800; color:var(--text-primary); margin-bottom:0.3rem; }
        .job-meta    { font-size:0.78rem; color:var(--text-secondary); display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.85rem; }
        .job-meta-tag { background:#f1f5f9; padding:0.2rem 0.6rem; border-radius:20px; font-weight:600; }
        .job-footer  { padding:0.85rem 1.25rem; background:#f8fafc; border-top:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center; }
        .job-applicant-count { font-size:0.8rem; font-weight:700; color:var(--primary-purple); }
        .badge { display:inline-block; padding:0.2rem 0.65rem; border-radius:999px; font-size:0.72rem; font-weight:700; text-transform:uppercase; }
        .badge-open      { background:#dbeafe; color:#1e40af; }
        .badge-closed    { background:#f3f4f6; color:#6b7280; }
        .badge-cancelled { background:#fee2e2; color:#991b1b; }

        /* ── Pipeline / Kanban ───────────────────────── */
        .pipeline { display:grid; grid-template-columns:repeat(6,1fr); gap:1rem; overflow-x:auto; padding-bottom:0.5rem; }
        @media(max-width:1200px){ .pipeline { grid-template-columns:repeat(3,1fr); } }
        .pipeline-col { background:#f8fafc; border-radius:var(--radius-md); overflow:hidden; min-width:160px; }
        .pipeline-col-header { padding:0.65rem 0.85rem; font-size:0.72rem; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; color:white; }
        .stage-applied    .pipeline-col-header { background:#64748b; }
        .stage-screening  .pipeline-col-header { background:#f59e0b; }
        .stage-interview  .pipeline-col-header { background:#8b5cf6; }
        .stage-job_offer  .pipeline-col-header { background:#3b82f6; }
        .stage-hired      .pipeline-col-header { background:#10b981; }
        .stage-rejected   .pipeline-col-header { background:#ef4444; }
        .pipeline-col-body { padding:0.5rem; min-height:120px; }
        .pipeline-card { background:white; border-radius:var(--radius-sm); padding:0.65rem 0.75rem; margin-bottom:0.5rem; box-shadow:0 1px 4px rgba(0,0,0,0.06); cursor:pointer; transition:box-shadow 0.15s; border-left:3px solid transparent; font-size:0.82rem; }
        .pipeline-card:hover { box-shadow:var(--shadow-sm); }
        .stage-applied   .pipeline-card { border-left-color:#64748b; }
        .stage-screening .pipeline-card { border-left-color:#f59e0b; }
        .stage-interview .pipeline-card { border-left-color:#8b5cf6; }
        .stage-job_offer .pipeline-card { border-left-color:#3b82f6; }
        .stage-hired     .pipeline-card { border-left-color:#10b981; }
        .stage-rejected  .pipeline-card { border-left-color:#ef4444; }
        .pipeline-card-name { font-weight:700; color:var(--text-primary); }
        .pipeline-card-job  { font-size:0.72rem; color:var(--text-secondary); margin-top:0.15rem; }
        .pipeline-col-count { font-size:0.7rem; opacity:0.85; float:right; }

        /* ── Filter bar ──────────────────────────────── */
        .filter-bar { display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1.25rem; }
        .filter-bar select, .filter-bar input { padding:0.55rem 1rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-family:inherit; background:white; font-size:0.88rem; }
        .filter-bar input { flex:1; min-width:180px; }

        /* ── Modal ───────────────────────────────────── */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; }
        .modal.active { display:flex; }
        .modal-content { background:white; padding:2rem; border-radius:var(--radius-lg); max-width:680px; width:90%; max-height:92vh; overflow-y:auto; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .form-grid .full { grid-column:1/-1; }
        .form-group { margin-bottom:0; }
        .form-group label { display:block; font-weight:600; font-size:0.8rem; margin-bottom:0.35rem; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.4px; }
        .form-group input, .form-group select, .form-group textarea { width:100%; padding:0.65rem 0.9rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-size:0.9rem; font-family:inherit; box-sizing:border-box; }
        .form-group textarea { resize:vertical; min-height:80px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline:none; border-color:var(--primary-purple); }
        .section-divider { font-size:0.78rem; font-weight:700; color:var(--primary-purple); text-transform:uppercase; letter-spacing:0.6px; margin:1.25rem 0 0.75rem; padding-bottom:0.4rem; border-bottom:2px solid #eef2f7; grid-column:1/-1; }

        /* ── Stage progress bar ──────────────────────── */
        .stage-steps { display:flex; align-items:center; gap:0; margin-bottom:1.5rem; overflow-x:auto; }
        .stage-step { display:flex; align-items:center; gap:0; flex:1; min-width:80px; }
        .stage-step-dot { width:28px; height:28px; border-radius:50%; background:#e5e7eb; color:white; display:flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:800; flex-shrink:0; z-index:1; }
        .stage-step-dot.done    { background:#10b981; }
        .stage-step-dot.current { background:var(--primary-purple); }
        .stage-step-dot.rejected{ background:#ef4444; }
        .stage-step-line { flex:1; height:2px; background:#e5e7eb; }
        .stage-step-line.done { background:#10b981; }
        .stage-step-label { font-size:0.6rem; font-weight:700; color:var(--text-secondary); text-align:center; margin-top:0.25rem; }
        .stage-step-wrap { display:flex; flex-direction:column; align-items:center; flex:1; }

        /* ── Onboard box ─────────────────────────────── */
        .onboard-box { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1.5px solid #86efac; border-radius:var(--radius-md); padding:1.25rem; margin-top:1rem; }
        .onboard-box h4 { color:#166534; margin:0 0 0.75rem; font-size:0.95rem; }

        /* ── Toast ───────────────────────────────────── */
        .toast { position:fixed; bottom:2rem; right:2rem; padding:1rem 1.5rem; border-radius:var(--radius-md); color:white; font-weight:600; z-index:9999; display:none; }
        .toast.success { background:#10b981; }
        .toast.error   { background:#ef4444; }

        /* ── Detail info rows ────────────────────────── */
        .detail-box { background:#f8fafc; border-radius:var(--radius-md); padding:1rem 1.25rem; margin-bottom:1rem; }
        .detail-row { display:flex; gap:0.5rem; padding:0.4rem 0; border-bottom:1px solid #f0f0f0; font-size:0.85rem; }
        .detail-row:last-child { border-bottom:none; }
        .detail-label { color:var(--text-secondary); font-weight:600; min-width:130px; font-size:0.78rem; text-transform:uppercase; }
        .detail-value { color:var(--text-primary); }
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
                    <span>HR Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">HR Management</div>
                    <a href="employees.php" class="nav-item"><span class="nav-icon">👤</span><span>Employee Profiles</span></a>
                    <a href="leaves.php" class="nav-item"><span class="nav-icon">📅</span><span>Leave Requests</span></a>
                    <a href="attendance.php" class="nav-item"><span class="nav-icon">🕐</span><span>Attendance</span></a>
                    <a href="id_cards.php" class="nav-item"><span class="nav-icon">🪪</span><span>ID Cards</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Resources</div>
                    <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>Announcements</span></a>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
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
                    <h1>Recruitment & Onboarding</h1>
                <p class="page-subtitle">Manage job postings, applicants, and new hire onboarding</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openJobModal()">+ Post a Job</button>
            </div>
        </header>

        <!-- Stats -->
        <div class="rec-stats">
            <div class="rec-stat open">    <div class="rec-stat-val" id="sOpen">—</div><div class="rec-stat-label">Open Positions</div></div>
            <div class="rec-stat applied"> <div class="rec-stat-val" id="sApplied">—</div><div class="rec-stat-label">Applicants</div></div>
            <div class="rec-stat interview"><div class="rec-stat-val" id="sInterview">—</div><div class="rec-stat-label">For Interview</div></div>
            <div class="rec-stat hired">   <div class="rec-stat-val" id="sHired">—</div><div class="rec-stat-label">Hired This Year</div></div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('jobs',this)">💼 Job Postings</button>
            <button class="tab-btn" onclick="switchTab('pipeline',this)">🔄 Applicant Pipeline</button>
        </div>

        <!-- Tab: Job Postings -->
        <div id="tab-jobs" class="tab-pane active">
            <div class="filter-bar">
                <input type="text" id="jobSearch" placeholder="🔍 Search jobs..." oninput="filterJobs()">
                <select id="jobStatusFilter" onchange="filterJobs()">
                    <option value="">All Status</option>
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select id="jobDeptFilter" onchange="filterJobs()">
                    <option value="">All Departments</option>
                    <?php foreach ($deptsArr as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="jobGrid" class="job-grid">
                <p style="color:var(--text-secondary);">Loading...</p>
            </div>
        </div>

        <!-- Tab: Applicant Pipeline -->
        <div id="tab-pipeline" class="tab-pane">
            <div class="filter-bar">
                <input type="text" id="pipeSearch" placeholder="🔍 Search applicants..." oninput="filterPipeline()">
                <select id="pipeJobFilter" onchange="filterPipeline()">
                    <option value="">All Jobs</option>
                </select>
            </div>
            <div class="pipeline" id="pipelineBoard"></div>
        </div>
    </main>
</div>

<!-- ══ Job Modal ══════════════════════════════════════════════ -->
<div id="jobModal" class="modal">
    <div class="modal-content">
        <h2 id="jobModalTitle" style="margin:0 0 1.5rem;">Post a Job</h2>
        <input type="hidden" id="jobId">
        <div class="form-grid">
            <div class="form-group full">
                <label>Job Title</label>
                <input type="text" id="jTitle" placeholder="e.g. Math Teacher, Registrar Staff">
            </div>
            <div class="form-group">
                <label>Department</label>
                <select id="jDept">
                    <option value="">-- None --</option>
                    <?php foreach ($deptsArr as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Employment Type</label>
                <select id="jEmpType">
                    <option value="full_time">Full Time</option>
                    <option value="part_time">Part Time</option>
                    <option value="contractual">Contractual</option>
                    <option value="probationary">Probationary</option>
                </select>
            </div>
            <div class="form-group">
                <label>Number of Slots</label>
                <input type="number" id="jSlots" value="1" min="1">
            </div>
            <div class="form-group">
                <label>Application Deadline</label>
                <input type="date" id="jDeadline">
            </div>
            <div class="form-group full">
                <label>Job Description</label>
                <textarea id="jDesc" placeholder="Describe the role and responsibilities..."></textarea>
            </div>
            <div class="form-group full">
                <label>Requirements / Qualifications</label>
                <textarea id="jReqs" placeholder="List required qualifications, experience, etc..."></textarea>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select id="jStatus">
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:1rem;margin-top:1.75rem;">
            <button class="btn btn-primary" onclick="saveJob()" style="flex:1;">💾 Save Job Posting</button>
            <button class="btn" onclick="closeModal('jobModal')" style="flex:1;">Cancel</button>
        </div>
    </div>
</div>

<!-- ══ Applicant Modal ════════════════════════════════════════ -->
<div id="appModal" class="modal">
    <div class="modal-content">
        <h2 id="appModalTitle" style="margin:0 0 0.75rem;">Applicant</h2>
        <div id="stageSteps" style="margin-bottom:1.25rem;"></div>
        <input type="hidden" id="appId">
        <input type="hidden" id="appJobId">
        <div class="form-grid">
            <div class="section-divider">Applicant Information</div>
            <div class="form-group full">
                <label>Full Name</label>
                <input type="text" id="aName" placeholder="Full name">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="aEmail" placeholder="email@example.com">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" id="aPhone" placeholder="09XX-XXX-XXXX">
            </div>
            <div class="form-group full">
                <label>Address</label>
                <input type="text" id="aAddress" placeholder="Home address">
            </div>
            <div class="form-group full">
                <label>Resume / Qualifications Notes</label>
                <textarea id="aResume" placeholder="Notes about the applicant's qualifications, experience, etc..."></textarea>
            </div>

            <div class="section-divider">Application Stage</div>
            <div class="form-group">
                <label>Current Stage</label>
                <select id="aStage" onchange="handleStageChange()">
                    <option value="applied">Applied</option>
                    <option value="screening">Screening</option>
                    <option value="interview">Interview</option>
                    <option value="job_offer">Job Offer</option>
                    <option value="hired">Hired</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="form-group" id="interviewDateGroup" style="display:none;">
                <label>Interview Date</label>
                <input type="date" id="aInterviewDate">
            </div>
            <div class="form-group full" id="interviewNotesGroup" style="display:none;">
                <label>Interview Notes</label>
                <textarea id="aInterviewNotes" placeholder="Notes from the interview..."></textarea>
            </div>
            <div class="form-group" id="offerDateGroup" style="display:none;">
                <label>Job Offer Date</label>
                <input type="date" id="aOfferDate">
            </div>
            <div class="form-group full" id="rejectionGroup" style="display:none;">
                <label>Rejection Reason</label>
                <textarea id="aRejection" placeholder="Reason for rejection (optional)..."></textarea>
            </div>
        </div>

        <!-- Onboarding Section (shown when hired) -->
        <div id="onboardSection" style="display:none;">
            <div class="onboard-box">
                <h4>✅ Ready to Onboard</h4>
                <p style="font-size:0.85rem;color:#166534;margin:0 0 1rem;">This applicant has been hired. You can now create their system account to give them access.</p>
                <div class="form-grid">
                    <div class="section-divider" style="margin-top:0;">New Employee Account</div>
                    <div class="form-group">
                        <label>System Username / Email</label>
                        <input type="email" id="obEmail" placeholder="Login email">
                    </div>
                    <div class="form-group">
                        <label>Temporary Password</label>
                        <input type="text" id="obPassword" placeholder="Set a temporary password">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select id="obRole">
                            <option value="teacher">Teacher</option>
                            <option value="registrar">Registrar</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <select id="obDept">
                            <option value="">-- None --</option>
                            <?php foreach ($deptsArr as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="onboardApplicant()" style="margin-top:1rem;width:100%;">🚀 Create Account & Onboard</button>
            </div>
        </div>

        <div style="display:flex;gap:1rem;margin-top:1.75rem;">
            <button class="btn btn-primary" onclick="saveApplicant()" style="flex:1;">💾 Save</button>
            <button class="btn" onclick="closeModal('appModal')" style="flex:1;">Cancel</button>
        </div>
    </div>
</div>

<!-- ══ Add Applicant to Job Modal ════════════════════════════ -->
<div id="jobDetailModal" class="modal">
    <div class="modal-content" style="max-width:560px;">
        <h2 id="jdTitle" style="margin:0 0 0.5rem;"></h2>
        <p id="jdMeta" style="color:var(--text-secondary);font-size:0.85rem;margin:0 0 1.5rem;"></p>
        <div id="jdApplicants" style="margin-bottom:1.5rem;"></div>
        <div style="display:flex;gap:0.75rem;">
            <button class="btn btn-primary" onclick="openAddApplicant()" style="flex:1;">+ Add Applicant</button>
            <button class="btn" onclick="openEditJob()" style="flex:1;">✏️ Edit Job</button>
            <button class="btn" onclick="closeModal('jobDetailModal')" style="flex:1;">Close</button>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
let allJobs       = [];
let allApplicants = [];
let currentJobId  = null;
const STAGES = ['applied','screening','interview','job_offer','hired','rejected'];
const STAGE_LABELS = { applied:'Applied', screening:'Screening', interview:'Interview', job_offer:'Job Offer', hired:'Hired', rejected:'Rejected' };

// ── Init ──────────────────────────────────────────────────────
(async function init() {
    await Promise.all([loadJobs(), loadApplicants()]);
    updateStats();
})();

// ── Load ──────────────────────────────────────────────────────
async function loadJobs() {
    const res  = await fetch('../../api/hr/get_jobs.php');
    const data = await res.json();
    if (!data.success) return;
    allJobs = data.jobs;
    filterJobs();
    populateJobFilter();
}

async function loadApplicants() {
    const res  = await fetch('../../api/hr/get_applicants.php');
    const data = await res.json();
    if (!data.success) return;
    allApplicants = data.applicants;
    filterPipeline();
}

// ── Stats ─────────────────────────────────────────────────────
function updateStats() {
    document.getElementById('sOpen').textContent     = allJobs.filter(j => j.status === 'open').length;
    document.getElementById('sApplied').textContent  = allApplicants.length;
    document.getElementById('sInterview').textContent= allApplicants.filter(a => a.stage === 'interview').length;
    document.getElementById('sHired').textContent    = allApplicants.filter(a => a.stage === 'hired').length;
}

// ── Tab switch ────────────────────────────────────────────────
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-'+tab).classList.add('active');
}

// ── Jobs ──────────────────────────────────────────────────────
function filterJobs() {
    const q      = document.getElementById('jobSearch').value.toLowerCase();
    const status = document.getElementById('jobStatusFilter').value;
    const dept   = document.getElementById('jobDeptFilter').value;
    const list   = allJobs.filter(j =>
        (!q      || j.title.toLowerCase().includes(q))
        && (!status || j.status === status)
        && (!dept   || String(j.department_id) === dept)
    );
    renderJobs(list);
}

function renderJobs(list) {
    const grid = document.getElementById('jobGrid');
    if (!list.length) {
        grid.innerHTML = '<p style="color:var(--text-secondary);">No job postings found. Click "+ Post a Job" to create one.</p>';
        return;
    }
    grid.innerHTML = list.map(j => {
        const count    = allApplicants.filter(a => a.job_id == j.id).length;
        const deadline = j.deadline ? `Deadline: ${j.deadline}` : 'No deadline';
        const empType  = (j.employment_type||'').replace('_',' ');
        return `
        <div class="job-card ${j.status}" onclick="openJobDetail(${j.id})">
            <div class="job-card-body">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.5rem;">
                    <div class="job-title">${esc(j.title)}</div>
                    <span class="badge badge-${j.status}">${j.status}</span>
                </div>
                <div class="job-meta">
                    <span class="job-meta-tag">📁 ${esc(j.department_name||'No Dept')}</span>
                    <span class="job-meta-tag">⏱ ${esc(empType)}</span>
                    <span class="job-meta-tag">🪑 ${j.slots} slot${j.slots>1?'s':''}</span>
                </div>
                <div style="font-size:0.78rem;color:var(--text-secondary);">📅 ${deadline}</div>
                ${j.description ? `<div style="font-size:0.82rem;color:var(--text-secondary);margin-top:0.5rem;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">${esc(j.description)}</div>` : ''}
            </div>
            <div class="job-footer">
                <span class="job-applicant-count">👥 ${count} applicant${count!==1?'s':''}</span>
                <span style="font-size:0.78rem;color:var(--text-secondary);">Click to view</span>
            </div>
        </div>`;
    }).join('');
}

// ── Pipeline ──────────────────────────────────────────────────
function populateJobFilter() {
    const sel = document.getElementById('pipeJobFilter');
    const cur = sel.value;
    sel.innerHTML = '<option value="">All Jobs</option>' + allJobs.map(j =>
        `<option value="${j.id}" ${cur==j.id?'selected':''}>${esc(j.title)}</option>`
    ).join('');
}

function filterPipeline() {
    const q   = document.getElementById('pipeSearch').value.toLowerCase();
    const jid = document.getElementById('pipeJobFilter').value;
    const list = allApplicants.filter(a =>
        (!q   || a.full_name.toLowerCase().includes(q))
        && (!jid  || String(a.job_id) === jid)
    );
    renderPipeline(list);
}

function renderPipeline(list) {
    const board = document.getElementById('pipelineBoard');
    const stages = ['applied','screening','interview','job_offer','hired','rejected'];
    board.innerHTML = stages.map(stage => {
        const cards = list.filter(a => a.stage === stage);
        return `
        <div class="pipeline-col stage-${stage}">
            <div class="pipeline-col-header">
                ${STAGE_LABELS[stage]}
                <span class="pipeline-col-count">${cards.length}</span>
            </div>
            <div class="pipeline-col-body">
                ${cards.length ? cards.map(a => `
                <div class="pipeline-card" onclick="openApplicantModal(${a.id})">
                    <div class="pipeline-card-name">${esc(a.full_name)}</div>
                    <div class="pipeline-card-job">${esc(a.job_title||'—')}</div>
                    ${a.interview_date ? `<div style="font-size:0.68rem;color:#8b5cf6;margin-top:0.2rem;">📅 ${a.interview_date}</div>` : ''}
                </div>`).join('') : `<div style="font-size:0.75rem;color:#94a3b8;text-align:center;padding:1rem 0.5rem;">No applicants</div>`}
            </div>
        </div>`;
    }).join('');
}

// ── Job Detail ────────────────────────────────────────────────
function openJobDetail(jobId) {
    currentJobId = jobId;
    const job  = allJobs.find(j => j.id == jobId);
    if (!job) return;
    const apps = allApplicants.filter(a => a.job_id == jobId);

    document.getElementById('jdTitle').textContent = job.title;
    document.getElementById('jdMeta').textContent  = `${(job.employment_type||'').replace('_',' ')} · ${job.department_name||'No Dept'} · ${job.slots} slot(s) · Status: ${job.status}`;

    const appHtml = apps.length ? apps.map(a => `
        <div style="display:flex;justify-content:space-between;align-items:center;padding:0.65rem 0;border-bottom:1px solid #f0f0f0;cursor:pointer;" onclick="closeModal('jobDetailModal');openApplicantModal(${a.id})">
            <div>
                <div style="font-weight:700;font-size:0.9rem;">${esc(a.full_name)}</div>
                <div style="font-size:0.75rem;color:var(--text-secondary);">${esc(a.email||'—')} · ${esc(a.phone||'—')}</div>
            </div>
            <span class="badge" style="${stageBadgeStyle(a.stage)}">${STAGE_LABELS[a.stage]}</span>
        </div>`).join('')
        : '<p style="color:var(--text-secondary);font-size:0.85rem;">No applicants yet.</p>';

    document.getElementById('jdApplicants').innerHTML = `
        <div style="font-size:0.78rem;font-weight:700;color:var(--primary-purple);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.5rem;">Applicants (${apps.length})</div>
        ${appHtml}`;

    document.getElementById('jobDetailModal').classList.add('active');
}

function stageBadgeStyle(stage) {
    const map = {
        applied:   'background:#f1f5f9;color:#475569;',
        screening: 'background:#fef3c7;color:#92400e;',
        interview: 'background:#ede9fe;color:#5b21b6;',
        job_offer: 'background:#dbeafe;color:#1e40af;',
        hired:     'background:#d1fae5;color:#065f46;',
        rejected:  'background:#fee2e2;color:#991b1b;'
    };
    return map[stage] || '';
}

// ── Job Modal ─────────────────────────────────────────────────
function openJobModal(jobId=null) {
    if (jobId) {
        const j = allJobs.find(j => j.id == jobId);
        document.getElementById('jobModalTitle').textContent = 'Edit Job Posting';
        document.getElementById('jobId').value     = j.id;
        document.getElementById('jTitle').value    = j.title;
        document.getElementById('jDept').value     = j.department_id || '';
        document.getElementById('jEmpType').value  = j.employment_type;
        document.getElementById('jSlots').value    = j.slots;
        document.getElementById('jDeadline').value = j.deadline || '';
        document.getElementById('jDesc').value     = j.description || '';
        document.getElementById('jReqs').value     = j.requirements || '';
        document.getElementById('jStatus').value   = j.status;
    } else {
        document.getElementById('jobModalTitle').textContent = 'Post a Job';
        document.getElementById('jobId').value = '';
        ['jTitle','jDesc','jReqs'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('jDept').value    = '';
        document.getElementById('jEmpType').value = 'full_time';
        document.getElementById('jSlots').value   = '1';
        document.getElementById('jDeadline').value= '';
        document.getElementById('jStatus').value  = 'open';
    }
    document.getElementById('jobModal').classList.add('active');
}

function openEditJob() {
    closeModal('jobDetailModal');
    openJobModal(currentJobId);
}

async function saveJob() {
    const payload = {
        id:              document.getElementById('jobId').value || null,
        title:           document.getElementById('jTitle').value.trim(),
        department_id:   document.getElementById('jDept').value || null,
        employment_type: document.getElementById('jEmpType').value,
        slots:           parseInt(document.getElementById('jSlots').value) || 1,
        deadline:        document.getElementById('jDeadline').value || null,
        description:     document.getElementById('jDesc').value,
        requirements:    document.getElementById('jReqs').value,
        status:          document.getElementById('jStatus').value,
    };
    if (!payload.title) { showToast('Job title is required', 'error'); return; }
    const res  = await fetch('../../api/hr/save_job.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    const data = await res.json();
    if (data.success) {
        showToast('Job saved!', 'success');
        closeModal('jobModal');
        await loadJobs();
        updateStats();
    } else {
        showToast('Error: ' + data.message, 'error');
    }
}

// ── Applicant Modal ───────────────────────────────────────────
function openAddApplicant() {
    closeModal('jobDetailModal');
    const job = allJobs.find(j => j.id == currentJobId);
    document.getElementById('appModalTitle').textContent = `Add Applicant — ${job?.title||''}`;
    document.getElementById('appId').value    = '';
    document.getElementById('appJobId').value = currentJobId;
    ['aName','aEmail','aPhone','aAddress','aResume','aInterviewNotes','aRejection','aInterviewDate','aOfferDate','obEmail','obPassword'].forEach(id => {
        const el = document.getElementById(id); if(el) el.value = '';
    });
    document.getElementById('aStage').value = 'applied';
    document.getElementById('obRole').value = 'teacher';
    document.getElementById('obDept').value = '';
    handleStageChange();
    renderStageSteps('applied');
    document.getElementById('onboardSection').style.display = 'none';
    document.getElementById('appModal').classList.add('active');
}

function openApplicantModal(appId) {
    const a = allApplicants.find(a => a.id == appId);
    if (!a) return;
    const job = allJobs.find(j => j.id == a.job_id);
    document.getElementById('appModalTitle').textContent = `${a.full_name} — ${job?.title||''}`;
    document.getElementById('appId').value    = a.id;
    document.getElementById('appJobId').value = a.job_id;
    document.getElementById('aName').value    = a.full_name;
    document.getElementById('aEmail').value   = a.email || '';
    document.getElementById('aPhone').value   = a.phone || '';
    document.getElementById('aAddress').value = a.address || '';
    document.getElementById('aResume').value  = a.resume_notes || '';
    document.getElementById('aStage').value   = a.stage;
    document.getElementById('aInterviewDate').value  = a.interview_date || '';
    document.getElementById('aInterviewNotes').value = a.interview_notes || '';
    document.getElementById('aOfferDate').value      = a.offer_date || '';
    document.getElementById('aRejection').value      = a.rejection_reason || '';
    document.getElementById('obEmail').value    = a.email || '';
    document.getElementById('obPassword').value = '12345678';
    document.getElementById('obRole').value     = 'teacher';
    document.getElementById('obDept').value     = job?.department_id || '';
    handleStageChange();
    renderStageSteps(a.stage);
    document.getElementById('onboardSection').style.display = (a.stage === 'hired' && !a.onboarded) ? 'block' : 'none';
    document.getElementById('appModal').classList.add('active');
}

function handleStageChange() {
    const stage = document.getElementById('aStage').value;
    document.getElementById('interviewDateGroup').style.display  = (stage==='interview'||stage==='job_offer'||stage==='hired') ? 'block' : 'none';
    document.getElementById('interviewNotesGroup').style.display = (stage==='interview'||stage==='job_offer'||stage==='hired') ? 'block' : 'none';
    document.getElementById('offerDateGroup').style.display      = (stage==='job_offer'||stage==='hired') ? 'block' : 'none';
    document.getElementById('rejectionGroup').style.display      = stage==='rejected' ? 'block' : 'none';
    renderStageSteps(stage);
    const appId = document.getElementById('appId').value;
    if (appId) {
        const a = allApplicants.find(a => a.id == appId);
        document.getElementById('onboardSection').style.display = (stage==='hired' && !(a?.onboarded)) ? 'block' : 'none';
    }
}

function renderStageSteps(currentStage) {
    const mainStages = ['applied','screening','interview','job_offer','hired'];
    const isRejected = currentStage === 'rejected';
    const curIdx = mainStages.indexOf(currentStage);
    let html = '<div style="display:flex;align-items:flex-start;gap:0;overflow-x:auto;">';
    mainStages.forEach((s, i) => {
        let dotClass = '';
        if (isRejected) dotClass = i < curIdx ? 'done' : '';
        else if (i < curIdx) dotClass = 'done';
        else if (i === curIdx) dotClass = 'current';
        const color = dotClass==='done' ? '#10b981' : dotClass==='current' ? '#3D6B9F' : '#cbd5e1';
        const lineColor = (i < curIdx && !isRejected) ? '#10b981' : '#e5e7eb';
        html += `<div style="display:flex;flex-direction:column;align-items:center;flex:1;">
            <div style="display:flex;align-items:center;width:100%;">
                <div style="width:26px;height:26px;border-radius:50%;background:${color};color:white;display:flex;align-items:center;justify-content:center;font-size:0.68rem;font-weight:800;flex-shrink:0;">${i+1}</div>
                ${i < mainStages.length-1 ? `<div style="flex:1;height:2px;background:${lineColor};"></div>` : ''}
            </div>
            <div style="font-size:0.6rem;font-weight:700;color:${dotClass?'#3D6B9F':'#94a3b8'};margin-top:0.25rem;text-align:center;white-space:nowrap;">${STAGE_LABELS[s]}</div>
        </div>`;
    });
    if (isRejected) {
        html += `<div style="display:flex;flex-direction:column;align-items:center;padding-left:0.5rem;">
            <div style="width:26px;height:26px;border-radius:50%;background:#ef4444;color:white;display:flex;align-items:center;justify-content:center;font-size:0.68rem;font-weight:800;">✕</div>
            <div style="font-size:0.6rem;font-weight:700;color:#ef4444;margin-top:0.25rem;">Rejected</div>
        </div>`;
    }
    html += '</div>';
    document.getElementById('stageSteps').innerHTML = html;
}

async function saveApplicant() {
    const payload = {
        id:              document.getElementById('appId').value || null,
        job_id:          document.getElementById('appJobId').value,
        full_name:       document.getElementById('aName').value.trim(),
        email:           document.getElementById('aEmail').value.trim(),
        phone:           document.getElementById('aPhone').value.trim(),
        address:         document.getElementById('aAddress').value.trim(),
        resume_notes:    document.getElementById('aResume').value,
        stage:           document.getElementById('aStage').value,
        interview_date:  document.getElementById('aInterviewDate').value || null,
        interview_notes: document.getElementById('aInterviewNotes').value,
        offer_date:      document.getElementById('aOfferDate').value || null,
        rejection_reason:document.getElementById('aRejection').value,
    };
    if (!payload.full_name) { showToast('Full name is required', 'error'); return; }
    if (!payload.job_id)    { showToast('No job selected', 'error'); return; }
    const res  = await fetch('../../api/hr/save_applicant.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    const data = await res.json();
    if (data.success) {
        if (payload.stage === 'hired') {
            // Keep modal open and show onboard section so HR can create account immediately
            document.getElementById('appId').value = data.id;
            document.getElementById('onboardSection').style.display = 'block';
            showToast('Applicant marked as Hired! Create their system account below.', 'success');
        } else {
            showToast('Applicant saved!', 'success');
            closeModal('appModal');
        }
        await loadApplicants();
        updateStats();
    } else {
        showToast('Error: ' + data.message, 'error');
    }
}

async function onboardApplicant() {
    const appId   = document.getElementById('appId').value;
    const email   = document.getElementById('obEmail').value.trim();
    const password= document.getElementById('obPassword').value.trim();
    const role    = document.getElementById('obRole').value;
    const deptId  = document.getElementById('obDept').value || null;
    const name    = document.getElementById('aName').value.trim();
    if (!email || !password) { showToast('Email and password are required to onboard', 'error'); return; }
    const res  = await fetch('../../api/hr/onboard_applicant.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ applicant_id:appId, name, email, password, role, department_id:deptId })
    });
    const data = await res.json();
    if (data.success) {
        showToast('Employee account created successfully!', 'success');
        closeModal('appModal');
        await Promise.all([loadJobs(), loadApplicants()]);
        updateStats();
    } else {
        showToast('Error: ' + data.message, 'error');
    }
}

function closeModal(id) { document.getElementById(id).classList.remove('active'); }

function showToast(msg, type='success') {
    const t = document.getElementById('toast');
    t.textContent = msg; t.className = `toast ${type}`; t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3000);
}
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
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
      <a href="employees.php" class="mobile-nav-item" data-page="employees">
        <span class="mobile-nav-icon">👤</span><span>Staff</span>
      </a>
      <a href="attendance.php" class="mobile-nav-item" data-page="attendance">
        <span class="mobile-nav-icon">🕐</span><span>Attend.</span>
      </a>
      <a href="leaves.php" class="mobile-nav-item" data-page="leaves">
        <span class="mobile-nav-icon">📅</span><span>Leaves</span>
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
