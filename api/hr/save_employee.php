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
$conn     = getDBConnection();
$hr_id = $_SESSION["user_id"];
$input    = json_decode(file_get_contents('php://input'), true);

$user_id                   = intval($input['user_id'] ?? 0);
$employment_type           = $input['employment_type'] ?? 'full_time';
$hire_date                 = $input['hire_date'] ?? null;
$salary_grade              = sanitizeInput($input['salary_grade'] ?? '');
$monthly_salary            = !empty($input['monthly_salary']) ? floatval($input['monthly_salary']) : null;
$position                  = sanitizeInput($input['position'] ?? '');
$department_id             = !empty($input['department_id']) ? intval($input['department_id']) : null;
$sss_number                = sanitizeInput($input['sss_number'] ?? '');
$philhealth_number         = sanitizeInput($input['philhealth_number'] ?? '');
$pagibig_number            = sanitizeInput($input['pagibig_number'] ?? '');
$tin_number                = sanitizeInput($input['tin_number'] ?? '');
$emergency_contact_name    = sanitizeInput($input['emergency_contact_name'] ?? '');
$emergency_contact_phone   = sanitizeInput($input['emergency_contact_phone'] ?? '');
$emergency_contact_relation= sanitizeInput($input['emergency_contact_relation'] ?? '');
$hr_status                 = $input['hr_status'] ?? 'active';

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

// Check if HR profile exists
$existing = $conn->prepare("SELECT id FROM hr_employees WHERE user_id = ?");
$existing->bind_param("i", $user_id);
$existing->execute();
$exists = $existing->get_result()->fetch_assoc();

if ($exists) {
    $stmt = $conn->prepare("
        UPDATE hr_employees SET
            employment_type=?, hire_date=?, salary_grade=?, monthly_salary=?,
            position=?, department_id=?, sss_number=?, philhealth_number=?,
            pagibig_number=?, tin_number=?, emergency_contact_name=?,
            emergency_contact_phone=?, emergency_contact_relation=?, status=?
        WHERE user_id=?
    ");
    $stmt->bind_param("sssdsisssssssi",
        $employment_type, $hire_date, $salary_grade, $monthly_salary,
        $position, $department_id, $sss_number, $philhealth_number,
        $pagibig_number, $tin_number, $emergency_contact_name,
        $emergency_contact_phone, $emergency_contact_relation, $hr_status,
        $user_id
    );
} else {
    $stmt = $conn->prepare("
        INSERT INTO hr_employees
            (user_id, employment_type, hire_date, salary_grade, monthly_salary,
             position, department_id, sss_number, philhealth_number,
             pagibig_number, tin_number, emergency_contact_name,
             emergency_contact_phone, emergency_contact_relation, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param("isssdsissssssss",
        $user_id, $employment_type, $hire_date, $salary_grade, $monthly_salary,
        $position, $department_id, $sss_number, $philhealth_number,
        $pagibig_number, $tin_number, $emergency_contact_name,
        $emergency_contact_phone, $emergency_contact_relation, $hr_status
    );
}

if ($stmt->execute()) {
    logAction($conn, $hr_id, "Saved HR profile for user ID: $user_id", 'hr_employees', $user_id);
    echo json_encode(['success' => true, 'message' => 'HR profile saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save HR profile: ' . $conn->error]);
}

$conn->close();
?>
