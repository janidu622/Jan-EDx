<?php
require_once 'config.php';
requireAdmin();

// Get statistics
$stats = [];

// Total courses
$stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
$stats['courses'] = $stmt->fetch()['count'];

// Total students
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stats['students'] = $stmt->fetch()['count'];

// Total purchases
$stmt = $pdo->query("SELECT COUNT(*) as count FROM purchases");
$stats['purchases'] = $stmt->fetch()['count'];

// Total revenue
$stmt = $pdo->query("SELECT SUM(amount) as total FROM purchases");
$stats['revenue'] = $stmt->fetch()['total'] ?? 0;

// Recent courses
$recent_courses = $pdo->query("
    SELECT c.*, cat.name as category_name,
           COUNT(p.id) as purchases_count
    FROM courses c 
    LEFT JOIN categories cat ON c.category_id = cat.id 
    LEFT JOIN purchases p ON c.id = p.course_id
    GROUP BY c.id
    ORDER BY c.created_at DESC 
    LIMIT 5
")->fetchAll();

// Recent purchases
$recent_purchases = $pdo->query("
    SELECT p.*, u.name as user_name, c.title as course_title
    FROM purchases p
    JOIN users u ON p.user_id = u.id
    JOIN courses c ON p.course_id = c.id
    ORDER BY p.purchase_date DESC
    LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .page-header {
            background: white;
            padding: 20px 0;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="sidebar p-3">
                <h4 class="text-white mb-4">
                    <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?>
                </h4>
                
                <nav class="nav flex-column">
                    <a class="nav-link active" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="admin_courses.php">
                        <i class="fas fa-book me-2"></i> Manage Courses
                    </a>
                    <a class="nav-link" href="admin_categories.php">
                        <i class="fas fa-tags me-2"></i> Categories
                    </a>
                    <a class="nav-link" href="admin_users.php">
                        <i class="fas fa-users me-2"></i> Users
                    </a>
                    <a class="nav-link" href="admin_purchases.php">
                        <i class="fas fa-shopping-cart me-2"></i> Purchases
                    </a>
                    <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-globe me-2"></i> View Site
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="main-content">
                <!-- Header -->
                <div class="page-header">
                    <div class="container-fluid">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="mb-0">Dashboard</h2>
                                <small class="text-muted">Welcome back, Admin!</small>
                            </div>
                            <div>
                                <a href="admin_courses.php?action=create" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Course
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid">
                    <?php showFlashMessage(); ?>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card text-center">
                                <div class="stat-number text-primary"><?php echo $stats['courses']; ?></div>
                                <div class="text-muted">Total Courses</div>
                                <i class="fas fa-book fa-2x text-primary mt-2"></i>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card text-center">
                                <div class="stat-number text-success"><?php echo $stats['students']; ?></div>
                                <div class="text-muted">Students</div>
                                <i class="fas fa-users fa-2x text-success mt-2"></i>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card text-center">
                                <div class="stat-number text-info"><?php echo $stats['purchases']; ?></div>
                                <div class="text-muted">Total Sales</div>
                                <i class="fas fa-shopping-cart fa-2x text-info mt-2"></i>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card text-center">
                                <div class="stat-number text-warning"><?php echo formatPrice($stats['revenue']); ?></div>
                                <div class="text-muted">Revenue</div>
                                <i class="fas fa-dollar-sign fa-2x text-warning mt-2"></i>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Recent Courses -->
                        <div class="col-lg-8 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-book me-2"></i>Recent Courses</h5>
                                    <a href="admin_courses.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_courses)): ?>
                                        <p class="text-muted text-center py-3">No courses created yet.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Course</th>
                                                        <th>Category</th>
                                                        <th>Price</th>
                                                        <th>Sales</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_courses as $course): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                                                                         alt="Course" class="rounded me-2" style="width: 40px; height: 30px; object-fit: cover;">
                                                                    <div>
                                                                        <div class="fw-semibold"><?php echo htmlspecialchars($course['title']); ?></div>
                                                                        <small class="text-muted"><?php echo htmlspecialchars($course['instructor_name']); ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($course['category_name']); ?></span></td>
                                                            <td><?php echo formatPrice($course['price']); ?></td>
                                                            <td><?php echo $course['purchases_count']; ?></td>
                                                            <td>
                                                                <a href="admin_courses.php?action=edit&id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Purchases -->
                        <div class="col-lg-4 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Recent Sales</h5>
                                    <a href="admin_purchases.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_purchases)): ?>
                                        <p class="text-muted text-center py-3">No purchases yet.</p>
                                    <?php else: ?>
                                        <?php foreach ($recent_purchases as $purchase): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($purchase['user_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($purchase['course_title'], 0, 30)) . '...'; ?></small>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-bold text-success"><?php echo formatPrice($purchase['amount']); ?></div>
                                                    <small class="text-muted"><?php echo date('M j', strtotime($purchase['purchase_date'])); ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>