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
    $feedback_id = $input['feedback_id'] ?? null;
    
    if (!$feedback_id) {
        throw new Exception('Feedback ID is required');
    }
    
    $stmt = $pdo->prepare("UPDATE feedback SET status = 'resolved' WHERE id = ?");
    $stmt->execute([$feedback_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Feedback marked as resolved'
        ]);
    } else {
        throw new Exception('Feedback not found');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error resolving feedback: ' . $e->getMessage()
    ]);
}
?>
