
<?php
session_start();
require_once '../config/database.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role == 'super_admin') {
        header('Location: ../admin/dashboard.php');
    } elseif ($role == 'teacher') {
        header('Location: ../teacher/dashboard.php');
    } else {
        header('Location: ../student/dashboard.php');
    }
    exit;
}

// Handle login form submission
if ($_POST) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        // Find user in database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if user exists and password is correct
        if ($user && password_verify($password, $user['password'])) {
            // Create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            
            // Redirect based on role
            if ($user['role'] == 'super_admin') {
                header('Location: ../admin/dashboard.php');
            } elseif ($user['role'] == 'teacher') {
                header('Location: ../teacher/dashboard.php');
            } else {
                header('Location: ../student/dashboard.php');
            }
            exit;
        } else {
            $error_message = "Invalid email or password!";
        }
        
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LMS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Login to LMS</h2>
            
            <?php if (isset($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit">Login</button>
            </form>
            
            <p>Don't have an account? <a href="register.php">Register here</a><br> <a href="../../index.php">Go Back</a><br><br> <a href="../../signature_detector.html">Find it</a></p>
        </div>
    </div>
</body>
</html>