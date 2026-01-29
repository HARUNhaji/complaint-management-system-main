<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get user data from session
$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? 'user@example.com';
$user_role = $_SESSION['role'] ?? 'user';
$user_image = $_SESSION['user_image'] ?? 'default.png';

include('../config/db.php');

// Count unread notifications for the user
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $notification_sql = "SELECT COUNT(*) as count FROM notifications WHERE student_id = ? AND status = 'unread'";
    $notification_stmt = mysqli_prepare($conn, $notification_sql);
    if ($notification_stmt !== false) {
        mysqli_stmt_bind_param($notification_stmt, "i", $user_id);
        mysqli_stmt_execute($notification_stmt);
        $notification_result = mysqli_stmt_get_result($notification_stmt);
        if ($notification_result) {
            $notification_row = mysqli_fetch_assoc($notification_result);
            $unread_count = $notification_row['count'];
        }
        mysqli_stmt_close($notification_stmt);
    }
}

// Check if the user_image is just a filename or full path
if (!empty($user_image) && $user_image !== 'default.png' && !strpos($user_image, '/') && !strpos($user_image, '..')) {
    // If it's just a filename, prepend the uploads path
    $user_image_path = '../assets/uploads/' . $user_image;
} else {
    // Otherwise use the existing path or default
    $user_image_path = $user_image !== 'default.png' ? $user_image : '../assets/uploads/default.png';
}

mysqli_close($conn);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-menu-item <?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="manage_complaints.php" class="sidebar-menu-item <?php echo strpos($_SERVER['REQUEST_URI'], 'manage_complaints.php') ? 'active' : ''; ?>">
                <i class="fas fa-list"></i>
                <span>Complaints</span>
            </a>
            <a href="manage_users.php" class="sidebar-menu-item <?php echo strpos($_SERVER['REQUEST_URI'], 'manage_users.php') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="reports.php" class="sidebar-menu-item <?php echo strpos($_SERVER['REQUEST_URI'], 'reports.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="view_complaints.php" class="sidebar-menu-item <?php echo strpos($_SERVER['REQUEST_URI'], 'view_complaints.php') ? 'active' : ''; ?>">
                <i class="fas fa-eye"></i>
                <span>View Complaints</span>
            </a>
            <a href="../auth/logout.php" class="sidebar-menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <div class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-chevron-left"></i>
    </div>
</div>