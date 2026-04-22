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
requireRole('registrar');

$input = json_decode(file_get_contents('php://input'), true);
$student_id = intval($input['student_id'] ?? 0);
$subject_id = intval($input['subject_id'] ?? 0);

if (!$student_id || !$subject_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$conn = getDBConnection();

// Check if already added
$stmt = $conn->prepare("SELECT id FROM study_loads WHERE student_id = ? AND subject_id = ?");
$stmt->bind_param("ii", $student_id, $subject_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Subject already added']);
    exit();
}

// Add subject
$stmt = $conn->prepare("INSERT INTO study_loads (student_id, subject_id, status) VALUES (?, ?, 'draft')");
$stmt->bind_param("ii", $student_id, $subject_id);

if ($stmt->execute()) {
    logAction($conn, $_SESSION['user_id'], "Added subject to student load", 'study_loads', $conn->insert_id);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add subject']);
}

$conn->close();
?>
