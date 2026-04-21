<?php
/**
 * Secure Session Manager - Simplified & Reliable
 */

function configureSecureSession() {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    ini_set('session.use_strict_mode',  1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_trans_sid',    0);
    ini_set('session.cookie_httponly',  1);
    ini_set('session.cookie_samesite',  'Lax');
    ini_set('session.cookie_secure',    $isHttps ? 1 : 0);
    ini_set('session.gc_maxlifetime',   1200); // 20 minutes
    ini_set('session.cookie_lifetime',  0);
    ini_set('session.name',             'SCC_SESS');
}

function startSecureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    configureSecureSession();
    session_start();
}

/**
 * Called automatically at the bottom of config.php after session starts.
 * Checks the session token on EVERY page load — even pages that don't
 * explicitly call requireLogin() — so concurrent session detection is
 * guaranteed to fire regardless of which page the user is on.
 */
function autoCheckConcurrentSession() {
    // Only check if user appears to be logged in
    if (!isset($_SESSION['user_id'], $_SESSION['session_token'])) return;

    // Skip API endpoints — they handle their own auth and return JSON
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/api/') !== false) return;

    $conn = null;
    // Use a simple inline connection to avoid circular dependency with config.php
    $host = defined('DB_HOST') ? DB_HOST : 'localhost';
    $port = defined('DB_PORT') ? (int)DB_PORT : 3306;
    $user = defined('DB_USER') ? DB_USER : 'root';
    $pass = defined('DB_PASS') ? DB_PASS : '';
    $db   = defined('DB_NAME') ? DB_NAME : 'school_management';

    $conn = new mysqli($host, $user, $pass, $db, $port);
    if ($conn->connect_error) return;

    $userId = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT session_token FROM users WHERE id = ?");
    if (!$stmt) { $conn->close(); return; }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$row) return; // user not found

    $db_token   = $row['session_token'] ?? null;
    $sess_token = $_SESSION['session_token'];

    // Token mismatch → another login happened → kick this session out
    if ($db_token !== null && !hash_equals($db_token, $sess_token)) {
        session_unset();
        session_destroy();
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        header('Location: ' . $baseUrl . '/login.html?error=session_displaced');
        exit();
    }
}

function validateSession() {
    if (!isset($_SESSION['user_id'])) return false;

    // Only check inactivity timeout - nothing else
    $timeout = 1200; // 20 minutes
    if (isset($_SESSION['last_activity'])) {
        if ((time() - $_SESSION['last_activity']) > $timeout) {
            destroySession();
            return false;
        }
    }

    // Update last activity on every request
    $_SESSION['last_activity'] = time();
    return true;
}

function initializeSession($user, $conn = null) {
    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['name']          = $user['name'];
    $_SESSION['email']         = $user['email'];
    $_SESSION['role']          = $user['role'];
    $_SESSION['course']        = $user['course'] ?? '';
    $_SESSION['last_activity'] = time();
    $_SESSION['created_at']    = time();

    // ── Concurrent session limit: invalidate any previous session ──
    if ($conn) {
        $token = generateSessionToken();
        $_SESSION['session_token'] = $token;
        writeSessionToken($conn, $user['id'], $token);
    }
}

function generateSessionToken() {
    return bin2hex(random_bytes(32)); // 64-char hex token
}

function writeSessionToken($conn, $userId, $token) {
    $stmt = $conn->prepare(
        "UPDATE users SET session_token = ?, session_started_at = NOW() WHERE id = ?"
    );
    if (!$stmt) {
        // Column likely doesn't exist yet - log and skip silently
        error_log("[SESSION] writeSessionToken failed (column missing?): " . $conn->error);
        return;
    }
    $stmt->bind_param("si", $token, $userId);
    if (!$stmt->execute()) {
        error_log("[SESSION] writeSessionToken execute failed: " . $stmt->error);
    }
    $stmt->close();
}

function validateSessionToken($conn, $userId, $token) {
    if (empty($token)) return false;
    $stmt = $conn->prepare("SELECT session_token FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    if (!$row) return false;
    return hash_equals((string)$row['session_token'], (string)$token);
}

function clearSessionToken($conn, $userId) {
    $stmt = $conn->prepare(
        "UPDATE users SET session_token = NULL, session_started_at = NULL WHERE id = ?"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function destroySession() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function checkLoginRateLimit($email) {
    $key      = 'login_attempts_' . md5($email . ($_SERVER['REMOTE_ADDR'] ?? ''));
    $maxTries = 5;
    $window   = 900; // 15 minutes

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }

    $data = &$_SESSION[$key];
    if ((time() - $data['first_attempt']) > $window) {
        $data = ['count' => 0, 'first_attempt' => time()];
    }

    $data['count']++;

    if ($data['count'] > $maxTries) {
        $remaining = $window - (time() - $data['first_attempt']);
        return [
            'allowed'  => false,
            'message'  => "Too many failed attempts. Try again in " . ceil($remaining / 60) . " minute(s)."
        ];
    }
    return ['allowed' => true];
}

function clearLoginRateLimit($email) {
    $key = 'login_attempts_' . md5($email . ($_SERVER['REMOTE_ADDR'] ?? ''));
    unset($_SESSION[$key]);
}
