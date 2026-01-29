<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../auth/login.php');
    exit();
}

// Get student data from session
$student_name = $_SESSION['user_name'] ?? 'Student';
$student_email = $_SESSION['user_email'] ?? 'student@example.com';
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Complaint Management System</title>
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
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card-stat {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .card-stat:hover {
            transform: translateY(-5px);
        }
        
        .card-header-stat {
            background: var(--primary-accent);
            color: #000;
            font-weight: 600;
            padding: 15px;
        }
        
        .card-body-stat {
            padding: 20px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--success-text);
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .welcome-section {
            margin-bottom: 30px;
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
            .dashboard-cards {
                grid-template-columns: 1fr;
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
        <div class="welcome-section">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-4">Welcome, <?php echo htmlspecialchars($student_name); ?>!</h1>
                    <p class="text-muted">Manage your complaints and track their status from this dashboard.</p>
                </div>
                <div class="position-relative">
                    <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#notificationsModal">
                        <i class="fas fa-bell"></i> Notifications
                        <?php
                        // Get notification count - CORRECTED
                        include('../config/db.php');
                        $notification_sql = "SELECT COUNT(*) as count 
                                            FROM notifications n 
                                            INNER JOIN complaints c ON n.complaint_id = c.id 
                                            WHERE c.student_id = ? 
                                            AND n.status = 'unread'";
                        $notification_stmt = mysqli_prepare($conn, $notification_sql);
                        if ($notification_stmt === false) {
                            $notification_count = 0;
                        } else {
                            mysqli_stmt_bind_param($notification_stmt, "i", $user_id);
                            mysqli_stmt_execute($notification_stmt);
                            $notification_result = mysqli_stmt_get_result($notification_stmt);
                            $notification_row = mysqli_fetch_assoc($notification_result);
                            $notification_count = $notification_row['count'] ?? 0;
                            mysqli_stmt_close($notification_stmt);
                        }
                        mysqli_close($conn);
                        
                        if ($notification_count > 0):
                        ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $notification_count; ?>
                            <span class="visually-hidden">unread messages</span>
                        </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>

        <?php
        include('../config/db.php');

        // Get complaint counts for the logged-in student
        $user_id = $_SESSION['user_id'];

        // Total complaints
        $total_sql = "SELECT COUNT(*) as count FROM complaints WHERE student_id = ?";
        $total_stmt = mysqli_prepare($conn, $total_sql);
        if ($total_stmt === false) {
            $total_complaints = 0;
        } else {
            mysqli_stmt_bind_param($total_stmt, "i", $user_id);
            mysqli_stmt_execute($total_stmt);
            $total_result = mysqli_stmt_get_result($total_stmt);
            $total_row = mysqli_fetch_assoc($total_result);
            $total_complaints = $total_row['count'] ?? 0;
            mysqli_stmt_close($total_stmt);
        }

        // Pending complaints
        $pending_sql = "SELECT COUNT(*) as count FROM complaints WHERE student_id = ? AND status = 'pending'";
        $pending_stmt = mysqli_prepare($conn, $pending_sql);
        if ($pending_stmt === false) {
            $pending_complaints = 0;
        } else {
            mysqli_stmt_bind_param($pending_stmt, "i", $user_id);
            mysqli_stmt_execute($pending_stmt);
            $pending_result = mysqli_stmt_get_result($pending_stmt);
            $pending_row = mysqli_fetch_assoc($pending_result);
            $pending_complaints = $pending_row['count'] ?? 0;
            mysqli_stmt_close($pending_stmt);
        }

        // Resolved complaints
        $resolved_sql = "SELECT COUNT(*) as count FROM complaints WHERE student_id = ? AND status = 'resolved'";
        $resolved_stmt = mysqli_prepare($conn, $resolved_sql);
        if ($resolved_stmt === false) {
            $resolved_complaints = 0;
        } else {
            mysqli_stmt_bind_param($resolved_stmt, "i", $user_id);
            mysqli_stmt_execute($resolved_stmt);
            $resolved_result = mysqli_stmt_get_result($resolved_stmt);
            $resolved_row = mysqli_fetch_assoc($resolved_result);
            $resolved_complaints = $resolved_row['count'] ?? 0;
            mysqli_stmt_close($resolved_stmt);
        }

        mysqli_close($conn);
        ?>

        <!-- Dashboard Cards -->
        <div class="dashboard-cards">
            <div class="card card-stat">
                <div class="card-header card-header-stat">
                    <i class="fas fa-journal-text me-2"></i>Total Complaints
                </div>
                <div class="card-body card-body-stat">
                    <div class="stat-number"><?php echo $total_complaints; ?></div>
                    <div class="stat-label">All your complaints</div>
                </div>
            </div>

            <div class="card card-stat">
                <div class="card-header card-header-stat" style="background-color: #FFEC8B; color: #000;">
                    <i class="fas fa-clock me-2"></i>Pending Complaints
                </div>
                <div class="card-body card-body-stat">
                    <div class="stat-number"><?php echo $pending_complaints; ?></div>
                    <div class="stat-label">Awaiting action</div>
                </div>
            </div>

            <div class="card card-stat">
                <div class="card-header card-header-stat" style="background-color: #90EE90; color: #000;">
                    <i class="fas fa-check-circle me-2"></i>Resolved Complaints
                </div>
                <div class="card-body card-body-stat">
                    <div class="stat-number"><?php echo $resolved_complaints; ?></div>
                    <div class="stat-label">Successfully resolved</div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="card rounded-3 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Recent Activity</h5>
            </div>
            <div class="card-body">
                <p class="text-center text-muted">No recent activity to show.</p>
            </div>
        </div>
    </div>

    <!-- Notifications Modal -->
    <div class="modal fade" id="notificationsModal" tabindex="-1" aria-labelledby="notificationsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationsModalLabel">Notifications</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                include('../config/db.php');
                                $user_id = $_SESSION['user_id'];
                                
                                // Fetch notifications - CORRECTED
                                $notif_sql = "SELECT n.*, c.title as complaint_title 
                                              FROM notifications n 
                                              INNER JOIN complaints c ON n.complaint_id = c.id 
                                              WHERE c.student_id = ? 
                                              ORDER BY n.created_at DESC LIMIT 10";
                                $notif_stmt = mysqli_prepare($conn, $notif_sql);
                                if ($notif_stmt !== false) {
                                    mysqli_stmt_bind_param($notif_stmt, "i", $user_id);
                                    mysqli_stmt_execute($notif_stmt);
                                    $notif_result = mysqli_stmt_get_result($notif_stmt);
                                    
                                    if (mysqli_num_rows($notif_result) > 0):
                                        while ($notif_row = mysqli_fetch_assoc($notif_result)):
                                ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?php if (!empty($notif_row['complaint_title'])): ?>
                                                Complaint "<?php echo htmlspecialchars($notif_row['complaint_title']); ?>"
                                            <?php endif; ?>
                                        </strong><br>
                                        <?php echo htmlspecialchars($notif_row['message']); ?>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($notif_row['created_at'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $notif_row['status'] == 'unread' ? 'bg-warning' : 'bg-success'; ?>">
                                            <?php echo $notif_row['status'] == 'unread' ? 'Unread' : 'Read'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No notifications yet</td>
                                </tr>
                                <?php endif; ?>
                                <?php 
                                    mysqli_stmt_close($notif_stmt);
                                } else { ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Error loading notifications</td>
                                </tr>
                                <?php } ?>
                                <?php mysqli_close($conn); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <?php 
                    // Check if there are unread notifications
                    include('../config/db.php');
                    $check_unread_sql = "SELECT COUNT(*) as count 
                                        FROM notifications n 
                                        INNER JOIN complaints c ON n.complaint_id = c.id 
                                        WHERE c.student_id = ? 
                                        AND n.status = 'unread'";
                    $check_stmt = mysqli_prepare($conn, $check_unread_sql);
                    if ($check_stmt !== false) {
                        mysqli_stmt_bind_param($check_stmt, "i", $user_id);
                        mysqli_stmt_execute($check_stmt);
                        $check_result = mysqli_stmt_get_result($check_stmt);
                        $check_row = mysqli_fetch_assoc($check_result);
                        $has_unread = ($check_row['count'] ?? 0) > 0;
                        mysqli_stmt_close($check_stmt);
                    } else {
                        $has_unread = false;
                    }
                    mysqli_close($conn);
                    
                    if ($has_unread): ?>
                    <a href="#" class="btn btn-primary" onclick="markAllAsRead()">Mark All as Read</a>
                    <?php endif; ?>
                </div>
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
        
        function markAllAsRead() {
            // AJAX call to mark all notifications as read
            fetch('../includes/mark_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'student_id=<?php echo $user_id; ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal and reload page to update notification count
                    const modalElement = document.getElementById('notificationsModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    modal.hide();
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>