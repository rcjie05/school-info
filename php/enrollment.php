<?php
require_once 'config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// ── Core fields ──────────────────────────────────────────
$name       = sanitizeInput($_POST['name']       ?? '');
$email      = sanitizeInput($_POST['email']      ?? '');
$student_id = sanitizeInput($_POST['student_id'] ?? '');
$course     = sanitizeInput($_POST['course']     ?? '');
$year_level = sanitizeInput($_POST['year_level'] ?? '');
$password   = $_POST['password'] ?? '';
$section_id = !empty($_POST['section_id']) ? (int)$_POST['section_id'] : null;

// ── Name parts ────────────────────────────────────────────
$first_name  = sanitizeInput($_POST['first_name']  ?? '');
$middle_name = sanitizeInput($_POST['middle_name'] ?? '');
$last_name   = sanitizeInput($_POST['last_name']   ?? '');
$suffix      = sanitizeInput($_POST['suffix']      ?? '');
$nickname    = sanitizeInput($_POST['nickname']    ?? '');

// ── Personal details ──────────────────────────────────────
$dob             = sanitizeInput($_POST['dob']             ?? '');
$sex             = sanitizeInput($_POST['sex']             ?? '');
$civil_status    = sanitizeInput($_POST['civil_status']    ?? '');
$nationality     = sanitizeInput($_POST['nationality']     ?? 'Filipino');
$religion        = sanitizeInput($_POST['religion']        ?? '');
$place_of_birth  = sanitizeInput($_POST['place_of_birth']  ?? '');
$mobile_number   = sanitizeInput($_POST['mobile_number']   ?? '');
$landline_number = sanitizeInput($_POST['landline_number'] ?? '');
$home_address    = sanitizeInput($_POST['home_address']    ?? '');

// ── Academic details ──────────────────────────────────────
$enrollment_type   = sanitizeInput($_POST['enrollment_type']   ?? 'New Student');
$semester          = sanitizeInput($_POST['semester']          ?? '');
$school_year       = sanitizeInput($_POST['school_year']       ?? '');
$prev_school       = sanitizeInput($_POST['prev_school']       ?? '');
$prev_school_addr  = sanitizeInput($_POST['prev_school_addr']  ?? '');
$prev_school_year  = sanitizeInput($_POST['prev_school_year']  ?? '');

// ── Family background ─────────────────────────────────────
$father_name       = sanitizeInput($_POST['father_name']       ?? '');
$father_occupation = sanitizeInput($_POST['father_occupation'] ?? '');
$father_contact    = sanitizeInput($_POST['father_contact']    ?? '');
$father_income     = sanitizeInput($_POST['father_income']     ?? '');

$mother_name       = sanitizeInput($_POST['mother_name']       ?? '');
$mother_occupation = sanitizeInput($_POST['mother_occupation'] ?? '');
$mother_contact    = sanitizeInput($_POST['mother_contact']    ?? '');
$mother_income     = sanitizeInput($_POST['mother_income']     ?? '');

$guardian_name     = sanitizeInput($_POST['guardian_name']     ?? '');
$guardian_relation = sanitizeInput($_POST['guardian_relation'] ?? '');
$guardian_contact  = sanitizeInput($_POST['guardian_contact']  ?? '');
$guardian_address  = sanitizeInput($_POST['guardian_address']  ?? '');

// ── Emergency contact ─────────────────────────────────────
$emergency_contact_name     = sanitizeInput($_POST['emergency_contact_name']     ?? '');
$emergency_contact_relation = sanitizeInput($_POST['emergency_contact_relation'] ?? '');
$emergency_contact_phone    = sanitizeInput($_POST['emergency_contact_phone']    ?? '');

// ── Validation ───────────────────────────────────────────
if (!$name || !$email || !$student_id || !$course || !$year_level || !$password) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit();
}
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// ── Duplicate checks ─────────────────────────────────────
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This email address is already registered.']);
    $conn->close(); exit();
}

$stmt = $conn->prepare("SELECT id FROM users WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This LRN/Student ID is already registered.']);
    $conn->close(); exit();
}

// ── Validate section ────────────────────────────────────
if ($section_id) {
    $sec = $conn->prepare("SELECT id FROM sections WHERE id = ? AND status = 'active'");
    $sec->bind_param("i", $section_id);
    $sec->execute();
    if ($sec->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected section is no longer available.']);
        $conn->close(); exit();
    }
}

// ── Handle photo upload ──────────────────────────────────
$avatar_url = null;
if (!empty($_FILES['photo']['tmp_name'])) {
    $uploadDir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext  = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (in_array($ext, $allowed) && $_FILES['photo']['size'] <= 5 * 1024 * 1024) {
        $filename = 'enroll_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
            $avatar_url = 'uploads/avatars/' . $filename;
        }
    }
}

// ── Ensure enrollment_details table exists (full schema) ──
$conn->query("
    CREATE TABLE IF NOT EXISTS `enrollment_details` (
        `id`                        INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`                   INT NOT NULL UNIQUE,

        -- Name parts
        `first_name`                VARCHAR(100) DEFAULT NULL,
        `middle_name`               VARCHAR(100) DEFAULT NULL,
        `last_name`                 VARCHAR(100) DEFAULT NULL,
        `suffix`                    VARCHAR(20)  DEFAULT NULL,
        `nickname`                  VARCHAR(100) DEFAULT NULL,

        -- Personal details
        `dob`                       DATE         DEFAULT NULL,
        `sex`                       VARCHAR(20)  DEFAULT NULL,
        `civil_status`              VARCHAR(30)  DEFAULT NULL,
        `nationality`               VARCHAR(100) DEFAULT NULL,
        `religion`                  VARCHAR(100) DEFAULT NULL,
        `place_of_birth`            VARCHAR(255) DEFAULT NULL,
        `mobile_number`             VARCHAR(50)  DEFAULT NULL,
        `landline_number`           VARCHAR(50)  DEFAULT NULL,
        `home_address`              TEXT         DEFAULT NULL,

        -- Academic details
        `enrollment_type`           VARCHAR(50)  DEFAULT 'New Student',
        `semester`                  VARCHAR(30)  DEFAULT NULL,
        `school_year`               VARCHAR(20)  DEFAULT NULL,
        `prev_school`               VARCHAR(255) DEFAULT NULL,
        `prev_school_addr`          VARCHAR(255) DEFAULT NULL,
        `prev_school_year`          VARCHAR(20)  DEFAULT NULL,

        -- Father's info
        `father_name`               VARCHAR(255) DEFAULT NULL,
        `father_occupation`         VARCHAR(255) DEFAULT NULL,
        `father_contact`            VARCHAR(50)  DEFAULT NULL,
        `father_income`             VARCHAR(100) DEFAULT NULL,

        -- Mother's info
        `mother_name`               VARCHAR(255) DEFAULT NULL,
        `mother_occupation`         VARCHAR(255) DEFAULT NULL,
        `mother_contact`            VARCHAR(50)  DEFAULT NULL,
        `mother_income`             VARCHAR(100) DEFAULT NULL,

        -- Guardian info
        `guardian_name`             VARCHAR(255) DEFAULT NULL,
        `guardian_relation`         VARCHAR(100) DEFAULT NULL,
        `guardian_contact`          VARCHAR(50)  DEFAULT NULL,
        `guardian_address`          TEXT         DEFAULT NULL,

        -- Emergency contact
        `emergency_contact_name`     VARCHAR(255) DEFAULT NULL,
        `emergency_contact_relation` VARCHAR(100) DEFAULT NULL,
        `emergency_contact_phone`    VARCHAR(50)  DEFAULT NULL,

        `created_at`                TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at`                TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Auto-add any missing columns (for existing installs) ──
$alter_columns = [
    'first_name'                => 'VARCHAR(100) DEFAULT NULL AFTER user_id',
    'middle_name'               => 'VARCHAR(100) DEFAULT NULL AFTER first_name',
    'last_name'                 => 'VARCHAR(100) DEFAULT NULL AFTER middle_name',
    'suffix'                    => 'VARCHAR(20)  DEFAULT NULL AFTER last_name',
    'nickname'                  => 'VARCHAR(100) DEFAULT NULL AFTER suffix',
    'religion'                  => 'VARCHAR(100) DEFAULT NULL AFTER nationality',
    'landline_number'           => 'VARCHAR(50)  DEFAULT NULL AFTER mobile_number',
    'prev_school_addr'          => 'VARCHAR(255) DEFAULT NULL AFTER prev_school',
    'prev_school_year'          => 'VARCHAR(20)  DEFAULT NULL AFTER prev_school_addr',
    'father_occupation'         => 'VARCHAR(255) DEFAULT NULL AFTER father_name',
    'father_contact'            => 'VARCHAR(50)  DEFAULT NULL AFTER father_occupation',
    'father_income'             => 'VARCHAR(100) DEFAULT NULL AFTER father_contact',
    'mother_occupation'         => 'VARCHAR(255) DEFAULT NULL AFTER mother_name',
    'mother_contact'            => 'VARCHAR(50)  DEFAULT NULL AFTER mother_occupation',
    'mother_income'             => 'VARCHAR(100) DEFAULT NULL AFTER mother_contact',
    'guardian_relation'         => 'VARCHAR(100) DEFAULT NULL AFTER guardian_name',
    'guardian_contact'          => 'VARCHAR(50)  DEFAULT NULL AFTER guardian_relation',
    'guardian_address'          => 'TEXT         DEFAULT NULL AFTER guardian_contact',
    'updated_at'                => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
];
foreach ($alter_columns as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM `enrollment_details` LIKE '$col'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE `enrollment_details` ADD COLUMN `$col` $def");
    }
}

// ── Insert user record ───────────────────────────────────
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    INSERT INTO users (name, email, student_id, course, year_level, section_id, password, avatar_url, role, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'student', 'pending')
");
$stmt->bind_param("sssssiss",
    $name, $email, $student_id, $course, $year_level,
    $section_id, $hashed_password, $avatar_url
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $conn->error]);
    $conn->close(); exit();
}

$user_id = $conn->insert_id;
$ref_number = 'ENR-' . date('Y') . '-' . str_pad($user_id, 5, '0', STR_PAD_LEFT);

// ── Insert enrollment details (ALL fields) ───────────────
$dob_val = $dob ?: null;
$det = $conn->prepare("
    INSERT INTO enrollment_details
        (user_id,
         first_name, middle_name, last_name, suffix, nickname,
         dob, sex, civil_status, nationality, religion, place_of_birth,
         mobile_number, landline_number, home_address,
         enrollment_type, semester, school_year,
         prev_school, prev_school_addr, prev_school_year,
         father_name, father_occupation, father_contact, father_income,
         mother_name, mother_occupation, mother_contact, mother_income,
         guardian_name, guardian_relation, guardian_contact, guardian_address,
         emergency_contact_name, emergency_contact_relation, emergency_contact_phone)
    VALUES (?,
            ?,?,?,?,?,
            ?,?,?,?,?,?,
            ?,?,?,
            ?,?,?,
            ?,?,?,
            ?,?,?,?,
            ?,?,?,?,
            ?,?,?,?,
            ?,?,?)
");
$det->bind_param(
    "isssssisssssssssssssssssssssssssssss",
    $user_id,
    $first_name, $middle_name, $last_name, $suffix, $nickname,
    $dob_val, $sex, $civil_status, $nationality, $religion, $place_of_birth,
    $mobile_number, $landline_number, $home_address,
    $enrollment_type, $semester, $school_year,
    $prev_school, $prev_school_addr, $prev_school_year,
    $father_name, $father_occupation, $father_contact, $father_income,
    $mother_name, $mother_occupation, $mother_contact, $mother_income,
    $guardian_name, $guardian_relation, $guardian_contact, $guardian_address,
    $emergency_contact_name, $emergency_contact_relation, $emergency_contact_phone
);
$det->execute();

// ── Notify registrars ────────────────────────────────────
$msg = "New enrollment application from $name ($student_id) — $course $year_level";
$regs = $conn->query("SELECT id FROM users WHERE role = 'registrar' AND status = 'active'");
while ($r = $regs->fetch_assoc()) {
    createNotification($conn, $r['id'], 'New Enrollment Application', $msg);
}

// Also notify admins
$admins = $conn->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
while ($a = $admins->fetch_assoc()) {
    createNotification($conn, $a['id'], 'New Enrollment Application', $msg);
}

$conn->close();

echo json_encode([
    'success' => true,
    'message' => 'Enrollment submitted successfully!',
    'id'      => $user_id,
    'ref'     => $ref_number,
]);
?>
