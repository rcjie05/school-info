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
requireRole('admin');

$conn = getDBConnection();

$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : null;
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : null;

$sql = "
    SELECT 
        ts.id,
        ts.teacher_id,
        ts.subject_id,
        ts.proficiency_level,
        ts.is_primary,
        ts.assigned_date,
        u.name as teacher_name,
        s.subject_code,
        s.subject_name
    FROM teacher_specialties ts
    INNER JOIN users u ON ts.teacher_id = u.id
    INNER JOIN subjects s ON ts.subject_id = s.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($teacher_id) {
    $sql .= " AND ts.teacher_id = ?";
    $params[] = $teacher_id;
    $types .= "i";
}

if ($subject_id) {
    $sql .= " AND ts.subject_id = ?";
    $params[] = $subject_id;
    $types .= "i";
}

$sql .= " ORDER BY u.name ASC, s.subject_code ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$specialties = [];
while ($row = $result->fetch_assoc()) {
    $specialties[] = $row;
}

echo json_encode([
    'success' => true,
    'specialties' => $specialties
]);

$conn->close();
?>
