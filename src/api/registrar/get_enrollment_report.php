<?php
require_once '../../php/config.php';
requireRoleApi('registrar');

$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Total students + status counts
$res = $conn->query("SELECT
    COUNT(*) as total_students,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_students,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_students,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_students
    FROM users WHERE role = 'student'");
$stats = $res->fetch_assoc();

// Recent enrollments (last 30 days)
$res2 = $conn->query("SELECT COUNT(*) as recent
    FROM users WHERE role = 'student'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stats['recent_enrollments'] = $res2->fetch_assoc()['recent'];

// By course
$res3 = $conn->query("SELECT
    COALESCE(course, 'Not Set') as course,
    COUNT(*) as total,
    SUM(CASE WHEN year_level = '1st Year' THEN 1 ELSE 0 END) as year_1,
    SUM(CASE WHEN year_level = '2nd Year' THEN 1 ELSE 0 END) as year_2,
    SUM(CASE WHEN year_level = '3rd Year' THEN 1 ELSE 0 END) as year_3,
    SUM(CASE WHEN year_level = '4th Year' THEN 1 ELSE 0 END) as year_4
    FROM users WHERE role = 'student'
    GROUP BY course ORDER BY total DESC");
$by_course = [];
while ($row = $res3->fetch_assoc()) $by_course[] = $row;

// By year level
$res4 = $conn->query("SELECT
    COALESCE(year_level, 'Not Set') as year_level,
    COUNT(*) as total
    FROM users WHERE role = 'student'
    GROUP BY year_level ORDER BY year_level ASC");
$by_year = [];
while ($row = $res4->fetch_assoc()) $by_year[] = $row;

$conn->close();
echo json_encode(['success' => true, 'stats' => $stats, 'by_course' => $by_course, 'by_year' => $by_year]);
