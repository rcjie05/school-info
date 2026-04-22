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
if (!isLoggedIn() || !hasRole('teacher')) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Get all classes (section-subject pairs) where this teacher is assigned
$stmt = $conn->prepare("
    SELECT
        ss.id AS section_subject_id,
        ss.section_id,
        ss.subject_id,
        sec.section_name,
        sec.section_code,
        sec.course,
        sec.year_level,
        sec.semester,
        sec.school_year,
        sub.subject_code,
        sub.subject_name,
        sub.units,
        GROUP_CONCAT(
            DISTINCT CONCAT(sch.day_of_week, ' ',
                TIME_FORMAT(sch.start_time,'%h:%i%p'), '-',
                TIME_FORMAT(sch.end_time,'%h:%i%p'))
            ORDER BY FIELD(sch.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
            SEPARATOR ', '
        ) AS schedule,
        GROUP_CONCAT(DISTINCT sch.room SEPARATOR ', ') AS rooms,
        (SELECT COUNT(*) FROM users u2 WHERE u2.section_id = sec.id AND u2.role = 'student') AS student_count
    FROM section_subjects ss
    JOIN sections sec ON ss.section_id = sec.id
    JOIN subjects sub ON ss.subject_id = sub.id
    LEFT JOIN section_schedules sch ON sch.section_subject_id = ss.id
    WHERE ss.teacher_id = ?
    GROUP BY ss.id, sec.id, sub.id
    ORDER BY sub.subject_code, sec.section_name
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$classes_result = $stmt->get_result();
$stmt->close();

$classes = [];
while ($class_row = $classes_result->fetch_assoc()) {
    // Get students in this section with their grades for this subject
    $stmt2 = $conn->prepare("
        SELECT
            u.id,
            u.name,
            u.student_id,
            u.email,
            u.course,
            u.year_level,
            g.midterm_grade,
            g.final_grade
        FROM users u
        LEFT JOIN grades g ON g.student_id = u.id AND g.subject_id = ?
            AND g.semester = ? AND g.school_year = ?
        WHERE u.section_id = ? AND u.role = 'student'
        ORDER BY u.name
    ");
    $semester    = $class_row['semester']    ?? '';
    $school_year = $class_row['school_year'] ?? '';
    $stmt2->bind_param('issi', $class_row['subject_id'], $semester, $school_year, $class_row['section_id']);
    $stmt2->execute();
    $students_result = $stmt2->get_result();
    $stmt2->close();

    $students = [];
    while ($student = $students_result->fetch_assoc()) {
        $students[] = [
            'id'            => $student['id'],
            'name'          => $student['name'],
            'student_id'    => $student['student_id'] ?: 'N/A',
            'email'         => $student['email'],
            'course'        => $student['course'],
            'year_level'    => $student['year_level'],
            'midterm_grade' => ($student['midterm_grade'] !== null) ? $student['midterm_grade'] : null,
            'final_grade'   => ($student['final_grade']   !== null) ? $student['final_grade']   : null,
        ];
    }

    $classes[] = [
        'subject_id'    => (int)$class_row['subject_id'],
        'section_id'    => (int)$class_row['section_id'],
        'subject_code'  => $class_row['subject_code'],
        'subject_name'  => $class_row['subject_name'],
        'units'         => $class_row['units'],
        'section'       => $class_row['section_name'],
        'section_code'  => $class_row['section_code'],
        'course'        => $class_row['course'],
        'year_level'    => $class_row['year_level'],
        'semester'      => $class_row['semester'],
        'school_year'   => $class_row['school_year'],
        'schedule'      => $class_row['schedule'] ?: 'TBA',
        'rooms'         => $class_row['rooms'] ?: 'TBA',
        'student_count' => (int)$class_row['student_count'],
        'students'      => $students,
    ];
}

echo json_encode([
    'success'        => true,
    'classes'        => $classes,
    'total_classes'  => count($classes),
    'total_students' => array_sum(array_column($classes, 'student_count')),
]);

$conn->close();
?>
