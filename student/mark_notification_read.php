<?php
// includes/mark_notifications_read.php
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include('../config/db.php');

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'student';

if ($user_role == 'admin') {
    $sql = "UPDATE notifications SET status = 'read' 
            WHERE recipient_role = 'admin' 
            AND recipient_id = ? 
            AND status = 'unread'";
} else {
    $sql = "UPDATE notifications SET status = 'read' 
            WHERE recipient_role = 'student' 
            AND recipient_id = ? 
            AND status = 'unread'";
}

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $user_id);

if (mysqli_stmt_execute($stmt)) {
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    echo json_encode([
        'success' => true, 
        'message' => "Marked $affected_rows notifications as read",
        'count' => $affected_rows
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update notifications'
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>