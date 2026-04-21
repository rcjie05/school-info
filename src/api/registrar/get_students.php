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

$conn = getDBConnection();

// Check if section_id column and sections table exist
$has_section_col = $conn->query("SHOW COLUMNS FROM `users` LIKE 'section_id'")->num_rows > 0;
$has_sections    = $conn->query("SHOW TABLES LIKE 'sections'")->num_rows > 0;

if ($has_section_col && $has_sections) {
    $sql = "
        SELECT u.id, u.name, u.email, u.student_id, u.course, u.year_level, u.section_id,
               s.section_name, s.section_code, s.semester, s.school_year
        FROM users u
        LEFT JOIN sections s ON u.section_id = s.id
        WHERE u.role = 'student' AND u.status IN ('approved','active')
        ORDER BY u.name
    ";
} else {
    $sql = "
        SELECT id, name, email, student_id, course, year_level,
               NULL as section_id, NULL as section_name, NULL as section_code,
               NULL as semester, NULL as school_year
        FROM users
        WHERE role = 'student' AND status IN ('approved','active')
        ORDER BY name
    ";
}

$result = $conn->query($sql);
$students = [];
while ($row = $result->fetch_assoc()) $students[] = $row;

echo json_encode(['success' => true, 'students' => $students]);
$conn->close();
?>
