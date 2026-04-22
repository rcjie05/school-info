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

$conn    = getDBConnection();
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
    $roleTypes    = 's';
}

header('Content-Type: text/csv');
$out = fopen('php://output', 'w');

if ($summary && $month) {
    [$yr, $mo] = explode('-', $month);
    $firstDay  = "$yr-$mo-01";
    $lastDay   = date('Y-m-t', strtotime($firstDay));
    header("Content-Disposition: attachment; filename=\"attendance_summary_{$month}.csv\"");

    fputcsv($out, ['Name', 'Role', 'Position', 'Present', 'Absent', 'Late', 'Half Day', 'On Leave', 'Attendance Rate %']);

    $sql = "
        SELECT u.name, u.role, e.position,
               SUM(CASE WHEN a.status='present'  THEN 1 ELSE 0 END) AS present,
               SUM(CASE WHEN a.status='absent'   THEN 1 ELSE 0 END) AS absent,
               SUM(CASE WHEN a.status='late'     THEN 1 ELSE 0 END) AS late,
               SUM(CASE WHEN a.status='half_day' THEN 1 ELSE 0 END) AS half_day,
               SUM(CASE WHEN a.status='on_leave' THEN 1 ELSE 0 END) AS on_leave
        FROM users u
        LEFT JOIN hr_employees e  ON u.id = e.user_id
        LEFT JOIN hr_attendance a ON u.id = a.user_id AND a.date BETWEEN ? AND ?
        WHERE u.role IN ('teacher','registrar','admin') $roleFilter
        GROUP BY u.id ORDER BY u.name ASC
    ";
    $types  = 'ss' . $roleTypes;
    $params = [$firstDay, $lastDay, ...$roleParams];
    $stmt   = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $total = ($r['present']+$r['absent']+$r['late']+$r['half_day']+$r['on_leave']);
        $rate  = $total ? round(($r['present']+$r['late']+$r['half_day']*.5)/$total*100, 1) : 0;
        fputcsv($out, [$r['name'], $r['role'], $r['position']??'', $r['present'], $r['absent'], $r['late'], $r['half_day'], $r['on_leave'], $rate.'%']);
    }
} elseif ($date) {
    header("Content-Disposition: attachment; filename=\"attendance_{$date}.csv\"");
    fputcsv($out, ['Name', 'Role', 'Position', 'Status', 'Time In', 'Time Out', 'Remarks']);

    $sql = "
        SELECT u.name, u.role, e.position, a.status, a.time_in, a.time_out, a.remarks
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
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        fputcsv($out, [$r['name'], $r['role'], $r['position']??'', $r['status']??'—', $r['time_in']??'—', $r['time_out']??'—', $r['remarks']??'']);
    }
}

fclose($out);
$conn->close();
?>
