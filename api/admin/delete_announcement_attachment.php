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
$projectRoot = dirname(__DIR__, 3);
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
