<?php
require_once 'config.php';
requireAdmin();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['create_category'])) {
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        
        if (empty($name)) {
            setFlashMessage('error', 'Category name is required.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                setFlashMessage('success', 'Category created successfully!');
            } else {
                setFlashMessage('error', 'Failed to create category.');
            }
        }
    } elseif (isset($_POST['update_category'])) {
        $id = (int)$_POST['category_id'];
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        
        if (empty($name)) {
            setFlashMessage('error', 'Category name is required.');
        } else {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $id])) {
                setFlashMessage('success', 'Category updated successfully!');
            } else {
                setFlashMessage('error', 'Failed to update category.');
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Check if category has courses
    $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM courses WHERE category_id = ?");
    $check_stmt->execute([$delete_id]);
    $course_count = $check_stmt->fetch()['count'];
    
    if ($course_count > 0) {
        setFlashMessage('error', 'Cannot delete category. It has courses assigned to it.');
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$delete_id])) {
            setFlashMessage('success', 'Category deleted successfully!');
        } else {
            setFlashMessage('error', 'Failed to delete category.');
        }
    }
    redirect('admin_categories.php');
}

// Get all categories with course counts
$categories = $pdo->query("
    SELECT c.*, COUNT(co.id) as course_count
    FROM categories c
    LEFT JOIN courses co ON c.id = co.category_id
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - <?php echo SITE_NAME; ?></title>
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
                    <a class="nav-link active" href="admin_categories.php">
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
                                <h2 class="mb-0">Manage Categories</h2>
                                <small class="text-muted">Organize your courses by categories</small>
                            </div>
                            <div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="fas fa-plus"></i> Add Category
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid">
                    <?php showFlashMessage(); ?>

                    <!-- Categories List -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($categories)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-tags fa-4x text-muted mb-3"></i>
                                    <h4>No categories created yet</h4>
                                    <p class="text-muted">Create your first category to organize courses</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                        <i class="fas fa-plus"></i> Create Category
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Category Name</th>
                                                <th>Description</th>
                                                <th>Courses</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $category): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                                 style="width: 40px; height: 40px;">
                                                                <i class="fas fa-tag"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-semibold"><?php echo htmlspecialchars($category['name']); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="text-muted">
                                                            <?php echo $category['description'] ? htmlspecialchars(substr($category['description'], 0, 80)) . '...' : 'No description'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo $category['course_count']; ?> courses</span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-outline-primary" 
                                                                    onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($category['course_count'] == 0): ?>
                                                                <a href="admin_categories.php?delete=<?php echo $category['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-danger"
                                                                   onclick="return confirm('Are you sure you want to delete this category?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete category with courses">
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

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Brief description of this category..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_category" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_category_id" name="category_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" 
                                      placeholder="Brief description of this category..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_category" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(category) {
            document.getElementById('edit_category_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_description').value = category.description || '';
            
            const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            editModal.show();
        }

        // Auto-focus first input in modals
        document.addEventListener('shown.bs.modal', function (event) {
            const modal = event.target;
            const firstInput = modal.querySelector('input[type="text"], textarea');
            if (firstInput) {
                firstInput.focus();
            }
        });

        // Clear form when add modal is closed
        document.getElementById('addCategoryModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });
    </script>
</body>
</html>