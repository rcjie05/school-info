<?php
require_once '../../php/config.php';
requireRoleApi('registrar');

$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Student account status counts
$res = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
    FROM users WHERE role = 'student'");
$stats = $res->fetch_assoc();

// By course
$res2 = $conn->query("SELECT
    COALESCE(course, 'Not Set') as course,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM users WHERE role = 'student'
    GROUP BY course ORDER BY total DESC");
$by_course = [];
while ($row = $res2->fetch_assoc()) $by_course[] = $row;

// Add/Drop requests summary
$res3 = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN request_type = 'add' THEN 1 ELSE 0 END) as add_requests,
    SUM(CASE WHEN request_type = 'drop' THEN 1 ELSE 0 END) as drop_requests
    FROM add_drop_requests");
$add_drop = $res3->fetch_assoc();

// Monthly student registrations (last 6 months)
$res4 = $conn->query("SELECT
    DATE_FORMAT(created_at, '%b %Y') as month,
    COUNT(*) as total
    FROM users WHERE role = 'student'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC");
$monthly = [];
while ($row = $res4->fetch_assoc()) $monthly[] = $row;

$conn->close();
echo json_encode(['success' => true, 'stats' => $stats, 'by_course' => $by_course, 'add_drop' => $add_drop, 'monthly' => $monthly]);
