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
$conn  = getDBConnection();
$hr_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$user_id              = intval($input['user_id'] ?? 0);
$payroll_month        = $input['payroll_month'] ?? null;
$basic_salary         = floatval($input['basic_salary']         ?? 0);
$days_worked          = floatval($input['days_worked']           ?? 0);
$days_absent          = floatval($input['days_absent']           ?? 0);
$overtime_hours       = floatval($input['overtime_hours']        ?? 0);
$overtime_pay         = floatval($input['overtime_pay']          ?? 0);
$allowances           = floatval($input['allowances']            ?? 0);
$gross_pay            = floatval($input['gross_pay']             ?? 0);
$sss_deduction        = floatval($input['sss_deduction']         ?? 0);
$philhealth_deduction = floatval($input['philhealth_deduction']  ?? 0);
$pagibig_deduction    = floatval($input['pagibig_deduction']     ?? 0);
$tax_deduction        = floatval($input['tax_deduction']         ?? 0);
$other_deductions     = floatval($input['other_deductions']      ?? 0);
$total_deductions     = floatval($input['total_deductions']      ?? 0);
$net_pay              = floatval($input['net_pay']               ?? 0);
$status               = $input['status']  ?? 'draft';
$remarks              = sanitizeInput($input['remarks'] ?? '');

if (!$user_id || !$payroll_month) {
    echo json_encode(['success' => false, 'message' => 'User ID and payroll month are required']);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO hr_payroll
        (user_id, payroll_month, basic_salary, days_worked, days_absent, overtime_hours,
         overtime_pay, allowances, gross_pay, sss_deduction, philhealth_deduction,
         pagibig_deduction, tax_deduction, other_deductions, total_deductions, net_pay,
         status, remarks, processed_by)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
        basic_salary         = VALUES(basic_salary),
        days_worked          = VALUES(days_worked),
        days_absent          = VALUES(days_absent),
        overtime_hours       = VALUES(overtime_hours),
        overtime_pay         = VALUES(overtime_pay),
        allowances           = VALUES(allowances),
        gross_pay            = VALUES(gross_pay),
        sss_deduction        = VALUES(sss_deduction),
        philhealth_deduction = VALUES(philhealth_deduction),
        pagibig_deduction    = VALUES(pagibig_deduction),
        tax_deduction        = VALUES(tax_deduction),
        other_deductions     = VALUES(other_deductions),
        total_deductions     = VALUES(total_deductions),
        net_pay              = VALUES(net_pay),
        status               = VALUES(status),
        remarks              = VALUES(remarks),
        processed_by         = VALUES(processed_by),
        updated_at           = CURRENT_TIMESTAMP
");

$stmt->bind_param(
    "isddddddddddddddssi",
    $user_id, $payroll_month, $basic_salary, $days_worked, $days_absent,
    $overtime_hours, $overtime_pay, $allowances, $gross_pay,
    $sss_deduction, $philhealth_deduction, $pagibig_deduction,
    $tax_deduction, $other_deductions, $total_deductions, $net_pay,
    $status, $remarks, $hr_id
);

if ($stmt->execute()) {
    logAction($conn, $hr_id, "Saved payroll for user ID: $user_id, month: $payroll_month", 'hr_payroll', $user_id);
    echo json_encode(['success' => true, 'message' => 'Payslip saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save: ' . $conn->error]);
}

$conn->close();
?>
