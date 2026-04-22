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
requireRole('registrar');

$conn = getDBConnection();

// Return empty if sections table doesn't exist yet
if ($conn->query("SHOW TABLES LIKE 'sections'")->num_rows === 0) {
    echo json_encode(['success' => true, 'sections' => []]);
    $conn->close();
    exit();
}

$status = $_GET['status'] ?? '';
$sql = "
    SELECT s.*, u.name AS adviser_name,
    (SELECT COUNT(*) FROM section_subjects ss WHERE ss.section_id = s.id) AS subject_count
    FROM sections s
    LEFT JOIN users u ON s.adviser_id = u.id
    " . ($status ? "WHERE s.status = '" . $conn->real_escape_string($status) . "'" : "") . "
    ORDER BY s.school_year DESC, s.year_level, s.section_name
";

$result = $conn->query($sql);
$sections = [];
while ($row = $result->fetch_assoc()) $sections[] = $row;

echo json_encode(['success' => true, 'sections' => $sections]);
$conn->close();
?>
