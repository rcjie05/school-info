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

$email    = sanitizeInput($_POST['email']    ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
    exit();
}

// ── Rate Limiting: block brute force ─────────────────────────────────────────
$rateCheck = checkLoginRateLimit($email);
if (!$rateCheck['allowed']) {
    echo json_encode(['success' => false, 'message' => $rateCheck['message']]);
    exit();
}

// ── Database lookup ───────────────────────────────────────────────────────────
$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$stmt = $conn->prepare("SELECT id, name, email, password, role, status, course FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Always take the same time to respond (prevent timing attacks)
if ($result->num_rows === 0) {
    password_verify('dummy', '$2y$10$dummyhashfortimingnormalization'); // constant time
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    $conn->close();
    exit();
}

$user = $result->fetch_assoc();

// ── Password verification ─────────────────────────────────────────────────────
if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    $conn->close();
    exit();
}

// ── Account status checks ─────────────────────────────────────────────────────
if ($user['status'] === 'inactive') {
    echo json_encode(['success' => false, 'message' => 'Your account has been deactivated. Please contact the administrator.']);
    $conn->close();
    exit();
}

if ($user['role'] === 'student' && $user['status'] === 'pending') {
    echo json_encode(['success' => false, 'message' => 'Your account is pending approval. Please wait for the registrar to approve your registration.']);
    $conn->close();
    exit();
}

if ($user['status'] === 'rejected') {
    echo json_encode(['success' => false, 'message' => 'Your account has been rejected. Please contact the registrar.']);
    $conn->close();
    exit();
}

// ── Login success — initialize secure session ─────────────────────────────────
clearLoginRateLimit($email);
initializeSession($user, $conn);

// Log the login with IP
logAction($conn, $user['id'], 'User logged in from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// ── Determine redirect ────────────────────────────────────────────────────────
$redirect = 'dashboard.php';
switch ($user['role']) {
    case 'student':   $redirect = 'student/dashboard.php';   break;
    case 'teacher':   $redirect = 'teacher/dashboard.php';   break;
    case 'registrar': $redirect = 'registrar/dashboard.php'; break;
    case 'admin':     $redirect = 'admin/dashboard.php';     break;
    case 'hr':        $redirect = 'hr/dashboard.php';        break;
}

echo json_encode([
    'success'  => true,
    'message'  => 'Login successful',
    'redirect' => BASE_URL . '/views/' . $redirect,
    'user'     => ['name' => $user['name'], 'role' => $user['role']]
]);

$conn->close();
?>
