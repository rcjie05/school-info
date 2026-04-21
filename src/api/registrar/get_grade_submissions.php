<?php
ob_start();
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
header('Content-Type: application/json');

if (!isLoggedIn() || !hasRole('registrar')) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

// Auto-create table if missing
$conn->query("CREATE TABLE IF NOT EXISTS `grade_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `teacher_note` text DEFAULT NULL,
  `registrar_note` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_data` longblob DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1");

// Auto-add missing columns (for existing installs)
foreach (['file_path VARCHAR(500) DEFAULT NULL', 'file_data LONGBLOB DEFAULT NULL', 'file_name VARCHAR(255) DEFAULT NULL'] as $colDef) {
    $colName = explode(' ', $colDef)[0];
    $chk = $conn->query("SHOW COLUMNS FROM `grade_submissions` LIKE '{$colName}'");
    if ($chk && $chk->num_rows === 0) {
        $conn->query("ALTER TABLE `grade_submissions` ADD COLUMN {$colDef}");
    }
}

$status_filter = $_GET['status'] ?? '';

$sql = "
    SELECT
        gs.id, gs.status, gs.teacher_note, gs.registrar_note, gs.file_path, gs.file_name,
        (gs.file_data IS NOT NULL AND LENGTH(gs.file_data) > 0) AS has_file_data,
        gs.semester, gs.school_year,
        DATE_FORMAT(gs.submitted_at, '%M %d, %Y %h:%i %p') AS submitted_at,
        DATE_FORMAT(gs.reviewed_at,  '%M %d, %Y %h:%i %p') AS reviewed_at,
        t.name  AS teacher_name,
        sub.subject_code, sub.subject_name, sub.units,
        sec.section_name, sec.course, sec.year_level,
        rv.name AS reviewed_by_name,
        (SELECT COUNT(*) FROM users u2
         WHERE u2.section_id = gs.section_id AND u2.role = 'student') AS student_count,
        (SELECT COUNT(*) FROM grades g
         WHERE g.subject_id = gs.subject_id
           AND g.student_id IN (SELECT id FROM users WHERE section_id = gs.section_id AND role='student')
           AND g.final_grade IS NOT NULL) AS graded_count
    FROM grade_submissions gs
    JOIN users t    ON t.id   = gs.teacher_id
    JOIN subjects sub ON sub.id = gs.subject_id
    JOIN sections sec ON sec.id = gs.section_id
    LEFT JOIN users rv ON rv.id = gs.reviewed_by
";
if ($status_filter) {
    $sql .= " WHERE gs.status = '" . $conn->real_escape_string($status_filter) . "'";
}
$sql .= " ORDER BY gs.submitted_at DESC";

$result = $conn->query($sql);
$submissions = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }
}

// Count by status
$counts = $conn->query("
    SELECT status, COUNT(*) as cnt FROM grade_submissions GROUP BY status
")->fetch_all(MYSQLI_ASSOC);
$countMap = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($counts as $c) $countMap[$c['status']] = (int)$c['cnt'];

$conn->close();
ob_end_clean();
echo json_encode(['success' => true, 'submissions' => $submissions, 'counts' => $countMap]);
