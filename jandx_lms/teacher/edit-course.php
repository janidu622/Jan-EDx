
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

// Fetch course details
$stmt = $pdo->prepare("
    SELECT c.*, d.name as department_name, ay.year_name 
    FROM courses c 
    LEFT JOIN departments d ON c.department_id = d.id 
    LEFT JOIN academic_years ay ON c.academic_year_id = ay.id 
    WHERE c.id = ? AND c.teacher_id = ?
");
$stmt->execute([$course_id, $teacher_id]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: dashboard.php');
    exit();
}

// Fetch departments for dropdown
$dept_stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $dept_stmt->fetchAll();

// Fetch academic years for dropdown
$year_stmt = $pdo->query("SELECT id, year_name FROM academic_years ORDER BY year_name");
$academic_years = $year_stmt->fetchAll();

$success_message = '';
$error_message = '';

// Handle form submission
if ($_POST) {
    try {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $course_code = trim($_POST['course_code']);
        $department_id = (int)$_POST['department_id'];
        $academic_year_id = (int)$_POST['academic_year_id'];
        $semester = $_POST['semester'];
        $year_level = $_POST['year_level'];
        $course_type = $_POST['course_type'];
        $credits = (int)$_POST['credits'];
        $credit_hours = (int)$_POST['credit_hours'];
        $max_students = (int)$_POST['max_students'];
        $prerequisites = trim($_POST['prerequisites']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validate required fields
        if (empty($title) || empty($course_code)) {
            throw new Exception('Title and Course Code are required.');
        }

        // Check if course code is unique (excluding current course)
        $check_stmt = $pdo->prepare("SELECT id FROM courses WHERE course_code = ? AND id != ?");
        $check_stmt->execute([$course_code, $course_id]);
        if ($check_stmt->fetch()) {
            throw new Exception('Course code already exists.');
        }

        // Update course
        $update_stmt = $pdo->prepare("
            UPDATE courses SET 
                title = ?, description = ?, course_code = ?, department_id = ?, 
                academic_year_id = ?, semester = ?, year_level = ?, course_type = ?, 
                credits = ?, credit_hours = ?, max_students = ?, prerequisites = ?, 
                is_active = ?, updated_at = NOW()
            WHERE id = ? AND teacher_id = ?
        ");
        
        $update_stmt->execute([
            $title, $description, $course_code, $department_id, $academic_year_id,
            $semester, $year_level, $course_type, $credits, $credit_hours,
            $max_students, $prerequisites, $is_active, $course_id, $teacher_id
        ]);

        $success_message = 'Course updated successfully!';
        
        // Refresh course data
        $stmt->execute([$course_id, $teacher_id]);
        $course = $stmt->fetch();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course - LMS</title>
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
                    <h2><i class="fas fa-edit me-2"></i>Edit Course</h2>
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

                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Course Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?php echo htmlspecialchars($course['title']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="course_code" class="form-label">Course Code *</label>
                                        <input type="text" class="form-control" id="course_code" name="course_code" 
                                               value="<?php echo htmlspecialchars($course['course_code']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($course['description']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="department_id" class="form-label">Department</label>
                                        <select class="form-select" id="department_id" name="department_id">
                                            <option value="">Select Department</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept['id']; ?>" 
                                                        <?php echo $dept['id'] == $course['department_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($dept['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="academic_year_id" class="form-label">Academic Year</label>
                                        <select class="form-select" id="academic_year_id" name="academic_year_id">
                                            <option value="">Select Academic Year</option>
                                            <?php foreach ($academic_years as $year): ?>
                                                <option value="<?php echo $year['id']; ?>"
                                                        <?php echo $year['id'] == $course['academic_year_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($year['year_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="semester" class="form-label">Semester</label>
                                        <select class="form-select" id="semester" name="semester">
                                            <option value="1st Semester" <?php echo $course['semester'] == '1st Semester' ? 'selected' : ''; ?>>1st Semester</option>
                                            <option value="2nd Semester" <?php echo $course['semester'] == '2nd Semester' ? 'selected' : ''; ?>>2nd Semester</option>
                                            <option value="Both Semesters" <?php echo $course['semester'] == 'Both Semesters' ? 'selected' : ''; ?>>Both Semesters</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="year_level" class="form-label">Year Level</label>
                                        <select class="form-select" id="year_level" name="year_level">
                                            <option value="">Select Year Level</option>
                                            <option value="1st Year" <?php echo $course['year_level'] == '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                                            <option value="2nd Year" <?php echo $course['year_level'] == '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                                            <option value="3rd Year" <?php echo $course['year_level'] == '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                                            <option value="4th Year" <?php echo $course['year_level'] == '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                                            <option value="Masters" <?php echo $course['year_level'] == 'Masters' ? 'selected' : ''; ?>>Masters</option>
                                            <option value="PhD" <?php echo $course['year_level'] == 'PhD' ? 'selected' : ''; ?>>PhD</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="course_type" class="form-label">Course Type</label>
                                        <select class="form-select" id="course_type" name="course_type">
                                            <option value="Core" <?php echo $course['course_type'] == 'Core' ? 'selected' : ''; ?>>Core</option>
                                            <option value="Elective" <?php echo $course['course_type'] == 'Elective' ? 'selected' : ''; ?>>Elective</option>
                                            <option value="Optional" <?php echo $course['course_type'] == 'Optional' ? 'selected' : ''; ?>>Optional</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="credits" class="form-label">Credits</label>
                                        <input type="number" class="form-control" id="credits" name="credits" 
                                               value="<?php echo $course['credits']; ?>" min="1" max="10">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="credit_hours" class="form-label">Credit Hours</label>
                                        <input type="number" class="form-control" id="credit_hours" name="credit_hours" 
                                               value="<?php echo $course['credit_hours']; ?>" min="1" max="20">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="max_students" class="form-label">Max Students</label>
                                        <input type="number" class="form-control" id="max_students" name="max_students" 
                                               value="<?php echo $course['max_students']; ?>" min="1" max="200">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="prerequisites" class="form-label">Prerequisites</label>
                                <textarea class="form-control" id="prerequisites" name="prerequisites" rows="2" 
                                          placeholder="List any prerequisite courses or requirements"><?php echo htmlspecialchars($course['prerequisites']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo $course['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active Course
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="view-course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Course
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>