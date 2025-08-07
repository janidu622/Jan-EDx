<?php
require_once 'config.php';
requireLogin();

$course_id = $_GET['id'] ?? 0;
$video_id = $_GET['video'] ?? null;

// Get course details
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    redirect('index.php?error=course_not_found');
}

// Check if user has purchased this course
if (!hasPurchasedCourse($_SESSION['user_id'], $course_id)) {
    redirect('course_detail.php?id=' . $course_id . '&error=not_purchased');
}

// Get course videos
$videos_stmt = $pdo->prepare("SELECT * FROM course_videos WHERE course_id = ? ORDER BY order_number");
$videos_stmt->execute([$course_id]);
$videos = $videos_stmt->fetchAll();

// Select current video
$current_video = null;
if ($video_id) {
    foreach ($videos as $video) {
        if ($video['id'] == $video_id) {
            $current_video = $video;
            break;
        }
    }
}

// If no specific video selected or video not found, use first video
if (!$current_video && !empty($videos)) {
    $current_video = $videos[0];
}
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
        .video-container {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            background: #000;
            border-radius: 10px;
            overflow: hidden;
        }
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .course-sidebar {
            background: #f8f9fa;
            height: calc(100vh - 76px);
            overflow-y: auto;
            border-left: 1px solid #dee2e6;
        }
        .video-list-item {
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .video-list-item:hover {
            background-color: rgba(102, 126, 234, 0.1);
            color: inherit;
            text-decoration: none;
        }
        .video-list-item.active {
            background-color: #667eea;
            color: white;
        }
        .video-list-item.active:hover {
            background-color: #5a67d8;
            color: white;
        }
        .video-duration {
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        .video-list-item.active .video-duration {
            background: rgba(255,255,255,0.2);
        }
        .course-header {
            background: white;
            border-bottom: 1px solid #dee2e6;
            padding: 15px 0;
        }
        .video-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .no-video-placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 60px 20px;
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
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
    <div class="course-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($course['title']); ?></li>
                        </ol>
                    </nav>
                </div>
                <div class="col-auto">
                    <span class="badge bg-success">
                        <i class="fas fa-check-circle"></i> Enrolled
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Main Video Content -->
            <div class="col-lg-8 col-xl-9">
                <div class="p-4">
                    <?php if ($current_video): ?>
                        <!-- Video Player -->
                        <div class="video-container mb-4">
                            <iframe src="<?php echo htmlspecialchars($current_video['video_url']); ?>" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                            </iframe>
                        </div>

                        <!-- Video Info -->
                        <div class="video-info">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h3 class="mb-2"><?php echo htmlspecialchars($current_video['title']); ?></h3>
                                    <div class="d-flex align-items-center text-muted">
                                        <i class="fas fa-clock me-2"></i>
                                        <span class="me-4"><?php echo htmlspecialchars($current_video['duration']); ?></span>
                                        <i class="fas fa-user me-2"></i>
                                        <span><?php echo htmlspecialchars($course['instructor_name']); ?></span>
                                    </div>
                                </div>
                                <div class="video-duration">
                                    Video <?php echo array_search($current_video, $videos) + 1; ?> of <?php echo count($videos); ?>
                                </div>
                            </div>
                            
                            <?php if ($current_video['description']): ?>
                                <div class="mt-3">
                                    <h5>About this lesson</h5>
                                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($current_video['description'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <!-- Navigation Buttons -->
                            <div class="d-flex justify-content-between mt-4">
                                <?php
                                $current_index = array_search($current_video, $videos);
                                $prev_video = $current_index > 0 ? $videos[$current_index - 1] : null;
                                $next_video = $current_index < count($videos) - 1 ? $videos[$current_index + 1] : null;
                                ?>
                                
                                <div>
                                    <?php if ($prev_video): ?>
                                        <a href="?id=<?php echo $course_id; ?>&video=<?php echo $prev_video['id']; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-chevron-left"></i> Previous Lesson
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <?php if ($next_video): ?>
                                        <a href="?id=<?php echo $course_id; ?>&video=<?php echo $next_video['id']; ?>" 
                                           class="btn btn-primary">
                                            Next Lesson <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-success" disabled>
                                            <i class="fas fa-trophy"></i> Course Completed!
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- No Video Available -->
                        <div class="no-video-placeholder">
                            <i class="fas fa-video fa-4x mb-3"></i>
                            <h4>No videos available</h4>
                            <p>This course doesn't have any video content yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Course Sidebar -->
            <div class="col-lg-4 col-xl-3">
                <div class="course-sidebar">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> Course Content
                        </h5>
                        <small class="text-muted"><?php echo count($videos); ?> lessons</small>
                    </div>
                    
                    <?php if (empty($videos)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-video fa-2x mb-3"></i>
                            <p>No videos available</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($videos as $index => $video): ?>
                            <a href="?id=<?php echo $course_id; ?>&video=<?php echo $video['id']; ?>" 
                               class="video-list-item <?php echo ($current_video && $current_video['id'] == $video['id']) ? 'active' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <?php if ($current_video && $current_video['id'] == $video['id']): ?>
                                            <i class="fas fa-play-circle fa-lg"></i>
                                        <?php else: ?>
                                            <i class="fas fa-play-circle fa-lg text-muted"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($video['title']); ?></div>
                                        <?php if ($video['description']): ?>
                                            <div class="small text-muted mt-1">
                                                <?php echo htmlspecialchars(substr($video['description'], 0, 60)) . '...'; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="video-duration">
                                        <?php echo htmlspecialchars($video['duration']); ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) return; // Don't interfere with browser shortcuts
            
            switch(e.key) {
                case 'ArrowLeft':
                    // Previous video
                    const prevBtn = document.querySelector('a[href*="video="]:has(i.fa-chevron-left)');
                    if (prevBtn) {
                        e.preventDefault();
                        window.location.href = prevBtn.href;
                    }
                    break;
                case 'ArrowRight':
                    // Next video
                    const nextBtn = document.querySelector('a[href*="video="]:has(i.fa-chevron-right)');
                    if (nextBtn) {
                        e.preventDefault();
                        window.location.href = nextBtn.href;
                    }
                    break;
                case 'Escape':
                    // Go back to dashboard
                    window.location.href = 'dashboard.php';
                    break;
            }
        });

        // Auto-scroll to current video in sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const activeVideo = document.querySelector('.video-list-item.active');
            if (activeVideo) {
                activeVideo.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
</body>
</html>