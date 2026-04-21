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
    SELECT j.*, d.department_name,
           u.name AS posted_by_name,
           (SELECT COUNT(*) FROM hr_applicants a WHERE a.job_id = j.id) AS applicant_count
    FROM hr_job_postings j
    LEFT JOIN departments d ON j.department_id = d.id
    LEFT JOIN users u ON j.posted_by = u.id
    ORDER BY j.created_at DESC
");

$jobs = [];
while ($row = $result->fetch_assoc()) $jobs[] = $row;
echo json_encode(['success' => true, 'jobs' => $jobs]);
$conn->close();
?>
