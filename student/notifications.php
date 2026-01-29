<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../auth/login.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch notifications for the logged-in student's complaints - FIXED QUERY
$sql = "SELECT n.*, c.title as complaint_title 
        FROM notifications n 
        INNER JOIN complaints c ON n.complaint_id = c.id 
        WHERE c.student_id = ? 
        ORDER BY n.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("SQL Prepare Failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}
mysqli_stmt_close($stmt);

// Also update sidebar.php notification count - Add this logic to sidebar.php too
$notification_count_sql = "SELECT COUNT(*) as count 
                          FROM notifications n 
                          INNER JOIN complaints c ON n.complaint_id = c.id 
                          WHERE c.student_id = ? 
                          AND n.status = 'unread'";
$count_stmt = mysqli_prepare($conn, $notification_count_sql);
mysqli_stmt_bind_param($count_stmt, "i", $student_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);
$unread_count = $count_row['count'];
mysqli_stmt_close($count_stmt);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Complaint Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-accent: #78FECF;
            --background-base: #E5EAFA;
            --success-text: #093824;
            --secondary: #C6CCB2;
            --error-state: #BF4E30;
        }
        
        body {
            background-color: var(--background-base);
            overflow-x: hidden;
        }
        
        /* Mobile-first responsive sidebar */
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #1e293b;
            color: white;
            transition: all 0.3s ease;
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
            transition: all 0.3s ease;
        }
        
        .sidebar-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar-user-info {
            flex: 1;
            transition: all 0.3s ease;
        }
        
        .sidebar-user-name {
            font-weight: bold;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar-user-email {
            font-size: 0.7rem;
            color: #94a3b8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            transition: all 0.2s ease;
            border-radius: 0 20px 20px 0;
            margin-right: 10px;
        }
        
        .sidebar-menu-item:hover {
            background: #334155;
            transform: translateX(5px);
        }
        
        .sidebar-menu-item:active {
            transform: scale(0.98);
        }
        
        .sidebar-menu-item.active {
            background: #334155;
            color: white;
        }
        
        .sidebar-menu-item i {
            margin-right: 10px;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu-item span {
            transition: opacity 0.3s ease, max-width 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
        }
        
        .sidebar.collapsed .sidebar-menu-item span {
            opacity: 0;
            max-width: 0;
            padding: 0;
        }
        
        .sidebar.collapsed .sidebar-user-info {
            opacity: 0;
            max-width: 0;
            padding: 0;
            margin: 0;
        }
        
        .sidebar.collapsed .sidebar-user img {
            margin-right: 0;
            margin-left: 23px;
        }
        
        /* Ensure icons remain visible in collapsed state */
        .sidebar.collapsed .sidebar-menu-item i {
            margin-right: 0;
            font-size: 1.2rem;
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
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            transform: scale(1.1);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed + .main-content {
            margin-left: 70px;
        }
        
        .notifications-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .notifications-header {
            background: var(--primary-accent);
            color: #000;
            padding: 20px;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .notification-item {
            border-bottom: 1px solid #e9ecef;
            padding: 15px 20px;
            transition: background-color 0.2s ease;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item.unread {
            background-color: #f8fff8;
            border-left: 4px solid var(--primary-accent);
        }
        
        .notification-item.read {
            background-color: #ffffff;
        }
        
        .notification-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }
        
        .notification-message {
            color: #475569;
            margin-bottom: 8px;
        }
        
        .notification-meta {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .notification-status {
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 12px;
            background-color: #e2e8f0;
        }
        
        .notification-status.unread {
            background-color: var(--primary-accent);
            color: #000;
        }
        
        .btn-mark-all-read {
            background: var(--primary-accent);
            border: none;
            color: #000;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 8px;
        }
        
        .btn-mark-all-read:hover {
            background: #67e8b8;
        }
        
        .empty-notifications {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }
        
        .empty-notifications i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Mobile sidebar styles */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: none;
            }
            
            /* Mobile menu button */
            .mobile-menu-btn {
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1000;
                background: var(--primary-accent);
                border: none;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #000;
                font-size: 1.2rem;
                cursor: pointer;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 10px;
            }
            
            .notification-item {
                padding: 12px 15px;
            }
        }
        
        /* Tablet styles */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .sidebar-user-info,
            .sidebar .sidebar-menu-item span {
                opacity: 0;
                max-width: 0;
                padding: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile menu button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Notifications</h1>
            <?php if (!empty($notifications)): ?>
            <a href="#" class="btn btn-mark-all-read" id="markAllReadBtn">
                <i class="fas fa-check-double me-1"></i> Mark All as Read
            </a>
            <?php endif; ?>
        </div>

        <div class="card notifications-card">
            <div class="card-header notifications-header">
                <i class="fas fa-bell me-2"></i>Your Notifications
                <?php if ($unread_count > 0): ?>
                <span class="badge bg-danger ms-2"><?php echo $unread_count; ?> unread</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($notifications)): ?>
                <div class="empty-notifications">
                    <i class="fas fa-bell-slash text-muted"></i>
                    <h5 class="text-muted">No notifications yet</h5>
                    <p class="text-muted">You don't have any notifications at the moment.</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['status'] == 'unread' ? 'unread' : 'read'; ?>" data-id="<?php echo $notification['id']; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="notification-title">
                                    <?php if (!empty($notification['complaint_title'])): ?>
                                        Complaint: <?php echo htmlspecialchars($notification['complaint_title']); ?>
                                    <?php else: ?>
                                        System Notification
                                    <?php endif; ?>
                                </div>
                                <div class="notification-message">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </div>
                                <div class="notification-meta">
                                    <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                </div>
                            </div>
                            <div class="notification-status <?php echo $notification['status'] == 'unread' ? 'unread' : 'read'; ?>">
                                <?php echo $notification['status'] == 'unread' ? 'Unread' : 'Read'; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar for desktop
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('collapsed');
            
            // Rotate the toggle icon
            const toggleIcon = this.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.classList.remove('fa-chevron-left');
                toggleIcon.classList.add('fa-chevron-right');
            } else {
                toggleIcon.classList.remove('fa-chevron-right');
                toggleIcon.classList.add('fa-chevron-left');
            }
        });
        
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const menuBtn = document.getElementById('mobileMenuBtn');
            
            if (window.innerWidth < 992 && sidebar.classList.contains('active')) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
        
        // Mark all as read
        document.getElementById('markAllReadBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            fetch('../includes/mark_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'student_id=<?php echo $student_id; ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ Failed to mark notifications as read');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('⚠️ Network error. Please try again.');
            });
        });
    </script>
</body>
</html>