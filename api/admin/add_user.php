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

// Check authentication
requireRoleApi('admin');

$conn = getDBConnection();

// Check if connection is valid
if (!$conn) {
    error_log("Database connection failed in add_user.php");
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// Log incoming request for debugging
$log_input = $input;
if (isset($log_input['password'])) $log_input['password'] = '[REDACTED]';
error_log("Add user request from admin ID: $user_id - Data: " . json_encode($log_input));

// Validate required fields
$required = ['name', 'email', 'password', 'role'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        $error_msg = "Field '$field' is required";
        error_log("Validation failed: $error_msg");
        echo json_encode(['success' => false, 'message' => $error_msg]);
        exit();
    }
}

$name = sanitizeInput($input['name']);
$email = sanitizeInput($input['email']);
$password = $input['password'];
$role = $input['role'];
$status = isset($input['status']) ? $input['status'] : 'active';

// Optional fields based on role
$student_id = isset($input['student_id']) && !empty($input['student_id']) ? sanitizeInput($input['student_id']) : null;
$course = isset($input['course']) && !empty($input['course']) ? sanitizeInput($input['course']) : null;
$year_level = isset($input['year_level']) && !empty($input['year_level']) ? sanitizeInput($input['year_level']) : null;
$department = isset($input['department']) && !empty($input['department']) ? sanitizeInput($input['department']) : null;
$office_location = isset($input['office_location']) && !empty($input['office_location']) ? sanitizeInput($input['office_location']) : null;
$office_hours = isset($input['office_hours']) && !empty($input['office_hours']) ? sanitizeInput($input['office_hours']) : null;

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_log("Invalid email format: $email");
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt) {
    error_log("Failed to prepare email check statement: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    error_log("Email already exists: $email");
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit();
}
$stmt->close();

// Check if student_id already exists (if provided)
if ($student_id) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE student_id = ?");
    if (!$stmt) {
        error_log("Failed to prepare student_id check statement: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        error_log("Student ID already exists: $student_id");
        echo json_encode(['success' => false, 'message' => 'Student ID already exists']);
        exit();
    }
    $stmt->close();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);
if (!$hashed_password) {
    error_log("Failed to hash password");
    echo json_encode(['success' => false, 'message' => 'Password hashing failed']);
    exit();
}

// Insert user
$stmt = $conn->prepare("
    INSERT INTO users (name, email, password, role, status, student_id, course, year_level, 
                       department, office_location, office_hours)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    error_log("Failed to prepare insert statement: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("sssssssssss", 
    $name, $email, $hashed_password, $role, $status, 
    $student_id, $course, $year_level, $department, $office_location, $office_hours
);

if ($stmt->execute()) {
    $new_user_id = $conn->insert_id;
    error_log("Successfully created user ID: $new_user_id, Name: $name, Role: $role");
    
    // Log action
    logAction($conn, $user_id, "Added new user: $name ($role)", 'users', $new_user_id);
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'User added successfully',
        'user_id' => $new_user_id
    ]);
} else {
    $error = $stmt->error;
    error_log("Failed to insert user: $error");
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add user: ' . $error
    ]);
}
?>
