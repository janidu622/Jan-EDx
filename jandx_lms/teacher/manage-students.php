
<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../auth/login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$course_id) {
    header('Location: dashboard.php');
    exit();
}

// Verify course belongs to teacher
$course_stmt = $pdo->prepare("SELECT title, max_students FROM courses WHERE id = ? AND teacher_id = ?");
$course_stmt->execute([$course_id, $teacher_id]);
$course = $course_stmt->fetch();

if (!$course) {
    header('Location: dashboard.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add_student') {
            $student_email = trim($_POST['student_email']);
            
            // Find student by email
            $student_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'student'");
            $student_stmt->execute([$student_email]);
            $student = $student_stmt->fetch();
            
            if (!$student) {
                throw new Exception('Student not found with this email address.');
            }
            
            // Check if already enrolled
            $check_enrollment = $pdo->prepare("SELECT id FROM course_enrollments WHERE course_id = ? AND student_id = ?");
            $check_enrollment->execute([$course_id, $student['id']]);
            
            if ($check_enrollment->fetch()) {
                throw new Exception('Student is already enrolled in this course.');
            }
            
            // Check max students limit
            $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM course_enrollments WHERE course_id = ? AND status = 'active'");
            $count_stmt->execute([$course_id]);
            $current_count = $count_stmt->fetch()['count'];
            
            if ($current_count >= $course['max_students']) {
                throw new Exception('Course has reached maximum student capacity.');
            }
            
            // Add enrollment
            $enroll_stmt = $pdo->prepare("
                INSERT INTO course_enrollments (course_id, student_id, semester, academic_year_id, status) 
                VALUES (?, ?, '1st Semester', 2, 'active')
            ");
            $enroll_stmt->execute([$course_id, $student['id']]);
            
            $success_message = 'Student enrolled successfully!';
            
        } elseif ($action === 'remove_student') {
            $enrollment_id = (int)$_POST['enrollment_id'];
            
            // Remove enrollment
            $remove_stmt = $pdo->prepare("
                DELETE FROM course_enrollments 
                WHERE id = ? AND course_id IN (SELECT id FROM courses WHERE teacher_id = ?)
            ");
            $remove_stmt->execute([$enrollment_id, $teacher_id]);
            
            $success_message = 'Student removed from course successfully!';
            
        } elseif ($action === 'update_status') {
            $enrollment_id = (int)$_POST['enrollment_id'];
            $new_status = $_POST['new_status'];
            
            // Update status
            $update_stmt = $pdo->prepare("
                UPDATE course_enrollments 
                SET status = ? 
                WHERE id = ? AND course_id IN (SELECT id FROM courses WHERE teacher_id = ?)
            ");
            $update_stmt->execute([$new_status, $enrollment_id, $teacher_id]);
            
            $success_message = 'Student status updated successfully!';
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch enrolled students
$students_stmt = $pdo->prepare("
    SELECT 
        ce.id as enrollment_id,
        ce.enrolled_at,
        ce.status,
        ce.grade,
        u.first_name,
        u.last_name,
        u.email,
        sp.student_reg_number,
        sp.year_of_study,
        d.name as department_name
    FROM course_enrollments ce
    JOIN users u ON ce.student_id = u.id
    LEFT JOIN student_profiles sp ON u.id = sp.user_id
    LEFT JOIN departments d ON sp.department_id = d.id
    WHERE ce.course_id = ?
    ORDER BY u.first_name, u.last_name
");
$students_stmt->execute([$course_id]);
$enrolled_students = $students_stmt->fetchAll();

// Get enrollment stats
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_enrolled,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_students,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_students,
        SUM(CASE WHEN status = 'dropped' THEN 1 ELSE 0 END) as dropped_students
    FROM course_enrollments 
    WHERE course_id = ?
");
$stats_stmt->execute([$course_id]);
$stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - <?php echo htmlspecialchars($course['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>LMS - Teacher Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-users me-2"></i>Manage Students</h2>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($course['title']); ?></p>
                    </div>
                    <a href="view-course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Course
                    </a>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Enrollment Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center bg-primary text-white">
                            <div class="card-body">
                                <h4><?php echo $stats['total_enrolled']; ?></h4>
                                <small>Total Enrolled</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body">
                                <h4><?php echo $stats['active_students']; ?></h4>
                                <small>Active Students</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-info text-white">
                            <div class="card-body">
                                <h4><?php echo $stats['completed_students']; ?></h4>
                                <small>Completed</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-warning text-white">
                            <div class="card-body">
                                <h4><?php echo $stats['dropped_students']; ?></h4>
                                <small>Dropped</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Student Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-user-plus me-2"></i>Add Student to Course</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="add_student">
                            <div class="col-md-8">
                                <label for="student_email" class="form-label">Student Email</label>
                                <input type="email" class="form-control" id="student_email" name="student_email" 
                                       placeholder="Enter student's email address" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Add Student
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Enrolled Students Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Enrolled Students (<?php echo count($enrolled_students); ?>/<?php echo $course['max_students']; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($enrolled_students)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No students enrolled in this course yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Registration No.</th>
                                            <th>Department</th>
                                            <th>Year of Study</th>
                                            <th>Enrolled Date</th>
                                            <th>Status</th>
                                            <th>Grade</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($enrolled_students as $student): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($student['email']); ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['student_reg_number'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($student['department_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($student['year_of_study'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($student['enrolled_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $student['status'] === 'active' ? 'success' : 
                                                             ($student['status'] === 'completed' ? 'info' : 'warning'); 
                                                    ?>">
                                                        <?php echo ucfirst($student['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $student['grade'] ? htmlspecialchars($student['grade']) : '-'; ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                                data-bs-toggle="dropdown">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><h6 class="dropdown-header">Change Status</h6></li>
                                                            <?php if ($student['status'] !== 'active'): ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="action" value="update_status">
                                                                        <input type="hidden" name="enrollment_id" value="<?php echo $student['enrollment_id']; ?>">
                                                                        <input type="hidden" name="new_status" value="active">
                                                                        <button type="submit" class="dropdown-item">
                                                                            <i class="fas fa-check text-success me-2"></i>Mark Active
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                            <?php if ($student['status'] !== 'completed'): ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="action" value="update_status">
                                                                        <input type="hidden" name="enrollment_id" value="<?php echo $student['enrollment_id']; ?>">
                                                                        <input type="hidden" name="new_status" value="completed">
                                                                        <button type="submit" class="dropdown-item">
                                                                            <i class="fas fa-graduation-cap text-info me-2"></i>Mark Completed
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                            <?php if ($student['status'] !== 'dropped'): ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="action" value="update_status">
                                                                        <input type="hidden" name="enrollment_id" value="<?php echo $student['enrollment_id']; ?>">
                                                                        <input type="hidden" name="new_status" value="dropped">
                                                                        <button type="submit" class="dropdown-item">
                                                                            <i class="fas fa-times text-warning me-2"></i>Mark Dropped
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" class="d-inline" 
                                                                      onsubmit="return confirm('Are you sure you want to remove this student from the course?')">
                                                                    <input type="hidden" name="action" value="remove_student">
                                                                    <input type="hidden" name="enrollment_id" value="<?php echo $student['enrollment_id']; ?>">
                                                                    <button type="submit" class="dropdown-item text-danger">
                                                                        <i class="fas fa-trash me-2"></i>Remove Student
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions Modal -->
    <div class="modal fade" id="bulkActionsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Actions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Select an action to perform on selected students:</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" onclick="bulkAction('active')">
                            <i class="fas fa-check me-2"></i>Mark as Active
                        </button>
                        <button class="btn btn-info" onclick="bulkAction('completed')">
                            <i class="fas fa-graduation-cap me-2"></i>Mark as Completed
                        </button>
                        <button class="btn btn-warning" onclick="bulkAction('dropped')">
                            <i class="fas fa-times me-2"></i>Mark as Dropped
                        </button>
                        <button class="btn btn-danger" onclick="bulkAction('remove')">
                            <i class="fas fa-trash me-2"></i>Remove Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive features
        function bulkAction(action) {
            // This would be implemented for bulk operations
            alert('Bulk action: ' + action + ' - This feature can be implemented based on requirements');
        }

        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
