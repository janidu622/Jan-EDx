<?php
require_once 'config.php';
requireLogin();

$course_id = $_GET['id'] ?? 0;

// Get course details
$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name 
    FROM courses c 
    LEFT JOIN categories cat ON c.category_id = cat.id 
    WHERE c.id = ?
");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    redirect('index.php?error=course_not_found');
}

// Check if user already purchased this course
$already_purchased = hasPurchasedCourse($_SESSION['user_id'], $course_id);

// Get course videos
$videos_stmt = $pdo->prepare("SELECT * FROM course_videos WHERE course_id = ? ORDER BY order_number");
$videos_stmt->execute([$course_id]);
$videos = $videos_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .course-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
        }
        .course-thumbnail {
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .price-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            position: sticky;
            top: 20px;
        }
        .price-original {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
        }
        .video-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .video-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.3s ease;
        }
        .video-item:hover {
            background-color: rgba(102, 126, 234, 0.1);
        }
        .video-item:last-child {
            border-bottom: none;
        }
        .video-duration {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home"></i> Browse Courses
                </a>
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Course Header -->
    <section class="course-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php" class="text-white-50">Home</a></li>
                            <li class="breadcrumb-item"><a href="index.php?category=<?php echo $course['category_id']; ?>" class="text-white-50"><?php echo htmlspecialchars($course['category_name']); ?></a></li>
                            <li class="breadcrumb-item active text-white"><?php echo htmlspecialchars($course['title']); ?></li>
                        </ol>
                    </nav>
                    
                    <h1 class="display-5 mb-3"><?php echo htmlspecialchars($course['title']); ?></h1>
                    <p class="lead mb-4"><?php echo htmlspecialchars($course['description']); ?></p>
                    
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <span class="badge bg-light text-dark px-3 py-2">
                            <i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($course['category_name']); ?>
                        </span>
                        <span class="badge bg-light text-dark px-3 py-2">
                            <i class="fas fa-signal"></i> <?php echo htmlspecialchars($course['level']); ?>
                        </span>
                        <span class="badge bg-light text-dark px-3 py-2">
                            <i class="fas fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?>
                        </span>
                        <span class="badge bg-light text-dark px-3 py-2">
                            <i class="fas fa-play-circle"></i> <?php echo count($videos); ?> Videos
                        </span>
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-circle fa-2x me-3"></i>
                        <div>
                            <h6 class="mb-0">Instructor</h6>
                            <p class="mb-0 opacity-75"><?php echo htmlspecialchars($course['instructor_name']); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                         alt="Course thumbnail" class="img-fluid course-thumbnail">
                </div>
            </div>
        </div>
    </section>

    <!-- Course Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Course Videos -->
                    <div class="mb-5">
                        <h3 class="mb-4"><i class="fas fa-play-circle"></i> Course Content</h3>
                        <?php if (empty($videos)): ?>
                            <div class="video-list text-center py-5">
                                <i class="fas fa-video fa-3x text-muted mb-3"></i>
                                <h5>No videos available</h5>
                                <p class="text-muted">Course content will be added soon.</p>
                            </div>
                        <?php else: ?>
                            <div class="video-list">
                                <?php foreach ($videos as $index => $video): ?>
                                    <div class="video-item">
                                        <div class="me-3">
                                            <i class="fas fa-play-circle fa-2x text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($video['title']); ?></h6>
                                            <p class="text-muted mb-0 small"><?php echo htmlspecialchars($video['description']); ?></p>
                                        </div>
                                        <div class="video-duration">
                                            <?php echo htmlspecialchars($video['duration']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Course Description -->
                    <div class="mb-5">
                        <h3 class="mb-4"><i class="fas fa-info-circle"></i> About This Course</h3>
                        <div class="bg-white p-4 rounded-3">
                            <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                            
                            <h5 class="mt-4 mb-3">What you'll learn:</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Master the fundamentals</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Hands-on practical experience</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Industry best practices</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Real-world projects</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="price-card">
                        <?php if ($already_purchased): ?>
                            <div class="text-center mb-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h4 class="text-success">Already Purchased</h4>
                                <p class="text-muted">You have access to this course</p>
                            </div>
                            <a href="watch_course.php?id=<?php echo $course['id']; ?>" 
                               class="btn btn-success btn-lg w-100 mb-3">
                                <i class="fas fa-play"></i> Start Learning
                            </a>
                        <?php else: ?>
                            <div class="text-center mb-4">
                                <div class="price-original"><?php echo formatPrice($course['price']); ?></div>
                                <p class="text-muted">One-time purchase</p>
                            </div>
                            
                            <form action="checkout.php" method="POST">
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                    <i class="fas fa-shopping-cart"></i> Enroll Now
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="course-features">
                            <h6 class="mb-3">This course includes:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-video text-primary me-2"></i>
                                    <?php echo count($videos); ?> video lectures
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-infinity text-primary me-2"></i>
                                    Lifetime access
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-mobile-alt text-primary me-2"></i>
                                    Access on mobile and desktop
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-certificate text-primary me-2"></i>
                                    Certificate of completion
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2025 <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>