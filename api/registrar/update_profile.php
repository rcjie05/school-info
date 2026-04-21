<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../php/config.php';
ob_clean();
header('Content-Type: application/json');
requireRole('registrar');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $full_name = $input['full_name'] ?? '';
    $email = $input['email'] ?? '';
    
    if (empty($full_name) || empty($email)) {
        throw new Exception('Full name and email are required');
    }
    
    // Check if email is already in use by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        throw new Exception('Email is already in use by another user');
    }
    
    // Update user profile
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
    $stmt->execute([$full_name, $email, $_SESSION['user_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating profile: ' . $e->getMessage()
    ]);
}
?>
