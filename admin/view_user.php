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

// Validate user ID
if (!$user_id || !is_numeric($user_id)) {
    header('Location: manage_users.php');
    exit();
}

// Fetch user details - CORRECTED QUERY
$sql = "SELECT id, fullname, email, phone, address, birth_place, birth_year, profile_image, role, created_at FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("SQL Prepare Failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    die("User not found");
}

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User - Complaint Management System</title>
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
        
        .user-details-card {
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
        
        .role-admin {
            background-color: #FFB6C1;
            color: #8B1A3A;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .role-student {
            background-color: #E0FFE0;
            color: #228B22;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #90EE90;
            color: #004A42;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-inactive {
            background-color: #FFB6C1;
            color: #8B1A3A;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
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
        
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-accent);
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
            <h1 class="h3">View User Details</h1>
            <a href="manage_users.php" class="btn btn-back">
                <i class="fas fa-arrow-left me-1"></i> Back to Users
            </a>
        </div>

        <div class="card user-details-card">
            <div class="card-header card-header-details">
                <i class="fas fa-user me-2"></i>User Profile
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        <?php 
                        $imagePath = !empty($user['profile_image']) && $user['profile_image'] != 'default.png' ? 
                                    '../assets/uploads/' . htmlspecialchars($user['profile_image']) : 
                                    'https://ui-avatars.com/api/?name=' . urlencode($user['fullname']) . '&background=0D8ABC&color=fff&size=128';
                        ?>
                        <img src="<?php echo $imagePath; ?>" 
                             alt="Profile" class="profile-image img-fluid">
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-5"><span class="detail-label">User ID:</span></div>
                                        <div class="col-7"><span class="detail-value">#<?php echo $user['id']; ?></span></div>
                                    </div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-5"><span class="detail-label">Full Name:</span></div>
                                        <div class="col-7"><span class="detail-value"><?php echo htmlspecialchars($user['fullname']); ?></span></div>
                                    </div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-5"><span class="detail-label">Email:</span></div>
                                        <div class="col-7"><span class="detail-value"><?php echo htmlspecialchars($user['email']); ?></span></div>
                                    </div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-5"><span class="detail-label">Phone:</span></div>
                                        <div class="col-7"><span class="detail-value"><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'N/A'; ?></span></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-5"><span class="detail-label">Role:</span></div>
                                        <div class="col-7">
                                            <span class="<?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-student'; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-5"><span class="detail-label">Birth Place:</span></div>
                                        <div class="col-7"><span class="detail-value"><?php echo !empty($user['birth_place']) ? htmlspecialchars($user['birth_place']) : 'N/A'; ?></span></div>
                                    </div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-5"><span class="detail-label">Birth Year:</span></div>
                                        <div class="col-7"><span class="detail-value"><?php echo !empty($user['birth_year']) ? htmlspecialchars($user['birth_year']) : 'N/A'; ?></span></div>
                                    </div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-5"><span class="detail-label">Account Created:</span></div>
                                        <div class="col-7"><span class="detail-value"><?php echo !empty($user['created_at']) ? date('M j, Y g:i A', strtotime($user['created_at'])) : 'N/A'; ?></span></div>
                                    </div>
                                </div>
                                
                                <!-- Removed Last Updated section since you don't have updated_at column -->
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="detail-row">
                                    <div class="detail-label mb-2">Address:</div>
                                    <div class="detail-value">
                                        <?php echo !empty($user['address']) ? htmlspecialchars($user['address']) : 'N/A'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-12 text-end">
                        <a href="manage_users.php" class="btn btn-secondary me-2">Back to Users</a>
                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning">Edit User</a>
                    </div>
                </div>
            </div>
        </div>
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