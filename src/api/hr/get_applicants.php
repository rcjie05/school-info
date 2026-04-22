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
$conn = getDBConnection();

$result = $conn->query("
    SELECT a.*, j.title AS job_title, j.department_id
    FROM hr_applicants a
    JOIN hr_job_postings j ON a.job_id = j.id
    ORDER BY a.created_at DESC
");

$applicants = [];
while ($row = $result->fetch_assoc()) $applicants[] = $row;
echo json_encode(['success' => true, 'applicants' => $applicants]);
$conn->close();
?>
