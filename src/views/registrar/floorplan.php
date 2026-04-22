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

// Only registrars can access this page
if ($_SESSION['role'] !== 'registrar') {
    header('Location: ../php/logout.php');
    exit();
}

$userRole = $_SESSION['role'];
$fullName = $_SESSION['name'] ?? 'Registrar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Map - Registrar</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/css/floor-styles.css">
    <style>
        body { background: var(--background-main) !important; padding: 0 !important; }
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
        
        /* FIXED: Force proper grid layout for non-admin */
        .content {
            display: grid !important;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            padding: 24px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Hide admin controls */
        .controls {
            display: none !important;
        }
        
        /* Ensure canvas container is visible */
        .canvas-container {
            background: white !important;
            border-radius: 20px !important;
            padding: 24px !important;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1) !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 20px;
        }

        .canvas-wrapper {
            position: relative;
        }
        
        /* Ensure canvas is visible and proper size */
        #floorPlan {
            border: 3px solid #e2e8f0 !important;
            border-radius: 16px !important;
            display: block !important;
            margin: 0 auto !important;
            visibility: visible !important;
            background: white;
        }

        .zoom-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 10;
        }

        .zoom-btn {
            width: 40px;
            height: 40px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #475569;
        }

        .zoom-btn:hover {
            background: #f8fafc;
            border-color: #2563eb;
            color: #2563eb;
            transform: scale(1.05);
        }

        /* IMPROVED LEGEND - BELOW CANVAS */
        .legend {
            background: #f8fafc;
            padding: 16px 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        .legend h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            flex: 1;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #475569;
            transition: all 0.2s;
        }

        .legend-item:hover {
            color: #1e293b;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 2px solid rgba(0,0,0,0.1);
            flex-shrink: 0;
        }

        .legend-item span {
            font-weight: 500;
            white-space: nowrap;
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
                    <a href="floorplan.php" class="nav-item active"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                    <a href="reports.php" class="nav-item"><span class="nav-icon">📈</span><span>Reports</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>Announcements</span></a>
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
            <div class="floor-header">
                <div class="floor-header-row"><button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button><h1>🗺️ Campus Navigation Map</h1></div>
                <p style="margin: 5px 0 0 0; color: #666;">Browse available routes to help you navigate the campus</p>
            </div>

            <div class="floor-container">
                <div class="container active" id="mainApp">
                    <div class="content" id="mainContent">
                        <!-- Canvas Container - LEFT SIDE -->
                        <div class="canvas-container">
                            <div class="canvas-wrapper">
                                <canvas id="floorPlan" width="900" height="700"></canvas>
                                <div class="zoom-controls">
                                    <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">+</button>
                                    <button class="zoom-btn" onclick="resetZoom()" title="Reset Zoom">⊙</button>
                                    <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">−</button>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT PANEL: Routes + Legend -->
                        <div class="right-panel">
                            <div class="saved-routes" id="registrarRouteSelector">
                                <div class="control-section">
                                    <h3>📚 Available Routes</h3>
                                    <input type="text" class="input-field" id="registrarRouteSearch"
                                        placeholder="🔍 Search routes..."
                                        oninput="filterRegistrarRoutes()"
                                        style="margin-bottom: 15px;">
                                    <p style="color: var(--gray-600); font-size: 0.95em; margin-bottom: 15px;">
                                        Click on any route below to display it on the map
                                    </p>
                                </div>
                                <div id="registrarRoutesList">
                                    <div class="empty-state">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                        </svg>
                                        <p><strong>No routes available</strong></p>
                                        <p style="font-size: 0.9em; margin-top: 5px;">Check back later for available routes</p>
                                    </div>
                                </div>
                            </div>
                            <div class="legend">
                                <h4>🗺️ Legend</h4>
                                <div class="legend-items">
                                    <div class="legend-item"><div class="legend-color" style="background:#F4D03F;"></div><span>Administrative</span></div>
                                    <div class="legend-item"><div class="legend-color" style="background:#85C1E2;"></div><span>Classrooms</span></div>
                                    <div class="legend-item"><div class="legend-color" style="background:#7DCEA0;"></div><span>Services</span></div>
                                    <div class="legend-item"><div class="legend-color" style="background:#F1948A;"></div><span>Common Areas</span></div>
                                    <div class="legend-item"><div class="legend-color" style="background:#FF6B6B;"></div><span>Route Path</span></div>
                                    <div class="legend-item"><div class="legend-color" style="background:#4ECDC4;"></div><span>Waypoints</span></div>
                                </div>
                            </div>
                        </div><!-- end right-panel -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        window.currentUserRole = 'registrar';
        window.canEditRoutes = false;
        window.userFullName = '<?php echo htmlspecialchars($fullName); ?>';
        console.log('Canvas element ready:', document.getElementById('floorPlan') !== null);
    </script>
    <script src="../../../public/js/floor-script.js"></script>
    
    <!-- Debug script -->
    <script>
        window.addEventListener('load', function() {
            console.log('=== REGISTRAR FLOOR PLAN DEBUG ===');
            const canvas = document.getElementById('floorPlan');
            console.log('1. Canvas found:', canvas !== null);
            
            if (canvas) {
                console.log('2. Canvas dimensions:', canvas.width, 'x', canvas.height);
                const ctx = canvas.getContext('2d');
                console.log('3. Context available:', ctx !== null);
                
                if (typeof drawFloorPlan === 'function') {
                    console.log('4. drawFloorPlan exists - calling now');
                    try {
                        drawFloorPlan();
                        console.log('5. SUCCESS - Floor plan drawn');
                    } catch(e) {
                        console.error('5. ERROR:', e);
                    }
                } else {
                    console.error('4. drawFloorPlan NOT FOUND');
                }
            }
            console.log('=== END DEBUG ===');
        });
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
</body>
</html>