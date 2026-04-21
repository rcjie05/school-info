<?php
require_once '../../php/config.php';
requireRoleApi('registrar');

$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Overall grade stats
$res = $conn->query("SELECT
    COUNT(*) as total_grades,
    SUM(CASE WHEN remarks = 'Passed' THEN 1 ELSE 0 END) as passed,
    SUM(CASE WHEN remarks = 'Failed' THEN 1 ELSE 0 END) as failed,
    SUM(CASE WHEN remarks = 'Incomplete' THEN 1 ELSE 0 END) as incomplete,
    SUM(CASE WHEN remarks IS NULL OR remarks = '' THEN 1 ELSE 0 END) as pending,
    ROUND(AVG(CASE WHEN final_grade IS NOT NULL THEN final_grade ELSE midterm_grade END), 2) as average_grade
    FROM grades");
$stats = $res->fetch_assoc();

// By course
$res2 = $conn->query("SELECT
    COALESCE(u.course, 'Not Set') as course,
    COUNT(g.id) as total,
    SUM(CASE WHEN g.remarks = 'Passed' THEN 1 ELSE 0 END) as passed,
    SUM(CASE WHEN g.remarks = 'Failed' THEN 1 ELSE 0 END) as failed,
    ROUND(AVG(CASE WHEN g.final_grade IS NOT NULL THEN g.final_grade ELSE g.midterm_grade END), 2) as avg_grade
    FROM grades g
    JOIN users u ON g.student_id = u.id
    GROUP BY u.course ORDER BY total DESC");
$by_course = [];
while ($row = $res2->fetch_assoc()) $by_course[] = $row;

// By semester (last 6)
$res3 = $conn->query("SELECT
    COALESCE(semester, 'Not Set') as semester,
    school_year,
    COUNT(*) as total,
    SUM(CASE WHEN remarks = 'Passed' THEN 1 ELSE 0 END) as passed,
    SUM(CASE WHEN remarks = 'Failed' THEN 1 ELSE 0 END) as failed
    FROM grades
    GROUP BY semester, school_year
    ORDER BY school_year DESC, semester ASC
    LIMIT 6");
$by_semester = [];
while ($row = $res3->fetch_assoc()) $by_semester[] = $row;

$conn->close();
echo json_encode(['success' => true, 'stats' => $stats, 'by_course' => $by_course, 'by_semester' => $by_semester]);
