<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../php/config.php';
ob_clean();
header('Content-Type: application/json');
requireRole('registrar');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Support both multipart/form-data (with files) and JSON
$isMultipart = isset($_POST['title']);

if ($isMultipart) {
    $announcement_id = isset($_POST['announcement_id']) && $_POST['announcement_id'] !== '' ? intval($_POST['announcement_id']) : null;
    $title           = isset($_POST['title'])           ? sanitizeInput($_POST['title'])   : null;
    $content         = isset($_POST['content'])         ? sanitizeInput($_POST['content']) : null;
    $target_audience = isset($_POST['target_audience']) ? $_POST['target_audience']        : 'all';
    $priority        = isset($_POST['priority'])        ? $_POST['priority']               : 'medium';
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    $announcement_id = isset($input['announcement_id']) ? intval($input['announcement_id']) : null;
    $title           = isset($input['title'])           ? sanitizeInput($input['title'])   : null;
    $content         = isset($input['content'])         ? sanitizeInput($input['content']) : null;
    $target_audience = isset($input['target_audience']) ? $input['target_audience']        : 'all';
    $priority        = isset($input['priority'])        ? $input['priority']               : 'medium';
}

if (!$title || !$content) {
    echo json_encode(['success' => false, 'message' => 'Title and content are required']);
    exit();
}

// Ensure the announcement_attachments table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS announcement_attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        announcement_id INT NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_type ENUM('image','video','file') NOT NULL DEFAULT 'file',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE
    )
");

// --- Handle uploaded files ---
$uploadedFiles = [];
if ($isMultipart && !empty($_FILES['attachments']['name'][0])) {
    $uploadDir = __DIR__ . '/../../uploads/announcements/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $totalFiles = count($_FILES['attachments']['name']);
    for ($i = 0; $i < $totalFiles; $i++) {
        if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;

        $originalName = basename($_FILES['attachments']['name'][$i]);
        $mime         = mime_content_type($_FILES['attachments']['tmp_name'][$i]);
        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (strpos($mime, 'image/') === 0)      $fileType = 'image';
        elseif (strpos($mime, 'video/') === 0)  $fileType = 'video';
        else                                     $fileType = 'file';

        $allowed = ['jpg','jpeg','png','gif','webp','mp4','mov','avi','webm','mkv','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip','rar'];
        if (!in_array($ext, $allowed)) continue;

        $safeName = uniqid('ann_', true) . '.' . $ext;
        $destPath = $uploadDir . $safeName;
        $webPath  = 'uploads/announcements/' . $safeName;

        if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $destPath)) {
            $uploadedFiles[] = ['path' => $webPath, 'original' => $originalName, 'type' => $fileType];
        }
    }
}

if ($announcement_id) {
    $stmt = $conn->prepare("UPDATE announcements SET title=?, content=?, target_audience=?, priority=? WHERE id=? AND posted_by=?");
    $stmt->bind_param("ssssii", $title, $content, $target_audience, $priority, $announcement_id, $user_id);

    if ($stmt->execute()) {
        foreach ($uploadedFiles as $file) {
            $stmtA = $conn->prepare("INSERT INTO announcement_attachments (announcement_id, file_path, original_name, file_type) VALUES (?,?,?,?)");
            $stmtA->bind_param("isss", $announcement_id, $file['path'], $file['original'], $file['type']);
            $stmtA->execute();
        }
        echo json_encode(['success' => true, 'message' => 'Announcement updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update announcement']);
    }
} else {
    $stmt = $conn->prepare("INSERT INTO announcements (title, content, target_audience, priority, posted_by) VALUES (?,?,?,?,?)");
    $stmt->bind_param("ssssi", $title, $content, $target_audience, $priority, $user_id);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;

        foreach ($uploadedFiles as $file) {
            $stmtA = $conn->prepare("INSERT INTO announcement_attachments (announcement_id, file_path, original_name, file_type) VALUES (?,?,?,?)");
            $stmtA->bind_param("isss", $new_id, $file['path'], $file['original'], $file['type']);
            $stmtA->execute();
        }

        echo json_encode(['success' => true, 'message' => 'Announcement posted successfully', 'announcement_id' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to post announcement']);
    }
}

$conn->close();
?>
