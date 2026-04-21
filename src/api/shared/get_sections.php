<?php
require_once '../config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$course     = $_GET['course']     ?? '';
$year_level = $_GET['year_level'] ?? '';
$semester   = $_GET['semester']   ?? '';

$where  = ["s.status = 'active'"];
$params = [];
$types  = '';

if ($course) {
    $where[]  = 's.course = ?';
    $params[] = $course;
    $types   .= 's';
}
if ($year_level) {
    $where[]  = 's.year_level = ?';
    $params[] = $year_level;
    $types   .= 's';
}
if ($semester) {
    $where[]  = 's.semester = ?';
    $params[] = $semester;
    $types   .= 's';
}

$whereStr = implode(' AND ', $where);
$sql = "SELECT id, section_name, section_code, course, year_level, semester, school_year, max_students
        FROM sections s
        WHERE $whereStr
        ORDER BY s.year_level, s.section_name";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$sections = [];
while ($row = $result->fetch_assoc()) {
    $sections[] = $row;
}

echo json_encode(['success' => true, 'sections' => $sections]);
$conn->close();
?>
