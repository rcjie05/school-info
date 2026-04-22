<?php
/**
 * Floor Plan Routes API
 * Handles CRUD operations for floor plan navigation routes
 */

header('Content-Type: application/json');

// Start session and include dependencies
require_once __DIR__ . '/../../php/config.php';

// ── Dynamic school name & school year ────────────────────────────────
$_sn_conn = getDBConnection();
$_sn_res  = $_sn_conn ? $_sn_conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name','current_school_year')") : false;
$school_name = 'My School';
$current_school_year = '----';
if ($_sn_res) { while ($_sn_row = $_sn_res->fetch_assoc()) { if ($_sn_row['setting_key']==='school_name') $school_name=$_sn_row['setting_value']; if ($_sn_row['setting_key']==='current_school_year') $current_school_year=$_sn_row['setting_value']; } }
// ──────────────────────────────────────────────────────────────────────

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all routes (admin sees all, others see only public routes)
            handleGetRoutes($conn, $user_role);
            break;
            
        case 'POST':
            // Create or update a route (admin only)
            handleSaveRoute($conn, $user_id, $user_role);
            break;
            
        case 'DELETE':
            // Delete a route (admin only)
            handleDeleteRoute($conn, $user_id, $user_role);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("Floor plan routes API error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

$conn->close();

/**
 * Get routes based on user role
 */
function handleGetRoutes($conn, $user_role) {
    if ($user_role === 'admin') {
        // Admin sees all routes
        $query = "SELECT id, name, description, start_room, end_room, waypoints, 
                         visible_to_students, created_by, created_at, updated_at 
                  FROM floor_plan_routes 
                  ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
    } else {
        // Other users see only public routes
        // Note: Using = 1 instead of = TRUE for MySQL compatibility
        $query = "SELECT id, name, description, start_room, end_room, waypoints, 
                         visible_to_students, created_at 
                  FROM floor_plan_routes 
                  WHERE visible_to_students = 1 
                  ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
    }
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database query preparation failed']);
        return;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Debug logging
    error_log("Routes API - User role: " . $user_role);
    error_log("Routes API - Query executed, rows found: " . $result->num_rows);
    
    $routes = [];
    while ($row = $result->fetch_assoc()) {
        // Decode JSON waypoints
        $waypoints = json_decode($row['waypoints'], true);
        
        // Debug each route
        error_log("Routes API - Processing route ID " . $row['id'] . ": " . $row['name']);
        error_log("Routes API - visible_to_students raw value: " . var_export($row['visible_to_students'], true));
        
        // Calculate distance from waypoints
        $distance = 0;
        if (!empty($waypoints) && count($waypoints) > 1) {
            for ($i = 1; $i < count($waypoints); $i++) {
                $dx = $waypoints[$i]['x'] - $waypoints[$i-1]['x'];
                $dy = $waypoints[$i]['y'] - $waypoints[$i-1]['y'];
                $distance += sqrt($dx * $dx + $dy * $dy);
            }
            $distance = round($distance / 10); // Scale to meters
        }
        
        // Transform snake_case to camelCase for JavaScript compatibility
        $route = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'startRoom' => $row['start_room'],
            'endRoom' => $row['end_room'],
            'waypoints' => $waypoints,
            'distance' => $distance,
            'visibleToStudents' => (bool)$row['visible_to_students'],
            'createdAt' => $row['created_at']
        ];
        
        // Add admin-only fields if present
        if (isset($row['created_by'])) {
            $route['createdBy'] = $row['created_by'];
        }
        if (isset($row['updated_at'])) {
            $route['updatedAt'] = $row['updated_at'];
        }
        
        $routes[] = $route;
    }
    
    // Final debug
    error_log("Routes API - Total routes being returned: " . count($routes));
    error_log("Routes API - Routes data: " . json_encode($routes));
    
    echo json_encode([
        'success' => true,
        'routes' => $routes,
        'debug' => [
            'role' => $user_role,
            'count' => count($routes),
            'query' => ($user_role === 'admin' ? 'all routes' : 'visible_to_students = 1')
        ]
    ]);
}

/**
 * Save a new route or update existing one
 */
function handleSaveRoute($conn, $user_id, $user_role) {
    // Only admins can create/update routes
    if ($user_role !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only administrators can create routes']);
        return;
    }
    
    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate required fields
    if (!isset($data['name']) || !isset($data['startRoom']) || !isset($data['endRoom']) || !isset($data['waypoints'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    $name = $data['name'];
    $description = $data['description'] ?? '';
    $start_room = $data['startRoom'];
    $end_room = $data['endRoom'];
    $waypoints = json_encode($data['waypoints']);
    $visible_to_students = isset($data['visibleToStudents']) ? (int)(bool)$data['visibleToStudents'] : 1;
    
    // Check if updating existing route
    if (isset($data['id']) && !empty($data['id'])) {
        $id = (int)$data['id'];
        
        $stmt = $conn->prepare(
            "UPDATE floor_plan_routes 
             SET name = ?, description = ?, start_room = ?, end_room = ?, 
                 waypoints = ?, visible_to_students = ?
             WHERE id = ?"
        );
        $stmt->bind_param("sssssii", $name, $description, $start_room, $end_room, $waypoints, $visible_to_students, $id);
        
        if ($stmt->execute()) {
            // Log action
            logAction($conn, $user_id, "Updated floor plan route: $name", 'floor_plan_routes', $id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Route updated successfully',
                'id' => $id
            ]);
        } else {
            throw new Exception('Failed to update route');
        }
    } else {
        // Insert new route
        $stmt = $conn->prepare(
            "INSERT INTO floor_plan_routes (name, description, start_room, end_room, waypoints, visible_to_students, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssii", $name, $description, $start_room, $end_room, $waypoints, $visible_to_students, $user_id);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            
            // Log action
            logAction($conn, $user_id, "Created floor plan route: $name", 'floor_plan_routes', $new_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Route created successfully',
                'id' => $new_id
            ]);
        } else {
            throw new Exception('Failed to create route');
        }
    }
}

/**
 * Delete a route
 */
function handleDeleteRoute($conn, $user_id, $user_role) {
    // Only admins can delete routes
    if ($user_role !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only administrators can delete routes']);
        return;
    }
    
    // Get DELETE data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Route ID is required']);
        return;
    }
    
    $id = (int)$data['id'];
    
    // Get route name before deleting (for logging)
    $stmt = $conn->prepare("SELECT name FROM floor_plan_routes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $route = $result->fetch_assoc();
    
    if (!$route) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Route not found']);
        return;
    }
    
    // Delete the route
    $stmt = $conn->prepare("DELETE FROM floor_plan_routes WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Log action
        logAction($conn, $user_id, "Deleted floor plan route: {$route['name']}", 'floor_plan_routes', $id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Route deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete route');
    }
}
?>
