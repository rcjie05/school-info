<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../php/config.php';
ob_clean();
header('Content-Type: application/json');
requireRole('student');

$conn = getDBConnection();
$target = isset($_GET['target']) ? $_GET['target'] : null;

$sql = "
    SELECT 
        a.id,
        a.title,
        a.content,
        a.target_audience,
        a.priority,
        u.name as posted_by_name,
        DATE_FORMAT(a.created_at, '%M %d, %Y %h:%i %p') as date
    FROM announcements a
    JOIN users u ON a.posted_by = u.id
    WHERE (a.target_audience = 'all' OR a.target_audience = 'students')
    AND a.deleted_at IS NULL
";

if ($target) {
    // Specific filter: rebuild query to show only exact audience (avoids WHERE conflict)
    $sql = "
        SELECT 
            a.id, a.title, a.content, a.target_audience, a.priority,
            u.name as posted_by_name,
            DATE_FORMAT(a.created_at, '%M %d, %Y %h:%i %p') as date
        FROM announcements a
        JOIN users u ON a.posted_by = u.id
        WHERE a.target_audience = ?
        AND a.deleted_at IS NULL
    ";
    $params = [$target];
    $types = "s";
} else {
    $params = [];
    $types = "";
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$announcements = [];
while ($row = $result->fetch_assoc()) {
    // Fetch attachments for this announcement
    $stmtAtt = $conn->prepare("SELECT file_path, original_name, file_type FROM announcement_attachments WHERE announcement_id = ? ORDER BY created_at ASC");
    $stmtAtt->bind_param("i", $row['id']);
    $stmtAtt->execute();
    $attResult = $stmtAtt->get_result();
    $attachments = [];
    while ($att = $attResult->fetch_assoc()) {
        $attachments[] = [
            'path'          => $att['file_path'],
            'original_name' => $att['original_name'],
            'type'          => $att['file_type']
        ];
    }
    $row['attachments'] = $attachments;
    $announcements[] = $row;
}

echo json_encode([
    'success' => true,
    'announcements' => $announcements
]);

$conn->close();
?>
