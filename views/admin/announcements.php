<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="../../manifest.json">
    <meta name="theme-color" content="#1E3352">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($school_name) ?> Portal">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <title>Announcements - Admin Dashboard</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        /* ── FB-style feed layout ── */
        .fb-feed { max-width: 680px; margin: 0 auto; padding: 1rem; display: flex; flex-direction: column; gap: 1rem; }

        /* ── Composer box (create post) ── */
        .fb-composer { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.12); padding: 1rem 1.25rem; }
        .fb-composer-top { display: flex; align-items: center; gap: 0.75rem; }
        .fb-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #1877f2, #42b0ff); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1rem; flex-shrink: 0; overflow: hidden; }
        .fb-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .fb-composer-btn { flex: 1; background: #f0f2f5; border: none; border-radius: 20px; padding: 0.6rem 1rem; text-align: left; color: #65676b; font-size: 0.95rem; cursor: pointer; transition: background 0.15s; }
        .fb-composer-btn:hover { background: #e4e6eb; }
        .fb-composer-actions { display: flex; border-top: 1px solid #e4e6eb; margin-top: 0.75rem; padding-top: 0.5rem; gap: 0.25rem; }
        .fb-composer-action { flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.45rem; border-radius: 8px; border: none; background: none; cursor: pointer; font-size: 0.82rem; font-weight: 600; color: #65676b; transition: background 0.15s; }
        .fb-composer-action:hover { background: #f0f2f5; }

        /* ── Post card ── */
        .fb-post { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.12); overflow: hidden; animation: postIn 0.25s ease; }
        @keyframes postIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        .fb-post-header { display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem 0.5rem; }
        .fb-post-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #1877f2, #42b0ff); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1rem; flex-shrink: 0; }
        .fb-post-meta { flex: 1; min-width: 0; }
        .fb-post-author { font-weight: 700; font-size: 0.95rem; color: #050505; line-height: 1.2; }
        .fb-post-sub { display: flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; color: #65676b; margin-top: 0.1rem; flex-wrap: wrap; }
        .fb-post-time { font-size: 0.78rem; color: #65676b; }
        .fb-audience-badge { display: inline-flex; align-items: center; gap: 0.2rem; font-size: 0.72rem; font-weight: 600; padding: 0.15rem 0.55rem; border-radius: 10px; }
        .fb-audience-badge.all     { background: #e7f3ff; color: #1877f2; }
        .fb-audience-badge.students { background: #e6f9f0; color: #0a7c42; }
        .fb-audience-badge.teachers { background: #fff3cd; color: #856404; }
        .fb-audience-badge.registrar { background: #fde8ff; color: #8b2fc9; }
        .fb-audience-badge.admin   { background: #ffeaea; color: #c0392b; }
        .fb-priority-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .fb-post-menu { display: flex; gap: 0.25rem; }
        .fb-menu-btn { background: none; border: none; border-radius: 50%; width: 34px; height: 34px; cursor: pointer; font-size: 0.8rem; color: #65676b; display: flex; align-items: center; justify-content: center; transition: background 0.15s; }
        .fb-menu-btn:hover { background: #f0f2f5; }
        .fb-post-title { font-weight: 700; font-size: 1rem; color: #050505; padding: 0.25rem 1.25rem 0; }
        .fb-post-content { padding: 0.5rem 1.25rem 0.75rem; font-size: 0.95rem; color: #1c1e21; line-height: 1.55; white-space: pre-wrap; word-break: break-word; }
        /* image grid like Facebook */
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
        .fb-media-item:hover img, .fb-media-item:hover video { transform: scale(1.03); }
        .fb-media-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.6rem; font-weight: 700; }
        .fb-file-attachments { padding: 0.5rem 1.25rem; display: flex; flex-direction: column; gap: 0.4rem; }
        .fb-file-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; background: #f0f2f5; border-radius: 8px; text-decoration: none; color: #050505; font-size: 0.85rem; transition: background 0.15s; }
        .fb-file-item:hover { background: #e4e6eb; }
        .fb-file-icon { width: 36px; height: 36px; border-radius: 8px; background: #1877f2; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
        .fb-file-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }
        /* action bar */


        /* ── Filter bar ── */
        .fb-filter-bar { max-width: 680px; margin: 0 auto 0; padding: 0 1rem 0.5rem; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .fb-filter-select { padding: 0.4rem 0.8rem; border-radius: 20px; border: 1.5px solid #e4e6eb; background: #fff; font-size: 0.82rem; font-weight: 600; color: #050505; cursor: pointer; }

        /* ── Modal ── */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; max-width: 600px; width: 94%; max-height: 92vh; overflow-y: auto; box-shadow: 0 8px 40px rgba(0,0,0,0.25); }
        .modal-header-fb { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid #e4e6eb; }
        .modal-header-fb h2 { font-size: 1.15rem; font-weight: 700; color: #050505; margin: 0; }
        .modal-close-x { background: #e4e6eb; border: none; border-radius: 50%; width: 34px; height: 34px; font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #050505; transition: background 0.15s; }
        .modal-close-x:hover { background: #ccd0d5; }
        .modal-body-fb { padding: 1.25rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.85rem; color: #050505; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.65rem 0.85rem; border: 1.5px solid #ccd0d5; border-radius: 8px; font-size: 0.9rem; color: #050505; transition: border-color 0.15s; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #1877f2; box-shadow: 0 0 0 3px rgba(24,119,242,0.15); }
        .fb-submit-btn { width: 100%; padding: 0.75rem; border-radius: 8px; border: none; background: #1877f2; color: white; font-size: 1rem; font-weight: 700; cursor: pointer; margin-top: 0.75rem; transition: background 0.15s; }
        .fb-submit-btn:hover { background: #166fe5; }
        .fb-cancel-btn { width: 100%; padding: 0.65rem; border-radius: 8px; border: none; background: #e4e6eb; color: #050505; font-size: 0.9rem; font-weight: 600; cursor: pointer; margin-top: 0.5rem; transition: background 0.15s; }
        .fb-cancel-btn:hover { background: #ccd0d5; }
        /* ── Upload Drop Zone ── */
        .upload-zone { border: 2px dashed #d1d5db; border-radius: 12px; padding: 2rem 1.5rem; text-align: center; cursor: pointer; transition: all 0.2s ease; background: #fafafa; position: relative; }
        .upload-zone:hover { border-color: #6366f1; background: #f5f3ff; }
        .upload-zone.dragover { border-color: #6366f1; background: #ede9fe; transform: scale(1.01); }
        .upload-zone input[type="file"] { display: none; }
        .upload-zone-icon { width: 52px; height: 52px; margin: 0 auto 0.75rem; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
        .upload-zone-title { font-size: 0.95rem; font-weight: 600; color: #1f2937; margin-bottom: 0.25rem; }
        .upload-zone-sub { font-size: 0.78rem; color: #9ca3af; margin-bottom: 1rem; }
        .upload-type-btns { display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap; }
        .upload-type-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.85rem; border-radius: 20px; font-size: 0.75rem; font-weight: 500; border: 1.5px solid; cursor: pointer; transition: all 0.15s; background: white; }
        .upload-type-btn.img  { color: #0891b2; border-color: #bae6fd; }
        .upload-type-btn.img:hover  { background: #e0f2fe; }
        .upload-type-btn.vid  { color: #7c3aed; border-color: #ddd6fe; }
        .upload-type-btn.vid:hover  { background: #ede9fe; }
        .upload-type-btn.doc  { color: #059669; border-color: #a7f3d0; }
        .upload-type-btn.doc:hover  { background: #d1fae5; }
        /* ── File Preview Grid ── */
        .file-preview-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.6rem; margin-top: 0.75rem; }
        .fp-item { position: relative; border-radius: 10px; overflow: hidden; background: #f3f4f6; border: 1.5px solid #e5e7eb; transition: box-shadow 0.15s; animation: fpIn 0.2s ease; }
        @keyframes fpIn { from { opacity:0; transform:scale(0.85); } to { opacity:1; transform:scale(1); } }
        .fp-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
        .fp-thumb { width: 100%; height: 80px; object-fit: cover; display: block; }
        .fp-icon-wrap { width: 100%; height: 80px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.2rem; font-size: 1.8rem; }
        .fp-ext { font-size: 0.6rem; font-weight: 700; letter-spacing: 0.05em; color: #6b7280; text-transform: uppercase; }
        .fp-label { padding: 0.3rem 0.4rem; font-size: 0.65rem; color: #4b5563; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-top: 1px solid #e5e7eb; background: white; }
        .fp-size { font-size: 0.6rem; color: #9ca3af; }
        .fp-remove { position: absolute; top: 4px; right: 4px; background: rgba(17,17,17,0.65); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 0.7rem; cursor: pointer; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); transition: background 0.15s; line-height:1; }
        .fp-remove:hover { background: rgba(220,38,38,0.9); }
        /* ── Existing Attachments (edit mode) ── */
        .existing-att-list { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 0.75rem; }
        .existing-att-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; }
        .existing-att-thumb { width: 44px; height: 36px; border-radius: 5px; object-fit: cover; flex-shrink: 0; }
        .existing-att-icon { width: 44px; height: 36px; border-radius: 5px; background: #ede9fe; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .existing-att-name { flex: 1; font-size: 0.8rem; color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .existing-att-remove { background: none; border: 1px solid #fca5a5; color: #dc2626; border-radius: 6px; padding: 0.2rem 0.5rem; font-size: 0.72rem; cursor: pointer; transition: all 0.15s; white-space: nowrap; flex-shrink: 0; }
        .existing-att-remove:hover { background: #fef2f2; }
        /* ── Announcement attachment display ── */
        .announcement-attachment { margin-top: 0.75rem; display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .announcement-attachment img { max-width: 100%; max-height: 280px; border-radius: 10px; object-fit: contain; background: #f3f4f6; cursor: zoom-in; transition: opacity 0.15s; display: block; }
        .announcement-attachment img:hover { opacity: 0.9; }
        .announcement-attachment video { max-width: 100%; max-height: 280px; border-radius: 10px; display: block; }
        .announcement-attachment .file-link { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.45rem 1rem; background: white; border: 1.5px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; font-size: 0.82rem; transition: all 0.15s; }
        .announcement-attachment .file-link:hover { background: #f3f4f6; border-color: #d1d5db; }
    </style>
    <link rel="stylesheet" href="../../css/enhancements.css">
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
                    <span>Admin Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="users.php" class="nav-item"><span class="nav-icon">👥</span><span>User Management</span></a>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                    <a href="buildings.php" class="nav-item"><span class="nav-icon">🏢</span><span>Buildings & Rooms</span></a>
                    <a href="departments.php" class="nav-item"><span class="nav-icon">🏛️</span><span>Departments</span></a>
                    <a href="courses.php" class="nav-item"><span class="nav-icon">🎓</span><span>Courses</span></a>
                    <a href="faculty.php" class="nav-item"><span class="nav-icon">👨‍🏫</span><span>Faculty Directory</span></a>
                    <a href="grades.php" class="nav-item"><span class="nav-icon">📝</span><span>Grades</span></a>
                    <a href="subjects.php" class="nav-item"><span class="nav-icon">📚</span><span>Subjects</span></a>
                    <a href="sections.php" class="nav-item"><span class="nav-icon">📁</span><span>Sections</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <a href="announcements.php" class="nav-item active"><span class="nav-icon">📢</span><span>Announcements</span></a>
                    <a href="audit_logs.php" class="nav-item"><span class="nav-icon">📋</span><span>Audit Logs</span></a>
                    <a href="recycle_bin.php" class="nav-item"><span class="nav-icon">🗑️</span><span>Recycle Bin</span></a>
                    <a href="feedback.php" class="nav-item"><span class="nav-icon">💬</span><span>Feedback</span></a>
                    <a href="account_settings.php" class="nav-item"><span class="nav-icon">👤</span><span>Profile Settings</span></a>
                    <a href="settings.php" class="nav-item"><span class="nav-icon">⚙️</span><span>System Settings</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Account</div>
                    <a href="../../php/logout.php" class="nav-item"><span class="nav-icon">🚪</span><span>Logout</span></a>
                </div>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="page-header">
                <div class="header-title">
                    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button>
                    <h1>Announcements</h1>
                    <p class="page-subtitle">Post and manage system announcements</p>
                </div>
            </header>

            <!-- Filter bar -->
            <div class="fb-filter-bar">
                <select id="targetFilter" class="fb-filter-select" onchange="loadAnnouncements()">
                    <option value="">🌐 All Audiences</option>
                    <option value="all">👥 Everyone</option>
                    <option value="students">🎓 Students</option>
                    <option value="teachers">👨‍🏫 Teachers</option>
                    <option value="registrar">📋 Registrars</option>
                    <option value="admin">🔑 Admins</option>
                </select>
            </div>

            <!-- Composer box -->
            <div class="fb-feed" style="padding-bottom:0;">
                <div class="fb-composer">
                    <div class="fb-composer-top">
                        <div class="fb-avatar" id="composerAvatar">A</div>
                        <button class="fb-composer-btn" onclick="openAddModal()">What do you want to announce?</button>
                    </div>
                    <div class="fb-composer-actions">
                        <button class="fb-composer-action" onclick="openAddModal()" style="color:#f3425f;">
                            <span>🖼️</span> Photo/Video
                        </button>
                        <button class="fb-composer-action" onclick="openAddModal()" style="color:#f7b928;">
                            <span>😊</span> Feeling/Activity
                        </button>
                        <button class="fb-composer-action" onclick="openAddModal()" style="color:#45bd62;">
                            <span>📢</span> Announcement
                        </button>
                    </div>
                </div>
            </div>

            <!-- Feed -->
            <div class="fb-feed">
                <div id="announcementsTable">
                    <div style="text-align:center;padding:2rem;color:#65676b;">Loading...</div>
                </div>
            </div>
        </main>
    </div>

    <!-- Lightbox Modal for image preview -->
    <div id="imageLightbox" onclick="closeLightbox()" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:2000; align-items:center; justify-content:center; cursor:zoom-out;">
        <img id="lightboxImg" src="" alt="" style="max-width:90%; max-height:90vh; border-radius:8px; object-fit:contain; box-shadow:0 4px 32px rgba(0,0,0,0.5);">
    </div>

    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <div class="modal-header-fb">
                <h2 id="modalTitle">Create Announcement</h2>
                <button class="modal-close-x" onclick="closeModal()">✕</button>
            </div>
            <div class="modal-body-fb">
            <form id="announcementForm" onsubmit="saveAnnouncement(event)">
                <input type="hidden" id="announcementId">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" id="title" placeholder="Write a title..." required>
                </div>
                <div class="form-group">
                    <label>Content *</label>
                    <textarea id="content" rows="5" placeholder="What's on your mind?" required style="resize:vertical;"></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Audience *</label>
                        <select id="targetAudience" required>
                            <option value="all">👥 Everyone</option>
                            <option value="students">🎓 Students</option>
                            <option value="teachers">👨‍🏫 Teachers</option>
                            <option value="registrar">📋 Registrars</option>
                            <option value="admin">🔑 Admins</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Priority</label>
                        <select id="priority">
                            <option value="low">🟢 Low</option>
                            <option value="medium" selected>🟡 Medium</option>
                            <option value="high">🔴 High</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-top:1rem;">
                    <label>Attachments <span style="font-weight:400;color:#65676b;font-size:0.8rem;">optional</span></label>
                    <div id="existingAttachment"></div>
                    <input type="file" id="inputImage" accept="image/*" multiple style="display:none" onchange="handleFileSelect(event)">
                    <input type="file" id="inputVideo" accept="video/*" multiple style="display:none" onchange="handleFileSelect(event)">
                    <input type="file" id="inputDoc" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar" multiple style="display:none" onchange="handleFileSelect(event)">
                    <input type="file" id="attachmentInput" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar" multiple style="display:none" onchange="handleFileSelect(event)">
                    <div class="upload-zone" id="uploadArea"
                         ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)"
                         onclick="document.getElementById('attachmentInput').click()">
                        <div class="upload-zone-icon">📎</div>
                        <div class="upload-zone-title">Drop files here or click to browse</div>
                        <div class="upload-zone-sub">Supports images, videos, PDFs, documents, and archives</div>
                        <div class="upload-type-btns" onclick="event.stopPropagation()">
                            <button type="button" class="upload-type-btn img" onclick="document.getElementById('inputImage').click()">🖼️ Image</button>
                            <button type="button" class="upload-type-btn vid" onclick="document.getElementById('inputVideo').click()">🎬 Video</button>
                            <button type="button" class="upload-type-btn doc" onclick="document.getElementById('inputDoc').click()">📄 Document</button>
                        </div>
                    </div>
                    <div class="file-preview-grid" id="filePreview"></div>
                </div>
                <button type="submit" class="fb-submit-btn" id="submitBtn">Post</button>
                <button type="button" class="fb-cancel-btn" onclick="closeModal()">Cancel</button>
            </form>
            </div>
        </div>
    </div>

    <script src="../../js/enhancements.js"></script>
    <script>
        let selectedFiles = [];

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

        // --- File Upload Helpers ---
        function handleDragOver(e) { e.preventDefault(); document.getElementById('uploadArea').classList.add('dragover'); }
        function handleDragLeave(e) { document.getElementById('uploadArea').classList.remove('dragover'); }
        function handleDrop(e) {
            e.preventDefault();
            document.getElementById('uploadArea').classList.remove('dragover');
            addFiles(Array.from(e.dataTransfer.files));
        }
        function handleFileSelect(e) { addFiles(Array.from(e.target.files)); e.target.value = ''; }

        function addFiles(files) {
            files.forEach(f => { if (!selectedFiles.find(x => x.name === f.name && x.size === f.size)) selectedFiles.push(f); });
            renderFilePreview();
        }

        function removeFile(idx) { selectedFiles.splice(idx, 1); renderFilePreview(); }

        function fmtSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
            return (bytes/(1024*1024)).toFixed(1) + ' MB';
        }

        function renderFilePreview() {
            const preview = document.getElementById('filePreview');
            if (selectedFiles.length === 0) { preview.innerHTML = ''; return; }
            preview.innerHTML = selectedFiles.map((f, i) => {
                const isImage = f.type.startsWith('image/');
                const isVideo = f.type.startsWith('video/');
                const url = URL.createObjectURL(f);
                const ext = f.name.split('.').pop().toLowerCase();
                let thumb = '';
                if (isImage) {
                    thumb = `<img class="fp-thumb" src="${url}" alt="${f.name}">`;
                } else if (isVideo) {
                    thumb = `<video class="fp-thumb" src="${url}"></video>`;
                } else {
                    thumb = `<div class="fp-icon-wrap">${getFileIcon(f.name)}<span class="fp-ext">${ext}</span></div>`;
                }
                return `<div class="fp-item">
                    ${thumb}
                    <div class="fp-label" title="${f.name}">
                        <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${f.name}</div>
                        <div class="fp-size">${fmtSize(f.size)}</div>
                    </div>
                    <button class="fp-remove" onclick="removeFile(${i})" title="Remove">✕</button>
                </div>`;
            }).join('');
        }

        function getFileIcon(name) {
            const ext = name.split('.').pop().toLowerCase();
            if (['pdf'].includes(ext)) return '📄';
            if (['doc','docx'].includes(ext)) return '📝';
            if (['xls','xlsx'].includes(ext)) return '📊';
            if (['ppt','pptx'].includes(ext)) return '📊';
            if (['zip','rar'].includes(ext)) return '🗜️';
            return '📁';
        }

        function getInitials(name) {
            return name ? name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2) : 'A';
        }

        function getAudienceBadge(aud) {
            const map = { all:'👥 Everyone', students:'🎓 Students', teachers:'👨‍🏫 Teachers', registrar:'📋 Registrars', admin:'🔑 Admins' };
            return `<span class="fb-audience-badge ${aud}">${map[aud] || aud}</span>`;
        }

        function getPriorityDot(p) {
            const c = p==='high' ? '#e74c3c' : p==='medium' ? '#f39c12' : '#27ae60';
            return `<span class="fb-priority-dot" style="background:${c};" title="${p} priority"></span>`;
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

        // --- Load Announcements ---
        async function loadAnnouncements() {
            const target = document.getElementById('targetFilter').value;
            const params = target ? `?target=${target}` : '';
            document.getElementById('announcementsTable').innerHTML = '<div style="text-align:center;padding:2rem;color:#65676b;">Loading...</div>';
            try {
                const response = await fetch(`../../api/admin/get_announcements.php${params}`);
                const data = await response.json();

                if (!data.success) {
                    document.getElementById('announcementsTable').innerHTML = `<div style="text-align:center;color:#e74c3c;padding:2rem;">⚠️ ${data.message || 'Failed to load.'}</div>`;
                    return;
                }
                if (data.announcements.length === 0) {
                    document.getElementById('announcementsTable').innerHTML = '<div style="text-align:center;color:#65676b;padding:3rem;">No announcements yet. Be the first to post!</div>';
                    return;
                }

                let html = '';
                data.announcements.forEach(a => {
                    const initials = getInitials(a.posted_by_name);
                    const mediaHtml = renderAttachments(a.attachments);
                    const safeData = JSON.stringify(a).replace(/'/g, '&#39;');
                    html += `
                    <div class="fb-post" id="post-${a.id}">
                        <div class="fb-post-header">
                            <div class="fb-post-avatar">${initials}</div>
                            <div class="fb-post-meta">
                                <div class="fb-post-author">${a.posted_by_name}</div>
                                <div class="fb-post-sub">
                                    ${getPriorityDot(a.priority)}
                                    <span class="fb-post-time">${a.date}</span>
                                    <span>·</span>
                                    ${getAudienceBadge(a.target_audience)}
                                </div>
                            </div>
                            <div class="fb-post-menu">
                                <button class="fb-menu-btn" onclick='editAnnouncement(${safeData})' title="Edit">✏️</button>
                                <button class="fb-menu-btn" onclick="deleteAnnouncement(${a.id},'${a.title.replace(/'/g,"\\'")}')​" title="Delete">🗑️</button>
                            </div>
                        </div>
                        ${a.title ? `<div class="fb-post-title">${a.title}</div>` : ''}
                        <div class="fb-post-content">${a.content}</div>
                        ${mediaHtml}

                    </div>`;
                });
                document.getElementById('announcementsTable').innerHTML = html;
            } catch(e) {
                document.getElementById('announcementsTable').innerHTML = '<div style="text-align:center;color:#e74c3c;padding:2rem;">⚠️ Error loading. Please refresh.</div>';
            }
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Create Announcement';
            document.getElementById('announcementForm').reset();
            document.getElementById('announcementId').value = '';
            document.getElementById('submitBtn').textContent = 'Post';
            selectedFiles = [];
            renderFilePreview();
            document.getElementById('existingAttachment').innerHTML = '';
            document.getElementById('announcementModal').classList.add('active');
        }

        function editAnnouncement(announcement) {
            document.getElementById('modalTitle').textContent = 'Edit Announcement';
            document.getElementById('submitBtn').textContent = 'Save Changes';
            document.getElementById('announcementId').value = announcement.id;
            document.getElementById('title').value = announcement.title;
            document.getElementById('content').value = announcement.content;
            document.getElementById('targetAudience').value = announcement.target_audience;
            document.getElementById('priority').value = announcement.priority;
            selectedFiles = [];
            renderFilePreview();

            // Show existing attachments with delete buttons
            const existing = document.getElementById('existingAttachment');
            if (announcement.attachments && announcement.attachments.length > 0) {
                existing.innerHTML = '<p style="margin:0 0 0.5rem; font-size:0.8rem; font-weight:600; color:#374151;">Current attachments:</p>' +
                    '<div class="existing-att-list">' +
                    announcement.attachments.map(att => {
                        let preview = '';
                        if (att.type === 'image') preview = `<img class="existing-att-thumb" src="../../uploads/announcements/${att.path.split('/').pop()}" alt="${att.original_name}">`;
                        else if (att.type === 'video') preview = `<video class="existing-att-thumb" src="../../uploads/announcements/${att.path.split('/').pop()}"></video>`;
                        else preview = `<div class="existing-att-icon">${getFileIcon(att.original_name)}</div>`;
                        return `<div class="existing-att-item" id="att-${att.id}">
                            ${preview}
                            <span class="existing-att-name" title="${att.original_name}">${att.original_name}</span>
                            <button type="button" class="existing-att-remove" onclick="deleteAttachment(${att.id})">✕ Remove</button>
                        </div>`;
                    }).join('') +
                    '</div>';
            } else {
                existing.innerHTML = '';
            }
            document.getElementById('announcementModal').classList.add('active');
        }

        async function deleteAttachment(attachmentId) {
            const ok = await sccConfirm({ title: 'Remove Attachment', message: 'Remove this attachment?', type: 'warning', confirmText: '✕ Remove' });
            if (!ok) return;
            try {
                const response = await fetch('../../api/admin/delete_announcement_attachment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ attachment_id: attachmentId })
                });
                const result = await response.json();
                if (result.success) {
                    const el = document.getElementById('att-' + attachmentId);
                    if (el) el.remove();
                } else {
                    showToast(result.message || 'Failed to remove attachment.', 'error');
                }
            } catch(e) { showToast('Error removing attachment.', 'error'); }
        }

        function closeModal() { document.getElementById('announcementModal').classList.remove('active'); }

        async function saveAnnouncement(e) {
            e.preventDefault();
            const formData = new FormData();
            const announcementId = document.getElementById('announcementId').value;
            if (announcementId) formData.append('announcement_id', announcementId);
            formData.append('title', document.getElementById('title').value);
            formData.append('content', document.getElementById('content').value);
            formData.append('target_audience', document.getElementById('targetAudience').value);
            formData.append('priority', document.getElementById('priority').value);
            selectedFiles.forEach(f => formData.append('attachments[]', f));
            try {
                const response = await fetch('../../api/admin/save_announcement.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) { showToast(result.message); closeModal(); loadAnnouncements(); }
                else { showToast(result.message || 'Failed to save.', 'error'); }
            } catch(e) { showToast('Error saving announcement.', 'error'); }
        }

        async function deleteAnnouncement(id, title) {
            const ok = await sccConfirm({ title: 'Delete Announcement', message: `Delete "<strong>${title}</strong>"? This cannot be undone.`, type: 'danger', confirmText: '🗑️ Delete' });
            if (!ok) return;
            try {
                const response = await fetch('../../api/admin/delete_announcement.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({announcement_id: id})
                });
                const result = await response.json();
                if (result.success) { showToast(result.message); loadAnnouncements(); }
                else { showToast(result.message || 'Failed to delete.', 'error'); }
            } catch(e) { showToast('Error deleting announcement.', 'error'); }
        }

        loadAnnouncements();
    </script>

    <script>
        (function() {
            var sidebar = document.querySelector('.sidebar');
            // Scroll active nav item into view
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
<script src="../../js/pwa.js"></script>

<!-- Mobile Bottom Navigation -->
    <script src="../../js/session-monitor.js"></script>
    <script src="../../js/apply-branding.js"></script>

    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
      <a href="dashboard.php" class="mobile-nav-item" data-page="dashboard">
        <span class="mobile-nav-icon">📊</span><span>Home</span>
      </a>
      <a href="users.php" class="mobile-nav-item" data-page="users">
        <span class="mobile-nav-icon">👥</span><span>Users</span>
      </a>
      <a href="sections.php" class="mobile-nav-item" data-page="sections">
        <span class="mobile-nav-icon">📁</span><span>Sections</span>
      </a>
      <a href="announcements.php" class="mobile-nav-item" data-page="announcements">
        <span class="mobile-nav-icon">📢</span><span>Notices</span>
      </a>
      <a href="account_settings.php" class="mobile-nav-item" data-page="account_settings">
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

</html>
