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

error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');

requireRole('admin');

$conn = getDBConnection();

if (!$conn) {
    error_log("get_users.php: Database connection failed");
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Ensure archived_at column exists (safe migration)
// Safe column migration
$_col_check = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'archived_at'");
if ($_col_check && $_col_check->fetch_assoc()['cnt'] == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN archived_at DATETIME DEFAULT NULL");
}

$role   = isset($_GET['role'])   ? $_GET['role']   : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

$sql = "SELECT id, name, email, role, status, student_id, course, year_level, department,
        office_location, office_hours, deactivated_until, deactivation_reason,
        DATE_FORMAT(created_at, '%M %d, %Y') as created_date
        FROM users WHERE archived_at IS NULL";

$params = [];
$types  = "";

if ($role) {
    $sql .= " AND role = ?";
    $params[] = $role;
    $types   .= "s";
}
if ($status) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types   .= "s";
}
if ($search) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
    $term     = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types   .= "sss";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("get_users.php: Failed to prepare statement: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Query preparation failed: ' . $conn->error]);
    $conn->close();
    exit();
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    error_log("get_users.php: Execute failed: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Query execution failed: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit();
}

$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'users'   => $users,
    'total'   => count($users)
]);
