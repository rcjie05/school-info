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

$target = isset($_GET['target']) ? $_GET['target'] : null;
$limit  = isset($_GET['limit'])  ? intval($_GET['limit']) : 50;

// Ensure columns/tables exist
// Safe column migration
$_col_check = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'announcements' AND COLUMN_NAME = 'deleted_at'");
if ($_col_check && $_col_check->fetch_assoc()['cnt'] == 0) {
    $conn->query("ALTER TABLE announcements ADD COLUMN deleted_at DATETIME DEFAULT NULL");
}
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

$sql = "
    SELECT 
        a.id,
        a.title,
        a.content,
        a.target_audience,
        a.priority,
        u.name as posted_by_name,
        DATE_FORMAT(a.created_at, '%M %d, %Y %h:%i %p') as date,
        a.created_at as timestamp
    FROM announcements a
    JOIN users u ON a.posted_by = u.id
    WHERE a.deleted_at IS NULL
";

$params = [];
$types  = "";

if ($target) {
    $sql .= " AND a.target_audience = ?";
    $params[] = $target;
    $types   .= "s";
}

$sql .= " ORDER BY a.created_at DESC LIMIT ?";
$params[] = $limit;
$types   .= "i";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$announcements = [];
while ($row = $result->fetch_assoc()) {
    // Fetch attachments for this announcement
    $stmtAtt = $conn->prepare("SELECT id, file_path, original_name, file_type FROM announcement_attachments WHERE announcement_id = ? ORDER BY created_at ASC");
    $stmtAtt->bind_param("i", $row['id']);
    $stmtAtt->execute();
    $attResult = $stmtAtt->get_result();
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
    $announcements[] = $row;
}

echo json_encode([
    'success'       => true,
    'announcements' => $announcements,
    'total'         => count($announcements)
]);

$conn->close();
?>
