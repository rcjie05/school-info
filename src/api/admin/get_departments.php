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

// Get all departments
$stmt = $conn->prepare("
    SELECT 
        id,
        department_name,
        department_code,
        head_of_department,
        office_location,
        contact_email,
        contact_phone,
        DATE_FORMAT(created_at, '%M %d, %Y') as created_date
    FROM departments
    ORDER BY department_name
");

$stmt->execute();
$result = $stmt->get_result();

$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

echo json_encode([
    'success' => true,
    'departments' => $departments,
    'total' => count($departments)
]);

$conn->close();
?>
