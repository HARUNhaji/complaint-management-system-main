<?php
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

include('../config/db.php');

$complaint_id = $_GET['id'] ?? null;

if (!$complaint_id) {
    header('Location: manage_complaints.php');
    exit();
}

// Fetch complaint details
$sql = "SELECT c.*, u.fullname, u.email, u.phone FROM complaints c JOIN users u ON c.student_id = u.id WHERE c.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $complaint_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$complaint = mysqli_fetch_assoc($result);

if (!$complaint) {
    header('Location: manage_complaints.php');
    exit();
}

// Fetch status history for this complaint
$status_history_sql = "SELECT * FROM complaint_status_history WHERE complaint_id = ? ORDER BY changed_at DESC";
$status_history_stmt = mysqli_prepare($conn, $status_history_sql);
mysqli_stmt_bind_param($status_history_stmt, "i", $complaint_id);
mysqli_stmt_execute($status_history_stmt);
$status_history_result = mysqli_stmt_get_result($status_history_stmt);

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
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
            transition: opacity 0.3s;
        }
        
        .sidebar.collapsed .sidebar-menu-item span {
            opacity: 0;
            width: 0;
            height: 0;
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
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
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
        
        .status-pending {
            background-color: #FFEC8B;
            color: #8B7300;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-in-review {
            background-color: #ADD8E6;
            color: #004A6E;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-resolved {
            background-color: #90EE90;
            color: #004A42;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
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
        
        .status-history-item {
            border-left: 3px solid #78FECF;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        
        .status-history-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-menu-item span {
                opacity: 0;
                width: 0;
                height: 0;
                overflow: hidden;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">View Complaint Details</h1>
            <a href="manage_complaints.php" class="btn btn-back">
                <i class="bi bi-arrow-left me-1"></i> Back to Complaints
            </a>
        </div>

        <div class="card complaint-details-card">
            <div class="card-header card-header-details">
                <i class="bi bi-file-text me-2"></i>Complaint #CMP<?php echo $complaint['id']; ?>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Complaint ID:</span></div>
                                <div class="col-8"><span class="detail-value">#CMP<?php echo $complaint['id']; ?></span></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Student Name:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo htmlspecialchars($complaint['fullname']); ?></span></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Email:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo htmlspecialchars($complaint['email']); ?></span></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Phone:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo htmlspecialchars($complaint['phone']); ?></span></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Category:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo htmlspecialchars($complaint['category']); ?></span></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Title:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo htmlspecialchars($complaint['title']); ?></span></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Subject/Teacher:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo htmlspecialchars($complaint['related_subject_or_teacher'] ?: 'N/A'); ?></span></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Status:</span></div>
                                <div class="col-8">
                                    <?php 
                                    $status = $complaint['status'];
                                    if ($status === 'Pending'): 
                                    ?>
                                        <span class="status-pending">Pending</span>
                                    <?php elseif ($status === 'In Review'): ?>
                                        <span class="status-in-review">In Review</span>
                                    <?php elseif ($status === 'Resolved'): ?>
                                        <span class="status-resolved">Resolved</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Date Submitted:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($complaint['created_at'])); ?></span></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="row">
                                <div class="col-4"><span class="detail-label">Last Updated:</span></div>
                                <div class="col-8"><span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($complaint['updated_at'])); ?></span></div>
                            </div>
                        </div>
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
                

                
                <!-- Status History Section -->
                <hr class="my-4">
                <div class="row">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="bi bi-clock-history me-2"></i>Status History</h5>
                        <?php if (mysqli_num_rows($status_history_result) > 0): ?>
                            <?php while ($history = mysqli_fetch_assoc($status_history_result)): ?>
                                <div class="status-history-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo htmlspecialchars($history['old_status']); ?></strong> → 
                                            <span class="status-<?php echo strtolower($history['new_status']); ?>"><?php echo htmlspecialchars($history['new_status']); ?></span>
                                        </div>
                                        <div class="status-history-date">
                                            <?php echo date('F j, Y g:i A', strtotime($history['changed_at'])); ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">Changed by Admin</small>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">No status changes recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-12 text-end">
                        <a href="manage_complaints.php" class="btn btn-secondary me-2">Back to Complaints</a>
                        <a href="manage_complaints.php" class="btn btn-success" onclick="document.getElementById('resolveForm').submit(); return false;">
                            <i class="bi bi-check-circle me-1"></i> Mark as Resolved
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hidden form for resolving complaint -->
        <form id="resolveForm" method="POST" action="manage_complaints.php" style="display: none;">
            <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
            <input type="hidden" name="resolve_complaint" value="1">
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('collapsed');
        });
    </script>
</body>
</html>