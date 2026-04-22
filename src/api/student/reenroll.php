<?php
/**
 * Returnee Re-Enrollment API
 * Updates existing student record with new enrollment details for the upcoming semester.
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

if (!isLoggedIn() || !hasRole('student')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Verify student is active/returnee — only they can use this endpoint
$chk = $conn->prepare("SELECT id, name, student_id, course, status FROM users WHERE id = ? AND role = 'student'");
$chk->bind_param("i", $user_id);
$chk->execute();
$student = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found.']);
    $conn->close(); exit();
}

$allowed_statuses = ['active', 'enrolled', 'approved'];
if (!in_array(strtolower($student['status']), $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Only returning students can use this re-enrollment form.']);
    $conn->close(); exit();
}

// Sanitize inputs
function clean($val) {
    return htmlspecialchars(strip_tags(trim($val ?? '')), ENT_QUOTES, 'UTF-8');
}

$full_name      = clean($input['full_name'] ?? '');
$dob            = clean($input['dob'] ?? '');
$sex            = clean($input['sex'] ?? '');
$civil_status   = clean($input['civil_status'] ?? '');
$nationality    = clean($input['nationality'] ?? 'Filipino');
$mobile_number  = clean($input['mobile_number'] ?? '');
$home_address   = clean($input['home_address'] ?? '');
$year_level     = clean($input['year_level'] ?? '');
$semester       = clean($input['semester'] ?? '');
$school_year    = clean($input['school_year'] ?? '');
$section_id     = !empty($input['section_id']) ? (int)$input['section_id'] : null;
$enrollment_type = 'Returnee';

// Required field validation
if (!$full_name || !$mobile_number || !$home_address || !$year_level || !$semester) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    $conn->close(); exit();
}

// Validate section exists if provided
if ($section_id) {
    $sec = $conn->prepare("SELECT id FROM sections WHERE id = ? AND status = 'active'");
    $sec->bind_param("i", $section_id);
    $sec->execute();
    if ($sec->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected section is no longer available. Please choose another.']);
        $conn->close(); exit();
    }
    $sec->close();
}

// Update user name & year level
$upd = $conn->prepare("UPDATE users SET name = ?, year_level = ?, section_id = ?, status = 'pending' WHERE id = ?");
$upd->bind_param("ssii", $full_name, $year_level, $section_id, $user_id);
if (!$upd->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to update student record: ' . $conn->error]);
    $conn->close(); exit();
}
$upd->close();

// Ensure enrollment_details table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS `enrollment_details` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL UNIQUE,
        `dob` DATE DEFAULT NULL,
        `sex` VARCHAR(20) DEFAULT NULL,
        `civil_status` VARCHAR(30) DEFAULT NULL,
        `nationality` VARCHAR(100) DEFAULT NULL,
        `place_of_birth` VARCHAR(255) DEFAULT NULL,
        `mobile_number` VARCHAR(50) DEFAULT NULL,
        `home_address` TEXT DEFAULT NULL,
        `enrollment_type` VARCHAR(50) DEFAULT 'Returnee',
        `semester` VARCHAR(30) DEFAULT NULL,
        `school_year` VARCHAR(20) DEFAULT NULL,
        `prev_school` VARCHAR(255) DEFAULT NULL,
        `father_name` VARCHAR(255) DEFAULT NULL,
        `mother_name` VARCHAR(255) DEFAULT NULL,
        `guardian_name` VARCHAR(255) DEFAULT NULL,
        `emergency_contact_name` VARCHAR(255) DEFAULT NULL,
        `emergency_contact_relation` VARCHAR(100) DEFAULT NULL,
        `emergency_contact_phone` VARCHAR(50) DEFAULT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Upsert enrollment_details (INSERT or UPDATE if record exists)
$dob_val = $dob ?: null;
$det = $conn->prepare("
    INSERT INTO enrollment_details
        (user_id, dob, sex, civil_status, nationality, mobile_number,
         home_address, enrollment_type, semester, school_year)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        dob = VALUES(dob),
        sex = VALUES(sex),
        civil_status = VALUES(civil_status),
        nationality = VALUES(nationality),
        mobile_number = VALUES(mobile_number),
        home_address = VALUES(home_address),
        enrollment_type = VALUES(enrollment_type),
        semester = VALUES(semester),
        school_year = VALUES(school_year)
");
$det->bind_param("isssssssss",
    $user_id, $dob_val, $sex, $civil_status, $nationality,
    $mobile_number, $home_address, $enrollment_type, $semester, $school_year
);
$det->execute();
$det->close();

// Generate reference number
$ref_number = 'REN-' . date('Y') . '-' . str_pad($user_id, 5, '0', STR_PAD_LEFT);

// Notify registrars
$msg = "Returnee re-enrollment from {$student['name']} ({$student['student_id']}) — {$student['course']} $year_level, $semester";
$regs = $conn->query("SELECT id FROM users WHERE role IN ('registrar','admin') AND status = 'active'");
while ($r = $regs->fetch_assoc()) {
    $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
    $title = 'Returnee Re-Enrollment Application';
    $notif->bind_param("iss", $r['id'], $title, $msg);
    $notif->execute();
    $notif->close();
}

$conn->close();

echo json_encode([
    'success' => true,
    'message' => 'Re-enrollment submitted successfully!',
    'ref'     => $ref_number,
]);
?>
