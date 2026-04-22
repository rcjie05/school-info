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
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Teacher Dashboard</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        .day-block { margin-bottom: 2rem; }
        .day-label {
            font-weight: 700;
            font-size: 1rem;
            color: var(--primary-purple);
            padding: 0.4rem 1rem;
            background: rgba(124,58,237,0.08);
            border-radius: var(--radius-md);
            display: inline-block;
            margin-bottom: 0.75rem;
            letter-spacing: 0.04em;
        }
        .class-card {
            padding: 1rem 1.25rem;
            background: var(--background-main);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--primary-purple);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        .class-card .subject-name { font-weight: 600; font-size: 1rem; margin-bottom: 0.2rem; }
        .class-card .meta { font-size: 0.82rem; color: var(--text-secondary); display: flex; flex-wrap: wrap; gap: 0.6rem; margin-top: 0.25rem; }
        .class-card .meta span { background: rgba(124,58,237,0.07); padding: 0.1rem 0.5rem; border-radius: 20px; }
        .class-card .time-room { text-align: right; flex-shrink: 0; }
        .class-card .time-room .time { font-weight: 700; color: var(--primary-purple); font-size: 0.95rem; }
        .class-card .time-room .room { font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.15rem; }
        .no-class { color: var(--text-secondary); font-style: italic; font-size: 0.9rem; padding: 0.5rem 0; }
        .summary-row { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        .summary-chip {
            padding: 0.5rem 1rem;
            background: var(--background-main);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid rgba(124,58,237,0.15);
        }
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
                    <span>Teacher Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="schedule.php" class="nav-item active"><span class="nav-icon">📅</span><span>My Schedule</span></a>
                    <a href="classes.php" class="nav-item"><span class="nav-icon">📚</span><span>My Classes</span></a>
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
                    <h1>My Schedule</h1>
                    <p class="page-subtitle">Weekly class schedule based on assigned sections</p>
                </div>
                <div class="header-actions">
                    <div class="school-year-badge"><span>📚</span><span id="schoolYearLabel">School Year</span></div>
                </div>
            </header>

            <div id="summaryRow" class="summary-row" style="display:none;"></div>

            <div class="content-card">
                <div class="card-header"><h2 class="card-title">Weekly Schedule</h2></div>
                <div id="weeklySchedule" style="padding: 1rem;">
                    <p style="text-align:center; color:var(--text-secondary); padding:2rem;">Loading schedule...</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        const DAYS = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

        async function loadSchedule() {
            try {
                const res  = await fetch('../../api/teacher/get_schedule.php');
                const data = await res.json();

                if (!data.success) {
                    document.getElementById('weeklySchedule').innerHTML =
                        '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">Could not load schedule.</p>';
                    return;
                }

                const schedule = data.schedule;

                // Summary chips
                let totalClasses = 0, totalStudents = 0, schoolYear = '';
                DAYS.forEach(d => {
                    totalClasses += schedule[d].length;
                    schedule[d].forEach(c => {
                        totalStudents += c.student_count;
                        if (!schoolYear && c.school_year) schoolYear = c.school_year;
                    });
                });

                if (schoolYear) document.getElementById('schoolYearLabel').textContent = 'SY ' + schoolYear;

                const summaryRow = document.getElementById('summaryRow');
                if (totalClasses > 0) {
                    summaryRow.style.display = 'flex';
                    summaryRow.innerHTML = `
                        <div class="summary-chip">📅 ${totalClasses} Class${totalClasses !== 1 ? 'es' : ''} This Week</div>
                        <div class="summary-chip">👥 ${totalStudents} Total Students</div>
                    `;
                }

                // Build schedule HTML
                let html = '';
                let hasAny = false;

                DAYS.forEach(day => {
                    const classes = schedule[day];
                    html += `<div class="day-block">
                        <div class="day-label">📆 ${day}</div>
                        <div>`;

                    if (classes.length > 0) {
                        hasAny = true;
                        classes.forEach(cls => {
                            html += `
                            <div class="class-card">
                                <div style="flex:1;">
                                    <div class="subject-name">${cls.subject_code} — ${cls.subject_name}</div>
                                    <div class="meta">
                                        <span>🏫 ${cls.section}</span>
                                        <span>📘 ${cls.course}</span>
                                        <span>📅 Year ${cls.year_level}</span>
                                        <span>📖 Sem ${cls.semester}</span>
                                        <span>👥 ${cls.student_count} students</span>
                                        <span>⚡ ${cls.units} units</span>
                                    </div>
                                </div>
                                <div class="time-room">
                                    <div class="time">🕐 ${cls.time}</div>
                                    <div class="room">📍 ${cls.room}</div>
                                </div>
                            </div>`;
                        });
                    } else {
                        html += `<div class="no-class">No classes scheduled</div>`;
                    }

                    html += `</div></div>`;
                });

                if (!hasAny) {
                    html = '<p style="text-align:center;color:var(--text-secondary);padding:3rem;">No classes scheduled yet. Contact the registrar to be assigned to sections.</p>';
                }

                document.getElementById('weeklySchedule').innerHTML = html;

            } catch (err) {
                document.getElementById('weeklySchedule').innerHTML =
                    '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">Failed to load schedule. Please try again.</p>';
            }
        }

        loadSchedule();
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
