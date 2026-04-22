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

$teacher_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Specialties - Teacher Dashboard</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/mobile-fix.css">
    <link rel="stylesheet" href="../../css/themes.css">
    <style>
        .specialty-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--primary-color);
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .specialty-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        .subject-code {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .subject-name {
            font-size: 1.1rem;
            color: #374151;
            margin-top: 0.25rem;
        }
        .specialty-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
        }
        .primary-badge {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        .proficiency-beginner { background: #94a3b8; }
        .proficiency-intermediate { background: #3b82f6; }
        .proficiency-advanced { background: #8b5cf6; }
        .proficiency-expert { background: #f59e0b; }
        .specialty-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        .detail-value {
            font-weight: 600;
            color: #111827;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
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
                    <a href="specialties.php" class="nav-item active"><span class="nav-icon">🎯</span><span>My Subjects</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Resources</div>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
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
        
        <main class="main-content">
            <header class="page-header">
                <div class="header-title">
                    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button>
                    <h1>My Subject Specialties</h1>
                    <p class="page-subtitle">View subjects you're qualified to teach</p>
                </div>
            </header>
            
            <div class="content-card">
                <div id="specialtiesContainer">
                    <div style="text-align: center; padding: 2rem;">Loading your specialties...</div>
                </div>
            </div>
        </main>
    </div>

    <script>
        async function loadSpecialties() {
            try {
                const response = await fetch('../../api/teacher/get_my_specialties.php');
                const data = await response.json();
                
                const container = document.getElementById('specialtiesContainer');
                
                if (!data.success) {
                    container.innerHTML = '<div class="alert alert-error">Failed to load specialties</div>';
                    return;
                }
                
                if (data.specialties.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">📚</div>
                            <h3>No Specialties Assigned</h3>
                            <p>You don't have any subject specialties assigned yet.<br>
                            Please contact the administrator to assign your teaching specialties.</p>
                        </div>
                    `;
                    return;
                }
                
                let html = '';
                let primarySpecialty = data.specialties.find(s => s.is_primary == '1');
                
                // Show primary specialty first if exists
                if (primarySpecialty) {
                    html += createSpecialtyCard(primarySpecialty, true);
                }
                
                // Show other specialties
                data.specialties.filter(s => s.is_primary != '1').forEach(specialty => {
                    html += createSpecialtyCard(specialty, false);
                });
                
                container.innerHTML = html;
            } catch (error) {
                console.error('Error loading specialties:', error);
                document.getElementById('specialtiesContainer').innerHTML = 
                    '<div class="alert alert-error">Error loading specialties</div>';
            }
        }
        
        function createSpecialtyCard(specialty, isPrimary) {
            const proficiencyClass = `proficiency-${specialty.proficiency_level}`;
            const proficiencyText = specialty.proficiency_level.charAt(0).toUpperCase() + 
                                   specialty.proficiency_level.slice(1);
            
            return `
                <div class="specialty-card">
                    <div class="specialty-header">
                        <div>
                            <div class="subject-code">${specialty.subject_code}</div>
                            <div class="subject-name">${specialty.subject_name}</div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end;">
                            ${isPrimary ? '<div class="specialty-badge primary-badge">⭐ Primary Specialty</div>' : ''}
                            <div class="specialty-badge ${proficiencyClass}">${proficiencyText}</div>
                        </div>
                    </div>
                    
                    ${specialty.description ? `<p style="color: #6b7280; margin-bottom: 1rem;">${specialty.description}</p>` : ''}
                    
                    <div class="specialty-details">
                        <div class="detail-item">
                            <div class="detail-label">Units</div>
                            <div class="detail-value">${specialty.units} ${specialty.units == 1 ? 'unit' : 'units'}</div>
                        </div>
                        ${specialty.course ? `
                        <div class="detail-item">
                            <div class="detail-label">Course</div>
                            <div class="detail-value">${specialty.course}</div>
                        </div>
                        ` : ''}
                        ${specialty.year_level ? `
                        <div class="detail-item">
                            <div class="detail-label">Year Level</div>
                            <div class="detail-value">${specialty.year_level}</div>
                        </div>
                        ` : ''}
                        <div class="detail-item">
                            <div class="detail-label">Assigned Since</div>
                            <div class="detail-value">${new Date(specialty.assigned_date).toLocaleDateString()}</div>
                        </div>
                    </div>
                    
                    ${specialty.prerequisites ? `
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                        <div class="detail-label">Prerequisites</div>
                        <div class="detail-value">${specialty.prerequisites}</div>
                    </div>
                    ` : ''}
                </div>
            `;
        }
        
        loadSpecialties();
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
