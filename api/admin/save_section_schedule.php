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
requireRoleApi('admin');

$data = json_decode(file_get_contents('php://input'), true);

$schedule_id        = $data['schedule_id']        ?? null;
$section_id         = (int)($data['section_id']   ?? 0);
$section_subject_id = (int)($data['section_subject_id'] ?? 0);
$day_of_week        = $data['day_of_week']         ?? '';
$start_time         = $data['start_time']          ?? '';
$end_time           = $data['end_time']            ?? '';
$room               = trim($data['room']           ?? '');
$building           = trim($data['building']       ?? '');
$floor              = trim($data['floor']          ?? '');
$class_type         = strtoupper(trim($data['class_type'] ?? 'LEC'));

if (!in_array($class_type, ['LEC', 'LAB'])) {
    echo json_encode(['success' => false, 'message' => 'Class type must be LEC or LAB']);
    exit();
}

if (!$section_id || !$section_subject_id || !$day_of_week || !$start_time || !$end_time) {
    echo json_encode(['success' => false, 'message' => 'Section, subject, day, and time are required']);
    exit();
}

if ($start_time >= $end_time) {
    echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Conflict check: same section, same day, overlapping time
$conflict_sql = "SELECT sch.id FROM section_schedules sch
    WHERE sch.section_id = ? AND sch.day_of_week = ?
    AND ? < sch.end_time AND ? > sch.start_time";
$conflict_params = [$section_id, $day_of_week, $start_time, $end_time];

if ($schedule_id) {
    $conflict_sql .= " AND sch.id != ?";
    $conflict_params[] = (int)$schedule_id;
}

$check = $conn->prepare($conflict_sql);
if ($schedule_id) {
    $check->bind_param('isssi', ...$conflict_params);
} else {
    $check->bind_param('isss', ...$conflict_params);
}
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Schedule conflict: another subject is scheduled at this time']);
    exit();
}

if ($schedule_id) {
    $stmt = $conn->prepare("UPDATE section_schedules SET section_subject_id=?, class_type=?, day_of_week=?, start_time=?, end_time=?, room=?, building=?, floor=? WHERE id=?");
    $stmt->bind_param('isssssssi', $section_subject_id, $class_type, $day_of_week, $start_time, $end_time, $room, $building, $floor, $schedule_id);
    $ok = $stmt->execute();
    $msg = $ok ? 'Schedule updated' : 'Failed to update schedule';
} else {
    // ── Hour limit validation ──
    // Check if subject_type column exists (pre-migration safety)
    $colCheck = $conn->query("SHOW COLUMNS FROM subjects LIKE 'subject_type'");
    $hasSubjectType = ($colCheck && $colCheck->num_rows > 0);
    $subjectTypeCol = $hasSubjectType ? "sub.subject_type" : "'major' AS subject_type";

    $typeStmt = $conn->prepare("
        SELECT $subjectTypeCol
        FROM section_subjects ss
        JOIN subjects sub ON ss.subject_id = sub.id
        WHERE ss.id = ?
    ");
    $typeStmt->bind_param('i', $section_subject_id);
    $typeStmt->execute();
    $typeRow = $typeStmt->get_result()->fetch_assoc();
    $typeStmt->close();

    if ($typeRow) {
        $subject_type = in_array($typeRow['subject_type'] ?? '', ['major','minor']) ? $typeRow['subject_type'] : 'major';
        $max_minutes  = ($subject_type === 'major') ? 360 : 180; // 6h or 3h

        $hoursStmt = $conn->prepare("
            SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) AS total_minutes
            FROM section_schedules
            WHERE section_subject_id = ?
        ");
        $hoursStmt->bind_param('i', $section_subject_id);
        $hoursStmt->execute();
        $hoursRow = $hoursStmt->get_result()->fetch_assoc();
        $hoursStmt->close();

        $already_minutes = (int)($hoursRow['total_minutes'] ?? 0);
        $new_start   = strtotime("2000-01-01 $start_time");
        $new_end     = strtotime("2000-01-01 $end_time");
        $new_minutes = (int)(($new_end - $new_start) / 60);
        $total_after = $already_minutes + $new_minutes;

        if ($total_after > $max_minutes) {
            $max_hours  = $max_minutes / 60;
            $remaining  = max(0, $max_minutes - $already_minutes);
            $rem_h = floor($remaining / 60);
            $rem_m = $remaining % 60;
            $rem_str = ($rem_h > 0 ? "{$rem_h}h " : "") . ($rem_m > 0 ? "{$rem_m}m" : "0m");
            echo json_encode([
                'success' => false,
                'message' => "⏱️ Hour limit exceeded! A {$subject_type} subject allows a maximum of {$max_hours} hours total (LAB + LEC combined). You only have " . trim($rem_str) . " remaining."
            ]);
            exit();
        }
    }

    $stmt = $conn->prepare("INSERT INTO section_schedules (section_id, section_subject_id, class_type, day_of_week, start_time, end_time, room, building, floor) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('iisssssss', $section_id, $section_subject_id, $class_type, $day_of_week, $start_time, $end_time, $room, $building, $floor);
    $ok = $stmt->execute();
    if ($ok) {
        logAction($conn, $_SESSION['user_id'], "Added schedule slot to section (section_id=$section_id)", 'section_schedules', $conn->insert_id);
    }
    $msg = $ok ? 'Schedule slot added' : 'Failed to add schedule';
}

echo json_encode(['success' => $ok, 'message' => $ok ? $msg : $conn->error]);
$stmt->close();
$conn->close();
?>
