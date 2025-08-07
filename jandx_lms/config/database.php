
<?php
// Database connection settings
$host = 'localhost';
$dbname = 'lms_db1';
$username = 'root';  // Change if your MySQL username is different
$password = '';      // Change if your MySQL has a password

try {
    // Create connection using PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connected successfully"; // Remove this line after testing
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>