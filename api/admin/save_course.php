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

$conn     = getDBConnection();
$admin_id = $_SESSION['user_id'];
$input    = json_decode(file_get_contents('php://input'), true);

$course_id      = isset($input['course_id']) ? intval($input['course_id']) : null;
$course_name    = isset($input['course_name'])    ? sanitizeInput($input['course_name'])    : '';
$course_code    = isset($input['course_code'])    ? sanitizeInput(strtoupper($input['course_code'])) : '';
$description    = isset($input['description'])    ? sanitizeInput($input['description'])    : '';
$department_id  = isset($input['department_id'])  && $input['department_id'] ? intval($input['department_id']) : null;
$duration_years = isset($input['duration_years']) ? intval($input['duration_years'])        : 4;
$total_units    = isset($input['total_units'])    && $input['total_units'] !== '' ? intval($input['total_units']) : null;
$status         = in_array($input['status'] ?? '', ['active','inactive']) ? $input['status'] : 'active';

if (!$course_name || !$course_code) {
    echo json_encode(['success' => false, 'message' => 'Course name and code are required.']);
    exit();
}

if ($course_id) {
    // Update
    $stmt = $conn->prepare("
        UPDATE courses
        SET course_name=?, course_code=?, description=?, department_id=?, duration_years=?, total_units=?, status=?
        WHERE id=?
    ");
    $stmt->bind_param('sssiiisi', $course_name, $course_code, $description, $department_id, $duration_years, $total_units, $status, $course_id);

    if ($stmt->execute()) {
        logAction($conn, $admin_id, "Updated course: $course_name", 'courses', $course_id);
        echo json_encode(['success' => true, 'message' => 'Course updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update: ' . $stmt->error]);
    }
} else {
    // Check code uniqueness
    $chk = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
    $chk->bind_param('s', $course_code);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "Course code '$course_code' already exists."]);
        exit();
    }

    $stmt = $conn->prepare("
        INSERT INTO courses (course_name, course_code, description, department_id, duration_years, total_units, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sssiiis', $course_name, $course_code, $description, $department_id, $duration_years, $total_units, $status);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        logAction($conn, $admin_id, "Added course: $course_name", 'courses', $new_id);
        echo json_encode(['success' => true, 'message' => 'Course added successfully.', 'course_id' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add course: ' . $stmt->error]);
    }
}

$conn->close();
?>
