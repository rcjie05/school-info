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
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Teacher Dashboard</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        /* ── Announcement Feed ── */
        #announcementsList { display: flex; flex-direction: column; gap: 1rem; padding: 1.25rem; }

        .ann-card {
            background: var(--background-card, #fff);
            border-radius: 14px;
            border: 1px solid var(--border-color, #e8edf3);
            overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .ann-card:hover {
            box-shadow: 0 6px 24px rgba(0,0,0,0.08);
            transform: translateY(-1px);
        }

        /* priority accent bar */
        .ann-card.priority-high   { border-top: 3px solid #ef4444; }
        .ann-card.priority-medium { border-top: 3px solid #f59e0b; }
        .ann-card.priority-low    { border-top: 3px solid #94a3b8; }

        .ann-card-inner { padding: 1.25rem 1.5rem; }

        /* header row */
        .ann-header { display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.9rem; }
        .ann-avatar {
            width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.95rem; color: #fff;
            background: var(--primary-purple, #3D6B9F);
        }
        .ann-author-block { flex: 1; min-width: 0; }
        .ann-author-name { font-size: 0.875rem; font-weight: 600; color: var(--text-primary, #1C2C42); }
        .ann-date { font-size: 0.75rem; color: var(--text-secondary, #64748b); margin-top: 1px; }

        /* priority badge */
        .ann-priority {
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.25rem 0.65rem; border-radius: 20px;
            font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; flex-shrink: 0;
        }
        .ann-priority.high   { background: #fef2f2; color: #ef4444; }
        .ann-priority.medium { background: #fffbeb; color: #d97706; }
        .ann-priority.low    { background: #f1f5f9; color: #64748b; }

        /* body */
        .ann-title { font-size: 1.05rem; font-weight: 700; color: var(--text-primary, #1C2C42); margin: 0 0 0.5rem; line-height: 1.4; }
        .ann-content { font-size: 0.9rem; color: var(--text-secondary, #475569); line-height: 1.65; margin: 0; white-space: pre-wrap; }

        /* footer */
        .ann-footer { display: flex; align-items: center; gap: 0.6rem; margin-top: 1rem; padding-top: 0.85rem; border-top: 1px solid var(--border-color, #e8edf3); flex-wrap: wrap; }
        .ann-tag {
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.2rem 0.6rem; border-radius: 20px;
            background: var(--background-main, #f8fafc); border: 1px solid var(--border-color, #e8edf3);
            font-size: 0.72rem; font-weight: 600; color: var(--text-secondary, #64748b);
        }

        /* attachments */
        .ann-attachments { margin-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem; }

        /* image grid: 1 image = full width, 2+ = 2-col grid */
        .ann-img-grid { display: grid; gap: 4px; border-radius: 12px; overflow: hidden; }
        .ann-img-grid.count-1 { grid-template-columns: 1fr; }
        .ann-img-grid.count-2 { grid-template-columns: 1fr 1fr; }
        .ann-img-grid.count-3 { grid-template-columns: 1fr 1fr; }
        .ann-img-grid.count-3 .ann-img-item:first-child { grid-column: 1 / -1; }
        .ann-img-grid.count-4 { grid-template-columns: 1fr 1fr; }

        .ann-img-item { overflow: hidden; aspect-ratio: 16/10; background: #000; }
        .ann-img-grid.count-1 .ann-img-item { aspect-ratio: unset; max-height: 400px; }
        .ann-img-item img {
            width: 100%; height: 100%; object-fit: cover;
            cursor: zoom-in; display: block;
            transition: transform 0.2s;
        }
        .ann-img-grid.count-1 .ann-img-item img {
            height: auto; max-height: 400px; object-fit: contain; background: #111;
        }
        .ann-img-item img:hover { transform: scale(1.02); }

        .ann-video-wrap { border-radius: 12px; overflow: hidden; background: #000; }
        .ann-video-wrap video { width: 100%; max-height: 340px; display: block; }

        .ann-file-link {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 0.9rem; border-radius: 8px;
            background: var(--background-main, #f8fafc); border: 1px solid var(--border-color, #e2e8f0);
            text-decoration: none; color: var(--text-primary, #1C2C42); font-size: 0.82rem; font-weight: 500;
            transition: background 0.15s;
        }
        .ann-file-link:hover { background: var(--border-color, #e2e8f0); }

        /* empty / error states */
        .ann-empty {
            text-align: center; color: var(--text-secondary, #94a3b8);
            padding: 4rem 2rem; font-size: 0.95rem;
        }
        .ann-empty .ann-empty-icon { font-size: 2.5rem; margin-bottom: 0.75rem; display: block; }

        /* filter bar */
        .ann-filter-bar { display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem 0; }
        .ann-filter-bar select {
            padding: 0.45rem 0.85rem; border-radius: 8px;
            border: 1px solid var(--border-color, #e2e8f0);
            background: var(--background-card, #fff); color: var(--text-primary, #1C2C42);
            font-size: 0.85rem; cursor: pointer; outline: none;
        }
        .ann-filter-bar select:focus { border-color: var(--primary-purple, #3D6B9F); }
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
                <div class="ann-filter-bar">
                    <select id="targetFilter" onchange="loadAnnouncements()">
                        <option value="">All Relevant Announcements</option>
                        <option value="all">Everyone</option>
                        <option value="teachers">Teachers Only</option>
                    </select>
                </div>
                <div id="announcementsList"><div class="ann-empty"><span class="ann-empty-icon">📢</span>Loading announcements…</div></div>
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

            const images = attachments.filter(a => a.type === 'image');
            const videos = attachments.filter(a => a.type === 'video');
            const files  = attachments.filter(a => a.type !== 'image' && a.type !== 'video');

            let html = '<div class="ann-attachments">';

            // Image grid
            if (images.length > 0) {
                const cnt = Math.min(images.length, 4);
                html += `<div class="ann-img-grid count-${cnt}">`;
                images.slice(0, 4).forEach(att => {
                    const src = `../../uploads/announcements/${att.path.split('/').pop()}`;
                    html += `<div class="ann-img-item">
                        <img src="${src}" alt="${att.original_name}" onclick="openLightbox('${src}')">
                    </div>`;
                });
                html += '</div>';
            }

            // Videos
            videos.forEach(att => {
                const src = `../../uploads/announcements/${att.path.split('/').pop()}`;
                html += `<div class="ann-video-wrap"><video src="${src}" controls></video></div>`;
            });

            // File links
            files.forEach(att => {
                const src = `../../uploads/announcements/${att.path.split('/').pop()}`;
                html += `<a href="${src}" target="_blank" download="${att.original_name}" class="ann-file-link">
                    ${getFileIcon(att.original_name)} ${att.original_name}
                </a>`;
            });

            html += '</div>';
            return html;
        }

        function getInitials(name) {
            return (name || '?').split(' ').slice(0,2).map(w=>w[0]).join('').toUpperCase();
        }

        const AVATAR_COLORS = ['#3D6B9F','#7C3AED','#059669','#DC2626','#D97706','#2563EB','#DB2777'];
        function avatarColor(name) {
            let h = 0; for (let c of (name||'')) h = (h*31 + c.charCodeAt(0)) & 0xffffffff;
            return AVATAR_COLORS[Math.abs(h) % AVATAR_COLORS.length];
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

                const priorityDot = { high: '🔴', medium: '🟡', low: '⚪' };
                const priorityLabel = { high: 'Urgent', medium: 'Medium', low: 'Normal' };

                container.innerHTML = data.announcements.map(a => `
                    <div class="ann-card priority-${a.priority}">
                        <div class="ann-card-inner">
                            <div class="ann-header">
                                <div class="ann-avatar" style="background:${avatarColor(a.posted_by_name)}">${getInitials(a.posted_by_name)}</div>
                                <div class="ann-author-block">
                                    <div class="ann-author-name">${a.posted_by_name}</div>
                                    <div class="ann-date">📅 ${a.date}</div>
                                </div>
                                <span class="ann-priority ${a.priority}">${priorityDot[a.priority]||''} ${priorityLabel[a.priority]||a.priority}</span>
                            </div>
                            ${a.title ? `<h3 class="ann-title">${a.title}</h3>` : ''}
                            <p class="ann-content">${a.content}</p>
                            ${renderAttachments(a.attachments)}
                            <div class="ann-footer">
                                <span class="ann-tag">👥 ${a.target_audience}</span>
                            </div>
                        </div>
                    </div>
                `).join('');
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
