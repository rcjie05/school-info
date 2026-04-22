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
requireRole('student');

$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];
$input   = json_decode(file_get_contents('php://input'), true);

$subject_id   = intval($input['subject_id'] ?? 0);
$request_type = $input['request_type'] ?? '';
$reason       = trim($input['reason'] ?? '');

if (!$subject_id || !in_array($request_type, ['add', 'drop'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}
if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a reason.']);
    exit();
}

// Check for existing pending request for same subject+type
$chk = $conn->prepare("SELECT id FROM add_drop_requests WHERE student_id=? AND subject_id=? AND request_type=? AND status='pending'");
$chk->bind_param('iis', $user_id, $subject_id, $request_type);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You already have a pending ' . $request_type . ' request for this subject.']);
    exit();
}

// For DROP: make sure subject is in their load
if ($request_type === 'drop') {
    $chk2 = $conn->prepare("SELECT id FROM study_loads WHERE student_id=? AND subject_id=?");
    $chk2->bind_param('ii', $user_id, $subject_id);
    $chk2->execute();
    if ($chk2->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Subject is not in your study load.']);
        exit();
    }
}

// For ADD: make sure subject is NOT already in their load
if ($request_type === 'add') {
    $chk3 = $conn->prepare("SELECT id FROM study_loads WHERE student_id=? AND subject_id=?");
    $chk3->bind_param('ii', $user_id, $subject_id);
    $chk3->execute();
    if ($chk3->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Subject is already in your study load.']);
        exit();
    }
}

$stmt = $conn->prepare("INSERT INTO add_drop_requests (student_id, subject_id, request_type, reason) VALUES (?, ?, ?, ?)");
$stmt->bind_param('iiss', $user_id, $subject_id, $request_type, $reason);

if ($stmt->execute()) {
    $type_label = $request_type === 'add' ? 'Add' : 'Drop';
    echo json_encode(['success' => true, 'message' => $type_label . ' request submitted successfully. Please wait for registrar approval.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit request.']);
}

$conn->close();
?>
