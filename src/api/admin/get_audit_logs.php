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

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

$sql = "
    SELECT 
        al.id,
        al.action,
        al.table_name,
        al.record_id,
        u.name as user_name,
        u.email as user_email,
        u.role as user_role,
        DATE_FORMAT(al.created_at, '%M %d, %Y %h:%i %p') as date,
        al.created_at as timestamp
    FROM audit_logs al
    JOIN users u ON al.user_id = u.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($user_filter) {
    $sql .= " AND al.user_id = ?";
    $params[] = $user_filter;
    $types .= "i";
}

$sql .= " ORDER BY al.created_at DESC LIMIT ?";
$params[] = $limit;
$types .= "i";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

echo json_encode([
    'success' => true,
    'logs' => $logs,
    'total' => count($logs)
]);

$conn->close();
?>
