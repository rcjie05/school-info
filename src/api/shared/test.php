<?php
/**
 * API Test File - Place this in /php/api/test.php
 * Access it at: http://localhost/school-management-system/php/api/test.php
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test 1: Basic response
echo json_encode([
    'test' => 'API is accessible',
    'timestamp' => date('Y-m-d H:i:s'),
    'script_path' => __FILE__,
    'current_dir' => __DIR__
]);

// Test 2: Check if config.php can be loaded
try {
    require_once __DIR__ . '/../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
    echo "\n\nConfig loaded successfully!";
} catch (Exception $e) {
    echo "\n\nConfig load failed: " . $e->getMessage();
}

// Test 3: Check session
session_start();
echo "\n\nSession status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive');
echo "\nSession ID: " . session_id();
echo "\nLogged in: " . (isLoggedIn() ? 'Yes' : 'No');
if (isset($_SESSION['user_id'])) {
    echo "\nUser ID: " . $_SESSION['user_id'];
    echo "\nRole: " . $_SESSION['role'];
}

// Test 4: Check database connection
$conn = getDBConnection();
if ($conn) {
    echo "\n\nDatabase connected successfully!";
    $conn->close();
} else {
    echo "\n\nDatabase connection failed!";
}
?>
