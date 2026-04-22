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

requireRole('registrar');

$input = json_decode(file_get_contents('php://input'), true);
$application_id = intval($input['application_id'] ?? 0);
$reason = sanitizeInput($input['reason'] ?? '');

if ($application_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid application ID']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Update application status
$stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ? AND role = 'student'");
$stmt->bind_param("i", $application_id);

if ($stmt->execute()) {
    // Get student details for notification + email
    $stmt2 = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt2->bind_param("i", $application_id);
    $stmt2->execute();
    $student = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    $notif_message = 'Your enrollment application has been rejected.';
    if (!empty($reason)) {
        $notif_message .= ' Reason: ' . $reason;
    }

    createNotification(
        $conn,
        $application_id,
        'Application Rejected',
        $notif_message
    );

    // ── Send rejection email ──────────────────────────────────────────
    if ($student && !empty($student['email'])) {
        require_once '../../php/smtp_mailer.php';
        $year    = date('Y');
        $name    = htmlspecialchars($student['name'], ENT_QUOTES);
        $reason_html = !empty($reason)
            ? "<div style='background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:14px 18px;margin:16px 0;font-size:13px;color:#991b1b;'><strong>Reason:</strong> " . htmlspecialchars($reason, ENT_QUOTES) . "</div>"
            : "";
        $subject = "Application Status Update — " . $school_name;
        $body    = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f5f5f5;padding:30px 0;'>
<tr><td align='center'>
<table width='500' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.12);'>
  <tr><td style='background:#8b0000;padding:28px 32px;text-align:center;border-bottom:4px solid #c8a951;'>
    <p style='margin:0;font-size:22px;font-weight:900;color:#fff;letter-spacing:.5px;'>{$school_name}</p>
    <p style='margin:6px 0 0;font-size:13px;color:rgba(255,255,255,.75);'>Enrollment Notification</p>
  </td></tr>
  <tr><td style='padding:36px 40px;'>
    <p style='margin:0 0 10px;font-size:16px;color:#222;'>Hello, <strong>{$name}</strong>,</p>
    <p style='margin:0 0 20px;font-size:14px;color:#555;line-height:1.7;'>
      We regret to inform you that your enrollment application has been <strong style='color:#dc2626;'>rejected</strong>.
    </p>
    <div style='background:#fef2f2;border:2px solid #dc2626;border-radius:12px;padding:24px;text-align:center;margin-bottom:16px;'>
      <p style='margin:0 0 6px;font-size:24px;'>❌</p>
      <p style='margin:0;font-size:16px;font-weight:700;color:#dc2626;'>Application Not Approved</p>
    </div>
    {$reason_html}
    <p style='margin:16px 0 0;font-size:13px;color:#555;line-height:1.7;'>
      If you believe this is an error or would like to appeal, please visit the Registrar's Office or contact us directly.
    </p>
  </td></tr>
  <tr><td style='background:#f8f8f8;padding:16px 40px;border-top:1px solid #eee;text-align:center;'>
    <p style='margin:0;font-size:11px;color:#bbb;'>&copy; {$year} {$school_name}. All rights reserved.</p>
  </td></tr>
</table>
</td></tr>
</table>
</body></html>";
        try {
            $mailer = new SMTPMailer();
            $mailer->send($student['email'], $student['name'], $subject, $body);
        } catch (Exception $e) {
            error_log('Rejection email failed: ' . $e->getMessage());
        }
    }

    // Log action
    logAction($conn, $_SESSION['user_id'], 'Rejected student application', 'users', $application_id);

    echo json_encode([
        'success' => true,
        'message' => 'Application rejected'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to reject application']);
}

$conn->close();
?>
