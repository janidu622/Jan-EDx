<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle enrollment of course 
if (isset($_POST['enroll_course'])) {
    $course_id = $_POST['course_id'];
    $semester = $_POST['semester'];
    
    try {
        // Get current academic year
        $stmt = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
        $current_academic_year = $stmt->fetch();
        
        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT id FROM course_enrollments WHERE course_id = ? AND student_id = ?");
        $stmt->execute([$course_id, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $error_message = "You are already enrolled in this course.";
        } else {
            // Check course capacity
            $stmt = $pdo->prepare("SELECT c.max_students, COUNT(ce.student_id) as current_enrollments 
                                   FROM courses c 
                                   LEFT JOIN course_enrollments ce ON c.id = ce.course_id 
                                   WHERE c.id = ? AND c.is_active = 1");
            $stmt->execute([$course_id]);
            $course_info = $stmt->fetch();
            
            if ($course_info['current_enrollments'] >= $course_info['max_students']) {
                $error_message = "This course is full. Cannot enroll.";
            } else {
                // Enroll student
                $stmt = $pdo->prepare("INSERT INTO course_enrollments (course_id, student_id, semester, academic_year_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$course_id, $_SESSION['user_id'], $semester, $current_academic_year['id']]);
                $success_message = "Successfully enrolled in the course!";
            }
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get student profile with department info
$stmt = $pdo->prepare("SELECT sp.*, d.name as department_name, d.code as department_code 
                       FROM student_profiles sp 
                       JOIN departments d ON sp.department_id = d.id 
                       WHERE sp.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Get enrolled courses
$stmt = $pdo->prepare("SELECT c.*, u.first_name, u.last_name, d.name as department_name,
                              ce.enrolled_at, ce.status, ce.semester, ce.grade,
                              ay.year_name
                       FROM courses c 
                       JOIN users u ON c.teacher_id = u.id 
                       JOIN departments d ON c.department_id = d.id
                       JOIN course_enrollments ce ON c.id = ce.course_id 
                       LEFT JOIN academic_years ay ON ce.academic_year_id = ay.id
                       WHERE ce.student_id = ? 
                       ORDER BY ce.enrolled_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$my_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available courses (matching student's year and not enrolled)
$stmt = $pdo->prepare("SELECT c.*, u.first_name, u.last_name, d.name as department_name,
                              COUNT(ce.student_id) as enrolled_count, ay.year_name
                       FROM courses c 
                       JOIN users u ON c.teacher_id = u.id 
                       JOIN departments d ON c.department_id = d.id
                       LEFT JOIN course_enrollments ce ON c.id = ce.course_id 
                       LEFT JOIN academic_years ay ON c.academic_year_id = ay.id
                       WHERE c.is_active = 1 
                       AND (c.year_level = ? OR c.year_level = 'All Years')
                       AND c.id NOT IN (
                           SELECT course_id FROM course_enrollments WHERE student_id = ?
                       )
                       GROUP BY c.id 
                       ORDER BY c.created_at DESC");
$stmt->execute([$student_profile['year_of_study'], $_SESSION['user_id']]);
$available_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_credits = array_sum(array_column($my_courses, 'credits'));
$completed_courses = count(array_filter($my_courses, function($course) {
    return $course['status'] === 'completed';
}));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JanEDx Student Dashboard </title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <h1>JanEDx Student Dashboard</h1>
            
            <div class="user-info">
                <div class="profile-header">
                    <h3><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></h3>
                    <p><strong>Student ID:</strong> <?php echo $student_profile['student_reg_number']; ?></p>
                    <p><strong>Department:</strong> <?php echo $student_profile['department_name'] . ' (' . $student_profile['department_code'] . ')'; ?></p>
                    <p><strong>Diploma Program:</strong> <?php echo $student_profile['degree_program']; ?></p>
                    <p><strong>Year of Study:</strong> <?php echo $student_profile['year_of_study']; ?></p>
                    <p><strong>Current Semester:</strong> <?php echo $student_profile['current_semester']; ?></p>
                    <p><strong>CGPA:</strong> <?php echo number_format($student_profile['current_cgpa'], 2); ?></p>
                </div>
                <a href="../auth/logout.php" class="logout-btn">Logout</a>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="success"><?php echo $success_message; ?></div>
            <?php elseif (isset($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Enrolled Courses</h4>
                    <p class="stat-number"><?php echo count($my_courses); ?></p>
                </div>
                <div class="stat-card">
                    <h4>Total Credits</h4>
                    <p class="stat-number"><?php echo $total_credits; ?></p>
                </div>
                <div class="stat-card">
                    <h4>Completed Courses</h4>
                    <p class="stat-number"><?php echo $completed_courses; ?></p>
                </div>
            </div>
            
            <div class="my-courses">
                <h3>My Enrolled Courses (<?php echo count($my_courses); ?>)</h3>
                
                <?php if (empty($my_courses)): ?>
                    <div class="empty-state">
                        <p>You haven't enrolled in any courses yet.</p>
                        <p>Browse available courses please </p>
                    </div>
                <?php else: ?>
                    <div class="courses-grid">
                        <?php foreach ($my_courses as $course): ?>
                            <div class="course-card enrolled">
                                <div class="course-header">
                                    <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <span class="course-code"><?php echo $course['course_code']; ?></span>
                                </div>
                                
                                <div class="course-details">
                                    <p><strong>Instructor:</strong> <?php echo $course['first_name'] . ' ' . $course['last_name']; ?></p>
                                    <p><strong>Department:</strong> <?php echo $course['department_name']; ?></p>
                                    <p><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                                    <p><strong>Semester:</strong> <?php echo $course['semester']; ?></p>
                                    <p><strong>Academic Year:</strong> <?php echo $course['year_name']; ?></p>
                                    <p><strong>Type:</strong> <?php echo $course['course_type']; ?></p>
                                    <p><strong>Enrolled:</strong> <?php echo date('M j, Y', strtotime($course['enrolled_at'])); ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="status <?php echo $course['status']; ?>">
                                            <?php echo ucfirst($course['status']); ?>
                                        </span>
                                    </p>
                                    <?php if ($course['grade']): ?>
                                        <p><strong>Grade:</strong> <span class="grade"><?php echo $course['grade']; ?></span></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="course-actions">
                                    <a href="course-materials.php?course_id=<?php echo $course['id']; ?>" class="btn">Materials</a>
                                    <a href="course-assignments.php?course_id=<?php echo $course['id']; ?>" class="btn">Assignments</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="available-courses">
                <h3>Available Courses for <?php echo $student_profile['year_of_study']; ?></h3>
                
                <?php if (empty($available_courses)): ?>
                    <div class="empty-state">
                        <p>No courses available for enrollment at the moment.</p>
                        <p>Check back later or contact your academic advisor.</p>
                    </div>
                <?php else: ?>
                    <div class="courses-grid">
                        <?php foreach ($available_courses as $course): ?>
                            <div class="course-card available">
                                <div class="course-header">
                                    <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <span class="course-code"><?php echo $course['course_code']; ?></span>
                                </div>
                                
                                <div class="course-details">
                                    <p><strong>Instructor:</strong> <?php echo $course['first_name'] . ' ' . $course['last_name']; ?></p>
                                    <p><strong>Department:</strong> <?php echo $course['department_name']; ?></p>
                                    <p><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                                    <p><strong>Credit Hours:</strong> <?php echo $course['credit_hours']; ?> hours/week</p>
                                    <p><strong>Semester:</strong> <?php echo $course['semester']; ?></p>
                                    <p><strong>Academic Year:</strong> <?php echo $course['year_name']; ?></p>
                                    <p><strong>Type:</strong> <?php echo $course['course_type']; ?></p>
                                    <p><strong>Capacity:</strong> <?php echo $course['enrolled_count']; ?>/<?php echo $course['max_students']; ?></p>
                                    
                                    <?php if ($course['description']): ?>
                                        <p><strong>Description:</strong> <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($course['prerequisites']): ?>
                                        <p><strong>Prerequisites:</strong> <?php echo htmlspecialchars($course['prerequisites']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($course['enrolled_count'] < $course['max_students']): ?>
                                    <form method="POST" class="enrollment-form">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <div class="form-group">
                                            <label>Select Semester:</label>
                                            <select name="semester" required>
                                                <?php if ($course['semester'] == 'Both Semesters'): ?>
                                                    <option value="1st Semester">1st Semester</option>
                                                    <option value="2nd Semester">2nd Semester</option>
                                                <?php else: ?>
                                                    <option value="<?php echo $course['semester']; ?>"><?php echo $course['semester']; ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <button type="submit" name="enroll_course" class="btn btn-primary btn-small">Enroll Now</button>
                                    </form>
                                <?php else: ?>
                                    <p class="course-full">Course Full</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>