<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../php/config.php';
ob_clean();
header('Content-Type: application/json');
requireRole('hr');

$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];

// Safe column migration
$_col_check = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'announcements' AND COLUMN_NAME = 'deleted_at'");
if ($_col_check && $_col_check->fetch_assoc()['cnt'] == 0) {
    $conn->query("ALTER TABLE announcements ADD COLUMN deleted_at DATETIME DEFAULT NULL");
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['announcement_id'])) {
    echo json_encode(['success' => false, 'message' => 'Announcement ID is required']);
    exit();
}

$announcement_id = intval($input['announcement_id']);

// Fetch and verify ownership
$stmt = $conn->prepare("SELECT title, posted_by FROM announcements WHERE id = ?");
$stmt->bind_param("i", $announcement_id);
$stmt->execute();
$announcement = $stmt->get_result()->fetch_assoc();

if (!$announcement) {
    echo json_encode(['success' => false, 'message' => 'Announcement not found']);
    exit();
}

if ($announcement['posted_by'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'You can only delete your own announcements']);
    exit();
}

// Soft delete
$stmt = $conn->prepare("UPDATE announcements SET deleted_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $announcement_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => "'{$announcement['title']}' deleted successfully"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete announcement']);
}

$conn->close();
?>
