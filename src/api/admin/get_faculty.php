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

$department = isset($_GET['department']) ? $_GET['department'] : null;

$sql = "
    SELECT 
        id,
        name,
        email,
        department,
        office_location,
        office_hours,
        status
    FROM users
    WHERE role IN ('teacher', 'registrar')
";

$params = [];
$types = "";

if ($department) {
    $sql .= " AND department = ?";
    $params[] = $department;
    $types .= "s";
}

$sql .= " ORDER BY department, name";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$faculty = [];
while ($row = $result->fetch_assoc()) {
    $faculty[] = $row;
}

// Get list of departments for filter
$dept_stmt = $conn->prepare("SELECT DISTINCT department FROM users WHERE role IN ('teacher', 'registrar') AND department IS NOT NULL ORDER BY department");
$dept_stmt->execute();
$dept_result = $dept_stmt->get_result();

$departments = [];
while ($dept = $dept_result->fetch_assoc()) {
    if ($dept['department']) {
        $departments[] = $dept['department'];
    }
}

echo json_encode([
    'success' => true,
    'faculty' => $faculty,
    'departments' => $departments,
    'total' => count($faculty)
]);

$conn->close();
?>
