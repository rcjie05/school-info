<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
header('Content-Type: application/json');
requireRole('admin');

$conn = getDBConnection();
$admin_id = $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
$subject_id = isset($input['subject_id']) ? intval($input['subject_id']) : null;

if (!$subject_id) {
    echo json_encode(['success' => false, 'message' => 'Subject ID is required']);
    exit();
}

// Get subject info before deletion for logging
$stmt = $conn->prepare("SELECT subject_code, subject_name FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Subject not found']);
    exit();
}

$subject = $result->fetch_assoc();

// Delete the subject (cascades to teacher_specialties due to foreign key)
$stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);

if ($stmt->execute()) {
    logAction($conn, $admin_id, "Deleted subject: {$subject['subject_code']} - {$subject['subject_name']}", 'subjects', $subject_id);
    echo json_encode(['success' => true, 'message' => 'Subject deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete subject: ' . $stmt->error]);
}

$conn->close();
?>
