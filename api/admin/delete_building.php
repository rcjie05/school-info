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

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['building_id'])) {
    echo json_encode(['success' => false, 'message' => 'Building ID is required']);
    exit();
}

$building_id = intval($input['building_id']);

// Get building info for logging
$stmt = $conn->prepare("SELECT building_name FROM buildings WHERE id = ?");
$stmt->bind_param("i", $building_id);
$stmt->execute();
$building = $stmt->get_result()->fetch_assoc();

if (!$building) {
    echo json_encode(['success' => false, 'message' => 'Building not found']);
    exit();
}

// Delete building (cascade will handle rooms)
$stmt = $conn->prepare("DELETE FROM buildings WHERE id = ?");
$stmt->bind_param("i", $building_id);

if ($stmt->execute()) {
    logAction($conn, $admin_id, "Deleted building: {$building['building_name']}", 'buildings', $building_id);
    echo json_encode(['success' => true, 'message' => 'Building deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete building: ' . $stmt->error]);
}

$conn->close();
?>
