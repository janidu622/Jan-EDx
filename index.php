<?php
require_once 'janedx-plus/config.php';

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
    <title>JanEDx - Modern Learning Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

        /* Hero Slider Styles */
        .hero-slider {
            position: relative;
            height: 100vh;
            overflow: hidden;
        }
        
        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            padding: 100px 0;
        }
        
        .hero-slide.active {
            opacity: 1;
        }
        
        .hero-slide-1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .hero-slide-2 {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .hero-slide-3 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .hero-slide-4 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .hero-slide-5 {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .hero-controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
        }
        
        .hero-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .hero-dot.active {
            background: white;
        }
        
        .hero-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            transition: background 0.3s ease;
            z-index: 10;
        }
        
        .hero-nav:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .hero-nav.prev {
            left: 30px;
        }
        
        .hero-nav.next {
            right: 30px;
        }

        /* Animated tagline */
        .animated-tagline {
            margin-left: 10px;
            font-size: 0.9rem;
            color: #666;
            opacity: 0;
            animation: fadeInOut 4s infinite;
        }

        @keyframes fadeInOut {
            0%, 100% { opacity: 0; }
            25%, 75% { opacity: 1; }
        }

        /* Fix full-width sections */
        .explore-section, .contact-section, .footer {
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            padding-left: calc(50vw - 50%);
            padding-right: calc(50vw - 50%);
        }

        .explore-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .contact-section {
            background-color: #f8f9fa;
            padding: 80px 0;
        }

        .footer {
            background-color: #2c3e50;
            color: white;
            padding: 60px 0 20px;
        }

        .footer a {
            color: #bdc3c7;
            text-decoration: none;
        }

        .footer a:hover {
            color: white;
        }

        .map-container {
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
        }

        .contact-form {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        #about {
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            padding-left: calc(50vw - 50%);
            padding-right: calc(50vw - 50%);
            background-color: #f8f9fa;
        }
        
        

</style>

</head>
<body data-theme="light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <i class="fas fa-graduation-cap me-2"></i>JanEDx
                <span class="animated-tagline">Empowering Sri Lankan Education with Heart</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto me-4">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#courses">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center gap-3">
                    <button class="theme-toggle" onclick="toggleTheme()">
                        <i class="fas fa-moon" id="theme-icon"></i>
                    </button>
                    
                    <button class="lang-toggle" onclick="toggleLanguage()">
                        <span id="lang-text">සිං</span>
                    </button>
                    
                    
                    <button class="bts1" onclick="window.location.href='janedx-plus/login.php'"> <span>Join Us</span>
                        </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Slider Section -->
    <section id="home" class="hero-slider">
        <!-- Slide 1: Transform Your Learning Journey -->
        <div class="hero-slide hero-slide-1 active">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 hero-content">
                        <h1 class="hero-title display-2 fw-bold">Transform Your Learning Journey</h1>
                        <p class="hero-description fs-5 mb-4">Discover world-class courses, connect with expert instructors, and unlock your potential with JanEDx's comprehensive learning platform.</p>
                        <div class="d-flex gap-3">
                            <button class="btn btn-light btn-lg px-4 py-3" onclick="window.location.href='#courses'">
                                <i class="fas fa-rocket me-2"></i>Start Learning
                            </button>
                            <button class="btn btn-outline-light btn-lg px-4 py-3" onclick="window.location.href='#about'">
                                <i class="fas fa-play me-2"></i>Watch Demo
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-4 text-center">
                        <i class="fas fa-graduation-cap" style="font-size: 12rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 2: JanEDx+ Premium Platform -->
        <div class="hero-slide hero-slide-2">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 hero-content">
                        <h1 class="hero-title display-2 fw-bold">JanEDx+ Premium</h1>
                        <p class="hero-description fs-5 mb-4">Experience our premium e-learning platform with high-quality video courses, interactive content, and expert instruction across Engineering, Management, IT, and English departments.</p>
                        <div class="mb-4">
                            <span class="badge bg-light text-dark me-2 fs-6 px-3 py-2">
                                <i class="fas fa-video me-1"></i>HD Video Lessons
                            </span>
                            <span class="badge bg-light text-dark me-2 fs-6 px-3 py-2">
                                <i class="fas fa-certificate me-1"></i>Certified Courses
                            </span>
                            <span class="badge bg-light text-dark fs-6 px-3 py-2">
                                <i class="fas fa-users me-1"></i>Expert Instructors
                            </span>
                        </div>
                        <button class="btn btn-light btn-lg px-4 py-3" onclick="window.location.href='janedx-plus/login.php'">
                            <i class="fas fa-crown me-2"></i>Explore Premium
                        </button>
                    </div>
                    <div class="col-lg-4 text-center">
                        <i class="fas fa-play-circle" style="font-size: 12rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 3: Free LMS for SLIATE -->
        <div class="hero-slide hero-slide-3">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 hero-content">
                        <h1 class="hero-title display-2 fw-bold">JanEDx Free LMS(Developing)</h1>
                        <p class="hero-description fs-5 mb-4">Designed specifically for SLIATE students and teachers. Access free learning resources, course materials, and collaborative tools to enhance your educational experience.</p>
                        <div class="mb-4">
                            <span class="badge bg-light text-dark me-2 fs-6 px-3 py-2">
                                <i class="fas fa-book me-1"></i>Free Resources
                            </span>
                            <span class="badge bg-light text-dark me-2 fs-6 px-3 py-2">
                                <i class="fas fa-chalkboard-teacher me-1"></i>For Teachers
                            </span>
                            <span class="badge bg-light text-dark fs-6 px-3 py-2">
                                <i class="fas fa-university me-1"></i>SLIATE Focused
                            </span>
                        </div>
                        <button class="btn btn-light btn-lg px-4 py-3" onclick="window.location.href='jandx_lms/auth/login.php'">
                            <i class="fas fa-door-open me-2"></i>Access Free LMS
                        </button>
                    </div>
                    <div class="col-lg-4 text-center">
                        <i class="fas fa-university" style="font-size: 12rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 4: Smart Features & Security -->
        <div class="hero-slide hero-slide-4">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 hero-content">
                        <h1 class="hero-title display-2 fw-bold">Advanced Smart Features</h1>
                        <p class="hero-description fs-5 mb-4">Experience cutting-edge technology with our signature fraud detection system, AI-powered chat assistant, and secure document verification for enhanced learning security.</p>
                        <div class="mb-4">
                            <span class="badge bg-light text-dark me-2 fs-6 px-3 py-2">
                                <i class="fas fa-shield-alt me-1"></i>Fraud Detection
                            </span>
                            <span class="badge bg-light text-dark me-2 fs-6 px-3 py-2">
                                <i class="fas fa-robot me-1"></i>AI Assistant
                            </span>
                            <span class="badge bg-light text-dark fs-6 px-3 py-2">
                                <i class="fas fa-signature me-1"></i>Signature Verification
                            </span>
                        </div>
                        <button class="btn btn-light btn-lg px-4 py-3" onclick="window.location.href='#about'">
                            <i class="fas fa-cog me-2"></i>Discover Features
                        </button>
                    </div>
                    <div class="col-lg-4 text-center">
                        <i class="fas fa-brain" style="font-size: 12rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 5: Interactive Learning -->
        <div class="hero-slide hero-slide-5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 hero-content">
                        <h1 class="hero-title display-2 fw-bold">Interactive Learning Hub</h1>
                        <p class="hero-description fs-5 mb-4">Engage with our AI-powered quiz system using Ollama+Phi, enjoy interactive browser games, and stay connected with SMTP messaging for a complete learning ecosystem.</p>
                        <div class="mb-4">
                            <span class="badge bg-light text-dark me-2 fs-6 px-3 py-2">
                                <i class="fas fa-brain me-1"></i>AI Quizzes
                            </span>
                            <span class="badge bg-light text-dark me-2 fs-6 px-3 py-2">
                                <i class="fas fa-gamepad me-1"></i>Fun Games
                            </span>
                            <span class="badge bg-light text-dark fs-6 px-3 py-2">
                                <i class="fas fa-envelope me-1"></i>Smart Messaging
                            </span>
                        </div>
                        <div class="d-flex gap-3">
                            <button class="btn btn-light btn-lg px-4 py-3" onclick="window.location.href='quiz-maker/quiz.html'">
                                <i class="fas fa-clipboard-list me-2"></i>Try Quizzes
                            </button>
                            <button class="btn btn-outline-light btn-lg px-4 py-3" onclick="window.location.href='games/index.html'">
                                <i class="fas fa-gamepad me-2"></i>Play Games
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-4 text-center">
                        <i class="fas fa-puzzle-piece" style="font-size: 12rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Controls -->
        <button class="hero-nav prev" onclick="previousSlide()">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="hero-nav next" onclick="nextSlide()">
            <i class="fas fa-chevron-right"></i>
        </button>

        <!-- Dots Navigation -->
        <div class="hero-controls">
            <span class="hero-dot active" onclick="currentSlide(1)"></span>
            <span class="hero-dot" onclick="currentSlide(2)"></span>
            <span class="hero-dot" onclick="currentSlide(3)"></span>
            <span class="hero-dot" onclick="currentSlide(4)"></span>
            <span class="hero-dot" onclick="currentSlide(5)"></span>
        </div>
    </section>

    <!-- Courses Section -->
    <section id="courses" class="courses-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">JanEDx+ </h2>
                <p class="lead text-muted">Explore our comprehensive range of Premium courses designed only for your success</p>
            </div>

            <!-- Secondary Search Bar -->
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
                                            <a href="janedx-plus/login.php" class="btn btn-outline-primary w-100">
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

    <!-- Explore More Section -->
    <section class="explore-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="display-4 fw-bold mb-4">Ready for Something Fun?</h2>
                    <p class="lead mb-5">Take a break from studying and enjoy our interactive learning games and AI-powered quizzes!</p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <button class="btn btn-light btn-lg px-4 py-3" onclick="window.location.href='quiz-maker/quiz.html'">
                            <i class="fas fa-clipboard-list me-2"></i>Explore AI Quizzes
                        </button>
                        <button class="btn btn-outline-light btn-lg px-4 py-3" onclick="window.location.href='games/index.html'">
                            <i class="fas fa-gamepad me-2"></i>Play Interactive Games 
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-4 fw-bold mb-4">About JanEDx</h2>
                    <p class="lead">We're revolutionizing education through innovative technology and comprehensive learning experiences.</p>
                    <p>Our platform offers courses across multiple departments including Engineering, Management, IT, and English, designed by industry experts and delivered through cutting-edge technology.</p>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Expert-designed curriculum</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Interactive learning experience</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>24/7 student support</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Premium content from top universities</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Mobile-friendly learning platform</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>AI-powered fraud detection system</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Smart chat assistant with Ollama+Phi</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Interactive browser games</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Secure messaging with SMTP</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-graduation-cap" style="font-size: 8rem; color: var(--primary-color); opacity: 0.8;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>
     <section class="explore-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="display-4 fw-bold mb-4">JanEdx AI</h2>
                    <p class="lead mb-5">Intelligent signature verification system designed to identity validation. Using advanced image processing and machine learning algorithms.</p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <button class="btn btn-light btn-lg px-4 py-3" onclick="window.location.href='signature_detector.html'">
                            <i class="fas fa-signature me-2"></i>Signature Detection
                        </button>
                        
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Get In Touch</h2>
                <p class="lead text-muted">Have questions? We'd love to hear from you.</p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-6 mb-4">
                    <div class="contact-form">
                        <h4 class="mb-4">Send us a message</h4>
                        <form id="contact-form" action="send.php" method="POST">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control" id="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" id="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" name="subject" class="form-control" id="subject" required>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea name="message" class="form-control" id="message" rows="5" required></textarea>
                                </div>
                                    <button type="submit" class="btn btn-primary w-100">Send Message</button>
                        </form>

                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63339.75100239449!2d80.150229768915!3d6.057179683028704!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae17390a3138f25%3A0x8c8b08c30c9ac3f2!2sSLIATE%20-%20Advanced%20Technological%20Institute%20-%20Labuduwa!5e0!3m2!1sen!2slk!4v1697442423695!5m2!1sen!2slk"
                            width="100%" 
                            height="100%" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="fas fa-graduation-cap me-2"></i>JanEDx</h5>
                    <p class="mb-3">Empowering students worldwide with quality education and innovative learning experiences.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-decoration-none">
                            <i class="fab fa-facebook-f fa-lg"></i>
                        </a>
                        <a href="#" class="text-decoration-none">
                            <i class="fab fa-twitter fa-lg"></i>
                        </a>
                        <a href="#" class="text-decoration-none">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <a href="#" class="text-decoration-none">
                            <i class="fab fa-linkedin-in fa-lg"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-2 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#courses">Courses</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 mb-4">
                    <h5>Departments</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Engineering</a></li>
                        <li><a href="#">Management</a></li>
                        <li><a href="#">Information Technology</a></li>
                        <li><a href="#">English</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 mb-4">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i>Matara, Sri Lanka</li>
                        <li><i class="fas fa-phone me-2"></i>+94 76 120 7155</li>
                        <li><i class="fas fa-envelope me-2"></i><a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="acc5c2cac3ecc6cdc2c9c8d482c0c7">[email&#160;protected]</a></li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2025 JanEDx. All rights reserved. Built with ❤️ for better education.</p>
            </div>
        </div>
    </footer>

    <df-messenger
        intent="WELCOME"
        chat-title="JanEDx"
        agent-id="52422a8d-31ed-4972-b02c-ca29d86309e5"
        language-code="en"
        chat-icon="icon.png">
    </df-messenger>

    <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://www.gstatic.com/dialogflow-console/fast/messenger/bootstrap.js?v=1"></script>

    <!-- Hero Slider JavaScript -->
    <script>
            
        function formatPrice($price) {
            return 'Rs.' . number_format($price, 2);
        }

   
        let currentSlideIndex = 0;
        const slides = document.querySelectorAll('.hero-slide');
        const dots = document.querySelectorAll('.hero-dot');
        const totalSlides = slides.length;

        function showSlide(index) {
            // Hide all slides
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Show current slide
            slides[index].classList.add('active');
            dots[index].classList.add('active');
        }

        function nextSlide() {
            currentSlideIndex = (currentSlideIndex + 1) % totalSlides;
            showSlide(currentSlideIndex);
        }

        function previousSlide() {
            currentSlideIndex = (currentSlideIndex - 1 + totalSlides) % totalSlides;
            showSlide(currentSlideIndex);
        }

        function currentSlide(index) {
            currentSlideIndex = index - 1;
            showSlide(currentSlideIndex);
        }

        // Auto-advance slides every 6 seconds
        setInterval(nextSlide, 6000);

        // Pause auto-advance on hover
        const heroSlider = document.querySelector('.hero-slider');
        let autoSlideInterval;

        function startAutoSlide() {
            autoSlideInterval = setInterval(nextSlide, 6000);
        }

        function stopAutoSlide() {
            clearInterval(autoSlideInterval);
        }

        heroSlider.addEventListener('mouseenter', stopAutoSlide);
        heroSlider.addEventListener('mouseleave', startAutoSlide);

        // Start auto-slide initially
        startAutoSlide();

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                previousSlide();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
            }
        });
    </script>

    <script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'969305410e699c3b',t:'MTc1NDE5NDgwNi4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script>
</body>
</html>
