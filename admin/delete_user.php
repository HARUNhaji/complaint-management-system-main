<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

include('../config/db.php');

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    header('Location: manage_users.php');
    exit();
}

// Fetch user to get profile image
$sql = "SELECT profile_image FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header('Location: manage_users.php');
    exit();
}

$profile_image = $user['profile_image'];

// Start transaction
mysqli_autocommit($conn, FALSE);

try {
    // Delete user's complaints first (due to foreign key constraint)
    $delete_complaints_sql = "DELETE FROM complaints WHERE student_id = ?";
    $delete_complaints_stmt = mysqli_prepare($conn, $delete_complaints_sql);
    if ($delete_complaints_stmt === false) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($delete_complaints_stmt, "i", $user_id);
    $complaints_success = mysqli_stmt_execute($delete_complaints_stmt);

    // Delete user's notifications
    $delete_notifications_sql = "DELETE FROM notifications WHERE student_id = ?";
    $delete_notifications_stmt = mysqli_prepare($conn, $delete_notifications_sql);
    if ($delete_notifications_stmt === false) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($delete_notifications_stmt, "i", $user_id);
    $notifications_success = mysqli_stmt_execute($delete_notifications_stmt);

    // Delete user's status history records
    $delete_status_history_sql = "DELETE FROM complaint_status_history WHERE changed_by = ?";
    $delete_status_history_stmt = mysqli_prepare($conn, $delete_status_history_sql);
    if ($delete_status_history_stmt === false) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($delete_status_history_stmt, "i", $user_id);
    $status_history_success = mysqli_stmt_execute($delete_status_history_stmt);

    // Delete the user
    $delete_user_sql = "DELETE FROM users WHERE id = ?";
    $delete_user_stmt = mysqli_prepare($conn, $delete_user_sql);
    if ($delete_user_stmt === false) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($delete_user_stmt, "i", $user_id);
    $user_success = mysqli_stmt_execute($delete_user_stmt);

    if ($complaints_success && $notifications_success && $status_history_success && $user_success) {
        mysqli_commit($conn);
        
        // Delete profile image if it's not the default
        if ($profile_image != 'default.png') {
            $image_path = '../assets/uploads/' . $profile_image;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $_SESSION['success_message'] = "User deleted successfully!";
        header('Location: manage_users.php');
        exit();
    } else {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Error deleting user: " . mysqli_error($conn);
        header('Location: manage_users.php');
        exit();
    }

    mysqli_stmt_close($delete_user_stmt);
    mysqli_stmt_close($delete_status_history_stmt);
    mysqli_stmt_close($delete_notifications_stmt);
    mysqli_stmt_close($delete_complaints_stmt);
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: manage_users.php');
    exit();
}

mysqli_close($conn);
?>