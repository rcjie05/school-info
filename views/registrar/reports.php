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
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        /* Report cards */
        .report-cards { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.25rem; margin-bottom: 2rem; }
        @media(max-width:768px){ .report-cards{ grid-template-columns:1fr; } }
        .report-card {
            background: var(--background-card);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, box-shadow .2s, transform .15s;
        }
        .report-card:hover { border-color: var(--primary-purple); box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .report-card.active { border-color: var(--primary-purple); background: rgba(61,107,159,0.05); }
        .report-card-icon { font-size: 2.5rem; margin-bottom: .75rem; }
        .report-card h3 { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin-bottom: .4rem; }
        .report-card p { font-size: .8rem; color: var(--text-secondary); }

        /* Report container */
        #reportContainer { display: none; }
        .report-header-bar {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: .75rem; margin-bottom: 1.5rem;
        }
        .report-header-bar h2 { font-size: 1.2rem; font-weight: 700; }
        .report-actions { display: flex; gap: .5rem; flex-wrap: wrap; }

        /* Stat mini-cards */
        .report-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 1.5rem; }
        @media(max-width:768px){ .report-stats{ grid-template-columns:1fr 1fr; } }
        @media(max-width:480px){ .report-stats{ grid-template-columns:1fr; } }
        .rstat {
            background: var(--background-main);
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            border-left: 4px solid var(--primary-purple);
        }
        .rstat.green { border-color: var(--secondary-green); }
        .rstat.red   { border-color: var(--secondary-pink); }
        .rstat.gold  { border-color: var(--secondary-yellow); }
        .rstat.blue  { border-color: var(--secondary-blue); }
        .rstat-label { font-size: .75rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .5px; margin-bottom: .3rem; }
        .rstat-value { font-size: 1.8rem; font-weight: 800; color: var(--text-primary); }

        /* Section headings */
        .report-section-title { font-size: .95rem; font-weight: 700; color: var(--text-primary); margin: 1.5rem 0 .75rem; padding-bottom: .4rem; border-bottom: 2px solid var(--border-color); }

        /* Table */
        .report-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .report-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .report-table th { background: var(--background-main); font-weight: 700; font-size: .75rem; text-transform: uppercase; letter-spacing: .5px; color: var(--text-secondary); padding: .6rem 1rem; text-align: left; }
        .report-table td { padding: .7rem 1rem; border-bottom: 1px solid var(--border-color); color: var(--text-primary); }
        .report-table tr:last-child td { border-bottom: none; }
        .report-table tr:hover td { background: var(--background-main); }
        .pass-rate-bar { display: flex; align-items: center; gap: .5rem; }
        .bar-track { flex: 1; height: 6px; background: var(--border-color); border-radius: 3px; min-width: 60px; }
        .bar-fill { height: 100%; border-radius: 3px; background: var(--secondary-green); transition: width .4s; }
        .bar-fill.low { background: var(--secondary-pink); }

        /* Loading / error */
        .report-loading { text-align: center; padding: 3rem 1rem; color: var(--text-secondary); font-size: .95rem; }
        .report-error { text-align: center; padding: 2rem; color: var(--secondary-pink); font-weight: 600; }

        /* Badge */
        .badge { display: inline-block; padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 700; }
        .badge-green { background: rgba(90,158,138,.15); color: var(--secondary-green); }
        .badge-red   { background: rgba(184,92,92,.15);  color: var(--secondary-pink); }
        .badge-gold  { background: rgba(212,169,106,.15); color: #a07830; }
        .badge-grey  { background: var(--background-main); color: var(--text-secondary); }

        /* Print styles */
        @media print {
            .sidebar, .page-header, .report-cards, .report-actions,
            .mobile-bottom-nav, .sidebar-overlay, .sidebar-toggle { display: none !important; }
            .main-content { margin: 0 !important; padding: 1cm !important; }
            #reportContainer { display: block !important; }
            .report-card { display: none; }
            body { background: white !important; }
            .report-table th { background: #eee !important; }
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">
                <img src="../../images/logo2.jpg" alt="Logo" id="sidebarLogoImg" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-md);">
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
                <a href="grades.php" class="nav-item"><span class="nav-icon">🎓</span><span>Grades</span></a>
                <a href="add_drop_requests.php" class="nav-item"><span class="nav-icon">🔄</span><span>Add/Drop Requests</span></a>
                <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                <a href="reports.php" class="nav-item active"><span class="nav-icon">📈</span><span>Reports</span></a>
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
                <h1>Reports & Analytics</h1>
                <p class="page-subtitle">Generate enrollment, grades, and student reports</p>
            </div>
        </header>

        <!-- Report type selection -->
        <div class="report-cards">
            <div class="report-card" id="card-enrollment" onclick="generateReport('enrollment')">
                <div class="report-card-icon">👥</div>
                <h3>Enrollment Report</h3>
                <p>Student counts by course, year level, and status</p>
            </div>
            <div class="report-card" id="card-grades" onclick="generateReport('grades')">
                <div class="report-card-icon">🎓</div>
                <h3>Grades Summary</h3>
                <p>Grade distribution, pass/fail rates by course and semester</p>
            </div>
            <div class="report-card" id="card-applications" onclick="generateReport('applications')">
                <div class="report-card-icon">📋</div>
                <h3>Student Status Report</h3>
                <p>Student approval rates, add/drop request summary</p>
            </div>
        </div>

        <!-- Report output -->
        <div class="content-card" id="reportContainer">
            <div class="report-header-bar">
                <h2 id="reportTitle">Report</h2>
                <div class="report-actions">
                    <button class="btn" onclick="printReport()" style="background:var(--background-main);border:1px solid var(--border-color);">🖨️ Print</button>
                    <button class="btn btn-primary" onclick="exportCSV()" id="exportBtn">⬇️ Export CSV</button>
                </div>
            </div>
            <div id="reportContent">
                <div class="report-loading">Select a report type above to get started.</div>
            </div>
        </div>
    </main>
</div>

<nav class="mobile-bottom-nav" aria-label="Mobile navigation">
    <a href="dashboard.php" class="mobile-nav-item" data-page="dashboard"><span class="mobile-nav-icon">📊</span><span>Home</span></a>
    <a href="applications.php" class="mobile-nav-item" data-page="applications"><span class="mobile-nav-icon">📋</span><span>Apps</span></a>
    <a href="manage_loads.php" class="mobile-nav-item" data-page="manage_loads"><span class="mobile-nav-icon">📚</span><span>Loads</span></a>
    <a href="announcements.php" class="mobile-nav-item" data-page="announcements"><span class="mobile-nav-icon">📢</span><span>Notices</span></a>
    <a href="profile.php" class="mobile-nav-item" data-page="profile"><span class="mobile-nav-icon">👤</span><span>Profile</span></a>
</nav>

<script>
(function(){
    var page = location.pathname.split('/').pop().replace('.php','');
    document.querySelectorAll('.mobile-nav-item').forEach(function(el){ if(el.dataset.page===page) el.classList.add('active'); });
})();

// Sidebar
(function(){
    var sidebar = document.querySelector('.sidebar');
    var saved = sessionStorage.getItem('sidebarScroll');
    if(saved) sidebar.scrollTop = parseInt(saved);
    document.querySelectorAll('.nav-item').forEach(function(link){
        link.addEventListener('click', function(){ sessionStorage.setItem('sidebarScroll', sidebar.scrollTop); });
    });
    var toggle  = document.getElementById('sidebarToggle');
    var overlay = document.getElementById('sidebarOverlay');
    function open(){ sidebar.classList.add('active'); overlay&&overlay.classList.add('active'); document.body.style.overflow='hidden'; }
    function close(){ sidebar.classList.remove('active'); overlay&&overlay.classList.remove('active'); document.body.style.overflow=''; }
    toggle&&toggle.addEventListener('click', function(){ sidebar.classList.contains('active')?close():open(); });
    overlay&&overlay.addEventListener('click', close);
    document.querySelectorAll('.nav-item').forEach(function(l){ l.addEventListener('click',function(){ if(window.innerWidth<=1024) close(); }); });
})();
</script>
<script src="../../js/theme-switcher.js"></script>
<script src="../../js/session-monitor.js"></script>
<script src="../../js/apply-branding.js"></script>

<script>
var currentReportType = null;
var currentReportData = null;

function generateReport(type) {
    currentReportType = type;
    // Highlight active card
    document.querySelectorAll('.report-card').forEach(function(c){ c.classList.remove('active'); });
    document.getElementById('card-' + type).classList.add('active');

    // Show container, set loading
    var container = document.getElementById('reportContainer');
    container.style.display = 'block';
    document.getElementById('reportContent').innerHTML = '<div class="report-loading">⏳ Loading report...</div>';

    var titles = { enrollment: 'Enrollment Report', grades: 'Grades Summary', applications: 'Student Status Report' };
    document.getElementById('reportTitle').textContent = titles[type] || 'Report';

    var urls = {
        enrollment:   '../../api/registrar/get_enrollment_report.php',
        grades:       '../../api/registrar/get_grades_report.php',
        applications: '../../api/registrar/get_applications_report.php'
    };

    fetch(urls[type])
        .then(function(r){ return r.json(); })
        .then(function(data){
            currentReportData = data;
            if(!data.success) throw new Error(data.message || 'Server error');
            if(type === 'enrollment')   renderEnrollment(data);
            if(type === 'grades')       renderGrades(data);
            if(type === 'applications') renderApplications(data);
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(function(e){
            document.getElementById('reportContent').innerHTML =
                '<div class="report-error">⚠️ Failed to load report: ' + e.message + '</div>';
        });
}

// ── Enrollment Report ────────────────────────────────────────────────
function renderEnrollment(d) {
    var s = d.stats;
    var html = '';

    html += '<div class="report-stats">';
    html += rstat('Total Students', s.total_students, '');
    html += rstat('Active', s.active_students, 'green');
    html += rstat('Pending', s.pending_students, 'gold');
    html += rstat('New (30 days)', s.recent_enrollments, 'blue');
    html += '</div>';

    // By year level
    if(d.by_year && d.by_year.length) {
        html += '<div class="report-section-title">Students by Year Level</div>';
        html += '<div class="report-table-wrap"><table class="report-table"><thead><tr><th>Year Level</th><th>Count</th><th>Share</th></tr></thead><tbody>';
        d.by_year.forEach(function(r){
            var pct = s.total_students > 0 ? ((r.total / s.total_students)*100).toFixed(1) : 0;
            html += '<tr><td><strong>' + esc(r.year_level) + '</strong></td><td>' + r.total + '</td>';
            html += '<td><div class="pass-rate-bar"><div class="bar-track"><div class="bar-fill" style="width:'+pct+'%"></div></div><span style="font-size:.8rem;min-width:36px">'+pct+'%</span></div></td></tr>';
        });
        html += '</tbody></table></div>';
    }

    // By course
    if(d.by_course && d.by_course.length) {
        html += '<div class="report-section-title">Students by Course</div>';
        html += '<div class="report-table-wrap"><table class="report-table"><thead><tr><th>Course</th><th>Total</th><th>1st Yr</th><th>2nd Yr</th><th>3rd Yr</th><th>4th Yr</th></tr></thead><tbody>';
        d.by_course.forEach(function(r){
            html += '<tr><td><strong>' + esc(r.course) + '</strong></td><td>' + r.total + '</td>';
            html += '<td>' + (r.year_1||0) + '</td><td>' + (r.year_2||0) + '</td><td>' + (r.year_3||0) + '</td><td>' + (r.year_4||0) + '</td></tr>';
        });
        html += '</tbody></table></div>';
    }

    document.getElementById('reportContent').innerHTML = html;
}

// ── Grades Report ────────────────────────────────────────────────────
function renderGrades(d) {
    var s = d.stats;
    var html = '';

    html += '<div class="report-stats">';
    html += rstat('Total Grade Records', s.total_grades, '');
    html += rstat('Passed', s.passed, 'green');
    html += rstat('Failed', s.failed, 'red');
    html += rstat('Avg Grade', s.average_grade || 'N/A', 'blue');
    html += '</div>';

    // By semester
    if(d.by_semester && d.by_semester.length) {
        html += '<div class="report-section-title">Results by Semester</div>';
        html += '<div class="report-table-wrap"><table class="report-table"><thead><tr><th>Semester</th><th>School Year</th><th>Total</th><th>Passed</th><th>Failed</th><th>Pass Rate</th></tr></thead><tbody>';
        d.by_semester.forEach(function(r){
            var rate = r.total > 0 ? ((r.passed / r.total)*100).toFixed(1) : 0;
            html += '<tr><td>' + esc(r.semester) + '</td><td>' + esc(r.school_year) + '</td>';
            html += '<td>' + r.total + '</td><td><span class="badge badge-green">' + r.passed + '</span></td>';
            html += '<td><span class="badge badge-red">' + r.failed + '</span></td>';
            html += '<td><div class="pass-rate-bar"><div class="bar-track"><div class="bar-fill '+(rate<50?'low':'')+'" style="width:'+rate+'%"></div></div><span style="font-size:.8rem;min-width:36px">'+rate+'%</span></div></td></tr>';
        });
        html += '</tbody></table></div>';
    }

    // By course
    if(d.by_course && d.by_course.length) {
        html += '<div class="report-section-title">Performance by Course</div>';
        html += '<div class="report-table-wrap"><table class="report-table"><thead><tr><th>Course</th><th>Total</th><th>Passed</th><th>Failed</th><th>Pass Rate</th><th>Avg Grade</th></tr></thead><tbody>';
        d.by_course.forEach(function(r){
            var rate = r.total > 0 ? ((r.passed / r.total)*100).toFixed(1) : 0;
            html += '<tr><td><strong>' + esc(r.course) + '</strong></td><td>' + r.total + '</td>';
            html += '<td><span class="badge badge-green">' + r.passed + '</span></td>';
            html += '<td><span class="badge badge-red">' + r.failed + '</span></td>';
            html += '<td><div class="pass-rate-bar"><div class="bar-track"><div class="bar-fill '+(rate<50?'low':'')+'" style="width:'+rate+'%"></div></div><span style="font-size:.8rem;min-width:36px">'+rate+'%</span></div></td>';
            html += '<td>' + (r.avg_grade || 'N/A') + '</td></tr>';
        });
        html += '</tbody></table></div>';
    }

    document.getElementById('reportContent').innerHTML = html;
}

// ── Applications / Student Status Report ────────────────────────────
function renderApplications(d) {
    var s = d.stats;
    var ad = d.add_drop || {};
    var html = '';

    html += '<div class="report-section-title">Student Account Status</div>';
    html += '<div class="report-stats">';
    html += rstat('Total Students', s.total, '');
    html += rstat('Active', s.approved, 'green');
    html += rstat('Pending', s.pending, 'gold');
    html += rstat('Rejected', s.rejected, 'red');
    html += '</div>';

    // Monthly registrations
    if(d.monthly && d.monthly.length) {
        html += '<div class="report-section-title">New Students (Last 6 Months)</div>';
        html += '<div class="report-table-wrap"><table class="report-table"><thead><tr><th>Month</th><th>New Students</th></tr></thead><tbody>';
        d.monthly.forEach(function(r){
            html += '<tr><td>' + esc(r.month) + '</td><td>' + r.total + '</td></tr>';
        });
        html += '</tbody></table></div>';
    }

    // By course
    if(d.by_course && d.by_course.length) {
        html += '<div class="report-section-title">Students by Course</div>';
        html += '<div class="report-table-wrap"><table class="report-table"><thead><tr><th>Course</th><th>Total</th><th>Active</th><th>Pending</th><th>Rejected</th><th>Approval Rate</th></tr></thead><tbody>';
        d.by_course.forEach(function(r){
            var rate = r.total > 0 ? ((r.approved / r.total)*100).toFixed(1) : 0;
            html += '<tr><td><strong>' + esc(r.course) + '</strong></td><td>' + r.total + '</td>';
            html += '<td><span class="badge badge-green">' + r.approved + '</span></td>';
            html += '<td><span class="badge badge-gold">' + r.pending + '</span></td>';
            html += '<td><span class="badge badge-red">' + r.rejected + '</span></td>';
            html += '<td><div class="pass-rate-bar"><div class="bar-track"><div class="bar-fill '+(rate<50?'low':'')+'" style="width:'+rate+'%"></div></div><span style="font-size:.8rem;min-width:36px">'+rate+'%</span></div></td></tr>';
        });
        html += '</tbody></table></div>';
    }

    // Add/Drop summary
    if(ad.total) {
        html += '<div class="report-section-title">Add/Drop Requests Summary</div>';
        html += '<div class="report-stats">';
        html += rstat('Total Requests', ad.total, '');
        html += rstat('Approved', ad.approved, 'green');
        html += rstat('Pending', ad.pending, 'gold');
        html += rstat('Rejected', ad.rejected, 'red');
        html += '</div>';
        html += '<div class="report-table-wrap"><table class="report-table"><thead><tr><th>Type</th><th>Count</th></tr></thead><tbody>';
        html += '<tr><td>Add Requests</td><td>' + ad.add_requests + '</td></tr>';
        html += '<tr><td>Drop Requests</td><td>' + ad.drop_requests + '</td></tr>';
        html += '</tbody></table></div>';
    }

    document.getElementById('reportContent').innerHTML = html;
}

// ── Helpers ──────────────────────────────────────────────────────────
function rstat(label, value, color) {
    return '<div class="rstat '+color+'"><div class="rstat-label">'+label+'</div><div class="rstat-value">'+(value||0)+'</div></div>';
}
function esc(s) {
    if(!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function printReport() {
    window.print();
}

function exportCSV() {
    if(!currentReportData || !currentReportData.success) {
        alert('Please generate a report first.');
        return;
    }
    var rows = [], title = document.getElementById('reportTitle').textContent;
    var d = currentReportData;

    if(currentReportType === 'enrollment' && d.by_course) {
        rows.push(['Course','Total','1st Year','2nd Year','3rd Year','4th Year']);
        d.by_course.forEach(function(r){ rows.push([r.course, r.total, r.year_1||0, r.year_2||0, r.year_3||0, r.year_4||0]); });
    } else if(currentReportType === 'grades' && d.by_course) {
        rows.push(['Course','Total','Passed','Failed','Avg Grade']);
        d.by_course.forEach(function(r){ rows.push([r.course, r.total, r.passed, r.failed, r.avg_grade||'N/A']); });
    } else if(currentReportType === 'applications' && d.by_course) {
        rows.push(['Course','Total','Active','Pending','Rejected']);
        d.by_course.forEach(function(r){ rows.push([r.course, r.total, r.approved, r.pending, r.rejected]); });
    }

    if(!rows.length) { alert('No data to export.'); return; }

    var csv = rows.map(function(r){ return r.map(function(c){ return '"'+String(c).replace(/"/g,'""')+'"'; }).join(','); }).join('\n');
    var blob = new Blob([csv], {type:'text/csv'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = title.replace(/\s+/g,'_') + '_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}
</script>
</body>
</html>
