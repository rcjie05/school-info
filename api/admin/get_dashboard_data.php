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
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}


// Get current admin info
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, avatar_url FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get total users
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['count'];

// Get total students
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stmt->execute();
$total_students = $stmt->get_result()->fetch_assoc()['count'];

// Get total teachers
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
$stmt->execute();
$total_teachers = $stmt->get_result()->fetch_assoc()['count'];

// Get total buildings
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM buildings");
$stmt->execute();
$total_buildings = $stmt->get_result()->fetch_assoc()['count'];

// Get recent activity
$stmt = $conn->prepare("
    SELECT 
        al.action,
        u.name as user_name,
        DATE_FORMAT(al.created_at, '%M %d, %Y %h:%i %p') as date
    FROM audit_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
");
$stmt->execute();
$activity_result = $stmt->get_result();

$recent_activity = [];
while ($row = $activity_result->fetch_assoc()) {
    $recent_activity[] = $row;
}

echo json_encode([
    'success' => true,
    'stats' => [
        'total_users' => $total_users,
        'total_students' => $total_students,
        'total_teachers' => $total_teachers,
        'total_buildings' => $total_buildings
    ],
    'recent_activity' => $recent_activity,
    'user' => [
        'name' => $admin_user['name'],
        'avatar_url' => getAvatarUrl($admin_user['avatar_url'] ?? null)
    ]
]);

$conn->close();
?>
