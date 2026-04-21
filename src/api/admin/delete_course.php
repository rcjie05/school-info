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

$conn     = getDBConnection();
$admin_id = $_SESSION['user_id'];
$input    = json_decode(file_get_contents('php://input'), true);
$course_id = intval($input['course_id'] ?? 0);

if (!$course_id) {
    echo json_encode(['success' => false, 'message' => 'Course ID required.']);
    exit();
}

// Get course name first
$chk = $conn->prepare("SELECT course_name FROM courses WHERE id=?");
$chk->bind_param('i', $course_id);
$chk->execute();
$row = $chk->get_result()->fetch_assoc();
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Course not found.']);
    exit();
}

// Check if any sections or students are using it
$sec_chk = $conn->prepare("SELECT COUNT(*) as c FROM sections WHERE course = ?");
$sec_chk->bind_param('s', $row['course_name']);
$sec_chk->execute();
$sec_count = $sec_chk->get_result()->fetch_assoc()['c'];

$stu_chk = $conn->prepare("SELECT COUNT(*) as c FROM users WHERE course = ? AND role = 'student'");
$stu_chk->bind_param('s', $row['course_name']);
$stu_chk->execute();
$stu_count = $stu_chk->get_result()->fetch_assoc()['c'];

if ($sec_count > 0 || $stu_count > 0) {
    echo json_encode([
        'success' => false,
        'message' => "Cannot delete: {$row['course_name']} is used by $sec_count section(s) and $stu_count student(s). Consider setting it to Inactive instead."
    ]);
    exit();
}

$del = $conn->prepare("DELETE FROM courses WHERE id=?");
$del->bind_param('i', $course_id);

if ($del->execute()) {
    logAction($conn, $admin_id, "Deleted course: {$row['course_name']}", 'courses', $course_id);
    echo json_encode(['success' => true, 'message' => "Course '{$row['course_name']}' deleted successfully."]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete course.']);
}

$conn->close();
?>
