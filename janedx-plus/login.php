<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$error_messages = [
    'oauth_failed' => 'Authentication failed. Please try again.',
    'email_exists' => 'An account with this email already exists. Please use a different login method.',
    'registration_failed' => 'Failed to create account. Please try again.',
    'invalid_state' => 'Invalid authentication request. Please try again.'
];

if (isset($_GET['error']) && isset($error_messages[$_GET['error']])) {
    $error = $error_messages[$_GET['error']];
}

// Handle email/password login
if ($_POST && isset($_POST['email_login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ? AND password IS NOT NULL");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            redirect('dashboard.php');
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            max-width: 400px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .oauth-btn {
            width: 100%;
            margin-bottom: 15px;
            padding: 15px 20px;
            font-size: 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .google-btn {
            background-color: #db4437;
            color: white;
        }
        .google-btn:hover {
            background-color: #c23321;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(219, 68, 55, 0.3);
        }
        .facebook-btn {
            background-color: #1877f2;
            color: white;
        }
        .facebook-btn:hover {
            background-color: #166fe5;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(24, 119, 242, 0.3);
        }
        .card {
            border: none;
            border-radius: 15px;
        }
        .brand-logo {
            color: #667eea;
            font-size: 2rem;
            font-weight: bold;
        }
        .loading {
            pointer-events: none;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card login-card">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <h2 class="brand-logo">
                                    <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?>
                                </h2>
                                <p class="text-muted">Sign in to access your courses</p>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <div class="d-grid gap-3 mb-4">
                                <a href="#" class="oauth-btn google-btn" onclick="loginWithGoogle(this)">
                                    <i class="fab fa-google me-3"></i>
                                    <span>Continue with Google</span>
                                </a>
                                
                                <a href="#" class="oauth-btn facebook-btn" onclick="loginWithFacebook(this)">
                                    <i class="fab fa-facebook-f me-3"></i>
                                    <span>Continue with Facebook</span>
                                </a>
                            </div>

                            <!-- Divider -->
                         
                            <!-- Email/Password Login Form -->
                            <form method="POST" class="mb-4">
                                <input type="hidden" name="email_login" value="1">
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               placeholder="Enter your email" required 
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Enter your password" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-dark btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Sign In with Email
                                    </button>
                                </div>
                            </form>

                            <div class="text-center mt-4">
                                <small class="text-muted">
                                    By signing in, you agree to our Terms of Service and Privacy Policy
                                </small>
                            </div>
                             <div class="text-center mt-4">
                                <a href="index.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-right me-1"></i> Find All courses
                                </a>
                            </div><br>
                            <div class="text-center mt-4">
                                <a href="../index.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Home
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function generateState(provider) {
            return provider + '_' + Math.random().toString(36).substring(2, 15) + 
                   Math.random().toString(36).substring(2, 15);
        }

        function showLoading(btn, provider) {
            btn.classList.add('loading');
            const originalContent = btn.innerHTML;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin me-3"></i>Connecting to ${provider}...`;
            return originalContent;
        }

        function loginWithGoogle(btn) {
            const originalContent = showLoading(btn, 'Google');
            
            const clientId = '<?php echo GOOGLE_CLIENT_ID; ?>';
            const redirectUri = encodeURIComponent('<?php echo BASE_URL; ?>/oauth_callback.php');
            const scope = encodeURIComponent('email profile');
            const state = encodeURIComponent(generateState('google'));
            
            const googleAuthUrl = `https://accounts.google.com/oauth/authorize?` +
                `client_id=${clientId}&` +
                `redirect_uri=${redirectUri}&` +
                `scope=${scope}&` +
                `response_type=code&` +
                `state=${state}&` +
                `access_type=online&` +
                `prompt=select_account`;
            
            // Small delay to show loading state
            setTimeout(() => {
                window.location.href = googleAuthUrl;
            }, 500);
        }

        function loginWithFacebook(btn) {
            const originalContent = showLoading(btn, 'Facebook');
            
            const appId = '<?php echo FACEBOOK_APP_ID; ?>';
            const redirectUri = encodeURIComponent('<?php echo BASE_URL; ?>/oauth_callback.php');
            const state = encodeURIComponent(generateState('facebook'));
            
            const facebookAuthUrl = `https://www.facebook.com/v18.0/dialog/oauth?` +
                `client_id=${appId}&` +
                `redirect_uri=${redirectUri}&` +
                `scope=email&` +
                `response_type=code&` +
                `state=${state}`;
            
            // Small delay to show loading state
            setTimeout(() => {
                window.location.href = facebookAuthUrl;
            }, 500);
        }

        // Handle page back/forward navigation
        window.addEventListener('pageshow', function(event) {
            // Reset any loading states if user navigates back
            document.querySelectorAll('.oauth-btn').forEach(btn => {
                btn.classList.remove('loading');
            });
        });

        // Password toggle functionality
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
        });

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    </script>
</body>
</html>