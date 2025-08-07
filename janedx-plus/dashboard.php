<?php
require_once 'config.php';
requireLogin();

// Redirect admin users to admin dashboard
if (isAdmin()) {
    redirect('admin_dashboard.php');
}

// Get user's purchased courses
$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name, p.purchase_date
    FROM purchases p
    JOIN courses c ON p.course_id = c.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE p.user_id = ?
    ORDER BY p.purchase_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$purchased_courses = $stmt->fetchAll();

// Get recommended courses (courses user hasn't purchased)
$purchased_ids = array_column($purchased_courses, 'id');
$not_in_clause = empty($purchased_ids) ? '' : 'AND c.id NOT IN (' . implode(',', array_fill(0, count($purchased_ids), '?')) . ')';

$recommended_stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name 
    FROM courses c 
    LEFT JOIN categories cat ON c.category_id = cat.id 
    WHERE 1=1 $not_in_clause
    ORDER BY c.created_at DESC 
    LIMIT 6
");
$recommended_stmt->execute($purchased_ids);
$recommended_courses = $recommended_stmt->fetchAll();

$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
        }
        .course-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        .course-thumbnail {
            height: 150px;
            object-fit: cover;
        }
        .progress-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-3px);
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .profile-picture-link {
            text-decoration: none;
            transition: transform 0.3s ease;
        }
        .profile-picture-link:hover {
            transform: scale(1.05);
        }
        .profile-picture-link:hover .profile-picture {
            box-shadow: 0 0 0 3px rgba(255,255,255,0.5);
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
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <section class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-6 mb-2">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                    <p class="lead mb-0">Continue your learning journey</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex align-items-center justify-content-end">
                        <a href="profile.php" class="profile-picture-link">
                            <?php if ($user['profile_picture']): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                     alt="Profile" class="rounded-circle profile-picture me-3" 
                                     style="width: 60px; height: 60px; object-fit: cover; transition: box-shadow 0.3s ease;">
                            <?php else: ?>
                                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-3 profile-picture" 
                                     style="width: 60px; height: 60px; font-size: 1.5rem; transition: box-shadow 0.3s ease;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number text-primary"><?php echo count($purchased_courses); ?></div>
                        <div class="text-muted">Courses Enrolled</div>
                        <i class="fas fa-book fa-2x text-primary mt-2"></i>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number text-success">
                            <?php
                            $total_videos = 0;
                            foreach ($purchased_courses as $course) {
                                $video_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM course_videos WHERE course_id = ?");
                                $video_count_stmt->execute([$course['id']]);
                                $total_videos += $video_count_stmt->fetch()['count'];
                            }
                            echo $total_videos;
                            ?>
                        </div>
                        <div class="text-muted">Total Lessons</div>
                        <i class="fas fa-play-circle fa-2x text-success mt-2"></i>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number text-info">
                            <?php
                            $total_spent = 0;
                            foreach ($purchased_courses as $course) {
                                $total_spent += $course['price'];
                            }
                            echo formatPrice($total_spent);
                            ?>
                        </div>
                        <div class="text-muted">Total Invested</div>
                        <i class="fas fa-dollar-sign fa-2x text-info mt-2"></i>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number text-warning">
                            <?php
                            $categories = [];
                            foreach ($purchased_courses as $course) {
                                if (!in_array($course['category_name'], $categories)) {
                                    $categories[] = $course['category_name'];
                                }
                            }
                            echo count($categories);
                            ?>
                        </div>
                        <div class="text-muted">Categories</div>
                        <i class="fas fa-tags fa-2x text-warning mt-2"></i>
                    </div>
                </div>
            </div>

            <!-- My Courses Section -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3><i class="fas fa-book-open"></i> My Courses</h3>
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-plus"></i> Browse More Courses
                        </a>
                    </div>
                    
                    <?php if (empty($purchased_courses)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-graduation-cap fa-4x text-muted mb-3"></i>
                            <h4>No courses enrolled yet</h4>
                            <p class="text-muted">Start your learning journey by enrolling in a course</p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Browse Courses
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($purchased_courses as $course): ?>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card course-card h-100">
                                        <div class="position-relative">
                                            <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                                                 class="card-img-top course-thumbnail" alt="Course thumbnail">
                                            <div class="progress-badge">
                                                <i class="fas fa-check-circle"></i> Enrolled
                                            </div>
                                        </div>
                                        <div class="card-body d-flex flex-column">
                                            <div class="mb-2">
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($course['category_name']); ?>
                                                </span>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($course['level']); ?>
                                                </span>
                                            </div>
                                            <h5 class="card-title">
                                                <?php echo htmlspecialchars($course['title']); ?>
                                            </h5>
                                            <p class="card-text text-muted flex-grow-1">
                                                <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?>
                                            </p>
                                            <div class="mt-auto">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user"></i> 
                                                        <?php echo htmlspecialchars($course['instructor_name']); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar"></i> 
                                                        <?php echo date('M j, Y', strtotime($course['purchase_date'])); ?>
                                                    </small>
                                                </div>
                                                <a href="watch_course.php?id=<?php echo $course['id']; ?>" 
                                                   class="btn btn-success w-100">
                                                    <i class="fas fa-play"></i> Continue Learning
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recommended Courses Section -->
            <?php if (!empty($recommended_courses)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3><i class="fas fa-star"></i> Recommended for You</h3>
                            <a href="index.php" class="btn btn-outline-primary">
                                View All Courses
                            </a>
                        </div>
                        
                        <div class="row">
                            <?php foreach ($recommended_courses as $course): ?>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card course-card h-100">
                                        <div class="position-relative">
                                            <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                                                 class="card-img-top course-thumbnail" alt="Course thumbnail">
                                            <div class="progress-badge">
                                                <?php echo formatPrice($course['price']); ?>
                                            </div>
                                        </div>
                                        <div class="card-body d-flex flex-column">
                                            <div class="mb-2">
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($course['category_name']); ?>
                                                </span>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($course['level']); ?>
                                                </span>
                                            </div>
                                            <h5 class="card-title">
                                                <?php echo htmlspecialchars($course['title']); ?>
                                            </h5>
                                            <p class="card-text text-muted flex-grow-1">
                                                <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?>
                                            </p>
                                            <div class="mt-auto">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user"></i> 
                                                        <?php echo htmlspecialchars($course['instructor_name']); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock"></i> 
                                                        <?php echo htmlspecialchars($course['duration']); ?>
                                                    </small>
                                                </div>
                                                <a href="course_detail.php?id=<?php echo $course['id']; ?>" 
                                                   class="btn btn-primary w-100">
                                                    <i class="fas fa-info-circle"></i> View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
</body>
</html>