<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
header('Content-Type: application/json');
requireRole('registrar');

$application_id = intval($_GET['id'] ?? 0);
if (!$application_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

$conn = getDBConnection();

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
        `enrollment_type` VARCHAR(50) DEFAULT 'New Student',
        `semester` VARCHAR(30) DEFAULT NULL,
        `school_year` VARCHAR(20) DEFAULT NULL,
        `prev_school` VARCHAR(255) DEFAULT NULL,
        `father_name` VARCHAR(255) DEFAULT NULL,
        `mother_name` VARCHAR(255) DEFAULT NULL,
        `guardian_name` VARCHAR(255) DEFAULT NULL,
        `emergency_contact_name` VARCHAR(255) DEFAULT NULL,
        `emergency_contact_relation` VARCHAR(100) DEFAULT NULL,
        `emergency_contact_phone` VARCHAR(50) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$stmt = $conn->prepare("
    SELECT
        u.id, u.name, u.email, u.student_id, u.course, u.year_level,
        u.status, u.avatar_url, u.created_at,
        s.section_name, s.section_code, s.semester as section_semester, s.school_year as section_school_year,
        d.dob, d.sex, d.civil_status, d.nationality, d.place_of_birth,
        d.mobile_number, d.home_address, d.enrollment_type, d.semester, d.school_year,
        d.prev_school, d.father_name, d.mother_name, d.guardian_name,
        d.emergency_contact_name, d.emergency_contact_relation, d.emergency_contact_phone
    FROM users u
    LEFT JOIN sections s ON u.section_id = s.id
    LEFT JOIN enrollment_details d ON u.id = d.user_id
    WHERE u.id = ? AND u.role = 'student'
");
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
if ($row) $row['avatar_url'] = getAvatarUrl($row['avatar_url'] ?? null);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Application not found']);
    $conn->close(); exit();
}

echo json_encode(['success' => true, 'application' => $row]);
$conn->close();
?>
