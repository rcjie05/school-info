<?php
// DEBUG FILE - shows exactly what submit_enrollment.php outputs
// Visit: http://localhost/school-info/api/public/enroll_debug.php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$output = [];

// Step 1: Can we read config.php?
$cfg = __DIR__ . '/../../php/config.php';
$output['config_exists'] = file_exists($cfg);
$output['config_readable'] = is_readable($cfg);

// Step 2: Parse DB credentials
$src = file_exists($cfg) ? file_get_contents($cfg) : '';
$get = function($k) use ($src) {
    return preg_match("/define\s*\(\s*['\"]" . preg_quote($k,'/') . "['\"]\s*,\s*'([^']*)'/", $src, $m) ? $m[1] : 'NOT FOUND';
};
$host = $get('DB_HOST');
$port = $get('DB_PORT');
$user = $get('DB_USER');
$pass_raw = $get('DB_PASS');
$db   = $get('DB_NAME');

$output['db_host'] = $host;
$output['db_port'] = $port;
$output['db_user'] = $user;
$output['db_pass'] = $pass_raw === '' ? '(empty)' : '(set)';
$output['db_name'] = $db;

// Step 3: Try connecting
try {
    $conn = new mysqli($host, $user, $pass_raw, $db, (int)$port);
    if ($conn->connect_error) {
        $output['db_connect'] = 'FAILED: ' . $conn->connect_error;
    } else {
        $output['db_connect'] = 'SUCCESS';
        // Step 4: Check tables exist
        $tables = [];
        $res = $conn->query("SHOW TABLES");
        while ($row = $res->fetch_row()) $tables[] = $row[0];
        $output['tables'] = $tables;
        $output['has_users'] = in_array('users', $tables);
        $output['has_enrollment_details'] = in_array('enrollment_details', $tables);
        $conn->close();
    }
} catch (Exception $e) {
    $output['db_connect'] = 'EXCEPTION: ' . $e->getMessage();
}

// Step 5: Check what submit_enrollment.php actually outputs
ob_clean();
$raw = shell_exec('php ' . escapeshellarg(__DIR__ . '/submit_enrollment.php') . ' 2>&1') ?? 'shell_exec not available';
$output['php_cli_available'] = function_exists('shell_exec');

header('Content-Type: application/json');
echo json_encode($output, JSON_PRETTY_PRINT);
