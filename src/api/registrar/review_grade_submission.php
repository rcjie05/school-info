<?php
/**
 * Registrar: approve or reject a grade submission
 * POST { submission_id, action: 'approve'|'reject', note }
 */
ob_start();
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
header('Content-Type: application/json');

if (!isLoggedIn() || !hasRole('registrar')) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit();
}

$registrar_id  = (int)$_SESSION['user_id'];
$input         = json_decode(file_get_contents('php://input'), true);
$submission_id = (int)($input['submission_id'] ?? 0);
$action        = $input['action'] ?? '';
$note          = trim($input['note'] ?? '');

if (!$submission_id || !in_array($action, ['approve', 'reject'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$new_status = $action === 'approve' ? 'approved' : 'rejected';

// Fetch submission metadata
$stmt = $conn->prepare("
    SELECT gs.id, gs.teacher_id, gs.subject_id, gs.section_id, gs.semester, gs.school_year,
           gs.status, gs.teacher_note, gs.file_path, gs.file_name,
           LENGTH(gs.file_data) AS blob_size,
           t.name AS teacher_name, sub.subject_code, sub.subject_name, sec.section_name
    FROM grade_submissions gs
    JOIN users t      ON t.id   = gs.teacher_id
    JOIN subjects sub ON sub.id = gs.subject_id
    JOIN sections sec ON sec.id = gs.section_id
    WHERE gs.id = ? AND gs.status = 'pending'
");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$sub = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sub) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Submission not found or already reviewed']);
    exit();
}

// Update status first
$stmt = $conn->prepare("UPDATE grade_submissions SET status=?, registrar_note=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?");
$stmt->bind_param("ssii", $new_status, $note, $registrar_id, $submission_id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Update failed']);
    exit();
}

// ── APPROVE: extract grades ───────────────────────────────────────────
$grades_extracted = 0;
$extract_errors   = [];
$debug            = [];

if ($action === 'approve') {

    $xlsxData = null;
    $debug['blob_size_in_db'] = (int)$sub['blob_size'];
    $debug['file_path_in_db'] = $sub['file_path'];

    // 1. Try blob from DB
    if ((int)$sub['blob_size'] > 0) {
        $blobStmt = $conn->prepare("SELECT file_data FROM grade_submissions WHERE id = ?");
        $blobStmt->bind_param("i", $submission_id);
        $blobStmt->execute();
        $blobRow  = $blobStmt->get_result()->fetch_assoc();
        $blobStmt->close();
        $xlsxData = $blobRow['file_data'] ?? null;
        $debug['blob_fetched_bytes'] = $xlsxData ? strlen($xlsxData) : 0;
    }

    // 2. Disk fallback
    if (!$xlsxData && !empty($sub['file_path'])) {
        $stored   = $sub['file_path'];
        $filename = basename($stored);
        // Build candidates: absolute path as stored, and relative to project root
        $base = realpath(dirname(__FILE__, 4)) ?: dirname(dirname(dirname(dirname(__FILE__))));
        $candidates = [
            $stored,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $stored),
            $base . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'grade_sheets' . DIRECTORY_SEPARATOR . $filename,
            $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'grade_sheets' . DIRECTORY_SEPARATOR . $filename,
        ];
        $debug['disk_candidates'] = $candidates;
        foreach ($candidates as $c) {
            if ($c && file_exists($c)) {
                $xlsxData = file_get_contents($c);
                $debug['disk_file_found'] = $c;
                $debug['disk_bytes'] = strlen($xlsxData);
                break;
            }
        }
    }

    if (!$xlsxData) {
        $extract_errors[] = 'No Excel file found in DB (blob_size=' . $sub['blob_size'] . ') or on disk. Please resubmit.';
    } else {
        // Write to temp file
        $tmpName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'grade_' . $submission_id . '_' . time() . '.xlsx';
        $written = file_put_contents($tmpName, $xlsxData);
        $debug['tmp_written_bytes'] = $written;
        $debug['tmp_path'] = $tmpName;

        if (!$written) {
            $extract_errors[] = 'Cannot write temp file to: ' . $tmpName;
        } else {
            $rows = readXlsxRows($tmpName);
            @unlink($tmpName);

            $debug['rows_count'] = is_array($rows) ? count($rows) : 'parse_failed';

            if ($rows === false) {
                $extract_errors[] = 'ZipArchive failed to open Excel. Ensure php_zip extension is enabled.';
            } elseif (count($rows) === 0) {
                $extract_errors[] = 'Excel parsed but found 0 rows.';
            } else {
                // Find header row containing Student ID
                $headerRowIdx = null;
                $headers      = [];
                $sidKeywords  = ['student id','student_id','studentid','id number','id no','student number','student no'];

                foreach ($rows as $ri => $row) {
                    $low = array_map(fn($v) => strtolower(trim($v)), $row);
                    foreach ($sidKeywords as $kw) {
                        if (in_array($kw, $low)) { $headerRowIdx = $ri; $headers = $low; break 2; }
                    }
                }
                if ($headerRowIdx === null) {
                    $headerRowIdx = 0;
                    $headers = array_map(fn($v) => strtolower(trim($v)), $rows[0]);
                }

                $debug['header_row_idx'] = $headerRowIdx;
                $debug['headers'] = $headers;

                $colSid     = findCol($headers, ['student id','student_id','studentid','id number','id no','student number','student no']);
                $colMidterm = findCol($headers, ['midterm','midterm grade','mid','midterm_grade','prelim','prelim grade']);
                $colFinal   = findCol($headers, ['final','final grade','final_grade','finals','final rating']);

                $debug['col_sid'] = $colSid;
                $debug['col_mid'] = $colMidterm;
                $debug['col_fin'] = $colFinal;

                if ($colSid === null) {
                    $extract_errors[] = 'Cannot find Student ID column. Headers found: ' . implode(', ', array_filter($headers));
                } else {
                    $semester    = $sub['semester']    ?? '1st Semester';
                    $school_year = $sub['school_year'] ?? date('Y') . '-' . (date('Y') + 1);
                    $subject_id  = (int)$sub['subject_id'];
                    $skip_words  = ['average','passed','failed','total','remarks','no.','no','#','n/a',''];

                    for ($i = $headerRowIdx + 1; $i < count($rows); $i++) {
                        $row = $rows[$i];
                        if (!array_filter($row, fn($v) => trim($v) !== '')) continue;

                        $raw = trim($row[$colSid] ?? '');
                        if ($raw === '') continue;
                        if (in_array(strtolower($raw), $skip_words)) continue;

                        $uid = resolveStudent($conn, $raw);
                        if (!$uid) {
                            $extract_errors[] = "Row " . ($i + 1) . ": Student '{$raw}' not found in DB.";
                            continue;
                        }

                        $mid = parseGrade($colMidterm !== null ? ($row[$colMidterm] ?? '') : '');
                        $fin = parseGrade($colFinal   !== null ? ($row[$colFinal]   ?? '') : '');
                        if ($mid === null && $fin === null) continue;

                        $rem = remarks($fin ?? $mid);

                        // Upsert grade
                        $chk = $conn->prepare("SELECT id FROM grades WHERE student_id=? AND subject_id=? AND semester=? AND school_year=?");
                        $chk->bind_param("iiss", $uid, $subject_id, $semester, $school_year);
                        $chk->execute();
                        $ex = $chk->get_result()->fetch_assoc();
                        $chk->close();

                        if ($ex) {
                            $u = $conn->prepare("UPDATE grades SET midterm_grade=?, final_grade=?, remarks=? WHERE id=?");
                            $u->bind_param("ddsi", $mid, $fin, $rem, $ex['id']);
                            $u->execute();
                            $u->close();
                        } else {
                            $ins = $conn->prepare("INSERT INTO grades (student_id, subject_id, midterm_grade, final_grade, semester, school_year, remarks) VALUES (?,?,?,?,?,?,?)");
                            $ins->bind_param("iiddsss", $uid, $subject_id, $mid, $fin, $semester, $school_year, $rem);
                            $ins->execute();
                            $ins->close();
                        }
                        $grades_extracted++;
                        createNotification($conn, $uid, '🎓 Grade Posted',
                            "Your grade for {$sub['subject_code']} {$sub['subject_name']} ({$semester} {$school_year}) has been recorded.");
                    }
                }
            }
        }
    }
}

// Notify teacher
$verb  = $action === 'approve' ? 'approved' : 'rejected';
$emoji = $action === 'approve' ? '✅' : '❌';
$msg   = "Your grade submission for {$sub['subject_code']} {$sub['subject_name']} — {$sub['section_name']} has been $verb.";
if ($note) $msg .= " Note: $note";
if ($grades_extracted > 0) $msg .= " ($grades_extracted grade(s) recorded.)";
createNotification($conn, $sub['teacher_id'], "$emoji Grade Sheet " . ucfirst($verb), $msg);
logAction($conn, $registrar_id, "Grade submission #$submission_id $verb ($grades_extracted extracted)", 'grade_submissions', $submission_id);

$conn->close();
ob_end_clean();

$res = ['success' => true, 'message' => "Submission $verb successfully."];
if ($action === 'approve') {
    $res['grades_extracted'] = $grades_extracted;
    if ($extract_errors) $res['extract_warnings'] = $extract_errors;
    $res['debug'] = $debug; // Remove this line after confirming it works
}
echo json_encode($res);

// ═══════════════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════════════

function readXlsxRows($file) {
    if (!class_exists('ZipArchive')) return false;
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) return false;

    $shared = [];
    $ssXml  = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml !== false) $shared = parseSharedStrings($ssXml);

    $sheetXml = false;
    for ($s = 1; $s <= 10; $s++) {
        $sheetXml = $zip->getFromName("xl/worksheets/sheet{$s}.xml");
        if ($sheetXml !== false) break;
    }
    $zip->close();
    if ($sheetXml === false) return false;
    return parseSheetRows($sheetXml, $shared);
}

function parseSharedStrings($xml) {
    $shared = [];
    $reader = new XMLReader();
    if (!$reader->XML($xml, 'UTF-8', LIBXML_NOERROR | LIBXML_NOWARNING)) return [];
    $inSi = false; $inT = false; $text = '';
    while ($reader->read()) {
        $local = $reader->localName; $type = $reader->nodeType;
        if ($type === XMLReader::ELEMENT) {
            if ($local === 'si') { $inSi = true; $text = ''; }
            elseif ($inSi && $local === 't') { $inT = true; }
        } elseif ($type === XMLReader::TEXT || $type === XMLReader::CDATA) {
            if ($inSi && $inT) $text .= $reader->value;
        } elseif ($type === XMLReader::END_ELEMENT) {
            if ($local === 't') $inT = false;
            elseif ($local === 'si') { $shared[] = $text; $inSi = false; $text = ''; }
        }
    }
    $reader->close();
    return $shared;
}

function parseSheetRows($xml, $shared) {
    $rows = [];
    $reader = new XMLReader();
    if (!$reader->XML($xml, 'UTF-8', LIBXML_NOERROR | LIBXML_NOWARNING)) return [];
    $inSD = false; $inRow = false; $inCell = false; $inV = false;
    $sparseRow = []; $cellRef = ''; $cellType = ''; $cellVal = '';
    while ($reader->read()) {
        $local = $reader->localName; $type = $reader->nodeType;
        if ($type === XMLReader::ELEMENT) {
            if ($local === 'sheetData') $inSD = true;
            elseif ($inSD && $local === 'row') { $inRow = true; $sparseRow = []; }
            elseif ($inRow && $local === 'c') {
                $inCell = true;
                $cellRef  = $reader->getAttribute('r') ?? '';
                $cellType = $reader->getAttribute('t') ?? '';
                $cellVal  = '';
            } elseif ($inCell && $local === 'v') $inV = true;
        } elseif ($type === XMLReader::TEXT || $type === XMLReader::CDATA) {
            if ($inV) $cellVal .= $reader->value;
        } elseif ($type === XMLReader::END_ELEMENT) {
            if ($local === 'v') $inV = false;
            elseif ($local === 'c') {
                $value = $cellVal;
                if ($cellType === 's') { $idx = (int)$cellVal; $value = $shared[$idx] ?? ''; }
                elseif ($cellType === 'b') $value = $cellVal ? 'TRUE' : 'FALSE';
                $col = rtrim(preg_replace('/[0-9]/', '', $cellRef));
                if ($col !== '') { $sparseRow[colIdx($col)] = $value; }
                $inCell = false; $cellVal = '';
            } elseif ($local === 'row') {
                if (!empty($sparseRow)) {
                    $max = max(array_keys($sparseRow));
                    $dense = [];
                    for ($c = 0; $c <= $max; $c++) $dense[] = $sparseRow[$c] ?? '';
                    if (array_filter($dense, fn($v) => trim($v) !== '')) $rows[] = $dense;
                }
                $inRow = false;
            } elseif ($local === 'sheetData') $inSD = false;
        }
    }
    $reader->close();
    return $rows;
}

function colIdx($letters) {
    $n = 0;
    for ($i = 0; $i < strlen($letters); $i++) $n = $n * 26 + (ord(strtoupper($letters[$i])) - 64);
    return $n - 1;
}

function findCol($headers, $candidates) {
    foreach ($candidates as $c) {
        $i = array_search(strtolower(trim($c)), $headers);
        if ($i !== false) return (int)$i;
    }
    foreach ($candidates as $c) {
        foreach ($headers as $i => $h) {
            if ($h !== '' && strpos($h, strtolower(trim($c))) !== false) return $i;
        }
    }
    return null;
}

function parseGrade($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') return null;
    if (in_array(strtolower($raw), ['n/a','inc','incomplete','dropped','w'])) return null;
    if (isset($raw[0]) && $raw[0] === '=') return null;
    $raw = rtrim($raw, '%');
    if (!is_numeric($raw)) return null;
    $v = (float)$raw;
    if ($v < 0 || $v > 100) return null;
    return $v;
}

function remarks($grade) {
    if ($grade === null) return null;
    // GPA scale 1.0-5.0 (Philippine grading: 1.0 best, 3.0 = passing, 5.0 = fail)
    if ($grade > 0 && $grade <= 5.0) return $grade <= 3.0 ? 'Passed' : 'Failed';
    // Percentage scale
    return $grade >= 75 ? 'Passed' : 'Failed';
}

function resolveStudent($conn, $raw) {
    // Match by student_id field
    $s = $conn->prepare("SELECT id FROM users WHERE student_id=? AND role='student' LIMIT 1");
    $s->bind_param("s", $raw); $s->execute();
    $r = $s->get_result()->fetch_assoc(); $s->close();
    if ($r) return (int)$r['id'];

    // Match by numeric DB id
    if (is_numeric($raw)) {
        $id = (int)$raw;
        $s  = $conn->prepare("SELECT id FROM users WHERE id=? AND role='student' LIMIT 1");
        $s->bind_param("i", $id); $s->execute();
        $r = $s->get_result()->fetch_assoc(); $s->close();
        if ($r) return (int)$r['id'];
    }

    // Match by name
    $s = $conn->prepare("SELECT id FROM users WHERE name=? AND role='student' LIMIT 1");
    $s->bind_param("s", $raw); $s->execute();
    $r = $s->get_result()->fetch_assoc(); $s->close();
    return $r ? (int)$r['id'] : null;
}
