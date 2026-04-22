<?php
require_once '../../php/config.php';
requireRole('teacher');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Teacher Dashboard</title>
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
                    <span>Teacher Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="schedule.php" class="nav-item"><span class="nav-icon">📅</span><span>My Schedule</span></a>
                    <a href="classes.php" class="nav-item"><span class="nav-icon">📚</span><span>My Classes</span></a>
                    <a href="specialties.php" class="nav-item"><span class="nav-icon">🎯</span><span>My Subjects</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Resources</div>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                    <a href="announcements.php" class="nav-item active"><span class="nav-icon">📢</span><span>Announcements</span></a>
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
                        <option value="teachers">Teachers Only</option>
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
                const response = await fetch(`../../api/teacher/get_announcements.php${params}`);
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
