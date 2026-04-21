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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    $semester    = $conn->real_escape_string($_GET['semester']    ?? '');
    $school_year = $conn->real_escape_string($_GET['school_year'] ?? '');

    $sql = "SELECT
                g.id,
                u.student_id   AS student_number,
                u.name         AS student_name,
                u.course,
                u.year_level,
                sub.subject_code,
                sub.subject_name,
                sub.units,
                g.midterm_grade,
                g.final_grade,
                g.remarks,
                g.semester,
                g.school_year
            FROM grades g
            JOIN users u    ON u.id  = g.student_id AND u.role = 'student'
            JOIN subjects sub ON sub.id = g.subject_id
            WHERE 1=1";

    if ($semester)    $sql .= " AND g.semester    = '$semester'";
    if ($school_year) $sql .= " AND g.school_year = '$school_year'";

    $sql .= " ORDER BY u.student_id, sub.subject_code";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $grades = [];
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row;
    }

    $conn->close();
    echo json_encode(['success' => true, 'grades' => $grades]);

} catch (Exception $e) {
    if (isset($conn)) $conn->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching grades: ' . $e->getMessage()]);
}
?>
