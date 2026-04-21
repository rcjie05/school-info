<?php
/**
 * Advanced Route Save Debug Script
 * This will test the API endpoint directly and show detailed error messages
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Route API Debug</title></head><body>";
echo "<h1>🔍 Route API Debug Test</h1>";
echo "<pre>";

echo "=== STEP 1: Session Check ===\n";
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "❌ NOT LOGGED IN\n";
    echo "Please login as admin first, then come back to this page.\n";
    echo "\nSession data:\n";
    print_r($_SESSION);
    exit;
}

echo "✅ Logged in\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "Role: " . $_SESSION['role'] . "\n";
echo "Name: " . ($_SESSION['name'] ?? 'N/A') . "\n";

if ($_SESSION['role'] !== 'admin') {
    echo "\n❌ YOU ARE NOT AN ADMIN!\n";
    echo "Current role: " . $_SESSION['role'] . "\n";
    echo "Only admin users can save routes.\n";
    exit;
}

echo "\n=== STEP 2: File Path Check ===\n";
$api_file = __DIR__ . '/php/api/routes.php';
echo "Looking for API file at: $api_file\n";

if (file_exists($api_file)) {
    echo "✅ API file exists\n";
} else {
    echo "❌ API file NOT FOUND at: $api_file\n";
    echo "\nChecking alternative locations...\n";
    
    $alternatives = [
        __DIR__ . '/api/routes.php',
        dirname(__DIR__) . '/php/api/routes.php',
        '../php/api/routes.php'
    ];
    
    foreach ($alternatives as $alt) {
        if (file_exists($alt)) {
            echo "Found at: $alt\n";
            $api_file = $alt;
        }
    }
}

echo "\n=== STEP 3: Config Path Check ===\n";
$config_paths = [
    __DIR__ . '/php/config.php',
    __DIR__ . '/config.php',
    dirname(__DIR__) . '/php/config.php'
];

$config_found = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        echo "✅ Found config.php at: $path\n";
        require_once $path;

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────
        $config_found = true;
        break;
    }
}

if (!$config_found) {
    echo "❌ config.php NOT FOUND\n";
    echo "Checked locations:\n";
    foreach ($config_paths as $path) {
        echo "- $path\n";
    }
    exit;
}

echo "\n=== STEP 4: Database Connection ===\n";
$conn = getDBConnection();
if (!$conn) {
    echo "❌ Database connection FAILED\n";
    echo "Error: " . mysqli_connect_error() . "\n";
    exit;
}
echo "✅ Database connected\n";

echo "\n=== STEP 5: Test API Call Directly ===\n";

// Simulate API call
$_SERVER['REQUEST_METHOD'] = 'POST';

// Test data
$testRouteData = [
    'name' => 'DEBUG TEST ROUTE ' . date('H:i:s'),
    'description' => 'Testing route save from debug script',
    'startRoom' => 'AVP Office',
    'endRoom' => 'Clinic',
    'waypoints' => [],
    'visibleToStudents' => true
];

echo "Attempting to save test route:\n";
echo "Name: " . $testRouteData['name'] . "\n";
echo "From: " . $testRouteData['startRoom'] . "\n";
echo "To: " . $testRouteData['endRoom'] . "\n";

// Prepare SQL
$stmt = $conn->prepare(
    "INSERT INTO floor_plan_routes (name, description, start_room, end_room, waypoints, visible_to_students, created_by) 
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    echo "\n❌ PREPARE FAILED\n";
    echo "Error: " . $conn->error . "\n";
    exit;
}

$waypoints_json = json_encode($testRouteData['waypoints']);
$visible = $testRouteData['visibleToStudents'] ? 1 : 0;

$stmt->bind_param(
    "sssssii",
    $testRouteData['name'],
    $testRouteData['description'],
    $testRouteData['startRoom'],
    $testRouteData['endRoom'],
    $waypoints_json,
    $visible,
    $_SESSION['user_id']
);

if ($stmt->execute()) {
    $new_id = $conn->insert_id;
    echo "\n✅ TEST ROUTE SAVED SUCCESSFULLY!\n";
    echo "Route ID: $new_id\n";
    
    // Verify it was saved
    $verify = $conn->query("SELECT * FROM floor_plan_routes WHERE id = $new_id");
    if ($verify && $row = $verify->fetch_assoc()) {
        echo "\nVerified in database:\n";
        echo "- Name: " . $row['name'] . "\n";
        echo "- Start: " . $row['start_room'] . "\n";
        echo "- End: " . $row['end_room'] . "\n";
        echo "- Visible to students: " . ($row['visible_to_students'] ? 'Yes' : 'No') . "\n";
        echo "- Created: " . $row['created_at'] . "\n";
    }
    
    // Clean up
    $conn->query("DELETE FROM floor_plan_routes WHERE id = $new_id");
    echo "\n✅ Test route cleaned up\n";
    
} else {
    echo "\n❌ FAILED TO SAVE\n";
    echo "Error: " . $stmt->error . "\n";
    echo "Error Code: " . $stmt->errno . "\n";
}

echo "\n=== STEP 6: Test Fetch API Access ===\n";
echo "Testing if browser can reach routes.php...\n";

?>

<script>
console.log('=== JavaScript Fetch Test ===');

// Test 1: Can we reach the API?
fetch('../php/api/routes.php', {
    credentials: 'include'
})
.then(response => {
    console.log('✅ API responded!');
    console.log('Status:', response.status);
    console.log('Status Text:', response.statusText);
    
    // Log to page
    document.getElementById('fetch-result').innerHTML = 
        '✅ API is reachable<br>' +
        'Status: ' + response.status + ' ' + response.statusText;
    
    return response.text();
})
.then(text => {
    console.log('Response body:', text);
    
    // Try to parse as JSON
    try {
        const data = JSON.parse(text);
        console.log('Parsed JSON:', data);
        document.getElementById('json-result').innerHTML = 
            '✅ JSON Response:<br>' +
            '<code>' + JSON.stringify(data, null, 2) + '</code>';
    } catch (e) {
        console.error('❌ Not valid JSON:', e);
        document.getElementById('json-result').innerHTML = 
            '❌ Response is not JSON:<br>' +
            '<code>' + text.substring(0, 500) + '</code>';
    }
})
.catch(error => {
    console.error('❌ Fetch failed:', error);
    document.getElementById('fetch-result').innerHTML = 
        '❌ Network Error: ' + error.message + '<br>' +
        'This is the error you\'re seeing in the floor plan!';
});

// Test 2: Try saving a route via JavaScript
setTimeout(() => {
    console.log('\n=== Testing Route Save via Fetch ===');
    
    const testRoute = {
        name: 'JavaScript Test Route ' + new Date().toLocaleTimeString(),
        description: 'Testing from JavaScript',
        startRoom: 'AVP Office',
        endRoom: 'Clinic',
        waypoints: [],
        visibleToStudents: true
    };
    
    fetch('../php/api/routes.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(testRoute)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Save response:', data);
        document.getElementById('save-result').innerHTML = 
            (data.success ? '✅' : '❌') + ' Save test: ' + 
            (data.message || 'Unknown result') + '<br>' +
            '<code>' + JSON.stringify(data, null, 2) + '</code>';
        
        // Clean up if successful
        if (data.success && data.id) {
            fetch('../php/api/routes.php', {
                method: 'DELETE',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: data.id })
            })
            .then(() => console.log('Test route cleaned up'));
        }
    })
    .catch(error => {
        console.error('Save failed:', error);
        document.getElementById('save-result').innerHTML = 
            '❌ Save failed: ' + error.message;
    });
}, 1000);
</script>

<?php
echo "\n=== STEP 7: JavaScript Fetch Results ===\n";
echo "(Check results below - they will appear after a moment)\n";
echo "</pre>";

echo "<h2>Fetch API Test Results:</h2>";
echo "<div id='fetch-result' style='background:#f0f0f0;padding:10px;margin:10px 0;'>Testing...</div>";
echo "<div id='json-result' style='background:#f0f0f0;padding:10px;margin:10px 0;'>Waiting for response...</div>";
echo "<div id='save-result' style='background:#f0f0f0;padding:10px;margin:10px 0;'>Testing route save...</div>";

echo "<h2>Summary:</h2>";
echo "<pre>";
echo "If you see '✅ API is reachable' above, the path is correct.\n";
echo "If you see '❌ Network Error', there's a path or CORS issue.\n";
echo "\nCheck the browser console (F12) for detailed error messages.\n";
echo "</pre>";

echo "    <script src="./js/theme-switcher.js"></script>
</body></html>";

$conn->close();
?>
