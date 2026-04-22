<?php
/**
 * Registrar: securely download a submitted grade sheet Excel file
 * GET ?id=<submission_id>
 * Serves from DB blob first, falls back to disk file_path.
 */
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────

if (!isLoggedIn() || !hasRole('registrar')) {
    http_response_code(403);
    die('Access denied');
}

$submission_id = (int)($_GET['id'] ?? 0);
if (!$submission_id) { http_response_code(400); die('Invalid request'); }

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT file_path, file_name, file_data FROM grade_submissions WHERE id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row) {
    http_response_code(404);
    die('Submission not found');
}

$filename = $row['file_name'] ?: ('GradeSheet_' . $submission_id . '.xlsx');

// Try blob first
if (!empty($row['file_data'])) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($row['file_data']));
    header('Cache-Control: no-store');
    echo $row['file_data'];
    exit();
}

// Fall back to disk
if (!empty($row['file_path'])) {
    $docRoot  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $filePath = $docRoot . str_replace('/', DIRECTORY_SEPARATOR, $row['file_path']);

    if (file_exists($filePath)) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-store');
        readfile($filePath);
        exit();
    }
}

http_response_code(404);
die('File not found. The grade sheet may not have been uploaded with this submission.');
