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
$student_course = strtolower($_SESSION['course'] ?? '');
$_avatar_conn = getDBConnection();
$_col = $_avatar_conn->query("SHOW COLUMNS FROM `users` LIKE 'avatar_url'");
if ($_col && $_col->num_rows === 0) {
    $_avatar_conn->query("ALTER TABLE `users` ADD COLUMN `avatar_url` VARCHAR(500) NULL DEFAULT NULL AFTER `status`");
}
$_avatar_stmt = $_avatar_conn->prepare("SELECT name, avatar_url FROM users WHERE id = ?");
$_avatar_stmt->bind_param("i", $_SESSION['user_id']);
$_avatar_stmt->execute();
$_avatar_user = $_avatar_stmt->get_result()->fetch_assoc();
$_avatar_stmt->close();
$_avatar_conn->close();

$show_bsit_bg = (strpos($student_course, 'bsit') !== false || strpos($student_course, 'information technology') !== false);
$show_bshtm_bg = (strpos($student_course, 'bshtm') !== false || strpos($student_course, 'hospitality') !== false || strpos($student_course, 'tourism') !== false || strpos($student_course, 'htm') !== false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        /* ── Weekly Schedule Widget ── */
        .sched-tabs {
            display: flex;
            gap: 0.35rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .sched-tab {
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            border: 1.5px solid var(--border-color);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            background: white;
            color: var(--text-secondary);
            transition: all 0.15s;
        }
        .sched-tab:hover { border-color: var(--primary-purple); color: var(--primary-purple); }
        .sched-tab.active {
            background: var(--primary-purple);
            border-color: var(--primary-purple);
            color: white;
        }
        .sched-tab.today-tab { position: relative; }
        .today-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            background: var(--secondary-green);
            border-radius: 50%;
            margin-left: 4px;
            vertical-align: middle;
        }
        .sched-day-panel { display: none; }
        .sched-day-panel.active { display: flex; flex-direction: column; gap: 0.75rem; }
        .sched-item {
            display: flex;
            align-items: stretch;
            gap: 0.75rem;
            background: var(--background-main, #f9fafb);
            border-radius: var(--radius-md);
            padding: 0.85rem 0.85rem;
            border-left: 4px solid var(--primary-purple);
            transition: box-shadow 0.15s;
            min-width: 0;
            overflow: hidden;
        }
        .sched-item:hover { box-shadow: 0 2px 8px rgba(91,78,155,0.12); }
        .sched-time-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 58px;
            max-width: 58px;
            flex-shrink: 0;
            text-align: center;
        }
        .sched-time-start {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--primary-purple);
        }
        .sched-time-end {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        .sched-divider {
            width: 1px;
            background: var(--border-color);
            align-self: stretch;
        }
        .sched-info { flex: 1; min-width: 0; overflow: hidden; }
        .sched-divider { flex-shrink: 0; }
        .sched-subj {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 0.92rem;
            white-space: normal;
            overflow-wrap: break-word;
            word-break: break-word;
            line-height: 1.3;
        }
        .sched-subj-code {
            font-size: 0.75rem;
            color: var(--primary-purple);
            font-weight: 600;
            margin-bottom: 0.15rem;
        }
        .sched-meta {
            display: flex;
            gap: 0.5rem 0.6rem;
            flex-wrap: wrap;
            margin-top: 0.3rem;
        }
        .sched-meta span {
            font-size: 0.78rem;
            color: var(--text-secondary);
            display: flex;
            align-items: flex-start;
            gap: 0.25rem;
            overflow-wrap: break-word;
            word-break: break-word;
            min-width: 0;
            flex-shrink: 1;
        }
        .no-class-msg {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        .no-class-msg .nc-icon { font-size: 2.2rem; display: block; margin-bottom: 0.5rem; }

        /* ── Enrollment Card ── */
        .enroll-section { margin-top: 2rem; }
        .enroll-card {
            background: white;
            border-radius: var(--radius-lg, 14px);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .enroll-card-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1.5px solid var(--border-color);
        }
        .enroll-card-header h2 {
            font-size: 1rem; font-weight: 700;
            color: var(--text-primary);
            display: flex; align-items: center; gap: 0.5rem;
        }
        /* Blocked state */
        .enroll-blocked { padding: 2.5rem 2rem; text-align: center; }
        .blocked-icon { font-size: 3.5rem; margin-bottom: 1rem; display: block; }
        .blocked-badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: rgba(184,92,92,0.12); color: var(--secondary-pink);
            border: 1px solid rgba(184,92,92,0.3); border-radius: 999px;
            padding: 0.35rem 1rem; font-size: 0.78rem; font-weight: 700;
            letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 1rem;
        }
        .blocked-title { font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
        .blocked-desc {
            font-size: 0.875rem; color: var(--text-secondary);
            max-width: 480px; margin: 0 auto 1.5rem; line-height: 1.7;
        }
        .blocked-info-box {
            background: rgba(212,169,106,0.12); border: 1px solid rgba(212,169,106,0.35);
            border-radius: 10px; padding: 1rem 1.25rem;
            max-width: 420px; margin: 0 auto; text-align: left;
        }
        .blocked-info-box strong { display: block; font-size: 0.82rem; color: var(--text-primary); margin-bottom: 0.4rem; }
        .blocked-info-box ul { padding-left: 1.2rem; font-size: 0.82rem; color: var(--text-secondary); line-height: 1.8; }
        .blocked-contact { margin-top: 1.25rem; font-size: 0.82rem; color: var(--text-secondary); }
        .blocked-contact a { color: var(--primary-purple); font-weight: 600; text-decoration: none; }
        /* Open state */
        .enroll-open-notice {
            background: rgba(90,158,138,0.08); border-bottom: 1.5px solid rgba(90,158,138,0.3);
            padding: 0.85rem 1.5rem;
            display: flex; align-items: center; gap: 0.75rem;
            font-size: 0.85rem; color: var(--secondary-green);
        }
        .enroll-open-notice strong { font-weight: 700; }
        .enroll-frame { width: 100%; height: 700px; border: none; display: block; }
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
                    <span>Student Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item active"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="schedule.php" class="nav-item"><span class="nav-icon">📅</span><span>My Schedule</span></a>
                    <a href="subjects.php" class="nav-item"><span class="nav-icon">📚</span><span>Study Load</span></a>
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
                    <h1>Welcome to <?= htmlspecialchars($school_name) ?></h1>
                </div>
                <div class="header-actions">
                    <!-- Global Search -->
                    <button class="global-search-btn" id="globalSearchBtn" aria-label="Search" title="Search">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </button>

                    <div class="school-year-badge">
                        <span>📚</span>
                        <span>School Year <?= htmlspecialchars($current_school_year) ?></span>
                    </div>
                    <a href="profile.php" style="text-decoration:none;">
                    <div class="user-profile">
                        <div class="user-avatar" id="userAvatar"><?php
$_avatar_url = !empty($_avatar_user['avatar_url']) ? htmlspecialchars(getAvatarUrl($_avatar_user['avatar_url'])) : null;
$_initials   = strtoupper(substr($_avatar_user['name'] ?? '?', 0, 1));
?>
<?php if ($_avatar_url): ?>
<img src="<?= $_avatar_url ?>?t=<?= time() ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" alt="">
<?php else: ?>
<?= $_initials ?>
<?php endif; ?></div>
                        <div class="user-info">
                            <div class="user-name" id="userName">Student Name</div>
                            <div class="user-role">Student</div>
                        </div>
                    </div>
                    </a>
                </div>
            </header>

            <!-- Enrollment Banner (shown when enrollment is open) -->
            <div id="enrollmentBanner" style="display:none; margin-bottom:1.25rem;">
                <div style="
                    background: var(--sidebar-gradient);
                    border: 1px solid var(--primary-purple);
                    border-radius: var(--radius-lg, 16px);
                    padding: 1.1rem 1.5rem;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 1rem;
                    flex-wrap: wrap;
                    box-shadow: var(--shadow-md);
                ">
                    <div style="display:flex; align-items:center; gap:0.85rem;">
                        <div style="
                            width: 2.75rem; height: 2.75rem;
                            background: rgba(255,255,255,0.15);
                            border-radius: 10px;
                            display: flex; align-items: center; justify-content: center;
                            font-size: 1.35rem; flex-shrink: 0;
                        ">📋</div>
                        <div>
                            <div style="font-weight:700; font-size:1rem; color:var(--text-white); letter-spacing:0.01em;">Enrollment is now open!</div>
                            <div style="font-size:0.82rem; color:rgba(255,255,255,0.7);" id="enrollmentBannerSub">Submit your enrollment for the current semester.</div>
                        </div>
                    </div>
                    <a id="enrollNowBtn" href="../enrollment.html" style="
                        display: inline-flex;
                        align-items: center;
                        gap: 0.4rem;
                        background: var(--btn-primary-gradient);
                        color: var(--text-white);
                        font-weight: 700;
                        font-size: 0.875rem;
                        padding: 0.6rem 1.25rem;
                        border-radius: var(--radius-md, 12px);
                        text-decoration: none;
                        white-space: nowrap;
                        transition: opacity 0.15s, box-shadow 0.15s;
                        box-shadow: 0 2px 8px var(--btn-primary-shadow);
                    " onmouseover="this.style.opacity='0.88';this.style.boxShadow='0 4px 14px var(--btn-primary-shadow-hover)'" onmouseout="this.style.opacity='1';this.style.boxShadow='0 2px 8px var(--btn-primary-shadow)'">
                        Enroll Now →
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card purple">
                    <div class="stat-header"><div class="stat-icon">🏫</div></div>
                    <div class="stat-label">Enrollment Status</div>
                    <div class="stat-value" id="enrollmentStatus">Pending</div>
                </div>
                <div class="stat-card pink">
                    <div class="stat-header"><div class="stat-icon">📚</div></div>
                    <div class="stat-label">Enrolled Subjects</div>
                    <div class="stat-value" id="enrolledSubjects">0</div>
                </div>
                <div class="stat-card yellow">
                    <div class="stat-header"><div class="stat-icon">⏰</div></div>
                    <div class="stat-label">Total Units</div>
                    <div class="stat-value" id="totalUnits">0</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-header"><div class="stat-icon">📊</div></div>
                    <div class="stat-label">Current GPA</div>
                    <div class="stat-value" id="currentGPA">—</div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Weekly Schedule -->
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">📅 My Schedule</h2>
                        <a href="schedule.php" class="view-all-btn">Full View</a>
                    </div>
                    <!-- Day tabs -->
                    <div class="sched-tabs" id="schedTabs"></div>
                    <!-- Day panels -->
                    <div id="schedPanels">
                        <p style="text-align:center;color:var(--text-secondary);padding:2rem;">Loading schedule...</p>
                    </div>
                </div>

                <!-- Recent Announcements -->
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">Announcements</h2>
                        <a href="announcements.php" class="view-all-btn">View All</a>
                    </div>
                    <div id="announcements">
                        <p style="text-align:center;color:var(--text-secondary);padding:2rem;">Loading announcements...</p>
                    </div>
                </div>
            </div>

            <!-- Calendar -->
            <div class="content-card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">Academic Calendar</h2>
                </div>
                <div class="calendar-container">
                    <div class="calendar-header">
                        <button class="btn btn-secondary" onclick="changeMonth(-1)">← Previous</button>
                        <h3 id="currentMonth">February 2025</h3>
                        <button class="btn btn-secondary" onclick="changeMonth(1)">Next →</button>
                    </div>
                    <div class="calendar-grid" id="calendarGrid"></div>
                </div>
            </div>

            <!-- Enrollment Section -->
            <div class="enroll-section">
                <div class="enroll-card">
                    <div class="enroll-card-header">
                        <h2>📋 School Year Enrollment</h2>
                        <span style="font-size:0.78rem;color:var(--text-secondary);">SY 2025–2026</span>
                    </div>

                    <?php
                    // Fetch fresh student status from DB
                    $enroll_conn = getDBConnection();
                    $enroll_stmt = $enroll_conn->prepare("SELECT status, name, course, year_level FROM users WHERE id = ?");
                    $enroll_stmt->bind_param("i", $_SESSION['user_id']);
                    $enroll_stmt->execute();
                    $enroll_user = $enroll_stmt->get_result()->fetch_assoc();
                    $enroll_stmt->close();
                    $enroll_conn->close();
                    $student_status = strtolower($enroll_user['status'] ?? 'pending');
                    $is_returnee = in_array($student_status, ['active', 'enrolled', 'approved']);
                    ?>

                    <?php if ($is_returnee): ?>

                    <?php else: ?>
                    <!-- OPEN: New / pending / inactive student can submit enrollment -->
                    <div class="enroll-open-notice">
                        <span style="font-size:1.1rem;">✅</span>
                        <div><strong>Enrollment is open.</strong> Complete all 5 steps of the form below to submit your application.</div>
                    </div>
                    <iframe
                        src="../enrollment.html"
                        class="enroll-frame"
                        title="Student Enrollment Form"
                        loading="lazy"
                    ></iframe>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>

    <script>
        const DAYS = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        let todayName = '';

        async function loadUserData() {
            try {
                const response = await fetch('../../api/student/get_dashboard_data.php');
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('[Dashboard] API returned non-JSON (session expired or redirect?):', text.substring(0, 300));
                    return;
                }
                const data = await response.json();
                console.log('[Dashboard] API response:', JSON.stringify(data.stats));
                if (data.success) {
                    document.getElementById('userName').textContent = data.user.name;
                    const avatarEl = document.getElementById('userAvatar');
                    if (data.user.avatar_url) {
                        avatarEl.innerHTML = '<img src="' + data.user.avatar_url + '?t=' + Date.now() + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">';
                    } else {
                        avatarEl.textContent = (data.user.name || '?').charAt(0).toUpperCase();
                    }
                    document.getElementById('enrollmentStatus').textContent = data.stats.enrollment_status || 'Unknown';
                    document.getElementById('enrolledSubjects').textContent = (data.stats.enrolled_subjects !== undefined) ? data.stats.enrolled_subjects : '0';
                    document.getElementById('totalUnits').textContent       = (data.stats.total_units !== undefined)       ? data.stats.total_units       : '0';
                    document.getElementById('currentGPA').textContent       = data.stats.gpa || '—';
                    todayName = data.today || new Date().toLocaleDateString('en-US', {weekday:'long'});
                    buildScheduleWidget(data.all_schedule || data.schedule || []);
                    loadAnnouncements(data.announcements);

                    // Enrollment banner
                    const banner = document.getElementById('enrollmentBanner');
                    if (data.enrollment_open) {
                        const sem   = data.current_semester || 'current semester';
                        const year  = data.school_year      || '';
                        document.getElementById('enrollmentBannerSub').textContent =
                            'Enrollment for ' + sem + (year ? ' S.Y. ' + year : '') + ' is now open. Submit your enrollment application.';
                        banner.style.display = 'block';

                        // Build enrollment URL with student info pre-filled
                        const studentName = data.user.name || '';
                        const nameParts   = studentName.trim().split(/\s+/);
                        // Heuristic: Last word = surname, first word = firstname, rest = middle
                        const surname   = nameParts.length > 1 ? nameParts[nameParts.length - 1] : studentName;
                        const firstname = nameParts.length > 0 ? nameParts[0] : '';
                        const middlename = nameParts.length > 2 ? nameParts.slice(1, -1).join(' ') : '';

                        const params = new URLSearchParams({
                            autofill:   '1',
                            surname:    surname,
                            firstname:  firstname,
                            middlename: middlename,
                            email:      data.user.email      || '',
                            student_id: data.user.student_id || '',
                            course:     data.user.course     || '',
                            year_level: data.user.year_level || '',
                            semester:   sem,
                            ay:         year
                        });
                        const enrollBtn = document.getElementById('enrollNowBtn');
                        if (enrollBtn) {
                            enrollBtn.href = '../enrollment.html?' + params.toString();
                        }
                        // Also update iframe src if enrollment section is showing
                        const enrollFrame = document.querySelector('.enroll-frame');
                        if (enrollFrame) {
                            enrollFrame.src = '../enrollment.html?' + params.toString();
                        }
                    } else {
                        banner.style.display = 'none';
                    }
                } else {
                    console.error('[Dashboard] API error:', data.message);
                }
            } catch (error) {
                console.error('[Dashboard] Fetch error:', error);
            }
        }

        function buildScheduleWidget(allSchedule) {
            // Group by day
            const byDay = {};
            DAYS.forEach(d => byDay[d] = []);
            allSchedule.forEach(item => {
                if (byDay[item.day]) byDay[item.day].push(item);
            });

            // Only show days that have classes, always include today
            const daysWithClasses = DAYS.filter(d => byDay[d].length > 0 || d === todayName);

            const tabsEl   = document.getElementById('schedTabs');
            const panelsEl = document.getElementById('schedPanels');
            tabsEl.innerHTML   = '';
            panelsEl.innerHTML = '';

            if (daysWithClasses.length === 0) {
                panelsEl.innerHTML = '<div class="no-class-msg"><span class="nc-icon">📭</span>No schedule found. Make sure your study load is finalized.</div>';
                return;
            }

            daysWithClasses.forEach((day, idx) => {
                const isToday = day === todayName;
                const shortDay = day.substring(0, 3);

                // Tab
                const tab = document.createElement('button');
                tab.className = 'sched-tab' + (isToday ? ' today-tab' : '');
                tab.dataset.day = day;
                tab.innerHTML = shortDay + (isToday ? '<span class="today-dot"></span>' : '');
                tab.onclick = () => switchDay(day);
                tabsEl.appendChild(tab);

                // Panel
                const panel = document.createElement('div');
                panel.className = 'sched-day-panel';
                panel.id = 'panel-' + day;
                panel.innerHTML = renderDayClasses(byDay[day], day);
                panelsEl.appendChild(panel);
            });

            // Activate today or first day
            const activateDay = daysWithClasses.includes(todayName) ? todayName : daysWithClasses[0];
            switchDay(activateDay);
        }

        function switchDay(day) {
            document.querySelectorAll('.sched-tab').forEach(t => {
                t.classList.toggle('active', t.dataset.day === day);
            });
            document.querySelectorAll('.sched-day-panel').forEach(p => {
                p.classList.toggle('active', p.id === 'panel-' + day);
            });
        }

        function renderDayClasses(classes, day) {
            const isToday = day === todayName;
            if (!classes || classes.length === 0) {
                return `<div class="no-class-msg">
                    <span class="nc-icon">🎉</span>
                    No classes ${isToday ? 'today' : 'on ' + day}
                </div>`;
            }

            return classes.map(c => `
                <div class="sched-item">
                    <div class="sched-time-col">
                        <div class="sched-time-start">${esc(c.start_time)}</div>
                        <div class="sched-time-end">${esc(c.end_time)}</div>
                    </div>
                    <div class="sched-divider"></div>
                    <div class="sched-info">
                        <div class="sched-subj-code">${esc(c.subject_code)}</div>
                        <div class="sched-subj">${esc(c.subject_name)}</div>
                        <div class="sched-meta">
                            <span>👨‍🏫 ${esc(c.teacher_name)}</span>
                            <span>📍 ${esc(c.room)}</span>
                            ${c.section && c.section !== 'TBA' ? `<span>🏷️ ${esc(c.section)}</span>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function loadAnnouncements(announcements) {
            const container = document.getElementById('announcements');

            if (!announcements || announcements.length === 0) {
                container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:2rem;">No announcements</p>';
                return;
            }

            let html = '<div style="display:flex;flex-direction:column;gap:1rem;">';
            announcements.forEach(item => {
                html += `
                    <div style="padding:1rem;background:var(--background-main);border-radius:var(--radius-md);">
                        <div style="font-weight:700;color:var(--text-primary);margin-bottom:0.25rem;">${esc(item.title)}</div>
                        <div style="font-size:0.875rem;color:var(--text-secondary);margin-bottom:0.5rem;">${esc(item.content)}</div>
                        <div style="font-size:0.75rem;color:var(--text-light);">${esc(item.date)}</div>
                    </div>`;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        function esc(str) {
            return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        // Calendar
        let currentDate = new Date();

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            document.getElementById('currentMonth').textContent =
                currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const calendarGrid = document.getElementById('calendarGrid');
            let html = '';
            ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => {
                html += `<div style="text-align:center;font-weight:700;padding:0.5rem;color:var(--text-secondary);">${d}</div>`;
            });
            for (let i = 0; i < firstDay; i++) html += '<div></div>';
            const today = new Date();
            for (let day = 1; day <= daysInMonth; day++) {
                const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
                html += `<div class="calendar-day ${isToday ? 'active' : ''}">${day}</div>`;
            }
            calendarGrid.innerHTML = html;
        }

        function changeMonth(direction) {
            currentDate.setMonth(currentDate.getMonth() + direction);
            renderCalendar();
        }

        loadUserData();
        renderCalendar();
    </script>

    <script>
        (function() {
            var sidebar = document.querySelector('.sidebar');
            // Always reset scroll to top on dashboard (entry point after login)
            sessionStorage.removeItem('sidebarScroll');
            sidebar.scrollTop = 0;
            // Save scroll position before navigating away
            document.querySelectorAll('.nav-item').forEach(function(link) {
                link.addEventListener('click', function() {
                    sessionStorage.setItem('sidebarScroll', sidebar.scrollTop);
                });
            });
        })();
    </script>
<?php include 'chatbot-widget.php'; ?>
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
