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
$specialty_id = isset($input['specialty_id']) ? intval($input['specialty_id']) : null;

if (!$specialty_id) {
    echo json_encode(['success' => false, 'message' => 'Specialty ID is required']);
    exit();
}

// Get specialty info before deletion for logging
$stmt = $conn->prepare("
    SELECT ts.id, u.name as teacher_name, s.subject_code
    FROM teacher_specialties ts
    INNER JOIN users u ON ts.teacher_id = u.id
    INNER JOIN subjects s ON ts.subject_id = s.id
    WHERE ts.id = ?
");
$stmt->bind_param("i", $specialty_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Specialty not found']);
    exit();
}

$specialty = $result->fetch_assoc();

// Delete the specialty
$stmt = $conn->prepare("DELETE FROM teacher_specialties WHERE id = ?");
$stmt->bind_param("i", $specialty_id);

if ($stmt->execute()) {
    logAction($conn, $admin_id, "Removed {$specialty['teacher_name']}'s specialty in {$specialty['subject_code']}", 'teacher_specialties', $specialty_id);
    echo json_encode(['success' => true, 'message' => 'Teacher specialty removed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove specialty: ' . $stmt->error]);
}

$conn->close();
?>
