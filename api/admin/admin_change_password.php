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

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $current_password = $input['current_password'] ?? '';
    $new_password = $input['new_password'] ?? '';
    
    if (empty($current_password) || empty($new_password)) {
        throw new Exception('Current password and new password are required');
    }
    
    if (strlen($new_password) < 6) {
        throw new Exception('New password must be at least 6 characters long');
    }
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user || !password_verify($current_password, $user['password'])) {
        throw new Exception('Current password is incorrect');
    }
    
    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $admin_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update password');
    }
    
    $stmt->close();
    
    // Log action
    logAction($conn, $admin_id, "Changed own password", 'users', $admin_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
