<?php
require_once '../../php/config.php';
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
    <title>Announcements - Student Dashboard</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        /* ── FB-style feed ── */
        .fb-feed { max-width: 680px; margin: 0 auto; padding: 1rem; display: flex; flex-direction: column; gap: 1rem; }

        /* ── Filter bar ── */
        .fb-filter-bar { max-width: 680px; margin: 0 auto 0; padding: 0.75rem 1rem 0; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .fb-filter-select { padding: 0.4rem 0.8rem; border-radius: 20px; border: 1.5px solid var(--border-color, #e4e6eb); background: var(--background-card, #fff); font-size: 0.82rem; font-weight: 600; color: var(--text-primary, #050505); cursor: pointer; outline: none; }
        .fb-filter-select:focus { border-color: #1877f2; }

        /* ── Post card ── */
        .fb-post { background: var(--background-card, #fff); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.12); overflow: hidden; animation: postIn 0.25s ease; }
        @keyframes postIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        .fb-post-header { display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem 0.5rem; }
        .fb-post-avatar { width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1rem; flex-shrink: 0; }
        .fb-post-meta { flex: 1; min-width: 0; }
        .fb-post-author { font-weight: 700; font-size: 0.95rem; color: var(--text-primary, #050505); line-height: 1.2; }
        .fb-post-sub { display: flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; color: var(--text-secondary, #65676b); margin-top: 0.1rem; flex-wrap: wrap; }
        .fb-post-time { font-size: 0.78rem; color: var(--text-secondary, #65676b); }
        .fb-priority-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; display: inline-block; }
        .fb-audience-badge { display: inline-flex; align-items: center; gap: 0.2rem; font-size: 0.72rem; font-weight: 600; padding: 0.15rem 0.55rem; border-radius: 10px; }
        .fb-audience-badge.all      { background: #e7f3ff; color: #1877f2; }
        .fb-audience-badge.students { background: #e6f9f0; color: #0a7c42; }
        .fb-audience-badge.teachers { background: #fff3cd; color: #856404; }
        .fb-audience-badge.registrar{ background: #fde8ff; color: #8b2fc9; }
        .fb-audience-badge.admin    { background: #ffeaea; color: #c0392b; }
        .fb-post-title { font-weight: 700; font-size: 1rem; color: var(--text-primary, #050505); padding: 0.25rem 1.25rem 0; }
        .fb-post-content { padding: 0.5rem 1.25rem 0.75rem; font-size: 0.95rem; color: var(--text-primary, #1c1e21); line-height: 1.55; white-space: pre-wrap; word-break: break-word; }

        /* ── Media grid ── */
        .fb-media-grid { display: grid; gap: 2px; }
        .fb-media-grid.count-1 { grid-template-columns: 1fr; }
        .fb-media-grid.count-2 { grid-template-columns: 1fr 1fr; }
        .fb-media-grid.count-3 { grid-template-columns: 1fr 1fr; grid-template-rows: 240px 240px; }
        .fb-media-grid.count-3 .fb-media-item:first-child { grid-column: 1 / -1; }
        .fb-media-grid.count-4 { grid-template-columns: 1fr 1fr; }
        .fb-media-grid.count-many { grid-template-columns: 1fr 1fr; grid-template-rows: 240px 240px; }
        .fb-media-item { overflow: hidden; position: relative; background: #f0f2f5; cursor: pointer; }
        .fb-media-grid.count-1 .fb-media-item { max-height: 500px; }
        .fb-media-grid:not(.count-1) .fb-media-item { height: 240px; }
        .fb-media-item img, .fb-media-item video { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.2s; }
        .fb-media-grid.count-1 .fb-media-item img  { height: auto; max-height: 500px; object-fit: contain; }
        .fb-media-grid.count-1 .fb-media-item video { height: auto; max-height: 500px; object-fit: contain; }
        .fb-media-item:hover img, .fb-media-item:hover video { transform: scale(1.03); }
        .fb-media-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.6rem; font-weight: 700; }

        /* ── File attachments ── */
        .fb-file-attachments { padding: 0.5rem 1.25rem; display: flex; flex-direction: column; gap: 0.4rem; }
        .fb-file-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; background: var(--background-main, #f0f2f5); border-radius: 8px; text-decoration: none; color: var(--text-primary, #050505); font-size: 0.85rem; transition: background 0.15s; }
        .fb-file-item:hover { background: var(--border-color, #e4e6eb); }
        .fb-file-icon { width: 36px; height: 36px; border-radius: 8px; background: #1877f2; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
        .fb-file-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }

        /* ── Empty / error ── */
        .ann-empty { text-align: center; color: var(--text-secondary, #94a3b8); padding: 4rem 2rem; font-size: 0.95rem; }
        .ann-empty .ann-empty-icon { font-size: 2.5rem; margin-bottom: 0.75rem; display: block; }
    </style>
        </head>
<body>
    <div class="page-wrapper">
        <!-- Sidebar -->
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
                    <a href="faculty.php" class="nav-item"><span class="nav-icon">👨‍🏫</span><span>Faculty Directory</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Support</div>
                    <a href="announcements.php" class="nav-item active"><span class="nav-icon">📢</span><span>Announcements</span></a>
                    <a href="feedback.php" class="nav-item"><span class="nav-icon">💬</span><span>Feedback</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Account</div>
                    <a href="profile.php" class="nav-item"><span class="nav-icon">👤</span><span>My Profile</span></a>
                    <a href="../../php/logout.php" class="nav-item"><span class="nav-icon">🚪</span><span>Logout</span></a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="page-header">
                <div class="header-title">
                    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button>
                    <h1>Announcements</h1>
                    <p class="page-subtitle">Stay up to date with school announcements</p>
                </div>
            </header>

            <div class="content-card">
                <div class="fb-filter-bar">
                    <select class="fb-filter-select" id="targetFilter" onchange="loadAnnouncements()">
                        <option value="">All Relevant Announcements</option>
                        <option value="all">Everyone</option>
                        <option value="students">Students Only</option>
                    </select>
                </div>
                <div class="fb-feed" id="announcementsList"><div class="ann-empty"><span class="ann-empty-icon">📢</span>Loading announcements…</div></div>
            </div>
        </main>
    </div>

    <!-- Lightbox Modal for image preview -->
    <div id="imageLightbox" onclick="closeLightbox()" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; cursor:zoom-out;">
        <img id="lightboxImg" src="" alt="" style="max-width:90%; max-height:90vh; border-radius:8px; object-fit:contain; box-shadow:0 4px 32px rgba(0,0,0,0.5);">
    </div>

    <script>
        function openLightbox(src) {
            const lb = document.getElementById('imageLightbox');
            document.getElementById('lightboxImg').src = src;
            lb.style.display = 'flex';
        }
        function closeLightbox() {
            document.getElementById('imageLightbox').style.display = 'none';
            document.getElementById('lightboxImg').src = '';
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

        function getFileIcon(name) {
            const ext = name.split('.').pop().toLowerCase();
            if (ext === 'pdf') return '📄';
            if (['doc','docx'].includes(ext)) return '📝';
            if (['xls','xlsx'].includes(ext)) return '📊';
            if (['ppt','pptx'].includes(ext)) return '📊';
            if (['zip','rar'].includes(ext)) return '🗜️';
            return '📁';
        }

        function renderAttachments(attachments) {
            if (!attachments || attachments.length === 0) return '';
            const images = attachments.filter(a => a.type === 'image' || a.type === 'video');
            const files  = attachments.filter(a => a.type === 'file');
            let html = '';

            if (images.length > 0) {
                const countClass = images.length === 1 ? 'count-1' : images.length === 2 ? 'count-2' : images.length === 3 ? 'count-3' : images.length === 4 ? 'count-4' : 'count-many';
                const visible = images.slice(0, 4);
                const extra = images.length - 4;
                html += `<div class="fb-media-grid ${countClass}">`;
                visible.forEach((att, idx) => {
                    const src = `../../uploads/announcements/${att.path.split('/').pop()}`;
                    const isLast = idx === 3 && extra > 0;
                    if (att.type === 'video') {
                        html += `<div class="fb-media-item"><video src="${src}" onclick="event.stopPropagation()" controls></video>${isLast ? `<div class="fb-media-overlay">+${extra}</div>` : ''}</div>`;
                    } else {
                        html += `<div class="fb-media-item" onclick="openLightbox('${src}')"><img src="${src}" alt="${att.original_name}" loading="lazy">${isLast ? `<div class="fb-media-overlay">+${extra}</div>` : ''}</div>`;
                    }
                });
                html += '</div>';
            }

            if (files.length > 0) {
                html += '<div class="fb-file-attachments">';
                files.forEach(att => {
                    const src = `../../uploads/announcements/${att.path.split('/').pop()}`;
                    html += `<a class="fb-file-item" href="${src}" target="_blank" download="${att.original_name}">
                        <div class="fb-file-icon">${getFileIcon(att.original_name)}</div>
                        <span class="fb-file-name">${att.original_name}</span>
                        <span style="font-size:0.75rem;color:#65676b;">↓</span>
                    </a>`;
                });
                html += '</div>';
            }
            return html;
        }

        function getInitials(name) {
            return (name || '?').split(' ').slice(0,2).map(w=>w[0]).join('').toUpperCase();
        }

        const AVATAR_COLORS = ['#1877f2','#7C3AED','#059669','#DC2626','#D97706','#2563EB','#DB2777'];
        function avatarColor(name) {
            let h = 0; for (let c of (name||'')) h = (h*31 + c.charCodeAt(0)) & 0xffffffff;
            return AVATAR_COLORS[Math.abs(h) % AVATAR_COLORS.length];
        }

        function getPriorityDot(p) {
            const c = p==='high' ? '#e74c3c' : p==='medium' ? '#f39c12' : '#27ae60';
            return `<span class="fb-priority-dot" style="background:${c};" title="${p} priority"></span>`;
        }

        function getAudienceBadge(aud) {
            const map = { all:'👥 Everyone', students:'🎓 Students', teachers:'👨‍🏫 Teachers', registrar:'📋 Registrars', admin:'🔑 Admins' };
            return `<span class="fb-audience-badge ${aud}">${map[aud] || aud}</span>`;
        }

        async function loadAnnouncements() {
            const target = document.getElementById('targetFilter').value;
            const params = target ? `?target=${target}` : '';
            const container = document.getElementById('announcementsList');
            container.innerHTML = '<div class="ann-empty"><span class="ann-empty-icon">⏳</span>Loading…</div>';

            try {
                const response = await fetch(`../../api/student/get_announcements.php${params}`);
                const data = await response.json();

                if (!data.success) {
                    container.innerHTML = `<div class="ann-empty"><span class="ann-empty-icon">⚠️</span>${data.message}</div>`;
                    return;
                }
                if (data.announcements.length === 0) {
                    container.innerHTML = '<div class="ann-empty"><span class="ann-empty-icon">📭</span>No announcements found.</div>';
                    return;
                }

                let html = '';
                data.announcements.forEach(a => {
                    const initials = getInitials(a.posted_by_name);
                    const mediaHtml = renderAttachments(a.attachments);
                    html += `
                    <div class="fb-post">
                        <div class="fb-post-header">
                            <div class="fb-post-avatar" style="background:${avatarColor(a.posted_by_name)}">${initials}</div>
                            <div class="fb-post-meta">
                                <div class="fb-post-author">${a.posted_by_name}</div>
                                <div class="fb-post-sub">
                                    ${getPriorityDot(a.priority)}
                                    <span class="fb-post-time">${a.date}</span>
                                    <span>·</span>
                                    ${getAudienceBadge(a.target_audience)}
                                </div>
                            </div>
                        </div>
                        ${a.title ? `<div class="fb-post-title">${a.title}</div>` : ''}
                        <div class="fb-post-content">${a.content}</div>
                        ${mediaHtml}
                    </div>`;
                });
                container.innerHTML = html;
            } catch (err) {
                container.innerHTML = '<div class="ann-empty"><span class="ann-empty-icon">⚠️</span>Failed to load announcements. Please try again.</div>';
            }
        }

        loadAnnouncements();
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