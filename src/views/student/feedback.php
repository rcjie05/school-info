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
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Student Portal</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        .feedback-form-card { background: white; border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-md); margin-bottom: 2rem; }
        .feedback-form-card h2 { margin: 0 0 1.5rem; font-size: 1.25rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem; color: var(--text-primary); }
        .form-group input, .form-group textarea { width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: var(--radius-md); font-size: 0.95rem; font-family: inherit; transition: border-color 0.2s; box-sizing: border-box; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--primary-purple); }
        .form-group textarea { resize: vertical; min-height: 120px; }
        .btn-submit { background: linear-gradient(135deg, var(--primary-purple), var(--secondary-pink)); color: white; border: none; padding: 0.875rem 2rem; border-radius: var(--radius-md); font-size: 1rem; font-weight: 600; cursor: pointer; width: 100%; transition: opacity 0.2s; }
        .btn-submit:hover { opacity: 0.9; }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
        .feedback-list { display: flex; flex-direction: column; gap: 1.25rem; }
        .feedback-item { background: white; border-radius: var(--radius-md); padding: 1.25rem 1.5rem; box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary-purple); }
        .feedback-item.resolved    { border-left-color: var(--secondary-green); }
        .feedback-item.in_progress { border-left-color: var(--secondary-yellow); }
        .feedback-item-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.4rem; }
        .feedback-subject { font-weight: 700; font-size: 1rem; color: var(--text-primary); }
        .feedback-date { font-size: 0.8rem; color: var(--text-secondary); white-space: nowrap; }
        .feedback-message { color: var(--text-secondary); font-size: 0.9rem; margin: 0.5rem 0 0; }
        .status-badge { display: inline-block; padding: 0.2rem 0.65rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .badge-pending     { background: rgba(212,169,106,0.2); color: var(--text-primary); }
        .badge-in_progress { background: rgba(61,107,159,0.15); color: var(--primary-purple-dark); }
        .badge-resolved    { background: rgba(90,158,138,0.15); color: var(--secondary-green); }
        .response-block { margin-top: 1rem; padding: 0.875rem 1rem; background: #f0fdf4; border-radius: var(--radius-md); border-left: 3px solid #10b981; font-size: 0.875rem; }
        .response-block .block-label { font-weight: 700; color: var(--secondary-green); margin-bottom: 0.3rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .response-block .block-text { color: var(--secondary-green); }
        .reply-block { margin-top: 1rem; padding: 0.875rem 1rem; background: #eff6ff; border-radius: var(--radius-md); border-left: 3px solid #3b82f6; font-size: 0.875rem; }
        .reply-block .block-label { font-weight: 700; color: var(--primary-purple-dark); margin-bottom: 0.3rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .reply-block .block-text { color: var(--primary-purple-dark); }
        .reply-form { margin-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .reply-form textarea { width: 100%; padding: 0.65rem 0.875rem; border: 1.5px solid #d1d5db; border-radius: var(--radius-md); font-family: inherit; font-size: 0.875rem; resize: vertical; min-height: 70px; box-sizing: border-box; }
        .reply-form textarea:focus { outline: none; border-color: var(--primary-purple); }
        .btn-reply { background: var(--primary-purple); color: white; border: none; padding: 0.5rem 1.25rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 600; cursor: pointer; align-self: flex-end; }
        .btn-reply:hover { opacity: 0.9; }
        .btn-reply:disabled { opacity: 0.6; cursor: not-allowed; }
        .file-upload-area { border: 2px dashed #d1d5db; border-radius: var(--radius-md); padding: 1.25rem; text-align: center; cursor: pointer; transition: border-color 0.2s, background 0.2s; margin-top: 0.25rem; }
        .file-upload-area:hover, .file-upload-area.dragover { border-color: var(--primary-purple); background: rgba(91,78,155,0.04); }
        .file-upload-area input[type="file"] { display: none; }
        .file-preview { display: flex; flex-wrap: wrap; gap: 0.65rem; margin-top: 0.65rem; }
        .file-preview-item { position: relative; border: 1px solid #e5e7eb; border-radius: var(--radius-md); overflow: hidden; background: #f9fafb; }
        .file-preview-item img, .file-preview-item video { display: block; max-width: 110px; max-height: 80px; object-fit: cover; }
        .file-preview-item .file-icon { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 90px; height: 70px; font-size: 1.75rem; gap: 0.2rem; }
        .file-preview-item .file-name { font-size: 0.68rem; color: var(--text-secondary); max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding: 0.2rem 0.4rem; }
        .file-preview-item .remove-file { position: absolute; top: 3px; right: 3px; background: rgba(220,38,38,0.85); color: white; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .feedback-attachments { margin-top: 0.75rem; display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .feedback-attachments img { max-width: 100%; max-height: 260px; border-radius: var(--radius-md); object-fit: contain; background: #f3f4f6; cursor: zoom-in; display: block; margin-top: 0.5rem; }
        .feedback-attachments video { max-width: 100%; max-height: 260px; border-radius: var(--radius-md); margin-top: 0.5rem; display: block; }
        .feedback-attachments .file-link { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.85rem; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: var(--radius-md); text-decoration: none; color: var(--text-primary); font-size: 0.82rem; margin-top: 0.4rem; }
        .feedback-attachments .file-link:hover { background: var(--border-color); }
        .lightbox { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center; cursor: zoom-out; }
        .lightbox.active { display: flex; }
        .lightbox img { max-width: 92%; max-height: 92vh; border-radius: 8px; object-fit: contain; }
        .empty-state { text-align: center; padding: 3rem; color: var(--text-secondary); }
        .empty-state span { font-size: 3rem; display: block; margin-bottom: 1rem; }
        .toast { position: fixed; bottom: 2rem; right: 2rem; padding: 1rem 1.5rem; border-radius: var(--radius-md); color: white; font-weight: 600; z-index: 9999; display: none; }
        .toast.success { background: var(--secondary-green); }
        .toast.error   { background: var(--secondary-pink); }
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
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                    <a href="faculty.php" class="nav-item"><span class="nav-icon">👨‍🏫</span><span>Faculty Directory</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Support</div>
                    <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>Announcements</span></a>
                    <a href="feedback.php" class="nav-item active"><span class="nav-icon">💬</span><span>Feedback</span></a>
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
                    <h1>Feedback</h1>
                <p class="page-subtitle">Send feedback and follow up with the registrar</p>
            </div>
        </header>

        <div class="feedback-form-card">
            <h2>💬 Submit New Feedback</h2>
            <div class="form-group">
                <label>Subject</label>
                <input type="text" id="subject" placeholder="Brief description..." maxlength="255">
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea id="message" placeholder="Describe your feedback, suggestion, or concern... (e.g. Lost & Found: describe the item, where/when found or lost)"></textarea>
            </div>
            <div class="form-group">
                <label>Attachments <span style="font-weight:400;color:var(--text-secondary);">(optional — images, videos, or files)</span></label>
                <div class="file-upload-area" id="uploadArea" onclick="document.getElementById('attachmentInput').click()" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
                    <input type="file" id="attachmentInput" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" multiple onchange="handleFileSelect(event)">
                    <div style="font-size:1.75rem;margin-bottom:0.35rem;">📎</div>
                    <p style="margin:0;font-size:0.875rem;color:var(--text-secondary);">Click to upload or drag & drop</p>
                    <p style="margin:0.2rem 0 0;font-size:0.75rem;color:var(--text-secondary);">Images, Videos, PDFs, and more</p>
                </div>
                <div class="file-preview" id="filePreview"></div>
            </div>
            <button class="btn-submit" id="submitBtn" onclick="submitFeedback()">📤 Submit Feedback</button>
        </div>

        <!-- Lightbox -->
        <div class="lightbox" id="lightbox" onclick="closeLightbox()">
            <img id="lightboxImg" src="" alt="">
        </div>

        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">📋 My Feedback History</h2>
            </div>
            <div id="feedbackList" style="padding: 1rem;">
                <p style="text-align:center;color:var(--text-secondary);">Loading...</p>
            </div>
        </div>
    </main>
</div>
<div class="toast" id="toast"></div>
<script>
// --- File Upload ---
let selectedFiles = [];

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
    if (!selectedFiles.length) { preview.innerHTML = ''; return; }
    preview.innerHTML = selectedFiles.map((f, i) => {
        const isImage = f.type.startsWith('image/');
        const isVideo = f.type.startsWith('video/');
        const url = URL.createObjectURL(f);
        let media = isImage ? `<img src="${url}" alt="${f.name}">`
                  : isVideo ? `<video src="${url}"></video>`
                  : `<div class="file-icon">${getFileIcon(f.name)}<span style="font-size:0.6rem;">${f.name.split('.').pop().toUpperCase()}</span></div>`;
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
    if (['zip','rar'].includes(ext)) return '🗜️';
    return '📁';
}

// --- Lightbox ---
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('active');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.getElementById('lightboxImg').src = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

// --- Render attachments in feedback history ---
function renderAttachments(attachments) {
    if (!attachments || !attachments.length) return '';
    const items = attachments.map(att => {
        const src = `../uploads/feedback/${att.path.split('/').pop()}`;
        if (att.type === 'image') return `<img src="${src}" alt="${esc(att.original_name)}" onclick="openLightbox('${src}')">`;
        if (att.type === 'video') return `<video src="${src}" controls></video>`;
        return `<a class="file-link" href="${src}" target="_blank" download="${esc(att.original_name)}">${getFileIcon(att.original_name)} ${esc(att.original_name)}</a>`;
    }).join('');
    return `<div class="feedback-attachments">${items}</div>`;
}

// --- Submit ---
async function submitFeedback() {
    const subject = document.getElementById('subject').value.trim();
    const message = document.getElementById('message').value.trim();
    const btn = document.getElementById('submitBtn');
    if (!subject || !message) { showToast('Please fill in both subject and message.', 'error'); return; }
    btn.disabled = true; btn.textContent = 'Submitting...';
    try {
        const formData = new FormData();
        formData.append('subject', subject);
        formData.append('message', message);
        selectedFiles.forEach(f => formData.append('attachments[]', f));

        const res  = await fetch('../../api/student/submit_feedback.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showToast('Feedback submitted!', 'success');
            document.getElementById('subject').value = '';
            document.getElementById('message').value = '';
            selectedFiles = [];
            renderFilePreview();
            loadMyFeedback();
        } else showToast(data.message || 'Failed to submit.', 'error');
    } catch(e) { showToast('Network error. Please try again.', 'error'); }
    btn.disabled = false; btn.textContent = '📤 Submit Feedback';
}

async function sendReply(id, btn) {
    const textarea = document.getElementById('reply-' + id);
    const reply = textarea.value.trim();
    if (!reply) { showToast('Please write a reply first.', 'error'); return; }
    btn.disabled = true; btn.textContent = 'Sending...';
    try {
        const res = await fetch('../../api/student/reply_feedback.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ feedback_id: id, user_reply: reply })
        });
        const data = await res.json();
        if (data.success) { showToast('Reply sent!', 'success'); loadMyFeedback(); }
        else showToast(data.message || 'Failed to send reply.', 'error');
    } catch(e) { showToast('Network error.', 'error'); }
    btn.disabled = false; btn.textContent = '📨 Send Reply';
}

async function loadMyFeedback() {
    const container = document.getElementById('feedbackList');
    try {
        const res  = await fetch('../../api/student/get_my_feedback.php');
        const data = await res.json();
        if (!data.success || !data.feedback.length) {
            container.innerHTML = `<div class="empty-state"><span>💬</span>No feedback submitted yet.</div>`;
            return;
        }
        container.innerHTML = '<div class="feedback-list">' + data.feedback.map(f => {
            const itemClass = f.status !== 'pending' ? f.status : '';
            const responseHtml = f.response ? `
                <div class="response-block">
                    <div class="block-label">📩 Registrar's Response</div>
                    <div class="block-text">${esc(f.response)}</div>
                </div>` : '';
            const existingReplyHtml = f.user_reply ? `
                <div class="reply-block">
                    <div class="block-label">✏️ Your Reply</div>
                    <div class="block-text">${esc(f.user_reply)}</div>
                </div>` : '';
            const replyFormHtml = (f.response && !f.user_reply && f.status !== 'resolved') ? `
                <div class="reply-form">
                    <textarea id="reply-${f.id}" placeholder="Reply to the registrar's response..."></textarea>
                    <button class="btn-reply" onclick="sendReply(${f.id}, this)">📨 Send Reply</button>
                </div>` : '';
            return `
                <div class="feedback-item ${itemClass}">
                    <div class="feedback-item-header">
                        <span class="feedback-subject">${esc(f.subject)}</span>
                        <span class="status-badge badge-${f.status}">${f.status.replace('_',' ')}</span>
                    </div>
                    <div class="feedback-date">📅 ${f.date}</div>
                    <p class="feedback-message">${esc(f.message)}</p>
                    ${renderAttachments(f.attachments)}
                    ${responseHtml}${existingReplyHtml}${replyFormHtml}
                </div>`;
        }).join('') + '</div>';
    } catch(e) { container.innerHTML = '<p style="color:red;text-align:center;">Failed to load feedback.</p>'; }
}

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.className = `toast ${type}`; t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3500);
}
loadMyFeedback();
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
