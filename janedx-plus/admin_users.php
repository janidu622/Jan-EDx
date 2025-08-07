<?php
require_once 'config.php';
requireAdmin();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['update_user_role'])) {
        $user_id = (int)$_POST['user_id'];
        $new_role = $_POST['role'];
        
        if (in_array($new_role, ['student', 'admin'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            if ($stmt->execute([$new_role, $user_id])) {
                setFlashMessage('success', 'User role updated successfully!');
            } else {
                setFlashMessage('error', 'Failed to update user role.');
            }
        }
    }
}

// Handle delete user
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Check if user has purchases
    $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchases WHERE user_id = ?");
    $check_stmt->execute([$delete_id]);
    $purchase_count = $check_stmt->fetch()['count'];
    
    if ($purchase_count > 0) {
        setFlashMessage('error', 'Cannot delete user. They have purchase history.');
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        if ($stmt->execute([$delete_id])) {
            setFlashMessage('success', 'User deleted successfully!');
        } else {
            setFlashMessage('error', 'Failed to delete user or cannot delete admin user.');
        }
    }
    redirect('admin_users.php');
}

// Get filter parameters
$role_filter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if ($role_filter && in_array($role_filter, ['student', 'admin'])) {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
}

if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

// Get users with purchase statistics
$users_stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(DISTINCT p.id) as total_purchases,
           SUM(p.amount) as total_spent,
           MAX(p.purchase_date) as last_purchase
    FROM users u
    LEFT JOIN purchases p ON u.id = p.user_id
    $where_clause
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users_stmt->execute($params);
$users = $users_stmt->fetchAll();

// Get statistics
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(CASE WHEN role = 'student' THEN 1 END) as students,
        COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins,
        COUNT(*) as total_users
    FROM users
");
$stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?php echo SITE_NAME; ?></title>
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
        }
        .user-avatar {
            width: 45px;
            height: 45px;
            object-fit: cover;
        }
        .user-avatar-placeholder {
            width: 45px;
            height: 45px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #6c757d;
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
                    <a class="nav-link active" href="admin_users.php">
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
                                <h2 class="mb-0">Manage Users</h2>
                                <small class="text-muted">View and manage platform users</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid">
                    <?php showFlashMessage(); ?>

                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stats-card text-center">
                                <h3 class="text-primary mb-2"><?php echo $stats['total_users']; ?></h3>
                                <p class="text-muted mb-0">Total Users</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card text-center">
                                <h3 class="text-success mb-2"><?php echo $stats['students']; ?></h3>
                                <p class="text-muted mb-0">Students</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card text-center">
                                <h3 class="text-warning mb-2"><?php echo $stats['admins']; ?></h3>
                                <p class="text-muted mb-0">Administrators</p>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Search Users</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Search by name or email...">
                                </div>
                                <div class="col-md-3">
                                    <label for="role" class="form-label">Filter by Role</label>
                                    <select class="form-select" id="role" name="role">
                                        <option value="">All Roles</option>
                                        <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Students</option>
                                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <a href="admin_users.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> Clear
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Users List -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($users)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                    <h4>No users found</h4>
                                    <p class="text-muted">No users match your current filters</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Role</th>
                                                <th>Courses Purchased</th>
                                                <th>Total Spent</th>
                                                <th>Last Purchase</th>
                                                <th>Joined</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($user['profile_picture']): ?>
                                                                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                                                     alt="Profile" class="rounded-circle user-avatar me-3">
                                                            <?php else: ?>
                                                                <div class="rounded-circle user-avatar-placeholder me-3">
                                                                    <i class="fas fa-user"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <div class="fw-semibold"><?php echo htmlspecialchars($user['name']); ?></div>
                                                                <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                                <?php if ($user['oauth_provider']): ?>
                                                                    <br><small class="badge bg-info"><?php echo ucfirst($user['oauth_provider']); ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'warning' : 'primary'; ?>">
                                                            <?php echo ucfirst($user['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success"><?php echo $user['total_purchases']; ?></span>
                                                    </td>
                                                    <td>
                                                        <?php echo $user['total_spent'] ? formatPrice($user['total_spent']) : '-'; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $user['last_purchase'] ? date('M j, Y', strtotime($user['last_purchase'])) : 'Never'; ?>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-outline-primary" 
                                                                    onclick="editUserRole(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                                <i class="fas fa-user-edit"></i>
                                                            </button>
                                                            <?php if ($user['role'] !== 'admin' && $user['total_purchases'] == 0): ?>
                                                                <a href="admin_users.php?delete=<?php echo $user['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-danger"
                                                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-outline-secondary" disabled 
                                                                        title="<?php echo $user['role'] === 'admin' ? 'Cannot delete admin' : 'User has purchases'; ?>">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            <?php endif; ?>
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

    <!-- Edit User Role Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="mb-3">
                            <label class="form-label">User</label>
                            <div id="user_info" class="form-control-plaintext"></div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="student">Student</option>
                                <option value="admin">Administrator</option>
                            </select>
                            <div class="form-text">Administrators have full access to the admin panel.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_user_role" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUserRole(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('user_info').innerHTML = `
                <strong>${user.name}</strong><br>
                <small class="text-muted">${user.email}</small>
            `;
            
            const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            editModal.show();
        }

        // Auto-focus first input in modals
        document.addEventListener('shown.bs.modal', function (event) {
            const modal = event.target;
            const firstSelect = modal.querySelector('select');
            if (firstSelect) {
                firstSelect.focus();
            }
        });
    </script>
</body>
</html>