<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
header('Content-Type: application/json');
requireRole('admin');

$conn = getDBConnection();
$admin_id = $_SESSION['user_id'];

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

// Ensure soft-delete column exists
// Safe column migration
$_col_check = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'announcements' AND COLUMN_NAME = 'deleted_at'");
if ($_col_check && $_col_check->fetch_assoc()['cnt'] == 0) {
    $conn->query("ALTER TABLE announcements ADD COLUMN deleted_at DATETIME DEFAULT NULL");
}

// --- Handle uploaded files ---
$uploadedFiles = [];
if ($isMultipart && !empty($_FILES['attachments']['name'][0])) {
    $uploadDir = __DIR__ . '/../../../uploads/announcements/';
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
    $stmt = $conn->prepare("UPDATE announcements SET title=?, content=?, target_audience=?, priority=? WHERE id=?");
    $stmt->bind_param("ssssi", $title, $content, $target_audience, $priority, $announcement_id);

    if ($stmt->execute()) {
        foreach ($uploadedFiles as $file) {
            $stmtA = $conn->prepare("INSERT INTO announcement_attachments (announcement_id, file_path, original_name, file_type) VALUES (?,?,?,?)");
            $stmtA->bind_param("isss", $announcement_id, $file['path'], $file['original'], $file['type']);
            $stmtA->execute();
        }
        logAction($conn, $admin_id, "Updated announcement: $title", 'announcements', $announcement_id);
        echo json_encode(['success' => true, 'message' => 'Announcement updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update announcement']);
    }
} else {
    $stmt = $conn->prepare("INSERT INTO announcements (title, content, target_audience, priority, posted_by) VALUES (?,?,?,?,?)");
    $stmt->bind_param("ssssi", $title, $content, $target_audience, $priority, $admin_id);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;

        foreach ($uploadedFiles as $file) {
            $stmtA = $conn->prepare("INSERT INTO announcement_attachments (announcement_id, file_path, original_name, file_type) VALUES (?,?,?,?)");
            $stmtA->bind_param("isss", $new_id, $file['path'], $file['original'], $file['type']);
            $stmtA->execute();
        }

        logAction($conn, $admin_id, "Posted announcement: $title", 'announcements', $new_id);

        $target_roles = ($target_audience === 'all') ? ['student','teacher','registrar','admin'] : [$target_audience];
        foreach ($target_roles as $role) {
            $stmt2 = $conn->prepare("SELECT id FROM users WHERE role=? AND status IN ('active','approved')");
            $stmt2->bind_param("s", $role);
            $stmt2->execute();
            $users = $stmt2->get_result();
            while ($user = $users->fetch_assoc()) {
                createNotification($conn, $user['id'], "New Announcement: $title", substr($content, 0, 100));
            }
        }

        echo json_encode(['success' => true, 'message' => 'Announcement posted successfully', 'announcement_id' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to post announcement']);
    }
}

$conn->close();
?>
