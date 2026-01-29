<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include('../config/db.php');

$error_message = '';
$success_message = '';

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error_message = "Please enter your email address.";
    } else {
        // Check if email exists in the database
        $sql = "SELECT id, fullname, email, role FROM users WHERE email = ? AND role = 'student'";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt === false) {
            $error_message = "Database error: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                
                // In a real application, you would send an email with a reset link
                // For this implementation, we'll show a message
                $success_message = "A password reset link has been sent to your email address. Please check your inbox.";
            } else {
                $error_message = "Email not found. Please try again.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Complaint Management System</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        
        .forgot-card {
            max-width: 500px;
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
        
        .btn-reset {
            background: var(--primary-accent);
            border: none;
            color: #000;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
        }
        
        .btn-reset:hover {
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
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .password-info {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="forgot-card card">
                    <div class="card-header">
                        <i class="fas fa-key me-2"></i>Reset Your Password
                    </div>
                    <div class="card-body">
                        <div class="password-info">
                            <p class="mb-0"><i class="fas fa-info-circle me-2"></i>Enter your email address and we'll send you a link to reset your password.</p>
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
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" name="submit" class="btn btn-reset">
                                    <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center">
                            <p><a href="login.php" class="login-link"><i class="fas fa-arrow-left me-1"></i> Back to Login</a></p>
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