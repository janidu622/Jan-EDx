<?php
require_once 'config.php';
requireAdmin();

// Get filter parameters
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$course_filter = $_GET['course_id'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';

// Build query with filters
$where_conditions = ['1=1'];
$params = [];

if ($date_from) {
    $where_conditions[] = "DATE(p.purchase_date) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(p.purchase_date) <= ?";
    $params[] = $date_to;
}

if ($course_filter) {
    $where_conditions[] = "p.course_id = ?";
    $params[] = $course_filter;
}

if ($payment_method) {
    $where_conditions[] = "p.payment_method = ?";
    $params[] = $payment_method;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get purchases with details
$purchases_stmt = $pdo->prepare("
    SELECT p.*, 
           u.name as user_name, 
           u.email as user_email,
           c.title as course_title,
           c.thumbnail as course_thumbnail,
           cat.name as category_name
    FROM purchases p
    JOIN users u ON p.user_id = u.id
    JOIN courses c ON p.course_id = c.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    $where_clause
    ORDER BY p.purchase_date DESC
");
$purchases_stmt->execute($params);
$purchases = $purchases_stmt->fetchAll();

// Get statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_purchases,
        SUM(amount) as total_revenue,
        AVG(amount) as avg_purchase,
        COUNT(DISTINCT user_id) as unique_customers,
        COUNT(DISTINCT course_id) as courses_sold
    FROM purchases p
    $where_clause
");
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch();

// Get monthly revenue data for chart (last 12 months)
$monthly_revenue = $pdo->query("
    SELECT 
        DATE_FORMAT(purchase_date, '%Y-%m') as month,
        SUM(amount) as revenue,
        COUNT(*) as sales
    FROM purchases 
    WHERE purchase_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(purchase_date, '%Y-%m')
    ORDER BY month
")->fetchAll();

// Get courses for filter dropdown
$courses = $pdo->query("SELECT id, title FROM courses ORDER BY title")->fetchAll();

// Get payment methods
$payment_methods = $pdo->query("SELECT DISTINCT payment_method FROM purchases WHERE payment_method IS NOT NULL ORDER BY payment_method")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchases & Sales - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.2);
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
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .course-thumbnail {
            width: 40px;
            height: 30px;
            object-fit: cover;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
                    <a class="nav-link" href="admin_dashboard.php">
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
                    <a class="nav-link active" href="admin_purchases.php">
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
                                <h2 class="mb-0">Purchases & Sales</h2>
                                <small class="text-muted">View and analyze sales data</small>
                            </div>
                            <div>
                                <button class="btn btn-success" onclick="exportData()">
                                    <i class="fas fa-download"></i> Export CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid">
                    <?php showFlashMessage(); ?>

                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-lg-2-4 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-number text-primary"><?php echo $stats['total_purchases']; ?></div>
                                <div class="text-muted">Total Sales</div>
                                <i class="fas fa-shopping-cart fa-2x text-primary mt-2"></i>
                            </div>
                        </div>
                        <div class="col-lg-2-4 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-number text-success"><?php echo formatPrice($stats['total_revenue']); ?></div>
                                <div class="text-muted">Total Revenue</div>
                                <i class="fas fa-dollar-sign fa-2x text-success mt-2"></i>
                            </div>
                        </div>
                        <div class="col-lg-2-4 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-number text-info"><?php echo formatPrice($stats['avg_purchase']); ?></div>
                                <div class="text-muted">Avg. Purchase</div>
                                <i class="fas fa-chart-line fa-2x text-info mt-2"></i>
                            </div>
                        </div>
                        <div class="col-lg-2-4 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-number text-warning"><?php echo $stats['unique_customers']; ?></div>
                                <div class="text-muted">Customers</div>
                                <i class="fas fa-users fa-2x text-warning mt-2"></i>
                            </div>
                        </div>
                        <div class="col-lg-2-4 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-number text-danger"><?php echo $stats['courses_sold']; ?></div>
                                <div class="text-muted">Courses Sold</div>
                                <i class="fas fa-book fa-2x text-danger mt-2"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Chart -->
                    <?php if (!empty($monthly_revenue)): ?>
                    <div class="chart-container">
                        <h5 class="mb-3"><i class="fas fa-chart-area"></i> Monthly Revenue (Last 12 Months)</h5>
                        <canvas id="revenueChart" style="max-height: 300px;"></canvas>
                    </div>
                    <?php endif; ?>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="date_from" class="form-label">From Date</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" 
                                           value="<?php echo htmlspecialchars($date_from); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="date_to" class="form-label">To Date</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" 
                                           value="<?php echo htmlspecialchars($date_to); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="course_id" class="form-label">Course</label>
                                    <select class="form-select" id="course_id" name="course_id">
                                        <option value="">All Courses</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo $course['id']; ?>" 
                                                    <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="payment_method" class="form-label">Payment Method</label>
                                    <select class="form-select" id="payment_method" name="payment_method">
                                        <option value="">All Methods</option>
                                        <?php foreach ($payment_methods as $method): ?>
                                            <option value="<?php echo htmlspecialchars($method['payment_method']); ?>" 
                                                    <?php echo $payment_method === $method['payment_method'] ? 'selected' : ''; ?>>
                                                <?php echo ucfirst(str_replace('_', ' ', $method['payment_method'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter"></i> Apply Filters
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <a href="admin_purchases.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> Clear Filters
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Purchases List -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($purchases)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                                    <h4>No purchases found</h4>
                                    <p class="text-muted">No purchases match your current filters</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="purchasesTable">
                                        <thead>
                                            <tr>
                                                <th>Purchase ID</th>
                                                <th>Customer</th>
                                                <th>Course</th>
                                                <th>Category</th>
                                                <th>Amount</th>
                                                <th>Payment Method</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($purchases as $purchase): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-secondary">#<?php echo $purchase['id']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars($purchase['user_name']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($purchase['user_email']); ?></small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo htmlspecialchars($purchase['course_thumbnail']); ?>" 
                                                                 alt="Course" class="rounded course-thumbnail me-2">
                                                            <div>
                                                                <div class="fw-semibold"><?php echo htmlspecialchars($purchase['course_title']); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($purchase['category_name']); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success"><?php echo formatPrice($purchase['amount']); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $purchase['payment_method'] === 'credit_card' ? 'primary' : 'warning'; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $purchase['payment_method'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div><?php echo date('M j, Y', strtotime($purchase['purchase_date'])); ?></div>
                                                        <small class="text-muted"><?php echo date('g:i A', strtotime($purchase['purchase_date'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-outline-info" 
                                                                    onclick="viewPurchaseDetails(<?php echo htmlspecialchars(json_encode($purchase)); ?>)">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <a href="course_detail.php?id=<?php echo $purchase['course_id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary" target="_blank">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                        </div>
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
            </div>
        </div>
    </div>

    <!-- Purchase Details Modal -->
    <div class="modal fade" id="purchaseDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Purchase Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="purchaseDetailsContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        <?php if (!empty($monthly_revenue)): ?>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php echo "'" . implode("','", array_column($monthly_revenue, 'month')) . "'"; ?>],
                datasets: [{
                    label: 'Revenue',
                    data: [<?php echo implode(',', array_column($monthly_revenue, 'revenue')); ?>],
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Sales Count',
                    data: [<?php echo implode(',', array_column($monthly_revenue, 'sales')); ?>],
                    borderColor: 'rgb(40, 167, 69)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Sales Count'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    }
                }
            }
        });
        <?php endif; ?>

        function viewPurchaseDetails(purchase) {
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Purchase Information</h6>
                        <p><strong>ID:</strong> #${purchase.id}</p>
                        <p><strong>Date:</strong> ${new Date(purchase.purchase_date).toLocaleString()}</p>
                        <p><strong>Amount:</strong> ${parseFloat(purchase.amount).toFixed(2)}</p>
                        <p><strong>Payment Method:</strong> ${purchase.payment_method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p><strong>Name:</strong> ${purchase.user_name}</p>
                        <p><strong>Email:</strong> ${purchase.user_email}</p>
                        <br>
                        <h6>Course Information</h6>
                        <p><strong>Title:</strong> ${purchase.course_title}</p>
                        <p><strong>Category:</strong> ${purchase.category_name}</p>
                    </div>
                </div>
            `;
            
            document.getElementById('purchaseDetailsContent').innerHTML = content;
            const modal = new bootstrap.Modal(document.getElementById('purchaseDetailsModal'));
            modal.show();
        }

        function exportData() {
            // Create CSV content
            const table = document.getElementById('purchasesTable');
            const rows = table.querySelectorAll('tr');
            let csv = [];
            
            // Headers
            const headers = [];
            rows[0].querySelectorAll('th').forEach((th, index) => {
                if (index < 7) { // Exclude Actions column
                    headers.push(th.textContent.trim());
                }
            });
            csv.push(headers.join(','));
            
            // Data rows
            for (let i = 1; i < rows.length; i++) {
                const row = [];
                const cells = rows[i].querySelectorAll('td');
                for (let j = 0; j < 7; j++) { // Exclude Actions column
                    if (cells[j]) {
                        let cellText = cells[j].textContent.trim();
                        // Clean up the text
                        cellText = cellText.replace(/\s+/g, ' ').replace(/"/g, '""');
                        if (cellText.includes(',')) {
                            cellText = `"${cellText}"`;
                        }
                        row.push(cellText);
                    }
                }
                csv.push(row.join(','));
            }
            
            // Download CSV
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `purchases_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Set default date range (last 30 days) if no filters are applied
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('date_from') && !urlParams.has('date_to')) {
                const today = new Date();
                const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
                
                document.getElementById('date_to').value = today.toISOString().split('T')[0];
                document.getElementById('date_from').value = thirtyDaysAgo.toISOString().split('T')[0];
            }
        });
    </script>

    <style>
        /* Custom CSS for 5-column layout */
        .col-lg-2-4 {
            flex: 0 0 auto;
            width: 20%;
        }
        
        @media (max-width: 991.98px) {
            .col-lg-2-4 {
                width: 50%;
            }
        }
        
        @media (max-width: 575.98px) {
            .col-lg-2-4 {
                width: 100%;
            }
        }
    </style>
</body>
</html>