<?php
require_once '../../php/config.php';

header('Content-Type: application/json');
requireRole('student');

$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];
$input   = json_decode(file_get_contents('php://input'), true);

$request_id = intval($input['request_id'] ?? 0);

if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
    exit();
}

// Verify the request belongs to this student and is still pending
$chk = $conn->prepare("SELECT id, request_type, status FROM add_drop_requests WHERE id = ? AND student_id = ?");
$chk->bind_param('ii', $request_id, $user_id);
$chk->execute();
$row = $chk->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit();
}

if ($row['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Only pending requests can be cancelled.']);
    exit();
}

// Delete the request
$del = $conn->prepare("DELETE FROM add_drop_requests WHERE id = ? AND student_id = ? AND status = 'pending'");
$del->bind_param('ii', $request_id, $user_id);

if ($del->execute() && $del->affected_rows > 0) {
    $type_label = $row['request_type'] === 'add' ? 'Add' : 'Drop';
    echo json_encode(['success' => true, 'message' => $type_label . ' request cancelled successfully.', 'request_type' => $row['request_type']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel request.']);
}

$conn->close();
?>
