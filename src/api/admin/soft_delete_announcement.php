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
requireRole('admin');

$conn     = getDBConnection();
$admin_id = $_SESSION['user_id'];

// Auto-add deleted_at column if missing
// Safe column migration
$_col_check = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'announcements' AND COLUMN_NAME = 'deleted_at'");
if ($_col_check && $_col_check->fetch_assoc()['cnt'] == 0) {
    $conn->query("ALTER TABLE announcements ADD COLUMN deleted_at DATETIME DEFAULT NULL");
}

$input = json_decode(file_get_contents('php://input'), true);
$id    = intval($input['announcement_id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Announcement ID required']);
    exit();
}

$stmt = $conn->prepare("SELECT title, deleted_at FROM announcements WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Announcement not found']);
    exit();
}
if ($row['deleted_at']) {
    echo json_encode(['success' => false, 'message' => 'Announcement is already in the recycle bin']);
    exit();
}

$stmt = $conn->prepare("UPDATE announcements SET deleted_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    logAction($conn, $admin_id, "Moved announcement to recycle bin: {$row['title']}", 'announcements', $id);
    echo json_encode(['success' => true, 'message' => "'{$row['title']}' moved to recycle bin"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete announcement']);
}
$conn->close();
