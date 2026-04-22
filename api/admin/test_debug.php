<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
// Debug script to test user creation and update functionality
// Place this in /php/api/admin/test_debug.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../php/config.php';
ob_clean();
header('Content-Type: application/json');

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
// Bypass role check for debugging
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in', 'session' => $_SESSION]);
    exit();
}

echo json_encode([
    'message' => 'Debug test',
    'logged_in' => isLoggedIn(),
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'role' => $_SESSION['role'] ?? 'not set',
    'has_admin_role' => hasRole('admin'),
    'php_version' => phpversion(),
    'post_data' => file_get_contents('php://input'),
    'connection_test' => testConnection()
]);

function testConnection() {
    $conn = getDBConnection();
    if (!$conn) {
        return 'Failed to connect';
    }
    
    // Test if we can query users
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return 'Connection OK - ' . $row['count'] . ' users in database';
}
?>
