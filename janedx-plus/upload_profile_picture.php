<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['profile_picture'];
$user_id = $_SESSION['user_id'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$file_type = mime_content_type($file['tmp_name']);

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.']);
    exit;
}

// Validate file size (max 5MB)
$max_size = 5 * 1024 * 1024; // 5MB in bytes
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File size too large. Maximum size is 5MB.']);
    exit;
}

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/profile_pictures/';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit;
    }
}

// Generate unique filename
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . $user_id . '_' . time() . '.' . strtolower($file_extension);
$filepath = $upload_dir . $filename;

// Get current user to check for existing profile picture
try {
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch();
    
    // Delete old profile picture if it exists and is a local file
    if ($current_user && $current_user['profile_picture']) {
        $old_file = $current_user['profile_picture'];
        if (strpos($old_file, 'uploads/') === 0 && file_exists($old_file)) {
            unlink($old_file);
        }
    }
} catch (Exception $e) {
    // Continue even if we can't delete the old file
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
    exit;
}

// Resize image if it's too large (optional, requires GD extension)
if (extension_loaded('gd')) {
    resizeImage($filepath, 400, 400); // Resize to max 400x400px
}

// Update database
try {
    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->execute([$filepath, $user_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profile picture updated successfully',
        'image_url' => $filepath
    ]);
} catch (Exception $e) {
    // Delete the uploaded file if database update fails
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    
    echo json_encode(['success' => false, 'message' => 'Failed to update profile picture in database']);
}

/**
 * Resize image to fit within specified dimensions while maintaining aspect ratio
 */
function resizeImage($filepath, $max_width, $max_height) {
    $image_info = getimagesize($filepath);
    if (!$image_info) {
        return false;
    }
    
    $width = $image_info[0];
    $height = $image_info[1];
    $mime_type = $image_info['mime'];
    
    // Check if resize is needed
    if ($width <= $max_width && $height <= $max_height) {
        return true;
    }
    
    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // Create image resource based on type
    switch ($mime_type) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($filepath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($filepath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($filepath);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $source = imagecreatefromwebp($filepath);
            } else {
                return false;
            }
            break;
        default:
            return false;
    }
    
    if (!$source) {
        return false;
    }
    
    // Create new image
    $destination = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save resized image
    $result = false;
    switch ($mime_type) {
        case 'image/jpeg':
            $result = imagejpeg($destination, $filepath, 85);
            break;
        case 'image/png':
            $result = imagepng($destination, $filepath, 6);
            break;
        case 'image/gif':
            $result = imagegif($destination, $filepath);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                $result = imagewebp($destination, $filepath, 85);
            }
            break;
    }
    
    // Clean up memory
    imagedestroy($source);
    imagedestroy($destination);
    
    return $result;
}
?>