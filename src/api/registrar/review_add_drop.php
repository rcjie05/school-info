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

$conn      = getDBConnection();
$user_id   = $_SESSION['user_id'];
$input     = json_decode(file_get_contents('php://input'), true);

$request_id = intval($input['request_id'] ?? 0);
$action     = $input['action'] ?? '';   // 'approve' or 'reject'
$note       = trim($input['note'] ?? '');

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

// Get the request
$stmt = $conn->prepare("SELECT * FROM add_drop_requests WHERE id=? AND status='pending'");
$stmt->bind_param('i', $request_id);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();

if (!$req) {
    echo json_encode(['success' => false, 'message' => 'Request not found or already reviewed.']);
    exit();
}

$conn->begin_transaction();
try {
    $new_status = $action === 'approve' ? 'approved' : 'rejected';

    // Update the request
    $upd = $conn->prepare("UPDATE add_drop_requests SET status=?, reviewed_by=?, reviewed_at=NOW(), registrar_note=? WHERE id=?");
    $upd->bind_param('sisi', $new_status, $user_id, $note, $request_id);
    $upd->execute();

    if ($action === 'approve') {
        if ($req['request_type'] === 'add') {
            // Get teacher from section_subjects
            $student_section = $conn->prepare("SELECT section_id FROM users WHERE id=?");
            $student_section->bind_param('i', $req['student_id']);
            $student_section->execute();
            $sec_row    = $student_section->get_result()->fetch_assoc();
            $section_id = $sec_row['section_id'] ?? null;

            $teacher_id = null;
            if ($section_id) {
                $t_stmt = $conn->prepare("SELECT teacher_id FROM section_subjects WHERE section_id=? AND subject_id=?");
                $t_stmt->bind_param('ii', $section_id, $req['subject_id']);
                $t_stmt->execute();
                $t_row      = $t_stmt->get_result()->fetch_assoc();
                $teacher_id = $t_row['teacher_id'] ?? null;
            }

            // Check not already in load
            $exists = $conn->prepare("SELECT id FROM study_loads WHERE student_id=? AND subject_id=?");
            $exists->bind_param('ii', $req['student_id'], $req['subject_id']);
            $exists->execute();
            if ($exists->get_result()->num_rows === 0) {
                $ins = $conn->prepare("INSERT INTO study_loads (student_id, subject_id, teacher_id, section_id, status) VALUES (?,?,?,?,'finalized')");
                $ins->bind_param('iiii', $req['student_id'], $req['subject_id'], $teacher_id, $section_id);
                $ins->execute();
            }

        } elseif ($req['request_type'] === 'drop') {
            $del = $conn->prepare("DELETE FROM study_loads WHERE student_id=? AND subject_id=?");
            $del->bind_param('ii', $req['student_id'], $req['subject_id']);
            $del->execute();
        }
    }

    // Notify student
    $type_label   = $req['request_type'] === 'add' ? 'Add' : 'Drop';
    $action_label = $action === 'approve' ? 'approved' : 'rejected';
    $notif_msg    = "Your $type_label request has been $action_label." . ($note ? " Note: $note" : '');
    createNotification($conn, $req['student_id'], "Subject Request $action_label", $notif_msg);

    logAction($conn, $user_id, "Reviewed add/drop request #$request_id: $action", 'add_drop_requests', $request_id);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Request has been $action_label."]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
