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

$input = json_decode(file_get_contents('php://input'), true);
$student_id = intval($input['student_id'] ?? 0);

$conn = getDBConnection();
$stmt = $conn->prepare("UPDATE study_loads SET status = 'finalized' WHERE student_id = ? AND status = 'draft'");
$stmt->bind_param("i", $student_id);

if ($stmt->execute()) {
    createNotification($conn, $student_id, 'Study Load Assigned', 'Your study load has been finalized. You can now view your enrolled subjects.');
    logAction($conn, $_SESSION['user_id'], "Finalized student study load", 'users', $student_id);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
$conn->close();
?>
