<?php
/**
 * get_branding_public.php
 * Public endpoint — NO login required.
 * Returns school_name, school_logo, and current_school_year
 * for use on login, register, and index pages.
 */
require_once '../../php/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$conn = getDBConnection();

if (!$conn) {
    echo json_encode([
        'success'             => false,
        'school_name'         => '',
        'school_logo'         => '',
        'current_school_year' => '',
    ]);
    exit;
}

$result = $conn->query(
    "SELECT setting_key, setting_value
     FROM system_settings
     WHERE setting_key IN ('school_name', 'school_logo', 'current_school_year', 'school_address', 'school_email', 'school_phone', 'registrar_phone', 'school_website', 'school_facebook')"
);

$data = [
    'school_name'         => '',
    'school_logo'         => '',
    'current_school_year' => '',
    'school_address'      => '',
    'school_email'        => '',
    'school_phone'        => '',
    'registrar_phone'     => '',
    'school_website'      => '',
    'school_facebook'     => '',
];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[$row['setting_key']] = $row['setting_value'];
    }
}

// Auto-seed missing rows
$seedKeys = [
    'school_address'  => 'School address displayed on login, index, and reports',
    'school_email'    => 'Main school contact email',
    'school_phone'    => 'Main school contact number',
    'registrar_phone' => 'Registrar office contact number',
    'school_website'  => 'Official school website URL',
    'school_facebook' => 'Official Facebook page URL',
];
foreach ($seedKeys as $key => $desc) {
    if ($data[$key] === '') {
        $conn->query("INSERT IGNORE INTO system_settings (setting_key, setting_value, description)
                      VALUES ('$key', '', '$desc')");
    }
}

$conn->close();
$logoUrl = '';
if (!empty($data['school_logo'])) {
    $logoUrl = rtrim(BASE_URL, '/') . '/' . ltrim($data['school_logo'], '/');
}

echo json_encode([
    'success'             => true,
    'school_name'         => $data['school_name'],
    'school_logo'         => $logoUrl,
    'current_school_year' => $data['current_school_year'],
    'school_address'      => $data['school_address'],
    'school_email'        => $data['school_email'],
    'school_phone'        => $data['school_phone'],
    'registrar_phone'     => $data['registrar_phone'],
    'school_website'      => $data['school_website'],
    'school_facebook'     => $data['school_facebook'],
]);
