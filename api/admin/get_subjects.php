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

$course     = isset($_GET['course'])     ? sanitizeInput($_GET['course'])     : '';
$year_level = isset($_GET['year_level']) ? sanitizeInput($_GET['year_level']) : '';
$status     = isset($_GET['status'])     ? sanitizeInput($_GET['status'])     : '';
$search     = isset($_GET['search'])     ? sanitizeInput($_GET['search'])     : '';

// Special sentinel: course=__none__ means only subjects with no course (All-Courses/shared subjects)
$course_none = ($course === '__none__');

$sql    = "SELECT * FROM subjects WHERE 1=1";
$params = [];
$types  = "";

if ($course_none) {
    // subjects where course IS NULL or empty string
    $sql .= " AND (course IS NULL OR course = '')";
} elseif (!empty($course)) {
    $sql .= " AND course = ?";
    $params[] = $course;
    $types   .= "s";
}

if (!empty($year_level)) {
    $sql .= " AND year_level = ?";
    $params[] = $year_level;
    $types   .= "s";
}

if (!empty($status)) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types   .= "s";
}

if (!empty($search)) {
    $sql .= " AND (subject_code LIKE ? OR subject_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types   .= "ss";
}

$sql .= " ORDER BY subject_code ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

echo json_encode([
    'success'  => true,
    'subjects' => $subjects
]);

$conn->close();
?>
