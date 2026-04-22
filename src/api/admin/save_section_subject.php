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

$section_id = (int)($data['section_id'] ?? 0);
$subject_id = (int)($data['subject_id'] ?? 0);
$teacher_id = !empty($data['teacher_id']) ? (int)$data['teacher_id'] : null;

if (!$section_id || !$subject_id) {
    echo json_encode(['success' => false, 'message' => 'Section and subject are required']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if already exists
$check = $conn->prepare("SELECT id FROM section_subjects WHERE section_id = ? AND subject_id = ?");
$check->bind_param('ii', $section_id, $subject_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This subject is already assigned to this section']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO section_subjects (section_id, subject_id, teacher_id) VALUES (?, ?, ?)");
$stmt->bind_param('iii', $section_id, $subject_id, $teacher_id);

if ($stmt->execute()) {
    $new_id = $conn->insert_id;
    logAction($conn, $_SESSION['user_id'], "Added subject to section (section_id=$section_id, subject_id=$subject_id)", 'section_subjects', $new_id);
    echo json_encode(['success' => true, 'message' => 'Subject assigned to section', 'id' => $new_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to assign subject: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
