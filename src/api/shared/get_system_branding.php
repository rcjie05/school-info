<?php
/**
 * get_system_branding.php
 * Public endpoint — returns school_name and school_logo for any logged-in user.
 * Called by every role's pages so the sidebar reflects admin-set branding.
 */
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
header('Content-Type: application/json');

// Must be logged in (any role)
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = getDBConnection();

$stmt = $conn->prepare(
    "SELECT setting_key, setting_value
     FROM system_settings
     WHERE setting_key IN ('school_name', 'school_logo')"
);
$stmt->execute();
$result = $stmt->get_result();

$branding = [
    'school_name' => '',
    'school_logo' => '',
];
while ($row = $result->fetch_assoc()) {
    $branding[$row['setting_key']] = $row['setting_value'];
}
$stmt->close();
$conn->close();

echo json_encode([
    'success'     => true,
    'school_name' => $branding['school_name'],
    'school_logo' => $branding['school_logo'],
    'base_url'    => BASE_URL,
]);
