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
// Allow teachers and registrars to submit leave
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['teacher', 'registrar', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];
$input   = json_decode(file_get_contents('php://input'), true);

$leave_type_id = intval($input['leave_type_id'] ?? 0);
$start_date    = $input['start_date'] ?? '';
$end_date      = $input['end_date'] ?? '';
$reason        = sanitizeInput($input['reason'] ?? '');

if (!$leave_type_id || !$start_date || !$end_date || !$reason) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (strtotime($end_date) < strtotime($start_date)) {
    echo json_encode(['success' => false, 'message' => 'End date cannot be before start date']);
    exit;
}

// Calculate working days (Mon-Fri only)
$total_days = 0;
$current = strtotime($start_date);
$end     = strtotime($end_date);
while ($current <= $end) {
    $dow = date('N', $current); // 1=Mon, 7=Sun
    if ($dow < 6) $total_days++;
    $current = strtotime('+1 day', $current);
}
if ($total_days < 1) $total_days = 1;

// Check for overlapping pending/approved leave
$overlap = $conn->prepare("
    SELECT id FROM hr_leave_requests
    WHERE user_id=? AND status IN ('pending','approved')
    AND NOT (end_date < ? OR start_date > ?)
");
$overlap->bind_param("iss", $user_id, $start_date, $end_date);
$overlap->execute();
if ($overlap->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You already have a leave request overlapping these dates']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO hr_leave_requests (user_id, leave_type_id, start_date, end_date, total_days, reason) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("iissss", $user_id, $leave_type_id, $start_date, $end_date, $total_days, $reason);

if ($stmt->execute()) {
    $new_id = $conn->insert_id;
    // Notify admins
    $admins = $conn->query("SELECT id FROM users WHERE role='admin' AND status='active'");
    $userName = $conn->query("SELECT name FROM users WHERE id=$user_id")->fetch_assoc()['name'];
    while ($admin = $admins->fetch_assoc()) {
        createNotification($conn, $admin['id'], "New Leave Request 📋", "$userName submitted a leave request ($start_date to $end_date)");
    }
    echo json_encode(['success' => true, 'message' => 'Leave request submitted successfully', 'id' => $new_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit leave request']);
}

$conn->close();
?>
