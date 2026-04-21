<?php
/**
 * Session ping endpoint — keeps session alive when user requests it
 */
require_once '../../php/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit();
}

// Update last activity
$_SESSION['last_activity'] = time();

echo json_encode(['success' => true, 'message' => 'Session extended']);
?>
