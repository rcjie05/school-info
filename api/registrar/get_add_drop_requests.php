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
requireRole('registrar');

$conn   = getDBConnection();
$status = $_GET['status'] ?? '';

$where  = $status ? "WHERE adr.status = ?" : "";
$params = $status ? [$status] : [];
$types  = $status ? "s" : "";

$sql = "
    SELECT adr.id, adr.request_type, adr.reason, adr.status, adr.registrar_note,
           adr.created_at, adr.reviewed_at,
           s.subject_code, s.subject_name, s.units,
           u.name AS student_name, u.student_id AS student_no, u.course, u.year_level,
           sec.section_name, sec.section_code,
           rv.name AS reviewed_by_name
    FROM add_drop_requests adr
    JOIN subjects s ON adr.subject_id = s.id
    JOIN users u ON adr.student_id = u.id
    LEFT JOIN sections sec ON u.section_id = sec.id
    LEFT JOIN users rv ON adr.reviewed_by = rv.id
    $where
    ORDER BY FIELD(adr.status,'pending','approved','rejected'), adr.created_at DESC
";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $row['created_at']  = date('M d, Y h:i A', strtotime($row['created_at']));
    $row['reviewed_at'] = $row['reviewed_at'] ? date('M d, Y h:i A', strtotime($row['reviewed_at'])) : null;
    $requests[] = $row;
}

// Count pending
$cnt = $conn->query("SELECT COUNT(*) as c FROM add_drop_requests WHERE status='pending'");
$pending_count = $cnt->fetch_assoc()['c'];

echo json_encode(['success' => true, 'requests' => $requests, 'pending_count' => $pending_count]);
$conn->close();
?>
