<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('hr');
$current_user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="../../../public/manifest.json">
    <meta name="theme-color" content="#1E3352">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($school_name) ?> Portal">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <title>Announcements - <?= htmlspecialchars($school_name) ?> Portal</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; }
        .modal.active { display:flex; }
        .modal-content { background:white; padding:2rem; border-radius:var(--radius-lg); max-width:700px; width:90%; max-height:90vh; overflow-y:auto; }
        .form-group { margin-bottom:1rem; }
        .form-group label { display:block; margin-bottom:0.5rem; font-weight:600; font-size:0.875rem; }
        .form-group input, .form-group select, .form-group textarea { width:100%; padding:0.75rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-family:inherit; font-size:0.9rem; box-sizing:border-box; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline:none; border-color:var(--primary-purple); }
        .form-group textarea { resize:vertical; min-height:120px; }
        .file-upload-area { border:2px dashed #ddd; border-radius:var(--radius-md); padding:1.5rem; text-align:center; cursor:pointer; transition:border-color 0.2s, background 0.2s; }
        .file-upload-area:hover, .file-upload-area.dragover { border-color:var(--primary-purple); background:rgba(99,102,241,0.05); }
        .file-upload-area input[type="file"] { display:none; }
        .file-upload-icon { font-size:2rem; margin-bottom:0.5rem; }
        .file-preview { display:flex; flex-wrap:wrap; gap:0.75rem; margin-top:0.75rem; }
        .file-preview-item { position:relative; border:1px solid #ddd; border-radius:var(--radius-md); overflow:hidden; background:#f9f9f9; }
        .file-preview-item img, .file-preview-item video { display:block; max-width:120px; max-height:90px; object-fit:cover; }
        .file-preview-item .file-icon { display:flex; flex-direction:column; align-items:center; justify-content:center; width:100px; height:80px; font-size:2rem; gap:0.25rem; }
        .file-preview-item .file-name { font-size:0.7rem; color:#555; max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; padding:0.25rem 0.5rem; }
        .file-preview-item .remove-file { position:absolute; top:3px; right:3px; background:rgba(220,38,38,0.85); color:white; border:none; border-radius:50%; width:20px; height:20px; font-size:0.75rem; cursor:pointer; display:flex; align-items:center; justify-content:center; }
        .ann-card { padding:1.5rem; background:var(--background-main); border-radius:var(--radius-md); border-left:4px solid #e5e7eb; margin-bottom:1rem; }
        .ann-card.priority-high   { border-left-color:#ef4444; }
        .ann-card.priority-medium { border-left-color:#f59e0b; }
        .ann-card.priority-low    { border-left-color:#9ca3af; }
        .ann-meta { display:flex; gap:1rem; flex-wrap:wrap; font-size:0.82rem; color:var(--text-secondary); margin-top:0.4rem; }
        .badge-audience { display:inline-block; padding:0.2rem 0.6rem; border-radius:999px; font-size:0.72rem; font-weight:700; text-transform:uppercase; background:#ede9fe; color:#5b21b6; }
        .badge-own { display:inline-block; padding:0.2rem 0.6rem; border-radius:999px; font-size:0.72rem; font-weight:700; background:#dbeafe; color:#1e40af; }
        .ann-body { margin:0.75rem 0 0; font-size:0.9rem; line-height:1.6; white-space:pre-wrap; }
        .announcement-attachment { margin-top:0.75rem; }
        .announcement-attachment img { max-width:100%; max-height:300px; border-radius:var(--radius-md); object-fit:contain; background:#f3f4f6; cursor:zoom-in; display:block; margin-bottom:0.5rem; }
        .announcement-attachment video { max-width:100%; max-height:300px; border-radius:var(--radius-md); margin-bottom:0.5rem; }
        .file-link { display:inline-flex; align-items:center; gap:0.5rem; padding:0.5rem 1rem; background:white; border:1px solid #ddd; border-radius:var(--radius-md); text-decoration:none; color:var(--text-primary); font-size:0.875rem; margin-bottom:0.5rem; }
        .file-link:hover { background:#e5e7eb; }
        .ann-actions { display:flex; gap:0.5rem; margin-top:0.75rem; }
        .btn-edit   { background:var(--primary-purple); color:white; border:none; padding:0.4rem 1rem; border-radius:var(--radius-md); font-size:0.8rem; font-weight:600; cursor:pointer; }
        .btn-delete { background:#ef4444; color:white; border:none; padding:0.4rem 1rem; border-radius:var(--radius-md); font-size:0.8rem; font-weight:600; cursor:pointer; }
        .toast { position:fixed; bottom:2rem; right:2rem; padding:1rem 1.5rem; border-radius:var(--radius-md); color:white; font-weight:600; z-index:9999; display:none; }
        .toast.success { background:#10b981; }
        .toast.error   { background:#ef4444; }
        .filter-bar { display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap; }
        .filter-bar select { padding:0.5rem 1rem; border:1.5px solid #e5e7eb; border-radius:var(--radius-md); font-family:inherit; background:white; }
        .empty-state { text-align:center; color:var(--text-secondary); padding:3rem; }
        .existing-attachment { display:flex; align-items:center; gap:0.75rem; padding:0.75rem; background:#f3f4f6; border-radius:var(--radius-md); margin-bottom:0.5rem; font-size:0.85rem; }
        .existing-attachment img { max-width:70px; max-height:52px; border-radius:4px; object-fit:cover; }
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
                    <span>HR Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">HR Management</div>
                    <a href="employees.php" class="nav-item"><span class="nav-icon">👤</span><span>Employee Profiles</span></a>
                    <a href="leaves.php" class="nav-item"><span class="nav-icon">📅</span><span>Leave Requests</span></a>
                    <a href="attendance.php" class="nav-item"><span class="nav-icon">🕐</span><span>Attendance</span></a>
                    <a href="id_cards.php" class="nav-item"><span class="nav-icon">🪪</span><span>ID Cards</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Resources</div>
                    <a href="announcements.php" class="nav-item active"><span class="nav-icon">📢</span><span>Announcements</span></a>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
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
                    <h1>Announcements</h1>
                <p class="page-subtitle">Post and view school-wide announcements</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAddModal()">📢 Post Announcement</button>
            </div>
        </header>

        <div class="content-card">
            <div class="card-header">
                <div class="filter-bar">
                    <select id="targetFilter" onchange="loadAnnouncements()">
                        <option value="">All Announcements</option>
                        <option value="all">Everyone</option>
                        <option value="teachers">Teachers Only</option>
                        <option value="registrar">Registrars Only</option>
                        <option value="staff">Staff Only</option>
                        <option value="admin">Admins Only</option>
                    </select>
                    <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.875rem;cursor:pointer;">
                        <input type="checkbox" id="myOnlyFilter" onchange="loadAnnouncements()"> My posts only
                    </label>
                </div>
            </div>
            <div id="announcementsList" style="padding:1rem;">Loading...</div>
        </div>
    </main>
</div>

<!-- Lightbox -->
<div id="imageLightbox" onclick="closeLightbox()" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:2000;align-items:center;justify-content:center;cursor:zoom-out;">
    <img id="lightboxImg" src="" alt="" style="max-width:90%;max-height:90vh;border-radius:8px;object-fit:contain;box-shadow:0 4px 32px rgba(0,0,0,0.5);">
</div>

<!-- Post / Edit Modal -->
<div id="announcementModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle" style="margin:0 0 1.5rem;">Post Announcement</h2>
        <form id="announcementForm" onsubmit="saveAnnouncement(event)">
            <input type="hidden" id="announcementId">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" id="annTitle" required placeholder="Announcement title">
            </div>
            <div class="form-group">
                <label>Content *</label>
                <textarea id="annContent" rows="6" required placeholder="Write your announcement here..."></textarea>
            </div>
            <div class="form-group">
                <label>Target Audience *</label>
                <select id="annTarget" required>
                    <option value="all">Everyone</option>
                    <option value="teachers">Teachers Only</option>
                    <option value="registrar">Registrars Only</option>
                    <option value="staff">Staff Only</option>
                    <option value="admin">Admins Only</option>
                </select>
            </div>
            <div class="form-group">
                <label>Priority</label>
                <select id="annPriority">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="form-group">
                <label>Attachments <span style="font-weight:400;color:var(--text-secondary);">(images, videos, or files)</span></label>
                <div id="existingAttachments"></div>
                <div class="file-upload-area" id="uploadArea"
                     onclick="document.getElementById('attachmentInput').click()"
                     ondragover="handleDragOver(event)"
                     ondragleave="handleDragLeave(event)"
                     ondrop="handleDrop(event)">
                    <input type="file" id="attachmentInput" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip" multiple onchange="handleFileSelect(event)">
                    <div class="file-upload-icon">📎</div>
                    <p style="margin:0;font-size:0.9rem;color:var(--text-secondary);">Click to upload or drag &amp; drop</p>
                    <p style="margin:0.25rem 0 0;font-size:0.75rem;color:var(--text-secondary);">Images, Videos, PDFs, Docs and more</p>
                </div>
                <div class="file-preview" id="filePreview"></div>
            </div>
            <div style="display:flex;gap:1rem;margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1;" id="saveBtn">📤 Post</button>
                <button type="button" class="btn" onclick="closeModal()" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const CURRENT_USER_ID = <?php echo $current_user_id; ?>;
let selectedFiles = [];

function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('imageLightbox').style.display = 'flex';
}
function closeLightbox() {
    document.getElementById('imageLightbox').style.display = 'none';
    document.getElementById('lightboxImg').src = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeLightbox(); closeModal(); } });

function handleDragOver(e)  { e.preventDefault(); document.getElementById('uploadArea').classList.add('dragover'); }
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
    if (!selectedFiles.length) { preview.innerHTML = ''; return; }
    preview.innerHTML = selectedFiles.map((f, i) => {
        const isImage = f.type.startsWith('image/');
        const isVideo = f.type.startsWith('video/');
        const url = URL.createObjectURL(f);
        let media = isImage ? '<img src="' + url + '" alt="' + f.name + '">'
                  : isVideo ? '<video src="' + url + '"></video>'
                  : '<div class="file-icon">' + getFileIcon(f.name) + '<span style="font-size:0.65rem;">' + f.name.split('.').pop().toUpperCase() + '</span></div>';
        return '<div class="file-preview-item">' + media +
            '<div class="file-name" title="' + f.name + '">' + f.name + '</div>' +
            '<button class="remove-file" type="button" onclick="removeFile(' + i + ')" title="Remove">&#x2715;</button>' +
            '</div>';
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
    if (!attachments || !attachments.length) return '';
    return '<div class="announcement-attachment">' + attachments.map(att => {
        const fname = att.path.split('/').pop();
        const src   = '../uploads/announcements/' + fname;
        if (att.type === 'image') return '<img src="' + src + '" alt="' + esc(att.original_name) + '" onclick="openLightbox(\'' + src + '\')">';
        if (att.type === 'video') return '<video src="' + src + '" controls></video>';
        return '<a class="file-link" href="' + src + '" target="_blank" download="' + esc(att.original_name) + '">' + getFileIcon(att.original_name) + ' ' + esc(att.original_name) + '</a>';
    }).join('') + '</div>';
}

async function loadAnnouncements() {
    const target = document.getElementById('targetFilter').value;
    const myOnly = document.getElementById('myOnlyFilter').checked;
    const params = target ? '?target=' + target : '';
    document.getElementById('announcementsList').innerHTML = '<p style="text-align:center;padding:2rem;color:var(--text-secondary);">Loading...</p>';

    try {
        const res  = await fetch('../../api/hr/get_announcements_manage.php' + params);
        const data = await res.json();

        if (!data.success) { document.getElementById('announcementsList').innerHTML = '<div class="empty-state">Failed to load announcements.</div>'; return; }

        let list = data.announcements;
        if (myOnly) list = list.filter(a => a.can_edit);

        const container = document.getElementById('announcementsList');
        if (!list.length) { container.innerHTML = '<div class="empty-state">No announcements found.</div>'; return; }

        const audienceMap = { all:'Everyone', teachers:'Teachers', registrar:'Registrars', staff:'Staff', admin:'Admins', students:'Students' };
        container.innerHTML = list.map(a => {
            const priority     = a.priority || 'low';
            const audienceLabel = audienceMap[a.target_audience] || a.target_audience;
            const ownBadge     = a.can_edit ? '<span class="badge-own">✏️ Your post</span>' : '';
            const editBtns     = a.can_edit
                ? '<div class="ann-actions"><button class="btn-edit" onclick=\'editAnnouncement(' + JSON.stringify(a) + ')\'>✏️ Edit</button><button class="btn-delete" onclick="deleteAnnouncement(' + a.id + ', \'' + esc(a.title).replace(/'/g, "\\'") + '\')">🗑️ Delete</button></div>'
                : '';
            return '<div class="ann-card priority-' + priority + '">' +
                '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">' +
                    '<div style="flex:1;"><h3 style="margin:0;font-size:1.05rem;">' + esc(a.title) + '</h3>' +
                    '<div class="ann-meta"><span>📌 ' + priority.charAt(0).toUpperCase() + priority.slice(1) + ' Priority</span>' +
                    '<span>👤 ' + esc(a.posted_by_name) + '</span><span>🕒 ' + esc(a.date) + '</span></div></div>' +
                    '<div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.3rem;flex-shrink:0;">' +
                    '<span class="badge-audience">' + audienceLabel + '</span>' + ownBadge + '</div></div>' +
                '<p class="ann-body">' + esc(a.content) + '</p>' +
                renderAttachments(a.attachments) +
                editBtns + '</div>';
        }).join('');
    } catch(err) {
        document.getElementById('announcementsList').innerHTML = '<div class="empty-state">Failed to load announcements.</div>';
    }
}

function openAddModal() {
    document.getElementById('modalTitle').textContent              = 'Post Announcement';
    document.getElementById('announcementId').value               = '';
    document.getElementById('annTitle').value                     = '';
    document.getElementById('annContent').value                   = '';
    document.getElementById('annTarget').value                    = 'all';
    document.getElementById('annPriority').value                  = 'medium';
    document.getElementById('existingAttachments').innerHTML      = '';
    document.getElementById('filePreview').innerHTML              = '';
    document.getElementById('saveBtn').textContent                = '📤 Post';
    selectedFiles = [];
    document.getElementById('announcementModal').classList.add('active');
}

function editAnnouncement(a) {
    document.getElementById('modalTitle').textContent  = 'Edit Announcement';
    document.getElementById('announcementId').value   = a.id;
    document.getElementById('annTitle').value         = a.title;
    document.getElementById('annContent').value       = a.content;
    document.getElementById('annTarget').value        = a.target_audience;
    document.getElementById('annPriority').value      = a.priority;
    document.getElementById('filePreview').innerHTML  = '';
    document.getElementById('saveBtn').textContent    = '💾 Save Changes';
    selectedFiles = [];

    const existingDiv = document.getElementById('existingAttachments');
    if (a.attachments && a.attachments.length) {
        existingDiv.innerHTML = a.attachments.map(att => {
            const src = '../uploads/announcements/' + att.path.split('/').pop();
            const preview = att.type === 'image' ? '<img src="' + src + '" alt="' + esc(att.original_name) + '">' : '<span>' + getFileIcon(att.original_name) + '</span>';
            return '<div class="existing-attachment">' + preview + '<span style="flex:1;">' + esc(att.original_name) + '</span></div>';
        }).join('');
    } else {
        existingDiv.innerHTML = '';
    }
    document.getElementById('announcementModal').classList.add('active');
}

function closeModal() { document.getElementById('announcementModal').classList.remove('active'); }

async function saveAnnouncement(e) {
    e.preventDefault();
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    const annId = document.getElementById('announcementId').value;
    btn.textContent = '⏳ Saving...';

    const formData = new FormData();
    if (annId) formData.append('announcement_id', annId);
    formData.append('title',           document.getElementById('annTitle').value);
    formData.append('content',         document.getElementById('annContent').value);
    formData.append('target_audience', document.getElementById('annTarget').value);
    formData.append('priority',        document.getElementById('annPriority').value);
    selectedFiles.forEach(f => formData.append('attachments[]', f));

    try {
        const res  = await fetch('../../api/hr/save_announcement.php', { method:'POST', body:formData });
        const data = await res.json();
        if (data.success) { showToast(data.message, 'success'); closeModal(); loadAnnouncements(); }
        else showToast(data.message || 'Failed to save.', 'error');
    } catch(err) {
        showToast('Network error. Please try again.', 'error');
    }
    btn.disabled = false;
    btn.textContent = annId ? '💾 Save Changes' : '📤 Post';
}

async function deleteAnnouncement(id, title) {
    if (!confirm('Delete "' + title + '"? This cannot be undone.')) return;
    const res  = await fetch('../../api/hr/delete_announcement.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ announcement_id: id })
    });
    const data = await res.json();
    if (data.success) { showToast(data.message, 'success'); loadAnnouncements(); }
    else showToast(data.message || 'Failed to delete.', 'error');
}

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.className = 'toast ' + type; t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3500);
}
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

loadAnnouncements();
</script>
<script>
(function() {
    var sidebar = document.querySelector('.sidebar');
    var saved = sessionStorage.getItem('sidebarScroll');
    if (saved) sidebar.scrollTop = parseInt(saved);
    document.querySelectorAll('.nav-item').forEach(function(link) {
        link.addEventListener('click', function() { sessionStorage.setItem('sidebarScroll', sidebar.scrollTop); });
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
<script src="../../../public/js/pwa.js"></script>

<!-- Mobile Bottom Navigation -->
    <script src="../../../public/js/session-monitor.js"></script>
    <script src="../../../public/js/apply-branding.js"></script>

    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
      <a href="dashboard.php" class="mobile-nav-item" data-page="dashboard">
        <span class="mobile-nav-icon">📊</span><span>Home</span>
      </a>
      <a href="employees.php" class="mobile-nav-item" data-page="employees">
        <span class="mobile-nav-icon">👤</span><span>Staff</span>
      </a>
      <a href="attendance.php" class="mobile-nav-item" data-page="attendance">
        <span class="mobile-nav-icon">🕐</span><span>Attend.</span>
      </a>
      <a href="leaves.php" class="mobile-nav-item" data-page="leaves">
        <span class="mobile-nav-icon">📅</span><span>Leaves</span>
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
