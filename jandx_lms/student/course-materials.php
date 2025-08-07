
<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../auth/login.php');
    exit();
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$course_id) {
    header('Location: dashboard.php');
    exit();
}

// Verify student is enrolled in this course
$stmt = $pdo->prepare("
    SELECT c.*, ce.enrolled_at 
    FROM courses c 
    JOIN course_enrollments ce ON c.id = ce.course_id 
    WHERE c.id = ? AND ce.student_id = ? AND ce.status = 'active'
");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: dashboard.php');
    exit();
}

// Get teacher name
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$course['teacher_id']]);
$teacher = $stmt->fetch();

// Get all materials for this course
$stmt = $pdo->prepare("
    SELECT cm.*, u.first_name, u.last_name 
    FROM course_materials cm 
    JOIN users u ON cm.uploaded_by = u.id 
    WHERE cm.course_id = ? AND cm.is_active = 1 
    ORDER BY cm.created_at DESC
");
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
        
        .course-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
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
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .material-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .material-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .material-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .material-info {
            color: #888;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .material-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-download {
            background: #28a745;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .file-icon {
            display: inline-block;
            width: 24px;
            height: 24px;
            background-size: contain;
            vertical-align: middle;
            margin-right: 8px;
        }
        
        .no-materials {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="materials-container">
        <div class="header">
            <h2>Course Materials</h2>
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>

        <!-- Course Info -->
        <div class="course-info">
            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
            <p><strong>Course Code:</strong> <?php echo htmlspecialchars($course['course_code']); ?></p>
            <p><strong>Instructor:</strong> <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></p>
            <p><strong>Enrolled:</strong> <?php echo date('M j, Y', strtotime($course['enrolled_at'])); ?></p>
        </div>

        <!-- Search Box -->
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search materials..." onkeyup="searchMaterials()">
        </div>

        <!-- Materials List -->
        <div class="materials-section">
            <h3>Available Materials (<?php echo count($materials); ?>)</h3>
            
            <?php if (empty($materials)): ?>
                <div class="no-materials">
                    <h4>No materials available yet</h4>
                    <p>Your instructor hasn't uploaded any materials for this course yet. Check back later!</p>
                </div>
            <?php else: ?>
                <div class="materials-grid" id="materialsGrid">
                    <?php foreach ($materials as $material): ?>
                        <div class="material-card" data-title="<?php echo strtolower($material['title']); ?>" 
                             data-description="<?php echo strtolower($material['description']); ?>">
                            
                            <div class="material-title"><?php echo htmlspecialchars($material['title']); ?></div>
                            
                            <?php if ($material['description']): ?>
                                <div class="material-description"><?php echo htmlspecialchars($material['description']); ?></div>
                            <?php endif; ?>
                            
                            <div class="material-info">
                                <div>
                                    <span class="file-icon">📄</span>
                                    <strong>File:</strong> <?php echo htmlspecialchars($material['file_name']); ?>
                                </div>
                                <div style="margin-top: 5px;">
                                    <strong>Size:</strong> <?php echo formatFileSize($material['file_size']); ?>
                                </div>
                            </div>
                            
                            <div style="text-align: center;">
                                <a href="<?php echo $material['file_path']; ?>" 
                                   target="_blank" 
                                   class="btn btn-download">
                                    📥 View/Download
                                </a>
                            </div>
                            
                            <div class="material-meta">
                                <span>Uploaded by <?php echo htmlspecialchars($material['first_name'] . ' ' . $material['last_name']); ?></span>
                                <span><?php echo date('M j, Y', strtotime($material['created_at'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function searchMaterials() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const grid = document.getElementById('materialsGrid');
            const cards = grid.getElementsByClassName('material-card');

            for (let i = 0; i < cards.length; i++) {
                const card = cards[i];
                const title = card.getAttribute('data-title');
                const description = card.getAttribute('data-description');
                
                if (title.indexOf(filter) > -1 || description.indexOf(filter) > -1) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>

<?php
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>