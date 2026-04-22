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

$conn   = getDBConnection();
$hr_id  = $_SESSION['user_id'];
$input  = json_decode(file_get_contents('php://input'), true);

$id              = !empty($input['id'])           ? intval($input['id'])           : null;
$title           = sanitizeInput($input['title']           ?? '');
$department_id   = !empty($input['department_id'])? intval($input['department_id']): null;
$employment_type = $input['employment_type']       ?? 'full_time';
$slots           = intval($input['slots']          ?? 1);
$deadline        = !empty($input['deadline'])      ? $input['deadline']             : null;
$description     = sanitizeInput($input['description']     ?? '');
$requirements    = sanitizeInput($input['requirements']    ?? '');
$status          = $input['status']               ?? 'open';

if (!$title) {
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit;
}

if ($id) {
    $stmt = $conn->prepare("
        UPDATE hr_job_postings SET title=?, department_id=?, employment_type=?, slots=?,
        deadline=?, description=?, requirements=?, status=?
        WHERE id=?
    ");
    $stmt->bind_param("sisissssi", $title, $department_id, $employment_type, $slots,
        $deadline, $description, $requirements, $status, $id);
    $action = "Updated job posting: $title (ID: $id)";
} else {
    $stmt = $conn->prepare("
        INSERT INTO hr_job_postings (title, department_id, employment_type, slots, deadline, description, requirements, status, posted_by)
        VALUES (?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param("sisissssi", $title, $department_id, $employment_type, $slots,
        $deadline, $description, $requirements, $status, $hr_id);
    $action = "Created job posting: $title";
}

if ($stmt->execute()) {
    $newId = $id ?: $conn->insert_id;
    logAction($conn, $hr_id, $action, 'hr_job_postings', $newId);
    echo json_encode(['success' => true, 'message' => 'Job saved', 'id' => $newId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed: ' . $conn->error]);
}
$conn->close();
?>
