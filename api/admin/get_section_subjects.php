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
requireRole('admin');

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

// Gracefully handle pre-migration databases where subject_type column may not exist yet
$colCheck = $conn->query("SHOW COLUMNS FROM subjects LIKE 'subject_type'");
$hasSubjectType = ($colCheck && $colCheck->num_rows > 0);
$subjectTypeCol = $hasSubjectType ? "sub.subject_type" : "'major' AS subject_type";

$sql = "
    SELECT ss.id, ss.section_id, ss.subject_id, ss.teacher_id,
           sub.subject_code, sub.subject_name, sub.units,
           $subjectTypeCol,
           u.name AS teacher_name,
           (SELECT COUNT(*) FROM section_schedules sch WHERE sch.section_subject_id = ss.id) AS schedule_count,
           (SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, sch2.start_time, sch2.end_time)), 0)
            FROM section_schedules sch2 WHERE sch2.section_subject_id = ss.id) AS total_scheduled_minutes
    FROM section_subjects ss
    JOIN subjects sub ON ss.subject_id = sub.id
    LEFT JOIN users u ON ss.teacher_id = u.id
    WHERE ss.section_id = ?
    ORDER BY sub.subject_code
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $section_id);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    // Always cast to int so JS parseInt() gets a real number, never null
    $row['total_scheduled_minutes'] = (int)($row['total_scheduled_minutes'] ?? 0);
    // Always ensure a valid subject_type value
    $row['subject_type'] = in_array($row['subject_type'] ?? '', ['major', 'minor']) ? $row['subject_type'] : 'major';
    $subjects[] = $row;
}

echo json_encode(['success' => true, 'subjects' => $subjects]);
$stmt->close();
$conn->close();
?>
