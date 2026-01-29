<?php
// mark_notifications_read.php - COMPLETE WORKING VERSION
header('Content-Type: application/json');

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Include database
include('../config/db.php');

// Get user info
$user_id = $_SESSION['user_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'student';

// WORKING QUERY: Updates notifications for the current user
$sql = "UPDATE notifications 
        SET status = 'read' 
        WHERE (recipient_id = ? OR student_id = ?) 
        AND status = 'unread'";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}

mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    echo json_encode([
        'success' => true, 
        'message' => "Successfully marked $affected_rows notifications as read",
        'count' => $affected_rows
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>