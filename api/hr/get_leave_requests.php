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
requireRole('hr');
$conn   = getDBConnection();
$status = $_GET['status'] ?? '';
$year   = intval($_GET['year'] ?? date('Y'));

$sql = "
    SELECT 
        lr.id, lr.start_date, lr.end_date, lr.total_days, lr.reason,
        lr.status, lr.review_note, lr.reviewed_at, lr.created_at,
        u.name as employee_name, u.role as employee_role,
        lt.name as leave_type, lt.id as leave_type_id,
        r.name as reviewed_by_name
    FROM hr_leave_requests lr
    JOIN users u ON lr.user_id = u.id
    JOIN hr_leave_types lt ON lr.leave_type_id = lt.id
    LEFT JOIN users r ON lr.reviewed_by = r.id
    WHERE YEAR(lr.created_at) = ?
";

$params = [$year];
$types  = "i";

if ($status) {
    $sql   .= " AND lr.status = ?";
    $params[] = $status;
    $types   .= "s";
}

$sql .= " ORDER BY lr.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) $requests[] = $row;

// Leave type summary for the year
$leaveTypes = $conn->query("SELECT id, name, max_days_per_year FROM hr_leave_types WHERE is_active=1");
$leaveTypesArr = [];
while ($lt = $leaveTypes->fetch_assoc()) $leaveTypesArr[] = $lt;

echo json_encode(['success' => true, 'requests' => $requests, 'leave_types' => $leaveTypesArr]);
$conn->close();
?>
