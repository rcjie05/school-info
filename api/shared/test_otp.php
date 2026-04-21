<?php
// Quick test — visit this URL to confirm PHP is working
// http://localhost/school-mgmt-fixed/php/api/test_otp.php
// DELETE this file after confirming it works.

ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once __DIR__ . '/../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────

$conn = getDBConnection();
$result = [
    'php_ok'    => true,
    'php_ver'   => PHP_VERSION,
    'db_ok'     => ($conn !== null),
    'db_error'  => $conn ? null : 'Connection failed',
];

if ($conn) {
    // Check if OTP table exists
    $r = $conn->query("SHOW TABLES LIKE 'password_reset_otps'");
    $result['otp_table_exists'] = ($r && $r->num_rows > 0);

    // Check users table
    $r2 = $conn->query("SELECT COUNT(*) AS cnt FROM users");
    $result['users_count'] = $r2 ? $r2->fetch_assoc()['cnt'] : 'error';

    $conn->close();
}

echo json_encode($result, JSON_PRETTY_PRINT);
