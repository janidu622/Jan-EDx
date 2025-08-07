<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../auth/login.php');
    exit();
}

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$course_id) {
    header('Location: dashboard.php');
    exit();
}

// Get course details with department and academic year info
$stmt = $pdo->prepare("
    SELECT c.*, 
           d.name as department_name, 
           ay.year_name as academic_year_name,
           COUNT(ce.id) as enrolled_students
    FROM courses c 
    LEFT JOIN departments d ON c.department_id = d.id 
    LEFT JOIN academic_years ay ON c.academic_year_id = ay.id 
    LEFT JOIN course_enrollments ce ON c.id = ce.course_id AND ce.status = 'active'
    WHERE c.id = ? AND c.teacher_id = ?
    GROUP BY c.id
");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: dashboard.php');
    exit();
}

// Get materials count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM course_materials WHERE course_id = ? AND is_active = 1");
$stmt->execute([$course_id]);
$materials_count = $stmt->fetch()['count'];

// Get assignments count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assignments WHERE course_id = ? AND is_active = 1");
$stmt->execute([$course_id]);
$assignments_count = $stmt->fetch()['count'];

// Get recent submissions count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM assignment_submissions sub 
    JOIN assignments a ON sub.assignment_id = a.id 
    WHERE a.course_id = ? AND sub.submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$stmt->execute([$course_id]);
$recent_submissions = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Details - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .course-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .course-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .course-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .course-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .meta-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
        }
        
        .meta-label {
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        
        .meta-value {
            font-size: 16px;
            font-weight: bold;
        }
        
        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .card-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .card-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .card-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            text-align: center;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .quick-actions {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .quick-actions h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
    </style>
</head>
<body>
    <div class="course-container">
        <!-- Header with Back Button -->
        <div style="margin-bottom: 20px;">
            <a href="dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
        </div>

        <!-- Course Header -->
        <div class="course-header">
            <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
            <div class="course-description"><?php echo htmlspecialchars($course['description']); ?></div>
            
            <div class="course-meta">
                <div class="meta-item">
                    <div class="meta-label">Course Code</div>
                    <div class="meta-value"><?php echo htmlspecialchars($course['course_code']); ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Department</div>
                    <div class="meta-value"><?php echo htmlspecialchars($course['department_name']); ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Academic Year</div>
                    <div class="meta-value"><?php echo htmlspecialchars($course['academic_year_name']); ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Enrolled Students</div>
                    <div class="meta-value"><?php echo $course['enrolled_students']; ?></div>
                </div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="action-cards">
            <!-- Materials Management -->
            <div class="action-card">
                <div class="card-icon">📚</div>
                <div class="card-title">Course Materials</div>
                <div class="card-description">
                    Upload and manage course materials, lecture notes, presentations, and resources for your students.
                </div>
                <div class="card-stats">
                    <div class="stat">
                        <div class="stat-number"><?php echo $materials_count; ?></div>
                        <div class="stat-label">Materials</div>
                    </div>
                </div>
                <a href="course-materials.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">
                    Manage Materials
                </a>
            </div>

            <!-- Assignments Management -->
            <div class="action-card">
                <div class="card-icon">📝</div>
                <div class="card-title">Assignments</div>
                <div class="card-description">
                    Create, manage, and grade assignments. Track student submissions and provide feedback.
                </div>
                <div class="card-stats">
                    <div class="stat">
                        <div class="stat-number"><?php echo $assignments_count; ?></div>
                        <div class="stat-label">Assignments</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?php echo $recent_submissions; ?></div>
                        <div class="stat-label">Recent Submissions</div>
                    </div>
                </div>
                <a href="course-assignments.php?course_id=<?php echo $course_id; ?>" class="btn btn-success">
                    Manage Assignments
                </a>
            </div>

            <!-- Student Management -->
            <div class="action-card">
                <div class="card-icon">👥</div>
                <div class="card-title">Student Management</div>
                <div class="card-description">
                    View enrolled students, track their progress, and manage course enrollment.
                </div>
                <div class="card-stats">
                    <div class="stat">
                        <div class="stat-number"><?php echo $course['enrolled_students']; ?></div>
                        <div class="stat-label">Enrolled</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?php echo $course['max_students']; ?></div>
                        <div class="stat-label">Max Capacity</div>
                    </div>
                </div>
                <a href="manage-students.php?course_id=<?php echo $course_id; ?>" class="btn btn-info">
                    Manage Students
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-buttons">
                <a href="edit-course.php?id=<?php echo $course_id; ?>" class="btn btn-warning">
                    ✏️ Edit Course Details
                </a>
                <a href="course-materials.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">
                    📚 Upload Material
                </a>
                <a href="course-assignments.php?course_id=<?php echo $course_id; ?>" class="btn btn-success">
                    📝 Create Assignment
                </a>
                <a href="manage-students.php?course_id=<?php echo $course_id; ?>" class="btn btn-info">
                    👥 View Students
                </a>
            </div>
        </div>
    </div>
</body>
</html>