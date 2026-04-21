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

$conn        = getDBConnection();
$building_id = isset($_GET['building_id']) ? intval($_GET['building_id']) : null;

if ($building_id) {
    $stmt = $conn->prepare("
        SELECT r.id, r.room_number, r.room_type, r.floor, r.capacity,
               r.color, r.x_pos, r.y_pos, r.width, r.height,
               r.purpose, r.image_url,
               b.building_name
        FROM rooms r
        JOIN buildings b ON b.id = r.building_id
        WHERE r.building_id = ?
        ORDER BY r.floor ASC, r.room_number ASC
    ");
    $stmt->bind_param("i", $building_id);
} else {
    $stmt = $conn->prepare("
        SELECT r.id, r.room_number, r.room_type, r.floor, r.capacity,
               r.color, r.x_pos, r.y_pos, r.width, r.height,
               r.purpose, r.image_url,
               b.building_name
        FROM rooms r
        JOIN buildings b ON b.id = r.building_id
        ORDER BY b.building_name ASC, r.floor ASC, r.room_number ASC
    ");
}

$stmt->execute();
$result = $stmt->get_result();
$rooms  = [];
while ($row = $result->fetch_assoc()) {
    $rooms[] = [
        'id'            => intval($row['id']),
        'room_number'   => $row['room_number'],
        'room_type'     => $row['room_type'],
        'floor'         => $row['floor'],
        'capacity'      => $row['capacity'],
        'color'         => $row['color'] ?? '#85C1E2',
        'x_pos'         => $row['x_pos'],
        'y_pos'         => $row['y_pos'],
        'width'         => $row['width'],
        'height'        => $row['height'],
        'building_name' => $row['building_name'],
        'purpose'       => $row['purpose'],
        'image_url'     => getAvatarUrl($row['image_url']),
        'on_floorplan'  => ($row['x_pos'] !== null && $row['y_pos'] !== null),
    ];
}

echo json_encode(['success' => true, 'rooms' => $rooms, 'total' => count($rooms)]);
$conn->close();
?>
