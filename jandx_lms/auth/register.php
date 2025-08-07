<?php
require_once '../config/database.php';

// Get departments for dropdown
$stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current academic year
$stmt = $pdo->query("SELECT * FROM academic_years WHERE is_current = 1 LIMIT 1");
$current_academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_POST) {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error_message = "Email already exists! Please use a different email.";
        } else {
            // Generate student ID for students
            $student_id = null;
            if ($role == 'student') {
                $year = date('Y');
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND YEAR(created_at) = $year");
                $count = $stmt->fetch()['count'] + 1;
                $student_id = $year . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
            
            // Insert into users table
            $stmt = $pdo->prepare("INSERT INTO users (email, password, role, first_name, last_name, phone, student_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$email, $password, $role, $first_name, $last_name, $phone, $student_id]);
            
            $user_id = $pdo->lastInsertId();
            
            // Insert role-specific data
            if ($role == 'teacher') {
                $department_id = $_POST['department_id'];
                $designation = $_POST['designation'];
                $qualification = $_POST['qualification'];
                $experience_years = $_POST['experience_years'];
                $employee_id = 'EMP' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                
                $stmt2 = $pdo->prepare("INSERT INTO teacher_profiles (user_id, employee_id, department_id, designation, qualification, experience_years) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt2->execute([$user_id, $employee_id, $department_id, $designation, $qualification, $experience_years]);
            } else {
                $department_id = $_POST['department_id'];
                $year_of_study = $_POST['year_of_study'];
                $current_semester = $_POST['current_semester'];
                $degree_program = $_POST['degree_program'];
                $guardian_name = $_POST['guardian_name'];
                $guardian_contact = $_POST['guardian_contact'];
                $admission_year = $_POST['admission_year'];
                
                $stmt2 = $pdo->prepare("INSERT INTO student_profiles (user_id, student_reg_number, department_id, year_of_study, current_semester, admission_year, degree_program, guardian_name, guardian_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt2->execute([$user_id, $student_id, $department_id, $year_of_study, $current_semester, $admission_year, $degree_program, $guardian_name, $guardian_contact]);
            }
            
            $success_message = "Registration successful! " . 
                ($role == 'student' ? "Your Student ID is: <strong>$student_id</strong>" : "Your Employee ID will be provided after approval.") . 
                " You can now login.";
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
    <title>janEDx</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Join JanEDx</h2>
            
            <?php if (isset($success_message)): ?>
                <div class="success"><?php echo $success_message; ?></div>
                <a href="login.php" class="btn btn-primary">Go to Login</a>
            <?php elseif (isset($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!isset($success_message)): ?>
            <form method="POST" id="registrationForm">
                <!-- User Type Toggle -->
                <div class="user-type-toggle">
                    <label>
                        <input type="radio" name="role" value="student" checked onclick="toggleUserType('student')">
                        I'm a Student
                    </label>
                    <label>
                        <input type="radio" name="role" value="teacher" onclick="toggleUserType('teacher')">
                        I'm a Lecturer
                    </label>
                </div>
                
                <!-- Common Fields -->
                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" name="last_name" required>
                </div>
                
                <div class="form-group">
                    <label> Email:</label>
                    <input type="email" name="email" required placeholder="yourname@university.edu">
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label>Phone Number:</label>
                    <input type="tel" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label>Department:</label>
                    <select name="department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>">
                                <?php echo $dept['name'] . ' (' . $dept['code'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Teacher-specific fields -->
                <div id="teacher-fields" style="display: none;">
                    <div class="form-group">
                        <label>Designation:</label>
                        <select name="designation">
                            <option value="Lecturer">Lecturer</option>
                            <option value="Assistant">Assistant</option>
                            <option value="Senior Lecturer">Senior Lecturer</option>
                            <option value="Department Head">Department Head</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Highest Qualification:</label>
                        <input type="text" name="qualification" placeholder="e.g., PhD in Computer Science">
                    </div>
                    
                    <div class="form-group">
                        <label>Years of Experience:</label>
                        <input type="number" name="experience_years" min="0" max="50" value="0">
                    </div>
                </div>
                
                <!-- Student-specific fields -->
                <div id="student-fields">
                    <div class="form-group">
                        <label>Year of Study:</label>
                        <select name="year_of_study" required>
                            <option value="">Select Year</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                           
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Current Semester:</label>
                        <select name="current_semester" required>
                           
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Diploma Program:</label>
                        <input type="text" name="degree_program" placeholder="e.g., HND in Computer Science" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Admission Year:</label>
                        <input type="number" name="admission_year" min="2020" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Guardian Name:</label>
                        <input type="text" name="guardian_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Guardian Contact:</label>
                        <input type="tel" name="guardian_contact" required>
                    </div>
                </div>
                
                <button type="submit">Register</button>
            </form>
            <?php endif; ?>
            
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
    
    <script>
        function toggleUserType(type) {
            const teacherFields = document.getElementById('teacher-fields');
            const studentFields = document.getElementById('student-fields');
            
            if (type === 'teacher') {
                teacherFields.style.display = 'block';
                studentFields.style.display = 'none';
                
                // Make teacher fields required
                document.querySelectorAll('#teacher-fields select, #teacher-fields input').forEach(field => {
                    if (field.name !== 'experience_years') field.required = true;
                });
                
                // Remove required from student fields
                document.querySelectorAll('#student-fields select, #student-fields input').forEach(field => {
                    field.required = false;
                });
            } else {
                teacherFields.style.display = 'none';
                studentFields.style.display = 'block';
                
                // Make student fields required
                document.querySelectorAll('#student-fields select, #student-fields input').forEach(field => {
                    field.required = true;
                });
                
                // Remove required from teacher fields
                document.querySelectorAll('#teacher-fields select, #teacher-fields input').forEach(field => {
                    field.required = false;
                });
            }
        }
        
        // Initialize
        toggleUserType('student');
    </script>
</body>
</html>