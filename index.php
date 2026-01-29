<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on user role
    if ($_SESSION['role'] === 'student') {
        header('Location: student/dashboard.php');
        exit();
    } else if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit();
    } else {
        // For other roles, redirect to login
        header('Location: auth/login.php');
        exit();
    }
} else {
    // If not logged in, redirect to login page
    header('Location: auth/login.php');
    exit();
}
?>