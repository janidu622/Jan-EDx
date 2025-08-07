
<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_teachers FROM users WHERE role = 'teacher'");
$total_teachers = $stmt->fetch()['total_teachers'];

$stmt = $pdo->query("SELECT COUNT(*) as total_students FROM users WHERE role = 'student'");
$total_students = $stmt->fetch()['total_students'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LMS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <h1>Super Admin Dashboard</h1>
            
            <div class="user-info">
                <p><strong>Welcome:</strong> <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></p>
                <p><strong>Role:</strong> Super Administrator</p>
                <a href="../auth/logout.php" class="logout-btn">Logout</a>
            </div>
            
            <div class="stats">
                <h3>System Statistics:</h3>
                <p>Total Users: <strong><?php echo $total_users; ?></strong></p>
                <p>Total Teachers: <strong><?php echo $total_teachers; ?></strong></p>
                <p>Total Students: <strong><?php echo $total_students; ?></strong></p>
            </div>
            
            <div class="actions">
                <h3>Quick Actions:</h3>
                <p>• Manage all users</p>
                <p>• View system reports</p>
                <p>• Configure system settings</p>
            </div>
        </div>
    </div>
</body>
</html>