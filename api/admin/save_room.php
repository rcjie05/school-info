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

$conn     = getDBConnection();
$admin_id = $_SESSION['user_id'];
$input    = json_decode(file_get_contents('php://input'), true);

$room_id     = !empty($input['room_id'])     ? intval($input['room_id'])           : null;
$building_id = !empty($input['building_id']) ? intval($input['building_id'])        : null;
$room_number = !empty($input['room_number']) ? sanitizeInput($input['room_number']) : null;
$room_type   = !empty($input['room_type'])   ? sanitizeInput($input['room_type'])   : 'Classroom';
$floor       = isset($input['floor'])        ? sanitizeInput($input['floor'])       : '1';
$capacity    = (isset($input['capacity']) && $input['capacity'] !== '') ? intval($input['capacity']) : null;
$color       = !empty($input['color'])       ? sanitizeInput($input['color'])       : '#85C1E2';
$x_pos       = (isset($input['x_pos'])   && $input['x_pos']   !== '') ? intval($input['x_pos'])   : null;
$y_pos       = (isset($input['y_pos'])   && $input['y_pos']   !== '') ? intval($input['y_pos'])   : null;
$width       = (isset($input['width'])   && $input['width']   !== '') ? intval($input['width'])   : null;
$height      = (isset($input['height'])  && $input['height']  !== '') ? intval($input['height'])  : null;
$purpose     = isset($input['purpose'])  ? trim($input['purpose'])  : null;

// Ensure purpose and image_url columns exist
// Safe column migration
$_col_check = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rooms' AND COLUMN_NAME = 'purpose'");
if ($_col_check && $_col_check->fetch_assoc()['cnt'] == 0) {
    $conn->query("ALTER TABLE rooms ADD COLUMN purpose TEXT DEFAULT NULL");
}
$_col_check2 = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rooms' AND COLUMN_NAME = 'image_url'");
if ($_col_check2 && $_col_check2->fetch_assoc()['cnt'] == 0) {
    $conn->query("ALTER TABLE rooms ADD COLUMN image_url VARCHAR(500) DEFAULT NULL");
}

if (!$building_id || !$room_number) {
    echo json_encode(['success' => false, 'message' => 'Building and room name are required']);
    exit();
}

if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
    $color = '#85C1E2';
}

if ($room_id) {
    $stmt = $conn->prepare("UPDATE rooms SET room_number=?, building_id=?, room_type=?, floor=?, capacity=?, color=?, x_pos=?, y_pos=?, width=?, height=?, purpose=? WHERE id=?");
    if (!$stmt) { echo json_encode(['success'=>false,'message'=>$conn->error]); exit(); }
    $stmt->bind_param("sissisiiiisi", $room_number, $building_id, $room_type, $floor, $capacity, $color, $x_pos, $y_pos, $width, $height, $purpose, $room_id);
    if ($stmt->execute()) {
        logAction($conn, $admin_id, "Updated room: $room_number", 'rooms', $room_id);
        echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update: ' . $stmt->error]);
    }
} else {
    $chk = $conn->prepare("SELECT id FROM rooms WHERE room_number=? AND building_id=?");
    $chk->bind_param("si", $room_number, $building_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'A room with that name already exists in this building']);
        exit();
    }
    $chk->close();

    $stmt = $conn->prepare("INSERT INTO rooms (room_number,building_id,room_type,floor,capacity,color,x_pos,y_pos,width,height,purpose) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) { echo json_encode(['success'=>false,'message'=>$conn->error]); exit(); }
    $stmt->bind_param("sissisiiiiis", $room_number, $building_id, $room_type, $floor, $capacity, $color, $x_pos, $y_pos, $width, $height, $purpose);
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        logAction($conn, $admin_id, "Added room: $room_number", 'rooms', $new_id);
        echo json_encode(['success' => true, 'message' => 'Room created successfully', 'room_id' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create: ' . $stmt->error]);
    }
}

$conn->close();
?>
