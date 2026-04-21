<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
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
requireRole('student');
$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];

// Support multipart/form-data (with files) or JSON
$isMultipart = isset($_POST['subject']);

if ($isMultipart) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
} else {
    $input   = json_decode(file_get_contents('php://input'), true);
    $subject = trim($input['subject'] ?? '');
    $message = trim($input['message'] ?? '');
}

if (!$subject || !$message) {
    echo json_encode(['success' => false, 'message' => 'Subject and message are required.']);
    exit;
}

// Ensure feedback_attachments table exists
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

// Insert feedback
$stmt = $conn->prepare("INSERT INTO feedback (user_id, subject, message, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
$stmt->bind_param("iss", $user_id, $subject, $message);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to submit feedback.']);
    exit;
}

$feedback_id = $conn->insert_id;

// Handle uploaded files
if ($isMultipart && !empty($_FILES['attachments']['name'][0])) {
    $uploadDir = dirname(__DIR__, 3) . '/uploads/feedback/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $allowed = ['jpg','jpeg','png','gif','webp','mp4','mov','avi','webm','mkv','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip','rar'];
    $total   = count($_FILES['attachments']['name']);

    for ($i = 0; $i < $total; $i++) {
        if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;

        $originalName = basename($_FILES['attachments']['name'][$i]);
        $mime         = mime_content_type($_FILES['attachments']['tmp_name'][$i]);
        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) continue;

        if (strpos($mime, 'image/') === 0)     $fileType = 'image';
        elseif (strpos($mime, 'video/') === 0) $fileType = 'video';
        else                                    $fileType = 'file';

        $safeName = uniqid('fb_', true) . '.' . $ext;
        $destPath = $uploadDir . $safeName;
        $webPath  = 'uploads/feedback/' . $safeName;

        if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $destPath)) {
            $stmtA = $conn->prepare("INSERT INTO feedback_attachments (feedback_id, file_path, original_name, file_type) VALUES (?,?,?,?)");
            $stmtA->bind_param("isss", $feedback_id, $webPath, $originalName, $fileType);
            $stmtA->execute();
        }
    }
}

echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully.']);
$conn->close();
?>
