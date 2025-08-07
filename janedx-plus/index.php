<?php
require_once 'config.php';

// Get categories
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT c.*, cat.name as category_name FROM courses c 
          LEFT JOIN categories cat ON c.category_id = cat.id 
          WHERE 1=1";
$params = [];

if ($category_filter) {
    $query .= " AND c.category_id = ?";
    $params[] = $category_filter;
}

if ($search) {
    $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Learn Skills, Advance Career</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
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
            height: 200px;
            object-fit: cover;
        }
        .price-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 30px 0;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .stats-section {
            background-color: #fff;
            padding: 60px 0;
        }
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand text-primary" href="index.php">
                <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="../index.php">
                        <i class="fas fa-home"></i> Home
                    </a>
                    <a class="nav-link" href="login.php">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Learn Skills, Advance Your Career</h1>
            <p class="lead mb-4">Choose from thousands of online courses from top instructors</p>
            <?php if (!isLoggedIn()): ?>
                <a href="login.php" class="btn btn-light btn-lg">
                    <i class="fas fa-play"></i> Start Learning Today
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($courses); ?></div>
                        <div>Courses Available</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($categories); ?></div>
                        <div>Categories</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">1000+</div>
                        <div>Students</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div>Support</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="filter-section">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <form method="GET" class="d-flex gap-3">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search courses..." value="<?php echo htmlspecialchars($search); ?>">
                        <select class="form-select" name="category" style="max-width: 200px;">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-4 text-end">
                    <p class="mb-0 text-muted">Showing <?php echo count($courses); ?> courses</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Courses Section -->
    <section class="py-5">
        <div class="container">
            <?php if (empty($courses)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4>No courses found</h4>
                    <p class="text-muted">Try adjusting your search criteria</p>
                    <a href="index.php" class="btn btn-primary">View All Courses</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($courses as $course): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card course-card h-100">
                                <div class="position-relative">
                                    <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                                         class="card-img-top course-thumbnail" alt="Course thumbnail">
                                    <div class="price-badge">
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
                                        
                                        <?php if (isLoggedIn()): ?>
                                            <?php if (hasPurchasedCourse($_SESSION['user_id'], $course['id'])): ?>
                                                <a href="watch_course.php?id=<?php echo $course['id']; ?>" 
                                                   class="btn btn-success w-100">
                                                    <i class="fas fa-play"></i> Watch Course
                                                </a>
                                            <?php else: ?>
                                                <a href="course_detail.php?id=<?php echo $course['id']; ?>" 
                                                   class="btn btn-primary w-100">
                                                    <i class="fas fa-info-circle"></i> View Details
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-sign-in-alt"></i> Sign In to Enroll
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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