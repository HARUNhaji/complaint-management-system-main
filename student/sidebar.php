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

// Count unread notifications for the user - CORRECTED QUERY
$unread_count = 0;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
    $student_id = $_SESSION['user_id'];
    
    // CORRECTED: Join with complaints table to get student-specific notifications
    $notification_sql = "SELECT COUNT(*) as count 
                        FROM notifications n 
                        INNER JOIN complaints c ON n.complaint_id = c.id 
                        WHERE c.student_id = ? 
                        AND n.status = 'unread'";
    
    $notification_stmt = mysqli_prepare($conn, $notification_sql);
    if ($notification_stmt !== false) {
        mysqli_stmt_bind_param($notification_stmt, "i", $student_id);
        mysqli_stmt_execute($notification_stmt);
        $notification_result = mysqli_stmt_get_result($notification_stmt);
        if ($notification_result) {
            $notification_row = mysqli_fetch_assoc($notification_result);
            $unread_count = $notification_row['count'] ?? 0;
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

<!DOCTYPE html>
<html>
<head>
<style>
    /* Notification badge styles - FIXED with pure CSS */
    .notification-badge {
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 11px;
        font-weight: bold;
        min-width: 18px;
        height: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 100;
    }
    
    .sidebar-menu-item {
        position: relative;
        padding-right: 35px !important; /* Make space for badge */
    }
    
    /* Make badge visible when sidebar is collapsed - NO JS REQUIRED */
    .sidebar.collapsed .notification-badge {
        position: absolute;
        right: 8px;
        top: 12px;
        font-size: 10px;
        padding: 1px 4px;
        min-width: 16px;
        height: 16px;
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Hide text but NOT the badge when collapsed */
    .sidebar.collapsed .sidebar-menu-item > span:not(.notification-badge) {
        opacity: 0;
        max-width: 0;
        display: inline-block;
        overflow: hidden;
    }
    
    /* Keep badge visible */
    .sidebar.collapsed .notification-badge {
        opacity: 1 !important;
        visibility: visible !important;
        display: flex !important;
    }
    
    /* Regular sidebar styles */
    .sidebar {
        width: 250px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background: #1e293b;
        color: white;
        transition: all 0.3s;
        z-index: 100;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    
    .sidebar.collapsed {
        width: 70px;
    }
    
    .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid #374151;
    }
    
    .sidebar-user {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .sidebar-user img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 10px;
    }
    
    .sidebar-user-info {
        flex: 1;
        transition: all 0.3s;
    }
    
    .sidebar-user-name {
        font-weight: bold;
        font-size: 0.9rem;
    }
    
    .sidebar-user-email {
        font-size: 0.7rem;
        color: #94a3b8;
    }
    
    .sidebar-menu {
        padding: 10px 0;
    }
    
    .sidebar-menu-item {
        padding: 12px 20px;
        color: #e2e8f0;
        text-decoration: none;
        display: flex;
        align-items: center;
        transition: all 0.2s;
    }
    
    .sidebar-menu-item:hover, .sidebar-menu-item.active {
        background: #334155;
        color: white;
    }
    
    .sidebar-menu-item i {
        margin-right: 10px;
        font-size: 1.1rem;
        width: 24px;
        text-align: center;
    }
    
    .sidebar-menu-item span {
        transition: opacity 0.3s, max-width 0.3s;
    }
    
    .sidebar.collapsed .sidebar-user-info,
    .sidebar.collapsed .sidebar-menu-item > span:not(.notification-badge) {
        opacity: 0;
        max-width: 0;
        overflow: hidden;
    }
    
    .sidebar-toggle {
        position: absolute;
        right: -12px;
        top: 50%;
        background: #1e293b;
        border: 2px solid white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 101;
    }
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-user">
            <img src="<?php echo htmlspecialchars($user_image_path); ?>" alt="Profile" class="rounded-circle">
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($user_name); ?></div>
                <div class="sidebar-user-email"><?php echo htmlspecialchars($user_email); ?></div>
            </div>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="sidebar-menu-item <?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="submit_complaint.php" class="sidebar-menu-item <?php echo strpos($_SERVER['REQUEST_URI'], 'submit_complaint.php') ? 'active' : ''; ?>">
            <i class="fas fa-edit"></i>
            <span>Submit Complaint</span>
        </a>
        <a href="my_complaints.php" class="sidebar-menu-item <?php echo strpos($_SERVER['REQUEST_URI'], 'my_complaints.php') ? 'active' : ''; ?>">
            <i class="fas fa-list"></i>
            <span>My Complaints</span>
        </a>
        <a href="notifications.php" class="sidebar-menu-item <?php echo strpos($_SERVER['REQUEST_URI'], 'notifications.php') ? 'active' : ''; ?>">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
            <?php if ($unread_count > 0): ?>
                <span class="notification-badge">
                    <?php echo $unread_count; ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="profile.php" class="sidebar-menu-item <?php echo strpos($_SERVER['REQUEST_URI'], 'profile.php') ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a href="../auth/logout.php" class="sidebar-menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
    
    <div class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
        <i class="fas fa-chevron-left"></i>
    </div>
</div>

<script>
// Only minimal JS for sidebar toggle (not for badge)
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('collapsed');
    
    const toggleIcon = document.querySelector('#sidebarToggle i');
    if (sidebar.classList.contains('collapsed')) {
        toggleIcon.classList.remove('fa-chevron-left');
        toggleIcon.classList.add('fa-chevron-right');
    } else {
        toggleIcon.classList.remove('fa-chevron-right');
        toggleIcon.classList.add('fa-chevron-left');
    }
}
</script>

</body>
</html>