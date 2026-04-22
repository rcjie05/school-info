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

$data = json_decode(file_get_contents('php://input'), true);
$schedule_id = (int)($data['schedule_id'] ?? 0);

if (!$schedule_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM section_schedules WHERE id = ?");
$stmt->bind_param('i', $schedule_id);

if ($stmt->execute()) {
    logAction($conn, $_SESSION['user_id'], "Deleted schedule slot (id=$schedule_id)", 'section_schedules', $schedule_id);
    echo json_encode(['success' => true, 'message' => 'Schedule slot removed']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove schedule']);
}

$stmt->close();
$conn->close();
?>
