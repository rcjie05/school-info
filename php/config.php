<?php
// ── Environment ───────────────────────────────────────────────────────────────
// putenv('GROQ_API_KEY=your_key_here'); // ← uncomment and add your Groq API key here

// Database configuration (XAMPP localhost)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_management');


// ── Base URL (works on both XAMPP and Railway) ────────────────────────────────
function getBaseUrl() {
    $docRoot     = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $projectRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $webPrefix   = str_replace($docRoot, '', $projectRoot);
    return $webPrefix ?: '';
}
define('BASE_URL', getBaseUrl());

// ── Secure Session ────────────────────────────────────────────────────────────
require_once __DIR__ . '/session.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
startSecureSession();
autoCheckConcurrentSession(); // ← enforces 1-session-per-account on every page

// ── Database Connection ───────────────────────────────────────────────────────
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }
}

// ── Auth Helpers ──────────────────────────────────────────────────────────────
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && validateSession();
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function checkUserStatus() {
    if (!isLoggedIn()) return false;

    $conn = getDBConnection();
    if (!$conn) return false;

    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT status, deactivated_until, session_token FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $conn->close();
        return false;
    }

    $user = $result->fetch_assoc();

    // ── Concurrent session check: kick out displaced sessions ──────────
    // If DB has a token AND session has a token AND they don't match → kicked out
    $db_token   = $user['session_token'] ?? null;
    $sess_token = $_SESSION['session_token'] ?? null;
    if ($db_token !== null && $sess_token !== null && !hash_equals($db_token, $sess_token)) {
        $conn->close();
        destroySession();
        header('Location: ' . BASE_URL . '/login.html?error=session_displaced');
        exit();
    }

    if ($user['status'] === 'inactive') {
        if ($user['deactivated_until']) {
            $deactivated_until = strtotime($user['deactivated_until']);
            if (time() >= $deactivated_until) {
                $update_stmt = $conn->prepare("UPDATE users SET status = 'active', deactivated_until = NULL, deactivation_reason = NULL WHERE id = ?");
                $update_stmt->bind_param("i", $user_id);
                $update_stmt->execute();
                $update_stmt->close();
                logAction($conn, $user_id, 'User auto-reactivated (suspension expired)', 'users', $user_id);
                $conn->close();
                return true;
            }
        }
        $conn->close();
        return false;
    }

    $conn->close();
    return true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Clear any stale session data
        destroySession();
        header('Location: ' . BASE_URL . '/login.html?error=session_expired');
        exit();
    }
    if (!checkUserStatus()) {
        destroySession();
        header('Location: ' . BASE_URL . '/login.html?error=deactivated');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        // Log unauthorized access attempt
        error_log("[SECURITY] Unauthorized role access: user_id=" . ($_SESSION['user_id'] ?? 'unknown') . " tried to access role=$role");
        header('Location: ' . BASE_URL . '/login.html?error=unauthorized');
        exit();
    }
}

// ── API-safe role check (returns JSON instead of redirecting) ─────────────────
function requireRoleApi($role) {
    header('Content-Type: application/json');
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
        exit();
    }
    if (!checkUserStatus()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Account is deactivated.']);
        exit();
    }
    if (!hasRole($role)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Insufficient role.']);
        exit();
    }
}

// ── Input Sanitization ────────────────────────────────────────────────────────
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// ── Notifications & Logging ───────────────────────────────────────────────────
function createNotification($conn, $user_id, $title, $message) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $title, $message);
    return $stmt->execute();
}

function logAction($conn, $user_id, $action, $table_name = null, $record_id = null) {
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $user_id, $action, $table_name, $record_id);
    return $stmt->execute();
}

// ── Avatar URL Helper ─────────────────────────────────────────────────────────
function getAvatarUrl($stored) {
    if (empty($stored)) return null;
    if (strpos($stored, 'http') === 0) return $stored;
    $relative = ltrim($stored, '/');
    if (strpos($relative, 'uploads/') !== false) {
        $relative = 'uploads/' . substr($relative, strpos($relative, 'uploads/') + 8);
    }
    $docRoot     = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $projectRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $webPrefix   = str_replace($docRoot, '', $projectRoot);
    return $webPrefix . '/' . $relative;
}

// ── System Settings ───────────────────────────────────────────────────────────
function getSystemSetting($conn, $key) {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) return $row['setting_value'];
    return null;
}

// ── SMTP Mail Configuration ───────────────────────────────────────────────────
define('SMTP_HOST',       'smtp.gmail.com');
define('SMTP_PORT',       587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USERNAME',   'godzdemonz05@gmail.com');
define('SMTP_PASSWORD',   'REPLACE_WITH_YOUR_GMAIL_APP_PASSWORD');
define('SMTP_FROM_EMAIL', 'godzdemonz05@gmail.com');
// ── HOW TO GET YOUR GMAIL APP PASSWORD ────────────────────────────────────────
// 1. Go to myaccount.google.com
// 2. Security → Enable 2-Step Verification first
// 3. Search "App passwords" → Select Mail → Other → type "XAMPP"
// 4. Copy the 16-character password → paste it above replacing REPLACE_WITH_YOUR_GMAIL_APP_PASSWORD
// ─────────────────────────────────────────────────────────────────────────────
// SMTP_FROM_NAME is set dynamically — see smtp config
define('SMTP_FROM_NAME', (function(){ $c=getDBConnection(); $r=$c?$c->query("SELECT setting_value FROM system_settings WHERE setting_key='school_name' LIMIT 1"):false; $n=$r?$r->fetch_assoc()['setting_value']??"School Portal":"School Portal"; $c&&$c->close(); return $n; })());
