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

// Fetch user details
$sql = "SELECT id, fullname, email, phone, address, birth_place, birth_year, profile_image, role FROM users WHERE id = ? AND role = ?";
$stmt = mysqli_prepare($conn, $sql);
$role = 'student';
mysqli_stmt_bind_param($stmt, "is", $user_id, $role);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header('Location: manage_users.php');
    exit();
}

mysqli_stmt_close($stmt);

// Handle form submission
if ($_POST) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $birth_place = trim($_POST['birth_place']);
    $birth_year = trim($_POST['birth_year']);
    $role = trim($_POST['role']);
    
    // Handle profile image upload
    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            $upload_dir = '../assets/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Delete old image if it's not the default
                if ($user['profile_image'] != 'default.png' && file_exists($upload_dir . $user['profile_image'])) {
                    unlink($upload_dir . $user['profile_image']);
                }
                $profile_image = $new_filename;
            }
        }
    }
    
    // Update user - REMOVED updated_at=NOW() since column doesn't exist
    $update_sql = "UPDATE users SET fullname=?, email=?, phone=?, address=?, birth_place=?, birth_year=?, role=?, profile_image=? WHERE id=? AND role='student'";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    
    // Bind parameters: fullname(s), email(s), phone(s), address(s), birth_place(s), birth_year(i), role(s), profile_image(s), user_id(i)
    // Types: s=string, i=integer
    mysqli_stmt_bind_param($update_stmt, "sssssissi", $fullname, $email, $phone, $address, $birth_place, $birth_year, $role, $profile_image, $user_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        mysqli_close($conn);
        header("Location: view_user.php?id=$user_id");
        exit();
    } else {
        $error_message = "Error updating user: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($update_stmt);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Complaint Management System</title>
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
        
        .user-edit-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .card-header-edit {
            background: var(--primary-accent);
            color: #000;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .btn-update {
            background: var(--primary-accent);
            border: none;
            color: #000;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
        }
        
        .btn-update:hover {
            background: #67e8b8;
        }
        
        .btn-cancel {
            background: #6c757d;
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .profile-preview {
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
            <h1 class="h3">Edit User</h1>
            <a href="manage_users.php" class="btn btn-cancel">
                <i class="fas fa-arrow-left me-1"></i> Back to Users
            </a>
        </div>

        <?php if (isset($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <div class="card user-edit-card">
            <div class="card-header card-header-edit">
                <i class="fas fa-user-edit me-2"></i>Edit User Profile
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4 text-center mb-4 mb-md-0">
                            <img src="../assets/uploads/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                 alt="Profile" class="profile-preview img-fluid mb-3">
                            <div class="form-group">
                                <label for="profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                                <div class="form-text">Choose a new profile image (JPG, PNG, GIF)</div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fullname" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="fullname" name="fullname" 
                                               value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="birth_place" class="form-label">Birth Place</label>
                                        <input type="text" class="form-control" id="birth_place" name="birth_place" 
                                               value="<?php echo htmlspecialchars($user['birth_place']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="birth_year" class="form-label">Birth Year</label>
                                        <input type="number" class="form-control" id="birth_year" name="birth_year" 
                                               value="<?php echo htmlspecialchars($user['birth_year']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role" class="form-label">Role *</label>
                                        <select class="form-select" id="role" name="role" required>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="manage_users.php" class="btn btn-cancel">Cancel</a>
                        <button type="submit" class="btn btn-update">
                            <i class="fas fa-save me-1"></i> Update User
                        </button>
                    </div>
                </form>
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