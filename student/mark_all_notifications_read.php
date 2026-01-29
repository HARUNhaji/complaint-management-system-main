<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include('../config/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['user_id'];
    
    // Update all unread notifications for the student to marked as read
    $sql = "UPDATE notifications SET is_read = 1 WHERE student_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $rows_affected = mysqli_stmt_affected_rows($stmt);
        echo json_encode(['success' => true, 'rows_affected' => $rows_affected]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

mysqli_close($conn);
?>