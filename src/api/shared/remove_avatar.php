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

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$conn    = getDBConnection();

$stmt = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!empty($row['avatar_url'])) {
    // Project root is 2 dirs up from php/api/
    $projectRoot = dirname(__DIR__, 2);

    $oldRelative = ltrim($row['avatar_url'], '/');
    if (strpos($oldRelative, 'uploads/') !== false) {
        $oldRelative = 'uploads/' . substr($oldRelative, strpos($oldRelative, 'uploads/') + 8);
    }
    $srcPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldRelative);

    $deletedDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'deleted_avatars' . DIRECTORY_SEPARATOR;
    if (!is_dir($deletedDir)) mkdir($deletedDir, 0755, true);

    if (file_exists($srcPath)) {
        @rename($srcPath, $deletedDir . 'user' . $user_id . '_' . time() . '_' . basename($srcPath));
    }
}

$stmt = $conn->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'message' => 'Profile picture removed']);
