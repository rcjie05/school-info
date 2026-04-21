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
requireRole('student');
$conn = getDBConnection();
$input = json_decode(file_get_contents('php://input'), true);

$feedback_id = intval($input['feedback_id'] ?? 0);
$user_reply  = trim($input['user_reply'] ?? '');
$user_id     = $_SESSION['user_id'];

if (!$feedback_id || !$user_reply) {
    echo json_encode(['success' => false, 'message' => 'Feedback ID and reply are required.']);
    exit;
}

// Make sure this feedback belongs to this student
$check = $conn->prepare("SELECT id, status FROM feedback WHERE id = ? AND user_id = ?");
$check->bind_param("ii", $feedback_id, $user_id);
$check->execute();
$row = $check->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Feedback not found.']);
    exit;
}

$stmt = $conn->prepare("UPDATE feedback SET user_reply = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("sii", $user_reply, $feedback_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Reply sent successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send reply.']);
}

$conn->close();
?>
