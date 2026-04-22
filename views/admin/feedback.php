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
    <title>Feedback - Admin Dashboard</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: var(--radius-lg); max-width: 650px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-lg); }
        .modal-content h2 { margin: 0 0 1.5rem; }
        .filter-bar { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .filter-bar select, .filter-bar input {
            padding: 0.6rem 1rem; border: 1.5px solid #e5e7eb;
            border-radius: var(--radius-md); font-size: 0.9rem; font-family: inherit; background: white;
        }
        .filter-bar input { flex: 1; min-width: 180px; }
        .feedback-list { display: flex; flex-direction: column; gap: 1rem; padding: 1rem; }
        .feedback-item {
            background: var(--background-main);
            border-radius: var(--radius-md);
            padding: 1.25rem 1.5rem;
            cursor: pointer;
            border-left: 4px solid var(--primary-purple);
            transition: box-shadow 0.2s;
        }
        .feedback-item:hover { box-shadow: var(--shadow-md); }
        .feedback-item.resolved    { border-left-color: #10b981; }
        .feedback-item.in_progress { border-left-color: #f59e0b; }
        .feedback-item-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.5rem; }
        .feedback-subject { font-weight: 700; font-size: 1rem; }
        .feedback-meta { font-size: 0.82rem; color: var(--text-secondary); display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.5rem; }
        .feedback-preview { font-size: 0.875rem; color: var(--text-secondary); }
        .status-badge { display: inline-block; padding: 0.2rem 0.65rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; white-space: nowrap; }
        .badge-pending     { background: #fef3c7; color: #92400e; }
        .badge-in_progress { background: #dbeafe; color: #1e40af; }
        .badge-resolved    { background: #d1fae5; color: #065f46; }
        .role-badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.72rem; font-weight: 600; background: rgba(91,78,155,0.12); color: var(--primary-purple); }
        .detail-row { margin-bottom: 0.75rem; font-size: 0.9rem; }
        .detail-row strong { display: inline-block; min-width: 110px; color: var(--text-primary); }
        .detail-message { margin-top: 0.5rem; padding: 1rem; background: var(--background-main); border-radius: var(--radius-md); font-size: 0.9rem; line-height: 1.6; }
        .response-box { margin-top: 1rem; }
        .response-box textarea { width: 100%; padding: 0.75rem; border: 1.5px solid #e5e7eb; border-radius: var(--radius-md); font-family: inherit; font-size: 0.9rem; resize: vertical; min-height: 100px; box-sizing: border-box; }
        .response-box textarea:focus { outline: none; border-color: var(--primary-purple); }
        .modal-actions { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .modal-actions button { flex: 1; padding: 0.75rem; border-radius: var(--radius-md); font-size: 0.95rem; font-weight: 600; cursor: pointer; border: none; }
        .btn-resolve { background: #10b981; color: white; }
        .btn-respond { background: var(--primary-purple); color: white; }
        .btn-close   { background: #e5e7eb; color: var(--text-primary); }
        .empty-state { text-align: center; padding: 3rem; color: var(--text-secondary); }
        .empty-state span { font-size: 3rem; display: block; margin-bottom: 1rem; }
        .toast { position: fixed; bottom: 2rem; right: 2rem; padding: 1rem 1.5rem; border-radius: var(--radius-md); color: white; font-weight: 600; z-index: 9999; display: none; }
        .toast.success { background: #10b981; }
        .toast.error   { background: #ef4444; }
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
                    <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>Announcements</span></a>
                    <a href="audit_logs.php" class="nav-item"><span class="nav-icon">📋</span><span>Audit Logs</span></a>
                    <a href="recycle_bin.php" class="nav-item"><span class="nav-icon">🗑️</span><span>Recycle Bin</span></a>
                    <a href="feedback.php" class="nav-item active"><span class="nav-icon">💬</span><span>Feedback</span></a>
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
                    <h1>Feedback</h1>
                <p class="page-subtitle">All feedback submitted by students and teachers</p>
            </div>
        </header>

        <div class="stats-grid" style="margin-bottom:2rem;">
            <div class="stat-card purple"><div class="stat-header"><div class="stat-icon">📬</div></div><div class="stat-label">Total</div><div class="stat-value" id="statTotal">0</div></div>
            <div class="stat-card yellow"><div class="stat-header"><div class="stat-icon">⏳</div></div><div class="stat-label">Pending</div><div class="stat-value" id="statPending">0</div></div>
            <div class="stat-card green"><div class="stat-header"><div class="stat-icon">✅</div></div><div class="stat-label">Resolved</div><div class="stat-value" id="statResolved">0</div></div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <div class="filter-bar">
                    <input type="text" id="searchInput" placeholder="🔍 Search subject or sender..." oninput="filterFeedback()">
                    <select id="statusFilter" onchange="filterFeedback()">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>
                    <select id="roleFilter" onchange="filterFeedback()">
                        <option value="">All Roles</option>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                    </select>
                </div>
            </div>
            <div id="feedbackList"><p style="text-align:center;color:var(--text-secondary);padding:2rem;">Loading...</p></div>
        </div>
    </main>
</div>

<!-- Detail Modal -->
<div id="feedbackModal" class="modal">
    <div class="modal-content">
        <h2>💬 Feedback Details</h2>
        <div id="feedbackDetails"></div>
        <div class="response-box">
            <label style="font-weight:600;font-size:0.875rem;display:block;margin-bottom:0.5rem;">Response (optional)</label>
            <textarea id="responseText" placeholder="Write a response to this feedback..."></textarea>
        </div>
        <div class="modal-actions">
            <button class="btn-respond" onclick="sendResponse()">📩 Send Response</button>
            <button class="btn-resolve" id="resolveBtn" onclick="markResolved()">✅ Mark Resolved</button>
            <button class="btn-close" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>
<div id="feedbackLightbox" onclick="closeLightbox()" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out;">
    <img id="feedbackLightboxImg" src="" alt="" style="max-width:90%;max-height:90vh;border-radius:8px;object-fit:contain;box-shadow:0 4px 32px rgba(0,0,0,0.5);">
</div>

<script>
let allFeedback = [];
let currentId = null;

function getFileIcon(name) {
    const ext = (name||'').split('.').pop().toLowerCase();
    if (ext === 'pdf') return '📄';
    if (['doc','docx'].includes(ext)) return '📝';
    if (['xls','xlsx'].includes(ext)) return '📊';
    if (['zip','rar'].includes(ext)) return '🗜️';
    return '📁';
}

function openLightbox(src) {
    const lb = document.getElementById('feedbackLightbox');
    document.getElementById('feedbackLightboxImg').src = src;
    lb.style.display = 'flex';
}
function closeLightbox() {
    document.getElementById('feedbackLightbox').style.display = 'none';
    document.getElementById('feedbackLightboxImg').src = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

function renderFeedbackAttachments(attachments) {
    if (!attachments || !attachments.length) return '';
    const items = attachments.map(att => {
        const src = `../uploads/feedback/${att.path.split('/').pop()}`;
        if (att.type === 'image') return `<img src="${src}" alt="${esc(att.original_name)}" onclick="openLightbox('${src}')" style="max-width:100%;max-height:220px;border-radius:8px;object-fit:contain;cursor:zoom-in;display:block;margin-top:0.4rem;background:#f3f4f6;">`;
        if (att.type === 'video') return `<video src="${src}" controls style="max-width:100%;max-height:220px;border-radius:8px;display:block;margin-top:0.4rem;"></video>`;
        return `<a href="${src}" target="_blank" download="${esc(att.original_name)}" style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.4rem 0.85rem;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;text-decoration:none;color:var(--text-primary);font-size:0.82rem;margin-top:0.4rem;">${getFileIcon(att.original_name)} ${esc(att.original_name)}</a>`;
    }).join('');
    return `<div class="detail-row"><strong>Attachments:</strong><div style="margin-top:0.25rem;">${items}</div></div>`;
}

async function loadFeedback() {
    const res = await fetch('../../api/admin/get_all_feedback.php');
    const data = await res.json();
    if (!data.success) return;
    allFeedback = data.feedback;
    document.getElementById('statTotal').textContent   = data.stats.total   || 0;
    document.getElementById('statPending').textContent = data.stats.pending  || 0;
    document.getElementById('statResolved').textContent= data.stats.resolved || 0;
    filterFeedback();
}

function filterFeedback() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const role   = document.getElementById('roleFilter').value;
    const filtered = allFeedback.filter(f => {
        const matchSearch = !search || f.subject.toLowerCase().includes(search) || f.submitted_by.toLowerCase().includes(search);
        const matchStatus = !status || f.status === status;
        const matchRole   = !role   || f.role   === role;
        return matchSearch && matchStatus && matchRole;
    });
    renderFeedback(filtered);
}

function renderFeedback(list) {
    const container = document.getElementById('feedbackList');
    if (list.length === 0) {
        container.innerHTML = `<div class="empty-state"><span>💬</span>No feedback found.</div>`;
        return;
    }
    container.innerHTML = '<div class="feedback-list">' + list.map(f => `
        <div class="feedback-item ${f.status}" onclick="openModal(${f.id})">
            <div class="feedback-item-header">
                <span class="feedback-subject">${esc(f.subject)}</span>
                <span class="status-badge badge-${f.status}">${f.status.replace('_',' ')}</span>
            </div>
            <div class="feedback-meta">
                <span>👤 ${esc(f.submitted_by)}</span>
                <span class="role-badge">${esc(f.role)}</span>
                <span>📅 ${f.date}</span>
            </div>
            <p class="feedback-preview">${esc(f.message.substring(0,160))}${f.message.length>160?'...':''}</p>
        </div>`).join('') + '</div>';
}

function openModal(id) {
    currentId = id;
    const f = allFeedback.find(x => x.id === id);
    if (!f) return;
    const replyHtml = f.user_reply ? `
        <div class="detail-row">
            <strong>User Reply:</strong>
            <div class="detail-message" style="background:#eff6ff;border-left:3px solid #3b82f6;color:#1e40af;">
                ${esc(f.user_reply)}
            </div>
        </div>` : '';
    document.getElementById('feedbackDetails').innerHTML = `
        <div class="detail-row"><strong>From:</strong> ${esc(f.submitted_by)} <span class="role-badge">${esc(f.role)}</span></div>
        <div class="detail-row"><strong>Subject:</strong> ${esc(f.subject)}</div>
        <div class="detail-row"><strong>Date:</strong> ${f.date}</div>
        <div class="detail-row"><strong>Status:</strong> <span class="status-badge badge-${f.status}">${f.status.replace('_',' ')}</span></div>
        <div class="detail-row"><strong>Message:</strong><div class="detail-message">${esc(f.message)}</div></div>
        ${renderFeedbackAttachments(f.attachments)}
        ${f.response ? `<div class="detail-row"><strong>Response:</strong><div class="detail-message" style="background:#f0fdf4;border-left:3px solid #10b981;">${esc(f.response)}</div></div>` : ''}
        ${replyHtml}
    `;
    document.getElementById('responseText').value = f.response || '';
    // Show resolve if not resolved; show confirm-solved prominently if user replied
    const resolveBtn = document.getElementById('resolveBtn');
    resolveBtn.style.display = f.status === 'resolved' ? 'none' : 'block';
    if (f.user_reply && f.status !== 'resolved') {
        resolveBtn.textContent = '✅ Confirm Solved';
        resolveBtn.style.background = '#10b981';
    } else {
        resolveBtn.textContent = '✅ Mark Resolved';
        resolveBtn.style.background = '#10b981';
    }
    document.getElementById('feedbackModal').classList.add('active');
}

async function sendResponse() {
    const response = document.getElementById('responseText').value.trim();
    if (!response) { showToast('Please write a response first.', 'error'); return; }
    const res = await fetch('../../api/admin/respond_feedback.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ feedback_id: currentId, response })
    });
    const data = await res.json();
    if (data.success) { showToast('Response sent!', 'success'); closeModal(); loadFeedback(); }
    else showToast(data.message || 'Error sending response.', 'error');
}

async function markResolved() {
    const res = await fetch('../../api/admin/respond_feedback.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ feedback_id: currentId, status: 'resolved', response: document.getElementById('responseText').value.trim() || null })
    });
    const data = await res.json();
    if (data.success) { showToast('Marked as resolved!', 'success'); closeModal(); loadFeedback(); }
    else showToast(data.message || 'Error.', 'error');
}

function closeModal() {
    document.getElementById('feedbackModal').classList.remove('active');
    currentId = null;
}

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.className = `toast ${type}`; t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 3500);
}

loadFeedback();
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

<script src="../../js/enhancements.js"></script>
</body>
</html>
