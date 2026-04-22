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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar & Reminders - Student Portal</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        /* ── Calendar Layout ── */
        .calendar-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 1.25rem;
            align-items: start;
        }

        /* ── Calendar Card ── */
        .calendar-card {
            background: var(--background-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1.5px solid var(--border-color);
            overflow: hidden;
        }

        .cal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1.5px solid var(--border-color);
            background: var(--background-sidebar);
        }

        .cal-nav-btn {
            background: rgba(255,255,255,0.1);
            border: 1.5px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            width: 36px;
            height: 36px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--text-white);
            transition: background 0.2s;
        }
        .cal-nav-btn:hover { background: var(--background-sidebar-hover); }

        .cal-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-white);
            letter-spacing: 0.02em;
        }

        .cal-today-btn {
            background: var(--secondary-yellow);
            border: none;
            border-radius: 6px;
            padding: 0.35rem 0.85rem;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-primary);
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .cal-today-btn:hover { opacity: 0.85; }

        /* ── Day of week row ── */
        .cal-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: var(--background-main);
            border-bottom: 1.5px solid var(--border-color);
        }
        .cal-weekday {
            text-align: center;
            padding: 0.6rem 0;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-secondary);
        }
        .cal-weekday.weekend { color: var(--secondary-pink); }

        /* ── Day grid ── */
        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0;
        }

        .cal-day {
            min-height: 90px;
            padding: 0.4rem;
            border-right: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background 0.15s;
            position: relative;
        }
        .cal-day:hover { background: var(--background-main); }
        .cal-day:nth-child(7n) { border-right: none; }

        .cal-day.other-month .day-num { color: var(--text-light); }
        .cal-day.other-month { background: var(--background-main); opacity: 0.7; }

        .cal-day.today .day-num {
            background: var(--primary-purple);
            color: var(--text-white);
            border-radius: 50%;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cal-day.selected {
            background: rgba(61,107,159,0.07);
            outline: 2px solid var(--primary-purple);
            outline-offset: -2px;
        }

        .day-num {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.3rem;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .day-weekend .day-num { color: var(--secondary-pink); }

        /* ── Reminder dots on calendar ── */
        .day-reminders {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .day-reminder-pill {
            font-size: 0.64rem;
            font-weight: 600;
            padding: 1px 5px;
            border-radius: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .pill-low    { background: rgba(61,107,159,0.15);  color: var(--primary-purple-dark); }
        .pill-medium { background: rgba(212,169,106,0.2);  color: #7a5a1e; }
        .pill-high   { background: rgba(184,92,92,0.15);   color: var(--secondary-pink); }

        .more-count {
            font-size: 0.62rem;
            color: var(--text-secondary);
            font-weight: 600;
            padding-left: 4px;
        }

        /* ── Sidebar Panel ── */
        .sidebar-panel {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .panel-card {
            background: var(--background-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1.5px solid var(--border-color);
            overflow: hidden;
        }

        .panel-header {
            padding: 1rem 1.25rem;
            border-bottom: 1.5px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .panel-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .panel-body { padding: 1rem 1.25rem; }

        /* ── Add Reminder Form ── */
        .form-group { margin-bottom: 0.85rem; }
        .form-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin-bottom: 0.35rem;
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.6rem 0.85rem;
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--background-card);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 3px rgba(61,107,159,0.1);
        }
        .form-textarea { resize: vertical; min-height: 60px; }

        .priority-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.4rem;
        }
        .priority-btn {
            padding: 0.45rem 0;
            border: 1.5px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            background: var(--background-card);
            color: var(--text-secondary);
        }
        .priority-btn.active.low    { background: rgba(61,107,159,0.12);  border-color: var(--primary-purple);  color: var(--primary-purple-dark); }
        .priority-btn.active.medium { background: rgba(212,169,106,0.18); border-color: var(--secondary-yellow); color: #7a5a1e; }
        .priority-btn.active.high   { background: rgba(184,92,92,0.12);   border-color: var(--secondary-pink);  color: var(--secondary-pink); }

        .btn-add {
            width: 100%;
            padding: 0.7rem;
            background: var(--primary-purple);
            color: var(--text-white);
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }
        .btn-add:hover { background: var(--primary-purple-dark); transform: translateY(-1px); }

        /* ── Selected Day Reminders ── */
        .selected-date-label {
            font-size: 0.78rem;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .reminder-item {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            padding: 0.7rem 0.85rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border: 1.5px solid transparent;
            transition: transform 0.15s;
        }
        .reminder-item:hover { transform: translateX(2px); }
        .reminder-item.low    { background: rgba(61,107,159,0.07);  border-color: rgba(61,107,159,0.2); }
        .reminder-item.medium { background: rgba(212,169,106,0.1);  border-color: rgba(212,169,106,0.35); }
        .reminder-item.high   { background: rgba(184,92,92,0.07);   border-color: rgba(184,92,92,0.2); }

        .reminder-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 4px;
        }
        .low    .reminder-dot { background: var(--primary-purple); }
        .medium .reminder-dot { background: var(--secondary-yellow); }
        .high   .reminder-dot { background: var(--secondary-pink); }

        .reminder-content { flex: 1; min-width: 0; }
        .reminder-title-text {
            font-size: 0.83rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.1rem;
        }
        .reminder-note {
            font-size: 0.75rem;
            color: var(--text-secondary);
            line-height: 1.4;
        }
        .reminder-time {
            font-size: 0.72rem;
            color: var(--text-light);
            white-space: nowrap;
            margin-top: 0.15rem;
        }

        .reminder-delete {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-light);
            font-size: 1rem;
            padding: 0;
            flex-shrink: 0;
            transition: color 0.2s;
        }
        .reminder-delete:hover { color: var(--secondary-pink); }

        .no-reminders {
            text-align: center;
            padding: 1.5rem 0;
            color: var(--text-light);
            font-size: 0.82rem;
        }
        .no-reminders span { font-size: 1.8rem; display: block; margin-bottom: 0.4rem; }

        /* ── Upcoming reminders ── */
        .upcoming-item {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.55rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        .upcoming-item:last-child { border-bottom: none; }

        .upcoming-date-badge {
            text-align: center;
            min-width: 38px;
            flex-shrink: 0;
        }
        .upcoming-day { font-size: 1.1rem; font-weight: 800; color: var(--primary-purple); line-height: 1; }
        .upcoming-mon { font-size: 0.62rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); }

        .upcoming-info { flex: 1; min-width: 0; }
        .upcoming-title { font-size: 0.82rem; font-weight: 700; color: var(--text-primary); }
        .upcoming-sub   { font-size: 0.72rem; color: var(--text-secondary); }

        .priority-badge {
            font-size: 0.62rem;
            font-weight: 700;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
        }
        .pb-low    { background: rgba(61,107,159,0.15);  color: var(--primary-purple-dark); }
        .pb-medium { background: rgba(212,169,106,0.2);  color: #7a5a1e; }
        .pb-high   { background: rgba(184,92,92,0.15);   color: var(--secondary-pink); }

        /* ── Toast notification ── */
        .toast {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            background: var(--background-sidebar);
            color: var(--text-white);
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            z-index: 9999;
            transform: translateY(80px);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow-lg);
        }
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.success { background: var(--secondary-green); }
        .toast.error   { background: var(--secondary-pink); }

        @media (max-width: 900px) {
            .calendar-layout { grid-template-columns: 1fr; }
        }
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
                    <a href="subjects.php" class="nav-item"><span class="nav-icon">📚</span><span>Study Load</span></a>
                    <a href="grades.php" class="nav-item"><span class="nav-icon">🎓</span><span>Grades</span></a>
                    <a href="calendar.php" class="nav-item active"><span class="nav-icon">🗓️</span><span>Calendar</span></a>
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
                    <h1>Calendar & Reminders</h1>
                <p class="page-subtitle">Keep track of your schedule and set personal reminders</p>
            </div>
        </header>

        <div class="calendar-layout">

            <!-- ── Main Calendar ── -->
            <div class="calendar-card">
                <div class="cal-header">
                    <button class="cal-nav-btn" onclick="changeMonth(-1)">‹</button>
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <span class="cal-title" id="calTitle"></span>
                        <button class="cal-today-btn" onclick="goToday()">Today</button>
                    </div>
                    <button class="cal-nav-btn" onclick="changeMonth(1)">›</button>
                </div>
                <div class="cal-weekdays">
                    <div class="cal-weekday weekend">Sun</div>
                    <div class="cal-weekday">Mon</div>
                    <div class="cal-weekday">Tue</div>
                    <div class="cal-weekday">Wed</div>
                    <div class="cal-weekday">Thu</div>
                    <div class="cal-weekday">Fri</div>
                    <div class="cal-weekday weekend">Sat</div>
                </div>
                <div class="cal-grid" id="calGrid"></div>
            </div>

            <!-- ── Right Sidebar ── -->
            <div class="sidebar-panel">

                <!-- Add Reminder -->
                <div class="panel-card">
                    <div class="panel-header">
                        <span class="panel-title">➕ Add Reminder</span>
                        <span id="formDateLabel" style="font-size:0.75rem;color:var(--text-secondary);font-weight:600;"></span>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-input" id="reminderDate">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Time (optional)</label>
                            <input type="time" class="form-input" id="reminderTime">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Title <span style="color:var(--primary-purple)">*</span></label>
                            <input type="text" class="form-input" id="reminderTitle" placeholder="e.g. Submit assignment">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Note (optional)</label>
                            <textarea class="form-textarea" id="reminderNote" placeholder="Additional details..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Priority</label>
                            <div class="priority-row">
                                <button class="priority-btn low active" onclick="setPriority('low', this)">🔵 Low</button>
                                <button class="priority-btn medium" onclick="setPriority('medium', this)">🟡 Medium</button>
                                <button class="priority-btn high" onclick="setPriority('high', this)">🔴 High</button>
                            </div>
                        </div>
                        <button class="btn-add" onclick="addReminder()">
                            <span>📌</span> Save Reminder
                        </button>
                    </div>
                </div>

                <!-- Selected Day's Reminders -->
                <div class="panel-card">
                    <div class="panel-header">
                        <span class="panel-title">📋 Day's Reminders</span>
                    </div>
                    <div class="panel-body">
                        <div class="selected-date-label" id="selectedDateLabel">Click a day to view reminders</div>
                        <div id="dayRemindersList"></div>
                    </div>
                </div>

                <!-- Upcoming Reminders -->
                <div class="panel-card">
                    <div class="panel-header">
                        <span class="panel-title">⏰ Upcoming (7 days)</span>
                    </div>
                    <div class="panel-body" id="upcomingList" style="padding-top:0.5rem;"></div>
                </div>

            </div>
        </div>
    </main>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
// ── State ──────────────────────────────────────────────────────────
const STORAGE_KEY = 'scc_reminders_v2';
let currentYear, currentMonth, selectedDate = null, selectedPriority = 'low';

const today = new Date();
today.setHours(0,0,0,0);

// ── Storage ────────────────────────────────────────────────────────
function loadReminders() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}; }
    catch { return {}; }
}
function saveReminders(data) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}
function getRemindersForDate(dateStr) {
    return (loadReminders()[dateStr] || []);
}

// ── Calendar render ────────────────────────────────────────────────
function renderCalendar() {
    const reminders = loadReminders();
    const firstDay = new Date(currentYear, currentMonth, 1);
    const lastDay  = new Date(currentYear, currentMonth + 1, 0);

    document.getElementById('calTitle').textContent =
        firstDay.toLocaleString('default', { month: 'long', year: 'numeric' });

    const grid = document.getElementById('calGrid');
    grid.innerHTML = '';

    // Padding before first day
    let startDow = firstDay.getDay(); // 0=Sun
    for (let i = 0; i < startDow; i++) {
        const prev = new Date(currentYear, currentMonth, -startDow + i + 1);
        grid.appendChild(makeDay(prev, true, reminders));
    }

    // Current month days
    for (let d = 1; d <= lastDay.getDate(); d++) {
        const date = new Date(currentYear, currentMonth, d);
        grid.appendChild(makeDay(date, false, reminders));
    }

    // Trailing days
    const total = startDow + lastDay.getDate();
    const trailing = total % 7 === 0 ? 0 : 7 - (total % 7);
    for (let i = 1; i <= trailing; i++) {
        const next = new Date(currentYear, currentMonth + 1, i);
        grid.appendChild(makeDay(next, true, reminders));
    }
}

function makeDay(date, otherMonth, reminders) {
    const dateStr = toDateStr(date);
    const dayReminders = reminders[dateStr] || [];
    const isToday = date.getTime() === today.getTime();
    const isSelected = selectedDate === dateStr;
    const dow = date.getDay();
    const isWeekend = dow === 0 || dow === 6;

    const cell = document.createElement('div');
    cell.className = 'cal-day'
        + (otherMonth ? ' other-month' : '')
        + (isToday ? ' today' : '')
        + (isSelected ? ' selected' : '')
        + (isWeekend && !otherMonth ? ' day-weekend' : '');
    cell.onclick = () => selectDay(dateStr, date);

    const numDiv = document.createElement('div');
    numDiv.className = 'day-num';
    numDiv.textContent = date.getDate();
    cell.appendChild(numDiv);

    if (dayReminders.length > 0) {
        const pillsWrap = document.createElement('div');
        pillsWrap.className = 'day-reminders';

        const showCount = 2;
        dayReminders.slice(0, showCount).forEach(r => {
            const pill = document.createElement('div');
            pill.className = `day-reminder-pill pill-${r.priority}`;
            pill.textContent = (r.time ? r.time + ' ' : '') + r.title;
            pillsWrap.appendChild(pill);
        });

        if (dayReminders.length > showCount) {
            const more = document.createElement('div');
            more.className = 'more-count';
            more.textContent = `+${dayReminders.length - showCount} more`;
            pillsWrap.appendChild(more);
        }
        cell.appendChild(pillsWrap);
    }

    return cell;
}

// ── Day selection ──────────────────────────────────────────────────
function selectDay(dateStr, date) {
    selectedDate = dateStr;

    // Set form date
    document.getElementById('reminderDate').value = dateStr;
    const friendly = date.toLocaleDateString('default', { weekday:'long', month:'long', day:'numeric' });
    document.getElementById('formDateLabel').textContent = friendly;
    document.getElementById('selectedDateLabel').textContent = friendly;

    renderCalendar();
    renderDayReminders();
}

function renderDayReminders() {
    const list = document.getElementById('dayRemindersList');
    if (!selectedDate) { list.innerHTML = ''; return; }

    const reminders = getRemindersForDate(selectedDate);
    if (reminders.length === 0) {
        list.innerHTML = '<div class="no-reminders"><span>📭</span>No reminders for this day</div>';
        return;
    }

    // Sort by time
    const sorted = [...reminders].sort((a,b) => (a.time||'99:99').localeCompare(b.time||'99:99'));

    list.innerHTML = sorted.map((r, i) => `
        <div class="reminder-item ${r.priority}">
            <div class="reminder-dot"></div>
            <div class="reminder-content">
                <div class="reminder-title-text">${esc(r.title)}</div>
                ${r.note ? `<div class="reminder-note">${esc(r.note)}</div>` : ''}
                ${r.time ? `<div class="reminder-time">🕐 ${r.time}</div>` : ''}
            </div>
            <button class="reminder-delete" onclick="deleteReminder('${selectedDate}', ${i})" title="Delete">✕</button>
        </div>`).join('');
}

// ── Upcoming reminders ─────────────────────────────────────────────
function renderUpcoming() {
    const reminders = loadReminders();
    const upcoming = [];

    for (let i = 0; i <= 7; i++) {
        const d = new Date(today);
        d.setDate(d.getDate() + i);
        const dateStr = toDateStr(d);
        (reminders[dateStr] || []).forEach(r => {
            upcoming.push({ ...r, date: d, dateStr });
        });
    }

    upcoming.sort((a,b) => {
        if (a.dateStr !== b.dateStr) return a.dateStr.localeCompare(b.dateStr);
        return (a.time||'99:99').localeCompare(b.time||'99:99');
    });

    const el = document.getElementById('upcomingList');
    if (upcoming.length === 0) {
        el.innerHTML = '<div class="no-reminders"><span>✅</span>No upcoming reminders</div>';
        return;
    }

    const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    el.innerHTML = upcoming.map(r => `
        <div class="upcoming-item">
            <div class="upcoming-date-badge">
                <div class="upcoming-day">${r.date.getDate()}</div>
                <div class="upcoming-mon">${MONTHS[r.date.getMonth()]}</div>
            </div>
            <div class="upcoming-info">
                <div class="upcoming-title">${esc(r.title)}</div>
                <div class="upcoming-sub">${r.time ? '🕐 ' + r.time : 'All day'}</div>
            </div>
            <span class="priority-badge pb-${r.priority}">${r.priority}</span>
        </div>`).join('');
}

// ── Add / Delete reminders ─────────────────────────────────────────
function addReminder() {
    const dateVal  = document.getElementById('reminderDate').value;
    const timeVal  = document.getElementById('reminderTime').value;
    const titleVal = document.getElementById('reminderTitle').value.trim();
    const noteVal  = document.getElementById('reminderNote').value.trim();

    if (!dateVal) { showToast('⚠️ Please select a date', 'error'); return; }
    if (!titleVal) { showToast('⚠️ Please enter a title', 'error'); return; }

    const data = loadReminders();
    if (!data[dateVal]) data[dateVal] = [];

    data[dateVal].push({
        title: titleVal,
        note: noteVal,
        time: timeVal,
        priority: selectedPriority,
        createdAt: new Date().toISOString()
    });

    saveReminders(data);

    // Reset form (keep date)
    document.getElementById('reminderTitle').value = '';
    document.getElementById('reminderNote').value = '';
    document.getElementById('reminderTime').value = '';

    if (selectedDate === dateVal) renderDayReminders();
    renderCalendar();
    renderUpcoming();
    showToast('✅ Reminder saved!', 'success');
}

function deleteReminder(dateStr, index) {
    const data = loadReminders();
    if (data[dateStr]) {
        data[dateStr].splice(index, 1);
        if (data[dateStr].length === 0) delete data[dateStr];
        saveReminders(data);
        renderDayReminders();
        renderCalendar();
        renderUpcoming();
        showToast('🗑️ Reminder deleted', '');
    }
}

// ── Priority selection ─────────────────────────────────────────────
function setPriority(priority, btn) {
    selectedPriority = priority;
    document.querySelectorAll('.priority-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// ── Navigation ─────────────────────────────────────────────────────
function changeMonth(dir) {
    currentMonth += dir;
    if (currentMonth > 11) { currentMonth = 0; currentYear++; }
    if (currentMonth < 0)  { currentMonth = 11; currentYear--; }
    renderCalendar();
}

function goToday() {
    currentYear  = today.getFullYear();
    currentMonth = today.getMonth();
    const dateStr = toDateStr(today);
    selectDay(dateStr, new Date(today));
}

// ── Helpers ────────────────────────────────────────────────────────
function toDateStr(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2,'0');
    const d = String(date.getDate()).padStart(2,'0');
    return `${y}-${m}-${d}`;
}

function esc(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `toast ${type}`;
    void t.offsetWidth;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
}

// ── Init ───────────────────────────────────────────────────────────
currentYear  = today.getFullYear();
currentMonth = today.getMonth();
goToday();
renderUpcoming();

// Sidebar scroll memory
(function() {
    var sidebar = document.querySelector('.sidebar');
    var saved = sessionStorage.getItem('sidebarScroll');
    if (saved) sidebar.scrollTop = parseInt(saved);
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
