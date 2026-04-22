<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('admin');

header('Content-Type: application/json');

$section_id = (int)($_GET['section_id'] ?? 0);

if (!$section_id) {
    echo json_encode(['success' => false, 'message' => 'Section ID required']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$stmt = $conn->prepare("
    SELECT sch.*,
           ss.section_id,
           sub.subject_code, sub.subject_name, sub.subject_type,
           u.name AS teacher_name,
           TIME_FORMAT(sch.start_time, '%h:%i %p') AS start_time_fmt,
           TIME_FORMAT(sch.end_time,   '%h:%i %p') AS end_time_fmt,
           TIMESTAMPDIFF(MINUTE, CONCAT('2000-01-01 ', sch.start_time), CONCAT('2000-01-01 ', sch.end_time)) AS duration_minutes
    FROM section_schedules sch
    JOIN section_subjects ss ON sch.section_subject_id = ss.id
    JOIN subjects sub ON ss.subject_id = sub.id
    LEFT JOIN users u ON ss.teacher_id = u.id
    WHERE sch.section_id = ?
    ORDER BY FIELD(sch.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), sch.start_time
");
$stmt->bind_param('i', $section_id);
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = $row;
}

echo json_encode(['success' => true, 'schedules' => $schedules]);
$stmt->close();
$conn->close();
?>
