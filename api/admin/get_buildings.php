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
requireRoleApi('admin');

$conn = getDBConnection();

// Get all buildings with room counts
$stmt = $conn->prepare("
    SELECT 
        b.id,
        b.building_name,
        b.building_code,
        b.description,
        b.location,
        COUNT(r.id) as room_count,
        DATE_FORMAT(b.created_at, '%M %d, %Y') as created_date
    FROM buildings b
    LEFT JOIN rooms r ON r.building_id = b.id
    GROUP BY b.id
    ORDER BY b.building_name
");

$stmt->execute();
$result = $stmt->get_result();

$buildings = [];
while ($row = $result->fetch_assoc()) {
    $buildings[] = $row;
}

echo json_encode([
    'success' => true,
    'buildings' => $buildings,
    'total' => count($buildings)
]);

$conn->close();
?>
