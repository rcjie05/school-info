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
requireRole('teacher');

$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];
$input   = json_decode(file_get_contents('php://input'), true);

$name            = trim($input['name'] ?? '');
$email           = trim($input['email'] ?? '');
$office_location = trim($input['office_location'] ?? '');
$office_hours    = trim($input['office_hours'] ?? '');

if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Name and email are required']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Check email uniqueness
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already taken']);
    exit;
}
$stmt->close();

$stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, office_location = ?, office_hours = ? WHERE id = ?");
$stmt->bind_param("ssssi", $name, $email, $office_location, $office_hours, $user_id);
$ok = $stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => $ok, 'message' => $ok ? 'Profile updated' : 'Update failed']);
?>
