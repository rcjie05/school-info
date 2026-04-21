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
requireRole('student');

$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get student's section
$sec_stmt = $conn->prepare("
    SELECT s.id, s.section_name, s.section_code, s.course, s.year_level,
           s.semester, s.school_year, s.room, s.building
    FROM users u
    JOIN sections s ON u.section_id = s.id
    WHERE u.id = ?
");
$sec_stmt->bind_param('i', $user_id);
$sec_stmt->execute();
$section = $sec_stmt->get_result()->fetch_assoc();

// Get study load + schedules pulled from section_schedules
$stmt = $conn->prepare("
    SELECT s.subject_code, s.subject_name, s.units,
           sl.section_id, sl.status,
           sec.section_name, sec.section_code AS section_code,
           u.name AS teacher,
           s.id AS subject_id,
           GROUP_CONCAT(
               DISTINCT CONCAT(ss_sch.day_of_week, ' ',
                   TIME_FORMAT(ss_sch.start_time,'%h:%i%p'),'-',
                   TIME_FORMAT(ss_sch.end_time,'%h:%i%p'))
               SEPARATOR ', '
           ) AS schedule,
           GROUP_CONCAT(
               DISTINCT CONCAT(IFNULL(ss_sch.room,''), IF(ss_sch.building IS NOT NULL,' (','' ), IFNULL(ss_sch.building,''), IF(ss_sch.building IS NOT NULL,')',''))
               SEPARATOR ', '
           ) AS room
    FROM study_loads sl
    JOIN subjects s ON sl.subject_id = s.id
    LEFT JOIN sections sec ON sl.section_id = sec.id
    LEFT JOIN users u ON sl.teacher_id = u.id
    LEFT JOIN section_subjects ss ON (ss.section_id = sl.section_id AND ss.subject_id = sl.subject_id)
    LEFT JOIN section_schedules ss_sch ON ss_sch.section_subject_id = ss.id
    WHERE sl.student_id = ?
    GROUP BY sl.id, s.subject_code, s.subject_name, s.units, sl.section_id, sl.status,
             sec.section_name, sec.section_code, u.name, s.id
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$subjects   = [];
$totalUnits = 0;
$status     = 'No Load';

while ($row = $result->fetch_assoc()) {
    $subjects[]  = $row;
    $totalUnits += intval($row['units']);
    $status      = ucfirst($row['status']);
}

echo json_encode([
    'success'  => true,
    'section'  => $section,
    'stats'    => [
        'total_subjects' => count($subjects),
        'total_units'    => $totalUnits,
        'status'         => $status
    ],
    'subjects' => $subjects
]);

$conn->close();
?>
