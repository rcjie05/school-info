<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
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
requireRoleApi('admin');

$conn = getDBConnection();
$admin_id = $_SESSION['user_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

$user_id = intval($input['user_id']);

// Prevent admin from deleting themselves
if ($user_id == $admin_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
    exit();
}

// Get user info before deletion for logging
$stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

// Delete user (cascade will handle related records)
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // Log action
    logAction($conn, $admin_id, "Deleted user: {$user['name']} ({$user['email']}, {$user['role']})", 'users', $user_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete user: ' . $stmt->error
    ]);
}

$conn->close();
?>
