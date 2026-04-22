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
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$conn    = getDBConnection();

// Ensure avatar_url column exists
// avatar_url column already exists in users table schema

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $errCode = isset($_FILES['avatar']) ? $_FILES['avatar']['error'] : 'none';
    echo json_encode(['success' => false, 'message' => "Upload error (code: $errCode)"]);
    exit;
}

$file    = $_FILES['avatar'];
$maxSize = 5 * 1024 * 1024;
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 5MB.']);
    exit;
}

$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
    exit;
}

// Secondary check: verify the file is actually a valid image (catches disguised files)
if (!@getimagesize($file['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => 'File does not appear to be a valid image.']);
    exit;
}

// Project root is 2 directories up from php/api/
$projectRoot = dirname(__DIR__, 2);
$uploadDir   = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Build unique filename
$mimeExts = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
$ext      = $mimeExts[$mimeType] ?? 'jpg';
$filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
$savePath = $uploadDir . $filename;

// Move old avatar to recycle bin
$stmt = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$old = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!empty($old['avatar_url'])) {
    $oldRelative = ltrim($old['avatar_url'], '/');
    if (strpos($oldRelative, 'uploads/') !== false) {
        $oldRelative = 'uploads/' . substr($oldRelative, strpos($oldRelative, 'uploads/') + 8);
    }
    $oldPhysical = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldRelative);
    if (file_exists($oldPhysical)) {
        $deletedDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'deleted_avatars' . DIRECTORY_SEPARATOR;
        if (!is_dir($deletedDir)) mkdir($deletedDir, 0755, true);
        @rename($oldPhysical, $deletedDir . 'user' . $user_id . '_' . time() . '_' . basename($oldPhysical));
    }
}

if (!move_uploaded_file($file['tmp_name'], $savePath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file. Check uploads/avatars/ permissions.']);
    exit;
}

// Store as a clean relative path — works regardless of folder name
$avatarRelative = 'uploads/avatars/' . $filename;

$stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
$stmt->bind_param("si", $avatarRelative, $user_id);
if (!$stmt->execute()) {
    @unlink($savePath);
    echo json_encode(['success' => false, 'message' => 'DB update failed: ' . $conn->error]);
    exit;
}
$stmt->close();
$conn->close();

// Return browser-accessible URL via the helper in config.php
$avatarUrl = getAvatarUrl($avatarRelative);

echo json_encode([
    'success'    => true,
    'message'    => 'Profile picture updated!',
    'avatar_url' => $avatarUrl,
]);
?>
