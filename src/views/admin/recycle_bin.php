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
    <title>Recycle Bin - Admin Dashboard</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        .tab-bar { display:flex; border-bottom:2px solid #e5e7eb; margin-bottom:1.5rem; }
        .tab-btn {
            padding:.65rem 1.4rem; border:none; background:none;
            font-size:.95rem; font-weight:600; color:var(--text-secondary,#6b7280);
            cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px;
            transition:color .2s, border-color .2s;
        }
        .tab-btn.active { color:var(--primary,#4f46e5); border-bottom-color:var(--primary,#4f46e5); }
        .tab-count {
            display:inline-block; background:#e5e7eb; color:#374151;
            font-size:.72rem; font-weight:700; border-radius:999px;
            padding:0 .5rem; margin-left:.35rem;
        }
        .tab-btn.active .tab-count { background:var(--primary,#4f46e5); color:#fff; }

        .recycle-empty {
            text-align:center; padding:3.5rem 2rem; color:var(--text-secondary);
        }
        .recycle-empty .icon { font-size:3.5rem; margin-bottom:1rem; }

        .recycle-item {
            display:flex; align-items:center; gap:1rem;
            padding:1rem 1.2rem; border:1px solid #e5e7eb;
            border-radius:var(--radius-md); margin-bottom:.75rem;
            background:#fff; transition:box-shadow .15s;
        }
        .recycle-item:hover { box-shadow:0 2px 8px rgba(0,0,0,.08); }
        .recycle-icon { font-size:2rem; flex-shrink:0; }
        .recycle-info { flex:1; min-width:0; }
        .recycle-info strong { display:block; font-size:.95rem; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .recycle-info .meta { font-size:.78rem; color:var(--text-secondary); margin-top:.2rem; }
        .recycle-info .deleted-when { font-size:.75rem; color:#9ca3af; margin-top:.15rem; }
        .recycle-actions { display:flex; gap:.4rem; flex-shrink:0; }

        .type-badge {
            display:inline-block; font-size:.68rem; font-weight:700;
            border-radius:999px; padding:.1rem .55rem; margin-right:.3rem;
        }
        .type-announcement { background:#dbeafe; color:#1d4ed8; }
        .type-grade_sheet  { background:#dcfce7; color:#15803d; }
        .type-avatar       { background:#fef3c7; color:#92400e; }

        .warning-banner {
            background:#fef9c3; border:1px solid #fde047;
            padding:.75rem 1rem; border-radius:var(--radius-md);
            margin-bottom:1.25rem; font-size:.875rem; color:#713f12;
        }

        #toast {
            position:fixed; bottom:2rem; right:2rem;
            padding:.85rem 1.5rem; border-radius:var(--radius-md);
            color:#fff; font-weight:600; font-size:.95rem;
            z-index:9999; display:none; box-shadow:0 4px 16px rgba(0,0,0,.18);
        }

        .search-bar { padding:.5rem .85rem; border:1px solid #ddd; border-radius:var(--radius-md); width:100%; max-width:360px; font-size:.9rem; }
        .toolbar { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:1.25rem; }
    </style>
</head>
<body>
<div id="toast"></div>
<div class="page-wrapper">
                <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <img src="../../../public/images/logo2.jpg" alt="SCC Logo" id="sidebarLogoImg" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-md);">
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
                    <a href="recycle_bin.php" class="nav-item active"><span class="nav-icon">🗑️</span><span>Recycle Bin</span></a>
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
                    <h1>🗑️ Recycle Bin</h1>
                <p class="page-subtitle">Recover or permanently delete files and records</p>
            </div>
            <div class="header-actions">
                <button class="btn" onclick="emptyBin()" style="background:#ef4444;color:#fff;">🗑️ Empty Bin</button>
            </div>
        </header>

        <div class="content-card">
            <div class="tab-bar">
                <button class="tab-btn active" id="tabAll"           onclick="switchTab('all')">
                    All <span class="tab-count" id="cntAll">—</span>
                </button>
                <button class="tab-btn" id="tabAnnouncement"         onclick="switchTab('announcement')">
                    📢 Announcements <span class="tab-count" id="cntAnnouncement">0</span>
                </button>
                <button class="tab-btn" id="tabGrade_sheet"          onclick="switchTab('grade_sheet')">
                    📊 Grade Sheets <span class="tab-count" id="cntGrade_sheet">0</span>
                </button>
                <button class="tab-btn" id="tabAvatar"               onclick="switchTab('avatar')">
                    🖼️ Avatars <span class="tab-count" id="cntAvatar">0</span>
                </button>
            </div>

            <div class="toolbar">
                <input class="search-bar" id="searchBin" type="text" placeholder="🔍 Search recycle bin…" oninput="renderItems()">
            </div>

            <div class="warning-banner">
                ⚠️ Items in the recycle bin are <strong>hidden from users</strong> but not permanently deleted.
                Use <strong>Restore</strong> to bring them back, or <strong>Delete Forever</strong> to remove them permanently.
            </div>

            <div id="binContent">
                <div class="recycle-empty"><div class="icon">⏳</div><p>Loading…</p></div>
            </div>
        </div>
    </main>
</div>

<script>
let allItems   = [];
let activeTab  = 'all';

/* ── Toast ── */
function showToast(msg, type='success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = type === 'success' ? '#22c55e' : '#ef4444';
    t.style.display = 'block';
    clearTimeout(t._t);
    t._t = setTimeout(() => t.style.display = 'none', 3500);
}

/* ── Tab ── */
function switchTab(tab) {
    activeTab = tab;
    ['all','announcement','grade_sheet','avatar'].forEach(t => {
        document.getElementById('tab' + cap(t)).classList.toggle('active', t === tab);
    });
    renderItems();
}
function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

/* ── Load ── */
async function loadBin() {
    try {
        const res  = await fetch('../../api/admin/get_recycle_bin.php');
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        allItems = data.items || [];
        updateCounts();
        renderItems();
    } catch(e) {
        document.getElementById('binContent').innerHTML =
            '<div class="recycle-empty"><div class="icon">⚠️</div><p>Failed to load recycle bin.</p></div>';
    }
}

function updateCounts() {
    document.getElementById('cntAll').textContent = allItems.length;
    ['announcement','grade_sheet','avatar'].forEach(t => {
        document.getElementById('cnt' + cap(t)).textContent = allItems.filter(i => i.type === t).length;
    });
}

/* ── Render ── */
const typeIcon  = { announcement:'📢', grade_sheet:'📊', avatar:'🖼️' };
const typeLabel = { announcement:'Announcement', grade_sheet:'Grade Sheet', avatar:'Avatar' };

function renderItems() {
    const search  = document.getElementById('searchBin').value.toLowerCase();
    let filtered  = activeTab === 'all' ? allItems : allItems.filter(i => i.type === activeTab);
    if (search)   filtered = filtered.filter(i =>
        (i.name||'').toLowerCase().includes(search) ||
        (i.meta||'').toLowerCase().includes(search) ||
        (i.file_name||'').toLowerCase().includes(search)
    );

    if (filtered.length === 0) {
        document.getElementById('binContent').innerHTML = `
            <div class="recycle-empty">
                <div class="icon">🗑️</div>
                <p>${activeTab === 'all' ? 'The recycle bin is empty.' : 'No deleted ' + typeLabel[activeTab] + 's found.'}</p>
            </div>`;
        return;
    }

    let html = '';
    filtered.forEach(item => {
        const safeId   = JSON.stringify(item.id).replace(/</g,'&lt;');
        const safeItem = JSON.stringify(item).replace(/</g,'&lt;').replace(/"/g,'&quot;');
        html += `
        <div class="recycle-item" id="item-${encodeURIComponent(item.id)}">
            <div class="recycle-icon">${typeIcon[item.type] || '📄'}</div>
            <div class="recycle-info">
                <strong>
                    <span class="type-badge type-${item.type}">${typeLabel[item.type]}</span>
                    ${escHtml(item.name)}
                </strong>
                <div class="meta">${escHtml(item.meta || '')}${item.file_name ? ' | 📎 ' + escHtml(item.file_name) : ''}${item.file_size ? ' (' + item.file_size + ')' : ''}</div>
                <div class="deleted-when">🕐 Deleted: ${escHtml(item.deleted_at_fmt || '—')}</div>
            </div>
            <div class="recycle-actions">
                <button class="btn btn-sm" onclick='restoreItem(${safeItem})' style="background:var(--status-approved);color:#fff;">♻️ Restore</button>
                <button class="btn btn-sm" onclick='deleteForever(${safeItem})' style="background:#ef4444;color:#fff;">🗑️ Delete Forever</button>
            </div>
        </div>`;
    });
    document.getElementById('binContent').innerHTML = html;
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Restore ── */
async function restoreItem(item) {
    if (!confirm(`Restore "${item.name}"?`)) return;
    try {
        const res    = await fetch('../../api/admin/restore_recycle_item.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ type: item.type, id: item.id, user_id: item.user_id || null })
        });
        const result = await res.json();
        if (result.success) { showToast(result.message); loadBin(); }
        else showToast(result.message, 'error');
    } catch(e) { showToast('Restore failed.', 'error'); }
}

/* ── Delete Forever ── */
async function deleteForever(item) {
    if (!confirm(`⚠️ Permanently delete "${item.name}"?\n\nThis CANNOT be undone.`)) return;
    try {
        const res    = await fetch('../../api/admin/permanent_delete_recycle_item.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ type: item.type, id: item.id })
        });
        const result = await res.json();
        if (result.success) { showToast(result.message); loadBin(); }
        else showToast(result.message, 'error');
    } catch(e) { showToast('Delete failed.', 'error'); }
}

/* ── Empty Bin ── */
async function emptyBin() {
    const tab     = activeTab === 'all' ? 'entire recycle bin' : 'all ' + typeLabel[activeTab] + 's';
    const visible = activeTab === 'all' ? allItems : allItems.filter(i => i.type === activeTab);
    if (visible.length === 0) { showToast('Nothing to delete.'); return; }
    if (!confirm(`⚠️ Permanently delete ALL items in ${tab}?\n\nThis CANNOT be undone.`)) return;

    let ok = 0, fail = 0;
    for (const item of visible) {
        try {
            const res    = await fetch('../../api/admin/permanent_delete_recycle_item.php', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ type: item.type, id: item.id })
            });
            const result = await res.json();
            if (result.success) ok++; else fail++;
        } catch(e) { fail++; }
    }
    showToast(`Done: ${ok} deleted${fail ? ', ' + fail + ' failed' : ''}.`, fail ? 'error' : 'success');
    loadBin();
}

/* ── Init ── */
loadBin();

/* ── Sidebar scroll ── */
(function() {
    var sidebar = document.querySelector('.sidebar');
    var saved = sessionStorage.getItem('sidebarScroll');
    if (saved) sidebar.scrollTop = parseInt(saved);
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
<script src="../../../public/js/pwa.js"></script>

<!-- Mobile Bottom Navigation -->
    <script src="../../../public/js/session-monitor.js"></script>
    <script src="../../../public/js/apply-branding.js"></script>

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

</body>
</html>
