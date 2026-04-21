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
requireRole('admin');

$data = json_decode(file_get_contents('php://input'), true);
$section_id = (int)($data['section_id'] ?? 0);

if (!$section_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid section ID']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get section info for logging
$stmt = $conn->prepare("SELECT section_code FROM sections WHERE id = ?");
$stmt->bind_param('i', $section_id);
$stmt->execute();
$result = $stmt->get_result();
$section = $result->fetch_assoc();

if (!$section) {
    echo json_encode(['success' => false, 'message' => 'Section not found']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM sections WHERE id = ?");
$stmt->bind_param('i', $section_id);

if ($stmt->execute()) {
    logAction($conn, $_SESSION['user_id'], "Deleted section: {$section['section_code']}", 'sections', $section_id);
    echo json_encode(['success' => true, 'message' => 'Section deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete section']);
}

$stmt->close();
$conn->close();
?>
