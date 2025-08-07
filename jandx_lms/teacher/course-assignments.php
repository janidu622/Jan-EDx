
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

// Handle assignment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $instructions = trim($_POST['instructions']);
    $due_date = $_POST['due_date'];
    $max_marks = (int)$_POST['max_marks'];
    
    if (!empty($title) && !empty($description) && !empty($due_date)) {
        $attachment_name = null;
        $attachment_path = null;
        
        // Handle optional file attachment
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
            $upload_dir = '../uploads/assignments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = $_FILES['attachment']['name'];
            $file_tmp = $_FILES['attachment']['tmp_name'];
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_filename = $course_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
            $attachment_path = $upload_dir . $unique_filename;
            
            if (move_uploaded_file($file_tmp, $attachment_path)) {
                $attachment_name = $file_name;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO assignments (course_id, title, description, instructions, due_date, max_marks, attachment_name, attachment_path, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$course_id, $title, $description, $instructions, $due_date, $max_marks, $attachment_name, $attachment_path, $_SESSION['user_id']]);
        
        $success_message = "Assignment created successfully!";
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Handle assignment deletion
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Get attachment path before deleting
    $stmt = $pdo->prepare("SELECT attachment_path FROM assignments WHERE id = ? AND course_id = ?");
    $stmt->execute([$delete_id, $course_id]);
    $assignment = $stmt->fetch();
    
    if ($assignment) {
        // Delete attachment file if exists
        if ($assignment['attachment_path'] && file_exists($assignment['attachment_path'])) {
            unlink($assignment['attachment_path']);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ? AND course_id = ?");
        $stmt->execute([$delete_id, $course_id]);
        
        $success_message = "Assignment deleted successfully!";
    }
}

// Get all assignments for this course
$stmt = $pdo->prepare("
    SELECT a.*, 
           COUNT(sub.id) as submission_count,
           COUNT(CASE WHEN sub.status = 'graded' THEN 1 END) as graded_count
    FROM assignments a 
    LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id 
    WHERE a.course_id = ? 
    GROUP BY a.id 
    ORDER BY a.due_date DESC
");
$stmt->execute([$course_id]);
$assignments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Assignments - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .assignments-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .create-form {
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .assignments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .assignment-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .assignment-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .assignment-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .due-date {
            font-weight: bold;
            color: #dc3545;
        }
        
        .due-date.future {
            color: #28a745;
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
        
        .toggle-form {
            margin-bottom: 20px;
        }
        
        .form-hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="assignments-container">
        <div class="header">
            <h2>Course Assignments - <?php echo htmlspecialchars($course['title']); ?></h2>
            <a href="view-course.php?id=<?php echo $course_id; ?>" class="btn btn-primary">Back to Course</a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Toggle Create Form Button -->
        <div class="toggle-form">
            <button onclick="toggleForm()" class="btn btn-success" id="toggleBtn">Create New Assignment</button>
        </div>

        <!-- Create Assignment Form -->
        <div class="create-form form-hidden" id="createForm">
            <h3>Create New Assignment</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Assignment Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="instructions">Instructions (Optional)</label>
                    <textarea id="instructions" name="instructions" rows="4"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="due_date">Due Date & Time</label>
                        <input type="datetime-local" id="due_date" name="due_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_marks">Maximum Marks</label>
                        <input type="number" id="max_marks" name="max_marks" value="100" min="1" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="attachment">Attachment (Optional)</label>
                    <input type="file" id="attachment" name="attachment">
                    <small>You can attach reference documents, templates, etc.</small>
                </div>
                
                <div style="text-align: right;">
                    <button type="button" onclick="toggleForm()" class="btn" style="background: #6c757d; color: white; margin-right: 10px;">Cancel</button>
                    <button type="submit" name="create_assignment" class="btn btn-success">Create Assignment</button>
                </div>
            </form>
        </div>

        <!-- Assignments List -->
        <div class="assignments-section">
            <h3>Course Assignments (<?php echo count($assignments); ?>)</h3>
            
            <?php if (empty($assignments)): ?>
                <p>No assignments created yet.</p>
            <?php else: ?>
                <div class="assignments-grid">
                    <?php foreach ($assignments as $assignment): ?>
                        <?php 
                        $due_date = new DateTime($assignment['due_date']);
                        $now = new DateTime();
                        $is_future = $due_date > $now;
                        ?>
                        <div class="assignment-card">
                            <div class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                            
                            <div class="assignment-description"><?php echo htmlspecialchars($assignment['description']); ?></div>
                            
                            <div class="assignment-info">
                                <strong>Due Date:</strong> 
                                <span class="due-date <?php echo $is_future ? 'future' : ''; ?>">
                                    <?php echo $due_date->format('M j, Y g:i A'); ?>
                                </span><br>
                                <strong>Max Marks:</strong> <?php echo $assignment['max_marks']; ?><br>
                                <strong>Submissions:</strong> <?php echo $assignment['submission_count']; ?><br>
                                <strong>Graded:</strong> <?php echo $assignment['graded_count']; ?>
                            </div>
                            
                            <?php if ($assignment['attachment_name']): ?>
                                <div class="assignment-info">
                                    <strong>Attachment:</strong> 
                                    <a href="<?php echo $assignment['attachment_path']; ?>" target="_blank">
                                        <?php echo htmlspecialchars($assignment['attachment_name']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="assignment-actions" style="margin-top: 15px;">
                                <a href="assignment-submissions.php?assignment_id=<?php echo $assignment['id']; ?>" 
                                   class="btn btn-primary">View Submissions</a>
                                <a href="?course_id=<?php echo $course_id; ?>&delete_id=<?php echo $assignment['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this assignment? This will also delete all submissions.')" 
                                   class="btn btn-danger">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleForm() {
            const form = document.getElementById('createForm');
            const btn = document.getElementById('toggleBtn');
            
            if (form.classList.contains('form-hidden')) {
                form.classList.remove('form-hidden');
                btn.textContent = 'Cancel';
                btn.style.background = '#6c757d';
            } else {
                form.classList.add('form-hidden');
                btn.textContent = 'Create New Assignment';
                btn.style.background = '#28a745';
            }
        }
    </script>
</body>
</html>