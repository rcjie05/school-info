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
    <title>Buildings & Rooms - Admin Dashboard</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        /* ── Modals ── */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; }
        .modal.active { display:flex; }
        .modal-content { background:white; padding:2rem; border-radius:var(--radius-lg); max-width:620px; width:90%; max-height:90vh; overflow-y:auto; }
        .modal-content h2 { margin:0 0 1.5rem; font-size:1.25rem; }
        .form-group { margin-bottom:1rem; }
        .form-group label { display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.9rem; }
        .form-group input, .form-group select, .form-group textarea { width:100%; padding:0.65rem 0.85rem; border:1px solid #ddd; border-radius:var(--radius-md); font-size:0.95rem; box-sizing:border-box; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem; }
        .modal-actions { display:flex; gap:0.75rem; margin-top:1.5rem; }
        .modal-actions button { flex:1; }

        /* ── Tabs ── */
        .tab-bar { display:flex; gap:0; border-bottom:2px solid #e0e0e0; margin-bottom:1.5rem; }
        .tab-btn { padding:0.7rem 1.5rem; border:none; background:none; cursor:pointer; font-size:0.95rem; font-weight:500; color:#666; border-bottom:3px solid transparent; margin-bottom:-2px; transition:all .2s; }
        .tab-btn.active { color:var(--primary-color, #8b0000); border-bottom-color:var(--primary-color, #8b0000); font-weight:700; }
        .tab-content { display:none; }
        .tab-content.active { display:block; }

        /* ── Rooms table ── */
        .rooms-toolbar { display:flex; align-items:center; gap:1rem; margin-bottom:1rem; flex-wrap:wrap; }
        .rooms-toolbar select { padding:0.5rem 0.75rem; border:1px solid #ddd; border-radius:var(--radius-md); font-size:0.9rem; }
        .badge-type { display:inline-block; padding:2px 10px; border-radius:20px; font-size:0.75rem; font-weight:600; }
        .badge-Classroom    { background:#dbeafe; color:#1d4ed8; }
        .badge-Administrative { background:#fef9c3; color:#854d0e; }
        .badge-Service      { background:#dcfce7; color:#166534; }
        .badge-Common\ Area { background:#fce7f3; color:#9d174d; }
        .badge-Laboratory   { background:#ede9fe; color:#5b21b6; }
        .badge-Other        { background:#f3f4f6; color:#374151; }
        .floorplan-dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:4px; vertical-align:middle; }
        .color-swatch { display:inline-block; width:18px; height:18px; border-radius:4px; border:1px solid #ccc; vertical-align:middle; margin-right:6px; }
        .color-input-wrap { display:flex; align-items:center; gap:0.5rem; }
        .color-input-wrap input[type=color] { width:42px; height:36px; padding:2px; border:1px solid #ddd; border-radius:6px; cursor:pointer; }
        .color-input-wrap input[type=text] { flex:1; }

        /* ── Stats row ── */
        .stats-mini { display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
        .stat-mini { background:white; border:1px solid #e5e7eb; border-radius:10px; padding:0.75rem 1.25rem; text-align:center; min-width:100px; }
        .stat-mini strong { display:block; font-size:1.4rem; color:var(--primary-color,#8b0000); }
        .stat-mini span { font-size:0.78rem; color:#666; }

        .empty-hint { text-align:center; color:#999; padding:2rem; font-size:0.95rem; }

        /* Room image upload */
        .room-img-preview { width:100%; height:160px; border-radius:10px; object-fit:cover; display:block; border:2px solid #e5e7eb; }
        .room-img-placeholder { width:100%; height:160px; border-radius:10px; border:2px dashed #d1d5db; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#9ca3af; font-size:.9rem; cursor:pointer; transition:.2s; background:#fafafa; }
        .room-img-placeholder:hover { border-color:#8b0000; color:#8b0000; background:#fff5f5; }
        .room-img-placeholder span { font-size:2rem; margin-bottom:.4rem; }
        .img-upload-row { display:flex; gap:.75rem; align-items:flex-start; margin-top:.5rem; }
        .img-upload-row .img-side { flex:1; }
        .img-upload-row .img-actions { display:flex; flex-direction:column; gap:.4rem; }
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
                    <span>Admin Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="users.php" class="nav-item"><span class="nav-icon">👥</span><span>User Management</span></a>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                    <a href="buildings.php" class="nav-item active"><span class="nav-icon">🏢</span><span>Buildings & Rooms</span></a>
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
                    <h1>Buildings & Rooms</h1>
                <p class="page-subtitle">Manage campus buildings and rooms</p>
            </div>
            <div class="header-actions" id="headerActions">
                <button class="btn btn-primary" onclick="openAddBuildingModal()">➕ Add Building</button>
            </div>
        </header>

        <div class="content-card">
            <!-- Tab Bar -->
            <div class="tab-bar">
                <button class="tab-btn active" onclick="switchTab('buildings', this)">🏢 Buildings</button>
                <button class="tab-btn"        onclick="switchTab('rooms', this)">🚪 Rooms</button>
            </div>

            <!-- BUILDINGS TAB -->
            <div id="tab-buildings" class="tab-content active">
                <div id="buildingsTable">
                    <div class="empty-hint">Loading buildings…</div>
                </div>
            </div>

            <!-- ROOMS TAB -->
            <div id="tab-rooms" class="tab-content">
                <div class="rooms-toolbar">
                    <label style="font-weight:600;font-size:.9rem;">Filter by Building:</label>
                    <select id="roomBuildingFilter" onchange="loadRooms()">
                        <option value="">All Buildings</option>
                    </select>
                    <label style="font-weight:600;font-size:.9rem;">Type:</label>
                    <select id="roomTypeFilter" onchange="filterRoomsTable()">
                        <option value="">All Types</option>
                        <option>Classroom</option>
                        <option>Administrative</option>
                        <option>Service</option>
                        <option>Common Area</option>
                        <option>Laboratory</option>
                        <option>Other</option>
                    </select>
                    <input type="text" id="roomSearch" placeholder="🔍 Search rooms…" oninput="filterRoomsTable()"
                        style="padding:.5rem .75rem;border:1px solid #ddd;border-radius:var(--radius-md);font-size:.9rem;flex:1;min-width:140px;">
                </div>
                <div class="stats-mini" id="roomStats"></div>
                <div id="roomsTable">
                    <div class="empty-hint">Loading rooms…</div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- ══════════════════════════════════════════
     BUILDING MODAL
══════════════════════════════════════════ -->
<div id="buildingModal" class="modal">
    <div class="modal-content">
        <h2 id="buildingModalTitle">Add Building</h2>
        <div class="form-group"><label>Building Name *</label><input type="text" id="buildingName" placeholder="e.g. Main Building"></div>
        <div class="form-row">
            <div class="form-group"><label>Building Code *</label><input type="text" id="buildingCode" placeholder="e.g. MAIN"></div>
            <div class="form-group"><label>Location</label><input type="text" id="buildingLocation" placeholder="e.g. Main Campus"></div>
        </div>
        <div class="form-group"><label>Description</label><textarea id="buildingDesc" rows="2" placeholder="Optional description…"></textarea></div>
        <input type="hidden" id="buildingId">
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="saveBuilding()">💾 Save</button>
            <button class="btn" onclick="closeBuildingModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════
     ROOM MODAL
══════════════════════════════════════════ -->
<div id="roomModal" class="modal">
    <div class="modal-content">
        <h2 id="roomModalTitle">Add Room</h2>
        <div class="form-row">
            <div class="form-group" style="grid-column:1/-1">
                <label>Room Name *</label>
                <input type="text" id="roomName" placeholder="e.g. Computer Lab 1">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Building *</label>
                <select id="roomBuilding"></select>
            </div>
            <div class="form-group">
                <label>Floor</label>
                <input type="text" id="roomFloor" placeholder="e.g. 1, 2, Ground">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Room Type</label>
                <select id="roomType">
                    <option>Classroom</option>
                    <option>Administrative</option>
                    <option>Service</option>
                    <option>Common Area</option>
                    <option>Laboratory</option>
                    <option>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Capacity</label>
                <input type="number" id="roomCapacity" placeholder="e.g. 40" min="1">
            </div>
        </div>
        <div class="form-group">
            <label>Floor Plan Color</label>
            <div class="color-input-wrap">
                <input type="color" id="roomColorPicker" value="#85C1E2" oninput="document.getElementById('roomColorText').value=this.value">
                <input type="text"  id="roomColorText"   value="#85C1E2" maxlength="7"
                    oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)) document.getElementById('roomColorPicker').value=this.value"
                    placeholder="#85C1E2">
            </div>
            <div style="display:flex;gap:.5rem;margin-top:.5rem;flex-wrap:wrap;">
                <?php
                $presets = ['#85C1E2'=>'Classroom','#F4D03F'=>'Admin','#7DCEA0'=>'Service','#F1948A'=>'Common','#C39BD3'=>'Lab','#F0B27A'=>'Other'];
                foreach ($presets as $hex => $label): ?>
                <button type="button" onclick="setColor('<?= $hex ?>')"
                    style="background:<?= $hex ?>;border:1px solid #ccc;border-radius:6px;padding:3px 10px;font-size:.78rem;cursor:pointer;">
                    <?= $label ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="form-group">
            <label>Purpose / Description</label>
            <textarea id="roomPurpose" rows="3" placeholder="Describe what this room is used for…" style="width:100%;padding:.65rem .85rem;border:1px solid #ddd;border-radius:var(--radius-md);font-size:.95rem;box-sizing:border-box;resize:vertical;"></textarea>
        </div>

        <div class="form-group" id="roomImageGroup">
            <label>Room Photo</label>
            <div class="img-upload-row">
                <div class="img-side">
                    <div id="roomImgPlaceholder" class="room-img-placeholder" onclick="document.getElementById('roomImageInput').click()">
                        <span>📷</span>Upload Room Photo
                    </div>
                    <img id="roomImgPreview" class="room-img-preview" style="display:none;" alt="Room photo">
                </div>
                <div class="img-actions" id="roomImgActions" style="display:none;">
                    <button type="button" class="btn btn-sm" onclick="document.getElementById('roomImageInput').click()">📷 Change</button>
                    <button type="button" class="btn btn-sm" style="background:#ef4444;color:white;" onclick="removeRoomImage()">🗑️ Remove</button>
                </div>
            </div>
            <input type="file" id="roomImageInput" accept="image/*" style="display:none;" onchange="previewRoomImage(this)">
            <p id="roomImgStatus" style="font-size:.8rem;color:#6b7280;margin-top:.4rem;"></p>
        </div>

        <details style="margin-top:.75rem;">
            <summary style="cursor:pointer;font-weight:600;font-size:.9rem;color:#555;">📐 Floor Plan Position (optional)</summary>
            <div style="margin-top:.75rem;">
                <p style="font-size:.82rem;color:#888;margin-bottom:.75rem;">Set X/Y position and size so this room appears on the visual floor map.</p>
                <div class="form-row-3">
                    <div class="form-group"><label>X Position</label><input type="number" id="roomX" placeholder="e.g. 10"></div>
                    <div class="form-group"><label>Y Position</label><input type="number" id="roomY" placeholder="e.g. 15"></div>
                    <div class="form-group"><label>Width</label><input type="number"  id="roomW" placeholder="e.g. 200"></div>
                </div>
                <div class="form-row" style="max-width:50%">
                    <div class="form-group"><label>Height</label><input type="number" id="roomH" placeholder="e.g. 95"></div>
                </div>
            </div>
        </details>
        <input type="hidden" id="roomId">
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="saveRoom()">💾 Save Room</button>
            <button class="btn" onclick="closeRoomModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
/* ══════════════════════════════════════
   STATE
══════════════════════════════════════ */
let allBuildings = [];
let allRooms     = [];

/* ══════════════════════════════════════
   TABS
══════════════════════════════════════ */
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');

    const hdr = document.getElementById('headerActions');
    if (tab === 'buildings') {
        hdr.innerHTML = '<button class="btn btn-primary" onclick="openAddBuildingModal()">➕ Add Building</button>';
    } else {
        hdr.innerHTML = '<button class="btn btn-primary" onclick="openAddRoomModal()">➕ Add Room</button>';
        loadRooms();
    }
}

/* ══════════════════════════════════════
   BUILDINGS
══════════════════════════════════════ */
async function loadBuildings() {
    const res  = await fetch('../../api/admin/get_buildings.php');
    const data = await res.json();
    if (!data.success) { document.getElementById('buildingsTable').innerHTML = '<div class="empty-hint">Failed to load buildings.</div>'; return; }
    allBuildings = data.buildings;

    // Populate building filter dropdown for rooms tab
    const sel = document.getElementById('roomBuildingFilter');
    sel.innerHTML = '<option value="">All Buildings</option>';
    allBuildings.forEach(b => sel.innerHTML += `<option value="${b.id}">${esc(b.building_name)}</option>`);

    if (!allBuildings.length) {
        document.getElementById('buildingsTable').innerHTML = '<div class="empty-hint">No buildings yet. Click "Add Building" to get started.</div>';
        return;
    }

    let html = `<table class="data-table">
        <thead><tr>
            <th>Building Name</th><th>Code</th><th>Location</th><th>Rooms</th><th>Description</th><th>Actions</th>
        </tr></thead><tbody>`;
    allBuildings.forEach(b => {
        html += `<tr>
            <td><strong>${esc(b.building_name)}</strong></td>
            <td><code style="background:#f3f4f6;padding:2px 8px;border-radius:4px;">${esc(b.building_code)}</code></td>
            <td>${esc(b.location || '—')}</td>
            <td><span style="font-weight:700;color:var(--primary-color,#8b0000)">${b.room_count}</span> rooms</td>
            <td style="color:#666;font-size:.88rem;">${esc(b.description || '—')}</td>
            <td>
                <button class="btn btn-sm" onclick='editBuilding(${JSON.stringify(b)})'>✏️ Edit</button>
                <button class="btn btn-sm" onclick="viewRooms(${b.id})" style="background:#3b82f6;color:white;margin-left:4px;">🚪 Rooms</button>
                <button class="btn btn-sm" onclick="deleteBuilding(${b.id},'${esc(b.building_name)}')" style="background:var(--status-rejected,#ef4444);color:white;margin-left:4px;">🗑️</button>
            </td>
        </tr>`;
    });
    html += '</tbody></table>';
    document.getElementById('buildingsTable').innerHTML = html;
}

function viewRooms(buildingId) {
    document.getElementById('roomBuildingFilter').value = buildingId;
    switchTab('rooms', document.querySelectorAll('.tab-btn')[1]);
    loadRooms();
}

function openAddBuildingModal() {
    document.getElementById('buildingModalTitle').textContent = 'Add Building';
    document.getElementById('buildingId').value   = '';
    document.getElementById('buildingName').value = '';
    document.getElementById('buildingCode').value = '';
    document.getElementById('buildingLocation').value = '';
    document.getElementById('buildingDesc').value = '';
    document.getElementById('buildingModal').classList.add('active');
}

function editBuilding(b) {
    document.getElementById('buildingModalTitle').textContent  = 'Edit Building';
    document.getElementById('buildingId').value       = b.id;
    document.getElementById('buildingName').value     = b.building_name;
    document.getElementById('buildingCode').value     = b.building_code;
    document.getElementById('buildingLocation').value = b.location  || '';
    document.getElementById('buildingDesc').value     = b.description || '';
    document.getElementById('buildingModal').classList.add('active');
}

function closeBuildingModal() { document.getElementById('buildingModal').classList.remove('active'); }

async function saveBuilding() {
    const name = document.getElementById('buildingName').value.trim();
    const code = document.getElementById('buildingCode').value.trim();
    if (!name || !code) { alert('Building name and code are required.'); return; }

    const payload = {
        building_id:   document.getElementById('buildingId').value || null,
        building_name: name,
        building_code: code,
        location:      document.getElementById('buildingLocation').value.trim(),
        description:   document.getElementById('buildingDesc').value.trim()
    };

    const res    = await fetch('../../api/admin/save_building.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    const result = await res.json();
    if (result.success) { closeBuildingModal(); loadBuildings(); } 
    else { alert('Error: ' + result.message); }
}

async function deleteBuilding(id, name) {
    if (!confirm(`Delete building "${name}"? All associated rooms will also be removed.`)) return;
    const res    = await fetch('../../api/admin/delete_building.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({building_id:id}) });
    const result = await res.json();
    if (result.success) { loadBuildings(); loadRooms(); }
    else { alert('Error: ' + result.message); }
}

/* ══════════════════════════════════════
   ROOMS
══════════════════════════════════════ */
async function loadRooms() {
    const bid = document.getElementById('roomBuildingFilter').value;
    const url = '../../api/admin/get_rooms.php' + (bid ? `?building_id=${bid}` : '');
    const res  = await fetch(url);
    const data = await res.json();
    if (!data.success) return;
    allRooms = data.rooms;
    filterRoomsTable();

    // Stats
    const types = {};
    allRooms.forEach(r => { types[r.room_type] = (types[r.room_type]||0)+1; });
    const onMap = allRooms.filter(r=>r.on_floorplan).length;
    let statsHtml = `<div class="stat-mini"><strong>${allRooms.length}</strong><span>Total Rooms</span></div>
        <div class="stat-mini"><strong>${onMap}</strong><span>On Floor Map</span></div>`;
    Object.entries(types).forEach(([t,c]) => {
        statsHtml += `<div class="stat-mini"><strong>${c}</strong><span>${t}</span></div>`;
    });
    document.getElementById('roomStats').innerHTML = statsHtml;
}

function filterRoomsTable() {
    const typeFilter  = document.getElementById('roomTypeFilter').value.toLowerCase();
    const searchQuery = document.getElementById('roomSearch').value.toLowerCase();
    const filtered    = allRooms.filter(r => {
        const matchType   = !typeFilter   || r.room_type.toLowerCase() === typeFilter;
        const matchSearch = !searchQuery  || r.room_number.toLowerCase().includes(searchQuery) || r.building_name.toLowerCase().includes(searchQuery);
        return matchType && matchSearch;
    });
    renderRoomsTable(filtered);
}

function renderRoomsTable(rooms) {
    if (!rooms.length) {
        document.getElementById('roomsTable').innerHTML = '<div class="empty-hint">No rooms found. Click "Add Room" to create one.</div>';
        return;
    }
    let html = `<table class="data-table">
        <thead><tr>
            <th>Room Name</th><th>Building</th><th>Floor</th><th>Type</th><th>Capacity</th><th>Color</th><th>Floor Map</th><th>Actions</th>
        </tr></thead><tbody>`;
    rooms.forEach(r => {
        const typeClass = r.room_type.replace(' ','_');
        const fpBadge   = r.on_floorplan
            ? `<span style="color:#166534;font-weight:600;">✅ Yes</span>`
            : `<span style="color:#9ca3af;">—</span>`;
        html += `<tr>
            <td><strong>${esc(r.room_number)}</strong></td>
            <td>${esc(r.building_name)}</td>
            <td>${esc(r.floor || '—')}</td>
            <td><span class="badge-type badge-${esc(r.room_type)}">${esc(r.room_type)}</span></td>
            <td>${r.capacity ? r.capacity + ' pax' : '—'}</td>
            <td><span class="color-swatch" style="background:${esc(r.color)};"></span>${esc(r.color)}</td>
            <td>${fpBadge}</td>
            <td>
                <button class="btn btn-sm" onclick='editRoom(${JSON.stringify(r)})'>✏️ Edit</button>
                <button class="btn btn-sm" onclick="deleteRoom(${r.id},'${esc(r.room_number)}')" style="background:var(--status-rejected,#ef4444);color:white;margin-left:4px;">🗑️</button>
            </td>
        </tr>`;
    });
    html += '</tbody></table>';
    document.getElementById('roomsTable').innerHTML = html;
}

/* ── Room Modal ── */
function populateBuildingSelect(selectedId) {
    const sel = document.getElementById('roomBuilding');
    sel.innerHTML = '';
    allBuildings.forEach(b => {
        const opt = document.createElement('option');
        opt.value       = b.id;
        opt.textContent = b.building_name;
        if (b.id == selectedId) opt.selected = true;
        sel.appendChild(opt);
    });
}

function openAddRoomModal() {
    document.getElementById('roomModalTitle').textContent = 'Add Room';
    document.getElementById('roomId').value       = '';
    document.getElementById('roomName').value     = '';
    document.getElementById('roomFloor').value    = '1';
    document.getElementById('roomType').value     = 'Classroom';
    document.getElementById('roomCapacity').value = '';
    document.getElementById('roomColorPicker').value = '#85C1E2';
    document.getElementById('roomColorText').value   = '#85C1E2';
    document.getElementById('roomPurpose').value = '';
    document.getElementById('roomImgPreview').style.display = 'none';
    document.getElementById('roomImgPlaceholder').style.display = 'flex';
    document.getElementById('roomImgActions').style.display = 'none';
    document.getElementById('roomImgPreview').src = '';
    document.getElementById('roomImageInput').value = '';
    document.getElementById('roomImgStatus').textContent = '';
    document.getElementById('roomImageGroup').dataset.existingUrl = '';
    document.getElementById('roomX').value = '';
    document.getElementById('roomY').value = '';
    document.getElementById('roomW').value = '';
    document.getElementById('roomH').value = '';

    const preselect = document.getElementById('roomBuildingFilter').value;
    populateBuildingSelect(preselect || (allBuildings[0]?.id ?? ''));
    document.getElementById('roomModal').classList.add('active');
}

function editRoom(r) {
    document.getElementById('roomModalTitle').textContent = 'Edit Room';
    document.getElementById('roomId').value              = r.id;
    document.getElementById('roomName').value            = r.room_number;
    document.getElementById('roomFloor').value           = r.floor || '1';
    document.getElementById('roomType').value            = r.room_type || 'Classroom';
    document.getElementById('roomCapacity').value        = r.capacity || '';
    document.getElementById('roomColorPicker').value     = r.color || '#85C1E2';
    document.getElementById('roomColorText').value       = r.color || '#85C1E2';
    document.getElementById('roomPurpose').value          = r.purpose || '';
    // Set existing image
    const imgGrp = document.getElementById('roomImageGroup');
    imgGrp.dataset.existingUrl = r.image_url || '';
    if (r.image_url) {
        document.getElementById('roomImgPreview').src          = r.image_url;
        document.getElementById('roomImgPreview').style.display = 'block';
        document.getElementById('roomImgPlaceholder').style.display = 'none';
        document.getElementById('roomImgActions').style.display = 'flex';
    } else {
        document.getElementById('roomImgPreview').style.display = 'none';
        document.getElementById('roomImgPlaceholder').style.display = 'flex';
        document.getElementById('roomImgActions').style.display = 'none';
    }
    document.getElementById('roomImageInput').value = '';
    document.getElementById('roomImgStatus').textContent = '';
    document.getElementById('roomX').value               = r.x_pos  ?? '';
    document.getElementById('roomY').value               = r.y_pos  ?? '';
    document.getElementById('roomW').value               = r.width  ?? '';
    document.getElementById('roomH').value               = r.height ?? '';
    populateBuildingSelect(r.building_name ? allBuildings.find(b=>b.building_name===r.building_name)?.id : null);
    // Find building id from name
    const bid = allBuildings.find(b => b.building_name === r.building_name)?.id;
    if (bid) document.getElementById('roomBuilding').value = bid;
    document.getElementById('roomModal').classList.add('active');
}

function closeRoomModal() { document.getElementById('roomModal').classList.remove('active'); }

function setColor(hex) {
    document.getElementById('roomColorPicker').value = hex;
    document.getElementById('roomColorText').value   = hex;
}

async function saveRoom() {
    const name = document.getElementById('roomName').value.trim();
    const bid  = document.getElementById('roomBuilding').value;
    if (!name)  { alert('Room name is required.'); return; }
    if (!bid)   { alert('Please select a building.'); return; }

    const color = document.getElementById('roomColorText').value.trim() || '#85C1E2';
    const payload = {
        room_id:     document.getElementById('roomId').value || null,
        room_number: name,
        building_id: bid,
        room_type:   document.getElementById('roomType').value,
        floor:       document.getElementById('roomFloor').value.trim() || '1',
        capacity:    document.getElementById('roomCapacity').value || null,
        color:       color,
        purpose:     document.getElementById('roomPurpose').value.trim() || null,
        x_pos:       document.getElementById('roomX').value !== '' ? document.getElementById('roomX').value : null,
        y_pos:       document.getElementById('roomY').value !== '' ? document.getElementById('roomY').value : null,
        width:       document.getElementById('roomW').value !== '' ? document.getElementById('roomW').value : null,
        height:      document.getElementById('roomH').value !== '' ? document.getElementById('roomH').value : null,
    };

    const res    = await fetch('../../api/admin/save_room.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    const result = await res.json();
    if (result.success) {
        // Upload image if a new one was selected
        const imageFile = document.getElementById('roomImageInput').files[0];
        const savedRoomId = result.room_id || payload.room_id;
        if (imageFile && savedRoomId) {
            const fd = new FormData();
            fd.append('room_id', savedRoomId);
            fd.append('image', imageFile);
            document.getElementById('roomImgStatus').textContent = '⏳ Uploading image…';
            const imgRes = await fetch('../../api/admin/upload_room_image.php', { method:'POST', body:fd });
            const imgData = await imgRes.json();
            if (!imgData.success) alert('Room saved but image upload failed: ' + imgData.message);
        }
        closeRoomModal(); loadBuildings(); loadRooms();
    } else { alert('Error: ' + result.message); }
}

async function deleteRoom(id, name) {
    if (!confirm(`Delete room "${name}"?`)) return;
    const res    = await fetch('../../api/admin/delete_room.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({room_id:id}) });
    const result = await res.json();
    if (result.success) { loadBuildings(); loadRooms(); }
    else { alert('Error: ' + result.message); }
}

/* ══════════════════════════════════════
   IMAGE HELPERS
══════════════════════════════════════ */
function previewRoomImage(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 5 * 1024 * 1024) { alert('Image must be under 5MB.'); input.value=''; return; }
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('roomImgPreview').src = e.target.result;
        document.getElementById('roomImgPreview').style.display = 'block';
        document.getElementById('roomImgPlaceholder').style.display = 'none';
        document.getElementById('roomImgActions').style.display = 'flex';
        document.getElementById('roomImgStatus').textContent = '✅ ' + file.name + ' ready to upload';
    };
    reader.readAsDataURL(file);
}

function removeRoomImage() {
    document.getElementById('roomImgPreview').src = '';
    document.getElementById('roomImgPreview').style.display = 'none';
    document.getElementById('roomImgPlaceholder').style.display = 'flex';
    document.getElementById('roomImgActions').style.display = 'none';
    document.getElementById('roomImageInput').value = '';
    document.getElementById('roomImgStatus').textContent = '';
    document.getElementById('roomImageGroup').dataset.existingUrl = '';
}

/* ══════════════════════════════════════
   HELPERS
══════════════════════════════════════ */
function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ══════════════════════════════════════
   INIT
══════════════════════════════════════ */
loadBuildings();

/* Sidebar scroll restore */
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
