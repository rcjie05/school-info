<?php
/**
 * DIAGNOSTIC PAGE - DELETE AFTER DEBUGGING
 * Visit this page as a logged-in student to see what the dashboard API returns.
 * It also tests the theme save API.
 */
require_once '../../php/config.php';
if (!isLoggedIn()) { die("Not logged in. Please login first."); }
?>
<!DOCTYPE html>
<html>
<head><title>Dashboard Debug</title>
<style>body{font-family:monospace;padding:20px;} pre{background:#f0f0f0;padding:10px;overflow-x:auto;} .ok{color:green;font-weight:bold;} .fail{color:red;font-weight:bold;}</style>
</head>
<body>
<h1>Dashboard Debug Tool</h1>
<p>User ID: <?= $_SESSION['user_id'] ?> | Role: <?= $_SESSION['role'] ?></p>

<h2>1. Dashboard API Response</h2>
<pre id="dashResult">Loading...</pre>

<h2>2. Theme API - GET (your saved theme)</h2>
<pre id="themeGetResult">Loading...</pre>

<h2>3. Theme API - POST (save 'jade' theme)</h2>
<pre id="themePostResult">Loading...</pre>

<script>
async function run() {
    // Test dashboard API
    try {
        const r = await fetch('../../api/student/get_dashboard_data.php');
        const ct = r.headers.get('content-type');
        document.getElementById('dashResult').textContent = 
            'HTTP Status: ' + r.status + '\n' +
            'Content-Type: ' + ct + '\n\n';
        if (ct && ct.includes('json')) {
            const d = await r.json();
            document.getElementById('dashResult').textContent += JSON.stringify(d, null, 2);
        } else {
            const t = await r.text();
            document.getElementById('dashResult').textContent += 'NON-JSON RESPONSE:\n' + t.substring(0, 500);
        }
    } catch(e) {
        document.getElementById('dashResult').textContent = 'ERROR: ' + e.message;
    }

    // Test theme GET
    try {
        const r = await fetch('../../api/shared/save_theme.php', { credentials: 'same-origin' });
        const ct = r.headers.get('content-type');
        document.getElementById('themeGetResult').textContent = 
            'HTTP Status: ' + r.status + '\n' +
            'Content-Type: ' + ct + '\n\n';
        if (ct && ct.includes('json')) {
            const d = await r.json();
            document.getElementById('themeGetResult').textContent += JSON.stringify(d, null, 2);
        } else {
            const t = await r.text();
            document.getElementById('themeGetResult').textContent += 'NON-JSON:\n' + t.substring(0, 500);
        }
    } catch(e) {
        document.getElementById('themeGetResult').textContent = 'ERROR: ' + e.message;
    }

    // Test theme POST
    try {
        const r = await fetch('../../api/shared/save_theme.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify({theme: 'jade'})
        });
        const ct = r.headers.get('content-type');
        document.getElementById('themePostResult').textContent = 
            'HTTP Status: ' + r.status + '\n' +
            'Content-Type: ' + ct + '\n\n';
        if (ct && ct.includes('json')) {
            const d = await r.json();
            document.getElementById('themePostResult').textContent += JSON.stringify(d, null, 2);
            if (d.success && d.affected > 0) {
                document.getElementById('themePostResult').textContent += '\n\n✅ THEME SAVED SUCCESSFULLY to database!';
            } else if (d.success && d.affected === 0) {
                document.getElementById('themePostResult').textContent += '\n\n⚠️ Execute succeeded but 0 rows affected (theme may already be same value)';
            } else {
                document.getElementById('themePostResult').textContent += '\n\n❌ THEME SAVE FAILED';
            }
        } else {
            const t = await r.text();
            document.getElementById('themePostResult').textContent += 'NON-JSON:\n' + t.substring(0, 500);
        }
    } catch(e) {
        document.getElementById('themePostResult').textContent = 'ERROR: ' + e.message;
    }
}
run();
</script>
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
    <script src="../../js/session-monitor.js"></script>
</body>
</html>
