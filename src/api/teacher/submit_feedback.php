<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('teacher');

header('Content-Type: application/json');

$conn = getDBConnection();
$input = json_decode(file_get_contents('php://input'), true);

$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');
$user_id = $_SESSION['user_id'];

if (!$subject || !$message) {
    echo json_encode(['success' => false, 'message' => 'Subject and message are required.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO feedback (user_id, subject, message, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
$stmt->bind_param("iss", $user_id, $subject, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit feedback.']);
}

$conn->close();
?>
