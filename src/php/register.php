<?php
require_once 'config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$name       = sanitizeInput($_POST['name']       ?? '');
$email      = sanitizeInput($_POST['email']      ?? '');
$student_id = sanitizeInput($_POST['student_id'] ?? '');
$course     = sanitizeInput($_POST['course']     ?? '');
$year_level = sanitizeInput($_POST['year_level'] ?? '');
$password   = $_POST['password'] ?? '';
$section_id = !empty($_POST['section_id']) ? (int)$_POST['section_id'] : null;

// Validation
if (empty($name) || empty($email) || empty($student_id) || empty($course) || empty($year_level) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit();
}

// Check if student ID already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Student ID already registered']);
    exit();
}

// Validate section if provided
$section_name = null;
if ($section_id) {
    $sec_stmt = $conn->prepare("SELECT id, section_name, section_code FROM sections WHERE id = ? AND status = 'active'");
    $sec_stmt->bind_param('i', $section_id);
    $sec_stmt->execute();
    $sec_result = $sec_stmt->get_result()->fetch_assoc();
    if (!$sec_result) {
        echo json_encode(['success' => false, 'message' => 'Selected section is no longer available. Please refresh and try again.']);
        exit();
    }
    $section_name = $sec_result['section_name'] . ' (' . $sec_result['section_code'] . ')';
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if section_id column exists in users table yet
$col_check       = $conn->query("SHOW COLUMNS FROM `users` LIKE 'section_id'");
$has_section_col = ($col_check && $col_check->num_rows > 0);

if ($has_section_col) {
    // 7 params: name(s) email(s) student_id(s) course(s) year_level(s) section_id(i) password(s)
    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, student_id, course, year_level, section_id, password, role, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'student', 'pending')"
    );
    $stmt->bind_param("sssss" . "i" . "s",
        $name, $email, $student_id, $course, $year_level, $section_id, $hashed_password
    );
} else {
    // Fallback when migration has not been run yet
    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, student_id, course, year_level, password, role, status)
         VALUES (?, ?, ?, ?, ?, ?, 'student', 'pending')"
    );
    $stmt->bind_param("ssssss",
        $name, $email, $student_id, $course, $year_level, $hashed_password
    );
}

if ($stmt->execute()) {
    $user_id = $conn->insert_id;

    // Back-fill section_id if we used fallback path
    if ($has_section_col && $section_id) {
        $upd = $conn->prepare("UPDATE users SET section_id = ? WHERE id = ?");
        $upd->bind_param("ii", $section_id, $user_id);
        $upd->execute();
    }

    // Notify registrars
    $section_info         = $section_name ? " | Section: $section_name" : '';
    $notification_message = "New student registration: $name ($student_id)$section_info";
    $reg_stmt = $conn->prepare("SELECT id FROM users WHERE role = 'registrar'");
    $reg_stmt->execute();
    $registrars = $reg_stmt->get_result();
    while ($registrar = $registrars->fetch_assoc()) {
        createNotification($conn, $registrar['id'], 'New Registration', $notification_message);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Please wait for approval from the registrar.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $conn->error]);
}

$conn->close();
?>
