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

$input      = json_decode(file_get_contents('php://input'), true);
$student_id = intval($input['student_id']         ?? 0);
$section_id = isset($input['section_id']) && $input['section_id'] !== null ? intval($input['section_id']) : null;
$auto_load  = (bool)($input['auto_load_subjects']  ?? true);

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid student']);
    exit();
}

$conn = getDBConnection();

// ── REMOVE section (null passed) ──────────────────────────
if ($section_id === null) {
    $conn->begin_transaction();
    try {
        // Clear study load
        $del = $conn->prepare("DELETE FROM study_loads WHERE student_id = ?");
        $del->bind_param('i', $student_id);
        $del->execute();

        // Remove section from user
        $upd = $conn->prepare("UPDATE users SET section_id = NULL WHERE id = ?");
        $upd->bind_param('i', $student_id);
        $upd->execute();

        $conn->commit();
        logAction($conn, $_SESSION['user_id'], "Removed section from student (student_id=$student_id)", 'users', $student_id);
        echo json_encode(['success' => true, 'message' => 'Section removed']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    $conn->close();
    exit();
}

// ── ASSIGN section ────────────────────────────────────────
// Validate section exists and is active
$sec_stmt = $conn->prepare("SELECT id, section_name, section_code FROM sections WHERE id = ? AND status = 'active'");
$sec_stmt->bind_param('i', $section_id);
$sec_stmt->execute();
$section = $sec_stmt->get_result()->fetch_assoc();
if (!$section) {
    echo json_encode(['success' => false, 'message' => 'Section not found or inactive']);
    exit();
}

$conn->begin_transaction();
try {
    // 1. Update user's section_id
    $upd = $conn->prepare("UPDATE users SET section_id = ? WHERE id = ?");
    $upd->bind_param('ii', $section_id, $student_id);
    $upd->execute();

    // 2. Update existing draft study_loads to link to new section
    $updLoad = $conn->prepare("UPDATE study_loads SET section_id = ? WHERE student_id = ? AND status = 'draft'");
    $updLoad->bind_param('ii', $section_id, $student_id);
    $updLoad->execute();

    $added = 0;
    // 3. Auto-load subjects from section if requested
    if ($auto_load) {
        // Get subjects already in student's load
        $existStmt = $conn->prepare("SELECT subject_id FROM study_loads WHERE student_id = ?");
        $existStmt->bind_param('i', $student_id);
        $existStmt->execute();
        $existResult = $existStmt->get_result();
        $existing = [];
        while ($r = $existResult->fetch_assoc()) $existing[] = $r['subject_id'];

        // Get all subjects assigned to the section with their teacher
        $subStmt = $conn->prepare("
            SELECT ss.subject_id, ss.teacher_id
            FROM section_subjects ss
            WHERE ss.section_id = ?
        ");
        $subStmt->bind_param('i', $section_id);
        $subStmt->execute();
        $subs = $subStmt->get_result();

        $insStmt = $conn->prepare("
            INSERT INTO study_loads (student_id, subject_id, teacher_id, section_id, status)
            VALUES (?, ?, ?, ?, 'draft')
        ");

        while ($sub = $subs->fetch_assoc()) {
            if (!in_array($sub['subject_id'], $existing)) {
                $insStmt->bind_param('iiii',
                    $student_id, $sub['subject_id'], $sub['teacher_id'], $section_id
                );
                $insStmt->execute();
                $added++;
            }
        }
    }

    $conn->commit();
    logAction($conn, $_SESSION['user_id'],
        "Assigned section {$section['section_code']} to student (student_id=$student_id, subjects_added=$added)",
        'users', $student_id
    );

    $msg = "Section '{$section['section_name']}' assigned.";
    if ($auto_load) $msg .= $added > 0 ? " $added subject(s) added to study load." : " No new subjects to add.";

    echo json_encode(['success' => true, 'message' => $msg, 'subjects_added' => $added]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed: ' . $e->getMessage()]);
}

$conn->close();
?>
