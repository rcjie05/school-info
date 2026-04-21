<?php
/**
 * Floor Plan Entry Point
 * Redirects to the proper floor plan page based on authentication
 */

// Start session
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // User is logged in, redirect to the authenticated version
    header('Location: /php/floorplan.php');
} else {
    // User not logged in, redirect to login page
    header('Location: /school-mgmt-clean/public/login.html?redirect=floorplan');
}
exit();
?>
