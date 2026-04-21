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
        .announcement-card {
            padding: 1.5rem;
            background: var(--background-main);
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }
        .priority-high { border-left: 4px solid var(--status-rejected); }
        .priority-medium { border-left: 4px solid var(--status-pending); }
        .priority-low { border-left: 4px solid var(--text-secondary); }
        .announcement-meta { display: flex; gap: 1rem; margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-secondary); flex-wrap: wrap; }
        .empty-state { text-align: center; color: var(--text-secondary); padding: 3rem; }
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
                <div class="card-header" style="display: flex; gap: 1rem; align-items: center;">
                    <select id="targetFilter" onchange="loadAnnouncements()" style="padding: 0.5rem; border-radius: var(--radius-md); border: 1px solid #ddd;">
                        <option value="">All Relevant Announcements</option>
                        <option value="all">Everyone</option>
                        <option value="teachers">Teachers Only</option>
                    </select>
                </div>
                <div id="announcementsList" style="padding: 1rem;">Loading...</div>
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
            const items = attachments.map(att => {
                if (att.type === 'image') {
                    const src = `../uploads/announcements/${att.path.split('/').pop()}`;
                    return `<div style="margin-top:0.5rem;">
                        <img src="${src}" alt="${att.original_name}"
                             onclick="openLightbox('${src}')"
                             style="max-width:100%; max-height:300px; border-radius:8px; object-fit:contain; background:#f3f4f6; cursor:zoom-in; display:block;">
                    </div>`;
                }
                if (att.type === 'video') {
                    const src = `../uploads/announcements/${att.path.split('/').pop()}`;
                    return `<div style="margin-top:0.5rem;"><video src="${src}" controls style="max-width:100%; max-height:300px; border-radius:8px;"></video></div>`;
                }
                const src = `../uploads/announcements/${att.path.split('/').pop()}`;
                return `<a href="${src}" target="_blank" download="${att.original_name}"
                           style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.5rem 1rem;background:var(--background-main);border:1px solid #ddd;border-radius:8px;text-decoration:none;color:var(--text-primary);font-size:0.875rem;margin-top:0.5rem;">
                    ${getFileIcon(att.original_name)} ${att.original_name}
                </a>`;
            }).join('');
            return `<div style="margin-top:0.75rem;">${items}</div>`;
        }

        async function loadAnnouncements() {
            const target = document.getElementById('targetFilter').value;
            const params = target ? `?target=${target}` : '';

            try {
                const response = await fetch(`../../api/teacher/get_announcements.php${params}`);
                const data = await response.json();

                const container = document.getElementById('announcementsList');

                if (!data.success) {
                    container.innerHTML = `<p class="empty-state">Error loading announcements: ${data.message}</p>`;
                    return;
                }

                if (data.announcements.length === 0) {
                    container.innerHTML = '<p class="empty-state">No announcements found.</p>';
                    return;
                }

                const priorityColor = p => p === 'high' ? 'var(--status-rejected)' : p === 'medium' ? 'var(--status-pending)' : 'var(--text-secondary)';

                container.innerHTML = data.announcements.map(a => `
                    <div class="announcement-card priority-${a.priority}">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                            <h3 style="margin: 0; font-size: 1.125rem;">${a.title}</h3>
                            <span style="color: ${priorityColor(a.priority)}; font-weight: 600; font-size: 0.8rem; white-space: nowrap; margin-left: 1rem;">${a.priority.toUpperCase()}</span>
                        </div>
                        <p style="margin: 0 0 0.75rem; color: var(--text-primary);">${a.content}</p>
                        <div class="announcement-meta">
                            <span>👤 ${a.posted_by_name}</span>
                            <span>📅 ${a.date}</span>
                            <span class="status-badge">${a.target_audience}</span>
                        </div>
                        ${renderAttachments(a.attachments)}
                    </div>
                `).join('');
            } catch (err) {
                document.getElementById('announcementsList').innerHTML = '<p class="empty-state">Failed to load announcements. Please try again.</p>';
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
