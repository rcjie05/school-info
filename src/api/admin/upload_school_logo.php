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

if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
    $code = isset($_FILES['logo']) ? $_FILES['logo']['error'] : 'none';
    echo json_encode(['success' => false, 'message' => "Upload error (code: $code)"]);
    exit;
}

$file    = $_FILES['logo'];
$maxSize = 5 * 1024 * 1024; // 5MB
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 5MB.']);
    exit;
}

$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Use JPG, PNG, GIF, or WebP.']);
    exit;
}

if (!@getimagesize($file['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => 'File does not appear to be a valid image.']);
    exit;
}

// Save to images/ folder as school_logo.ext
$projectRoot = dirname(__DIR__, 3); // php/api/admin -> root
$mimeExts    = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
$ext         = $mimeExts[$mimeType] ?? 'jpg';
$filename    = 'school_logo.' . $ext;
$savePath    = $projectRoot . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($file['tmp_name'], $savePath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file. Check images/ folder permissions.']);
    exit;
}

$logoRelative = 'images/' . $filename;

// Upsert into system_settings
$stmt = $conn->prepare("
    INSERT INTO system_settings (setting_key, setting_value, description)
    VALUES ('school_logo', ?, 'Path to the school logo image')
    ON DUPLICATE KEY UPDATE setting_value = ?
");
$stmt->bind_param("ss", $logoRelative, $logoRelative);
$stmt->execute();
$stmt->close();

logAction($conn, $admin_id, 'Updated school logo', 'system_settings', null);
$conn->close();

// Return URL relative to project root
$baseUrl = BASE_URL;
echo json_encode([
    'success'        => true,
    'message'        => 'School logo updated!',
    'logo_url'       => $baseUrl . '/' . $logoRelative . '?v=' . time(),
    'logo_relative'  => $logoRelative,
]);
