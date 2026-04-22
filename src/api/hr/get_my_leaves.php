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

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['teacher', 'registrar', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];
$year    = intval($_GET['year'] ?? date('Y'));

// My leave requests
$stmt = $conn->prepare("
    SELECT lr.id, lr.start_date, lr.end_date, lr.total_days, lr.reason,
           lr.status, lr.review_note, lr.reviewed_at, lr.created_at,
           lt.name as leave_type, lt.id as leave_type_id, lt.max_days_per_year
    FROM hr_leave_requests lr
    JOIN hr_leave_types lt ON lr.leave_type_id = lt.id
    WHERE lr.user_id = ? AND YEAR(lr.created_at) = ?
    ORDER BY lr.created_at DESC
");
$stmt->bind_param("ii", $user_id, $year);
$stmt->execute();
$result = $stmt->get_result();
$leaves = [];
while ($row = $result->fetch_assoc()) $leaves[] = $row;

// Leave balances for the year
$balStmt = $conn->prepare("
    SELECT lt.id, lt.name, lt.max_days_per_year,
           COALESCE(b.used_days, 0) as used_days
    FROM hr_leave_types lt
    LEFT JOIN hr_leave_balances b ON lt.id = b.leave_type_id AND b.user_id = ? AND b.year = ?
    WHERE lt.is_active = 1
");
$balStmt->bind_param("ii", $user_id, $year);
$balStmt->execute();
$balResult = $balStmt->get_result();
$balances  = [];
while ($row = $balResult->fetch_assoc()) $balances[] = $row;

// Leave types for the dropdown
$ltResult  = $conn->query("SELECT id, name, max_days_per_year, description FROM hr_leave_types WHERE is_active=1");
$leaveTypes = [];
while ($row = $ltResult->fetch_assoc()) $leaveTypes[] = $row;

echo json_encode(['success' => true, 'leaves' => $leaves, 'balances' => $balances, 'leave_types' => $leaveTypes]);
$conn->close();
?>
