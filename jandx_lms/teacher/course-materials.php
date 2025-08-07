<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../auth/login.php');
    exit();
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$course_id) {
    header('Location: dashboard.php');
    exit();
}

// Verify teacher owns this course
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: dashboard.php');
    exit();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_material'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (!empty($title) && isset($_FILES['material_file']) && $_FILES['material_file']['error'] === 0) {
        $upload_dir = '../uploads/materials/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = $_FILES['material_file']['name'];
        $file_tmp = $_FILES['material_file']['tmp_name'];
        $file_size = $_FILES['material_file']['size'];
        $file_type = $_FILES['material_file']['type'];
        
        // Generate unique filename
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_filename = $course_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $unique_filename;
        
        if (move_uploaded_file($file_tmp, $file_path)) {
            $stmt = $pdo->prepare("INSERT INTO course_materials (course_id, title, description, file_name, file_path, file_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$course_id, $title, $description, $file_name, $file_path, $file_type, $file_size, $_SESSION['user_id']]);
            
            $success_message = "Material uploaded successfully!";
        } else {
            $error_message = "Failed to upload file.";
        }
    } else {
        $error_message = "Please provide title and select a file.";
    }
}

// Handle delete material
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Get file path before deleting
    $stmt = $pdo->prepare("SELECT file_path FROM course_materials WHERE id = ? AND course_id = ?");
    $stmt->execute([$delete_id, $course_id]);
    $material = $stmt->fetch();
    
    if ($material) {
        // Delete file
        if (file_exists($material['file_path'])) {
            unlink($material['file_path']);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM course_materials WHERE id = ? AND course_id = ?");
        $stmt->execute([$delete_id, $course_id]);
        
        $success_message = "Material deleted successfully!";
    }
}

// Get all materials for this course
$stmt = $pdo->prepare("SELECT * FROM course_materials WHERE course_id = ? ORDER BY created_at DESC");
$stmt->execute([$course_id]);
$materials = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Materials - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .materials-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .upload-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .materials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .material-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .material-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .material-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="materials-container">
        <div class="header">
            <h2>Course Materials - <?php echo htmlspecialchars($course['title']); ?></h2>
            <a href="view-course.php?id=<?php echo $course_id; ?>" class="btn btn-primary">Back to Course</a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="upload-form">
            <h3>Upload New Material</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Material Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="material_file">Select File</label>
                    <input type="file" id="material_file" name="material_file" required>
                    <small>Supported formats: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, Images</small>
                </div>
                
                <button type="submit" name="upload_material" class="btn btn-primary">Upload Material</button>
            </form>
        </div>

        <!-- Materials List -->
        <div class="materials-section">
            <h3>Uploaded Materials (<?php echo count($materials); ?>)</h3>
            
            <?php if (empty($materials)): ?>
                <p>No materials uploaded yet.</p>
            <?php else: ?>
                <div class="materials-grid">
                    <?php foreach ($materials as $material): ?>
                        <div class="material-card">
                            <div class="material-title"><?php echo htmlspecialchars($material['title']); ?></div>
                            
                            <?php if ($material['description']): ?>
                                <div class="material-description"><?php echo htmlspecialchars($material['description']); ?></div>
                            <?php endif; ?>
                            
                            <div class="material-info">
                                <strong>File:</strong> <?php echo htmlspecialchars($material['file_name']); ?><br>
                                <strong>Size:</strong> <?php echo round($material['file_size'] / 1024, 2); ?> KB<br>
                                <strong>Uploaded:</strong> <?php echo date('M j, Y g:i A', strtotime($material['created_at'])); ?>
                            </div>
                            
                            <div class="material-actions">
                                <a href="<?php echo $material['file_path']; ?>" target="_blank" class="btn btn-primary">View/Download</a>
                                <a href="?course_id=<?php echo $course_id; ?>&delete_id=<?php echo $material['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this material?')" 
                                   class="btn btn-danger">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>