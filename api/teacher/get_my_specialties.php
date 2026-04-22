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
requireRole('teacher');

$conn = getDBConnection();
$teacher_id = $_SESSION['user_id'];

$sql = "
    SELECT 
        ts.id,
        ts.proficiency_level,
        ts.is_primary,
        ts.assigned_date,
        s.id as subject_id,
        s.subject_code,
        s.subject_name,
        s.description,
        s.units,
        s.course,
        s.year_level,
        s.prerequisites
    FROM teacher_specialties ts
    INNER JOIN subjects s ON ts.subject_id = s.id
    WHERE ts.teacher_id = ? AND s.status = 'active'
    ORDER BY ts.is_primary DESC, s.subject_code ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

$specialties = [];
while ($row = $result->fetch_assoc()) {
    $specialties[] = $row;
}

echo json_encode([
    'success' => true,
    'specialties' => $specialties
]);

$conn->close();
?>
