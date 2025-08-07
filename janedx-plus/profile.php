<?php
require_once 'config.php';
requireLogin();

// Redirect admin users to admin dashboard
if (isAdmin()) {
    redirect('admin_dashboard.php');
}

$user = getCurrentUser();
$success_message = '';
$error_message = '';

// Handle form submission
if ($_POST) {
    $name = trim($_POST['name'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate name
    if (empty($name)) {
        $errors[] = "Name is required";
    } elseif (strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters long";
    }
    
    // If password change is requested
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        } elseif (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    if (empty($errors)) {
        try {
            // Update name
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $_SESSION['user_id']]);
            
            // Update password if provided
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            }
            
            $success_message = "Profile updated successfully!";
            // Refresh user data
            $user = getCurrentUser();
        } catch (Exception $e) {
            $error_message = "An error occurred while updating your profile. Please try again.";
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Get user's course statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT p.course_id) as total_courses,
        SUM(c.price) as total_spent,
        COUNT(DISTINCT c.category_id) as categories_count
    FROM purchases p
    JOIN courses c ON p.course_id = c.id
    WHERE p.user_id = ?
");
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-top: -50px;
            position: relative;
            z-index: 1;
        }
        .profile-picture-container {
            position: relative;
            display: inline-block;
        }
        .profile-picture {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .profile-picture-placeholder {
            width: 120px;
            height: 120px;
            background: #f8f9fa;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #6c757d;
        }
        .upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
            border-radius: 50%;
        }
        .profile-picture-container:hover .upload-overlay {
            opacity: 1;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 20px;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand text-primary" href="index.php">
                <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home"></i> Browse Courses
                </a>
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link active" href="profile.php">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Profile Header -->
    <section class="profile-header">
        <div class="container text-center">
            <h1 class="display-6 mb-2">My Profile</h1>
            <p class="lead mb-0">Manage your account settings</p>
        </div>
    </section>

    <!-- Profile Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Profile Picture and Stats -->
                <div class="col-lg-4">
                    <div class="profile-card text-center">
                        <div class="profile-picture-container mb-4">
                            <?php if ($user['profile_picture']): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                     alt="Profile" class="rounded-circle profile-picture" id="profileImage">
                            <?php else: ?>
                                <div class="rounded-circle profile-picture-placeholder" id="profilePlaceholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <div class="upload-overlay rounded-circle" onclick="document.getElementById('profilePictureInput').click()">
                                <i class="fas fa-camera text-white fa-2x"></i>
                            </div>
                            <input type="file" id="profilePictureInput" accept="image/*" style="display: none;" onchange="uploadProfilePicture(this)">
                        </div>
                        
                        <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> 
                            Member since <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </small>
                    </div>

                    <!-- Stats Cards -->
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['total_courses'] ?? 0; ?></div>
                        <div>Courses Enrolled</div>
                        <i class="fas fa-book fa-2x mt-2"></i>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-number"><?php echo formatPrice($stats['total_spent'] ?? 0); ?></div>
                        <div>Total Invested</div>
                        <i class="fas fa-dollar-sign fa-2x mt-2"></i>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['categories_count'] ?? 0; ?></div>
                        <div>Categories Explored</div>
                        <i class="fas fa-tags fa-2x mt-2"></i>
                    </div>
                </div>

                <!-- Profile Form -->
                <div class="col-lg-8">
                    <div class="profile-card">
                        <h3 class="mb-4"><i class="fas fa-edit text-primary"></i> Edit Profile</h3>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <!-- Personal Information -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3">Personal Information</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                    <div class="form-text">Email cannot be changed</div>
                                </div>
                            </div>

                            <!-- Password Change -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3">Change Password</h5>
                                    <p class="text-muted small">Leave password fields empty if you don't want to change your password</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard.php" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2025 <?php echo SITE_NAME; ?>. All rights reserved.</p>
            <p class="mb-0">
                <a href="#" class="text-white-50 me-3">Privacy Policy</a>
                <a href="#" class="text-white-50 me-3">Terms of Service</a>
                <a href="#" class="text-white-50">Contact Us</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function uploadProfilePicture(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('profile_picture', input.files[0]);
                
                // Show loading state
                const overlay = document.querySelector('.upload-overlay');
                overlay.innerHTML = '<i class="fas fa-spinner fa-spin text-white fa-2x"></i>';
                
                fetch('upload_profile_picture.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the profile picture
                        const profileImage = document.getElementById('profileImage');
                        const profilePlaceholder = document.getElementById('profilePlaceholder');
                        
                        if (profileImage) {
                            profileImage.src = data.image_url + '?t=' + new Date().getTime();
                        } else if (profilePlaceholder) {
                            // Replace placeholder with actual image
                            profilePlaceholder.outerHTML = `<img src="${data.image_url}?t=${new Date().getTime()}" alt="Profile" class="rounded-circle profile-picture" id="profileImage">`;
                        }
                        
                        // Show success message
                        showAlert('success', 'Profile picture updated successfully!');
                    } else {
                        showAlert('danger', data.message || 'Failed to upload profile picture');
                    }
                })
                .catch(error => {
                    showAlert('danger', 'An error occurred while uploading the image');
                })
                .finally(() => {
                    // Restore camera icon
                    overlay.innerHTML = '<i class="fas fa-camera text-white fa-2x"></i>';
                });
            }
        }
        
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const form = document.querySelector('form');
            form.parentNode.insertBefore(alertDiv, form);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>