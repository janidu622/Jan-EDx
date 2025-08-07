<?php
require_once 'config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$course_id = $_GET['id'] ?? 0;

// Handle form submissions
if ($_POST) {
    if (isset($_POST['create_course'])) {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $category_id = (int)$_POST['category_id'];
        $price = (float)$_POST['price'];
        $instructor_name = sanitizeInput($_POST['instructor_name']);
        $duration = sanitizeInput($_POST['duration']);
        $level = sanitizeInput($_POST['level']);
        $thumbnail = sanitizeInput($_POST['thumbnail']);
        
        if (empty($title) || empty($description) || empty($instructor_name)) {
            setFlashMessage('error', 'Please fill in all required fields.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO courses (title, description, category_id, price, thumbnail, instructor_name, duration, level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $description, $category_id, $price, $thumbnail, $instructor_name, $duration, $level])) {
                $new_course_id = $pdo->lastInsertId();
                setFlashMessage('success', 'Course created successfully!');
                redirect("admin_courses.php?action=edit&id=$new_course_id");
            } else {
                setFlashMessage('error', 'Failed to create course.');
            }
        }
    } elseif (isset($_POST['update_course'])) {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $category_id = (int)$_POST['category_id'];
        $price = (float)$_POST['price'];
        $instructor_name = sanitizeInput($_POST['instructor_name']);
        $duration = sanitizeInput($_POST['duration']);
        $level = sanitizeInput($_POST['level']);
        $thumbnail = sanitizeInput($_POST['thumbnail']);
        
        $stmt = $pdo->prepare("UPDATE courses SET title=?, description=?, category_id=?, price=?, thumbnail=?, instructor_name=?, duration=?, level=? WHERE id=?");
        if ($stmt->execute([$title, $description, $category_id, $price, $thumbnail, $instructor_name, $duration, $level, $course_id])) {
            setFlashMessage('success', 'Course updated successfully!');
        } else {
            setFlashMessage('error', 'Failed to update course.');
        }
    } elseif (isset($_POST['add_video'])) {
        $video_title = sanitizeInput($_POST['video_title']);
        $video_url = getYouTubeEmbedUrl(sanitizeInput($_POST['video_url']));
        $video_description = sanitizeInput($_POST['video_description']);
        $duration = sanitizeInput($_POST['video_duration']);
        $order_number = (int)$_POST['order_number'];
        
        $stmt = $pdo->prepare("INSERT INTO course_videos (course_id, title, video_url, description, duration, order_number) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$course_id, $video_title, $video_url, $video_description, $duration, $order_number])) {
            setFlashMessage('success', 'Video added successfully!');
        } else {
            setFlashMessage('error', 'Failed to add video.');
        }
    }
}

// Handle delete actions
if (isset($_GET['delete_course'])) {
    $delete_id = (int)$_GET['delete_course'];
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    if ($stmt->execute([$delete_id])) {
        setFlashMessage('success', 'Course deleted successfully!');
    } else {
        setFlashMessage('error', 'Failed to delete course.');
    }
    redirect('admin_courses.php');
}

if (isset($_GET['delete_video'])) {
    $video_id = (int)$_GET['delete_video'];
    $stmt = $pdo->prepare("DELETE FROM course_videos WHERE id = ?");
    if ($stmt->execute([$video_id])) {
        setFlashMessage('success', 'Video deleted successfully!');
    } else {
        setFlashMessage('error', 'Failed to delete video.');
    }
    redirect("admin_courses.php?action=edit&id=$course_id");
}

// Get data based on action
$course = null;
$videos = [];
$categories = [];

if ($action === 'edit' && $course_id) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        redirect('admin_courses.php');
    }
    
    $videos_stmt = $pdo->prepare("SELECT * FROM course_videos WHERE course_id = ? ORDER BY order_number");
    $videos_stmt->execute([$course_id]);
    $videos = $videos_stmt->fetchAll();
}

// Get categories for dropdown
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

// Get all courses for list view
if ($action === 'list') {
    $courses_stmt = $pdo->query("
        SELECT c.*, cat.name as category_name, COUNT(p.id) as purchases_count
        FROM courses c 
        LEFT JOIN categories cat ON c.category_id = cat.id 
        LEFT JOIN purchases p ON c.id = p.course_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $courses = $courses_stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - <?php echo SITE_NAME; ?></title>
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
        .video-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
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
                    <a class="nav-link active" href="admin_courses.php">
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
                                <h2 class="mb-0">
                                    <?php if ($action === 'create'): ?>
                                        Create New Course
                                    <?php elseif ($action === 'edit'): ?>
                                        Edit Course
                                    <?php else: ?>
                                        Manage Courses
                                    <?php endif; ?>
                                </h2>
                            </div>
                            <div>
                                <?php if ($action === 'list'): ?>
                                    <a href="admin_courses.php?action=create" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Create Course
                                    </a>
                                <?php else: ?>
                                    <a href="admin_courses.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to List
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid">
                    <?php showFlashMessage(); ?>

                    <?php if ($action === 'list'): ?>
                        <!-- Courses List -->
                        <div class="card">
                            <div class="card-body">
                                <?php if (empty($courses)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-book fa-4x text-muted mb-3"></i>
                                        <h4>No courses created yet</h4>
                                        <p class="text-muted">Create your first course to get started</p>
                                        <a href="admin_courses.php?action=create" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Create Course
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Course</th>
                                                    <th>Category</th>
                                                    <th>Price</th>
                                                    <th>Level</th>
                                                    <th>Sales</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($courses as $course): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                                                                     alt="Course" class="rounded me-3" style="width: 50px; height: 35px; object-fit: cover;">
                                                                <div>
                                                                    <div class="fw-semibold"><?php echo htmlspecialchars($course['title']); ?></div>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($course['instructor_name']); ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($course['category_name']); ?></span></td>
                                                        <td><?php echo formatPrice($course['price']); ?></td>
                                                        <td><span class="badge bg-info"><?php echo htmlspecialchars($course['level']); ?></span></td>
                                                        <td><?php echo $course['purchases_count']; ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($course['created_at'])); ?></td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <a href="admin_courses.php?action=edit&id=<?php echo $course['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="course_detail.php?id=<?php echo $course['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-info" target="_blank">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <a href="admin_courses.php?delete_course=<?php echo $course['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-danger"
                                                                   onclick="return confirm('Are you sure you want to delete this course?')">
                                                                    <i class="fas fa-trash"></i>
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

                    <?php elseif ($action === 'create' || $action === 'edit'): ?>
                        <!-- Create/Edit Course Form -->
                        <form method="POST">
                            <div class="form-section">
                                <h4 class="mb-4"><i class="fas fa-info-circle"></i> Course Information</h4>
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="title" class="form-label">Course Title *</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo $course ? htmlspecialchars($course['title']) : ''; ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description *</label>
                                            <textarea class="form-control" id="description" name="description" rows="4" required><?php echo $course ? htmlspecialchars($course['description']) : ''; ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="thumbnail" class="form-label">Thumbnail URL</label>
                                            <input type="url" class="form-control" id="thumbnail" name="thumbnail" 
                                                   value="<?php echo $course ? htmlspecialchars($course['thumbnail']) : 'https://img.youtube.com/vi/Your_VIDEO_ID/hqdefault.jpg'; ?>">
                                            <div class="form-text">Enter a URL for the course thumbnail image</div>
                                        </div>
                                        
                                        <div class="thumbnail-preview mt-2">
                                            <img id="thumbnailPreview" src="<?php echo $course ? htmlspecialchars($course['thumbnail']) : 'https://img.youtube.com/vi/VIDEO_ID/hqdefault.jpg'; ?>" 
                                                 class="img-fluid rounded" style="max-height: 150px;">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="category_id" class="form-label">Category</label>
                                            <select class="form-select" id="category_id" name="category_id">
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" 
                                                            <?php echo ($course && $course['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="instructor_name" class="form-label">Instructor Name *</label>
                                            <input type="text" class="form-control" id="instructor_name" name="instructor_name" 
                                                   value="<?php echo $course ? htmlspecialchars($course['instructor_name']) : ''; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="price" class="form-label">Price ($)</label>
                                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0"
                                                   value="<?php echo $course ? $course['price'] : '29.99'; ?>">
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label for="duration" class="form-label">Duration</label>
                                                    <input type="text" class="form-control" id="duration" name="duration" 
                                                           placeholder="e.g., 10 hours"
                                                           value="<?php echo $course ? htmlspecialchars($course['duration']) : ''; ?>">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label for="level" class="form-label">Level</label>
                                                    <select class="form-select" id="level" name="level">
                                                        <option value="Beginner" <?php echo ($course && $course['level'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                                        <option value="Intermediate" <?php echo ($course && $course['level'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                                        <option value="Advanced" <?php echo ($course && $course['level'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <?php if ($action === 'create'): ?>
                                        <button type="submit" name="create_course" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Create Course
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="update_course" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Course
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>

                        <?php if ($action === 'edit' && $course): ?>
                            <!-- Course Videos Section -->
                            <div class="form-section">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4><i class="fas fa-video"></i> Course Videos</h4>
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addVideoModal">
                                        <i class="fas fa-plus"></i> Add Video
                                    </button>
                                </div>
                                
                                <?php if (empty($videos)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-video fa-3x text-muted mb-3"></i>
                                        <h5>No videos added yet</h5>
                                        <p class="text-muted">Add your first video to get started</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($videos as $video): ?>
                                        <div class="video-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-8">
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <i class="fas fa-play-circle fa-2x text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($video['title']); ?></h6>
                                                            <p class="text-muted mb-1 small"><?php echo htmlspecialchars($video['description']); ?></p>
                                                            <small class="text-muted">Duration: <?php echo htmlspecialchars($video['duration']); ?> | Order: <?php echo $video['order_number']; ?></small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <a href="<?php echo htmlspecialchars($video['video_url']); ?>" target="_blank" class="btn btn-sm btn-outline-info me-2">
                                                        <i class="fas fa-external-link-alt"></i> Preview
                                                    </a>
                                                    <a href="admin_courses.php?action=edit&id=<?php echo $course_id; ?>&delete_video=<?php echo $video['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Are you sure you want to delete this video?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Add Video Modal -->
                            <div class="modal fade" id="addVideoModal" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Add New Video</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="video_title" class="form-label">Video Title *</label>
                                                    <input type="text" class="form-control" id="video_title" name="video_title" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="video_url" class="form-label">YouTube Video URL *</label>
                                                    <input type="url" class="form-control" id="video_url" name="video_url" 
                                                           placeholder="https://www.youtube.com/watch?v=..." required>
                                                    <div class="form-text">Paste the YouTube video URL here. It will be automatically converted to embed format.</div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="video_description" class="form-label">Video Description</label>
                                                    <textarea class="form-control" id="video_description" name="video_description" rows="3"></textarea>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="video_duration" class="form-label">Duration</label>
                                                            <input type="text" class="form-control" id="video_duration" name="video_duration" 
                                                                   placeholder="e.g., 15:30">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="order_number" class="form-label">Order Number</label>
                                                            <input type="number" class="form-control" id="order_number" name="order_number" 
                                                                   value="<?php echo count($videos) + 1; ?>" min="1">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="add_video" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> Add Video
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Thumbnail preview
        document.getElementById('thumbnail')?.addEventListener('input', function() {
            const url = this.value;
            const preview = document.getElementById('thumbnailPreview');
            if (url) {
                preview.src = url;
                preview.onerror = function() {
                    this.src = 'https://img.youtube.com/vi/VIDEO_ID/hqdefault.jpg';
                };
            }
        });

        // Auto-focus first input in modals
        document.addEventListener('shown.bs.modal', function (event) {
            const modal = event.target;
            const firstInput = modal.querySelector('input[type="text"], textarea');
            if (firstInput) {
                firstInput.focus();
            }
        });
    </script>
</body>
</html>