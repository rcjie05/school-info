<?php
require_once '../../php/config.php';
requireRole('registrar');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Registrar Dashboard</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        /* ── FB-style feed ── */
        .fb-feed { max-width: 680px; margin: 0 auto; padding: 1rem; display: flex; flex-direction: column; gap: 1rem; }
        .fb-filter-bar { max-width: 680px; margin: 0 auto; padding: 0.75rem 1rem 0; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .fb-filter-select { padding: 0.4rem 0.8rem; border-radius: 20px; border: 1.5px solid var(--border-color, #e4e6eb); background: var(--background-card, #fff); font-size: 0.82rem; font-weight: 600; color: var(--text-primary, #050505); cursor: pointer; outline: none; }
        .fb-filter-select:focus { border-color: #1877f2; }
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
        .fb-audience-badge.staff    { background: #e0f2fe; color: #0369a1; }
        .fb-post-menu { display: flex; gap: 0.25rem; }
        .fb-menu-btn { background: none; border: none; border-radius: 50%; width: 34px; height: 34px; cursor: pointer; font-size: 0.8rem; color: var(--text-secondary, #65676b); display: flex; align-items: center; justify-content: center; transition: background 0.15s; }
        .fb-menu-btn:hover { background: var(--background-main, #f0f2f5); }
        .fb-post-title { font-weight: 700; font-size: 1rem; color: var(--text-primary, #050505); padding: 0.25rem 1.25rem 0; }
        .fb-post-content { padding: 0.5rem 1.25rem 0.75rem; font-size: 0.95rem; color: var(--text-primary, #1c1e21); line-height: 1.55; white-space: pre-wrap; word-break: break-word; }
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
        .fb-file-attachments { padding: 0.5rem 1.25rem; display: flex; flex-direction: column; gap: 0.4rem; }
        .fb-file-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; background: var(--background-main, #f0f2f5); border-radius: 8px; text-decoration: none; color: var(--text-primary, #050505); font-size: 0.85rem; transition: background 0.15s; }
        .fb-file-item:hover { background: var(--border-color, #e4e6eb); }
        .fb-file-icon { width: 36px; height: 36px; border-radius: 8px; background: #1877f2; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
        .fb-file-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }
        /* ── Modal ── */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; max-width: 600px; width: 94%; max-height: 92vh; overflow-y: auto; box-shadow: 0 8px 40px rgba(0,0,0,0.25); }
        .modal-header-fb { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid #e4e6eb; }
        .modal-header-fb h2 { font-size: 1.15rem; font-weight: 700; color: #050505; margin: 0; }
        .modal-close-x { background: #e4e6eb; border: none; border-radius: 50%; width: 34px; height: 34px; font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #050505; }
        .modal-close-x:hover { background: #ccd0d5; }
        .modal-body-fb { padding: 1.25rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.85rem; color: #050505; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.65rem 0.85rem; border: 1.5px solid #ccd0d5; border-radius: 8px; font-size: 0.9rem; color: #050505; box-sizing: border-box; font-family: inherit; transition: border-color 0.15s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #1877f2; box-shadow: 0 0 0 3px rgba(24,119,242,0.15); }
        .fb-submit-btn { width: 100%; padding: 0.75rem; border-radius: 8px; border: none; background: #1877f2; color: white; font-size: 1rem; font-weight: 700; cursor: pointer; margin-top: 0.75rem; transition: background 0.15s; }
        .fb-submit-btn:hover { background: #166fe5; }
        .fb-cancel-btn { width: 100%; padding: 0.65rem; border-radius: 8px; border: none; background: #e4e6eb; color: #050505; font-size: 0.9rem; font-weight: 600; cursor: pointer; margin-top: 0.5rem; }
        .fb-cancel-btn:hover { background: #ccd0d5; }
        .upload-zone { border: 2px dashed #d1d5db; border-radius: 12px; padding: 1.5rem; text-align: center; cursor: pointer; transition: all 0.2s ease; background: #fafafa; }
        .upload-zone:hover, .upload-zone.dragover { border-color: #6366f1; background: #f5f3ff; }
        .upload-zone input[type="file"] { display: none; }
        .fp-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; }
        .fp-item { position: relative; border-radius: 8px; overflow: hidden; background: #f0f2f5; width: 90px; }
        .fp-thumb { width: 90px; height: 70px; object-fit: cover; display: block; }
        .fp-icon-wrap { width: 90px; height: 70px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.2rem; font-size: 1.6rem; }
        .fp-label { font-size: 0.65rem; color: #555; padding: 0.2rem 0.4rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fp-remove { position: absolute; top: 3px; right: 3px; background: rgba(17,17,17,0.65); color: white; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 0.65rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .existing-att-list { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 0.75rem; }
        .existing-att-item { display: flex; align-items: center; gap: 0.6rem; padding: 0.5rem 0.75rem; background: #f3f4f6; border-radius: 8px; font-size: 0.82rem; }
        .existing-att-thumb { width: 44px; height: 36px; border-radius: 5px; object-fit: cover; flex-shrink: 0; }
        .existing-att-icon { width: 44px; height: 36px; border-radius: 5px; background: #ede9fe; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .existing-att-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .existing-att-remove { background: rgba(220,38,38,0.85); color: white; border: none; border-radius: 6px; padding: 0.2rem 0.5rem; cursor: pointer; font-size: 0.72rem; font-weight: 600; flex-shrink: 0; }
        .ann-empty { text-align: center; color: var(--text-secondary, #94a3b8); padding: 4rem 2rem; font-size: 0.95rem; }
        .ann-empty-icon { font-size: 2.5rem; margin-bottom: 0.75rem; display: block; }
        .toast { position: fixed; bottom: 2rem; right: 2rem; padding: 1rem 1.5rem; border-radius: 10px; color: white; font-weight: 600; z-index: 9999; display: none; box-shadow: 0 4px 16px rgba(0,0,0,0.2); }
        .toast.success { background: #10b981; }
        .toast.error   { background: #ef4444; }
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
                    <span>Registrar Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="applications.php" class="nav-item"><span class="nav-icon">📋</span><span>Applications</span></a>
                    <a href="manage_loads.php" class="nav-item"><span class="nav-icon">📚</span><span>Study Loads</span></a>
                    <a href="grades.php" class="nav-item"><span class="nav-icon">🎓</span><span>Grades</span></a>
                    <a href="add_drop_requests.php" class="nav-item"><span class="nav-icon">🔄</span><span>Add/Drop Requests</span></a>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                    <a href="reports.php" class="nav-item"><span class="nav-icon">📈</span><span>Reports</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
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
                    <p class="page-subtitle">Post and manage announcements</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openAddModal()">📢 Post Announcement</button>
                </div>
            </header>

            <div class="content-card">
                <div class="fb-filter-bar">
                    <select class="fb-filter-select" id="targetFilter" onchange="loadAnnouncements()">
                        <option value="">All Audiences</option>
                        <option value="all">Everyone</option>
                        <option value="students">Students Only</option>
                        <option value="teachers">Teachers Only</option>
                        <option value="registrar">Registrars Only</option>
                        <option value="staff">Staff (HR) Only</option>
                    </select>
                </div>
                <div class="fb-feed" id="announcementsTable"><div class="ann-empty"><span class="ann-empty-icon">⏳</span>Loading…</div></div>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <div class="modal-header-fb">
                <h2 id="modalTitle">Post Announcement</h2>
                <button class="modal-close-x" onclick="closeModal()">✕</button>
            </div>
            <div class="modal-body-fb">
            <form id="announcementForm" onsubmit="saveAnnouncement(event)">
                <input type="hidden" id="announcementId">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" id="title" required>
                </div>
                <div class="form-group">
                    <label>Content *</label>
                    <textarea id="content" rows="5" required></textarea>
                </div>
                <div class="form-group" style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                    <div>
                        <label>Target Audience *</label>
                        <select id="targetAudience" required>
                            <option value="all">Everyone</option>
                            <option value="students">Students Only</option>
                            <option value="teachers">Teachers Only</option>
                            <option value="registrar">Registrars Only</option>
                            <option value="staff">Staff (HR) Only</option>
                        </select>
                    </div>
                    <div>
                        <label>Priority</label>
                        <select id="priority">
                            <option value="low">🟢 Low</option>
                            <option value="medium" selected>🟡 Medium</option>
                            <option value="high">🔴 High</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Attachments <span style="font-weight:400;color:#65676b;font-size:0.8rem;">optional</span></label>
                    <div id="existingAttachment"></div>
                    <input type="file" id="attachmentInput" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip" multiple style="display:none" onchange="handleFileSelect(event)">
                    <div class="upload-zone" id="uploadArea"
                         ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)"
                         onclick="document.getElementById('attachmentInput').click()">
                        <div style="font-size:1.8rem;margin-bottom:0.4rem;">📎</div>
                        <div style="font-size:0.9rem;font-weight:600;color:#374151;">Drop files here or click to browse</div>
                        <div style="font-size:0.75rem;color:#9ca3af;margin-top:0.25rem;">Images, videos, PDFs, documents</div>
                    </div>
                    <div class="fp-grid" id="filePreview"></div>
                </div>
                <button type="submit" class="fb-submit-btn" id="submitBtn">Post</button>
                <button type="button" class="fb-cancel-btn" onclick="closeModal()">Cancel</button>
            </form>
            </div>
        </div>
    </div>

    <!-- Lightbox -->
    <div id="imageLightbox" onclick="closeLightbox()" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:2000; align-items:center; justify-content:center; cursor:zoom-out;">
        <img id="lightboxImg" src="" alt="" style="max-width:90%; max-height:90vh; border-radius:8px; object-fit:contain; box-shadow:0 4px 32px rgba(0,0,0,0.5);">
    </div>
    <div class="toast" id="toast"></div>

    <script>
        let selectedFiles = [];

        function showToast(msg, type='success') {
            const t = document.getElementById('toast');
            t.textContent = msg; t.className = 'toast ' + type; t.style.display = 'block';
            setTimeout(() => t.style.display = 'none', 3500);
        }
        function openLightbox(src) { document.getElementById('lightboxImg').src = src; document.getElementById('imageLightbox').style.display = 'flex'; }
        function closeLightbox() { document.getElementById('imageLightbox').style.display = 'none'; document.getElementById('lightboxImg').src = ''; }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeLightbox(); closeModal(); } });

        function handleDragOver(e) { e.preventDefault(); document.getElementById('uploadArea').classList.add('dragover'); }
        function handleDragLeave(e) { document.getElementById('uploadArea').classList.remove('dragover'); }
        function handleDrop(e) { e.preventDefault(); document.getElementById('uploadArea').classList.remove('dragover'); addFiles(Array.from(e.dataTransfer.files)); }
        function handleFileSelect(e) { addFiles(Array.from(e.target.files)); e.target.value = ''; }
        function addFiles(files) { files.forEach(f => { if (!selectedFiles.find(x => x.name === f.name && x.size === f.size)) selectedFiles.push(f); }); renderFilePreview(); }
        function removeFile(idx) { selectedFiles.splice(idx, 1); renderFilePreview(); }

        function renderFilePreview() {
            const preview = document.getElementById('filePreview');
            if (!selectedFiles.length) { preview.innerHTML = ''; return; }
            preview.innerHTML = selectedFiles.map((f, i) => {
                const isImage = f.type.startsWith('image/'), isVideo = f.type.startsWith('video/');
                const url = URL.createObjectURL(f);
                let thumb = isImage ? `<img class="fp-thumb" src="${url}" alt="${f.name}">`
                          : isVideo ? `<video class="fp-thumb" src="${url}"></video>`
                          : `<div class="fp-icon-wrap">${getFileIcon(f.name)}</div>`;
                return `<div class="fp-item">${thumb}<div class="fp-label" title="${f.name}">${f.name}</div><button class="fp-remove" type="button" onclick="removeFile(${i})">✕</button></div>`;
            }).join('');
        }

        function getFileIcon(name) {
            const ext = name.split('.').pop().toLowerCase();
            if (ext === 'pdf') return '📄'; if (['doc','docx'].includes(ext)) return '📝';
            if (['xls','xlsx'].includes(ext)) return '📊'; if (['zip','rar'].includes(ext)) return '🗜️'; return '📁';
        }

        function getInitials(name) { return (name||'?').split(' ').slice(0,2).map(w=>w[0]).join('').toUpperCase(); }
        const AVATAR_COLORS = ['#1877f2','#7C3AED','#059669','#DC2626','#D97706','#2563EB','#DB2777'];
        function avatarColor(name) { let h=0; for (let c of (name||'')) h=(h*31+c.charCodeAt(0))&0xffffffff; return AVATAR_COLORS[Math.abs(h)%AVATAR_COLORS.length]; }
        function getPriorityDot(p) { const c=p==='high'?'#e74c3c':p==='medium'?'#f39c12':'#27ae60'; return `<span class="fb-priority-dot" style="background:${c};" title="${p} priority"></span>`; }
        function getAudienceBadge(aud) { const map={all:'👥 Everyone',students:'🎓 Students',teachers:'👨‍🏫 Teachers',registrar:'📋 Registrars',admin:'🔑 Admins',staff:'🏢 Staff'}; return `<span class="fb-audience-badge ${aud}">${map[aud]||aud}</span>`; }

        function renderAttachments(attachments) {
            if (!attachments || !attachments.length) return '';
            const images = attachments.filter(a => a.type === 'image' || a.type === 'video');
            const files  = attachments.filter(a => a.type === 'file');
            let html = '';
            if (images.length > 0) {
                const countClass = images.length===1?'count-1':images.length===2?'count-2':images.length===3?'count-3':images.length===4?'count-4':'count-many';
                const visible = images.slice(0,4), extra = images.length-4;
                html += `<div class="fb-media-grid ${countClass}">`;
                visible.forEach((att,idx) => {
                    const src = `../../uploads/announcements/${att.path.split('/').pop()}`;
                    const isLast = idx===3 && extra>0;
                    if (att.type==='video') html += `<div class="fb-media-item"><video src="${src}" onclick="event.stopPropagation()" controls></video>${isLast?`<div class="fb-media-overlay">+${extra}</div>`:''}</div>`;
                    else html += `<div class="fb-media-item" onclick="openLightbox('${src}')"><img src="${src}" alt="${att.original_name}" loading="lazy">${isLast?`<div class="fb-media-overlay">+${extra}</div>`:''}</div>`;
                });
                html += '</div>';
            }
            if (files.length > 0) {
                html += '<div class="fb-file-attachments">';
                files.forEach(att => {
                    const src = `../../uploads/announcements/${att.path.split('/').pop()}`;
                    html += `<a class="fb-file-item" href="${src}" target="_blank" download="${att.original_name}"><div class="fb-file-icon">${getFileIcon(att.original_name)}</div><span class="fb-file-name">${att.original_name}</span><span style="font-size:0.75rem;color:#65676b;">↓</span></a>`;
                });
                html += '</div>';
            }
            return html;
        }

        async function loadAnnouncements() {
            const target = document.getElementById('targetFilter').value;
            const params = target ? `?target=${target}` : '';
            const container = document.getElementById('announcementsTable');
            container.innerHTML = '<div class="ann-empty"><span class="ann-empty-icon">⏳</span>Loading…</div>';
            try {
                const response = await fetch(`../../api/registrar/get_announcements.php${params}`);
                const data = await response.json();
                if (!data.success || !data.announcements.length) {
                    container.innerHTML = '<div class="ann-empty"><span class="ann-empty-icon">📭</span>No announcements found.</div>'; return;
                }
                let html = '';
                data.announcements.forEach(a => {
                    const safeData = JSON.stringify(a).replace(/'/g, '&#39;');
                    html += `<div class="fb-post" id="post-${a.id}">
                        <div class="fb-post-header">
                            <div class="fb-post-avatar" style="background:${avatarColor(a.posted_by_name)}">${getInitials(a.posted_by_name)}</div>
                            <div class="fb-post-meta">
                                <div class="fb-post-author">${a.posted_by_name}</div>
                                <div class="fb-post-sub">${getPriorityDot(a.priority)}<span class="fb-post-time">${a.date}</span><span>·</span>${getAudienceBadge(a.target_audience)}</div>
                            </div>
                            <div class="fb-post-menu">
                                <button class="fb-menu-btn" onclick='editAnnouncement(${safeData})' title="Edit">✏️</button>
                                <button class="fb-menu-btn" onclick="deleteAnnouncement(${a.id},'${a.title.replace(/'/g,"\\'")}')" title="Delete">🗑️</button>
                            </div>
                        </div>
                        ${a.title ? `<div class="fb-post-title">${a.title}</div>` : ''}
                        <div class="fb-post-content">${a.content}</div>
                        ${renderAttachments(a.attachments)}
                    </div>`;
                });
                container.innerHTML = html;
            } catch(e) { container.innerHTML = '<div class="ann-empty"><span class="ann-empty-icon">⚠️</span>Failed to load.</div>'; }
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Create Announcement';
            document.getElementById('announcementForm').reset();
            document.getElementById('announcementId').value = '';
            document.getElementById('submitBtn').textContent = 'Post';
            selectedFiles = []; renderFilePreview();
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
            selectedFiles = []; renderFilePreview();
            const existing = document.getElementById('existingAttachment');
            if (announcement.attachments && announcement.attachments.length > 0) {
                existing.innerHTML = '<p style="margin:0 0 0.5rem;font-size:0.8rem;font-weight:600;color:#374151;">Current attachments:</p><div class="existing-att-list">' +
                    announcement.attachments.map(att => {
                        let preview = att.type==='image' ? `<img class="existing-att-thumb" src="../../uploads/announcements/${att.path.split('/').pop()}" alt="${att.original_name}">` : att.type==='video' ? `<video class="existing-att-thumb" src="../../uploads/announcements/${att.path.split('/').pop()}"></video>` : `<div class="existing-att-icon">${getFileIcon(att.original_name)}</div>`;
                        return `<div class="existing-att-item" id="att-${att.id}">${preview}<span class="existing-att-name">${att.original_name}</span><button type="button" class="existing-att-remove" onclick="deleteAttachment(${att.id})">✕ Remove</button></div>`;
                    }).join('') + '</div>';
            } else { existing.innerHTML = ''; }
            document.getElementById('announcementModal').classList.add('active');
        }

        async function deleteAttachment(attachmentId) {
            if (!confirm('Remove this attachment?')) return;
            const response = await fetch('../../api/registrar/delete_announcement_attachment.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({attachment_id:attachmentId}) });
            const result = await response.json();
            if (result.success) { const el = document.getElementById('att-'+attachmentId); if (el) el.remove(); }
            else showToast(result.message||'Failed to remove.','error');
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
            const response = await fetch('../../api/registrar/save_announcement.php', { method:'POST', body:formData });
            const result = await response.json();
            if (result.success) { showToast(result.message); closeModal(); loadAnnouncements(); }
            else showToast(result.message||'Failed to save.','error');
        }

        async function deleteAnnouncement(id, title) {
            if (!confirm(`Delete "${title}"?`)) return;
            const response = await fetch('../../api/registrar/delete_announcement.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({announcement_id:id}) });
            const result = await response.json();
            if (result.success) { showToast(result.message); loadAnnouncements(); }
            else showToast(result.message||'Failed to delete.','error');
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
      <a href="applications.php" class="mobile-nav-item" data-page="applications">
        <span class="mobile-nav-icon">📋</span><span>Apps</span>
      </a>
      <a href="manage_loads.php" class="mobile-nav-item" data-page="manage_loads">
        <span class="mobile-nav-icon">📚</span><span>Loads</span>
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
