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

$teacher_id = isset($input['teacher_id']) ? intval($input['teacher_id']) : null;
$subject_id = isset($input['subject_id']) ? intval($input['subject_id']) : null;
$proficiency_level = isset($input['proficiency_level']) ? sanitizeInput($input['proficiency_level']) : 'intermediate';
$is_primary = isset($input['is_primary']) ? intval($input['is_primary']) : 0;

if (!$teacher_id || !$subject_id) {
    echo json_encode(['success' => false, 'message' => 'Teacher and subject are required']);
    exit();
}

// Verify teacher exists and is a teacher
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'teacher'");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Teacher not found or user is not a teacher']);
    exit();
}

$teacher = $result->fetch_assoc();

// Verify subject exists
$stmt = $conn->prepare("SELECT subject_code, subject_name FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Subject not found']);
    exit();
}

$subject = $result->fetch_assoc();

// Check if this specialty already exists
$stmt = $conn->prepare("SELECT id FROM teacher_specialties WHERE teacher_id = ? AND subject_id = ?");
$stmt->bind_param("ii", $teacher_id, $subject_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This teacher is already assigned to this subject']);
    exit();
}

// If this is set as primary, remove primary flag from other specialties for this teacher
if ($is_primary) {
    $stmt = $conn->prepare("UPDATE teacher_specialties SET is_primary = 0 WHERE teacher_id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
}

// Insert the specialty
$stmt = $conn->prepare("
    INSERT INTO teacher_specialties (teacher_id, subject_id, proficiency_level, is_primary)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iisi", $teacher_id, $subject_id, $proficiency_level, $is_primary);

if ($stmt->execute()) {
    $new_id = $conn->insert_id;
    logAction($conn, $admin_id, "Assigned {$teacher['name']} specialty in {$subject['subject_code']}", 'teacher_specialties', $new_id);
    echo json_encode(['success' => true, 'message' => 'Teacher specialty assigned successfully', 'specialty_id' => $new_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to assign specialty: ' . $stmt->error]);
}

$conn->close();
?>
