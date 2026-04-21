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
$admin_id = $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['department_id'])) {
    echo json_encode(['success' => false, 'message' => 'Department ID is required']);
    exit();
}

$dept_id = intval($input['department_id']);

$stmt = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
$stmt->bind_param("i", $dept_id);
$stmt->execute();
$dept = $stmt->get_result()->fetch_assoc();

if (!$dept) {
    echo json_encode(['success' => false, 'message' => 'Department not found']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
$stmt->bind_param("i", $dept_id);

if ($stmt->execute()) {
    logAction($conn, $admin_id, "Deleted department: {$dept['department_name']}", 'departments', $dept_id);
    echo json_encode(['success' => true, 'message' => 'Department deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete department: ' . $stmt->error]);
}

$conn->close();
?>
