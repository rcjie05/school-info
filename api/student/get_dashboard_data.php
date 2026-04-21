<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
/**
 * Student Dashboard Data API
 * Returns: user info, enrollment stats (subjects count, total units, GPA), schedule, announcements
 */
ob_start();

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

header('Cache-Control: no-store');

// If session check fails, return JSON error instead of redirecting
if (!isLoggedIn()) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!hasRole('student')) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Access denied: not a student']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// ── 1. User info ─────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT name, email, student_id, course, year_level, status, avatar_url FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'User not found (id=' . $user_id . ')']);
    exit();
}

// ── 2. Study load stats ───────────────────────────────────────────────
// Count all study loads that are not explicitly dropped/rejected
// (includes finalized, draft, approved, active — whatever status the system uses)
$subject_count = 0;
$total_units   = 0;

$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT sl.id)     AS subject_count,
        COALESCE(SUM(s.units), 0) AS total_units
    FROM study_loads sl
    INNER JOIN subjects s ON s.id = sl.subject_id
    WHERE sl.student_id = ?
      AND sl.status NOT IN ('rejected', 'dropped')
");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $subject_count = (int)($row['subject_count'] ?? 0);
    $total_units   = (int)($row['total_units']   ?? 0);
} else {
    // Fallback: simpler query with no status filter
    $result = $conn->query("
        SELECT COUNT(DISTINCT sl.id) AS subject_count, COALESCE(SUM(s.units),0) AS total_units
        FROM study_loads sl
        INNER JOIN subjects s ON s.id = sl.subject_id
        WHERE sl.student_id = $user_id
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $subject_count = (int)($row['subject_count'] ?? 0);
        $total_units   = (int)($row['total_units']   ?? 0);
    }
}

// ── 3. GPA ────────────────────────────────────────────────────────────
$gpa = null;
$stmt = $conn->prepare("SELECT AVG(final_grade) AS gpa FROM grades WHERE student_id = ? AND final_grade IS NOT NULL");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!empty($row['gpa'])) {
        $gpa = number_format((float)$row['gpa'], 2);
    }
}

// ── 4. Weekly schedule ────────────────────────────────────────────────
$today          = date('l'); // e.g. "Tuesday"
$all_schedule   = [];
$today_schedule = [];

// Detect which schema variant is installed (safe checks)
$has_sec_sched = false;
$has_sec_subj  = false;
$has_sl_sec    = false;

$r = $conn->query("SHOW TABLES LIKE 'section_schedules'");
if ($r !== false) $has_sec_sched = ($r->num_rows > 0);

$r = $conn->query("SHOW TABLES LIKE 'section_subjects'");
if ($r !== false) $has_sec_subj = ($r->num_rows > 0);

$r = $conn->query("SHOW COLUMNS FROM `study_loads` LIKE 'section_id'");
if ($r !== false) $has_sl_sec = ($r->num_rows > 0);

$sched_stmt = null;

if ($has_sec_sched && $has_sec_subj && $has_sl_sec) {
    $sched_stmt = $conn->prepare("
        SELECT
            s.subject_code,
            s.subject_name,
            sec.section_name,
            u.name            AS teacher_name,
            sc.day_of_week,
            sc.start_time,
            sc.end_time,
            sc.room,
            sc.building
        FROM study_loads sl
        INNER JOIN subjects s       ON s.id  = sl.subject_id
        LEFT  JOIN sections sec     ON sec.id = sl.section_id
        LEFT  JOIN users u          ON u.id   = sl.teacher_id
        LEFT  JOIN section_subjects ss
               ON ss.section_id = sl.section_id AND ss.subject_id = sl.subject_id
        LEFT  JOIN section_schedules sc ON sc.section_subject_id = ss.id
        WHERE sl.student_id = ?
          AND sl.status NOT IN ('rejected', 'dropped')
        ORDER BY
            FIELD(sc.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
            sc.start_time
    ");
}

if (!$sched_stmt) {
    // Try legacy 'schedules' table
    $r = $conn->query("SHOW TABLES LIKE 'schedules'");
    if ($r !== false && $r->num_rows > 0) {
        $sched_stmt = $conn->prepare("
            SELECT
                s.subject_code,
                s.subject_name,
                sl.section  AS section_name,
                u.name      AS teacher_name,
                sch.day_of_week,
                sch.start_time,
                sch.end_time,
                sch.room,
                sch.building
            FROM study_loads sl
            INNER JOIN subjects s    ON s.id   = sl.subject_id
            LEFT  JOIN schedules sch ON sch.study_load_id = sl.id
            LEFT  JOIN users u       ON u.id   = sl.teacher_id
            WHERE sl.student_id = ?
              AND sl.status NOT IN ('rejected', 'dropped')
            ORDER BY sch.day_of_week, sch.start_time
        ");
    }
}

if ($sched_stmt) {
    $sched_stmt->bind_param("i", $user_id);
    $sched_stmt->execute();
    $res = $sched_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (empty($row['day_of_week']) || empty($row['start_time'])) continue;
        $room = trim(($row['building'] ? $row['building'] . ' - ' : '') . ($row['room'] ?? ''), ' -');
        $entry = [
            'day'          => $row['day_of_week'],
            'subject_code' => $row['subject_code'],
            'subject_name' => $row['subject_name'],
            'teacher_name' => $row['teacher_name'] ?: 'TBA',
            'start_time'   => date('g:i A', strtotime($row['start_time'])),
            'end_time'     => date('g:i A', strtotime($row['end_time'])),
            'time'         => date('g:i A', strtotime($row['start_time'])) . ' – ' . date('g:i A', strtotime($row['end_time'])),
            'room'         => $room ?: 'TBA',
            'section'      => $row['section_name'] ?? 'TBA',
        ];
        $all_schedule[] = $entry;
        if ($row['day_of_week'] === $today) $today_schedule[] = $entry;
    }
    $sched_stmt->close();
}

// ── 5. Enrollment status ─────────────────────────────────────────────
$enrollment_open = false;
$current_semester = '';
$res = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('registration_open','current_semester','current_school_year')");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if ($row['setting_key'] === 'registration_open')  $enrollment_open    = $row['setting_value'] === '1';
        if ($row['setting_key'] === 'current_semester')   $current_semester   = $row['setting_value'];
        if ($row['setting_key'] === 'current_school_year') $current_school_year = $row['setting_value'];
    }
}

// ── 6. Announcements ─────────────────────────────────────────────────
$announcements = [];
$stmt = $conn->prepare("
    SELECT title, content, DATE_FORMAT(created_at, '%M %d, %Y') AS date
    FROM announcements
    WHERE target_audience IN ('all', 'students')
    ORDER BY created_at DESC LIMIT 5
");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $announcements[] = $row;
    $stmt->close();
}

$conn->close();

ob_end_clean();
echo json_encode([
    'success' => true,
    'user' => [
        'name'       => $user['name'],
        'email'      => $user['email'],
        'student_id' => $user['student_id'],
        'course'     => $user['course'],
        'year_level' => $user['year_level'],
        'avatar_url' => getAvatarUrl($user['avatar_url'] ?? null),
    ],
    'stats' => [
        'enrollment_status' => ucfirst($user['status']),
        'enrolled_subjects' => $subject_count,
        'total_units'       => $total_units,
        'gpa'               => $gpa,
    ],
    'today'            => $today,
    'schedule'         => $today_schedule,
    'all_schedule'     => $all_schedule,
    'announcements'    => $announcements,
    'enrollment_open'  => $enrollment_open,
    'current_semester' => $current_semester,
    'school_year'      => $current_school_year,
], JSON_UNESCAPED_UNICODE);
