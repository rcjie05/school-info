<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
/**
 * Session ping endpoint — keeps session alive when user requests it
 */
require_once '../../php/config.php';
ob_clean();
header('Content-Type: application/json');
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit();
}

// Update last activity
$_SESSION['last_activity'] = time();

echo json_encode(['success' => true, 'message' => 'Session extended']);
?>
