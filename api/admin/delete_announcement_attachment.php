<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../php/config.php';
ob_clean();
header('Content-Type: application/json');
requireRoleApi('admin');

$conn     = getDBConnection();
$admin_id = $_SESSION['user_id'];

$input         = json_decode(file_get_contents('php://input'), true);
$attachment_id = intval($input['attachment_id'] ?? 0);

if (!$attachment_id) {
    echo json_encode(['success' => false, 'message' => 'Attachment ID required']);
    exit();
}

// Fetch attachment info before deleting
$stmt = $conn->prepare("SELECT id, file_path, original_name, announcement_id FROM announcement_attachments WHERE id = ?");
$stmt->bind_param("i", $attachment_id);
$stmt->execute();
$att = $stmt->get_result()->fetch_assoc();

if (!$att) {
    echo json_encode(['success' => false, 'message' => 'Attachment not found']);
    exit();
}

// Delete the physical file
$projectRoot = dirname(__DIR__, 2);
$filePath    = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $att['file_path']);
if (file_exists($filePath)) {
    @unlink($filePath);
}

// Delete from DB
$stmt = $conn->prepare("DELETE FROM announcement_attachments WHERE id = ?");
$stmt->bind_param("i", $attachment_id);

if ($stmt->execute()) {
    logAction($conn, $admin_id, "Deleted attachment: {$att['original_name']}", 'announcements', $att['announcement_id']);
    echo json_encode(['success' => true, 'message' => 'Attachment deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete attachment']);
}

$conn->close();
?>
