<?php
require_once '../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
requireRole('student');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../../../public/images/logo2.jpg">
    <link rel="apple-touch-icon" href="../../../public/images/logo2.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & FAQ - Student Portal</title>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/mobile-fix.css">
    <link rel="stylesheet" href="../../../public/css/themes.css">
    <style>
        .chat-wrapper {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 130px);
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        /* Quick topic chips */
        .topic-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f1f1;
            background: #fafafa;
        }
        .topic-chip {
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            border: 1.5px solid #e5e7eb;
            background: white;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .topic-chip:hover {
            border-color: var(--primary-purple);
            color: var(--primary-purple);
            background: #f5f3ff;
        }

        /* Messages area */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .chat-messages::-webkit-scrollbar { width: 4px; }
        .chat-messages::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }

        /* Message bubbles */
        .msg {
            display: flex;
            gap: 0.65rem;
            align-items: flex-end;
            max-width: 78%;
        }
        .msg.user { align-self: flex-end; flex-direction: row-reverse; }
        .msg.bot  { align-self: flex-start; }

        .msg-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .msg.bot  .msg-avatar { background: linear-gradient(135deg, var(--primary-purple), var(--secondary-pink)); }
        .msg.user .msg-avatar { background: #e5e7eb; }

        .msg-bubble {
            padding: 0.75rem 1rem;
            border-radius: 18px;
            font-size: 0.9rem;
            line-height: 1.55;
            max-width: 100%;
        }
        .msg.bot  .msg-bubble {
            background: #f3f4f6;
            color: var(--text-primary);
            border-bottom-left-radius: 4px;
        }
        .msg.user .msg-bubble {
            background: linear-gradient(135deg, var(--primary-purple), var(--secondary-pink));
            color: white;
            border-bottom-right-radius: 4px;
        }
        .msg-bubble ul {
            margin: 0.5rem 0 0;
            padding-left: 1.25rem;
        }
        .msg-bubble ul li { margin-bottom: 0.25rem; }
        .msg-bubble a {
            color: inherit;
            text-decoration: underline;
            opacity: 0.85;
        }

        /* Typing indicator */
        .typing-indicator .msg-bubble {
            background: #f3f4f6;
            padding: 0.75rem 1rem;
        }
        .typing-dots { display: flex; gap: 4px; align-items: center; height: 18px; }
        .typing-dots span {
            width: 7px; height: 7px;
            background: #9ca3af;
            border-radius: 50%;
            animation: bounce 1.2s infinite ease-in-out;
        }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-6px); }
        }

        /* Input bar */
        .chat-input-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-top: 1px solid #f1f1f1;
            background: white;
        }
        .chat-input {
            flex: 1;
            padding: 0.7rem 1.1rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 999px;
            font-size: 0.925rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }
        .chat-input:focus { border-color: var(--primary-purple); }
        .chat-send-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, var(--primary-purple), var(--secondary-pink));
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: opacity 0.2s;
        }
        .chat-send-btn:hover { opacity: 0.88; }
        .chat-send-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        /* Bot header bar */
        .bot-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f1f1;
        }
        .bot-header-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-purple), var(--secondary-pink));
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
        }
        .bot-header-info { flex: 1; }
        .bot-header-name { font-weight: 700; font-size: 0.95rem; color: var(--text-primary); }
        .bot-header-status { font-size: 0.78rem; color: #10b981; display: flex; align-items: center; gap: 4px; }
        .status-dot { width: 7px; height: 7px; background: #10b981; border-radius: 50%; }
        .btn-clear {
            padding: 0.4rem 0.9rem;
            border-radius: 999px;
            border: 1.5px solid #e5e7eb;
            background: white;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-clear:hover { border-color: #ef4444; color: #ef4444; }
    </style>
</head>
<body>
<div class="page-wrapper">
                <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <img src="../../../public/images/logo2.jpg" alt="SCC Logo" id="sidebarLogoImg" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-md);">
                </div>
                <div class="logo-text">
                    <span id="sidebarSchoolName"><?= htmlspecialchars($school_name) ?></span>
                    <span>Student Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span>Dashboard</span></a>
                    <a href="schedule.php" class="nav-item"><span class="nav-icon">📅</span><span>My Schedule</span></a>
                    <a href="subjects.php" class="nav-item"><span class="nav-icon">📚</span><span>Study Load</span></a>
                    <a href="grades.php" class="nav-item"><span class="nav-icon">🎓</span><span>Grades</span></a>
                    <a href="calendar.php" class="nav-item"><span class="nav-icon">🗓️</span><span>Calendar</span></a>
                    <a href="floorplan.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Floor Plan</span></a>
                    <a href="faculty.php" class="nav-item"><span class="nav-icon">👨‍🏫</span><span>Faculty Directory</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Support</div>
                    <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>Announcements</span></a>
                    <a href="feedback.php" class="nav-item"><span class="nav-icon">💬</span><span>Feedback</span></a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Account</div>
                    <a href="profile.php" class="nav-item"><span class="nav-icon">👤</span><span>My Profile</span></a>
                    <a href="../../php/logout.php" class="nav-item"><span class="nav-icon">🚪</span><span>Logout</span></a>
                </div>
            </nav>
        </aside>

    <main class="main-content">
        <header class="page-header">
            <div class="header-title">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button>
                    <h1>Help & FAQ</h1>
                <p class="header-subtitle">Ask anything about enrollment, grades, schedules, and more</p>
            </div>
        </header>

        <div class="chat-wrapper">
            <!-- Bot header -->
            <div class="bot-header">
                <div class="bot-header-avatar">🤖</div>
                <div class="bot-header-info">
                    <div class="bot-header-name">OL Assistant</div>
                    <div class="bot-header-status"><span class="status-dot"></span> Online</div>
                </div>
                <button class="btn-clear" onclick="clearChat()">🗑 Clear chat</button>
            </div>

            <!-- Quick topic chips -->
            <div class="topic-chips">
                <button class="topic-chip" onclick="sendQuick('How do I enroll?')">📋 Enrollment</button>
                <button class="topic-chip" onclick="sendQuick('What are the enrollment requirements?')">📄 Requirements</button>
                <button class="topic-chip" onclick="sendQuick('How do I check my grades?')">🎓 Grades</button>
                <button class="topic-chip" onclick="sendQuick('How do I view my schedule?')">📅 Schedule</button>
                <button class="topic-chip" onclick="sendQuick('What are the school fees?')">💰 Tuition & Fees</button>
                <button class="topic-chip" onclick="sendQuick('How do I contact my professor?')">👨‍🏫 Faculty</button>
                <button class="topic-chip" onclick="sendQuick('What are the important school dates?')">📆 School Calendar</button>
                <button class="topic-chip" onclick="sendQuick('How do I submit feedback?')">💬 Feedback</button>
            </div>

            <!-- Messages -->
            <div class="chat-messages" id="chatMessages">
                <!-- Welcome message -->
                <div class="msg bot">
                    <div class="msg-avatar">🤖</div>
                    <div class="msg-bubble">
                        Hi there! 👋 I'm the <strong>OL Assistant</strong>, your school help guide.<br><br>
                        I can answer questions about enrollment, grades, schedules, school fees, and more. Pick a topic above or just type your question below!
                    </div>
                </div>
            </div>

            <!-- Input bar -->
            <div class="chat-input-bar">
                <input type="text" class="chat-input" id="chatInput" placeholder="Ask me anything…" autocomplete="off" />
                <button class="chat-send-btn" id="sendBtn" onclick="sendMessage()">➤</button>
            </div>
        </div>
    </main>
</div>

<script>
// ─── Knowledge Base ───────────────────────────────────────────────────────────
const KB = [
    {
        patterns: ['enroll', 'enrollment', 'how to enroll', 'register', 'sign up', 'admission'],
        answer: `Here's how to enroll at <?= htmlspecialchars($school_name) ?>:
<ul>
<li><strong>Step 1 – Pre-enrollment:</strong> Fill out the online pre-enrollment form on the school website or visit the registrar's office.</li>
<li><strong>Step 2 – Submit requirements</strong> (see the Requirements topic).</li>
<li><strong>Step 3 – Assessment:</strong> Proceed to the cashier for tuition assessment.</li>
<li><strong>Step 4 – Payment:</strong> Pay the required fees (downpayment accepted).</li>
<li><strong>Step 5 – Confirmation:</strong> Receive your enrollment confirmation and student ID.</li>
</ul>
For assistance, visit the Registrar's Office (Room 101) or call <strong>(032) 555-0100</strong>.`
    },
    {
        patterns: ['requirement', 'documents', 'needed', 'bring', 'submit', 'papers'],
        answer: `The following documents are required for enrollment:
<ul>
<li>📄 Original and photocopy of Form 138 (Report Card)</li>
<li>📄 PSA Birth Certificate (original + 2 photocopies)</li>
<li>📸 2×2 ID photos (4 pieces, white background)</li>
<li>📋 Accomplished enrollment form</li>
<li>💳 Valid government-issued ID or Barangay Certificate</li>
<li>📄 Certificate of Good Moral Character (for transferees)</li>
<li>📄 Transcript of Records (for transferees or college students)</li>
</ul>
<strong>Note:</strong> Original documents must be presented for verification.`
    },
    {
        patterns: ['grade', 'check grade', 'view grade', 'gpa', 'grade report', 'how do i check'],
        answer: `To check your grades:
<ul>
<li>Go to <a href="grades.php">🎓 Grades</a> from the sidebar menu.</li>
<li>Your grades are shown per subject with midterm and final scores.</li>
<li>Overall GPA is computed and displayed at the top.</li>
</ul>
If you notice a discrepancy, contact your subject teacher or visit the Registrar's Office within <strong>7 days</strong> of grade posting.`
    },
    {
        patterns: ['schedule', 'timetable', 'class schedule', 'when is my class', 'view schedule'],
        answer: `To view your class schedule:
<ul>
<li>Click <a href="schedule.php">📅 My Schedule</a> in the sidebar.</li>
<li>Your schedule is displayed as a weekly timetable (7 AM – 9 PM).</li>
<li>You can print it using the 🖨️ Print button.</li>
</ul>
For changes to your schedule, you must submit a <strong>change of schedule form</strong> to the Registrar within the first 2 weeks of the semester.`
    },
    {
        patterns: ['fee', 'tuition', 'payment', 'how much', 'cost', 'price', 'downpayment', 'installment'],
        answer: `Tuition and fee information:
<ul>
<li><strong>BSIT:</strong> ₱18,500 per semester (approx.)</li>
<li><strong>BSHTM:</strong> ₱16,000 per semester (approx.)</li>
<li>Miscellaneous fees: ₱2,500–₱3,500 depending on course</li>
</ul>
Payment options:
<ul>
<li>💵 Full payment (5% discount applies)</li>
<li>📆 Installment: 50% downpayment + 2 installments</li>
<li>🏦 GCash, bank transfer, or over-the-counter at the cashier</li>
</ul>
For exact amounts, visit the <strong>Cashier's Office (Room 102)</strong>.`
    },
    {
        patterns: ['professor', 'teacher', 'faculty', 'contact teacher', 'instructor', 'email teacher'],
        answer: `To find and contact faculty members:
<ul>
<li>Go to <a href="faculty.php">👨‍🏫 Faculty Directory</a> from the sidebar.</li>
<li>Search by name, department, or subject.</li>
<li>Faculty emails and consultation schedules are listed there.</li>
</ul>
You may also visit the <strong>Faculty Room (Room 201)</strong> during office hours, typically <strong>7:30 AM – 5:00 PM, Monday–Friday</strong>.`
    },
    {
        patterns: ['calendar', 'school date', 'holiday', 'semester', 'midterm', 'final exam', 'exam week', 'schedule of exam'],
        answer: `Important school dates for AY 2024–2025:
<ul>
<li>📅 <strong>1st Semester:</strong> August 12 – December 20, 2024</li>
<li>📅 <strong>Midterm Exams:</strong> October 7–11, 2024</li>
<li>📅 <strong>Final Exams:</strong> December 9–13, 2024</li>
<li>📅 <strong>2nd Semester:</strong> January 13 – May 23, 2025</li>
<li>📅 <strong>Midterm Exams:</strong> March 3–7, 2025</li>
<li>📅 <strong>Final Exams:</strong> May 12–16, 2025</li>
</ul>
Check the <a href="announcements.php">📢 Announcements</a> page for the latest updates.`
    },
    {
        patterns: ['feedback', 'complaint', 'suggestion', 'concern', 'report', 'how to submit'],
        answer: `To submit feedback or concerns:
<ul>
<li>Go to <a href="feedback.php">💬 Feedback</a> from the sidebar.</li>
<li>Fill in the subject and your message.</li>
<li>You can track the status of your feedback (Pending, In Progress, Resolved).</li>
</ul>
For urgent concerns, you may also visit the <strong>Guidance Office (Room 105)</strong> or the <strong>Dean's Office (Room 201)</strong>.`
    },
    {
        patterns: ['id', 'student id', 'lost id', 'replace id', 'identification'],
        answer: `For student ID concerns:
<ul>
<li><strong>First issuance:</strong> Included in enrollment. Claim at the Registrar after 3–5 working days.</li>
<li><strong>Lost/damaged ID:</strong> Submit a written request at the Registrar with an Affidavit of Loss.</li>
<li><strong>Replacement fee:</strong> ₱150</li>
</ul>
Processing time is <strong>3–5 working days</strong> from submission.`
    },
    {
        patterns: ['withdraw', 'withdrawal', 'dropping', 'drop subject', 'leave of absence', 'loa'],
        answer: `For withdrawal or dropping of subjects:
<ul>
<li>Get a <strong>withdrawal/dropping form</strong> from the Registrar's Office.</li>
<li>Have it signed by your subject teacher, Dean, and Registrar.</li>
<li>Submit within the <strong>official dropping period</strong> (first 4 weeks of semester).</li>
<li>Late dropping may result in a grade of <strong>W (Withdrawn)</strong> on your record.</li>
</ul>
For Leave of Absence (LOA), submit a letter of request to the Dean's Office at least <strong>2 weeks in advance</strong>.`
    },
    {
        patterns: ['scholarship', 'scholar', 'financial aid', 'discount', 'subsidy'],
        answer: `Available scholarships and financial assistance:
<ul>
<li>🏛 <strong>Government:</strong> CHED UniFAST, TESDA, DSWD Scholarship</li>
<li>🏫 <strong>School-based:</strong> Academic Excellence Award (top 3 per course per year)</li>
<li>🏅 <strong>Athletic/Special talent</strong> scholarships</li>
<li>💼 <strong>Working student program</strong> (discounted tuition in exchange for service hours)</li>
</ul>
Visit the <strong>Scholarship Office (Room 103)</strong> or ask at the Registrar for requirements and application deadlines.`
    },
    {
        patterns: ['wifi', 'internet', 'password', 'network', 'connect'],
        answer: `School Wi-Fi access:
<ul>
<li>Network: <strong>OL-SmartSchool-Student</strong></li>
<li>Password: Request at the IT Department (Room 301) with your student ID.</li>
<li>Wi-Fi is available in all classrooms, library, and canteen areas.</li>
</ul>
For connectivity issues, contact the <strong>IT Support Desk (Room 301)</strong>, open Monday–Friday, 8 AM–5 PM.`
    },
    {
        patterns: ['library', 'book', 'borrow', 'librar'],
        answer: `Library services:
<ul>
<li>📚 Located at the <strong>2nd floor, Building B</strong></li>
<li>Hours: <strong>Monday–Friday, 7:30 AM – 6:00 PM</strong></li>
<li>Students may borrow up to <strong>3 books</strong> for 3 days.</li>
<li>Overdue fine: <strong>₱5 per book per day</strong></li>
<li>E-library access is available via your student portal credentials.</li>
</ul>`
    },
    {
        patterns: ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening', 'sup', 'start'],
        answer: `Hello! 👋 How can I help you today? Feel free to ask about:
<ul>
<li>📋 Enrollment process & requirements</li>
<li>🎓 Grades and schedules</li>
<li>💰 Tuition fees and scholarships</li>
<li>👨‍🏫 Faculty and contacts</li>
<li>📆 School calendar</li>
</ul>
Just type your question or tap a topic button at the top!`
    },
    {
        patterns: ['thank', 'thanks', 'salamat', 'ty'],
        answer: `You're welcome! 😊 Is there anything else I can help you with? Don't hesitate to ask!`
    },
    {
        patterns: ['bye', 'goodbye', 'see you', 'ok bye'],
        answer: `Take care! 👋 Feel free to come back anytime you have questions. Good luck with your studies! 🎓`
    }
];

// ─── Matching ─────────────────────────────────────────────────────────────────
function findAnswer(input) {
    const lower = input.toLowerCase().trim();
    for (const entry of KB) {
        for (const pattern of entry.patterns) {
            if (lower.includes(pattern)) return entry.answer;
        }
    }
    return null;
}

// ─── DOM helpers ──────────────────────────────────────────────────────────────
function scrollToBottom() {
    const msgs = document.getElementById('chatMessages');
    msgs.scrollTop = msgs.scrollHeight;
}

function appendMessage(type, html) {
    const msgs = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = 'msg ' + type;
    const icon = type === 'bot' ? '🤖' : '👤';
    div.innerHTML = `<div class="msg-avatar">${icon}</div><div class="msg-bubble">${html}</div>`;
    msgs.appendChild(div);
    scrollToBottom();
    return div;
}

function showTyping() {
    const msgs = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = 'msg bot typing-indicator';
    div.id = 'typingIndicator';
    div.innerHTML = `<div class="msg-avatar">🤖</div><div class="msg-bubble"><div class="typing-dots"><span></span><span></span><span></span></div></div>`;
    msgs.appendChild(div);
    scrollToBottom();
}

function removeTyping() {
    const el = document.getElementById('typingIndicator');
    if (el) el.remove();
}

// ─── Core send ────────────────────────────────────────────────────────────────
function sendMessage() {
    const input = document.getElementById('chatInput');
    const text = input.value.trim();
    if (!text) return;

    input.value = '';
    document.getElementById('sendBtn').disabled = true;

    appendMessage('user', escapeHtml(text));
    showTyping();

    // Simulate a short delay
    setTimeout(() => {
        removeTyping();

        const answer = findAnswer(text);
        if (answer) {
            appendMessage('bot', answer);
        } else {
            appendMessage('bot', `I'm sorry, I don't have information on that yet. 😕<br><br>
For specific concerns, you can:
<ul>
<li>Visit the <strong>Registrar's Office (Room 101)</strong></li>
<li>Submit a concern via <a href="feedback.php">💬 Feedback</a></li>
<li>Call the school at <strong>(032) 555-0100</strong></li>
</ul>`);
        }

        document.getElementById('sendBtn').disabled = false;
        document.getElementById('chatInput').focus();
    }, 700 + Math.random() * 400);
}

function sendQuick(text) {
    document.getElementById('chatInput').value = text;
    sendMessage();
}

function clearChat() {
    if (!confirm('Clear the chat history?')) return;
    const msgs = document.getElementById('chatMessages');
    msgs.innerHTML = '';
    appendMessage('bot', `Chat cleared! 🗑️ How can I help you? Feel free to type your question or tap a topic above.`);
}

function escapeHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Enter key support
document.getElementById('chatInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});
</script>
    <script src="../../../public/js/theme-switcher.js"></script>
    <script>
    (function() {
        var toggle   = document.getElementById('sidebarToggle');
        var sidebar  = document.querySelector('.sidebar');
        var overlay  = document.getElementById('sidebarOverlay');
        if (!toggle || !sidebar) return;

        function openSidebar() {
            sidebar.classList.add('active');
            overlay && overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            sidebar.classList.remove('active');
            overlay && overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        toggle.addEventListener('click', function() {
            sidebar.classList.contains('active') ? closeSidebar() : openSidebar();
        });
        overlay && overlay.addEventListener('click', closeSidebar);

        // Close sidebar when a nav link is clicked (mobile UX)
        document.querySelectorAll('.nav-item').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 1024) closeSidebar();
            });
        });
    })();
    </script>
    <script src="../../../public/js/session-monitor.js"></script>
    <script src="../../../public/js/apply-branding.js"></script>

<!-- ── Global Search Overlay ─────────────────────────────────────── -->
<style>
.global-search-btn {
    background: var(--background-card, #fff);
    border: 1.5px solid var(--border-color, #e2e8f0);
    border-radius: var(--radius-md, 8px);
    width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: var(--text-secondary, #64748b);
    transition: all .2s; flex-shrink: 0;
}
.global-search-btn:hover {
    background: var(--primary-purple, #3D6B9F);
    color: #fff; border-color: var(--primary-purple, #3D6B9F);
    transform: scale(1.05);
}
.gs-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(10,20,40,0.55); backdrop-filter: blur(6px);
    z-index: 99999; align-items: flex-start; justify-content: center;
    padding-top: clamp(3rem, 10vh, 6rem);
}
.gs-overlay.open { display: flex; animation: gsFadeIn .18s ease; }
@keyframes gsFadeIn { from { opacity:0; } to { opacity:1; } }
.gs-box {
    background: var(--background-card, #fff);
    border-radius: 16px;
    box-shadow: 0 24px 80px rgba(0,0,0,0.28);
    width: min(640px, calc(100vw - 2rem));
    max-height: 70vh; display: flex; flex-direction: column;
    overflow: hidden; animation: gsSlideIn .2s ease;
}
@keyframes gsSlideIn { from { opacity:0; transform:translateY(-16px) scale(.97); } to { opacity:1; transform:translateY(0) scale(1); } }
.gs-input-wrap {
    display: flex; align-items: center; gap: .75rem;
    padding: 1rem 1.25rem; border-bottom: 1.5px solid var(--border-color, #e2e8f0);
    flex-shrink: 0;
}
.gs-input-wrap svg { color: var(--text-secondary, #64748b); flex-shrink:0; }
.gs-input {
    flex: 1; border: none; outline: none; background: transparent;
    font-size: 1.05rem; color: var(--text-primary, #1C2C42);
    font-family: inherit;
}
.gs-input::placeholder { color: var(--text-secondary, #94a3b8); }
.gs-close {
    background: var(--background-page, #f8fafc); border: 1.5px solid var(--border-color, #e2e8f0);
    border-radius: 6px; padding: .2rem .5rem; font-size: .72rem;
    color: var(--text-secondary, #64748b); cursor: pointer; flex-shrink:0;
    font-family: inherit; transition: all .15s;
}
.gs-close:hover { background: var(--border-color, #e2e8f0); }
.gs-results {
    overflow-y: auto; flex: 1; padding: .5rem 0;
    scrollbar-width: thin;
}
.gs-section-label {
    font-size: .65rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 1px; color: var(--text-secondary, #94a3b8);
    padding: .6rem 1.25rem .3rem; margin-top: .25rem;
}
.gs-item {
    display: flex; align-items: center; gap: .85rem;
    padding: .7rem 1.25rem; cursor: pointer; text-decoration: none;
    transition: background .13s; border-radius: 0;
}
.gs-item:hover, .gs-item.active {
    background: var(--background-hover, #f1f5f9);
}
.gs-icon {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; background: var(--background-page, #f8fafc);
}
.gs-item-text { flex: 1; min-width: 0; }
.gs-item-title { font-size: .88rem; font-weight: 600; color: var(--text-primary, #1C2C42); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gs-item-sub { font-size: .75rem; color: var(--text-secondary, #64748b); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gs-arrow { color: var(--text-secondary, #cbd5e1); flex-shrink: 0; }
.gs-empty { text-align: center; padding: 2.5rem 1rem; color: var(--text-secondary, #94a3b8); font-size: .9rem; }
.gs-footer {
    border-top: 1.5px solid var(--border-color, #e2e8f0);
    padding: .6rem 1.25rem; display: flex; gap: 1rem; flex-shrink: 0;
    align-items: center;
}
.gs-hint { font-size: .68rem; color: var(--text-secondary, #94a3b8); display: flex; align-items: center; gap: .3rem; }
.gs-hint kbd {
    background: var(--background-page, #f1f5f9); border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 4px; padding: .1rem .35rem; font-size: .65rem;
    font-family: inherit; color: var(--text-secondary, #64748b);
}
mark.gs-hl { background: rgba(61,107,159,.15); color: var(--primary-purple, #3D6B9F); border-radius: 3px; padding: 0 2px; font-style: normal; }
</style>

<!-- Search Overlay HTML -->
<div class="gs-overlay" id="gsOverlay" role="dialog" aria-modal="true" aria-label="Global Search">
    <div class="gs-box" id="gsBox">
        <div class="gs-input-wrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input class="gs-input" id="gsInput" type="text" placeholder="Search pages, subjects, grades, announcements…" autocomplete="off" spellcheck="false">
            <button class="gs-close" id="gsCloseBtn">ESC</button>
        </div>
        <div class="gs-results" id="gsResults"></div>
        <div class="gs-footer">
            <span class="gs-hint"><kbd>↑</kbd><kbd>↓</kbd> navigate</span>
            <span class="gs-hint"><kbd>↵</kbd> open</span>
            <span class="gs-hint"><kbd>ESC</kbd> close</span>
        </div>
    </div>
</div>

<script>
(function() {
    // ── Static page index ──────────────────────────────────────────────
    const PAGES = [
        { title: 'Dashboard',        url: 'dashboard.php',      icon: '🏠', sub: 'Home overview' },
        { title: 'My Schedule',      url: 'schedule.php',       icon: '📅', sub: 'Class timetable' },
        { title: 'Study Load',       url: 'subjects.php',       icon: '📚', sub: 'Enrolled subjects' },
        { title: 'Grades',           url: 'grades.php',         icon: '📊', sub: 'Academic performance' },
        { title: 'Calendar',         url: 'calendar.php',       icon: '🗓️', sub: 'Academic calendar & events' },
        { title: 'Floor Plan',       url: 'floorplan.php',      icon: '🗺️', sub: 'Campus map & rooms' },
        { title: 'Faculty Directory',url: 'faculty.php',        icon: '👩‍🏫', sub: 'Teachers & staff' },
        { title: 'Announcements',    url: 'announcements.php',  icon: '📢', sub: 'School announcements' },
        { title: 'Feedback',         url: 'feedback.php',       icon: '💬', sub: 'Submit feedback' },
        { title: 'Profile',          url: 'profile.php',        icon: '👤', sub: 'My account & settings' },
        { title: 'Chatbot',          url: 'chatbot.php',        icon: '🤖', sub: 'AI assistant' },
    ];

    // ── Dynamic data cache ─────────────────────────────────────────────
    let dynData = [];
    let dynLoaded = false;

    async function loadDynamic() {
        if (dynLoaded) return;
        dynLoaded = true;
        try {
            const [gradesRes, subjectsRes, announcementsRes] = await Promise.allSettled([
                fetch('../../api/student/get_grades.php').then(r => r.json()),
                fetch('../../api/student/get_study_load.php').then(r => r.json()),
                fetch('../../api/student/get_announcements.php').then(r => r.json()),
            ]);

            if (gradesRes.status === 'fulfilled' && gradesRes.value?.grades) {
                gradesRes.value.grades.forEach(g => {
                    dynData.push({
                        icon: '📊', section: 'Grades',
                        title: g.subject_name || g.subject_code,
                        sub: `Grade: ${g.final_grade ?? g.midterm_grade ?? 'No grade yet'} · ${g.subject_code || ''}`,
                        url: 'grades.php'
                    });
                });
            }
            if (subjectsRes.status === 'fulfilled' && subjectsRes.value?.subjects) {
                subjectsRes.value.subjects.forEach(s => {
                    dynData.push({
                        icon: '📚', section: 'Subjects',
                        title: s.subject_name || s.name,
                        sub: `${s.subject_code || ''} · ${s.units || ''} units · ${s.teacher_name || ''}`,
                        url: 'subjects.php'
                    });
                });
            }
            if (announcementsRes.status === 'fulfilled' && announcementsRes.value?.announcements) {
                announcementsRes.value.announcements.forEach(a => {
                    dynData.push({
                        icon: '📢', section: 'Announcements',
                        title: a.title,
                        sub: a.date || '',
                        url: 'announcements.php'
                    });
                });
            }
        } catch(e) {}
    }

    // ── Search logic ───────────────────────────────────────────────────
    function highlight(text, query) {
        if (!query) return escHtml(text);
        const escaped = escHtml(text);
        const re = new RegExp('(' + escRegex(query) + ')', 'gi');
        return escaped.replace(re, '<mark class="gs-hl">$1</mark>');
    }
    function escHtml(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function escRegex(s) {
        return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function search(query) {
        const q = query.trim().toLowerCase();
        const results = [];

        // Pages
        const pageMatches = PAGES.filter(p =>
            p.title.toLowerCase().includes(q) ||
            p.sub.toLowerCase().includes(q)
        );
        if (pageMatches.length) {
            results.push({ type: 'section', label: 'Pages' });
            pageMatches.forEach(p => results.push({ type: 'item', ...p, section: 'Pages' }));
        }

        // Dynamic data
        if (q.length >= 2) {
            const groups = {};
            dynData.forEach(d => {
                if (d.title.toLowerCase().includes(q) || d.sub.toLowerCase().includes(q)) {
                    if (!groups[d.section]) groups[d.section] = [];
                    groups[d.section].push(d);
                }
            });
            Object.entries(groups).forEach(([sec, items]) => {
                results.push({ type: 'section', label: sec });
                items.slice(0, 5).forEach(i => results.push({ type: 'item', ...i }));
            });
        }

        return results;
    }

    function renderResults(query) {
        const results = query ? search(query) : getDefaults();
        const container = document.getElementById('gsResults');

        if (!results.length) {
            container.innerHTML = '<div class="gs-empty">No results for <strong>"' + escHtml(query) + '"</strong></div>';
            activeIdx = -1;
            return;
        }

        container.innerHTML = results.map((r, i) => {
            if (r.type === 'section') {
                return `<div class="gs-section-label">${escHtml(r.label)}</div>`;
            }
            const q = query.trim();
            return `<a class="gs-item" href="${escHtml(r.url)}" data-idx="${i}">
                <div class="gs-icon">${r.icon}</div>
                <div class="gs-item-text">
                    <div class="gs-item-title">${highlight(r.title, q)}</div>
                    ${r.sub ? `<div class="gs-item-sub">${highlight(r.sub, q)}</div>` : ''}
                </div>
                <svg class="gs-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </a>`;
        }).join('');

        activeIdx = -1;
    }

    function getDefaults() {
        const defaults = [];
        defaults.push({ type: 'section', label: 'Quick Access' });
        PAGES.slice(0, 6).forEach(p => defaults.push({ type: 'item', ...p }));
        return defaults;
    }

    // ── Keyboard navigation ────────────────────────────────────────────
    let activeIdx = -1;

    function getItems() {
        return Array.from(document.querySelectorAll('#gsResults .gs-item'));
    }

    function setActive(idx) {
        const items = getItems();
        if (!items.length) return;
        items.forEach(i => i.classList.remove('active'));
        activeIdx = Math.max(0, Math.min(idx, items.length - 1));
        items[activeIdx].classList.add('active');
        items[activeIdx].scrollIntoView({ block: 'nearest' });
    }

    // ── Open / Close ───────────────────────────────────────────────────
    const overlay = document.getElementById('gsOverlay');
    const input   = document.getElementById('gsInput');

    function openSearch() {
        overlay.classList.add('open');
        input.value = '';
        renderResults('');
        setTimeout(() => input.focus(), 50);
        loadDynamic();
    }

    function closeSearch() {
        overlay.classList.remove('open');
        activeIdx = -1;
    }

    // Trigger button
    const btn = document.getElementById('globalSearchBtn');
    if (btn) btn.addEventListener('click', openSearch);

    // Close button
    document.getElementById('gsCloseBtn').addEventListener('click', closeSearch);

    // Click outside to close
    overlay.addEventListener('click', e => { if (e.target === overlay) closeSearch(); });

    // Keyboard shortcut: Ctrl+K or /
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); openSearch(); }
        if (e.key === 'Escape' && overlay.classList.contains('open')) closeSearch();
        if (overlay.classList.contains('open')) {
            if (e.key === 'ArrowDown') { e.preventDefault(); setActive(activeIdx + 1); }
            if (e.key === 'ArrowUp')   { e.preventDefault(); setActive(activeIdx - 1); }
            if (e.key === 'Enter') {
                const items = getItems();
                if (activeIdx >= 0 && items[activeIdx]) {
                    items[activeIdx].click();
                }
            }
        }
    });

    // Input handler
    input.addEventListener('input', e => {
        renderResults(e.target.value);
    });

})();
</script>


    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
      <a href="dashboard.php" class="mobile-nav-item" data-page="dashboard">
        <span class="mobile-nav-icon">📊</span><span>Home</span>
      </a>
      <a href="schedule.php" class="mobile-nav-item" data-page="schedule">
        <span class="mobile-nav-icon">📅</span><span>Schedule</span>
      </a>
      <a href="grades.php" class="mobile-nav-item" data-page="grades">
        <span class="mobile-nav-icon">🎓</span><span>Grades</span>
      </a>
      <a href="announcements.php" class="mobile-nav-item" data-page="announcements">
        <span class="mobile-nav-icon">📢</span><span>Notices</span>
      </a>
      <a href="profile.php" class="mobile-nav-item" data-page="profile">
        <span class="mobile-nav-icon">👤</span><span>Profile</span>
      </a>
    </nav>

    <script>
    // Auto-highlight mobile bottom nav item
    (function() {
      var page = location.pathname.split('/').pop().replace('.php','');
      document.querySelectorAll('.mobile-nav-item').forEach(function(el) {
        if (el.dataset.page === page) el.classList.add('active');
      });
    })();
    </script>

</body>
</html>
