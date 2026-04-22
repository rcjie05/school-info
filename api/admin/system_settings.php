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

// Handle GET request - fetch settings
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT setting_key, setting_value, description FROM system_settings ORDER BY setting_key");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = [
            'value' => $row['setting_value'],
            'description' => $row['description']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);
    exit();
}

// Handle POST request - update settings
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['settings']) || !is_array($input['settings'])) {
    echo json_encode(['success' => false, 'message' => 'Settings data is required']);
    exit();
}

$updated = 0;
foreach ($input['settings'] as $key => $value) {
    // Never overwrite an existing value with an empty string
    if ($value === '' || $value === null) {
        $check = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $check->bind_param("s", $key);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();
        if ($row && $row['setting_value'] !== '') continue; // skip — keep existing
    }
    // Use INSERT ... ON DUPLICATE KEY UPDATE so new settings are created automatically
    $stmt = $conn->prepare("
        INSERT INTO system_settings (setting_key, setting_value, description)
        VALUES (?, ?, '')
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->bind_param("ss", $key, $value);
    if ($stmt->execute()) {
        $updated++;
    }
}

if ($updated > 0) {
    logAction($conn, $admin_id, "Updated $updated system settings", 'system_settings', null);
    echo json_encode([
        'success' => true,
        'message' => "$updated settings updated successfully"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No settings were updated'
    ]);
}

$conn->close();
?>
