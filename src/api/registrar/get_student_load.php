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

$student_id = intval($_GET['student_id'] ?? 0);
$conn = getDBConnection();

$has_sections    = $conn->query("SHOW TABLES LIKE 'sections'")->num_rows > 0;
$has_sec_sched   = $conn->query("SHOW TABLES LIKE 'section_schedules'")->num_rows > 0;
$has_sec_subj    = $conn->query("SHOW TABLES LIKE 'section_subjects'")->num_rows > 0;
$has_section_col = $conn->query("SHOW COLUMNS FROM `users` LIKE 'section_id'")->num_rows > 0;
$has_sl_sec_col  = $conn->query("SHOW COLUMNS FROM `study_loads` LIKE 'section_id'")->num_rows > 0;

$section = null;

// Get student's section
if ($has_sections && $has_section_col) {
    $s = $conn->prepare("
        SELECT s.id, s.section_name, s.section_code, s.course, s.year_level,
               s.semester, s.school_year, s.room, s.building
        FROM users u JOIN sections s ON u.section_id = s.id
        WHERE u.id = ?
    ");
    $s->bind_param('i', $student_id);
    $s->execute();
    $section = $s->get_result()->fetch_assoc();
}

// Get study load
if ($has_sec_sched && $has_sec_subj && $has_sl_sec_col) {
    $stmt = $conn->prepare("
        SELECT sl.id AS load_id, s.id AS subject_id, s.subject_code, s.subject_name, s.units,
               sl.section_id, sl.status, u.name AS teacher_name,
               GROUP_CONCAT(DISTINCT CONCAT(sc.day_of_week,' ',
                   TIME_FORMAT(sc.start_time,'%h:%i%p'),'-',
                   TIME_FORMAT(sc.end_time,'%h:%i%p')) SEPARATOR ', ') AS schedule,
               GROUP_CONCAT(DISTINCT IFNULL(sc.room,'') SEPARATOR ', ') AS room
        FROM study_loads sl
        JOIN subjects s ON sl.subject_id = s.id
        LEFT JOIN users u ON sl.teacher_id = u.id
        LEFT JOIN section_subjects ss ON ss.section_id = sl.section_id AND ss.subject_id = sl.subject_id
        LEFT JOIN section_schedules sc ON sc.section_subject_id = ss.id
        WHERE sl.student_id = ?
        GROUP BY sl.id, s.id, s.subject_code, s.subject_name, s.units, sl.section_id, sl.status, u.name
    ");
} else {
    $stmt = $conn->prepare("
        SELECT sl.id AS load_id, s.id AS subject_id, s.subject_code, s.subject_name, s.units,
               NULL AS section_id, sl.status, u.name AS teacher_name,
               NULL AS schedule, NULL AS room
        FROM study_loads sl
        JOIN subjects s ON sl.subject_id = s.id
        LEFT JOIN users u ON sl.teacher_id = u.id
        WHERE sl.student_id = ?
        GROUP BY sl.id, s.id, s.subject_code, s.subject_name, s.units, sl.status, u.name
    ");
}
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

$load = [];
while ($row = $result->fetch_assoc()) {
    $row['room'] = trim($row['room'] ?? '', ' -,');
    $load[] = $row;
}

echo json_encode(['success' => true, 'load' => $load, 'section' => $section]);
$conn->close();
?>
