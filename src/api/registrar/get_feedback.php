<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('registrar');
header('Content-Type: application/json');

$conn = getDBConnection();

// Ensure table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS feedback_attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        feedback_id INT NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_type ENUM('image','video','file') NOT NULL DEFAULT 'file',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (feedback_id) REFERENCES feedback(id) ON DELETE CASCADE
    )
");

$stmt = $conn->prepare("
    SELECT 
        f.id, f.subject, f.message, f.status, f.response, f.user_reply,
        DATE_FORMAT(f.created_at, '%M %d, %Y %h:%i %p') as date,
        u.name as submitted_by, u.role
    FROM feedback f
    JOIN users u ON f.user_id = u.id
    ORDER BY f.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();

$feedback = [];
while ($row = $result->fetch_assoc()) {
    $stmtA = $conn->prepare("SELECT id, file_path, original_name, file_type FROM feedback_attachments WHERE feedback_id = ? ORDER BY created_at ASC");
    $stmtA->bind_param("i", $row['id']);
    $stmtA->execute();
    $attResult   = $stmtA->get_result();
    $attachments = [];
    while ($att = $attResult->fetch_assoc()) {
        $attachments[] = [
            'id'            => $att['id'],
            'path'          => $att['file_path'],
            'original_name' => $att['original_name'],
            'type'          => $att['file_type']
        ];
    }
    $row['attachments'] = $attachments;
    $feedback[] = $row;
}

$statsStmt = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM feedback");
$stats = $statsStmt->fetch_assoc();

echo json_encode(['success' => true, 'feedback' => $feedback, 'stats' => $stats]);
$conn->close();
?>
