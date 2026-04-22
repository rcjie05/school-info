<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
/**
 * Per-user theme preference API
 * GET  ?action=get  -> returns { success, theme }
 * POST body: {"theme":"ocean"}  -> saves and returns { success, theme }
 */
ob_start(); // buffer any stray output so JSON stays clean

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

header('Cache-Control: no-store');

if (!isLoggedIn()) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Auto-migrate: add theme_preference column if it doesn't exist yet
$col = $conn->query("SHOW COLUMNS FROM `users` LIKE 'theme_preference'");
if ($col && $col->num_rows === 0) {
    $conn->query("ALTER TABLE `users` ADD COLUMN `theme_preference` VARCHAR(50) NULL DEFAULT NULL AFTER `avatar_url`");
}

$method = $_SERVER['REQUEST_METHOD'];
$user_id = (int) $_SESSION['user_id'];

// ── GET ──────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $stmt = $conn->prepare("SELECT theme_preference FROM users WHERE id = ?");
    if (!$stmt) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Query prepare failed']);
        exit();
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'theme'   => $row['theme_preference'] ?? null
    ]);
    exit();
}

// ── POST ─────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body  = file_get_contents('php://input');
    $input = json_decode($body, true);
    $theme = trim($input['theme'] ?? '');

    $allowed = ['ocean', 'cyan', 'rose', 'jade', 'amethyst'];
    if (!in_array($theme, $allowed, true)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid theme: ' . $theme]);
        exit();
    }

    $stmt = $conn->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
    if (!$stmt) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Query prepare failed']);
        exit();
    }
    $stmt->bind_param("si", $theme, $user_id);
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $conn->close();
    ob_end_clean();
    echo json_encode([
        'success'  => $ok,
        'theme'    => $theme,
        'affected' => $affected
    ]);
    exit();
}

// Unsupported method
ob_end_clean();
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
