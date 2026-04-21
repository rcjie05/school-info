<?php
/**
 * Floor Plan Setup Checker
 * Run this file to diagnose issues with your floor plan installation
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Floor Plan Setup Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .check { margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid #ddd; }
        .check.success { border-left-color: #4CAF50; background: #e8f5e9; }
        .check.error { border-left-color: #f44336; background: #ffebee; }
        .check.warning { border-left-color: #ff9800; background: #fff3e0; }
        code { background: #eee; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .url-box { background: #e3f2fd; padding: 15px; margin: 20px 0; border-radius: 5px; border: 2px solid #2196F3; }
        .url-box strong { color: #1976D2; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🗺️ Floor Plan Setup Diagnostic</h2>
        
        <?php
        $allGood = true;
        
        // Get current directory
        $baseDir = __DIR__;
        $basePath = $_SERVER['REQUEST_URI'];
        $basePath = str_replace('/check-setup.php', '', $basePath);
        
        echo "<div class='url-box'>";
        echo "<strong>📍 Your Base URL:</strong> http://{$_SERVER['HTTP_HOST']}{$basePath}<br>";
        echo "<strong>📁 Your Base Directory:</strong> $baseDir";
        echo "</div>";
        
        echo "<h3>File Existence Check</h3>";
        
        // Check file existence
        $files = [
            'floorplan.php' => 'Root redirector',
            'php/floorplan.php' => 'Main floor plan (authenticated)',
            'php/api/routes.php' => 'Routes API endpoint',
            'php/config.php' => 'Database configuration',
            'js/floor-script.js' => 'JavaScript file',
            'css/floor-styles.css' => 'CSS styles',
            'floorplan.html' => 'Standalone HTML version'
        ];
        
        foreach ($files as $file => $desc) {
            $fullPath = $baseDir . '/' . $file;
            $exists = file_exists($fullPath);
            $class = $exists ? 'success' : 'error';
            $icon = $exists ? '✅' : '❌';
            
            if (!$exists) $allGood = false;
            
            echo "<div class='check $class'>";
            echo "$icon <strong>$desc</strong>: <code>$file</code>";
            if (!$exists) {
                echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;⚠️ File not found at: $fullPath";
            }
            echo "</div>";
        }
        
        // Check PHP version
        echo "<h3>System Check</h3>";
        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, '7.4', '>=');
        echo "<div class='check " . ($phpOk ? 'success' : 'error') . "'>";
        echo ($phpOk ? '✅' : '❌') . " <strong>PHP Version:</strong> $phpVersion";
        if (!$phpOk) {
            echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;⚠️ PHP 7.4 or higher required";
            $allGood = false;
        }
        echo "</div>";
        
        // Check session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $sessionOk = session_status() === PHP_SESSION_ACTIVE;
        echo "<div class='check " . ($sessionOk ? 'success' : 'error') . "'>";
        echo ($sessionOk ? '✅' : '❌') . " <strong>PHP Sessions:</strong> " . ($sessionOk ? 'Working' : 'Not working');
        if (!$sessionOk) $allGood = false;
        echo "</div>";
        
        // Check if logged in
        $loggedIn = isset($_SESSION['user_id']) && isset($_SESSION['role']);
        echo "<div class='check " . ($loggedIn ? 'success' : 'warning') . "'>";
        echo ($loggedIn ? '✅' : '⚠️') . " <strong>User Session:</strong> ";
        if ($loggedIn) {
            echo "Logged in as " . ($_SESSION['name'] ?? 'Unknown') . " (Role: " . $_SESSION['role'] . ")";
        } else {
            echo "Not logged in (You need to login to access authenticated floor plan)";
        }
        echo "</div>";
        
        // Check database
        echo "<h3>Database Check</h3>";
        if (file_exists($baseDir . '/php/config.php')) {
            require_once $baseDir . '/php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
            
            // Check connection
            $conn = @getDBConnection();
            $dbOk = ($conn !== null);
            echo "<div class='check " . ($dbOk ? 'success' : 'error') . "'>";
            echo ($dbOk ? '✅' : '❌') . " <strong>Database Connection:</strong> " . ($dbOk ? 'Connected' : 'Failed');
            
            if (!$dbOk) {
                echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;⚠️ Check database credentials in php/config.php";
                $allGood = false;
            }
            echo "</div>";
            
            // Check table
            if ($dbOk) {
                $result = @$conn->query("SHOW TABLES LIKE 'floor_plan_routes'");
                $tableExists = $result && $result->num_rows > 0;
                echo "<div class='check " . ($tableExists ? 'success' : 'error') . "'>";
                echo ($tableExists ? '✅' : '❌') . " <strong>Routes Table:</strong> ";
                
                if ($tableExists) {
                    $countResult = $conn->query("SELECT COUNT(*) as count FROM floor_plan_routes");
                    $count = $countResult->fetch_assoc()['count'];
                    echo "Exists ($count routes saved)";
                } else {
                    echo "Missing - Run migration SQL!";
                    $allGood = false;
                }
                echo "</div>";
                
                $conn->close();
            }
        } else {
            echo "<div class='check error'>";
            echo "❌ <strong>Config file missing:</strong> php/config.php not found";
            $allGood = false;
            echo "</div>";
        }
        
        // Generate URLs
        echo "<h3>🔗 Access URLs</h3>";
        $baseUrl = "http://{$_SERVER['HTTP_HOST']}{$basePath}";
        
        echo "<div class='url-box'>";
        echo "<strong>Option 1 - Authenticated (Recommended):</strong><br>";
        echo "🔐 <a href='{$baseUrl}/php/floorplan.php' target='_blank'>{$baseUrl}/php/floorplan.php</a><br>";
        echo "<small>Requires login. Admins can create routes, others can view.</small>";
        echo "</div>";
        
        echo "<div class='url-box'>";
        echo "<strong>Option 2 - Standalone Demo:</strong><br>";
        echo "🌐 <a href='{$baseUrl}/floorplan.html' target='_blank'>{$baseUrl}/floorplan.html</a><br>";
        echo "<small>No login needed. Uses localStorage (not database).</small>";
        echo "</div>";
        
        echo "<div class='url-box'>";
        echo "<strong>Option 3 - Auto-redirect:</strong><br>";
        echo "↩️ <a href='{$baseUrl}/floorplan.php' target='_blank'>{$baseUrl}/floorplan.php</a><br>";
        echo "<small>Redirects to authenticated version if logged in, or to login page.</small>";
        echo "</div>";
        
        // Final verdict
        echo "<h3>Overall Status</h3>";
        if ($allGood) {
            echo "<div class='check success'>";
            echo "🎉 <strong>Everything looks good!</strong> Your floor plan should work correctly.";
            echo "</div>";
        } else {
            echo "<div class='check error'>";
            echo "⚠️ <strong>Issues detected!</strong> Fix the errors above before using the floor plan.";
            echo "</div>";
        }
        
        // Quick fixes
        echo "<h3>Quick Fixes</h3>";
        echo "<div class='check warning'>";
        echo "<strong>If you see errors:</strong><br>";
        echo "1. Run the database migration: <code>migration_add_floor_plan_routes.sql</code><br>";
        echo "2. Check database credentials in: <code>php/config.php</code><br>";
        echo "3. Make sure all files are extracted to: <code>$baseDir</code><br>";
        echo "4. Login as admin to create routes<br>";
        echo "5. Restart Apache if you made config changes";
        echo "</div>";
        ?>
    </div>
    <script src="./js/theme-switcher.js"></script>
</body>
</html>
