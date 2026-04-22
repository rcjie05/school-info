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

$input = json_decode(file_get_contents('php://input'), true);
$type  = $input['type'] ?? '';
$id    = $input['id']   ?? '';

switch ($type) {

    case 'announcement':
        $nm = $conn->query("SELECT title FROM announcements WHERE id = " . intval($id))->fetch_assoc()['title'] ?? '';
        $stmt = $conn->prepare("UPDATE announcements SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            logAction($conn, $admin_id, "Restored announcement: $nm", 'announcements', $id);
            echo json_encode(['success' => true, 'message' => "Announcement restored successfully"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Restore failed or item not in recycle bin']);
        }
        break;

    case 'grade_sheet':
        $stmt = $conn->prepare("UPDATE grade_submissions SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            logAction($conn, $admin_id, "Restored grade sheet id=$id", 'grade_submissions', $id);
            echo json_encode(['success' => true, 'message' => 'Grade sheet restored successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Restore failed or item not in recycle bin']);
        }
        break;

    case 'avatar':
        // For avatars: move file from deleted_avatars back to avatars folder
        $file_path = $id;
        if (!file_exists($file_path) || strpos($file_path, 'deleted_avatars') === false) {
            echo json_encode(['success' => false, 'message' => 'File not found in recycle bin']);
            break;
        }
        $filename     = basename($file_path);
        $restore_path = str_replace('deleted_avatars/', 'avatars/', $file_path);
        if (rename($file_path, $restore_path)) {
            // Extract user_id from filename pattern: avatar_{user_id}_...
            preg_match('/avatar_(\d+)_/', $filename, $m);
            $uid = intval($m[1] ?? 0);
            if ($uid) {
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->bind_param("si", $restore_path, $uid);
                $stmt->execute();
            }
            logAction($conn, $admin_id, "Restored avatar file: $filename", 'users', $uid);
            echo json_encode(['success' => true, 'message' => 'Avatar restored successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move avatar file']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown item type']);
}

$conn->close();
