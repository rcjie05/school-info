<?php
/**
 * Floor Plan Routes API
 * Handles CRUD operations for floor plan navigation routes
 */

header('Content-Type: application/json');

// Start session and include dependencies
require_once __DIR__ . '/config.php';

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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
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
        $query = "SELECT id, name, description, start_room, end_room, waypoints, 
                         visible_to_students, created_at 
                  FROM floor_plan_routes 
                  WHERE visible_to_students = TRUE 
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
    
    $routes = [];
    while ($row = $result->fetch_assoc()) {
        // Decode JSON waypoints
        $row['waypoints'] = json_decode($row['waypoints'], true);
        $routes[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'routes' => $routes
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