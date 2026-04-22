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

// Ensure soft-delete column exists
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

$stmt = $conn->prepare("SELECT title FROM announcements WHERE id = ?");
$stmt->bind_param("i", $announcement_id);
$stmt->execute();
$announcement = $stmt->get_result()->fetch_assoc();

if (!$announcement) {
    echo json_encode(['success' => false, 'message' => 'Announcement not found']);
    exit();
}

// Soft delete — move to recycle bin
$stmt = $conn->prepare("UPDATE announcements SET deleted_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $announcement_id);

if ($stmt->execute()) {
    logAction($conn, $admin_id, "Moved announcement to recycle bin: {$announcement['title']}", 'announcements', $announcement_id);
    echo json_encode(['success' => true, 'message' => "'{$announcement['title']}' moved to recycle bin"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete announcement']);
}

$conn->close();
