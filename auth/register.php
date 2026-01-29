<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

if (isset($_POST['register'])) {
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $birth    = trim($_POST['birth_place']);
    $year     = trim($_POST['year']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords match
    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } else {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $role     = "student";
    }

    // Validate inputs
    if (!isset($error_message) && (empty($fullname) || empty($email) || empty($phone) || empty($address) || empty($birth) || empty($year) || empty($_POST['password']) || empty($_POST['confirm_password']))) {
        $error_message = "All fields are required";
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "Email already exists";
        } else {
            $image = $_FILES['image']['name'];
            if (!empty($image)) {
                $tmp   = $_FILES['image']['tmp_name'];
                $upload_dir = "../assets/uploads/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                move_uploaded_file($tmp, $upload_dir.$image);
            } else {
                $image = "default.png"; // Default image if no upload
            }

            $sql = "INSERT INTO users (fullname, email, phone, address, birth_place, birth_year, profile_image, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);
            
            if ($stmt === false) {
                $error_message = "Database error: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($stmt, "sssssssss", $fullname, $email, $phone, $address, $birth, $year, $image, $password, $role);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Redirect to login after successful registration
                    mysqli_close($conn);
                    header("Location: login.php?registered=success");
                    exit();
                } else {
                    $error_message = "Registration failed: " . mysqli_stmt_error($stmt);
                }
                
                mysqli_stmt_close($stmt);
            }
        }
        mysqli_stmt_close($check_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Complaint Management System</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        
        .register-card {
            max-width: 800px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: var(--primary-accent);
            color: #000;
            padding: 20px;
            font-weight: 600;
            text-align: center;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
        }
        
        .form-control:focus {
            border-color: #78FECF;
            box-shadow: 0 0 0 0.25rem rgba(120, 254, 207, 0.25);
        }
        
        .btn-register {
            background: var(--primary-accent);
            border: none;
            color: #000;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
        }
        
        .btn-register:hover {
            background: #67e8b8;
            transform: translateY(-2px);
        }
        
        .register-link {
            color: #007bff;
            text-decoration: none;
        }
        
        .register-link:hover {
            text-decoration: underline;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .section-divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 25px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="register-card card">
                    <div class="card-header">
                        <i class="bi bi-person-plus me-2"></i>Create Your Account
                    </div>
                    <div class="card-body">
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
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fullname" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="fullname" name="fullname" placeholder="Enter your full name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter your phone number" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="year" class="form-label">Birth Year</label>
                                        <input type="number" class="form-control" id="year" name="year" placeholder="Enter birth year" required min="1900" max="2025">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" placeholder="Enter your address" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="birth_place" class="form-label">Birth Place</label>
                                        <input type="text" class="form-control" id="birth_place" name="birth_place" placeholder="Enter birth place" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="image" class="form-label">Profile Image</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="section-divider"></div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" name="register" class="btn btn-register">Register Account</button>
                            </div>
                            
                            <div class="text-center">
                                <p>Already have an account? <a href="login.php" class="register-link">Login here</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    </script>
</body>
</html>
