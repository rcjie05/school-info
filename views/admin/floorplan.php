<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireLogin();

// Only admin can access this page
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../php/logout.php');
    exit();
}

$userRole = $_SESSION['role'];
$fullName = $_SESSION['name'] ?? 'Administrator';
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
    <title>Floor Plan Navigator - Admin</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/floor-styles.css">
    <style>
        body { background: var(--background-main) !important; padding: 0 !important; }
        /* Integrate floor plan with admin dashboard */
        .page-wrapper {
            display: flex;
            min-height: 100vh;
        }
        .main-content {
            flex: 1;
            padding: 0;
            background: #f5f5f5;
        }
        .floor-container {
            padding: 20px;
            max-width: 100%;
        }
        /* floor-header styles handled by floor-styles.css */
        .container.active {
            box-shadow: none;
            margin: 0;
            background: transparent;
        }
        .content {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
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
                    <span>Admin Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="users.php" class="nav-item"><span class="nav-icon">👥</span><span>User Management</span></a>
                    <a href="floorplan.php" class="nav-item active"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
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
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="floor-header">
                <div class="floor-header-row"><button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button><h1>🗺️ Floor Plan Navigator - Administrator</h1></div>
                <p style="margin: 5px 0 0 0; color: #666;">Create and manage navigation routes for the campus</p>
            </div>

            <div class="floor-container">
                <div class="container active" id="mainApp">
                    <div class="content" id="mainContent">
                        <!-- Left Controls (admin only) -->
                        <div class="controls">

                            <div class="success-message" id="successMessage"></div>

                            <div class="mode-indicator" id="modeIndicator">
                                ✏️ <strong>Route Drawing Active!</strong> Click on the map to add waypoints to your custom route.
                            </div>

                            <div class="control-section">
                                <h3>📍 Route Setup</h3>
                                <select class="room-select" id="startRoom">
                                    <option value="">Select Starting Point</option>
                                </select>
                                <select class="room-select" id="endRoom">
                                    <option value="">Select Destination</option>
                                </select>
                            </div>

                            <div class="control-section">
                                <h3>🎨 Create Route</h3>
                                <button class="btn" id="drawRouteBtn" onclick="startDrawingRoute()">
                                    ✏️ Draw Custom Route
                                </button>
                                <button class="btn btn-secondary" onclick="findDirectRoute()">
                                    ➡️ Show Direct Route
                                </button>
                                <button class="btn btn-success" onclick="completeRoute()">
                                    ✅ Complete Route
                                </button>
                            </div>

                            <div class="control-section">
                                <h3>💾 Save Route</h3>
                                <input type="text" class="input-field" id="routeName" placeholder="Enter route name...">
                                <textarea class="input-field" id="routeDescription" placeholder="Optional description..." rows="2"></textarea>
                                
                                <div class="route-visibility">
                                    <input type="checkbox" id="visibleToStudents" checked>
                                    <label for="visibleToStudents">👁️ Visible to all users</label>
                                </div>
                                
                                <button class="btn btn-warning" onclick="saveRoute()">
                                    💾 Save Current Route
                                </button>
                            </div>

                            <div class="control-section">
                                <h3>📋 Route Waypoints</h3>
                                <div id="waypointList" class="waypoint-list">
                                    <p style="text-align: center; color: var(--gray-500);">No waypoints yet</p>
                                </div>
                            </div>

                            <div class="control-section">
                                <h3>🔄 Controls</h3>
                                <button class="btn btn-clear" onclick="undoLastWaypoint()">↩️ Undo Last</button>
                                <button class="btn btn-clear" onclick="clearRoute()">🗑️ Clear Route</button>
                                <button class="btn btn-clear" onclick="resetAll()">🔄 Reset All</button>
                            </div>

                            <div id="routeInfo" class="route-info" style="display: none;">
                                <strong>📊 Route Information</strong>
                                <div id="routeDetails"></div>
                            </div>
                        </div>

                        <!-- Center Canvas -->
                        <div class="canvas-container">
                            <canvas id="floorPlan" width="900" height="700"></canvas>
                            
                            <div class="zoom-controls">
                                <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">+</button>
                                <button class="zoom-btn" onclick="resetZoom()" title="Reset Zoom">⊙</button>
                                <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">−</button>
                            </div>
                            
                            <div class="legend">
                                <h4>🗺️ Legend</h4>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #F4D03F;"></div>
                                    <span>Administrative</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #85C1E2;"></div>
                                    <span>Classrooms</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #7DCEA0;"></div>
                                    <span>Services</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #F1948A;"></div>
                                    <span>Common Areas</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #FF6B6B;"></div>
                                    <span>Route Path</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #4ECDC4;"></div>
                                    <span>Waypoints</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Saved Routes (Admin) -->
                        <div class="saved-routes" id="adminRoutesPanel">
                            <div class="control-section">
                                <h3>📚 Saved Routes</h3>
                                
                                <div class="stats-grid" id="adminStats" style="display: none;">
                                    <div class="stat-card">
                                        <div class="stat-value" id="totalRoutesCount">0</div>
                                        <div class="stat-label">Total Routes</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-value" id="publicRoutesCount">0</div>
                                        <div class="stat-label">Public Routes</div>
                                    </div>
                                </div>
                                
                                <button class="btn btn-secondary" onclick="exportRoutes()" style="margin-top: 12px;">
                                    📤 Export All
                                </button>
                                <button class="btn btn-success" onclick="document.getElementById('importFile').click()">
                                    📥 Import Routes
                                </button>
                                <input type="file" id="importFile" accept=".json" style="display: none" onchange="importRoutes(event)">
                            </div>

                            <div id="savedRoutesList">
                                <div class="empty-state">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                    </svg>
                                    <p><strong>No saved routes yet</strong></p>
                                    <p style="font-size: 0.9em; margin-top: 5px;">Create and save your first route!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Pass PHP variables to JavaScript
        window.currentUserRole = 'admin';
        window.canEditRoutes = true;
        window.userFullName = '<?php echo htmlspecialchars($fullName); ?>';
    </script>
    <script src="../../js/floor-script.js"></script>

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
</body>
</html>
