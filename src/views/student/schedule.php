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
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        /* ── Timetable grid ── */
        .timetable-wrap { overflow-x: auto; }
        .timetable {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
            font-size: .85rem;
        }
        .timetable th {
            background: var(--primary-purple);
            color: var(--text-white);
            padding: .65rem .5rem;
            text-align: center;
            font-weight: 700;
            white-space: nowrap;
        }
        .timetable td {
            border: 1px solid var(--border-color);
            vertical-align: middle;
            padding: 0;
            height: 30px;
        }
        .timetable .time-col {
            background: var(--background-main);
            font-weight: 600;
            font-size: .72rem;
            color: var(--text-secondary);
            text-align: center;
            padding: .2rem .4rem;
            white-space: nowrap;
            width: 90px;
        }
        .timetable .empty-cell { background: var(--background-main); }

        .sched-cell {
            background: rgba(61,107,159,0.1);
            border-left: 3px solid var(--primary-purple);
            padding: .45rem .55rem;
            height: 100%;
            box-sizing: border-box;
            cursor: default;
        }
        .sched-cell .sc-subj { font-weight: 700; font-size: .8rem; color: var(--primary-purple-dark); }
        .sched-cell .sc-time { font-size: .72rem; color: var(--primary-purple); margin-top: .1rem; }
        .sched-cell .sc-room { font-size: .72rem; color: var(--secondary-blue); }
        .sched-cell .sc-tchr { font-size: .7rem;  color: var(--text-secondary); margin-top: .1rem; }

        /* day color variants */
        .sched-cell.mon { background: rgba(61,107,159,0.12); border-color:var(--primary-purple); }
        .sched-cell.tue { background: rgba(184,92,92,0.1); border-color:var(--secondary-pink); }
        .sched-cell.wed { background: rgba(90,158,138,0.12); border-color:var(--secondary-green); }
        .sched-cell.thu { background: rgba(212,169,106,0.15); border-color:var(--secondary-yellow); }
        .sched-cell.fri { background: rgba(91,141,184,0.12); border-color:var(--secondary-blue); }
        .sched-cell.sat { background: rgba(184,92,92,0.07); border-color:var(--secondary-pink); }
        .sched-cell.mon .sc-subj { color:var(--primary-purple-dark); }
        .sched-cell.tue .sc-subj { color:var(--secondary-pink); }
        .sched-cell.wed .sc-subj { color:var(--secondary-green); }
        .sched-cell.thu .sc-subj { color:var(--text-primary); }
        .sched-cell.fri .sc-subj { color:var(--primary-purple-dark); }
        .sched-cell.sat .sc-subj { color:var(--secondary-pink); }

        /* ── Class list cards ── */
        .class-card {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            background: var(--background-card);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--primary-purple);
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            margin-bottom: .75rem;
        }
        .class-card h3 { margin: 0 0 .3rem; font-size: 1rem; }
        .class-card p  { margin: .15rem 0; font-size: .83rem; color: var(--text-secondary); }
        .section-pill {
            background: var(--primary-purple);
            color: var(--text-white);
            padding: .3rem .85rem;
            border-radius: 1rem;
            font-weight: 700;
            font-size: .8rem;
            white-space: nowrap;
        }

        /* empty state */
        .empty-sched {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }
        .empty-sched .ei { font-size: 3rem; margin-bottom: .75rem; }
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
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="schedule.php" class="nav-item active"><span class="nav-icon">📅</span><span>My Schedule</span></a>
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
                    <h1>My Class Schedule</h1>
                <p class="page-subtitle" id="sectionLabel">Loading…</p>
            </div>
            <div class="header-actions">
                    <!-- Global Search -->
                    <button class="global-search-btn" id="globalSearchBtn" aria-label="Search" title="Search">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </button>

                <button class="btn btn-primary" onclick="window.print()">🖨️ Print</button>
            </div>
        </header>

        <!-- Timetable -->
        <div class="content-card" style="margin-bottom:1.5rem;">
            <div class="card-header">
                <h2 class="card-title">Weekly Timetable</h2>
            </div>
            <div class="timetable-wrap" style="padding:1rem;">
                <table class="timetable">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Monday</th>
                            <th>Tuesday</th>
                            <th>Wednesday</th>
                            <th>Thursday</th>
                            <th>Friday</th>
                            <th>Saturday</th>
                        </tr>
                    </thead>
                    <tbody id="timetableBody">
                        <tr><td colspan="7" style="text-align:center;padding:2rem;">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Class list -->
        <div class="content-card">
            <div class="card-header"><h2 class="card-title">Class List</h2></div>
            <div style="padding:1rem;" id="classList">
                <div style="text-align:center;padding:2rem;color:var(--text-secondary);">Loading…</div>
            </div>
        </div>
    </main>
</div>

<script>
const DAYS    = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const DAY_CSS = { Monday:'mon', Tuesday:'tue', Wednesday:'wed', Thursday:'thu', Friday:'fri', Saturday:'sat' };

// 30-minute slots from 7:00 AM to 9:00 PM (7.0 to 21.0, step 0.5)
const SLOTS = [];
for (let t = 7; t <= 21; t += 0.5) SLOTS.push(t);

function fmtSlot(t) {
    const h = Math.floor(t);
    const m = t % 1 === 0.5 ? '30' : '00';
    if (h === 0  && m === '00') return '12:00 AM';
    if (h === 12 && m === '00') return '12:00 PM';
    const period = h < 12 ? 'AM' : 'PM';
    const disp   = h <= 12 ? h : h - 12;
    return disp + ':' + m + ' ' + period;
}

// Convert "HH:MM" time string to decimal hour (e.g. "08:30" -> 8.5)
function timeToDecimal(str) {
    if (!str) return null;
    const parts = str.split(':');
    return parseInt(parts[0]) + (parseInt(parts[1]) >= 30 ? 0.5 : 0);
}

// Convert "HH:MM" to the slot key it belongs to (rounded down to nearest 0.5)
function timeToSlot(str) {
    if (!str) return null;
    const parts = str.split(':');
    const h = parseInt(parts[0]);
    const m = parseInt(parts[1]);
    return h + (m >= 30 ? 0.5 : 0);
}

async function loadSchedule() {
    try {
        const res  = await fetch('../../api/student/get_schedule.php');
        const data = await res.json();

        if (!data.success) {
            showEmpty();
            return;
        }

        renderTimetable(data.schedule || []);
        renderClassList(data.classes  || []);

    } catch (err) {
        console.error(err);
        showEmpty();
    }
}

/* ── Timetable grid ── */
function renderTimetable(schedule) {
    const tbody = document.getElementById('timetableBody');

    if (!schedule.length) {
        tbody.innerHTML = '<tr><td colspan="7"><div class="empty-sched"><div class="ei">📅</div><p>No schedule found.<br><small>Make sure your study load is assigned to a section with schedules set up.</small></p></div></td></tr>';
        return;
    }

    // Build lookup: day -> startSlot -> { ...class, rowspan }
    const lookup = {};
    const blockedCells = {}; // day -> Set of slot decimals that are "consumed" by a rowspan

    schedule.forEach(s => {
        if (!lookup[s.day]) lookup[s.day] = {};
        if (!blockedCells[s.day]) blockedCells[s.day] = new Set();
        const startSlot = s.start_time ? timeToSlot(s.start_time) : s.hour;
        const endSlot   = s.end_time   ? timeToSlot(s.end_time)   : (s.hour + 1);
        const rowspan   = Math.max(1, Math.round((endSlot - startSlot) / 0.5));
        lookup[s.day][startSlot] = { ...s, startSlot, endSlot, rowspan };
        // Mark all slots after the first as blocked (they'll be skipped in render)
        for (let t = startSlot + 0.5; t < endSlot; t = Math.round((t + 0.5) * 10) / 10) {
            blockedCells[s.day].add(t);
        }
    });

    // Format a decimal slot as 12h range label: "hh:mm-hh:mm" (e.g. "07:30-08:00", "12:30-01:00")
    function fmtRange(t) {
        function to12(h) { return h === 0 ? 12 : h > 12 ? h - 12 : h; }
        function pad(n)  { return String(n).padStart(2, '0'); }
        const h1 = Math.floor(t);
        const m1 = t % 1 === 0.5 ? 30 : 0;
        const t2 = Math.round((t + 0.5) * 10) / 10;
        const h2 = Math.floor(t2);
        const m2 = t2 % 1 === 0.5 ? 30 : 0;
        return pad(to12(h1)) + ':' + pad(m1) + '-' + pad(to12(h2)) + ':' + pad(m2);
    }

    let html = '';
    SLOTS.forEach(t => {
        html += '<tr>';
        html += '<td class="time-col">' + fmtRange(t) + '</td>';
        DAYS.forEach(day => {
            if (!blockedCells[day]) blockedCells[day] = new Set();
            if (blockedCells[day].has(t)) return; // skip — covered by a rowspan above

            const entry = lookup[day] && lookup[day][t] ? lookup[day][t] : null;
            if (entry) {
                const css = DAY_CSS[day] || '';
                html += '<td rowspan="' + entry.rowspan + '" style="padding:0;vertical-align:top;">' +
                    '<div class="sched-cell ' + css + '" style="height:100%;box-sizing:border-box;">' +
                    '  <div class="sc-subj">' + esc(entry.subject_code) + '</div>' +
                    '  <div class="sc-time">' + esc(entry.start_fmt) + ' – ' + esc(entry.end_fmt) + '</div>' +
                    (entry.room    ? '<div class="sc-room">🚪 ' + esc(entry.room)    + '</div>' : '') +
                    (entry.teacher ? '<div class="sc-tchr">👨‍🏫 ' + esc(entry.teacher) + '</div>' : '') +
                    '</div></td>';
            } else {
                html += '<td class="empty-cell"></td>';
            }
        });
        html += '</tr>';
    });
    tbody.innerHTML = html;
}

/* ── Class list ── */
function renderClassList(classes) {
    const el = document.getElementById('classList');

    if (!classes.length) {
        el.innerHTML = '<div class="empty-sched"><div class="ei">📚</div><p>No classes in your study load yet.</p></div>';
        return;
    }

    // Update header subtitle
    const sec = classes[0].section;
    if (sec && sec !== 'TBA') {
        document.getElementById('sectionLabel').textContent = 'Section: ' + sec;
    } else {
        document.getElementById('sectionLabel').textContent = '';
    }

    el.innerHTML = classes.map(c => {
        return '<div class="class-card">' +
            '<div style="flex:1;">' +
            '  <h3>' + esc(c.subject_name) + '</h3>' +
            '  <p><strong>Code:</strong> ' + esc(c.subject_code) + ' &nbsp;|&nbsp; <strong>Units:</strong> ' + esc(c.units) + '</p>' +
            '  <p><strong>Teacher:</strong> ' + esc(c.teacher_name) + '</p>' +
            (c.schedule ? '<p>📅 ' + esc(c.schedule) + '</p>' : '') +
            (c.room     ? '<p>🚪 ' + esc(c.room)     + '</p>' : '') +
            '</div>' +
            '<div style="flex-shrink:0;">' +
            '  <div class="section-pill">' + esc(c.section || 'TBA') + '</div>' +
            '</div>' +
            '</div>';
    }).join('');
}

function showEmpty() {
    document.getElementById('timetableBody').innerHTML =
        '<tr><td colspan="7"><div class="empty-sched"><div class="ei">⚠️</div><p>Could not load schedule. Please try again.</p></div></td></tr>';
    document.getElementById('classList').innerHTML = '';
}

function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
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