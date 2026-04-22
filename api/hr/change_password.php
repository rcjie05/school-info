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
requireRole('hr');

$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];
$input   = json_decode(file_get_contents('php://input'), true);

$current = $input['current_password'] ?? '';
$new     = $input['new_password']     ?? '';

if (empty($current) || empty($new)) {
    echo json_encode(['success' => false, 'message' => 'All fields required']); exit;
}
if (strlen($new) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']); exit;
}

$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!password_verify($current, $row['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']); exit;
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $hash, $user_id);
$ok = $stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => $ok, 'message' => $ok ? 'Password changed' : 'Failed to change password']);
?>
