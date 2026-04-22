<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('registrar');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Registrar Dashboard</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: var(--radius-lg); max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: var(--radius-md); }
        .file-upload-area { border: 2px dashed #ddd; border-radius: var(--radius-md); padding: 1.5rem; text-align: center; cursor: pointer; transition: border-color 0.2s, background 0.2s; }
        .file-upload-area:hover, .file-upload-area.dragover { border-color: var(--primary); background: rgba(99,102,241,0.05); }
        .file-upload-area input[type="file"] { display: none; }
        .file-upload-icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .file-preview { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.75rem; }
        .file-preview-item { position: relative; border: 1px solid #ddd; border-radius: var(--radius-md); overflow: hidden; background: #f9f9f9; }
        .file-preview-item img, .file-preview-item video { display: block; max-width: 120px; max-height: 90px; object-fit: cover; }
        .file-preview-item .file-icon { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100px; height: 80px; font-size: 2rem; gap: 0.25rem; }
        .file-preview-item .file-name { font-size: 0.7rem; color: #555; max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding: 0.25rem 0.5rem; }
        .file-preview-item .remove-file { position: absolute; top: 3px; right: 3px; background: rgba(220,38,38,0.85); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1; }
        .existing-attachment { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: #f3f4f6; border-radius: var(--radius-md); margin-top: 0.5rem; }
        .existing-attachment img, .existing-attachment video { max-width: 80px; max-height: 60px; border-radius: 4px; object-fit: cover; }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Sidebar -->
                <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <img src="../../../public/images/logo2.jpg" alt="SCC Logo" id="sidebarLogoImg" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-md);">
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
                <div class="card-header" style="display: flex; gap: 1rem;">
                    <select id="targetFilter" onchange="loadAnnouncements()" style="padding: 0.5rem; border-radius: var(--radius-md); border: 1px solid #ddd;">
                        <option value="">All Audiences</option>
                        <option value="all">Everyone</option>
                        <option value="students">Students Only</option>
                        <option value="teachers">Teachers Only</option>
                        <option value="registrar">Registrars Only</option>
                    </select>
                </div>
                <div id="announcementsTable">Loading...</div>
            </div>
        </main>
    </div>

    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Post Announcement</h2>
            <form id="announcementForm" onsubmit="saveAnnouncement(event)">
                <input type="hidden" id="announcementId">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" id="title" required>
                </div>
                <div class="form-group">
                    <label>Content *</label>
                    <textarea id="content" rows="6" required></textarea>
                </div>
                <div class="form-group">
                    <label>Target Audience *</label>
                    <select id="targetAudience" required>
                        <option value="all">Everyone</option>
                        <option value="students">Students Only</option>
                        <option value="teachers">Teachers Only</option>
                        <option value="registrar">Registrars Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select id="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Attachments <span style="font-weight:400; color: var(--text-secondary);">(images, videos, or files)</span></label>
                    <div id="existingAttachment"></div>
                    <div class="file-upload-area" id="uploadArea" onclick="document.getElementById('attachmentInput').click()" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
                        <input type="file" id="attachmentInput" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip" multiple onchange="handleFileSelect(event)">
                        <div class="file-upload-icon">📎</div>
                        <p style="margin:0; font-size:0.9rem; color: var(--text-secondary);">Click to upload or drag & drop here</p>
                        <p style="margin:0.25rem 0 0; font-size:0.75rem; color: var(--text-secondary);">Images, Videos, PDFs, Docs, and more</p>
                    </div>
                    <div class="file-preview" id="filePreview"></div>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Post</button>
                    <button type="button" class="btn" onclick="closeModal()" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
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

        let selectedFiles = [];

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

        function renderFilePreview() {
            const preview = document.getElementById('filePreview');
            if (selectedFiles.length === 0) { preview.innerHTML = ''; return; }
            preview.innerHTML = selectedFiles.map((f, i) => {
                const isImage = f.type.startsWith('image/');
                const isVideo = f.type.startsWith('video/');
                const url = URL.createObjectURL(f);
                let media = isImage
                    ? `<img src="${url}" alt="${f.name}">`
                    : isVideo
                    ? `<video src="${url}"></video>`
                    : `<div class="file-icon">${getFileIcon(f.name)}<span style="font-size:0.65rem;">${f.name.split('.').pop().toUpperCase()}</span></div>`;
                return `<div class="file-preview-item">
                    ${media}
                    <div class="file-name" title="${f.name}">${f.name}</div>
                    <button class="remove-file" onclick="removeFile(${i})" title="Remove">✕</button>
                </div>`;
            }).join('');
        }

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
            
            const response = await fetch(`../../api/registrar/get_announcements.php${params}`);
            const data = await response.json();
            
            if (data.success) {
                let html = '<div style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem;">';
                data.announcements.forEach(a => {
                    const priorityColor = a.priority === 'high' ? 'var(--status-rejected)' : a.priority === 'medium' ? 'var(--status-pending)' : 'var(--text-secondary)';
                    html += `
                        <div style="padding: 1.5rem; background: var(--background-main); border-radius: var(--radius-md); border-left: 4px solid ${priorityColor};">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                                <div>
                                    <h3 style="margin: 0; font-size: 1.125rem;">${a.title}</h3>
                                    <div style="display: flex; gap: 1rem; margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-secondary);">
                                        <span>👤 ${a.posted_by_name}</span>
                                        <span>📅 ${a.date}</span>
                                        <span class="status-badge">${a.target_audience}</span>
                                        <span style="color: ${priorityColor}; font-weight: 600;">${a.priority.toUpperCase()}</span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button class="btn btn-sm" onclick='editAnnouncement(${JSON.stringify(a)})'>Edit</button>
                                    <button class="btn btn-sm" onclick="deleteAnnouncement(${a.id}, '${a.title}')" style="background: var(--status-rejected);">Delete</button>
                                </div>
                            </div>
                            <p style="margin: 0; color: var(--text-primary);">${a.content}</p>
                            ${renderAttachments(a.attachments)}
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('announcementsTable').innerHTML = data.announcements.length > 0 ? html : '<p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No announcements found</p>';
            }
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Post Announcement';
            document.getElementById('announcementForm').reset();
            document.getElementById('announcementId').value = '';
            selectedFiles = [];
            renderFilePreview();
            document.getElementById('existingAttachment').innerHTML = '';
            document.getElementById('announcementModal').classList.add('active');
        }

        function editAnnouncement(announcement) {
            document.getElementById('modalTitle').textContent = 'Edit Announcement';
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
                existing.innerHTML = '<p style="margin:0 0 0.5rem; font-size:0.85rem; color:var(--text-secondary);">Current attachments:</p>' +
                    announcement.attachments.map(att => {
                        let preview = '';
                        if (att.type === 'image') preview = `<img src="../uploads/announcements/${att.path.split('/').pop()}" alt="${att.original_name}" style="max-width:80px;max-height:60px;border-radius:4px;object-fit:cover;">`;
                        else if (att.type === 'video') preview = `<video src="../uploads/announcements/${att.path.split('/').pop()}" style="max-width:80px;max-height:60px;"></video>`;
                        else preview = `<span style="font-size:1.5rem;">${getFileIcon(att.original_name)}</span>`;
                        return `<div class="existing-attachment" id="att-${att.id}">
                            ${preview}
                            <span style="font-size:0.85rem;flex:1;">${att.original_name}</span>
                            <button type="button" onclick="deleteAttachment(${att.id})" style="background:rgba(220,38,38,0.85);color:white;border:none;border-radius:6px;padding:0.25rem 0.6rem;cursor:pointer;font-size:0.75rem;">✕ Remove</button>
                        </div>`;
                    }).join('');
            } else {
                existing.innerHTML = '';
            }
            document.getElementById('announcementModal').classList.add('active');
        }

        async function deleteAttachment(attachmentId) {
            if (!confirm('Remove this attachment?')) return;
            const response = await fetch('../../api/registrar/delete_announcement_attachment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ attachment_id: attachmentId })
            });
            const result = await response.json();
            if (result.success) {
                // Remove the element from the UI instantly
                const el = document.getElementById(`att-${attachmentId}`);
                if (el) el.remove();
            } else {
                alert('Error: ' + result.message);
            }
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

            const response = await fetch('../../api/registrar/save_announcement.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) { alert(result.message); closeModal(); loadAnnouncements(); } 
            else { alert('Error: ' + result.message); }
        }

        async function deleteAnnouncement(id, title) {
            if (!confirm(`Delete "${title}"?`)) return;
            const response = await fetch('../../api/registrar/delete_announcement.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({announcement_id: id})
            });
            const result = await response.json();
            if (result.success) { alert(result.message); loadAnnouncements(); } 
            else { alert('Error: ' + result.message); }
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
