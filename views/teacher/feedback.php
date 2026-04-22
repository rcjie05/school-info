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
    <title>Feedback - Teacher Portal</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
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
        .feedback-item.resolved    { border-left-color: #10b981; }
        .feedback-item.in_progress { border-left-color: #f59e0b; }
        .feedback-item-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.4rem; }
        .feedback-subject { font-weight: 700; font-size: 1rem; color: var(--text-primary); }
        .feedback-date { font-size: 0.8rem; color: var(--text-secondary); white-space: nowrap; }
        .feedback-message { color: var(--text-secondary); font-size: 0.9rem; margin: 0.5rem 0 0; }
        .status-badge { display: inline-block; padding: 0.2rem 0.65rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .badge-pending     { background: #fef3c7; color: #92400e; }
        .badge-in_progress { background: #dbeafe; color: #1e40af; }
        .badge-resolved    { background: #d1fae5; color: #065f46; }
        .response-block { margin-top: 1rem; padding: 0.875rem 1rem; background: #f0fdf4; border-radius: var(--radius-md); border-left: 3px solid #10b981; font-size: 0.875rem; }
        .response-block .block-label { font-weight: 700; color: #065f46; margin-bottom: 0.3rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .response-block .block-text { color: #065f46; }
        .reply-block { margin-top: 1rem; padding: 0.875rem 1rem; background: #eff6ff; border-radius: var(--radius-md); border-left: 3px solid #3b82f6; font-size: 0.875rem; }
        .reply-block .block-label { font-weight: 700; color: #1e40af; margin-bottom: 0.3rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .reply-block .block-text { color: #1e40af; }
        .reply-form { margin-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .reply-form textarea { width: 100%; padding: 0.65rem 0.875rem; border: 1.5px solid #d1d5db; border-radius: var(--radius-md); font-family: inherit; font-size: 0.875rem; resize: vertical; min-height: 70px; box-sizing: border-box; }
        .reply-form textarea:focus { outline: none; border-color: var(--primary-purple); }
        .btn-reply { background: var(--primary-purple); color: white; border: none; padding: 0.5rem 1.25rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 600; cursor: pointer; align-self: flex-end; }
        .btn-reply:hover { opacity: 0.9; }
        .btn-reply:disabled { opacity: 0.6; cursor: not-allowed; }
        .empty-state { text-align: center; padding: 3rem; color: var(--text-secondary); }
        .empty-state span { font-size: 3rem; display: block; margin-bottom: 1rem; }
        .toast { position: fixed; bottom: 2rem; right: 2rem; padding: 1rem 1.5rem; border-radius: var(--radius-md); color: white; font-weight: 600; z-index: 9999; display: none; }
        .toast.success { background: #10b981; }
        .toast.error   { background: #ef4444; }
    </style>
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
                    <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>Announcements</span></a>
                    <a href="feedback.php" class="nav-item active"><span class="nav-icon">💬</span><span>Feedback</span></a>
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
                <textarea id="message" placeholder="Describe your feedback, suggestion, or concern..."></textarea>
            </div>
            <button class="btn-submit" id="submitBtn" onclick="submitFeedback()">📤 Submit Feedback</button>
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
async function submitFeedback() {
    const subject = document.getElementById('subject').value.trim();
    const message = document.getElementById('message').value.trim();
    const btn = document.getElementById('submitBtn');
    if (!subject || !message) { showToast('Please fill in both subject and message.', 'error'); return; }
    btn.disabled = true; btn.textContent = 'Submitting...';
    try {
        const res = await fetch('../../api/teacher/submit_feedback.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ subject, message })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Feedback submitted!', 'success');
            document.getElementById('subject').value = '';
            document.getElementById('message').value = '';
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
        const res = await fetch('../../api/teacher/reply_feedback.php', {
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
        const res = await fetch('../../api/teacher/get_my_feedback.php');
        const data = await res.json();
        if (!data.success || !data.feedback.length) {
            container.innerHTML = `<div class="empty-state"><span>💬</span>No feedback submitted yet.</div>`;
            return;
        }
        container.innerHTML = '<div class="feedback-list">' + data.feedback.map(f => {
            const itemClass = f.status !== 'pending' ? f.status : '';
            // Registrar response block
            const responseHtml = f.response ? `
                <div class="response-block">
                    <div class="block-label">📩 Registrar's Response</div>
                    <div class="block-text">${esc(f.response)}</div>
                </div>` : '';
            // User reply block (already sent)
            const existingReplyHtml = f.user_reply ? `
                <div class="reply-block">
                    <div class="block-label">✏️ Your Reply</div>
                    <div class="block-text">${esc(f.user_reply)}</div>
                </div>` : '';
            // Reply form — only show if registrar responded but user hasn't replied yet, and not resolved
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
