<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: ../auth/login.php');
    exit;
}

// Get teacher's department
$stmt = $pdo->prepare("SELECT department_id FROM teacher_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher_dept = $stmt->fetch()['department_id'];

// Get departments
$stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get academic years
$stmt = $pdo->query("SELECT * FROM academic_years ORDER BY start_date DESC");
$academic_years = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_POST) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $course_code = strtoupper($_POST['course_code']);
    $department_id = $_POST['department_id'];
    $semester = $_POST['semester'];
    $academic_year_id = $_POST['academic_year_id'];
    $year_level = $_POST['year_level'];
    $course_type = $_POST['course_type'];
    $credits = $_POST['credits'];
    $credit_hours = $_POST['credit_hours'];
    $max_students = $_POST['max_students'];
    $prerequisites = $_POST['prerequisites'];
    $teacher_id = $_SESSION['user_id'];
    
    try {
        // Check if course code already exists
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE course_code = ?");
        $stmt->execute([$course_code]);
        
        if ($stmt->fetch()) {
            $error_message = "Course code already exists! Please choose a different one.";
        } else {
            // Insert new course
            $stmt = $pdo->prepare("INSERT INTO courses (title, description, teacher_id, department_id, course_code, semester, academic_year_id, year_level, course_type, credits, credit_hours, max_students, prerequisites) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $teacher_id, $department_id, $course_code, $semester, $academic_year_id, $year_level, $course_type, $credits, $credit_hours, $max_students, $prerequisites]);
            
            $success_message = "Course created successfully!";
            header("refresh:2;url=dashboard.php");
        }
        
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container large">
            <h2>Create New Course</h2>
            
            <?php if (isset($success_message)): ?>
                <div class="success"><?php echo $success_message; ?> Redirecting to dashboard...</div>
            <?php elseif (isset($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Course Title:</label>
                        <input type="text" name="title" required placeholder="e.g., Advanced Database Systems">
                    </div>
                    
                    <div class="form-group">
                        <label>Course Code:</label>
                        <input type="text" name="course_code" required placeholder="e.g., CS301" maxlength="20">
                        <small>Must be unique across all courses</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Course Description:</label>
                    <textarea name="description" rows="4" placeholder="Describe the course objectives, content, and learning outcomes..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Department:</label>
                        <select name="department_id" required>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo $dept['id'] == $teacher_dept ? 'selected' : ''; ?>>
                                    <?php echo $dept['name'] . ' (' . $dept['code'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Academic Year:</label>
                        <select name="academic_year_id" required>
                            <?php foreach ($academic_years as $year): ?>
                                <option value="<?php echo $year['id']; ?>" <?php echo $year['is_current'] ? 'selected' : ''; ?>>
                                    <?php echo $year['year_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Year Level:</label>
                        <select name="year_level" required>
                            <option value="">Select Year</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                       
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Semester:</label>
                        <select name="semester" required>
                            <option value="">Select Semester</option>
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                          
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Course Type:</label>
                        <select name="course_type" required>
                            <option value="Core">Core (Mandatory)</option>
                            <option value="Elective">Elective</option>
                            
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Credits:</label>
                        <input type="number" name="credits" min="1" max="10" value="3" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Credit Hours per Week:</label>
                        <input type="number" name="credit_hours" min="1" max="20" value="3" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Maximum Students:</label>
                        <input type="number" name="max_students" min="1" max="200" value="50" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Prerequisites:</label>
                    <textarea name="prerequisites" rows="2" placeholder="List any prerequisite courses or requirements..."></textarea>
                    <small>Optional: Specify courses students must complete before enrolling</small>
                </div>
                
                <button type="submit">Create Course</button>
            </form>
            
            <p><a href="dashboard.php">← Back to Dashboard</a></p>
        </div>
    </div>
</body>
</html>