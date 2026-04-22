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

$conn     = getDBConnection();
$admin_id = $_SESSION['user_id'];
$input    = json_decode(file_get_contents('php://input'), true);
$room_id  = !empty($input['room_id']) ? intval($input['room_id']) : null;

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit();
}

// Get room name for audit log
$name_stmt = $conn->prepare("SELECT room_number FROM rooms WHERE id=?");
$name_stmt->bind_param("i", $room_id);
$name_stmt->execute();
$row = $name_stmt->get_result()->fetch_assoc();
$name_stmt->close();
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Room not found']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM rooms WHERE id=?");
$stmt->bind_param("i", $room_id);
if ($stmt->execute()) {
    logAction($conn, $admin_id, "Deleted room: " . $row['room_number'], 'rooms', $room_id);
    echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete: ' . $stmt->error]);
}

$conn->close();
?>
