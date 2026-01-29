<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../auth/login.php');
    exit();
}

// Get student data from database
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($_POST) {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $birth_place = trim($_POST['birth_place']);
    $birth_year = trim($_POST['birth_year']);
    
    // Get current email from database (not from form input)
    $current_email = $user['email'];
    
    // Handle file upload if provided
    $profile_image = $user['profile_image']; // Keep existing image as default
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $file_type = mime_content_type($_FILES['profile_image']['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            // Validate file size (max 5MB)
            if ($_FILES['profile_image']['size'] <= 5000000) {
                $upload_dir = '../assets/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                $file_name = time() . '_' . $user_id . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    // Delete old image if it's not the default
                    if ($user['profile_image'] !== 'default.png' && file_exists($upload_dir . $user['profile_image'])) {
                        unlink($upload_dir . $user['profile_image']);
                    }
                    $profile_image = $file_name;
                } else {
                    $error_message = "Error uploading profile image.";
                }
            } else {
                $error_message = "File size exceeds 5MB limit.";
            }
        } else {
            $error_message = "Invalid file type. Only JPG and PNG files are allowed.";
        }
    } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] != 4) {
        // Error occurred during file upload
        $error_message = "Error uploading profile image: " . $_FILES['profile_image']['error'];
    }
    
    if (!isset($error_message)) {
        // Update user data (email is not updated)
        $update_sql = "UPDATE users SET fullname=?, email=?, phone=?, address=?, birth_place=?, birth_year=?, profile_image=? WHERE id=?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "sssssssi", $fullname, $current_email, $phone, $address, $birth_place, $birth_year, $profile_image, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Update session data
            $_SESSION['user_name'] = $fullname;
            $_SESSION['user_email'] = $current_email;
            $_SESSION['user_image'] = $profile_image;
            
            $success_message = "Profile updated successfully!";
            // Refresh user data
            $sql = "SELECT * FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Error updating profile: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($update_stmt);
    }
}

$student_name = $user['fullname'] ?? 'Student Name';
$student_email = $user['email'] ?? 'student@example.com';
$student_phone = $user['phone'] ?? '';
$student_address = $user['address'] ?? '';
$student_birth_place = $user['birth_place'] ?? '';
$student_academic_year = $user['birth_year'] ?? '';
$student_image = $user['profile_image'] ?? 'default.png';

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Complaint Management System</title>
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
        
        .profile-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .profile-header {
            background: var(--primary-accent);
            color: #000;
            padding: 20px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .profile-body {
            padding: 30px;
        }
        
        .profile-img-container {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid #e2e8f0;
            object-fit: cover;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .btn-save-profile {
            background: var(--primary-accent);
            border: none;
            color: #000;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            width: 100%;
        }
        
        .btn-save-profile:hover {
            background: #67e8b8;
            transform: translateY(-2px);
        }
        
        .btn-cancel {
            background: #6c757d;
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            width: 100%;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
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
        <div class="card profile-card">
            <div class="card-header profile-header">
                <div>
                    <i class="fas fa-pencil me-2"></i>Edit Profile
                </div>
                <a href="profile.php" class="btn btn-outline-dark">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
            </div>
            <div class="card-body profile-body">
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
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="profile-img-container">
                        <?php $imageExists = !empty($student_image) && $student_image !== 'default.png' && file_exists('../assets/uploads/' . $student_image); ?>
                        <img src="<?php echo $imageExists ? '../assets/uploads/' . htmlspecialchars($student_image) : 'https://ui-avatars.com/api/?name=' . urlencode($student_name) . '&background=0D8ABC&color=fff&size=128'; ?>" alt="Profile Image" class="profile-img">
                        <div class="mt-3">
                            <label for="profile_image" class="form-label">Change Profile Picture</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png">
                            <div class="form-text">Upload a new profile picture (JPG, PNG only, max 5MB)</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fullname" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($student_name); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address (read-only)</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student_email); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($student_phone); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="birth_year" class="form-label">Birth Year</label>
                                <input type="number" class="form-control" id="birth_year" name="birth_year" value="<?php echo htmlspecialchars($student_academic_year); ?>" min="1900" max="2025">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($student_address); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="birth_place" class="form-label">Birth Place</label>
                        <input type="text" class="form-control" id="birth_place" name="birth_place" value="<?php echo htmlspecialchars($student_birth_place); ?>">
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-save-profile">Save Changes</button>
                        </div>
                        <div class="col-md-6">
                            <a href="profile.php" class="btn btn-cancel">Cancel</a>
                        </div>
                    </div>
                </form>
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