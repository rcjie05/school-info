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
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get teacher info
$stmt = $conn->prepare("SELECT name, email, department, office_location, office_hours, avatar_url FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get total classes count
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT sl.id) as class_count
    FROM study_loads sl
    WHERE sl.teacher_id = ? AND sl.status = 'finalized'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$class_stats = $stmt->get_result()->fetch_assoc();

// Get total students count across all classes
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT sl.student_id) as student_count
    FROM study_loads sl
    WHERE sl.teacher_id = ? AND sl.status = 'finalized'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_stats = $stmt->get_result()->fetch_assoc();

// Get today's classes
$today = date('l'); // Get day name
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT sch.id) as today_count
    FROM study_loads sl
    JOIN schedules sch ON sch.study_load_id = sl.id
    WHERE sl.teacher_id = ? 
    AND sl.status = 'finalized'
    AND sch.day_of_week = ?
");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_stats = $stmt->get_result()->fetch_assoc();

// Get today's schedule with class details
$stmt = $conn->prepare("
    SELECT 
        s.subject_code,
        s.subject_name,
        sl.section,
        sch.start_time,
        sch.end_time,
        sch.room,
        sch.building,
        COUNT(DISTINCT sl2.student_id) as student_count
    FROM study_loads sl
    JOIN subjects s ON sl.subject_id = s.id
    JOIN schedules sch ON sch.study_load_id = sl.id
    LEFT JOIN study_loads sl2 ON sl2.subject_id = s.id AND sl2.section = sl.section AND sl2.status = 'finalized'
    WHERE sl.teacher_id = ? 
    AND sl.status = 'finalized'
    AND sch.day_of_week = ?
    GROUP BY sl.id, s.subject_code, s.subject_name, sl.section, sch.start_time, sch.end_time, sch.room, sch.building
    ORDER BY sch.start_time
");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$schedule_result = $stmt->get_result();

$today_schedule = [];
while ($row = $schedule_result->fetch_assoc()) {
    $today_schedule[] = [
        'subject_code' => $row['subject_code'],
        'subject_name' => $row['subject_name'],
        'section' => $row['section'],
        'time' => date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time'])),
        'room' => $row['building'] . ' - ' . $row['room'],
        'student_count' => $row['student_count']
    ];
}

// Get all classes with student counts
$stmt = $conn->prepare("
    SELECT 
        s.subject_code,
        s.subject_name,
        s.units,
        sl.section,
        COUNT(DISTINCT sl2.student_id) as student_count,
        GROUP_CONCAT(DISTINCT CONCAT(sch.day_of_week, ' ', 
            DATE_FORMAT(sch.start_time, '%h:%i%p'), '-',
            DATE_FORMAT(sch.end_time, '%h:%i%p')) SEPARATOR ', ') as schedule
    FROM study_loads sl
    JOIN subjects s ON sl.subject_id = s.id
    LEFT JOIN study_loads sl2 ON sl2.subject_id = s.id AND sl2.section = sl.section AND sl2.teacher_id = sl.teacher_id AND sl2.status = 'finalized'
    LEFT JOIN schedules sch ON sch.study_load_id = sl.id
    WHERE sl.teacher_id = ? AND sl.status = 'finalized'
    GROUP BY sl.subject_id, sl.section, s.subject_code, s.subject_name, s.units
    ORDER BY s.subject_code
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$classes_result = $stmt->get_result();

$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = [
        'subject_code' => $row['subject_code'],
        'subject_name' => $row['subject_name'],
        'section' => $row['section'],
        'units' => $row['units'],
        'student_count' => $row['student_count'],
        'schedule' => $row['schedule'] ?: 'TBA'
    ];
}

// Get recent announcements
$stmt = $conn->prepare("
    SELECT title, content, DATE_FORMAT(created_at, '%M %d, %Y') as date
    FROM announcements
    WHERE target_audience IN ('all', 'teachers')
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute();
$announcements_result = $stmt->get_result();

$announcements = [];
while ($row = $announcements_result->fetch_assoc()) {
    $announcements[] = $row;
}

echo json_encode([
    'success' => true,
    'user' => [
        'name' => $user['name'],
        'email' => $user['email'],
        'department' => $user['department'],
        'office_location' => $user['office_location'] ?: 'Not assigned',
        'avatar_url' => getAvatarUrl($user['avatar_url'] ?? null),
        'office_hours' => $user['office_hours'] ?: 'TBA'
    ],
    'stats' => [
        'total_classes' => $class_stats['class_count'] ?: 0,
        'total_students' => $student_stats['student_count'] ?: 0,
        'today_classes' => $today_stats['today_count'] ?: 0,
        'office' => $user['office_location'] ?: '—'
    ],
    'today_schedule' => $today_schedule,
    'classes' => $classes,
    'announcements' => $announcements
]);

$conn->close();
?>
