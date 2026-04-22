<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('student');
header('Content-Type: application/json');

$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];

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
    SELECT id, subject, message, status, response, user_reply,
           DATE_FORMAT(created_at, '%M %d, %Y %h:%i %p') as date
    FROM feedback
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$feedback = [];
while ($row = $result->fetch_assoc()) {
    // Fetch attachments
    $stmtA = $conn->prepare("SELECT id, file_path, original_name, file_type FROM feedback_attachments WHERE feedback_id = ? ORDER BY created_at ASC");
    $stmtA->bind_param("i", $row['id']);
    $stmtA->execute();
    $attResult  = $stmtA->get_result();
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

echo json_encode(['success' => true, 'feedback' => $feedback]);
$conn->close();
?>
