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

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get pending applications count

// Get current registrar info
$reg_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, avatar_url FROM users WHERE id = ?");
$stmt->bind_param("i", $reg_id);
$stmt->execute();
$reg_user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND status = 'pending'");
$stmt->execute();
$pending_count = $stmt->get_result()->fetch_assoc()['count'];

// Get approved today count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM users 
    WHERE role = 'student' 
    AND status IN ('approved', 'active')
    AND DATE(updated_at) = CURDATE()
");
$stmt->execute();
$approved_today = $stmt->get_result()->fetch_assoc()['count'];

// Get total students
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stmt->execute();
$total_students = $stmt->get_result()->fetch_assoc()['count'];

// Get enrolled count (students with finalized loads)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT student_id) as count 
    FROM study_loads 
    WHERE status = 'finalized'
");
$stmt->execute();
$enrolled_count = $stmt->get_result()->fetch_assoc()['count'];

// Get pending applications
$stmt = $conn->prepare("
    SELECT id, name, email, student_id, course, year_level, status, avatar_url
    FROM users
    WHERE role = 'student' AND status = 'pending'
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute();
$applications_result = $stmt->get_result();

$applications = [];
while ($row = $applications_result->fetch_assoc()) {
    $applications[] = $row;
}

// Get recent activity from audit logs
$stmt = $conn->prepare("
    SELECT action, DATE_FORMAT(created_at, '%M %d, %Y %h:%i %p') as date
    FROM audit_logs
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$user_id = $_SESSION['user_id'];
$stmt->bind_param("i", $user_id);
$stmt->execute();
$activity_result = $stmt->get_result();

$recent_activity = [];
while ($row = $activity_result->fetch_assoc()) {
    $recent_activity[] = $row;
}

echo json_encode([
    'success' => true,
    'stats' => [
        'pending_count' => $pending_count,
        'approved_today' => $approved_today,
        'total_students' => $total_students,
        'enrolled_count' => $enrolled_count
    ],
    'applications' => $applications,
    'recent_activity' => $recent_activity,
    'user' => [
        'name' => $reg_user['name'],
        'avatar_url' => getAvatarUrl($reg_user['avatar_url'] ?? null)
    ]
]);

$conn->close();
?>
