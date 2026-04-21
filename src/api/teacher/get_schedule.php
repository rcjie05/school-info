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
requireRole('teacher');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get all schedules from sections where this teacher is assigned to a subject
$stmt = $conn->prepare("
    SELECT 
        sec.id AS section_id,
        sec.section_name,
        sec.section_code,
        sec.course,
        sec.year_level,
        sec.semester,
        sec.school_year,
        sub.subject_code,
        sub.subject_name,
        sub.units,
        sch.day_of_week,
        sch.room,
        TIME_FORMAT(sch.start_time, '%h:%i %p') AS start_time_fmt,
        TIME_FORMAT(sch.end_time,   '%h:%i %p') AS end_time_fmt,
        (SELECT COUNT(*) FROM users u2 WHERE u2.section_id = sec.id AND u2.role = 'student') AS student_count
    FROM section_subjects ss
    JOIN sections sec ON ss.section_id = sec.id
    JOIN subjects sub ON ss.subject_id = sub.id
    JOIN section_schedules sch ON sch.section_subject_id = ss.id
    WHERE ss.teacher_id = ?
    ORDER BY 
        FIELD(sch.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
        sch.start_time
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$schedule_by_day = [
    'Monday'    => [],
    'Tuesday'   => [],
    'Wednesday' => [],
    'Thursday'  => [],
    'Friday'    => [],
    'Saturday'  => [],
    'Sunday'    => []
];

while ($row = $result->fetch_assoc()) {
    $day = $row['day_of_week'];
    if (!array_key_exists($day, $schedule_by_day)) continue;
    $schedule_by_day[$day][] = [
        'subject_code'  => $row['subject_code'],
        'subject_name'  => $row['subject_name'],
        'units'         => $row['units'],
        'section'       => $row['section_name'],
        'section_code'  => $row['section_code'],
        'course'        => $row['course'],
        'year_level'    => $row['year_level'],
        'semester'      => $row['semester'],
        'school_year'   => $row['school_year'],
        'time'          => $row['start_time_fmt'] . ' - ' . $row['end_time_fmt'],
        'room'          => $row['room'] ?: 'TBA',
        'student_count' => (int)$row['student_count']
    ];
}

echo json_encode([
    'success'  => true,
    'schedule' => $schedule_by_day
]);

$conn->close();
?>
