<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: ../auth/login.php');
    exit;
}

// Get teacher's profile with department info
$stmt = $pdo->prepare("SELECT tp.*, d.name as department_name, d.code as department_code 
                       FROM teacher_profiles tp 
                       JOIN departments d ON tp.department_id = d.id 
                       WHERE tp.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher_profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current academic year
$stmt = $pdo->query("SELECT * FROM academic_years WHERE is_current = 1 LIMIT 1");
$current_academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

// Get teacher's courses
$stmt = $pdo->prepare("SELECT c.*, d.name as department_name, d.code as department_code,
                              ay.year_name, COUNT(ce.student_id) as enrolled_students 
                       FROM courses c 
                       LEFT JOIN departments d ON c.department_id = d.id
                       LEFT JOIN academic_years ay ON c.academic_year_id = ay.id
                       LEFT JOIN course_enrollments ce ON c.id = ce.course_id 
                       WHERE c.teacher_id = ? 
                       GROUP BY c.id 
                       ORDER BY c.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$my_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_courses FROM courses WHERE teacher_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_courses = $stmt->fetch()['total_courses'];

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT ce.student_id) as total_students 
                       FROM course_enrollments ce 
                       JOIN courses c ON ce.course_id = c.id 
                       WHERE c.teacher_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_students = $stmt->fetch()['total_students'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JanEDx Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <h1>JanEDx Dashboard-Teacher</h1>
            
            <div class="user-info">
                <div class="profile-header">
                    <h3><?php echo $teacher_profile['designation'] ?? 'Faculty Member'; ?> 
                        <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></h3>
                    <p><strong>Employee ID:</strong> <?php echo $teacher_profile['employee_id'] ?? 'Pending'; ?></p>
                    <p><strong>Department:</strong> <?php echo $teacher_profile['department_name'] . ' (' . $teacher_profile['department_code'] . ')'; ?></p>
                    <p><strong>Qualification:</strong> <?php echo $teacher_profile['qualification'] ?? 'Not specified'; ?></p>
                    <p><strong>Experience:</strong> <?php echo $teacher_profile['experience_years'] ?? 0; ?> years</p>
                </div>
                <a href="../auth/logout.php" class="logout-btn">Logout</a>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Courses</h4>
                    <p class="stat-number"><?php echo $total_courses; ?></p>
                </div>
                <div class="stat-card">
                    <h4>Total Students</h4>
                    <p class="stat-number"><?php echo $total_students; ?></p>
                </div>
                <div class="stat-card">
                    <h4>Current Academic Year</h4>
                    <p class="stat-text"><?php echo $current_academic_year['year_name'] ?? 'Not Set'; ?></p>
                </div>
            </div>
            
            <div class="course-actions">
                <h3>Course Management</h3>
                <a href="create-course.php" class="btn btn-primary">Create New Course</a>
                <a href="my-profile.php" class="btn btn-secondary">Update Profile</a>
            </div>
            
            <div class="my-courses">
                <h3>My Courses (<?php echo count($my_courses); ?>)</h3>
                
                <?php if (empty($my_courses)): ?>
                    <div class="empty-state">
                        <p>You haven't created any courses yet.</p>
                        <a href="create-course.php" class="btn btn-primary">Create Your First Course</a>
                    </div>
                <?php else: ?>
                    <div class="courses-grid">
                        <?php foreach ($my_courses as $course): ?>
                            <div class="course-card teacher-course">
                                <div class="course-header">
                                    <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <span class="course-code"><?php echo $course['course_code']; ?></span>
                                </div>
                                
                                <div class="course-details">
                                    <p><strong>Year Level:</strong> <?php echo $course['year_level']; ?></p>
                                    <p><strong>Semester:</strong> <?php echo $course['semester']; ?></p>
                                    <p><strong>Academic Year:</strong> <?php echo $course['year_name']; ?></p>
                                    <p><strong>Type:</strong> <?php echo $course['course_type']; ?></p>
                                    <p><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                                    <p><strong>Students:</strong> <?php echo $course['enrolled_students']; ?>/<?php echo $course['max_students']; ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="status <?php echo $course['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </p>
                                </div>
                                
                                <div class="course-actions">
                                    <a href="view-course.php?id=<?php echo $course['id']; ?>" class="btn btn-small">View Details</a>
                                    <a href="edit-course.php?id=<?php echo $course['id']; ?>" class="btn btn-small btn-secondary">Edit</a>
                                    <a href="manage-students.php?id=<?php echo $course['id']; ?>" class="btn btn-small btn-info">Students</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>