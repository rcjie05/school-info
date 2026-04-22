<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('student');
header('Content-Type: application/json');

$conn = getDBConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

// Get faculty basic info
$stmt = $conn->prepare("
    SELECT id, name, email, department, office_location, office_hours, role, status, avatar_url
    FROM users
    WHERE id = ? AND role IN ('teacher', 'registrar') AND status = 'active'
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$faculty = $result->fetch_assoc();

if (!$faculty) {
    echo json_encode(['success' => false, 'message' => 'Faculty member not found']);
    exit;
}

// Resolve avatar URL
$faculty['avatar_url'] = getAvatarUrl($faculty['avatar_url']);

// Get specialties (for teachers)
$specialties = [];
if ($faculty['role'] === 'teacher') {
    $spec_stmt = $conn->prepare("
        SELECT 
            ts.proficiency_level,
            ts.is_primary,
            s.subject_code,
            s.subject_name,
            s.units,
            s.course,
            s.year_level
        FROM teacher_specialties ts
        INNER JOIN subjects s ON ts.subject_id = s.id
        WHERE ts.teacher_id = ? AND s.status = 'active'
        ORDER BY ts.is_primary DESC, s.subject_code ASC
    ");
    $spec_stmt->bind_param("i", $id);
    $spec_stmt->execute();
    $spec_result = $spec_stmt->get_result();
    while ($row = $spec_result->fetch_assoc()) {
        $specialties[] = $row;
    }
}

// Get current classes/schedule
$classes = [];
$class_stmt = $conn->prepare("
    SELECT DISTINCT
        ss.day_of_week,
        ss.start_time,
        ss.end_time,
        ss.room,
        sub.subject_name,
        sub.subject_code,
        sec.section_name,
        sec.course,
        sec.year_level
    FROM section_schedules ss
    INNER JOIN section_subjects ssub ON ss.section_subject_id = ssub.id
    INNER JOIN subjects sub ON ssub.subject_id = sub.id
    INNER JOIN sections sec ON ssub.section_id = sec.id
    WHERE ssub.teacher_id = ?
    ORDER BY FIELD(ss.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), ss.start_time
");
$class_stmt->bind_param("i", $id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
while ($row = $class_result->fetch_assoc()) {
    $classes[] = $row;
}

echo json_encode([
    'success' => true,
    'faculty' => $faculty,
    'specialties' => $specialties,
    'classes' => $classes
]);

$conn->close();
?>
