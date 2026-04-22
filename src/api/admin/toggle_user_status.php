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

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $input['user_id'] ?? null;
    $status = $input['status'] ?? null;
    $deactivated_until = $input['deactivated_until'] ?? null;
    $deactivation_reason = $input['deactivation_reason'] ?? null;
    
    if (!$user_id || !$status) {
        throw new Exception('User ID and status are required');
    }
    
    // Validate status
    $allowed_statuses = ['active', 'inactive', 'pending', 'approved', 'rejected'];
    if (!in_array($status, $allowed_statuses)) {
        throw new Exception('Invalid status value');
    }
    
    $conn = getDBConnection();
    
    if ($status === 'inactive') {
        // Deactivating user - require reason and optional end date
        if (empty($deactivation_reason)) {
            throw new Exception('Deactivation reason is required');
        }
        
        // Update user status with deactivation details
        $stmt = $conn->prepare("UPDATE users SET status = ?, deactivated_until = ?, deactivation_reason = ? WHERE id = ?");
        $stmt->bind_param("sssi", $status, $deactivated_until, $deactivation_reason, $user_id);
        
        if ($stmt->execute()) {
            // Log the action
            $action = $deactivated_until 
                ? "User suspended until " . date('Y-m-d H:i', strtotime($deactivated_until)) 
                : "User deactivated indefinitely";
            logAction($conn, $_SESSION['user_id'], $action, 'users', $user_id);
            
            echo json_encode([
                'success' => true,
                'message' => $deactivated_until 
                    ? "User suspended until " . date('M d, Y h:i A', strtotime($deactivated_until))
                    : "User deactivated successfully"
            ]);
        } else {
            throw new Exception('Failed to deactivate user');
        }
    } else {
        // Activating user - clear deactivation details
        $stmt = $conn->prepare("UPDATE users SET status = ?, deactivated_until = NULL, deactivation_reason = NULL WHERE id = ?");
        $stmt->bind_param("si", $status, $user_id);
        
        if ($stmt->execute()) {
            // Log the action
            logAction($conn, $_SESSION['user_id'], 'User activated/status changed', 'users', $user_id);
            
            echo json_encode([
                'success' => true,
                'message' => "User activated successfully"
            ]);
        } else {
            throw new Exception('Failed to update user status');
        }
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
