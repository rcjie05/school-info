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
    <title>Faculty Directory - Student Portal</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        .filter-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-bar input,
        .filter-bar select {
            padding: 0.65rem 1rem;
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            font-family: inherit;
            background: white;
            transition: border-color 0.2s;
        }
        .filter-bar input { flex: 1; min-width: 200px; }
        .filter-bar input:focus,
        .filter-bar select:focus { outline: none; border-color: var(--primary-purple); }

        .faculty-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
        }
        .faculty-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1.5px solid var(--border-color);
            transition: box-shadow 0.2s, border-color 0.2s, transform 0.15s;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            cursor: pointer;
        }
        .faculty-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--primary-purple);
            transform: translateY(-2px);
        }
        .faculty-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-purple), var(--secondary-pink));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }
        .faculty-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .faculty-name {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-primary);
            margin: 0;
        }
        .faculty-role {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-transform: capitalize;
        }
        .faculty-info {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        .faculty-info span {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .dept-badge {
            display: inline-block;
            padding: 0.2rem 0.65rem;
            background: rgba(91,78,155,0.1);
            color: var(--primary-purple);
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .view-profile-hint {
            font-size: 0.78rem;
            color: var(--primary-purple);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: 0.2rem;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
            grid-column: 1 / -1;
        }
        .empty-state span { font-size: 3rem; display: block; margin-bottom: 1rem; }
        .count-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        /* ---- Profile Modal ---- */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: white;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 580px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: modalIn 0.22s ease;
        }
        @keyframes modalIn {
            from { transform: translateY(18px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .modal-header {
            padding: 1.75rem 1.75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            border-bottom: 1.5px solid var(--border-color);
            position: relative;
        }
        .modal-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-purple), var(--secondary-pink));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.9rem;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--background-main);
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            transition: background 0.2s;
        }
        .modal-close:hover { background: var(--border-color); }
        .modal-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 0.25rem;
        }
        .modal-role-line {
            font-size: 0.82rem;
            color: var(--text-secondary);
            text-transform: capitalize;
        }
        .modal-body {
            padding: 1.5rem 1.75rem 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 1.4rem;
        }
        .modal-section-title {
            font-size: 0.73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--primary-purple);
            margin-bottom: 0.65rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .modal-info-row {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
            padding: 0.22rem 0;
        }
        .modal-info-row .icon { flex-shrink: 0; width: 1.1rem; text-align: center; }
        .specialty-item {
            background: var(--background-main);
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 0.65rem 0.9rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.45rem;
        }
        .subj-code { font-size: 0.85rem; font-weight: 600; color: var(--text-primary); }
        .subj-name { font-size: 0.78rem; color: var(--text-secondary); margin-top: 0.1rem; }
        .primary-badge {
            background: rgba(91,78,155,0.12);
            color: var(--primary-purple);
            font-size: 0.69rem;
            font-weight: 700;
            padding: 0.18rem 0.55rem;
            border-radius: 999px;
            white-space: nowrap;
        }
        .prof-level {
            font-size: 0.75rem;
            color: var(--text-secondary);
            white-space: nowrap;
        }
        .sched-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }
        .sched-table th {
            text-align: left;
            padding: 0.4rem 0.55rem;
            color: var(--text-secondary);
            font-weight: 600;
            border-bottom: 1.5px solid var(--border-color);
        }
        .sched-table td {
            padding: 0.45rem 0.55rem;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }
        .modal-empty {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-style: italic;
        }
        .modal-loading {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
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
                    <a href="calendar.php" class="nav-item"><span class="nav-icon">🗓️</span><span>Calendar</span></a>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                    <a href="faculty.php" class="nav-item active"><span class="nav-icon">👨‍🏫</span><span>Faculty Directory</span></a>
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
                    <h1>Faculty Directory</h1>
                    <p class="page-subtitle">Browse teachers and staff — click a card to view their profile</p>
                </div>
            </header>

            <div class="content-card">
                <div class="card-header">
                    <div class="filter-bar">
                        <input type="text" id="searchInput" placeholder="🔍 Search by name or email..." oninput="filterFaculty()">
                        <select id="deptFilter" onchange="filterFaculty()">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                </div>
                <div class="count-label" id="countLabel" style="padding: 0 1rem;"></div>
                <div class="faculty-grid" id="facultyGrid" style="padding: 1rem;">
                    <p style="text-align:center; color:var(--text-secondary);">Loading...</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Profile Modal -->
    <div class="modal-overlay" id="profileModal" onclick="handleOverlayClick(event)">
        <div class="modal-box">
            <div id="modalContent">
                <div class="modal-loading">Loading profile...</div>
            </div>
        </div>
    </div>

    <script>
        let allFaculty = [];

        async function loadFaculty() {
            const res = await fetch('../../api/student/get_faculty.php');
            const data = await res.json();
            if (!data.success) return;

            allFaculty = data.faculty;

            // Populate department filter
            const deptFilter = document.getElementById('deptFilter');
            data.departments.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d; opt.textContent = d;
                deptFilter.appendChild(opt);
            });

            renderFaculty(allFaculty);
        }

        function filterFaculty() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const dept = document.getElementById('deptFilter').value;
            const filtered = allFaculty.filter(f => {
                const matchSearch = !search ||
                    (f.name || '').toLowerCase().includes(search) ||
                    (f.email || '').toLowerCase().includes(search);
                const matchDept = !dept || f.department === dept;
                return matchSearch && matchDept;
            });
            renderFaculty(filtered);
        }

        function makeAvatar(url, name, cssClass) {
            const initials = (name || '?').split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
            if (url) {
                return `<div class="${cssClass}" style="background:none;padding:0;overflow:hidden;">
                    <img src="${url}" alt="${esc(name)}"
                         style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;"
                         onerror="this.style.display='none';this.parentNode.style.background='linear-gradient(135deg,var(--primary-purple),var(--secondary-pink))';this.parentNode.innerHTML='${initials}';">
                </div>`;
            }
            return `<div class="${cssClass}">${initials}</div>`;
        }

        function renderFaculty(list) {
            const grid = document.getElementById('facultyGrid');
            const count = document.getElementById('countLabel');
            count.textContent = `Showing ${list.length} faculty member${list.length !== 1 ? 's' : ''}`;

            if (list.length === 0) {
                grid.innerHTML = `<div class="empty-state"><span>👨‍🏫</span>No faculty members found.</div>`;
                return;
            }

            grid.innerHTML = list.map(f => {
                const dept = f.department ? `<span class="dept-badge">${esc(f.department)}</span>` : '';
                const email = f.email ? `<span>✉️ ${esc(f.email)}</span>` : '';
                const office = f.office_location ? `<span>📍 ${esc(f.office_location)}</span>` : '';
                const hours = f.office_hours ? `<span>🕐 ${esc(f.office_hours)}</span>` : '';
                return `
                    <div class="faculty-card" onclick="openProfile(${f.id})">
                        <div class="faculty-header">
                            ${makeAvatar(f.avatar_url, f.name, 'faculty-avatar')}
                            <div>
                                <p class="faculty-name">${esc(f.name || 'Unknown')}</p>
                                <span class="faculty-role">${esc(f.role || '')}</span>
                            </div>
                        </div>
                        ${dept}
                        <div class="faculty-info">
                            ${email}${office}${hours}
                        </div>
                        <div class="view-profile-hint">👁️ View Profile</div>
                    </div>`;
            }).join('');
        }

        function esc(str) {
            return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        async function openProfile(id) {
            document.getElementById('profileModal').classList.add('active');
            document.getElementById('modalContent').innerHTML = '<div class="modal-loading">⏳ Loading profile...</div>';

            try {
                const res = await fetch(`../../api/student/get_faculty_profile.php?id=${id}`);
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed to load');
                renderModal(data);
            } catch(e) {
                document.getElementById('modalContent').innerHTML = `<div class="modal-loading">❌ Could not load profile.</div>`;
            }
        }

        function renderModal(data) {
            const f = data.faculty;
            const roleIcon = f.role === 'teacher' ? '👨‍🏫' : '🗂️';

            // Contact info rows
            let contactRows = '';
            if (f.email) contactRows += `<div class="modal-info-row"><span class="icon">✉️</span><span>${esc(f.email)}</span></div>`;
            if (f.department) contactRows += `<div class="modal-info-row"><span class="icon">🏢</span><span>${esc(f.department)}</span></div>`;
            if (f.office_location) contactRows += `<div class="modal-info-row"><span class="icon">📍</span><span>${esc(f.office_location)}</span></div>`;
            if (f.office_hours) contactRows += `<div class="modal-info-row"><span class="icon">🕐</span><span>Office Hours: ${esc(f.office_hours)}</span></div>`;
            if (!contactRows) contactRows = '<p class="modal-empty">No contact info available.</p>';

            // Specialties (teachers only)
            let specialtiesHtml = '';
            if (f.role === 'teacher') {
                let specItems = '';
                if (data.specialties && data.specialties.length > 0) {
                    specItems = data.specialties.map(s => `
                        <div class="specialty-item">
                            <div>
                                <div class="subj-code">${esc(s.subject_code)} — ${esc(s.subject_name)}</div>
                                <div class="subj-name">${s.units ? s.units + ' units' : ''}${s.course ? ' · ' + esc(s.course) : ''}${s.year_level ? ' Yr ' + esc(s.year_level) : ''}</div>
                            </div>
                            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.25rem;">
                                ${s.is_primary == 1 ? '<span class="primary-badge">Primary</span>' : ''}
                                ${s.proficiency_level ? `<span class="prof-level">${esc(s.proficiency_level)}</span>` : ''}
                            </div>
                        </div>`).join('');
                } else {
                    specItems = '<p class="modal-empty">No specialties listed.</p>';
                }
                specialtiesHtml = `
                    <div>
                        <div class="modal-section-title">📖 Subject Specialties</div>
                        ${specItems}
                    </div>`;
            }

            // Classes/Schedule
            let schedHtml = '';
            if (data.classes && data.classes.length > 0) {
                const rows = data.classes.map(c => `
                    <tr>
                        <td>${esc(c.day_of_week)}</td>
                        <td>${esc(c.start_time)} – ${esc(c.end_time)}</td>
                        <td>${esc(c.subject_code)}<br><small style="color:var(--text-secondary)">${esc(c.subject_name)}</small></td>
                        <td>${esc(c.section_name)}</td>
                        <td>${esc(c.room || '—')}</td>
                    </tr>`).join('');
                schedHtml = `
                    <div>
                        <div class="modal-section-title">📅 Current Classes</div>
                        <div style="overflow-x:auto;">
                        <table class="sched-table">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Subject</th>
                                    <th>Section</th>
                                    <th>Room</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                        </div>
                    </div>`;
            } else {
                schedHtml = `
                    <div>
                        <div class="modal-section-title">📅 Current Classes</div>
                        <p class="modal-empty">No class schedule available.</p>
                    </div>`;
            }

            document.getElementById('modalContent').innerHTML = `
                <div class="modal-header">
                    ${makeAvatar(f.avatar_url, f.name, 'modal-avatar')}
                    <div>
                        <div class="modal-name">${esc(f.name || 'Unknown')}</div>
                        <div class="modal-role-line">${roleIcon} ${esc(f.role)}</div>
                    </div>
                    <button class="modal-close" onclick="closeProfile()">✕</button>
                </div>
                <div class="modal-body">
                    <div>
                        <div class="modal-section-title">📋 Contact Information</div>
                        ${contactRows}
                    </div>
                    ${specialtiesHtml}
                    ${schedHtml}
                </div>`;
        }

        function closeProfile() {
            document.getElementById('profileModal').classList.remove('active');
        }

        function handleOverlayClick(e) {
            if (e.target === document.getElementById('profileModal')) closeProfile();
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeProfile();
        });

        loadFaculty();
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
