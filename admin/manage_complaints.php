<?php
// VERY FIRST LINE - NO WHITESPACE BEFORE THIS
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Get admin data from session
$admin_name = $_SESSION['user_name'] ?? 'Admin';
$admin_email = $_SESSION['user_email'] ?? 'admin@example.com';

// Handle complaint deletion BEFORE ANY OUTPUT
if (isset($_POST['delete_complaint'])) {
    include('../config/db.php');
    $complaint_id = $_POST['complaint_id'];
    $sql = "DELETE FROM complaints WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        $_SESSION['error_message'] = "Database error: " . mysqli_error($conn);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $complaint_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Complaint deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting complaint: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
    header("Location: manage_complaints.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints - Complaint Management System</title>
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
        
        .complaints-table {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .table-header {
            background: var(--primary-accent);
            color: #000;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #475569;
        }
        
        .status-pending {
            background-color: #FFEC8B;
            color: #8B7300;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-in-review {
            background-color: #ADD8E6;
            color: #004A6E;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-resolved {
            background-color: #90EE90;
            color: #004A42;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .priority-high {
            background-color: #FFB6C1;
            color: #8B1A3A;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .priority-medium {
            background-color: #FFE4B5;
            color: #8B7355;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .priority-low {
            background-color: #E0FFE0;
            color: #228B22;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .btn-action {
            padding: 5px 10px;
            margin: 0 2px;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        
        .btn-view {
            background: var(--primary-accent);
            border: none;
            color: #000;
        }
        
        .btn-edit {
            background: #E0E0E0;
            border: none;
            color: #333;
        }
        
        .btn-delete {
            background: var(--error-state);
            border: none;
            color: white;
        }
        
        .btn-view:hover {
            background: #67e8b8;
        }
        
        .btn-edit:hover {
            background: #D0D0D0;
        }
        
        .btn-delete:hover {
            background: #a83f27;
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
            .table-responsive {
                font-size: 0.85rem;
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
            <h1 class="h3">Manage Complaints</h1>
            <div class="d-flex">
                <input type="text" class="form-control me-2" placeholder="Search complaints..." style="width: 250px;">
                <button class="btn btn-outline-secondary">Filter</button>
            </div>
        </div>

        <div class="card complaints-table">
            <div class="card-header table-header">
                <i class="fas fa-list me-2"></i>Complaints Management
            </div>
            <div class="card-body">
                <?php
                include('../config/db.php');

                // Handle complaint resolution
                if (isset($_POST['resolve_complaint'])) {
                    $complaint_id = $_POST['complaint_id'];
                    $sql = "UPDATE complaints SET status = 'resolved', resolved_at = NOW(), updated_at = NOW() WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    if ($stmt === false) {
                        $error_message = "Database error: " . mysqli_error($conn);
                    } else {
                        mysqli_stmt_bind_param($stmt, "i", $complaint_id);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            // Get complaint details
                            $complaint_sql = "SELECT student_id, status, title FROM complaints WHERE id = ?";
                            $complaint_stmt = mysqli_prepare($conn, $complaint_sql);
                            if ($complaint_stmt !== false) {
                                mysqli_stmt_bind_param($complaint_stmt, "i", $complaint_id);
                                mysqli_stmt_execute($complaint_stmt);
                                $complaint_result = mysqli_stmt_get_result($complaint_stmt);
                                $complaint_row = mysqli_fetch_assoc($complaint_result);
                                $student_id = $complaint_row['student_id'];
                                $old_status = $complaint_row['status'];
                                
                                // Insert notification for the student
                                $notification_sql = "INSERT INTO notifications (student_id, complaint_id, message, status) VALUES (?, ?, ?, 'unread')";
                                $notification_message = "Your complaint #{$complaint_id} has been resolved.";
                                $notification_stmt = mysqli_prepare($conn, $notification_sql);
                                if ($notification_stmt !== false) {
                                    mysqli_stmt_bind_param($notification_stmt, "iis", $student_id, $complaint_id, $notification_message);
                                    mysqli_stmt_execute($notification_stmt);
                                    mysqli_stmt_close($notification_stmt);
                                }
                                
                                // Insert status history record
                                $status_history_sql = "INSERT INTO complaint_status_history (complaint_id, old_status, new_status, changed_by, changed_at) VALUES (?, ?, 'Resolved', ?, NOW())";
                                $status_history_stmt = mysqli_prepare($conn, $status_history_sql);
                                if ($status_history_stmt !== false) {
                                    mysqli_stmt_bind_param($status_history_stmt, "isi", $complaint_id, $old_status, $_SESSION['user_id']);
                                    mysqli_stmt_execute($status_history_stmt);
                                    mysqli_stmt_close($status_history_stmt);
                                }
                                
                                mysqli_stmt_close($complaint_stmt);
                                $success_message = "Complaint resolved successfully!";
                            } else {
                                $error_message = "Error resolving complaint: " . mysqli_error($conn);
                            }
                        } else {
                            $error_message = "Error resolving complaint: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt);
                    }
                }

                // Fetch complaints with student names
                $sql = "SELECT c.*, u.fullname FROM complaints c JOIN users u ON c.student_id = u.id ORDER BY c.created_at DESC";
                $result = mysqli_query($conn, $sql);
                ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Complaint ID</th>
                                <th>Student Name</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Subject/Teacher</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>#CMP<?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo htmlspecialchars($row['related_subject_or_teacher'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    $status = $row['status'];
                                    if ($status === 'pending'): 
                                    ?>
                                        <span class="status-pending">Pending</span>
                                    <?php elseif ($status === 'in_review'): ?>
                                        <span class="status-in-review">In Review</span>
                                    <?php elseif ($status === 'resolved'): ?>
                                        <span class="status-resolved">Resolved</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="resolve_complaint" class="btn btn-view btn-action" onclick="return confirm('Are you sure you want to resolve this complaint?')">
                                            <i class="fas fa-check-circle"></i> Resolve
                                        </button>
                                    </form>
                                    <a href="view_complaints.php?id=<?php echo $row['id']; ?>" class="btn btn-edit btn-action">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this complaint?')">
                                        <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="delete_complaint" class="btn btn-delete btn-action">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Error loading complaints</td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($result && mysqli_num_rows($result) == 0): ?>
                            <tr>
                                <td colspan="8" class="text-center">No complaints found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                mysqli_close($conn);
                ?>
                
                <nav aria-label="Complaints pagination">
                    <ul class="pagination justify-content-center mt-4">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
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
    </script>
</body>
</html>