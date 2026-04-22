<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['reply' => 'Your session has expired. Please log in again.']);
    exit();
}
if (!hasRole('student')) {
    echo json_encode(['reply' => 'Access denied. This assistant is for students only.']);
    exit();
}

$conn    = getDBConnection();
$user_id = $_SESSION['user_id'];

if (!$conn) {
    echo json_encode(['reply' => 'Sorry, I could not connect to the database right now.']);
    exit();
}

// ── Load student data ─────────────────────────────────────────────────────────
function getStudentData($conn, $user_id) {
    $data = [];

    // Profile
    $stmt = $conn->prepare("SELECT name, email, student_id, course, year_level, status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $data['profile'] = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Load count
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT sl.id) as subject_count, COALESCE(SUM(s.units),0) as total_units FROM study_loads sl JOIN subjects s ON sl.subject_id = s.id WHERE sl.student_id = ? AND sl.status = 'finalized'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $data['load'] = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Grades
    $stmt = $conn->prepare("SELECT s.subject_code, s.subject_name, s.units, g.midterm_grade, g.final_grade, g.remarks FROM grades g JOIN subjects s ON g.subject_id = s.id WHERE g.student_id = ? ORDER BY s.subject_code");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data['grades'] = [];
    $totalGrade = 0; $gradeCount = 0;
    while ($row = $res->fetch_assoc()) {
        $data['grades'][] = $row;
        if ($row['final_grade']) { $totalGrade += $row['final_grade']; $gradeCount++; }
    }
    $data['gpa'] = $gradeCount > 0 ? number_format($totalGrade / $gradeCount, 2) : null;
    $stmt->close();

    // Schedule
    $has_sec_sched = $conn->query("SHOW TABLES LIKE 'section_schedules'")->num_rows > 0;
    $has_sec_subj  = $conn->query("SHOW TABLES LIKE 'section_subjects'")->num_rows > 0;
    $has_sl_sec    = $conn->query("SHOW COLUMNS FROM `study_loads` LIKE 'section_id'")->num_rows > 0;

    if ($has_sec_sched && $has_sec_subj && $has_sl_sec) {
        $sql = "SELECT s.subject_code, s.subject_name, sec.section_name, u.name AS teacher_name, sc.day_of_week, sc.start_time, sc.end_time, sc.room, sc.building FROM study_loads sl JOIN subjects s ON sl.subject_id = s.id LEFT JOIN sections sec ON sl.section_id = sec.id LEFT JOIN users u ON sl.teacher_id = u.id LEFT JOIN section_subjects ss ON ss.section_id = sl.section_id AND ss.subject_id = sl.subject_id LEFT JOIN section_schedules sc ON sc.section_subject_id = ss.id WHERE sl.student_id = ? ORDER BY sc.start_time";
    } else {
        $sql = "SELECT s.subject_code, s.subject_name, sl.section AS section_name, u.name AS teacher_name, sch.day_of_week, sch.start_time, sch.end_time, sch.room, sch.building FROM study_loads sl JOIN subjects s ON sl.subject_id = s.id LEFT JOIN schedules sch ON sch.study_load_id = sl.id LEFT JOIN users u ON sl.teacher_id = u.id WHERE sl.student_id = ? ORDER BY sch.day_of_week, sch.start_time";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data['schedule'] = [];
    while ($row = $res->fetch_assoc()) {
        if (empty($row['day_of_week'])) continue;
        $roomStr = trim(($row['building'] ? $row['building'] . ' - ' : '') . ($row['room'] ?? ''), ' -');
        $data['schedule'][] = [
            'subject_code' => $row['subject_code'],
            'subject_name' => $row['subject_name'],
            'section'      => $row['section_name'] ?? 'TBA',
            'teacher'      => $row['teacher_name']  ?? 'TBA',
            'day'          => $row['day_of_week'],
            'start'        => $row['start_time'] ? date('g:i A', strtotime($row['start_time'])) : 'TBA',
            'end'          => $row['end_time']   ? date('g:i A', strtotime($row['end_time']))   : 'TBA',
            'room'         => $roomStr ?: 'TBA',
        ];
    }
    $stmt->close();

    // Announcements
    $stmt = $conn->prepare("SELECT title, content, DATE_FORMAT(created_at, '%M %d, %Y') as date FROM announcements WHERE target_audience IN ('all','students') ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $res = $stmt->get_result();
    $data['announcements'] = [];
    while ($row = $res->fetch_assoc()) $data['announcements'][] = $row;
    $stmt->close();

    // Section
    $data['section'] = null;
    $sec_stmt = $conn->prepare("SELECT s.section_name, s.section_code, s.course, s.year_level, s.semester, s.school_year, s.room, s.building FROM users u JOIN sections s ON u.section_id = s.id WHERE u.id = ?");
    if ($sec_stmt) {
        $sec_stmt->bind_param('i', $user_id);
        $sec_stmt->execute();
        $data['section'] = $sec_stmt->get_result()->fetch_assoc();
        $sec_stmt->close();
    }

    // Feedback
    $data['feedback'] = [];
    $fb_stmt = $conn->prepare("SELECT subject, message, status, response, DATE_FORMAT(created_at, '%M %d, %Y') as date FROM feedback WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    if ($fb_stmt) {
        $fb_stmt->bind_param('i', $user_id);
        $fb_stmt->execute();
        $fb_res = $fb_stmt->get_result();
        while ($row = $fb_res->fetch_assoc()) $data['feedback'][] = $row;
        $fb_stmt->close();
    }

    return $data;
}

function getRoutes($conn) {
    $routes = [];
    $stmt = $conn->prepare("SELECT name, description, start_room, end_room FROM floor_plan_routes WHERE visible_to_students = 1 ORDER BY name ASC");
    if (!$stmt) return $routes;
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $routes[] = $row;
    $stmt->close();
    return $routes;
}

function getCourses($conn) {
    $courses = [];
    $stmt = $conn->prepare("SELECT course_name, course_code FROM courses WHERE status = 'active' ORDER BY course_name");
    if (!$stmt) return $courses;
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $courses[] = $row;
    $stmt->close();
    return $courses;
}

// ── Build context for Claude ──────────────────────────────────────────────────
function buildContext($data, $routes, $courses) {
    $p    = $data['profile'];
    $today = date('l, F j, Y');
    $todayDay = date('l');

    $ctx = "TODAY: $today\n\n";
    $ctx .= "=== STUDENT PROFILE ===\n";
    $ctx .= "Name: {$p['name']}\n";
    $ctx .= "Student ID: {$p['student_id']}\n";
    $ctx .= "Email: {$p['email']}\n";
    $ctx .= "Course: {$p['course']}\n";
    $ctx .= "Year Level: {$p['year_level']}\n";
    $ctx .= "Status: {$p['status']}\n\n";

    if ($data['section']) {
        $sec = $data['section'];
        $ctx .= "=== SECTION ===\n";
        $ctx .= "Section: {$sec['section_name']} ({$sec['section_code']})\n";
        $ctx .= "Semester: {$sec['semester']} | School Year: {$sec['school_year']}\n\n";
    }

    $ctx .= "=== ENROLLED SUBJECTS ({$data['load']['subject_count']} subjects, {$data['load']['total_units']} units) ===\n";
    $seen = [];
    foreach ($data['schedule'] as $s) {
        if (!isset($seen[$s['subject_code']])) {
            $seen[$s['subject_code']] = true;
            $ctx .= "- {$s['subject_code']}: {$s['subject_name']} | Teacher: {$s['teacher']}\n";
        }
    }

    $ctx .= "\n=== SCHEDULE ===\n";
    $byDay = [];
    foreach ($data['schedule'] as $s) $byDay[$s['day']][] = $s;
    $order = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    foreach ($order as $day) {
        if (empty($byDay[$day])) continue;
        $ctx .= "$day:\n";
        foreach ($byDay[$day] as $s) {
            $ctx .= "  - {$s['subject_code']} {$s['subject_name']}: {$s['start']}-{$s['end']} | Room: {$s['room']}\n";
        }
    }

    if (!empty($data['grades'])) {
        $ctx .= "\n=== GRADES ===\n";
        foreach ($data['grades'] as $g) {
            $mid = $g['midterm_grade'] ?? 'N/A';
            $fin = $g['final_grade'] ?? 'N/A';
            $rem = $g['remarks'] ?? '';
            $ctx .= "- {$g['subject_code']} {$g['subject_name']}: Midterm={$mid}, Final={$fin} {$rem}\n";
        }
        if ($data['gpa']) $ctx .= "GPA: {$data['gpa']}\n";
    }

    if (!empty($data['announcements'])) {
        $ctx .= "\n=== LATEST ANNOUNCEMENTS ===\n";
        foreach ($data['announcements'] as $a) {
            $ctx .= "- [{$a['date']}] {$a['title']}: {$a['content']}\n";
        }
    }

    if (!empty($data['feedback'])) {
        $ctx .= "\n=== MY FEEDBACK SUBMISSIONS ===\n";
        foreach ($data['feedback'] as $f) {
            $ctx .= "- {$f['subject']} ({$f['status']}) - {$f['date']}\n";
            if ($f['response']) $ctx .= "  Response: {$f['response']}\n";
        }
    }

    if (!empty($routes)) {
        $ctx .= "\n=== CAMPUS NAVIGATION ROUTES ===\n";
        foreach ($routes as $r) {
            $ctx .= "- {$r['name']}: {$r['start_room']} → {$r['end_room']}\n";
        }
    }

    if (!empty($courses)) {
        $ctx .= "\n=== AVAILABLE COURSES ===\n";
        foreach ($courses as $c) {
            $ctx .= "- {$c['course_name']} ({$c['course_code']})\n";
        }
    }

    $ctx .= "\n=== SCHOOL INFO ===\n";
    $ctx .= "School: St. Cecilia's College-Cebu, Inc.\n";
    $ctx .= "Address: Poblacion Ward II, Minglanilla, Cebu, Philippines\n";
    $ctx .= "Registrar: (032) 326-3677 | Email: info@stcecilia.edu.ph\n";
    $ctx .= "Office Hours: Mon-Fri 7:30AM-5PM\n";

    return $ctx;
}

// ── Direct answers for simple questions (fast, no AI needed) ─────────────────
function directAnswer($msg, $data, $routes, $courses) {
    $m = strtolower(trim($msg));
    $p = $data['profile'];
    $nameParts = explode(' ', $p['name']);
    $firstName = $nameParts[0];

    // Greeting
    if (preg_match('/^(hi|hello|hey|good morning|good afternoon|good evening|start|kamusta)/', $m))
        return "Hi, <strong>$firstName</strong>! 👋 I'm your School Assistant. Ask me about your grades, schedule, subjects, teachers, announcements, and more!";

    // Name
    if (strpos($m, 'my name') !== false || strpos($m, 'who am i') !== false || $m === 'name')
        return "Your name is <strong>{$p['name']}</strong>.";

    // Student ID
    if (strpos($m, 'student id') !== false || strpos($m, 'my id') !== false || strpos($m, 'id number') !== false || strpos($m, 'school id') !== false)
        return "Your Student ID is <strong>{$p['student_id']}</strong>.";

    // Email
    if (strpos($m, 'my email') !== false || strpos($m, 'email address') !== false)
        return "Your registered email is <strong>{$p['email']}</strong>.";

    // Course
    if (strpos($m, 'my course') !== false || strpos($m, 'what course') !== false || strpos($m, 'my program') !== false)
        return "You are enrolled in <strong>{$p['course']}</strong>.";

    // Year level
    if (strpos($m, 'year level') !== false || strpos($m, 'what year') !== false || strpos($m, 'year am i') !== false)
        return "You are currently in <strong>{$p['year_level']}</strong>.";

    // Status
    if (strpos($m, 'my status') !== false || strpos($m, 'enrollment status') !== false || strpos($m, 'am i enrolled') !== false)
        return "Your enrollment status is: <strong>" . ucfirst($p['status']) . "</strong>.";

    // Section
    if (strpos($m, 'my section') !== false || strpos($m, 'what section') !== false || strpos($m, 'my block') !== false) {
        $sec = $data['section'];
        if (!$sec) return "You are not assigned to a section yet. Please contact the Registrar.";
        return "Your section is <strong>{$sec['section_name']}</strong> ({$sec['section_code']})<br>
                Semester: {$sec['semester']} | School Year: {$sec['school_year']}";
    }

    // Teachers
    if (strpos($m, 'teacher') !== false || strpos($m, 'professor') !== false || strpos($m, 'instructor') !== false || strpos($m, 'who teaches') !== false || strpos($m, 'sino teacher') !== false) {
        if (empty($data['schedule'])) return "No teacher info found. Please contact the Registrar.";
        $seen = []; $rows = '';
        foreach ($data['schedule'] as $s) {
            if (!isset($seen[$s['subject_code']])) {
                $seen[$s['subject_code']] = true;
                $rows .= "<li><strong>{$s['subject_code']}</strong> {$s['subject_name']} — {$s['teacher']}</li>";
            }
        }
        return "Your subject teachers:<ul>$rows</ul>";
    }

    // Grades
    if (strpos($m, 'grade') !== false || strpos($m, 'gpa') !== false || strpos($m, 'passed') !== false || strpos($m, 'failed') !== false) {
        if (empty($data['grades'])) return "No grades have been posted yet. Check back later!";
        $rows = '';
        foreach ($data['grades'] as $g) {
            $mid = $g['midterm_grade'] ?? '-';
            $fin = $g['final_grade']   ?? '-';
            $rem = $g['remarks']       ?? '';
            $color = $rem === 'Passed' ? 'color:#16a34a' : ($rem === 'Failed' ? 'color:#dc2626' : '');
            $rows .= "<li><strong>{$g['subject_code']}</strong> {$g['subject_name']}<br>
                      <span style='font-size:.88em'>Midterm: <strong>$mid</strong> | Final: <strong>$fin</strong>" .
                      ($rem ? " | <span style='$color'>$rem</span>" : '') . "</span></li>";
        }
        $gpa = $data['gpa'] ? "<br>Overall GPA: <strong>{$data['gpa']}</strong>" : '';
        return "Here are your grades:$gpa<ul>$rows</ul>";
    }

    // Schedule - today
    if (strpos($m, 'today') !== false || strpos($m, 'ngayon') !== false || strpos($m, 'class today') !== false) {
        $today = date('l');
        $todaySched = array_filter($data['schedule'], fn($s) => $s['day'] === $today);
        if (empty($todaySched)) return "You have no classes today (<strong>$today</strong>). Enjoy your day! 😊";
        $rows = '';
        foreach ($todaySched as $s) {
            $rows .= "<li><strong>{$s['subject_code']}</strong> {$s['subject_name']}<br>
                      <span style='font-size:.88em'>🕐 {$s['start']} - {$s['end']} | 🚪 {$s['room']} | 👨‍🏫 {$s['teacher']}</span></li>";
        }
        return "Your classes for today (<strong>$today</strong>):<ul>$rows</ul>";
    }

    // Schedule - full
    if (strpos($m, 'schedule') !== false || strpos($m, 'timetable') !== false || strpos($m, 'my classes') !== false || strpos($m, 'class schedule') !== false) {
        if (empty($data['schedule'])) return "No schedule found. Please contact the Registrar.";
        $byDay = [];
        foreach ($data['schedule'] as $s) $byDay[$s['day']][] = $s;
        $order = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        $out = "Your weekly schedule:";
        foreach ($order as $day) {
            if (empty($byDay[$day])) continue;
            $out .= "<br><br><strong>$day</strong><ul>";
            foreach ($byDay[$day] as $s) {
                $out .= "<li><strong>{$s['subject_code']}</strong> {$s['subject_name']}<br>
                         <span style='font-size:.88em'>🕐 {$s['start']}-{$s['end']} | 🚪 {$s['room']} | 👨‍🏫 {$s['teacher']}</span></li>";
            }
            $out .= "</ul>";
        }
        return $out;
    }

    // Subjects
    if (strpos($m, 'subject') !== false || strpos($m, 'enrolled in') !== false || strpos($m, 'study load') !== false || strpos($m, 'how many units') !== false) {
        $count = $data['load']['subject_count'] ?? 0;
        $units = $data['load']['total_units']   ?? 0;
        if ($count == 0) return "You don't have any finalized enrolled subjects yet.";
        $seen = []; $rows = '';
        foreach ($data['schedule'] as $s) {
            if (!isset($seen[$s['subject_code']])) {
                $seen[$s['subject_code']] = true;
                $rows .= "<li><strong>{$s['subject_code']}</strong> — {$s['subject_name']}</li>";
            }
        }
        return "You are enrolled in <strong>$count subject(s)</strong> totaling <strong>$units units</strong>:<ul>$rows</ul>";
    }

    // Announcements
    if (strpos($m, 'announcement') !== false || strpos($m, 'news') !== false || strpos($m, 'updates') !== false || strpos($m, 'notice') !== false) {
        if (empty($data['announcements'])) return "No announcements at the moment. Check back later!";
        $rows = '';
        foreach ($data['announcements'] as $a) {
            $preview = strlen($a['content']) > 120 ? substr($a['content'], 0, 120) . '...' : $a['content'];
            $rows .= "<li><strong>{$a['title']}</strong> <small>({$a['date']})</small><br><span style='font-size:.9em'>$preview</span></li>";
        }
        return "Latest announcements:<ul>$rows</ul>";
    }

    // Feedback
    if (strpos($m, 'my feedback') !== false || strpos($m, 'feedback status') !== false || strpos($m, 'my complaint') !== false) {
        if (empty($data['feedback'])) return "You haven't submitted any feedback yet. Go to <a href='feedback.php'>Feedback</a> in the sidebar.";
        $rows = '';
        foreach ($data['feedback'] as $f) {
            $status = ucfirst(str_replace('_', ' ', $f['status']));
            $rep = $f['response'] ? "<br><em>Response: " . substr($f['response'], 0, 80) . "...</em>" : '';
            $rows .= "<li><strong>{$f['subject']}</strong> — $status <small>({$f['date']})</small>$rep</li>";
        }
        return "Your feedback submissions:<ul>$rows</ul>";
    }

    // Available courses
    if (strpos($m, 'available course') !== false || strpos($m, 'courses offered') !== false || strpos($m, 'programs offered') !== false || strpos($m, 'course list') !== false) {
        if (empty($courses)) return "Please visit the Registrar's Office for available programs.";
        $rows = '';
        foreach ($courses as $c) $rows .= "<li><strong>{$c['course_name']}</strong> ({$c['course_code']})</li>";
        return "Available programs at St. Cecilia's College-Cebu:<ul>$rows</ul>";
    }

    // Floor plan / routes
    if (strpos($m, 'floor plan') !== false || strpos($m, 'campus map') !== false || strpos($m, 'available routes') !== false) {
        if (empty($routes)) return "No navigation routes set up yet. Visit the <a href='floorplan.php'>Campus Map</a>!";
        $rows = '';
        foreach ($routes as $r) $rows .= "<li><strong>{$r['name']}</strong>: {$r['start_room']} → {$r['end_room']}</li>";
        return "Available campus routes:<ul>$rows</ul>Open the <a href='floorplan.php'>Floor Plan</a> to see them on the map!";
    }

    // Thanks / Bye
    if (strpos($m, 'thank') !== false || strpos($m, 'salamat') !== false)
        return "You're welcome, <strong>$firstName</strong>! 😊 Is there anything else I can help you with?";
    if (strpos($m, 'bye') !== false || strpos($m, 'goodbye') !== false)
        return "Take care, <strong>$firstName</strong>! Good luck with your studies! 👋";

    // No direct match — use AI
    return null;
}

// ── Convert markdown to HTML ─────────────────────────────────────────────────
function markdownToHtml($text) {
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
    $text = preg_replace('/^[\*\-]\s+(.+)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/(<li>.*?<\/li>(\s*<li>.*?<\/li>)*)/s', '<ul>$1</ul>', $text);
    $text = nl2br($text);
    return $text;
}

// ── Call Groq API for complex questions ───────────────────────────────────────
function askGroq($userMessage, $context) {
    $apiKey = getenv('GROQ_API_KEY');
    if (!$apiKey) return "I can answer basic questions about your grades, schedule, subjects, and teachers. For other questions, please contact the Registrar at <strong>(032) 326-3677</strong>.";

    $systemPrompt = "You are a helpful school assistant chatbot for St. Cecilia's College-Cebu, Inc. Answer using the student data below. Be friendly and concise. Use plain text only. If the answer is not in the data, suggest contacting the Registrar.\n\n--- STUDENT DATA ---\n$context";

    $payload = json_encode([
        'model'    => 'llama-3.1-8b-instant',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userMessage]
        ],
        'max_tokens'  => 400,
        'temperature' => 0.5,
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return "Sorry, I'm having trouble connecting right now. Please try again or contact the Registrar at <strong>(032) 326-3677</strong>.";
    }

    $data = json_decode($response, true);
    if (isset($data['choices'][0]['message']['content'])) {
        return markdownToHtml($data['choices'][0]['message']['content']);
    }

    return "I couldn't process that request. Please contact the Registrar at <strong>(032) 326-3677</strong>.";
}

// ── Main ──────────────────────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
$msg   = isset($input['message']) ? trim($input['message']) : '';

if ($msg === '') {
    echo json_encode(['reply' => 'Please type a message.']);
    exit();
}

$data    = getStudentData($conn, $user_id);
$routes  = getRoutes($conn);
$courses = getCourses($conn);

// Try direct answer first (fast, no API call)
$reply = directAnswer($msg, $data, $routes, $courses);

// If no direct match, use Groq AI
if ($reply === null) {
    $context = buildContext($data, $routes, $courses);
    $reply   = askGroq($msg, $context);
}

echo json_encode(['reply' => $reply]);
$conn->close();
?>
