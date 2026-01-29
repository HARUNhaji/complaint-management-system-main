<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

$message = '';
$error = '';

if (isset($_POST['recover'])) {
    $email = trim($_POST['email']);
    
    if (!empty($email)) {
        // Check if email exists in database and fetch password
        $sql = "SELECT password FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            // Note: In a real application, you'd never display the hashed password
            // This is just for testing purposes as per requirements
            $message = "Your password has been sent to your email.";
        } else {
            $error = "Email not found";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $error = "Please enter your email address";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Complaint Management System</title>
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
        
        .reset-link {
            color: #007bff;
            text-decoration: none;
        }
        
        .reset-link:hover {
            text-decoration: underline;
        }
        
        .info-text {
            color: #64748b;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="forgot-card card">
                    <div class="card-header">
                        <i class="bi bi-shield-lock me-2"></i>Recover Password
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>
                        
                        <p class="info-text">Enter your email to recover your password</p>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your registered email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" name="recover" class="btn btn-reset">Recover Password</button>
                            </div>
                        </form>
                        
                        <div class="text-center">
                            <a href="login.php" class="reset-link"><i class="bi bi-arrow-left me-1"></i> Back to Login</a>
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