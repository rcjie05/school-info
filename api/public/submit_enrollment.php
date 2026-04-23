<?php
// Turn on all errors so we can see what's crashing
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
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

function si($v) { return trim(strip_tags($v ?? '')); }

$surname          = si($input['surname']          ?? '');
$firstname        = si($input['firstname']        ?? '');
$middlename       = si($input['middlename']       ?? '');
$mobile           = si($input['mobile']           ?? '');
$byear            = si($input['byear']            ?? '');
$bmonth           = si($input['bmonth']           ?? '');
$bday             = si($input['bday']             ?? '');
$sex              = si($input['sex']              ?? '');
$religion         = si($input['religion']         ?? '');
$nationality      = si($input['nationality']      ?? '');
$civil_status     = si($input['civil_status']     ?? '');
$pob              = si($input['pob']              ?? '');
$address          = si($input['address']          ?? '');
$semester         = si($input['semester']         ?? '');
$year_level       = si($input['year_level']       ?? '');
$course           = si($input['course']           ?? '');
$acad_status      = si($input['acad_status']      ?? 'Regular');
$student_type     = si($input['student_type']     ?? 'New Student');
$student_id       = si($input['student_id']       ?? '');
$prev_school      = si($input['prev_school']      ?? '');
$prev_addr        = si($input['prev_addr']        ?? '');
$father_name      = si($input['father_name']      ?? '');
$father_occup     = si($input['father_occup']     ?? '');
$mother_name      = si($input['mother_name']      ?? '');
$mother_occup     = si($input['mother_occup']     ?? '');
$guardian_name    = si($input['guardian_name']    ?? '');
$guardian_rel     = si($input['guardian_rel']     ?? '');
$guardian_contact = si($input['guardian_contact'] ?? '');

$full_name = trim("$firstname $middlename $surname");
if (!$surname || !$firstname || !$course || !$year_level || !$semester) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}

$dob = null;
if ($byear && $bmonth && $bday) {
    $dob_str = "$byear-" . date('m', strtotime($bmonth . " 1")) . "-" . str_pad($bday, 2, '0', STR_PAD_LEFT);
    $dob = date('Y-m-d', strtotime($dob_str)) ?: null;
}

// Direct DB connection - no config.php
$conn = new mysqli('localhost', 'root', '', 'school_management', 3306);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connect failed: ' . $conn->connect_error]);
    exit();
}
$conn->set_charset('utf8mb4');

$ay = 'N/A';
$sy = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='current_school_year' LIMIT 1");
if ($sy) { $r = $sy->fetch_assoc(); if ($r) $ay = $r['setting_value']; }
$ay = si($input['ay'] ?? $ay);

// Returning student check
$is_returning = false;
$existing_user_id = null;
if ($student_id) {
    $q = $conn->prepare("SELECT id, name FROM users WHERE student_id = ? LIMIT 1");
    $q->bind_param("s", $student_id);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    if ($row) {
        if (strtolower(trim($row['name'])) === strtolower(trim($full_name))) {
            $is_returning = true;
            $existing_user_id = (int)$row['id'];
        } else {
            $conn->close();
            echo json_encode(['success' => false, 'message' => 'Student ID already registered to another student.']);
            exit();
        }
    }
}

// Duplicate check
if (!$is_returning) {
    $dup = $conn->prepare("SELECT id FROM users WHERE name=? AND course=? AND year_level=? AND status='pending' AND role='student' LIMIT 1");
    $dup->bind_param("sss", $full_name, $course, $year_level);
    $dup->execute();
    $has_dup = $dup->get_result()->num_rows > 0;
    $dup->close();
    if ($has_dup) {
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'A pending application already exists for this student.']);
        exit();
    }
}

// Insert or update user
if ($existing_user_id) {
    $upd = $conn->prepare("UPDATE users SET status='pending', course=?, year_level=? WHERE id=?");
    $upd->bind_param("ssi", $course, $year_level, $existing_user_id);
    $upd->execute();
    $upd->close();
    $user_id = $existing_user_id;
} else {
    $temp_pw = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    $email   = strtolower(preg_replace('/\s+/', '.', $firstname)) . '.' . strtolower(preg_replace('/\s+/', '', $surname)) . '.' . time() . '@enrollment.pending';
    $ins = $conn->prepare("INSERT INTO users (name,email,student_id,course,year_level,password,role,status,created_at) VALUES (?,?,?,?,?,?,'student','pending',NOW())");
    $ins->bind_param("ssssss", $full_name, $email, $student_id, $course, $year_level, $temp_pw);
    if (!$ins->execute()) {
        $e = $ins->error; $conn->close();
        echo json_encode(['success' => false, 'message' => 'Failed to save user: ' . $e]);
        exit();
    }
    $user_id = $conn->insert_id;
    $ins->close();
}

// Delete old enrollment_details and reinsert
$conn->query("DELETE FROM enrollment_details WHERE user_id=$user_id");

$det = $conn->prepare(
    "INSERT INTO enrollment_details
        (user_id,first_name,middle_name,last_name,
         dob,sex,civil_status,nationality,religion,
         place_of_birth,mobile_number,home_address,
         enrollment_type,semester,school_year,
         prev_school,prev_school_addr,
         father_name,father_occupation,
         mother_name,mother_occupation,
         guardian_name,guardian_relation,guardian_contact)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
);
if ($det) {
    $det->bind_param("iissssssssssssssssssssss",
        $user_id,
        $firstname, $middlename, $surname,
        $dob, $sex, $civil_status, $nationality, $religion,
        $pob, $mobile, $address,
        $student_type, $semester, $ay,
        $prev_school, $prev_addr,
        $father_name, $father_occup,
        $mother_name, $mother_occup,
        $guardian_name, $guardian_rel, $guardian_contact
    );
    if (!$det->execute()) {
        error_log('enrollment_details error: ' . $det->error);
    }
    $det->close();
}

// Notify registrars
$msg = "New enrollment: $full_name — $course ($year_level, $semester)";
$regs = $conn->query("SELECT id FROM users WHERE role='registrar' AND status='active'");
if ($regs) {
    while ($reg = $regs->fetch_assoc()) {
        $ns = $conn->prepare("INSERT INTO notifications (user_id,title,message) VALUES (?,?,?)");
        if ($ns) {
            $t = 'New Enrollment Application';
            $ns->bind_param("iss", $reg['id'], $t, $msg);
            $ns->execute();
            $ns->close();
        }
    }
}

// Audit log
$log = $conn->prepare("INSERT INTO audit_logs (user_id,action,table_name,record_id,created_at) VALUES (?,?,?,?,NOW())");
if ($log) {
    $action     = 'enrollment_form_submitted';
    $table_name = 'users';
    $log->bind_param("issi", $user_id, $action, $table_name, $user_id);
    $log->execute();
    $log->close();
}

$conn->close();

echo json_encode([
    'success'    => true,
    'message'    => 'Enrollment submitted successfully! Please wait for approval from the Registrar.',
    'student_id' => $user_id
]);
?>
