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
requireRole('student');

$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT adr.id, adr.subject_id, adr.request_type, adr.reason, adr.status, adr.registrar_note,
           adr.created_at, adr.reviewed_at,
           s.subject_code, s.subject_name, s.units,
           u.name AS reviewed_by_name
    FROM add_drop_requests adr
    JOIN subjects s ON adr.subject_id = s.id
    LEFT JOIN users u ON adr.reviewed_by = u.id
    WHERE adr.student_id = ?
    ORDER BY adr.created_at DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $row['created_at']  = date('M d, Y h:i A', strtotime($row['created_at']));
    $row['reviewed_at'] = $row['reviewed_at'] ? date('M d, Y h:i A', strtotime($row['reviewed_at'])) : null;
    $requests[] = $row;
}

echo json_encode(['success' => true, 'requests' => $requests]);
$conn->close();
?>
