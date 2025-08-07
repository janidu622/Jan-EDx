<?php
require_once 'config.php';
requireLogin();

$course_id = $_POST['course_id'] ?? $_GET['course_id'] ?? 0;

// Get course details
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    redirect('index.php?error=course_not_found');
}

// Check if user already purchased this course
if (hasPurchasedCourse($_SESSION['user_id'], $course_id)) {
    redirect('watch_course.php?id=' . $course_id);
}

$user = getCurrentUser();
$payment_processed = false;
$error = '';

// Handle payment processing
if ($_POST && isset($_POST['process_payment'])) {
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (empty($payment_method)) {
        $error = 'Please select a payment method';
    } else {
        // Simulate payment processing delay
        sleep(2);
        
        // Fake payment success (90% success rate for demo)
        $payment_success = (rand(1, 10) <= 9);
        
        if ($payment_success) {
            // Record the purchase
            $stmt = $pdo->prepare("INSERT INTO purchases (user_id, course_id, amount, payment_method) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $course_id, $course['price'], $payment_method])) {
                $payment_processed = true;
            } else {
                $error = 'Failed to process purchase. Please try again.';
            }
        } else {
            $error = 'Payment failed. Please try again with a different payment method.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .checkout-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 50px 0;
        }
        .checkout-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .course-summary {
            background: #f8f9fa;
            padding: 30px;
            border-bottom: 1px solid #dee2e6;
        }
        .payment-methods {
            padding: 30px;
        }
        .payment-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-option:hover {
            border-color: #667eea;
            background-color: #f8f9fa;
        }
        .payment-option.selected {
            border-color: #667eea;
            background-color: rgba(102, 126, 234, 0.1);
        }
        .payment-option input[type="radio"] {
            transform: scale(1.2);
        }
        .success-animation {
            animation: fadeInUp 0.8s ease-out;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .processing-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .processing-content {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Processing Overlay -->
    <div id="processingOverlay" class="processing-overlay" style="display: none;">
        <div class="processing-content">
            <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
            <h4>Processing Payment...</h4>
            <p class="text-muted mb-0">Please wait while we process your payment</p>
        </div>
    </div>

    <div class="checkout-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <?php if ($payment_processed): ?>
                        <!-- Success Message -->
                        <div class="checkout-card success-animation">
                            <div class="text-center p-5">
                                <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                                <h2 class="text-success mb-3">Payment Successful!</h2>
                                <p class="lead mb-4">You have successfully enrolled in <strong><?php echo htmlspecialchars($course['title']); ?></strong></p>
                                
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="watch_course.php?id=<?php echo $course['id']; ?>" class="btn btn-success btn-lg">
                                        <i class="fas fa-play"></i> Start Learning
                                    </a>
                                    <a href="dashboard.php" class="btn btn-outline-primary btn-lg">
                                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Checkout Form -->
                        <div class="checkout-card">
                            <!-- Course Summary -->
                            <div class="course-summary">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                                         alt="Course thumbnail" class="rounded me-4" style="width: 100px; height: 60px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($course['title']); ?></h5>
                                        <p class="text-muted mb-0">by <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                    </div>
                                    <div class="text-end">
                                        <h4 class="text-primary mb-0"><?php echo formatPrice($course['price']); ?></h4>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Methods -->
                            <div class="payment-methods">
                                <h4 class="mb-4"><i class="fas fa-credit-card"></i> Payment Method</h4>
                                
                                <?php if ($error): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="alert alert-info mb-4">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Demo Mode:</strong> 
                                </div>

                                <form method="POST" id="paymentForm">
                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                    <input type="hidden" name="process_payment" value="1">

                                    <!-- Credit Card -->
                                    <div class="payment-option" onclick="selectPayment('credit_card')">
                                        <div class="d-flex align-items-center">
                                            <input type="radio" name="payment_method" value="credit_card" id="credit_card" class="me-3">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-credit-card fa-2x text-primary me-3"></i>
                                                    <div>
                                                        <h6 class="mb-0">Credit Card</h6>
                                                        <small class="text-muted">Visa, Mastercard, American Express</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="payment-icons">
                                                <i class="fab fa-cc-visa fa-2x text-info me-2"></i>
                                                <i class="fab fa-cc-mastercard fa-2x text-warning me-2"></i>
                                                <i class="fab fa-cc-amex fa-2x text-primary"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PayPal -->
                                    <div class="payment-option" onclick="selectPayment('paypal')">
                                        <div class="d-flex align-items-center">
                                            <input type="radio" name="payment_method" value="paypal" id="paypal" class="me-3">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center">
                                                    <i class="fab fa-paypal fa-2x text-primary me-3"></i>
                                                    <div>
                                                        <h6 class="mb-0">PayPal</h6>
                                                        <small class="text-muted">Pay with your PayPal account</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Google Pay -->
                                    <div class="payment-option" onclick="selectPayment('google_pay')">
                                        <div class="d-flex align-items-center">
                                            <input type="radio" name="payment_method" value="google_pay" id="google_pay" class="me-3">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center">
                                                    <i class="fab fa-google-pay fa-2x text-success me-3"></i>
                                                    <div>
                                                        <h6 class="mb-0">Google Pay</h6>
                                                        <small class="text-muted">Quick and secure payment</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <!-- Order Summary -->
                                    <div class="bg-light p-3 rounded mb-4">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Course Price:</span>
                                            <span><?php echo formatPrice($course['price']); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Discount:</span>
                                            <span class="text-success">$0.00</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <strong>Total:</strong>
                                            <strong class="text-primary"><?php echo formatPrice($course['price']); ?></strong>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-3">
                                        <button type="submit" class="btn btn-primary btn-lg flex-grow-1" id="paymentBtn">
                                            <i class="fas fa-lock me-2"></i>
                                            Complete Payment
                                        </button>
                                        <a href="course_detail.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-secondary btn-lg">
                                            Cancel
                                        </a>
                                    </div>

                                    <div class="text-center mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-shield-alt me-1"></i>
                                            Your payment information is secure and encrypted
                                        </small>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPayment(method) {
            // Remove selected class from all options
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.getElementById(method).checked = true;
        }

        // Handle form submission
        document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
            const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
            
            if (!selectedPayment) {
                e.preventDefault();
                alert('Please select a payment method');
                return;
            }

            // Show processing overlay
            document.getElementById('processingOverlay').style.display = 'flex';
            
            // Disable the submit button
            const paymentBtn = document.getElementById('paymentBtn');
            paymentBtn.disabled = true;
            paymentBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        });

        // Auto-select first payment method
        document.addEventListener('DOMContentLoaded', function() {
            const firstPaymentOption = document.querySelector('.payment-option');
            if (firstPaymentOption) {
                firstPaymentOption.click();
            }
        });
    </script>
</body>
</html>