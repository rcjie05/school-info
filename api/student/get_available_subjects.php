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

// Get student's section_id
$sec_stmt = $conn->prepare("SELECT section_id FROM users WHERE id=?");
$sec_stmt->bind_param('i', $user_id);
$sec_stmt->execute();
$row = $sec_stmt->get_result()->fetch_assoc();
$section_id = $row['section_id'] ?? null;

if (!$section_id) {
    echo json_encode(['success' => true, 'subjects' => [], 'message' => 'No section assigned.']);
    $conn->close();
    exit();
}

// Get all subjects in their section NOT already in their study load
$stmt = $conn->prepare("
    SELECT s.id, s.subject_code, s.subject_name, s.units,
           u.name AS teacher,
           GROUP_CONCAT(
               DISTINCT CONCAT(sch.day_of_week,' ',
                   TIME_FORMAT(sch.start_time,'%h:%i%p'),'-',
                   TIME_FORMAT(sch.end_time,'%h:%i%p'))
               SEPARATOR ', '
           ) AS schedule
    FROM section_subjects ss
    JOIN subjects s ON ss.subject_id = s.id
    LEFT JOIN users u ON ss.teacher_id = u.id
    LEFT JOIN section_schedules sch ON sch.section_subject_id = ss.id
    WHERE ss.section_id = ?
    AND s.id NOT IN (SELECT subject_id FROM study_loads WHERE student_id = ?)
    GROUP BY s.id, s.subject_code, s.subject_name, s.units, u.name
    ORDER BY s.subject_code
");
$stmt->bind_param('ii', $section_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

echo json_encode(['success' => true, 'subjects' => $subjects]);
$conn->close();
?>
