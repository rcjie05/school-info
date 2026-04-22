<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('hr');
header('Content-Type: application/json');

$conn  = getDBConnection();
$hr_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$id               = !empty($input['id'])             ? intval($input['id'])   : null;
$job_id           = intval($input['job_id']           ?? 0);
$full_name        = sanitizeInput($input['full_name'] ?? '');
$email            = sanitizeInput($input['email']     ?? '');
$phone            = sanitizeInput($input['phone']     ?? '');
$address          = sanitizeInput($input['address']   ?? '');
$resume_notes     = sanitizeInput($input['resume_notes']     ?? '');
$stage            = $input['stage']                   ?? 'applied';
$interview_date   = !empty($input['interview_date'])  ? $input['interview_date']  : null;
$interview_notes  = sanitizeInput($input['interview_notes']  ?? '');
$offer_date       = !empty($input['offer_date'])      ? $input['offer_date']      : null;
$rejection_reason = sanitizeInput($input['rejection_reason'] ?? '');

if (!$full_name || !$job_id) {
    echo json_encode(['success' => false, 'message' => 'Name and job are required']);
    exit;
}

// Get previous stage before updating (to detect stage change)
$prev_stage = null;
if ($id) {
    $prevStmt = $conn->prepare("SELECT stage FROM hr_applicants WHERE id = ?");
    $prevStmt->bind_param("i", $id);
    $prevStmt->execute();
    $prevRow = $prevStmt->get_result()->fetch_assoc();
    $prev_stage = $prevRow['stage'] ?? null;
}

if ($id) {
    $stmt = $conn->prepare("
        UPDATE hr_applicants SET job_id=?, full_name=?, email=?, phone=?, address=?,
        resume_notes=?, stage=?, interview_date=?, interview_notes=?,
        offer_date=?, rejection_reason=?
        WHERE id=?
    ");
    $stmt->bind_param("issssssssssi",
        $job_id, $full_name, $email, $phone, $address,
        $resume_notes, $stage, $interview_date, $interview_notes,
        $offer_date, $rejection_reason, $id
    );
    $action = "Updated applicant: $full_name (stage: $stage)";
} else {
    $stmt = $conn->prepare("
        INSERT INTO hr_applicants (job_id, full_name, email, phone, address, resume_notes,
        stage, interview_date, interview_notes, offer_date, rejection_reason, handled_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param("issssssssssi",
        $job_id, $full_name, $email, $phone, $address,
        $resume_notes, $stage, $interview_date, $interview_notes,
        $offer_date, $rejection_reason, $hr_id
    );
    $action = "Added applicant: $full_name for job ID $job_id";
}

if ($stmt->execute()) {
    $newId = $id ?: $conn->insert_id;
    logAction($conn, $hr_id, $action, 'hr_applicants', $newId);

    // ── Send email notification only when stage actually changes ──────────
    $stage_changed = ($id && $prev_stage !== $stage) || (!$id);

    if ($email && $stage_changed && in_array($stage, ['interview', 'job_offer', 'hired'])) {

        // Get job title
        $jobStmt = $conn->prepare("SELECT j.title FROM hr_job_postings j JOIN hr_applicants a ON a.job_id = j.id WHERE a.id = ?");
        $jobStmt->bind_param("i", $newId);
        $jobStmt->execute();
        $jobRow = $jobStmt->get_result()->fetch_assoc();
        $job_title = $jobRow['title'] ?? 'the position';

        require_once dirname(__DIR__, 2) . '/php/smtp_mailer.php';
        $mailer = new SMTPMailer();

        if ($stage === 'interview') {
            $interview_formatted = $interview_date ? date('F j, Y', strtotime($interview_date)) : 'a date to be confirmed';
            $subject = "Interview Invitation – {$job_title}";
            $body = "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
              <div style='background:#8b5cf6;padding:24px 32px;'>
                <h1 style='color:white;margin:0;font-size:1.4rem;'>Interview Invitation</h1>
              </div>
              <div style='padding:28px 32px;color:#374151;'>
                <p>Dear <strong>{$full_name}</strong>,</p>
                <p>We are pleased to inform you that you have been selected for an interview for the position of <strong>{$job_title}</strong> at <strong>" . $school_name . "</strong>.</p>
                <div style='background:#f5f3ff;border-left:4px solid #8b5cf6;padding:12px 16px;margin:20px 0;border-radius:4px;'>
                  <p style='margin:0;'><strong>Interview Date:</strong> {$interview_formatted}</p>
                  " . ($interview_notes ? "<p style='margin:8px 0 0;'><strong>Notes:</strong> " . htmlspecialchars($interview_notes) . "</p>" : "") . "
                </div>
                <p>Please confirm your attendance by replying to this email. If you have any questions, feel free to reach out to us.</p>
                <p>We look forward to meeting you!</p>
                <p style='margin-top:32px;'>Best regards,<br><strong>Human Resources Department</strong><br>" . $school_name . "</p>
              </div>
              <div style='background:#f9fafb;padding:14px 32px;text-align:center;font-size:0.78rem;color:#9ca3af;'>
                This is an automated message from the SCC HR System. Please do not reply directly to this email.
              </div>
            </div>";
            $mailer->send($email, $full_name, $subject, $body);

        } elseif ($stage === 'job_offer') {
            $offer_formatted = $offer_date ? date('F j, Y', strtotime($offer_date)) : 'soon';
            $subject = "Job Offer – {$job_title}";
            $body = "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
              <div style='background:#3b82f6;padding:24px 32px;'>
                <h1 style='color:white;margin:0;font-size:1.4rem;'>Congratulations – Job Offer!</h1>
              </div>
              <div style='padding:28px 32px;color:#374151;'>
                <p>Dear <strong>{$full_name}</strong>,</p>
                <p>Congratulations! After careful consideration, we are delighted to extend a job offer to you for the position of <strong>{$job_title}</strong> at <strong>" . $school_name . "</strong>.</p>
                <div style='background:#eff6ff;border-left:4px solid #3b82f6;padding:12px 16px;margin:20px 0;border-radius:4px;'>
                  <p style='margin:0;'><strong>Position:</strong> {$job_title}</p>
                  <p style='margin:8px 0 0;'><strong>Offer Date:</strong> {$offer_formatted}</p>
                </div>
                <p>Please review the offer details and respond at your earliest convenience. Our HR team will follow up with you regarding the next steps, including onboarding documentation.</p>
                <p>We are excited about the possibility of you joining our team!</p>
                <p style='margin-top:32px;'>Best regards,<br><strong>Human Resources Department</strong><br>" . $school_name . "</p>
              </div>
              <div style='background:#f9fafb;padding:14px 32px;text-align:center;font-size:0.78rem;color:#9ca3af;'>
                This is an automated message from the SCC HR System. Please do not reply directly to this email.
              </div>
            </div>";
            $mailer->send($email, $full_name, $subject, $body);

        } elseif ($stage === 'hired') {
            $subject = "Congratulations! You've Been Hired – {$job_title}";
            $body = "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
              <div style='background:#10b981;padding:24px 32px;'>
                <h1 style='color:white;margin:0;font-size:1.4rem;'>You've Been Hired!</h1>
              </div>
              <div style='padding:28px 32px;color:#374151;'>
                <p>Dear <strong>{$full_name}</strong>,</p>
                <p>We are thrilled to inform you that you have been officially <strong>hired</strong> for the position of <strong>{$job_title}</strong> at <strong>" . $school_name . "</strong>!</p>
                <div style='background:#f0fdf4;border-left:4px solid #10b981;padding:12px 16px;margin:20px 0;border-radius:4px;'>
                  <p style='margin:0;'><strong>Position:</strong> {$job_title}</p>
                  <p style='margin:8px 0 0;'>Our HR team will be reaching out shortly with further details regarding your onboarding schedule, orientation, and system account access.</p>
                </div>
                <p>We are truly excited to have you join the SCC family. Welcome aboard!</p>
                <p style='margin-top:32px;'>Warm regards,<br><strong>Human Resources Department</strong><br>" . $school_name . "</p>
              </div>
              <div style='background:#f9fafb;padding:14px 32px;text-align:center;font-size:0.78rem;color:#9ca3af;'>
                This is an automated message from the SCC HR System. Please do not reply directly to this email.
              </div>
            </div>";
            $mailer->send($email, $full_name, $subject, $body);
        }
    }
    // ── End email notification ────────────────────────────────────────────

    echo json_encode(['success' => true, 'message' => 'Applicant saved', 'id' => $newId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed: ' . $conn->error]);
}
$conn->close();
?>
