<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../php/config.php';
ob_clean();
header('Content-Type: application/json');

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');

$input    = json_decode(file_get_contents('php://input'), true);
$conn     = getDBConnection();

$job_id      = intval($input['job_id']      ?? 0);
$full_name   = sanitizeInput($input['full_name']   ?? '');
$email       = sanitizeInput($input['email']       ?? '');
$phone       = sanitizeInput($input['phone']       ?? '');
$address     = sanitizeInput($input['address']     ?? '');
$resume_notes= sanitizeInput($input['resume_notes']?? '');

// Validate
if (!$job_id || !$full_name || !$email || !$phone) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

// Check job exists and is open
$jobCheck = $conn->prepare("SELECT id, title FROM hr_job_postings WHERE id = ? AND status = 'open'");
$jobCheck->bind_param("i", $job_id);
$jobCheck->execute();
$job = $jobCheck->get_result()->fetch_assoc();
if (!$job) {
    echo json_encode(['success' => false, 'message' => 'This position is no longer accepting applications.']);
    exit;
}

// Check for duplicate application (same email + same job)
$dupCheck = $conn->prepare("SELECT id FROM hr_applicants WHERE job_id = ? AND email = ?");
$dupCheck->bind_param("is", $job_id, $email);
$dupCheck->execute();
if ($dupCheck->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already submitted an application for this position.']);
    exit;
}

// Find a default HR user to assign as handler (first HR user)
$hrUser = $conn->query("SELECT id FROM users WHERE role = 'hr' AND status = 'active' LIMIT 1")->fetch_assoc();
$handled_by = $hrUser ? $hrUser['id'] : 1;

// Insert applicant
$stmt = $conn->prepare("
    INSERT INTO hr_applicants (job_id, full_name, email, phone, address, resume_notes, stage, handled_by)
    VALUES (?, ?, ?, ?, ?, ?, 'applied', ?)
");
$stmt->bind_param("isssssi", $job_id, $full_name, $email, $phone, $address, $resume_notes, $handled_by);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit application. Please try again.']);
}

$conn->close();
?>
