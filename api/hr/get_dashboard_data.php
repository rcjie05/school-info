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
requireRole('hr');
$conn = getDBConnection();

// Ensure tables exist
$conn->query("CREATE TABLE IF NOT EXISTS `hr_employees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `employment_type` ENUM('full_time','part_time','contractual','probationary') NOT NULL DEFAULT 'full_time',
  `hire_date` DATE DEFAULT NULL,
  `salary_grade` VARCHAR(50) DEFAULT NULL,
  `monthly_salary` DECIMAL(10,2) DEFAULT NULL,
  `position` VARCHAR(255) DEFAULT NULL,
  `department_id` INT DEFAULT NULL,
  `sss_number` VARCHAR(50) DEFAULT NULL,
  `philhealth_number` VARCHAR(50) DEFAULT NULL,
  `pagibig_number` VARCHAR(50) DEFAULT NULL,
  `tin_number` VARCHAR(50) DEFAULT NULL,
  `emergency_contact_name` VARCHAR(255) DEFAULT NULL,
  `emergency_contact_phone` VARCHAR(50) DEFAULT NULL,
  `emergency_contact_relation` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active','on_leave','resigned','terminated') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `hr_leave_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `max_days_per_year` INT NOT NULL DEFAULT 5,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `hr_leave_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `leave_type_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_days` INT NOT NULL DEFAULT 1,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `reviewed_by` INT DEFAULT NULL,
  `review_note` TEXT DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`leave_type_id`) REFERENCES `hr_leave_types`(`id`),
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed default leave types if empty
$check = $conn->query("SELECT COUNT(*) as cnt FROM hr_leave_types")->fetch_assoc();
if ($check['cnt'] == 0) {
    $conn->query("INSERT INTO hr_leave_types (name, max_days_per_year, description) VALUES
        ('Sick Leave', 15, 'Leave due to illness or medical appointments'),
        ('Vacation Leave', 15, 'Annual vacation or personal leave'),
        ('Emergency Leave', 3, 'Unexpected personal or family emergency'),
        ('Maternity Leave', 105, 'Maternity leave for female employees'),
        ('Paternity Leave', 7, 'Paternity leave for male employees'),
        ('Bereavement Leave', 3, 'Leave due to death of immediate family member')");
}

// Stats
$year = date('Y');

$totalStaff   = $conn->query("SELECT COUNT(*) as c FROM users WHERE role IN ('teacher','registrar','admin') AND status IN ('active','approved')")->fetch_assoc()['c'];
$totalEmployees = $conn->query("SELECT COUNT(*) as c FROM hr_employees")->fetch_assoc()['c'];
$pendingLeaves  = $conn->query("SELECT COUNT(*) as c FROM hr_leave_requests WHERE status='pending'")->fetch_assoc()['c'];
$approvedLeaves = $conn->query("SELECT COUNT(*) as c FROM hr_leave_requests WHERE status='approved' AND YEAR(start_date)=$year")->fetch_assoc()['c'];
$onLeaveToday   = $conn->query("SELECT COUNT(*) as c FROM hr_leave_requests WHERE status='approved' AND start_date <= CURDATE() AND end_date >= CURDATE()")->fetch_assoc()['c'];

// Recent leave requests
$recentLeaves = $conn->query("
    SELECT lr.id, lr.start_date, lr.end_date, lr.total_days, lr.status, lr.reason, lr.created_at,
           u.name as employee_name, u.role as employee_role,
           lt.name as leave_type
    FROM hr_leave_requests lr
    JOIN users u ON lr.user_id = u.id
    JOIN hr_leave_types lt ON lr.leave_type_id = lt.id
    ORDER BY lr.created_at DESC
    LIMIT 10
");
$recentLeavesArr = [];
while ($row = $recentLeaves->fetch_assoc()) $recentLeavesArr[] = $row;

// Upcoming approved leaves (next 30 days)
$upcoming = $conn->query("
    SELECT lr.start_date, lr.end_date, lr.total_days,
           u.name as employee_name,
           lt.name as leave_type
    FROM hr_leave_requests lr
    JOIN users u ON lr.user_id = u.id
    JOIN hr_leave_types lt ON lr.leave_type_id = lt.id
    WHERE lr.status = 'approved' AND lr.start_date >= CURDATE() AND lr.start_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY lr.start_date ASC
");
$upcomingArr = [];
while ($row = $upcoming->fetch_assoc()) $upcomingArr[] = $row;

// Employment type breakdown
$empTypes = $conn->query("
    SELECT employment_type, COUNT(*) as count FROM hr_employees GROUP BY employment_type
");
$empTypesArr = [];
while ($row = $empTypes->fetch_assoc()) $empTypesArr[] = $row;

echo json_encode([
    'success' => true,
    'stats' => [
        'total_staff'      => $totalStaff,
        'total_employees'  => $totalEmployees,
        'pending_leaves'   => $pendingLeaves,
        'approved_leaves'  => $approvedLeaves,
        'on_leave_today'   => $onLeaveToday,
    ],
    'recent_leaves'   => $recentLeavesArr,
    'upcoming_leaves' => $upcomingArr,
    'emp_types'       => $empTypesArr,
]);
$conn->close();
?>
