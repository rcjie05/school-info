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
requireRole('student');

$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];

// Check if new section tables exist
$has_sec_sched = $conn->query("SHOW TABLES LIKE 'section_schedules'")->num_rows > 0;
$has_sec_subj  = $conn->query("SHOW TABLES LIKE 'section_subjects'")->num_rows  > 0;
$has_sl_sec    = $conn->query("SHOW COLUMNS FROM `study_loads` LIKE 'section_id'")->num_rows > 0;

if ($has_sec_sched && $has_sec_subj && $has_sl_sec) {
    $stmt = $conn->prepare("
        SELECT
            s.subject_code, s.subject_name, s.units,
            sec.section_name, sec.section_code,
            u.name AS teacher_name,
            sc.day_of_week,
            sc.start_time,
            sc.end_time,
            sc.room,
            sc.building
        FROM study_loads sl
        JOIN subjects s ON sl.subject_id = s.id
        LEFT JOIN sections sec ON sl.section_id = sec.id
        LEFT JOIN users u ON sl.teacher_id = u.id
        LEFT JOIN section_subjects ss
               ON ss.section_id = sl.section_id AND ss.subject_id = sl.subject_id
        LEFT JOIN section_schedules sc ON sc.section_subject_id = ss.id
        WHERE sl.student_id = ?
        ORDER BY FIELD(sc.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
                 sc.start_time
    ");
} else {
    $stmt = $conn->prepare("
        SELECT
            s.subject_code, s.subject_name, s.units,
            sl.section AS section_code, sl.section AS section_name,
            u.name AS teacher_name,
            sch.day_of_week, sch.start_time, sch.end_time, sch.room, sch.building
        FROM study_loads sl
        JOIN subjects s ON sl.subject_id = s.id
        LEFT JOIN schedules sch ON sch.study_load_id = sl.id
        LEFT JOIN users u ON sl.teacher_id = u.id
        WHERE sl.student_id = ?
        ORDER BY sch.day_of_week, sch.start_time
    ");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$scheduleGrid = [];  // for timetable grid: day -> hour -> entry
$classesMap   = [];  // for class list

while ($row = $result->fetch_assoc()) {
    $key = $row['subject_code'];

    if (!isset($classesMap[$key])) {
        $classesMap[$key] = [
            'subject_code' => $row['subject_code'],
            'subject_name' => $row['subject_name'],
            'units'        => $row['units'],
            'section'      => $row['section_name'] ?? 'TBA',
            'teacher_name' => $row['teacher_name'] ?: 'TBA',
            'schedule'     => '',
            'room'         => ''
        ];
    }

    if (!empty($row['day_of_week']) && !empty($row['start_time'])) {
        $startH   = (int)date('G', strtotime($row['start_time'])); // 0-23 hour
        $startFmt = date('g:i A', strtotime($row['start_time']));
        $endFmt   = date('g:i A', strtotime($row['end_time']));
        $roomStr  = trim(($row['building'] ? $row['building'] . ' - ' : '') . ($row['room'] ?? ''), ' -');

        // Grid: index by day + starting hour
        $gridKey = $row['day_of_week'] . '_' . $startH;
        if (!isset($scheduleGrid[$gridKey])) {
            $scheduleGrid[$gridKey] = [
                'day'          => $row['day_of_week'],
                'hour'         => $startH,
                'start_time'   => date('H:i', strtotime($row['start_time'])),
                'end_time'     => date('H:i', strtotime($row['end_time'])),
                'start_fmt'    => $startFmt,
                'end_fmt'      => $endFmt,
                'subject_code' => $row['subject_code'],
                'subject_name' => $row['subject_name'],
                'room'         => $roomStr,
                'teacher'      => $row['teacher_name'] ?: 'TBA'
            ];
        }

        // Class list schedule string
        $schedStr = $row['day_of_week'] . ' ' . $startFmt . '–' . $endFmt;
        if (strpos($classesMap[$key]['schedule'], $schedStr) === false) {
            $classesMap[$key]['schedule'] .= ($classesMap[$key]['schedule'] ? ', ' : '') . $schedStr;
        }
        if (!$classesMap[$key]['room'] && $roomStr) {
            $classesMap[$key]['room'] = $roomStr;
        }
    }
}

echo json_encode([
    'success'  => true,
    'schedule' => array_values($scheduleGrid),
    'classes'  => array_values($classesMap)
]);
$conn->close();
?>
