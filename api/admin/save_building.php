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

$conn = getDBConnection();
$admin_id = $_SESSION['user_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$building_id = isset($input['building_id']) ? intval($input['building_id']) : null;
$building_name = isset($input['building_name']) ? sanitizeInput($input['building_name']) : null;
$building_code = isset($input['building_code']) ? sanitizeInput($input['building_code']) : null;
$description = isset($input['description']) ? sanitizeInput($input['description']) : null;
$location = isset($input['location']) ? sanitizeInput($input['location']) : null;

// Validate required fields
if (!$building_name || !$building_code) {
    echo json_encode(['success' => false, 'message' => 'Building name and code are required']);
    exit();
}

if ($building_id) {
    // Update existing building
    $stmt = $conn->prepare("
        UPDATE buildings 
        SET building_name = ?, building_code = ?, description = ?, location = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssssi", $building_name, $building_code, $description, $location, $building_id);
    
    if ($stmt->execute()) {
        logAction($conn, $admin_id, "Updated building: $building_name", 'buildings', $building_id);
        echo json_encode(['success' => true, 'message' => 'Building updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update building: ' . $stmt->error]);
    }
} else {
    // Check if building code already exists
    $stmt = $conn->prepare("SELECT id FROM buildings WHERE building_code = ?");
    $stmt->bind_param("s", $building_code);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Building code already exists']);
        exit();
    }
    
    // Add new building
    $stmt = $conn->prepare("
        INSERT INTO buildings (building_name, building_code, description, location)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssss", $building_name, $building_code, $description, $location);
    
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        logAction($conn, $admin_id, "Added new building: $building_name", 'buildings', $new_id);
        echo json_encode(['success' => true, 'message' => 'Building added successfully', 'building_id' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add building: ' . $stmt->error]);
    }
}

$conn->close();
?>
