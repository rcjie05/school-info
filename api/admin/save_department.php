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
requireRoleApi('admin');

$conn = getDBConnection();
$admin_id = $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);

$dept_id = isset($input['department_id']) ? intval($input['department_id']) : null;
$dept_name = isset($input['department_name']) ? sanitizeInput($input['department_name']) : null;
$dept_code = isset($input['department_code']) ? sanitizeInput($input['department_code']) : null;
$head_of_dept = isset($input['head_of_department']) ? sanitizeInput($input['head_of_department']) : null;
$office_location = isset($input['office_location']) ? sanitizeInput($input['office_location']) : null;
$contact_email = isset($input['contact_email']) ? sanitizeInput($input['contact_email']) : null;
$contact_phone = isset($input['contact_phone']) ? sanitizeInput($input['contact_phone']) : null;

if (!$dept_name || !$dept_code) {
    echo json_encode(['success' => false, 'message' => 'Department name and code are required']);
    exit();
}

if ($dept_id) {
    // Update
    $stmt = $conn->prepare("
        UPDATE departments 
        SET department_name = ?, department_code = ?, head_of_department = ?, 
            office_location = ?, contact_email = ?, contact_phone = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssssssi", $dept_name, $dept_code, $head_of_dept, $office_location, $contact_email, $contact_phone, $dept_id);
    
    if ($stmt->execute()) {
        logAction($conn, $admin_id, "Updated department: $dept_name", 'departments', $dept_id);
        echo json_encode(['success' => true, 'message' => 'Department updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update department: ' . $stmt->error]);
    }
} else {
    // Check if code exists
    $stmt = $conn->prepare("SELECT id FROM departments WHERE department_code = ?");
    $stmt->bind_param("s", $dept_code);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Department code already exists']);
        exit();
    }
    
    // Add new
    $stmt = $conn->prepare("
        INSERT INTO departments (department_name, department_code, head_of_department, 
                                 office_location, contact_email, contact_phone)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssss", $dept_name, $dept_code, $head_of_dept, $office_location, $contact_email, $contact_phone);
    
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        logAction($conn, $admin_id, "Added new department: $dept_name", 'departments', $new_id);
        echo json_encode(['success' => true, 'message' => 'Department added successfully', 'department_id' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add department: ' . $stmt->error]);
    }
}

$conn->close();
?>
