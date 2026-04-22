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

// Ensure archived_at column exists
// Safe column migration
$_col_check = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'archived_at'");
if ($_col_check && $_col_check->fetch_assoc()['cnt'] == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN archived_at DATETIME DEFAULT NULL");
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

$user_id = intval($input['user_id']);

$stmt = $conn->prepare("SELECT name, email, role, archived_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

if (!$user['archived_at']) {
    echo json_encode(['success' => false, 'message' => 'User is not archived']);
    exit();
}

$stmt = $conn->prepare("UPDATE users SET archived_at = NULL, status = 'active' WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    logAction($conn, $admin_id, "Restored archived user: {$user['name']} ({$user['email']}, {$user['role']})", 'users', $user_id);
    echo json_encode(['success' => true, 'message' => "User '{$user['name']}' has been restored"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to restore user: ' . $stmt->error]);
}

$conn->close();
?>
