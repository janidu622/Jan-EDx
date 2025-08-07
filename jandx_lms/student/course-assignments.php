
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

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {
    $assignment_id = (int)$_POST['assignment_id'];
    $submission_text = trim($_POST['submission_text']);
    
    // Check if assignment exists and is active
    $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ? AND course_id = ? AND is_active = 1");
    $stmt->execute([$assignment_id, $course_id]);
    $assignment = $stmt->fetch();
    
    if ($assignment) {
        // Check if already submitted
        $stmt = $pdo->prepare("SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
        $stmt->execute([$assignment_id, $_SESSION['user_id']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $file_name = null;
            $file_path = null;
            
            // Handle file upload
            if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === 0) {
                $upload_dir = '../uploads/submissions/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $original_name = $_FILES['submission_file']['name'];
                $file_tmp = $_FILES['submission_file']['tmp_name'];
                $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                $unique_filename = $assignment_id . '_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $unique_filename;
                
                if (move_uploaded_file($file_tmp, $file_path)) {
                    $file_name = $original_name;
                }
            }
            
            // Insert submission
            $stmt = $pdo->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, file_name, file_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$assignment_id, $_SESSION['user_id'], $submission_text, $file_name, $file_path]);
            
            $success_message = "Assignment submitted successfully!";
        } else {
            $error_message = "You have already submitted this assignment.";
        }
    } else {
        $error_message = "Invalid assignment.";
    }
}

// Get all assignments for this course with submission status
$stmt = $pdo->prepare("
    SELECT a.*, 
           sub.id as submission_id,
           sub.submitted_at,
           sub.marks_obtained,
           sub.feedback,
           sub.status as submission_status
    FROM assignments a 
    LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
    WHERE a.course_id = ? AND a.is_active = 1 
    ORDER BY a.due_date ASC
");
$stmt->execute([$_SESSION['user_id'], $course_id]);
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
        
        .course-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .assignments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .assignment-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .assignment-card.overdue {
            border-left: 4px solid #dc3545;
        }
        
        .assignment-card.submitted {
            border-left: 4px solid #28a745;
        }
        
        .assignment-card.pending {
            border-left: 4px solid #ffc107;
        }
        
        .assignment-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .assignment-description {
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .assignment-info {
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .due-date {
            font-weight: bold;
        }
        
        .due-date.overdue {
            color: #dc3545;
        }
        
        .due-date.upcoming {
            color: #ffc107;
        }
        
        .due-date.future {
            color: #28a745;
        }
        
        .submission-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-submitted {
            background: #d4edda;
            color: #155724;
        }
        
        .status-graded {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-overdue {
            background: #f8d7da;
            color: #721c24;
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
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-download {
            background: #17a2b8;
            color: white;
        }
        
        .submission-form {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group textarea, .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        
        .no-assignments {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .toggle-form {
            cursor: pointer;
            color: #007bff;
            text-decoration: underline;
        }
        
        .form-hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="assignments-container">
        <div class="header">
            <h2>Course Assignments</h2>
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Course Info -->
        <div class="course-info">
            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
            <p><strong>Course Code:</strong> <?php echo htmlspecialchars($course['course_code']); ?></p>
            <p><strong>Instructor:</strong> <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></p>
        </div>

        <!-- Assignments List -->
        <div class="assignments-section">
            <h3>Course Assignments (<?php echo count($assignments); ?>)</h3>
            
            <?php if (empty($assignments)): ?>
                <div class="no-assignments">
                    <h4>No assignments available yet</h4>
                    <p>Your instructor hasn't created any assignments for this course yet.</p>
                </div>
            <?php else: ?>
                <div class="assignments-grid">
                    <?php foreach ($assignments as $assignment): ?>
                        <?php 
                        $due_date = new DateTime($assignment['due_date']);
                        $now = new DateTime();
                        $is_overdue = $due_date < $now;
                        $is_upcoming = $due_date->diff($now)->days <= 3 && !$is_overdue;
                        
                        $card_class = '';
                        $status_class = '';
                        $status_text = '';
                        
                        if ($assignment['submission_id']) {
                            if ($assignment['submission_status'] === 'graded') {
                                $card_class = 'submitted';
                                $status_class = 'status-graded';
                                $status_text = 'Graded';
                            } else {
                                $card_class = 'submitted';
                                $status_class = 'status-submitted';
                                $status_text = 'Submitted';
                            }
                        } elseif ($is_overdue) {
                            $card_class = 'overdue';
                            $status_class = 'status-overdue';
                            $status_text = 'Overdue';
                        } else {
                            $card_class = 'pending';
                            $status_class = 'status-pending';
                            $status_text = 'Pending';
                        }
                        ?>
                        
                        <div class="assignment-card <?php echo $card_class; ?>">
                            <div class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                            
                            <div class="assignment-description"><?php echo htmlspecialchars($assignment['description']); ?></div>
                            
                            <?php if ($assignment['instructions']): ?>
                                <div class="assignment-info">
                                    <strong>Instructions:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($assignment['instructions'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="assignment-info">
                                <strong>Due Date:</strong> 
                                <span class="due-date <?php echo $is_overdue ? 'overdue' : ($is_upcoming ? 'upcoming' : 'future'); ?>">
                                    <?php echo $due_date->format('M j, Y g:i A'); ?>
                                </span><br>
                                <strong>Maximum Marks:</strong> <?php echo $assignment['max_marks']; ?><br>
                                <strong>Status:</strong> <span class="submission-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </div>
                            
                            <?php if ($assignment['attachment_name']): ?>
                                <div class="assignment-info">
                                    <strong>Assignment File:</strong> 
                                    <a href="<?php echo $assignment['attachment_path']; ?>" target="_blank" class="btn btn-download">
                                        📥 <?php echo htmlspecialchars($assignment['attachment_name']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($assignment['submission_id']): ?>
                                <!-- Already Submitted -->
                                <div class="assignment-info">
                                    <strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($assignment['submitted_at'])); ?>
                                    <?php if ($assignment['marks_obtained'] !== null): ?>
                                        <br><strong>Grade:</strong> <?php echo $assignment['marks_obtained']; ?>/<?php echo $assignment['max_marks']; ?>
                                    <?php endif; ?>
                                    <?php if ($assignment['feedback']): ?>
                                        <br><strong>Feedback:</strong> <?php echo htmlspecialchars($assignment['feedback']); ?>
                                    <?php endif; ?>
                                </div>
                            <?php elseif (!$is_overdue): ?>
                                <!-- Can Submit -->
                                <div style="margin-top: 15px;">
                                    <span class="toggle-form" onclick="toggleSubmissionForm(<?php echo $assignment['id']; ?>)">
                                        📝 Submit Assignment
                                    </span>
                                    
                                    <div id="form_<?php echo $assignment['id']; ?>" class="submission-form form-hidden">
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                            
                                            <div class="form-group">
                                                <label for="submission_text_<?php echo $assignment['id']; ?>">Submission Text (Optional)</label>
                                                <textarea id="submission_text_<?php echo $assignment['id']; ?>" 
                                                          name="submission_text" 
                                                          rows="4" 
                                                          placeholder="Enter your submission text, notes, or comments..."></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="submission_file_<?php echo $assignment['id']; ?>">Upload File (Optional)</label>
                                                <input type="file" 
                                                       id="submission_file_<?php echo $assignment['id']; ?>" 
                                                       name="submission_file">
                                            </div>
                                            
                                            <div style="text-align: right;">
                                                <button type="button" 
                                                        onclick="toggleSubmissionForm(<?php echo $assignment['id']; ?>)" 
                                                        class="btn" 
                                                        style="background: #6c757d; color: white; margin-right: 10px;">
                                                    Cancel
                                                </button>
                                                <button type="submit" name="submit_assignment" class="btn btn-success">
                                                    Submit Assignment
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Overdue -->
                                <div style="margin-top: 15px; color: #dc3545; font-weight: bold;">
                                    ⚠️ This assignment is overdue and can no longer be submitted.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSubmissionForm(assignmentId) {
            const form = document.getElementById('form_' + assignmentId);
            
            if (form.classList.contains('form-hidden')) {
                // Hide all other forms first
                const allForms = document.querySelectorAll('.submission-form');
                allForms.forEach(f => f.classList.add('form-hidden'));
                
                // Show this form
                form.classList.remove('form-hidden');
            } else {
                form.classList.add('form-hidden');
            }
        }
    </script>
</body>
</html>