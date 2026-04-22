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

// Auto-add deleted_at columns where missing
// Safe column migration
$_col_check = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'announcements' AND COLUMN_NAME = 'deleted_at'");
if ($_col_check && $_col_check->fetch_assoc()['cnt'] == 0) {
    $conn->query("ALTER TABLE announcements ADD COLUMN deleted_at DATETIME DEFAULT NULL");
}
// Safe column migration
$_col_check = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'grade_submissions' AND COLUMN_NAME = 'deleted_at'");
if ($_col_check && $_col_check->fetch_assoc()['cnt'] == 0) {
    $conn->query("ALTER TABLE grade_submissions ADD COLUMN deleted_at DATETIME DEFAULT NULL");
}

$items = [];

// ── 1. Deleted announcements ──────────────────────────────────────────
$res = $conn->query("
    SELECT a.id, 'announcement' AS type, a.title AS name,
           CONCAT('Priority: ', a.priority, ' | Audience: ', a.target_audience) AS meta,
           u.name AS deleted_info,
           DATE_FORMAT(a.deleted_at, '%M %d, %Y %h:%i %p') AS deleted_at_fmt,
           a.deleted_at AS raw_deleted_at,
           NULL AS file_name,
           NULL AS file_size
    FROM announcements a
    LEFT JOIN users u ON u.id = a.posted_by
    WHERE a.deleted_at IS NOT NULL
    ORDER BY a.deleted_at DESC
");
if ($res) {
    while ($row = $res->fetch_assoc()) $items[] = $row;
}

// ── 2. Deleted grade sheets ────────────────────────────────────────────
$chk = $conn->query("SHOW TABLES LIKE 'grade_submissions'");
if ($chk && $chk->num_rows > 0) {
    $res = $conn->query("
        SELECT gs.id, 'grade_sheet' AS type,
               CONCAT(u.name, ' — ', COALESCE(sub.subject_name, 'Unknown Subject')) AS name,
               CONCAT('Section: ', COALESCE(sec.section_name, '?'), 
                      ' | SY: ', COALESCE(gs.school_year, '?'),
                      ' | Semester: ', COALESCE(gs.semester, '?')) AS meta,
               u.name AS deleted_info,
               DATE_FORMAT(gs.deleted_at, '%M %d, %Y %h:%i %p') AS deleted_at_fmt,
               gs.deleted_at AS raw_deleted_at,
               gs.file_name,
               NULL AS file_size
        FROM grade_submissions gs
        LEFT JOIN users u ON u.id = gs.teacher_id
        LEFT JOIN subjects sub ON sub.id = gs.subject_id
        LEFT JOIN sections sec ON sec.id = gs.section_id
        WHERE gs.deleted_at IS NOT NULL
        ORDER BY gs.deleted_at DESC
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) $items[] = $row;
    }
}

// ── 3. Deleted avatars (files in deleted_avatars folder) ──────────────
$projectRoot = dirname(__FILE__, 4);
$deletedDir  = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'deleted_avatars' . DIRECTORY_SEPARATOR;
if (is_dir($deletedDir)) {
    foreach (glob($deletedDir . 'user*.*') as $file) {
        $fname = basename($file);
        preg_match('/^user(\d+)_(\d+)_/', $fname, $m);
        $uid   = $m[1] ?? 0;
        $ts    = $m[2] ?? 0;
        $uname = 'Unknown User';
        if ($uid) {
            $s = $conn->prepare("SELECT name FROM users WHERE id = ?");
            $s->bind_param("i", $uid);
            $s->execute();
            $ur = $s->get_result()->fetch_assoc();
            if ($ur) $uname = $ur['name'];
        }
        $dt = $ts ? date('F d, Y h:i A', $ts) : '—';
        $items[] = [
            'id'             => $file, // use filepath as id for avatars
            'type'           => 'avatar',
            'name'           => "Avatar: $uname",
            'meta'           => "User ID: $uid",
            'deleted_info'   => $uname,
            'deleted_at_fmt' => $dt,
            'raw_deleted_at' => $ts ? date('Y-m-d H:i:s', $ts) : null,
            'file_name'      => $fname,
            'file_size'      => round(filesize($file) / 1024) . ' KB',
            'file_path'      => $file,
            'user_id'        => $uid,
        ];
    }
}

// Sort all items by deleted_at descending
usort($items, function($a, $b) {
    return strtotime($b['raw_deleted_at'] ?? 0) - strtotime($a['raw_deleted_at'] ?? 0);
});

echo json_encode([
    'success' => true,
    'items'   => $items,
    'total'   => count($items)
]);

$conn->close();
