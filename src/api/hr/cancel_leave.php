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

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['teacher', 'registrar', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];
$input   = json_decode(file_get_contents('php://input'), true);
$leave_id = intval($input['leave_id'] ?? 0);

if (!$leave_id) {
    echo json_encode(['success' => false, 'message' => 'Leave ID required']);
    exit;
}

// Only allow cancelling own pending requests
$stmt = $conn->prepare("SELECT id, status FROM hr_leave_requests WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $leave_id, $user_id);
$stmt->execute();
$leave = $stmt->get_result()->fetch_assoc();

if (!$leave) {
    echo json_encode(['success' => false, 'message' => 'Leave request not found']);
    exit;
}
if ($leave['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Only pending requests can be cancelled']);
    exit;
}

$stmt = $conn->prepare("UPDATE hr_leave_requests SET status='cancelled' WHERE id=?");
$stmt->bind_param("i", $leave_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Leave request cancelled']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel leave request']);
}

$conn->close();
?>
