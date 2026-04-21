<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('hr');

header('Content-Type: application/json');

$conn   = getDBConnection();
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
    WHERE (a.target_audience = 'all' OR a.target_audience = 'staff')
    AND a.deleted_at IS NULL
";

$params = [];
$types  = "";

if ($target) {
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
        WHERE a.target_audience = ?
        AND a.deleted_at IS NULL
    ";
    $params[] = $target;
    $types   .= "s";
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
    $stmtAtt = $conn->prepare("SELECT file_path, original_name, file_type FROM announcement_attachments WHERE announcement_id = ? ORDER BY created_at ASC");
    $stmtAtt->bind_param("i", $row['id']);
    $stmtAtt->execute();
    $attResult   = $stmtAtt->get_result();
    $attachments = [];
    while ($att = $attResult->fetch_assoc()) {
        $attachments[] = [
            'path'          => $att['file_path'],
            'original_name' => $att['original_name'],
            'type'          => $att['file_type']
        ];
    }
    $row['attachments'] = $attachments;
    $announcements[]    = $row;
}

echo json_encode(['success' => true, 'announcements' => $announcements]);
$conn->close();
?>
