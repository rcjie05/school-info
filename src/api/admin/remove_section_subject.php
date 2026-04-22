<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('admin');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$section_subject_id = (int)($data['section_subject_id'] ?? 0);

if (!$section_subject_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM section_subjects WHERE id = ?");
$stmt->bind_param('i', $section_subject_id);

if ($stmt->execute()) {
    logAction($conn, $_SESSION['user_id'], "Removed subject from section (section_subject_id=$section_subject_id)", 'section_subjects', $section_subject_id);
    echo json_encode(['success' => true, 'message' => 'Subject removed from section']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove subject']);
}

$stmt->close();
$conn->close();
?>
