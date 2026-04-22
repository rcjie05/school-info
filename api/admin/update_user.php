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
// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');

requireRoleApi('admin');

$conn = getDBConnection();

if (!$conn) {
    error_log("Database connection failed in update_user.php");
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$admin_id = $_SESSION['user_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// Log incoming request
error_log("Update user request from admin ID: $admin_id - Data: " . json_encode($input));

if (!isset($input['user_id'])) {
    error_log("User ID missing in update request");
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

$user_id = intval($input['user_id']);

// Check if user exists
$check_stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $check_stmt->close();
    $conn->close();
    error_log("User ID $user_id not found");
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}
$check_stmt->close();

// Build update query dynamically based on provided fields
$updates = [];
$params = [];
$types = "";

$allowed_fields = [
    'name' => 's',
    'email' => 's',
    'role' => 's',
    'status' => 's',
    'student_id' => 's',
    'course' => 's',
    'year_level' => 's',
    'department' => 's',
    'office_location' => 's',
    'office_hours' => 's'
];

foreach ($allowed_fields as $field => $type) {
    if (isset($input[$field])) {
        $value = $input[$field];
        // Handle empty strings for optional fields
        if (in_array($field, ['student_id', 'course', 'year_level', 'department', 'office_location', 'office_hours']) && empty($value)) {
            $value = null;
        } else {
            $value = sanitizeInput($value);
        }
        $updates[] = "$field = ?";
        $params[] = $value;
        $types .= $type;
    }
}

// Handle password separately if provided
if (isset($input['password']) && !empty($input['password'])) {
    $hashed_password = password_hash($input['password'], PASSWORD_BCRYPT);
    if (!$hashed_password) {
        error_log("Failed to hash password for user ID: $user_id");
        echo json_encode(['success' => false, 'message' => 'Password hashing failed']);
        exit();
    }
    $updates[] = "password = ?";
    $params[] = $hashed_password;
    $types .= "s";
    error_log("Updating password for user ID: $user_id");
}

if (empty($updates)) {
    error_log("No fields to update for user ID: $user_id");
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit();
}

// Add user_id to params for WHERE clause
$params[] = $user_id;
$types .= "i";

$sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
error_log("Update SQL: $sql with types: $types");

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Failed to prepare update statement: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

// Bind parameters using call_user_func_array to handle dynamic parameters
$bind_names = [$types];
for ($i = 0; $i < count($params); $i++) {
    $bind_names[] = &$params[$i];
}

call_user_func_array([$stmt, 'bind_param'], $bind_names);

if ($stmt->execute()) {
    error_log("Successfully updated user ID: $user_id");
    
    // Log action
    logAction($conn, $admin_id, "Updated user ID: $user_id", 'users', $user_id);
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully'
    ]);
} else {
    $error = $stmt->error;
    error_log("Failed to update user ID $user_id: $error");
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update user: ' . $error
    ]);
}
?>
