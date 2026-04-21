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
requireRole('teacher');

$conn    = getDBConnection();
$user_id = (int)$_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['student_id']) || !isset($input['subject_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$student_id    = (int)$input['student_id'];
$subject_id    = (int)$input['subject_id'];
$midterm_grade = isset($input['midterm_grade']) && $input['midterm_grade'] !== '' ? (float)$input['midterm_grade'] : null;
$final_grade   = isset($input['final_grade'])   && $input['final_grade']   !== '' ? (float)$input['final_grade']   : null;
$semester      = isset($input['semester'])    ? trim($input['semester'])    : 'First Semester';
$school_year   = isset($input['school_year']) ? trim($input['school_year']) : date('Y') . '-' . (date('Y') + 1);

// ── Verify teacher is assigned to this subject ──────────────────────
// Check section_subjects first (primary assignment table)
$authorized = false;

$stmt = $conn->prepare("SELECT id FROM section_subjects WHERE teacher_id = ? AND subject_id = ? LIMIT 1");
$stmt->bind_param("ii", $user_id, $subject_id);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) $authorized = true;
$stmt->close();

// Fallback: check study_loads
if (!$authorized) {
    $stmt = $conn->prepare("SELECT id FROM study_loads WHERE teacher_id = ? AND subject_id = ? LIMIT 1");
    $stmt->bind_param("ii", $user_id, $subject_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) $authorized = true;
    $stmt->close();
}

if (!$authorized) {
    echo json_encode(['success' => false, 'message' => 'You are not authorized to grade this subject']);
    exit();
}

// ── Validate grade range (Philippine system: 1.0–5.0) ───────────────
if ($midterm_grade !== null && ($midterm_grade < 1.0 || $midterm_grade > 5.0)) {
    echo json_encode(['success' => false, 'message' => 'Midterm grade must be between 1.0 and 5.0']);
    exit();
}
if ($final_grade !== null && ($final_grade < 1.0 || $final_grade > 5.0)) {
    echo json_encode(['success' => false, 'message' => 'Final grade must be between 1.0 and 5.0']);
    exit();
}

// ── Compute remarks ──────────────────────────────────────────────────
$remarks = null;
if ($final_grade !== null) {
    $remarks = $final_grade <= 3.0 ? 'Passed' : 'Failed';
}

// ── Upsert grade record ──────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT id FROM grades
    WHERE student_id = ? AND subject_id = ? AND semester = ? AND school_year = ?
");
$stmt->bind_param("iiss", $student_id, $subject_id, $semester, $school_year);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $stmt = $conn->prepare("
        UPDATE grades
        SET midterm_grade = ?,
            final_grade   = ?,
            remarks       = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ddsi", $midterm_grade, $final_grade, $remarks, $existing['id']);
} else {
    $stmt = $conn->prepare("
        INSERT INTO grades (student_id, subject_id, midterm_grade, final_grade, semester, school_year, remarks)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiddsss", $student_id, $subject_id, $midterm_grade, $final_grade, $semester, $school_year, $remarks);
}

if ($stmt->execute()) {
    logAction($conn, $user_id, "Saved grade for student_id=$student_id subject_id=$subject_id", 'grades', $student_id);
    createNotification($conn, $student_id, '📝 Grade Updated', 'Your grade has been updated by your teacher.');
    echo json_encode(['success' => true, 'message' => 'Grade saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save grade: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
