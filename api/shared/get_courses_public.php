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
header('Access-Control-Allow-Origin: *');

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'courses' => []]);
    exit();
}

// Check if courses table exists
$check = $conn->query("SHOW TABLES LIKE 'courses'");
if ($check->num_rows === 0) {
    echo json_encode(['success' => true, 'courses' => []]);
    $conn->close();
    exit();
}

$result = $conn->query("
    SELECT id, course_name, course_code, duration_years
    FROM courses
    WHERE status = 'active'
    ORDER BY course_name
");

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

echo json_encode(['success' => true, 'courses' => $courses]);
$conn->close();
?>
