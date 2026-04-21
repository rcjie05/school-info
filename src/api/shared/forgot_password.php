<?php
/**
 * Forgot Password — OTP via Email (Gmail SMTP)
 * Actions: send_otp | verify_otp | reset_pass
 */

ini_set('display_errors', 0);
error_reporting(0);
ob_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────

// ── Respond helper ────────────────────────────────────────────────────────────
function respond($success, $message, $extra = []) {
    ob_clean();
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

// ── Auto-create OTP table if missing ─────────────────────────────────────────
function ensureOtpTable($conn) {
    $conn->query("
        CREATE TABLE IF NOT EXISTS `password_reset_otps` (
          `id`         int(11)      NOT NULL AUTO_INCREMENT,
          `user_id`    int(11)      NOT NULL,
          `otp_hash`   varchar(255) NOT NULL,
          `expires_at` datetime     NOT NULL,
          `used`       tinyint(1)   NOT NULL DEFAULT 0,
          `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_user` (`user_id`),
          KEY `idx_expires` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
}

// ── Safe audit log ────────────────────────────────────────────────────────────
function safeLog($conn, $user_id, $action) {
    try {
        $stmt = $conn->prepare(
            "INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, 'users', ?)"
        );
        if ($stmt) {
            $stmt->bind_param('isi', $user_id, $action, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {}
}

// ── Send OTP via Gmail SMTP ───────────────────────────────────────────────────
function sendOtpEmail($toEmail, $toName, $otp) {
    $subject = "Your Password Reset OTP - " . $school_name . "";
    $name    = htmlspecialchars($toName, ENT_QUOTES);
    $year    = date('Y');

    $body = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f5f5f5;padding:30px 0;'>
<tr><td align='center'>
<table width='500' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.12);'>

  <tr><td style='background:#8b0000;padding:30px 32px;text-align:center;border-bottom:4px solid #c8a951;'>
    <p style='margin:0;font-size:22px;font-weight:900;color:#fff;letter-spacing:.5px;'>" . $school_name . "</p>
    <p style='margin:6px 0 0;font-size:13px;color:rgba(255,255,255,.75);'>My School</p>
  </td></tr>

  <tr><td style='padding:38px 40px;'>
    <p style='margin:0 0 10px;font-size:16px;color:#222;'>Hello, <strong>{$name}</strong> 👋</p>
    <p style='margin:0 0 28px;font-size:14px;color:#555;line-height:1.7;'>
      We received a request to reset your password.<br>
      Use the <strong>6-digit OTP</strong> below to continue. It expires in <strong>10 minutes</strong>.
    </p>

    <div style='background:#fdf8f0;border:2px solid #c8a951;border-radius:12px;padding:28px;text-align:center;margin-bottom:28px;'>
      <p style='margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#8b0000;'>Your OTP Code</p>
      <p style='margin:0;font-size:44px;font-weight:900;letter-spacing:12px;color:#8b0000;font-family:Courier New,monospace;'>{$otp}</p>
    </div>

    <p style='margin:0 0 6px;font-size:13px;color:#999;'>⚠️ Do not share this code with anyone.</p>
    <p style='margin:0;font-size:13px;color:#999;line-height:1.6;'>
      If you didn't request a password reset, please ignore this email or contact the Registrar at
      <a href='mailto:info@stcecilia.edu.ph' style='color:#8b0000;'>info@stcecilia.edu.ph</a>.
    </p>
  </td></tr>

  <tr><td style='background:#f8f8f8;padding:16px 40px;border-top:1px solid #eee;text-align:center;'>
    <p style='margin:0;font-size:11px;color:#bbb;'>&copy; {$year} " . $school_name . ", Inc. &middot; All rights reserved</p>
  </td></tr>

</table>
</td></tr>
</table>
</body></html>";

    try {
        $mailer = new SMTPMailer();
        return $mailer->send($toEmail, $toName, $subject, $body);
    } catch (Exception $e) {
        error_log('Email send failed: ' . $e->getMessage());
        return false;
    }
}

// ── Only accept POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed.');
}

$action = trim($_POST['action'] ?? '');
if (empty($action)) respond(false, 'No action specified.');

$conn = getDBConnection();
if (!$conn) respond(false, 'Database connection failed. Please contact the administrator.');

ensureOtpTable($conn);

// =============================================================================
// ACTION: send_otp
// =============================================================================
if ($action === 'send_otp') {
    $email = trim(strtolower($_POST['email'] ?? ''));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Please enter a valid email address.');
    }

    $stmt = $conn->prepare("SELECT id, name, status FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) respond(false, 'Database error. Please try again.');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $result->free();
    $stmt->close();

    if (!$user) {
        // Vague — prevent email enumeration
        respond(true, 'If that email is registered, an OTP has been sent. Check your inbox and spam folder.');
    }

    if (!in_array($user['status'], ['active', 'approved'])) {
        $msg = match($user['status']) {
            'pending'  => 'Your account is not yet activated. Please contact the Registrar.',
            'rejected' => 'Your account application was rejected. Please contact the Registrar.',
            'inactive' => 'Your account has been deactivated. Please contact the Registrar.',
            default    => 'Your account is not eligible for a password reset. Please contact the Registrar.',
        };
        respond(false, $msg);
    }

    // Rate limit: max 3 per 10 minutes
    $rl = $conn->prepare(
        "SELECT COUNT(*) AS cnt FROM password_reset_otps
         WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
    );
    $rl->bind_param('i', $user['id']);
    $rl->execute();
    $rl_result = $rl->get_result();
    $cnt       = $rl_result->fetch_assoc()['cnt'];
    $rl_result->free();
    $rl->close();

    if ($cnt >= 3) {
        respond(false, 'Too many requests. Please wait 10 minutes before trying again.');
    }

    // Generate OTP
    $otp      = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_hash = password_hash($otp, PASSWORD_DEFAULT);

    // Delete old OTPs
    $del = $conn->prepare("DELETE FROM password_reset_otps WHERE user_id = ?");
    $del->bind_param('i', $user['id']);
    $del->execute();
    $del->close();

    // Save new OTP — use MySQL NOW() + INTERVAL to avoid PHP/DB timezone mismatch
    $ins = $conn->prepare(
        "INSERT INTO password_reset_otps (user_id, otp_hash, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))"
    );
    if (!$ins) respond(false, 'Failed to create OTP. Please try again.');
    $ins->bind_param('is', $user['id'], $otp_hash);
    if (!$ins->execute()) respond(false, 'Failed to save OTP: ' . $ins->error);
    $ins->close();

    // Send email
    require_once __DIR__ . '/../../php/smtp_mailer.php';
    $sent = sendOtpEmail($email, $user['name'], $otp);

    safeLog($conn, $user['id'], 'Password reset OTP requested');
    $conn->close();

    if (!$sent) {
        // Email failed — still allow testing by returning OTP (localhost dev only)
        $is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']);
        if ($is_local) {
            respond(true, 'Email not sent (check SMTP settings in config.php). Dev OTP shown below.', ['dev_otp' => $otp]);
        }
        respond(false, 'Failed to send OTP email. Please check SMTP settings or contact the administrator.');
    }

    respond(true, 'OTP sent successfully! Check your email inbox and spam folder.');
}

// =============================================================================
// ACTION: verify_otp
// =============================================================================
if ($action === 'verify_otp') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $otp   = trim($_POST['otp'] ?? '');

    if (empty($email) || strlen($otp) !== 6) {
        respond(false, 'Please enter your email and the complete 6-digit OTP.');
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $vResult = $stmt->get_result();
    $user    = $vResult->fetch_assoc();
    $vResult->free();
    $stmt->close();

    if (!$user) respond(false, 'Invalid OTP or it has expired. Please try again.');

    $otp_stmt = $conn->prepare(
        "SELECT otp_hash FROM password_reset_otps
         WHERE user_id = ? AND expires_at > NOW() AND used = 0
         ORDER BY created_at DESC LIMIT 1"
    );
    $otp_stmt->bind_param('i', $user['id']);
    $otp_stmt->execute();
    $otp_result = $otp_stmt->get_result();
    $row        = $otp_result->fetch_assoc();
    $otp_result->free();
    $otp_stmt->close();
    $conn->close();

    if (!$row || !password_verify($otp, $row['otp_hash'])) {
        respond(false, 'Invalid OTP or it has expired. Please try again.');
    }

    respond(true, 'OTP verified successfully.');
}

// =============================================================================
// ACTION: reset_pass
// =============================================================================
if ($action === 'reset_pass') {
    $email    = trim(strtolower($_POST['email'] ?? ''));
    $otp      = trim($_POST['otp'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($otp) || empty($password) || empty($confirm)) {
        respond(false, 'All fields are required.');
    }
    if ($password !== $confirm) respond(false, 'Passwords do not match.');
    if (strlen($password) < 8)  respond(false, 'Password must be at least 8 characters.');
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        respond(false, 'Password must contain at least one letter and one number.');
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $rResult = $stmt->get_result();
    $user    = $rResult->fetch_assoc();
    $rResult->free();
    $stmt->close();

    if (!$user) respond(false, 'Invalid request. Please start over.');

    $otp_stmt = $conn->prepare(
        "SELECT id, otp_hash FROM password_reset_otps
         WHERE user_id = ? AND expires_at > NOW() AND used = 0
         ORDER BY created_at DESC LIMIT 1"
    );
    $otp_stmt->bind_param('i', $user['id']);
    $otp_stmt->execute();
    $otp_result2 = $otp_stmt->get_result();
    $row         = $otp_result2->fetch_assoc();
    $otp_result2->free();
    $otp_stmt->close();

    if (!$row || !password_verify($otp, $row['otp_hash'])) {
        respond(false, 'OTP expired or invalid. Please request a new one.');
    }

    // Reset password
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $upd->bind_param('si', $new_hash, $user['id']);
    $upd->execute();
    $upd->close();

    // Mark OTP used
    $mark = $conn->prepare("UPDATE password_reset_otps SET used = 1 WHERE id = ?");
    $mark->bind_param('i', $row['id']);
    $mark->execute();
    $mark->close();

    safeLog($conn, $user['id'], 'Password reset via OTP');
    $conn->close();

    respond(true, 'Password reset successfully! You can now log in with your new password.');
}

respond(false, 'Invalid action.');
