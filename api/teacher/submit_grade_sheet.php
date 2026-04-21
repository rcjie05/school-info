<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
/**
 * Teacher: Submit grade sheet to registrar
 * POST (multipart): subject_id, section_id, semester, school_year, teacher_note, grade_file (xlsx)
 * GET  ?subject_id=&section_id=  → returns current submission status
 */
ob_start();
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
header('Cache-Control: no-store');

if (!isLoggedIn() || !hasRole('teacher')) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$teacher_id = (int)$_SESSION['user_id'];

// ── Ensure table + columns exist ──────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `grade_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `teacher_note` text DEFAULT NULL,
  `registrar_note` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_data` longblob DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1");

foreach (['file_path VARCHAR(500) DEFAULT NULL', 'file_data LONGBLOB DEFAULT NULL', 'file_name VARCHAR(255) DEFAULT NULL'] as $colDef) {
    $colName = explode(' ', $colDef)[0];
    $chk = $conn->query("SHOW COLUMNS FROM `grade_submissions` LIKE '{$colName}'");
    if ($chk && $chk->num_rows === 0) {
        $conn->query("ALTER TABLE `grade_submissions` ADD COLUMN {$colDef}");
    }
}

// ── GET: check status ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $subject_id = (int)($_GET['subject_id'] ?? 0);
    $section_id = (int)($_GET['section_id'] ?? 0);

    $stmt = $conn->prepare("
        SELECT gs.id, gs.status, gs.teacher_note, gs.registrar_note, gs.file_path, gs.file_name,
               gs.submitted_at, gs.reviewed_at,
               u.name AS reviewed_by_name
        FROM grade_submissions gs
        LEFT JOIN users u ON u.id = gs.reviewed_by
        WHERE gs.teacher_id = ? AND gs.subject_id = ? AND gs.section_id = ?
        ORDER BY gs.submitted_at DESC LIMIT 1
    ");
    $stmt->bind_param("iii", $teacher_id, $subject_id, $section_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    ob_end_clean();
    echo json_encode(['success' => true, 'submission' => $row]);
    exit();
}

// ── POST: submit ──────────────────────────────────────────────────────
$subject_id  = (int)($_POST['subject_id']  ?? 0);
$section_id  = (int)($_POST['section_id']  ?? 0);
$semester    = trim($_POST['semester']     ?? '');
$school_year = trim($_POST['school_year']  ?? '');
$note        = trim($_POST['teacher_note'] ?? '');

if (!$subject_id || !$section_id) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Missing subject or section']);
    exit();
}

// Verify teacher is assigned to this class
$assigned = false;
$r = $conn->query("SHOW TABLES LIKE 'section_subjects'");
if ($r && $r->num_rows > 0) {
    $stmt = $conn->prepare("SELECT id FROM section_subjects WHERE subject_id=? AND section_id=? AND teacher_id=? LIMIT 1");
    $stmt->bind_param("iii", $subject_id, $section_id, $teacher_id);
    $stmt->execute();
    $assigned = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if (!$assigned) {
    $stmt = $conn->prepare("SELECT id FROM study_loads WHERE subject_id=? AND section_id=? AND teacher_id=? LIMIT 1");
    $stmt->bind_param("iii", $subject_id, $section_id, $teacher_id);
    $stmt->execute();
    $assigned = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if (!$assigned) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not authorised for this class']);
    exit();
}

// Block if pending submission already exists
$stmt = $conn->prepare("SELECT id FROM grade_submissions WHERE teacher_id=? AND subject_id=? AND section_id=? AND status='pending' LIMIT 1");
$stmt->bind_param("iii", $teacher_id, $subject_id, $section_id);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close(); $conn->close(); ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'A submission is already pending review.']);
    exit();
}
$stmt->close();

// Check for previously approved submission
$stmt = $conn->prepare("SELECT id FROM grade_submissions WHERE teacher_id=? AND subject_id=? AND section_id=? AND status='approved' ORDER BY submitted_at DESC LIMIT 1");
$stmt->bind_param("iii", $teacher_id, $subject_id, $section_id);
$stmt->execute();
$prevApproved   = $stmt->get_result()->fetch_assoc();
$stmt->close();
$isResubmission = (bool)$prevApproved;

// ── Handle file upload ────────────────────────────────────────────────
if (!isset($_FILES['grade_file']) || $_FILES['grade_file']['error'] !== UPLOAD_ERR_OK) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error: ' . ($_FILES['grade_file']['error'] ?? 'no file')]);
    exit();
}

$file    = $_FILES['grade_file'];
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'File too large (max 10MB)']);
    exit();
}

// Read raw bytes
$file_data = file_get_contents($file['tmp_name']);
$file_name = basename($file['name']);

if ($file_data === false || strlen($file_data) === 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Could not read uploaded file']);
    exit();
}

// Save to disk (primary storage — most reliable)
$projectRoot = dirname(__FILE__, 4);
$uploadDir   = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'grade_sheets' . DIRECTORY_SEPARATOR;
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
$diskFilename = 'GradeSheet_' . $teacher_id . '_' . $subject_id . '_' . $section_id . '_' . time() . '.xlsx';
$savePath     = $uploadDir . $diskFilename;
$file_path    = null;

// Use copy from tmp instead of move_uploaded_file for reliability
if (@copy($file['tmp_name'], $savePath)) {
    $file_path = $savePath;
} elseif (@move_uploaded_file($file['tmp_name'], $savePath)) {
    $file_path = $savePath;
}

// Insert submission — use simple string binding, store blob via UPDATE after insert
$stmt = $conn->prepare("
    INSERT INTO grade_submissions (teacher_id, subject_id, section_id, semester, school_year, teacher_note, file_path, file_name)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("iiisssss", $teacher_id, $subject_id, $section_id, $semester, $school_year, $note, $file_path, $file_name);
$ok     = $stmt->execute();
$new_id = $conn->insert_id;
$stmt->close();

if (!$ok || !$new_id) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Failed to save submission: ' . $conn->error]);
    exit();
}

// Store blob via a separate UPDATE (avoids bind_param blob complexity entirely)
$blobStmt = $conn->prepare("UPDATE grade_submissions SET file_data = ? WHERE id = ?");
$null = null;
$blobStmt->bind_param("bi", $null, $new_id);
$blobStmt->send_long_data(0, $file_data);
$blobStmt->execute();
$blobStmt->close();

// Notify registrar users
$regs = $conn->query("SELECT id FROM users WHERE role='registrar' AND status NOT IN ('inactive','rejected')");
if ($regs) {
    $info = $conn->query("
        SELECT u.name AS tname, sub.subject_name, sub.subject_code, sec.section_name
        FROM users u
        JOIN subjects sub ON sub.id = $subject_id
        JOIN sections sec ON sec.id = $section_id
        WHERE u.id = $teacher_id LIMIT 1
    ")->fetch_assoc();

    $title = "📋 Grade Sheet Submitted";
    $msg   = ($info['tname'] ?? 'A teacher') . " submitted grades for " .
             ($info['subject_code'] ?? '') . " " . ($info['subject_name'] ?? '') .
             " — Section " . ($info['section_name'] ?? '') . ".";

    while ($reg = $regs->fetch_assoc()) {
        createNotification($conn, $reg['id'], $title, $msg);
    }
}

logAction($conn, $teacher_id, "Submitted grade sheet for subject_id=$subject_id section_id=$section_id", 'grade_submissions', $new_id);
$conn->close();
ob_end_clean();
echo json_encode([
    'success'         => true,
    'message'         => ($isResubmission
                            ? 'Grade sheet resubmitted! The registrar will review your updated grades.'
                            : 'Grade sheet submitted to registrar! Excel file attached.'),
    'submission_id'   => $new_id,
    'file_attached'   => true,
    'is_resubmission' => $isResubmission,
]);
