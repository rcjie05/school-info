<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireLogin();
if ($_SESSION['role'] !== 'student') { header('Location: ../php/logout.php'); exit(); }
$fullName = $_SESSION['name'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Map - Student</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/css/floor-styles.css">
    <style>
        body { background: var(--background-main) !important; padding: 0 !important; }
        .main-content { flex: 1; padding: 0; background: var(--background-main); }
        .floor-container { padding: 20px; max-width: 100%; }
        
        
        .container.active { box-shadow: none; margin: 0; background: transparent; }

        .content {
            display: grid !important;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            padding: 24px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Hide admin-only controls */
        .controls { display: none !important; }

        .canvas-container {
            background: white !important;
            border-radius: 20px !important;
            padding: 24px !important;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1) !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 20px;
        }
        .canvas-wrapper { position: relative; }

        #floorPlan {
            border: 3px solid var(--border-color) !important;
            border-radius: 16px !important;
            display: block !important;
            margin: 0 auto !important;
            visibility: visible !important;
            background: white;
            max-width: 100%;
        }

        .zoom-controls {
            position: absolute; top: 20px; right: 20px;
            display: flex; flex-direction: column; gap: 8px; z-index: 10;
        }
        .zoom-btn {
            width: 40px; height: 40px;
            border: 2px solid var(--border-color); background: var(--background-card);
            border-radius: 8px; font-size: 20px; cursor: pointer;
            transition: all 0.2s;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; color: var(--text-secondary);
        }
        .zoom-btn:hover { background: var(--background-main); border-color: var(--primary-purple); color: var(--primary-purple); transform: scale(1.05); }

        .legend {
            background: var(--background-main); padding: 16px 20px;
            border-radius: 12px; border: 1px solid var(--border-color);
            display: flex; flex-wrap: wrap; gap: 20px; align-items: center;
        }
        .legend h4 { margin: 0; font-size: 14px; font-weight: 600; color: var(--text-primary); }
        .legend-items { display: flex; flex-wrap: wrap; gap: 16px; flex: 1; }
        .legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); }
        .legend-color { width: 20px; height: 20px; border-radius: 4px; border: 2px solid rgba(0,0,0,0.1); flex-shrink: 0; }
        .legend-item span { font-weight: 500; white-space: nowrap; }

        /* My Classes panel integration */
        .my-classes-section {
            background: var(--background-main);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 16px 20px;
        }
        .my-classes-section h4 {
            margin: 0 0 12px;
            font-size: 14px; font-weight: 700; color: var(--text-primary);
        }
        .class-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px; margin: 3px;
            background: var(--background-card); border: 1.5px solid var(--border-color);
            border-radius: 999px; font-size: 12px; font-weight: 600;
            color: var(--text-primary); cursor: pointer; transition: all 0.15s;
        }
        .class-chip:hover { border-color: var(--primary-purple, #5b4e9b); color: var(--primary-purple, #5b4e9b); background: rgba(61,107,159,0.08); }
        .class-chip.active { border-color: #ef4444; color: #ef4444; background: rgba(239,68,68,0.08); box-shadow: 0 0 0 3px rgba(239,68,68,0.15); }
        .class-chip.active .dot { background: #ef4444; }
        .class-chip .dot { width:8px;height:8px;border-radius:50%;background:var(--secondary-green);flex-shrink:0; }
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
                    <a href="schedule.php" class="nav-item"><span class="nav-icon">📅</span><span>My Schedule</span></a>
                    <a href="subjects.php" class="nav-item"><span class="nav-icon">📚</span><span>Study Load</span></a>
                    <a href="grades.php" class="nav-item"><span class="nav-icon">🎓</span><span>Grades</span></a>
                    <a href="calendar.php" class="nav-item"><span class="nav-icon">🗓️</span><span>Calendar</span></a>
                    <a href="floorplan.php" class="nav-item active"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
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
        <div class="floor-header">
            <div class="floor-header-row"><button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button><h1>🗺️ Campus Navigation Map</h1></div>
            <p style="margin: 5px 0 0 0; color: var(--text-secondary);">Browse available routes to help you navigate the campus</p>
        </div>

        <div class="floor-container">
            <div class="container active" id="mainApp">
                <div class="content" id="mainContent">

                    <!-- Canvas (LEFT) -->
                    <div class="canvas-container">
                        <div class="canvas-wrapper">
                            <canvas id="floorPlan" width="900" height="700"></canvas>
                            <div class="zoom-controls">
                                <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">+</button>
                                <button class="zoom-btn" onclick="resetZoom()" title="Reset Zoom">⊙</button>
                                <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">−</button>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT PANEL: Routes + Legend + My Classes -->
                    <div class="right-panel">

                        <!-- Routes -->
                        <div class="saved-routes" id="studentRouteSelector">
                            <div class="control-section">
                                <h3>📚 Available Routes</h3>
                                <input type="text" class="input-field" id="studentRouteSearch"
                                    placeholder="🔍 Search routes..."
                                    oninput="filterStudentRoutes()"
                                    style="margin-bottom: 15px;">
                                <p style="color: var(--gray-600); font-size: 0.95em; margin-bottom: 15px;">
                                    Click on any route below to display it on the map
                                </p>
                            </div>
                            <div id="studentRoutesList">
                                <div class="empty-state">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                    </svg>
                                    <p><strong>No routes available</strong></p>
                                    <p style="font-size:0.9em;margin-top:5px;">Check back later for available routes</p>
                                </div>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="legend">
                            <h4>🗺️ Legend</h4>
                            <div class="legend-items">
                                <div class="legend-item"><div class="legend-color" style="background:#F4D03F;"></div><span>Administrative</span></div>
                                <div class="legend-item"><div class="legend-color" style="background:#85C1E2;"></div><span>Classrooms</span></div>
                                <div class="legend-item"><div class="legend-color" style="background:#7DCEA0;"></div><span>Services</span></div>
                                <div class="legend-item"><div class="legend-color" style="background:#F1948A;"></div><span>Common Areas</span></div>
                                <div class="legend-item"><div class="legend-color" style="background:#FF6B6B;"></div><span>Route Path</span></div>
                                <div class="legend-item"><div class="legend-color" style="background:#4ECDC4;"></div><span>Waypoints</span></div>
                            </div>
                        </div>

                        <!-- My Classes quick-nav -->
                        <div class="my-classes-section" id="myClassesSection" style="display:none;">
                            <h4>📚 My Classes – Quick Navigate</h4>
                            <div id="myClassChips"></div>
                        </div>

                    </div><!-- end right-panel -->

                </div>
            </div>
        </div>
    </main>
</div>

<!-- Success message element required by floor-script.js -->
<div id="successMessage" style="
    position:fixed;bottom:80px;left:50%;transform:translateX(-50%);
    background:var(--secondary-green);color:var(--text-white);padding:10px 24px;border-radius:999px;
    font-weight:600;font-size:0.9rem;box-shadow:0 4px 12px rgba(0,0,0,0.15);
    opacity:0;transition:opacity 0.3s;pointer-events:none;z-index:9999;
"></div>
<!-- Stubs for admin elements floor-script.js may reference -->
<div id="routeInfo" style="display:none;"></div>
<div id="routeDetails" style="display:none;"></div>
<div id="waypointList" style="display:none;"></div>

<style>
#successMessage.show { opacity:1 !important; }
</style>

<script>
window.currentUserRole = 'student';
window.canEditRoutes   = false;
window.userFullName    = '<?php echo htmlspecialchars($fullName); ?>';
</script>
<script src="../../../public/js/floor-script.js?v=<?php echo time(); ?>"></script>

<script>
// ── Load student's own classes and show quick-nav chips ───────────────────────
(async function loadMyClassChips() {
    try {
        const res  = await fetch('../../api/student/get_schedule.php');
        const data = await res.json();
        if (!data.success || !data.classes || data.classes.length === 0) return;

        // Build unique subject+room list
        const seen = new Set();
        const chips = [];
        data.classes.forEach(cls => {
            const key = cls.subject_code + '|' + (cls.room || '');
            if (!seen.has(key)) {
                seen.add(key);
                chips.push({ code: cls.subject_code, name: cls.subject_name, room: cls.room || 'TBA' });
            }
        });

        if (chips.length === 0) return;

        const chipsEl = document.getElementById('myClassChips');
        chipsEl.innerHTML = chips.map(c => `
            <span class="class-chip" onclick="findRoomOnMap('${c.room.replace(/'/g,"\\'")}')">
                <span class="dot"></span>
                <strong>${c.code}</strong> – ${c.room}
            </span>
        `).join('');

        document.getElementById('myClassesSection').style.display = 'block';
    } catch(e) { /* silent fail */ }
})();

// Pin a room on the canvas when a class chip is clicked
function findRoomOnMap(roomName) {
    if (!roomName || roomName === 'TBA') return;

    const match = (typeof rooms !== 'undefined' ? rooms : []).find(r =>
        r.name.toLowerCase() === roomName.toLowerCase() ||
        r.name.toLowerCase().includes(roomName.toLowerCase()) ||
        roomName.toLowerCase().includes(r.name.toLowerCase())
    );

    if (!match) {
        const msg = document.getElementById('successMessage');
        if (msg) { msg.textContent = '\uD83D\uDD0D Room not found: ' + roomName; msg.classList.add('show'); setTimeout(() => msg.classList.remove('show'), 2500); }
        return;
    }

    // If same room clicked again — unpin
    if (typeof pinnedRoom !== 'undefined' && pinnedRoom && pinnedRoom.name === match.name) {
        unpinRoom();
        return;
    }

    // Pin the room (drop animation + glow)
    if (typeof pinRoom === 'function') pinRoom(match);

    // Highlight the active chip
    document.querySelectorAll('.class-chip').forEach(el => el.classList.remove('active'));
    const clicked = [...document.querySelectorAll('.class-chip')].find(el => el.textContent.includes(roomName));
    if (clicked) clicked.classList.add('active');
}

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

</body>
</html>
