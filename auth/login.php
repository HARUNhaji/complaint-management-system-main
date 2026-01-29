<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $pass  = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt === false) {
        $error_message = "Database error: " . mysqli_error($conn);
    } else {
        mysqli_stmt_bind_param($stmt,"s",$email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($pass, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['fullname'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_image'] = $row['profile_image'];
                $_SESSION['role'] = $row['role'];

                if ($row['role'] == "admin") {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../student/dashboard.php");
                }
                exit();
            } else {
                $error_message = "Invalid email or password";
            }
        } else {
            $error_message = "Invalid email or password";
        }
        
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Complaint Management System</title>
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
        
        .login-container {
            max-width: 1000px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .login-left {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .login-right {
            background: white;
            padding: 40px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: var(--primary-accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .logo i {
            font-size: 2.5rem;
            color: #000;
        }
        
        .login-title {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .login-subtitle {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 30px;
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
        
        .btn-login {
            background: var(--primary-accent);
            border: none;
            color: #000;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
        }
        
        .btn-login:hover {
            background: #67e8b8;
            transform: translateY(-2px);
        }
        
        .login-link {
            color: #007bff;
            text-decoration: none;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
        
        .divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 25px 0;
        }
        
        @media (max-width: 992px) {
            .login-left {
                padding: 30px 20px;
            }
            
            .login-right {
                padding: 30px 20px;
            }
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-left {
                padding: 30px 20px;
            }
            
            .login-right {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="login-container card">
                    <div class="row g-0">
                        <div class="col-md-6 d-none d-md-block login-left">
                            <div class="logo">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <h2 class="login-title">Welcome to Complaint Management System</h2>
                            <p class="login-subtitle">Secure student complaint handling platform</p>
                            <div class="mt-4">
                                <i class="bi bi-graph-up" style="font-size: 3rem; color: var(--primary-accent);"></i>
                                <p class="mt-3">Track your complaints, get resolutions, and improve your academic experience</p>
                            </div>
                        </div>
                        <div class="col-md-6 login-right">
                            <div class="text-center mb-4">
                                <div class="logo d-inline-block mb-3">
                                    <i class="bi bi-shield-lock"></i>
                                </div>
                                <h2 class="login-title">Login to Your Account</h2>
                                <p class="login-subtitle">Enter your credentials to access your dashboard</p>
                            </div>
                            
                            <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
                            <div class="alert alert-success" role="alert">
                                Registration successful! Please login with your credentials.
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                </div>
                                <div class="d-grid mb-3">
                                    <button type="submit" name="login" class="btn btn-login">Login</button>
                                </div>
                            </form>
                            
                            <div class="text-center">
                                <p class="mb-2">Don't have an account? <a href="register.php" class="login-link">Register here</a></p>
                                <p><a href="forget_password.php" class="login-link">Forgot Password?</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
