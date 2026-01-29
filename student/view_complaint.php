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
$complaint_id = $_GET['id'] ?? null;

if (!$complaint_id) {
    header('Location: my_complaints.php');
    exit();
}

// Fetch complaint details ensuring it belongs to the logged-in student
$sql = "SELECT * FROM complaints WHERE id = ? AND student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $complaint_id, $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$complaint = mysqli_fetch_assoc($result);

if (!$complaint) {
    // Complaint doesn't exist or doesn't belong to the student
    header('Location: my_complaints.php');
    exit();
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Complaint - Complaint Management System</title>
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
        
        .complaint-details-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .card-header-details {
            background: var(--primary-accent);
            color: #000;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .detail-row {
            border-bottom: 1px solid #e9ecef;
            padding: 12px 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: #475569;
        }
        
        .detail-value {
            color: #374151;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #FFEC8B;
            color: #000;
        }
        
        .status-resolved {
            background-color: #90EE90;
            color: #000;
        }
        
        .status-in-progress {
            background-color: #87CEEB;
            color: #000;
        }
        
        .btn-back {
            background: var(--primary-accent);
            border: none;
            color: #000;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 8px;
        }
        
        .btn-back:hover {
            background: #67e8b8;
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
                font-size: 0.8rem;
            }
            
            .status-badge {
                font-size: 0.7rem;
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
            <h1 class="h3">View Complaint Details</h1>
            <a href="my_complaints.php" class="btn btn-back">
                <i class="fas fa-arrow-left me-1"></i> Back to My Complaints
            </a>
        </div>

        <div class="card complaint-details-card">
            <div class="card-header card-header-details">
                <i class="fas fa-file-alt me-2"></i>Complaint Details #<?php echo htmlspecialchars($complaint['id']); ?>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Complaint ID:</span></div>
                                <div class="col-8"><span class="detail-value">#<?php echo htmlspecialchars($complaint['id']); ?></span></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Title:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo htmlspecialchars($complaint['title']); ?></span></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Category:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo htmlspecialchars($complaint['category']); ?></span></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Related Info:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo htmlspecialchars($complaint['related_subject_or_teacher'] ?: 'N/A'); ?></span></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Status:</span></div>
                                <div class="col-8">
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $complaint['status'])); ?>">
                                        <?php echo htmlspecialchars($complaint['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Priority:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo htmlspecialchars($complaint['priority']); ?></span></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Date Submitted:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($complaint['created_at'])); ?></span></div>
                            </div>
                        </div>
                        
                        <?php if ($complaint['resolved_at']): ?>
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Date Resolved:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($complaint['resolved_at'])); ?></span></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-12">
                        <div class="detail-row">
                            <div class="detail-label mb-2">Description:</div>
                            <div class="detail-value p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($complaint['description'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-12 text-end">
                        <a href="my_complaints.php" class="btn btn-back">
                            <i class="fas fa-arrow-left me-1"></i> Back to My Complaints
                        </a>
                    </div>
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
    </script>
</body>
</html>