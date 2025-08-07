<?php
require_once 'config.php';

// This script creates an admin user for testing
// Run this once, then delete it for security

$admin_email = 'admin@janedx.com';
$admin_password = 'admin123';
$admin_name = 'Admin User';

try {
    // Check if admin already exists
    $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->execute([$admin_email]);
    
    if ($check_stmt->fetch()) {
        echo "<h2>Admin user already exists!</h2>";
        echo "<p>Email: <strong>$admin_email</strong></p>";
        echo "<p>Password: <strong>$admin_password</strong></p>";
        echo "<p><a href='login.php'>Go to Login Page</a></p>";
    } else {
        // Create admin user
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$admin_name, $admin_email, $hashed_password]);
        
        echo "<h2 style='color: green;'>Admin user created successfully!</h2>";
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h3>Login Credentials:</h3>";
        echo "<p><strong>Email:</strong> $admin_email</p>";
        echo "<p><strong>Password:</strong> $admin_password</p>";
        echo "</div>";
        echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
        echo "<hr>";
        echo "<p style='color: red;'><strong>Security Note:</strong> Delete this file (create_admin.php) after creating the admin user!</p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error creating admin user:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Make sure your database connection is working properly.</p>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Use the credentials above to login</li>";
echo "<li>Delete this create_admin.php file for security</li>";
echo "<li>Test the OAuth login with Google/Facebook</li>";
echo "<li>Browse and purchase courses to test the full functionality</li>";
echo "</ol>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Setup - <?php echo SITE_NAME; ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h2 { color: #333; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="warning">
        <strong>⚠️ Security Warning:</strong> This page creates admin credentials. Delete this file after use!
    </div>
</body>
</html>