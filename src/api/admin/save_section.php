<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('admin');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$section_id   = $data['section_id']   ?? null;
$section_name = trim($data['section_name'] ?? '');
$section_code = trim($data['section_code'] ?? '');
$course       = trim($data['course']       ?? '');
$year_level   = trim($data['year_level']   ?? '');
$semester     = trim($data['semester']     ?? '');
$school_year  = trim($data['school_year']  ?? '');
$max_students = (int)($data['max_students'] ?? 40);
$room         = trim($data['room']         ?? '');
$building     = trim($data['building']     ?? '');
$adviser_id   = !empty($data['adviser_id']) ? (int)$data['adviser_id'] : null;
$status       = $data['status']            ?? 'active';

if (!$section_name || !$section_code) {
    echo json_encode(['success' => false, 'message' => 'Section name and code are required']);
    exit();
}

if ($section_id) {
    // Update
    $stmt = $conn->prepare("UPDATE sections SET section_name=?, section_code=?, course=?, year_level=?, semester=?, school_year=?, max_students=?, room=?, building=?, adviser_id=?, status=? WHERE id=?");
    $stmt->bind_param('ssssssiisssi', $section_name, $section_code, $course, $year_level, $semester, $school_year, $max_students, $room, $building, $adviser_id, $status, $section_id);
    
    if ($stmt->execute()) {
        logAction($conn, $_SESSION['user_id'], "Updated section: $section_code", 'sections', $section_id);
        echo json_encode(['success' => true, 'message' => 'Section updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update: ' . $conn->error]);
    }
} else {
    // Insert
    $stmt = $conn->prepare("INSERT INTO sections (section_name, section_code, course, year_level, semester, school_year, max_students, room, building, adviser_id, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('ssssssiisss', $section_name, $section_code, $course, $year_level, $semester, $school_year, $max_students, $room, $building, $adviser_id, $status);
    
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        logAction($conn, $_SESSION['user_id'], "Created section: $section_code", 'sections', $new_id);
        echo json_encode(['success' => true, 'message' => 'Section created successfully', 'section_id' => $new_id]);
    } else {
        $err = $conn->error;
        if (strpos($err, 'Duplicate entry') !== false) {
            echo json_encode(['success' => false, 'message' => "Section code '$section_code' already exists"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create: ' . $err]);
        }
    }
}

$stmt->close();
$conn->close();
?>
