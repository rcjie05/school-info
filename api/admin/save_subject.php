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
requireRole('admin');

$conn = getDBConnection();
$admin_id = $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);

$subject_id = isset($input['subject_id']) ? intval($input['subject_id']) : null;
$subject_code = isset($input['subject_code']) ? sanitizeInput($input['subject_code']) : null;
$subject_name = isset($input['subject_name']) ? sanitizeInput($input['subject_name']) : null;
$description = isset($input['description']) ? sanitizeInput($input['description']) : null;
$units = isset($input['units']) ? intval($input['units']) : null;
$course = isset($input['course']) ? sanitizeInput($input['course']) : null;
$year_level = isset($input['year_level']) ? sanitizeInput($input['year_level']) : null;
$prerequisites = isset($input['prerequisites']) ? sanitizeInput($input['prerequisites']) : null;
$status = isset($input['status']) ? sanitizeInput($input['status']) : 'active';
$subject_type = isset($input['subject_type']) && in_array($input['subject_type'], ['major', 'minor']) ? $input['subject_type'] : 'major';

if (!$subject_code || !$subject_name || !$units) {
    echo json_encode(['success' => false, 'message' => 'Subject code, name, and units are required']);
    exit();
}

if ($units < 1 || $units > 6) {
    echo json_encode(['success' => false, 'message' => 'Units must be between 1 and 6']);
    exit();
}

if ($subject_id) {
    // Update existing subject
    // Check if code is being changed and if new code already exists
    $checkStmt = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ? AND id != ?");
    $checkStmt->bind_param("si", $subject_code, $subject_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Subject code already exists']);
        exit();
    }
    
    $stmt = $conn->prepare("
        UPDATE subjects 
        SET subject_code = ?, subject_name = ?, description = ?, units = ?, 
            subject_type = ?, course = ?, year_level = ?, prerequisites = ?, status = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssisssssi", $subject_code, $subject_name, $description, $units, 
                      $subject_type, $course, $year_level, $prerequisites, $status, $subject_id);
    
    if ($stmt->execute()) {
        logAction($conn, $admin_id, "Updated subject: $subject_code - $subject_name", 'subjects', $subject_id);
        echo json_encode(['success' => true, 'message' => 'Subject updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update subject: ' . $stmt->error]);
    }
} else {
    // Check if code already exists
    $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ?");
    $stmt->bind_param("s", $subject_code);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Subject code already exists']);
        exit();
    }
    
    // Add new subject
    $stmt = $conn->prepare("
        INSERT INTO subjects (subject_code, subject_name, description, units, 
                             subject_type, course, year_level, prerequisites, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssisssss", $subject_code, $subject_name, $description, $units, 
                      $subject_type, $course, $year_level, $prerequisites, $status);
    
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        logAction($conn, $admin_id, "Added new subject: $subject_code - $subject_name", 'subjects', $new_id);
        echo json_encode(['success' => true, 'message' => 'Subject added successfully', 'subject_id' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add subject: ' . $stmt->error]);
    }
}

$conn->close();
?>