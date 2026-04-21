<?php
/**
 * DEBUG: Test grade extraction from a submission without actually approving it
 * GET ?id=<submission_id>
 * REMOVE THIS FILE IN PRODUCTION
 */
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
if (!isLoggedIn() || !hasRole('registrar')) { die('Access denied'); }
header('Content-Type: application/json');

$submission_id = (int)($_GET['id'] ?? 0);
if (!$submission_id) { echo json_encode(['error' => 'Need ?id=']); exit; }

$conn = getDBConnection();

// Fetch metadata
$stmt = $conn->prepare("SELECT id, teacher_id, subject_id, section_id, semester, school_year, status, file_path, file_name, LENGTH(file_data) as blob_size FROM grade_submissions WHERE id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$sub = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sub) { echo json_encode(['error' => 'Submission not found']); exit; }

$result = [
    'submission' => $sub,
    'blob_size_in_db' => $sub['blob_size'],
    'file_path' => $sub['file_path'],
    'file_name' => $sub['file_name'],
];

// Try to get blob
$blobStmt = $conn->prepare("SELECT file_data FROM grade_submissions WHERE id = ?");
$blobStmt->bind_param("i", $submission_id);
$blobStmt->execute();
$blobRow = $blobStmt->get_result()->fetch_assoc();
$blobStmt->close();

$xlsxData = $blobRow['file_data'] ?? null;
$result['blob_fetched_bytes'] = $xlsxData ? strlen($xlsxData) : 0;

// Try disk fallback
if (!$xlsxData && !empty($sub['file_path'])) {
    $stored = $sub['file_path'];
    $filename = basename($stored);
    $base = realpath(dirname(__FILE__, 4)) ?: dirname(dirname(dirname(dirname(__FILE__))));
    $candidates = [
        $stored,
        str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $stored),
        $base . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'grade_sheets' . DIRECTORY_SEPARATOR . $filename,
        $_SERVER['DOCUMENT_ROOT'] . $stored,
    ];
    foreach ($candidates as $c) {
        if ($c && file_exists($c)) {
            $xlsxData = file_get_contents($c);
            $result['file_found_at'] = $c;
            break;
        }
    }
    $result['disk_candidates_tried'] = $candidates;
}

if (!$xlsxData) {
    $result['error'] = 'No Excel data found in DB blob or disk';
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

$result['xlsx_bytes_available'] = strlen($xlsxData);

// Try writing temp file
$tmpName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'debug_grade_' . $submission_id . '.xlsx';
$written = file_put_contents($tmpName, $xlsxData);
$result['tmp_file_written'] = $written;
$result['tmp_file_path'] = $tmpName;

if (!$written) {
    $result['error'] = 'Cannot write temp file';
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

// Try parsing
if (!class_exists('ZipArchive')) {
    $result['error'] = 'ZipArchive not available';
    @unlink($tmpName);
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

$zip = new ZipArchive();
$zipOpen = $zip->open($tmpName);
$result['zip_open_result'] = $zipOpen; // true = success

if ($zipOpen !== true) {
    $result['error'] = 'ZipArchive cannot open file. Result code: ' . $zipOpen;
    // Check if it's actually a valid zip (xlsx)
    $result['first_bytes_hex'] = bin2hex(substr($xlsxData, 0, 8));
    @unlink($tmpName);
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

$result['zip_files'] = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $result['zip_files'][] = $zip->getNameIndex($i);
}
$zip->close();

// Now use the readXlsxRows function
function readXlsxRows($file) {
    if (!class_exists('ZipArchive')) return false;
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) return false;
    $shared = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
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
    $rows = []; $reader = new XMLReader();
    if (!$reader->XML($xml, 'UTF-8', LIBXML_NOERROR | LIBXML_NOWARNING)) return [];
    $inSD = false; $inRow = false; $inCell = false; $inV = false;
    $sparseRow = []; $cellRef = ''; $cellType = ''; $cellVal = '';
    while ($reader->read()) {
        $local = $reader->localName; $type = $reader->nodeType;
        if ($type === XMLReader::ELEMENT) {
            if ($local === 'sheetData') $inSD = true;
            elseif ($inSD && $local === 'row') { $inRow = true; $sparseRow = []; }
            elseif ($inRow && $local === 'c') { $inCell = true; $cellRef = $reader->getAttribute('r') ?? ''; $cellType = $reader->getAttribute('t') ?? ''; $cellVal = ''; }
            elseif ($inCell && $local === 'v') $inV = true;
        } elseif ($type === XMLReader::TEXT || $type === XMLReader::CDATA) {
            if ($inV) $cellVal .= $reader->value;
        } elseif ($type === XMLReader::END_ELEMENT) {
            if ($local === 'v') $inV = false;
            elseif ($local === 'c') {
                $value = $cellVal;
                if ($cellType === 's') { $idx = (int)$cellVal; $value = $shared[$idx] ?? ''; }
                $col = rtrim(preg_replace('/[0-9]/', '', $cellRef));
                if ($col !== '') { $idx = colIdx($col); $sparseRow[$idx] = $value; }
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

$rows = readXlsxRows($tmpName);
@unlink($tmpName);

$result['rows_parsed'] = is_array($rows) ? count($rows) : 'false (parse failed)';
$result['rows_data'] = $rows;

// Find students
if (is_array($rows) && count($rows) > 0) {
    $sidKeywords = ['student id','student_id','studentid','id number','id no','student number','student no'];
    $headerRowIdx = null; $headers = [];
    foreach ($rows as $ri => $row) {
        $low = array_map(fn($v) => strtolower(trim($v)), $row);
        foreach ($sidKeywords as $kw) {
            if (in_array($kw, $low)) { $headerRowIdx = $ri; $headers = $low; break 2; }
        }
    }
    $result['header_row_idx'] = $headerRowIdx;
    $result['headers'] = $headers;

    if ($headerRowIdx !== null) {
        // Check each student in DB
        for ($i = $headerRowIdx + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $colSid = null;
            foreach ($headers as $j => $h) { if ($h === 'student id') { $colSid = $j; break; } }
            $raw = trim($row[$colSid] ?? '');
            if ($raw === '') continue;

            // Check DB
            $s = $conn->prepare("SELECT id, name, student_id FROM users WHERE student_id=? AND role='student' LIMIT 1");
            $s->bind_param("s", $raw); $s->execute();
            $found = $s->get_result()->fetch_assoc(); $s->close();
            $result['student_lookup'][] = ['excel_id' => $raw, 'found_in_db' => $found ? ['id'=>$found['id'],'name'=>$found['name']] : null];
        }
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);
