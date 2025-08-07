<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'janedx_plus');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'JaneDX+');
define('BASE_URL', 'http://localhost/Jan-EDx/janedx_plus');

// OAuth configuration
define('GOOGLE_CLIENT_ID', '916812700328-g45liu8sioh3h4d6lkd9f4irbqupo1a0.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('FACEBOOK_APP_ID', 'your_facebook_app_id');
define('FACEBOOK_APP_SECRET', 'your_facebook_app_secret');

// Start session
session_start();

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    if (!isLoggedIn()) return false;
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    return $user && $user['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect('index.php?error=access_denied');
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function formatPrice($price) {
    return 'Rs ' . number_format($price, 2);
}

function hasPurchasedCourse($user_id, $course_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM purchases WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    return $stmt->fetch() !== false;
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateSlug($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
}

function uploadImage($file, $directory = 'uploads/') {
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $directory . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filepath;
    }
    
    return false;
}

function getYouTubeEmbedUrl($url) {
    // Convert YouTube URL to embed format
    $video_id = '';
    
    // Handle different YouTube URL formats
    if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $url, $matches)) {
        $video_id = $matches[1];
    } elseif (preg_match('/youtu\.be\/([^?]+)/', $url, $matches)) {
        $video_id = $matches[1];
    } elseif (preg_match('/youtube\.com\/embed\/([^?]+)/', $url, $matches)) {
        return $url; // Already in embed format
    }
    
    if ($video_id) {
        return "https://www.youtube.com/embed/" . $video_id;
    }
    
    return $url; // Return original if not YouTube
}

// Add flash message functions
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function showFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = $flash['type'] === 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}
?>