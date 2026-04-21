<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../php/config.php';
ob_clean();
header('Content-Type: application/json');
requireRole('registrar');

try {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        throw new Exception('Feedback ID is required');
    }
    
    $stmt = $pdo->prepare("SELECT 
                f.id,
                f.subject,
                f.message,
                f.type,
                f.rating,
                f.status,
                f.response,
                f.created_at as date,
                u.full_name as submitted_by,
                u.role as user_role
            FROM feedback f
            JOIN users u ON f.user_id = u.id
            WHERE f.id = ?");
    $stmt->execute([$id]);
    $feedback = $stmt->fetch();
    
    if (!$feedback) {
        throw new Exception('Feedback not found');
    }
    
    $feedback['date'] = date('M d, Y h:i A', strtotime($feedback['date']));
    
    echo json_encode([
        'success' => true,
        'feedback' => $feedback
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching feedback: ' . $e->getMessage()
    ]);
}
?>
