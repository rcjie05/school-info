<?php
require_once 'config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────

// Log the logout and clear session token before destroying session
if (isLoggedIn()) {
    $conn = getDBConnection();
    if ($conn) {
        logAction($conn, $_SESSION['user_id'], 'User logged out');
        // ── Clear session token so no ghost sessions remain ──
        clearSessionToken($conn, $_SESSION['user_id']);
        $conn->close();
    }
}

// Destroy session completely
destroySession();

// Redirect to login
header('Location: ' . BASE_URL . '/login.html');
exit();
?>
