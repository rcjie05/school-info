<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../php/config.php';
ob_clean();
header('Content-Type: application/json');

// ── Dynamic school name & school year ──────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name         = 'My School';
$current_school_year = '----';
if ($_sn_res) {
    while ($_sn_row = $_sn_res->fetch_assoc()) {
        if ($_sn_row['setting_key'] === 'school_name')         $school_name         = $_sn_row['setting_value'];
        if ($_sn_row['setting_key'] === 'current_school_year') $current_school_year = $_sn_row['setting_value'];
    }
}
// ───────────────────────────────────────────────────────────────────────

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit();
}

// ── Helper ─────────────────────────────────────────────────────────────
function si($v) { return trim(strip_tags($v ?? '')); }

// ── Pull all fields ────────────────────────────────────────────────────
$surname      = si($input['surname']      ?? '');
$firstname    = si($input['firstname']    ?? '');
$middlename   = si($input['middlename']   ?? '');
$mobile       = si($input['mobile']       ?? '');
$byear        = si($input['byear']        ?? '');
$bmonth       = si($input['bmonth']       ?? '');
$bday         = si($input['bday']         ?? '');
$sex          = si($input['sex']          ?? '');
$religion     = si($input['religion']     ?? '');
$civil_status = si($input['civil_status'] ?? '');
$pob          = si($input['pob']          ?? '');
$address      = si($input['address']      ?? '');
$semester     = si($input['semester']     ?? '');
$ay           = si($input['ay']           ?? $current_school_year);
$year_level   = si($input['year_level']   ?? '');
$course       = si($input['course']       ?? '');
$acad_status  = si($input['acad_status']  ?? 'Regular');
$student_type = si($input['student_type'] ?? 'New Student');
$shift_from   = si($input['shift_from']   ?? '');
$shift_to     = si($input['shift_to']     ?? '');
$student_id   = si($input['student_id']   ?? '');
$prev_school  = si($input['prev_school']  ?? '');
$prev_addr    = si($input['prev_addr']    ?? '');
$father_name  = si($input['father_name']  ?? '');
$father_occup = si($input['father_occup'] ?? '');
$mother_name  = si($input['mother_name']  ?? '');
$mother_occup = si($input['mother_occup'] ?? '');
$requirements = si($input['requirements'] ?? '');

// ── Validate required fields ───────────────────────────────────────────
$full_name = trim("$firstname $middlename $surname");
if (!$surname || !$firstname || !$course || !$year_level || !$semester) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields (name, course, year level, semester).']);
    exit();
}

// ── Build DOB string ───────────────────────────────────────────────────
$dob = null;
if ($byear && $bmonth && $bday) {
    $dob_str = "$byear-" . date('m', strtotime($bmonth . " 1")) . "-" . str_pad($bday, 2, '0', STR_PAD_LEFT);
    $dob     = date('Y-m-d', strtotime($dob_str)) ?: null;
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// ── Duplicate check: same name + course + semester already pending ─────
$dup = $conn->prepare("
    SELECT id FROM users
    WHERE name = ? AND course = ? AND year_level = ? AND status = 'pending' AND role = 'student'
    LIMIT 1
");
$dup->bind_param("sss", $full_name, $course, $year_level);
$dup->execute();
if ($dup->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'An enrollment application with this name, course, and year level is already pending review.']);
    exit();
}

// ── If student_id given, check it's not already taken ──────────────────
if ($student_id) {
    $idck = $conn->prepare("SELECT id FROM users WHERE student_id = ? LIMIT 1");
    $idck->bind_param("s", $student_id);
    $idck->execute();
    if ($idck->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Student ID is already registered.']);
        exit();
    }
}

// ── Insert into users (status = pending) ──────────────────────────────
$temp_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT); // locked until approved
$email_placeholder = strtolower(preg_replace('/\s+/', '.', $firstname)) . '.' . strtolower(preg_replace('/\s+/', '', $surname)) . '.' . time() . '@enrollment.pending';

$stmt = $conn->prepare("
    INSERT INTO users (name, email, student_id, course, year_level, password, role, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'student', 'pending', NOW())
");
$stmt->bind_param("ssssss",
    $full_name, $email_placeholder, $student_id,
    $course, $year_level, $temp_password
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to save enrollment: ' . $conn->error]);
    exit();
}

$user_id = $conn->insert_id;

// ── Insert detailed enrollment info ────────────────────────────────────
$col_check = $conn->query("SHOW TABLES LIKE 'enrollment_details'");
if ($col_check && $col_check->num_rows > 0) {
    $det = $conn->prepare("
        INSERT INTO enrollment_details
            (user_id, first_name, middle_name, last_name, dob, sex, civil_status, religion,
             place_of_birth, mobile_number, home_address, enrollment_type, semester, school_year,
             prev_school, prev_school_addr, father_name, father_occupation, mother_name, mother_occupation)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $det->bind_param("isssssssssssssssssss",
        $user_id, $firstname, $middlename, $surname,
        $dob, $sex, $civil_status, $religion,
        $pob, $mobile, $address, $student_type, $semester, $ay,
        $prev_school, $prev_addr,
        $father_name, $father_occup, $mother_name, $mother_occup
    );
    $det->execute(); // non-fatal if it fails
}

// ── Notify all registrars ──────────────────────────────────────────────
$msg = "New enrollment form submitted: $full_name — $course ($year_level, $semester)";
if ($student_id) $msg .= " | ID: $student_id";

$reg_res = $conn->query("SELECT id FROM users WHERE role = 'registrar' AND status = 'active'");
if ($reg_res) {
    while ($reg = $reg_res->fetch_assoc()) {
        createNotification($conn, $reg['id'], 'New Enrollment Application', $msg);
    }
}

// ── Log audit ─────────────────────────────────────────────────────────
$log_check = $conn->query("SHOW TABLES LIKE 'audit_logs'");
if ($log_check && $log_check->num_rows > 0) {
    $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, created_at) VALUES (?,?,?,NOW())");
    $action  = 'enrollment_form_submitted';
    $details = json_encode(['name' => $full_name, 'course' => $course, 'year_level' => $year_level, 'semester' => $semester]);
    $log->bind_param("iss", $user_id, $action, $details);
    $log->execute();
}

$conn->close();

echo json_encode([
    'success'    => true,
    'message'    => 'Enrollment form submitted successfully! Please wait for approval from the Registrar.',
    'student_id' => $user_id
]);
?>
