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

$applicant_id = intval($input['applicant_id'] ?? 0);
$name         = sanitizeInput($input['name']     ?? '');
$email        = trim($input['email'] ?? '');
$password     = !empty($input['password']) ? $input['password'] : '12345678';
$role         = $input['role']                   ?? 'teacher';
$department_id= !empty($input['department_id'])  ? intval($input['department_id']) : null;

if (!$applicant_id || !$name || !$email) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Check if email already exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists in the system']);
    exit;
}

// Get applicant info for HR profile pre-fill
$appStmt = $conn->prepare("SELECT a.*, j.title AS job_title FROM hr_applicants a JOIN hr_job_postings j ON a.job_id = j.id WHERE a.id = ?");
$appStmt->bind_param("i", $applicant_id);
$appStmt->execute();
$applicant = $appStmt->get_result()->fetch_assoc();
if (!$applicant) {
    echo json_encode(['success' => false, 'message' => 'Applicant not found']);
    exit;
}

// Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Create user account
$userStmt = $conn->prepare("
    INSERT INTO users (name, email, password, role, status, department)
    VALUES (?, ?, ?, ?, 'active', ?)
");
$dept_name = '';
if ($department_id) {
    $dRes = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
    $dRes->bind_param("i", $department_id);
    $dRes->execute();
    $dRow = $dRes->get_result()->fetch_assoc();
    $dept_name = $dRow['department_name'] ?? '';
}
$userStmt->bind_param("sssss", $name, $email, $hashed, $role, $dept_name);

if (!$userStmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to create user: ' . $conn->error]);
    exit;
}
$new_user_id = $conn->insert_id;

// Create HR employee profile
$hrStmt = $conn->prepare("
    INSERT INTO hr_employees (user_id, position, department_id, employment_type, status, hire_date)
    VALUES (?, ?, ?, 'probationary', 'active', CURDATE())
");
$position = $applicant['job_title'] ?? '';
$hrStmt->bind_param("isi", $new_user_id, $position, $department_id);
$hrStmt->execute();

// Mark applicant as onboarded
$onbStmt = $conn->prepare("UPDATE hr_applicants SET onboarded=1, onboard_user_id=? WHERE id=?");
$onbStmt->bind_param("ii", $new_user_id, $applicant_id);
$onbStmt->execute();

logAction($conn, $hr_id, "Onboarded applicant ID $applicant_id as new user ID $new_user_id ($name, role: $role)", 'users', $new_user_id);

// ── Send welcome / hired email with account credentials ──────────────────
if ($email) {
    require_once dirname(__DIR__, 2) . '/php/smtp_mailer.php';
    $mailer    = new SMTPMailer();
    $job_title = $applicant['job_title'] ?? 'Staff';
    $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'] ?? 'scc-school.local';
    $login_url = $protocol . '://' . $host . '/school-mgmt-clean/login.html';

    $subject = "Welcome to " . $school_name . " – Your Account is Ready!";
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
      <div style='background:#10b981;padding:24px 32px;'>
        <h1 style='color:white;margin:0;font-size:1.4rem;'>&#127881; Congratulations &mdash; You're Hired!</h1>
      </div>
      <div style='padding:28px 32px;color:#374151;'>
        <p>Dear <strong>{$name}</strong>,</p>
        <p>We are thrilled to welcome you to <strong>" . $school_name . "</strong>! Your application for <strong>{$job_title}</strong> has been approved and you have been officially onboarded as part of our team.</p>

        <div style='background:#f0fdf4;border-left:4px solid #10b981;padding:16px 20px;margin:20px 0;border-radius:4px;'>
          <p style='margin:0 0 8px;font-weight:700;color:#166534;'>&#128272; Your System Login Credentials</p>
          <p style='margin:4px 0;'><strong>Portal:</strong> <a href='{$login_url}' style='color:#10b981;'>{$login_url}</a></p>
          <p style='margin:4px 0;'><strong>Email:</strong> {$email}</p>
          <p style='margin:4px 0;'><strong>Default Password:</strong> <code style='background:#dcfce7;padding:2px 8px;border-radius:4px;font-size:1rem;letter-spacing:1px;'>{$password}</code></p>
        </div>

        <p style='background:#fef9c3;border:1px solid #fde68a;padding:10px 14px;border-radius:4px;font-size:0.88rem;'>
          &#9888;&#65039; <strong>Security Notice:</strong> Please log in immediately and change your password to something secure. Do not share your credentials with anyone.
        </p>

        <p>Our HR team will be in touch with you regarding your onboarding schedule, orientation, and other important details. If you have any questions, please don't hesitate to reach out.</p>
        <p>We are so excited to have you on board. Welcome to the SCC family!</p>
        <p style='margin-top:32px;'>Warm regards,<br><strong>Human Resources Department</strong><br>" . $school_name . "</p>
      </div>
      <div style='background:#f9fafb;padding:14px 32px;text-align:center;font-size:0.78rem;color:#9ca3af;'>
        This is an automated message from the SCC HR System. Please do not reply directly to this email.
      </div>
    </div>";

    $mailer->send($email, $name, $subject, $body);
}
// ── End welcome email ─────────────────────────────────────────────────────

echo json_encode([
    'success' => true,
    'message' => "Account created for $name. A welcome email with login credentials has been sent to $email.",
    'user_id' => $new_user_id
]);
$conn->close();
?>
