<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('admin');
header('Content-Type: application/json');

$conn = getDBConnection();
$input = json_decode(file_get_contents('php://input'), true);

$feedback_id = $input['feedback_id'] ?? null;
$response    = $input['response']    ?? null;
$status      = $input['status']      ?? 'in_progress';

if (!$feedback_id) {
    echo json_encode(['success' => false, 'message' => 'Feedback ID is required.']);
    exit;
}

// If response provided, save it and set status
if ($response) {
    $stmt = $conn->prepare("UPDATE feedback SET response = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $response, $status, $feedback_id);
} else {
    // Just update status
    $stmt = $conn->prepare("UPDATE feedback SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $feedback_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Feedback updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update feedback.']);
}

$conn->close();
?>
