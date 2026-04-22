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
$conn = getDBConnection();

$date    = $_GET['date']    ?? null;
$month   = $_GET['month']   ?? null;
$role    = $_GET['role']    ?? '';
$summary = isset($_GET['summary']) && $_GET['summary'] == '1';

$roleFilter = '';
$roleParams = [];
$roleTypes  = '';

if ($role) {
    $roleFilter = "AND u.role = ?";
    $roleParams[] = $role;
    $roleTypes   = 's';
}

// ── Monthly Summary ──────────────────────────────────────────────────────────
if ($summary && $month) {
    [$yr, $mo] = explode('-', $month);
    $firstDay  = "$yr-$mo-01";
    $lastDay   = date('Y-m-t', strtotime($firstDay));

    $sql = "
        SELECT u.id, u.name, u.role, u.avatar_url,
               e.position,
               SUM(CASE WHEN a.status='present'  THEN 1 ELSE 0 END) AS present,
               SUM(CASE WHEN a.status='absent'   THEN 1 ELSE 0 END) AS absent,
               SUM(CASE WHEN a.status='late'     THEN 1 ELSE 0 END) AS late,
               SUM(CASE WHEN a.status='half_day' THEN 1 ELSE 0 END) AS half_day,
               SUM(CASE WHEN a.status='on_leave' THEN 1 ELSE 0 END) AS on_leave
        FROM users u
        LEFT JOIN hr_employees e  ON u.id = e.user_id
        LEFT JOIN hr_attendance a ON u.id = a.user_id AND a.date BETWEEN ? AND ?
        WHERE u.role IN ('teacher','registrar','admin') $roleFilter
        GROUP BY u.id
        ORDER BY u.name ASC
    ";

    $types  = 'ss' . $roleTypes;
    $params = [$firstDay, $lastDay, ...$roleParams];
    $stmt   = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res  = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $r['avatar_url'] = getAvatarUrl($r['avatar_url'] ?? null);
        $rows[] = $r;
    }
    echo json_encode(['success' => true, 'summary' => $rows]);
    $conn->close();
    exit;
}

// ── Daily Log ────────────────────────────────────────────────────────────────
if (!$date) {
    echo json_encode(['success' => false, 'message' => 'Date required']);
    exit;
}

$sql = "
    SELECT u.id, u.name, u.email, u.role, u.avatar_url,
           e.position,
           a.id AS att_id, a.status AS att_status, a.time_in, a.time_out, a.remarks
    FROM users u
    LEFT JOIN hr_employees e  ON u.id = e.user_id
    LEFT JOIN hr_attendance a ON u.id = a.user_id AND a.date = ?
    WHERE u.role IN ('teacher','registrar','admin') $roleFilter
    ORDER BY u.name ASC
";

$types  = 's' . $roleTypes;
$params = [$date, ...$roleParams];
$stmt   = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res  = $stmt->get_result();
$employees = [];
while ($r = $res->fetch_assoc()) {
    $r['avatar_url'] = getAvatarUrl($r['avatar_url'] ?? null);
    $employees[] = $r;
}

echo json_encode(['success' => true, 'employees' => $employees]);
$conn->close();
?>
