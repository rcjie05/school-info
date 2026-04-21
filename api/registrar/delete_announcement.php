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
requireRole('registrar');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
$announcement_id = isset($input['announcement_id']) ? intval($input['announcement_id']) : null;

if (!$announcement_id) {
    echo json_encode(['success' => false, 'message' => 'Announcement ID is required']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM announcements WHERE id = ? AND posted_by = ?");
$stmt->bind_param("ii", $announcement_id, $user_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Announcement not found or you do not have permission to delete it']);
}

$conn->close();
?>
