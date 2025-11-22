<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'velvetvogue';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Fetch user information
$user_query = "SELECT u.username, u.email, c.firstname, c.lastname, c.address, c.contactno, c.country 
               FROM user u 
               LEFT JOIN customer c ON u.user_id = c.user_id 
               WHERE u.user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

// Fetch cart items
$cart_query = "SELECT c.cart_id, c.product_id, c.quantity, c.price, p.product_name, p.image_path 
               FROM cart c 
               JOIN product p ON c.product_id = p.product_id 
               WHERE c.user_id = ? 
               ORDER BY c.created_at DESC";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param('i', $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$cart_items = array();

while ($row = $cart_result->fetch_assoc()) {
    $cart_items[] = $row;
}
$cart_stmt->close();

// Calculate totals
$subtotal = 0;
$item_count = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $item_count += $item['quantity'];
}

if ($item_count == 0) {
    header('Location: cart.php');
    exit();
}

$tax = $subtotal * 0.1; // 10% tax
$shipping = 5.99;
$total = $subtotal + $tax + $shipping;

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $shipping_address = trim($_POST['shipping_address']);
    $payment_method = trim($_POST['payment_method']);
    
    // Validate required fields
    if (empty($shipping_address)) {
        $error = "Shipping address is required.";
    } else {
        // Begin transaction
        $conn->autocommit(FALSE);
        
        try {
            // Insert order
            $order_query = "INSERT INTO orders (user_id, total_amount, order_status, shipping_address, payment_status, created_at, updated_at) 
                            VALUES (?, ?, 'pending', ?, 'pending', NOW(), NOW())";
            $order_stmt = $conn->prepare($order_query);
            $order_stmt->bind_param('ids', $user_id, $total, $shipping_address);
            $order_stmt->execute();
            $order_id = $conn->insert_id;
            $order_stmt->close();
            
            // Insert order items
            foreach ($cart_items as $item) {
                $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                               VALUES (?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_query);
                $item_stmt->bind_param('iiid', $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $item_stmt->execute();
                $item_stmt->close();
            }
            
            // Clear cart
            $clear_query = "DELETE FROM cart WHERE user_id = ?";
            $clear_stmt = $conn->prepare($clear_query);
            $clear_stmt->bind_param('i', $user_id);
            $clear_stmt->execute();
            $clear_stmt->close();
            
            // Commit transaction
            $conn->commit();
            $conn->autocommit(TRUE);
            
            // Redirect to order confirmation
            header("Location: order_confirmation.php?id=$order_id");
            exit();
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $conn->autocommit(TRUE);
            $error = "An error occurred while processing your order. Please try again.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Velvet Vogue</title>
    <link rel="icon" href="img/logo2.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2c4c75;
            --primary-dark: #1a365d;
            --secondary: #00bfff;
            --accent: #ff6b6b;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border: #dee2e6;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            padding-top: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Back to Cart Button */
        .back-to-cart {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-to-cart:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .checkout-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .checkout-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: var(--primary);
        }

        .checkout-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: stretch;
        }

        @media (max-width: 992px) {
            .checkout-content {
                grid-template-columns: 1fr;
            }
        }

        .checkout-form {
            display: flex;
            flex-direction: column;
            gap: 30px;
            height: 100%;
        }

        .checkout-section {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .checkout-section-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .section-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--secondary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 76, 117, 0.1);
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-col {
            flex: 1;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        @media (max-width: 576px) {
            .payment-methods {
                grid-template-columns: 1fr;
            }
        }

        .payment-method {
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .payment-method:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
        }

        .payment-method input {
            display: none;
        }

        .payment-method.selected {
            border-color: var(--primary);
            background-color: rgba(44, 76, 117, 0.05);
            box-shadow: 0 5px 15px rgba(44, 76, 117, 0.1);
        }

        .payment-method i {
            font-size: 24px;
            color: var(--primary);
        }

        .payment-method-label {
            font-weight: 500;
            text-align: center;
        }

        .order-summary {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 30px;
            display: flex;
            flex-direction: column;
            height: fit-content;
        }

        .summary-items {
            flex: 1;
            max-height: 350px;
            overflow-y: auto;
            margin-bottom: 20px;
            padding-right: 10px;
        }

        .summary-items::-webkit-scrollbar {
            width: 6px;
        }

        .summary-items::-webkit-scrollbar-thumb {
            background: var(--gray);
            border-radius: 3px;
        }

        .summary-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            object-fit: cover;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
        }

        .item-image i {
            color: var(--gray);
            font-size: 20px;
        }

        .item-details {
            flex: 1;
            min-width: 0;
        }

        .item-name {
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-price-quantity {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: var(--gray);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }

        .summary-row:not(:last-child) {
            border-bottom: 1px solid var(--light-gray);
        }

        .summary-label {
            color: var(--gray);
        }

        .summary-value {
            font-weight: 500;
        }

        .summary-total {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            margin-top: 10px;
        }

        .place-order-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .place-order-btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid var(--danger);
            color: var(--danger);
        }

        .customer-info {
            background: var(--light);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: 500;
            width: 150px;
            color: var(--dark);
        }

        .info-value {
            flex: 1;
            color: var(--gray);
        }

        /* Progress indicator */
        .checkout-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }

        .progress-bar {
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--light-gray);
            z-index: 1;
        }

        .progress-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 66%;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 2;
            position: relative;
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--light);
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--gray);
        }

        .step-label {
            font-size: 14px;
            color: var(--gray);
            text-align: center;
            font-weight: 500;
        }

        .progress-step.active .step-number {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .progress-step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }

        .progress-step.completed .step-number {
            background: var(--success);
            color: white;
            border-color: var(--success);
        }

        .progress-step.completed .step-label {
            color: var(--success);
        }

        /* Mobile Responsive Styles */
        @media (max-width: 965px) {
            body {
                padding: 15px;
            }

            .container {
                padding: 20px 15px;
            }

            .checkout-header h1 {
                font-size: 1.8rem;
            }

            .checkout-content {
                grid-template-columns: 1fr;
            }

            .checkout-form, .order-summary {
                width: 100%;
            }

            .form-row {
                flex-direction: column;
            }

            .form-col {
                width: 100%;
            }
        }

        @media (max-width: 485px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 10px;
                max-width: 100%;
            }

            .checkout-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .checkout-header h1 {
                font-size: 1.3rem;
            }

            .back-to-cart {
                font-size: 13px;
                padding: 8px 12px;
                width: 100%;
                justify-content: center;
            }

            .checkout-progress {
                margin-bottom: 20px;
            }

            .progress-step {
                flex: 1;
            }

            .step-number {
                width: 25px;
                height: 25px;
                font-size: 12px;
            }

            .step-label {
                font-size: 11px;
                max-width: 80px;
                word-wrap: break-word;
            }

            .checkout-section {
                padding: 15px;
                border-radius: 8px;
            }

            .section-title {
                font-size: 1rem;
                padding-bottom: 10px;
                margin-bottom: 15px;
            }

            .section-title i {
                font-size: 16px;
            }

            .customer-info {
                padding: 15px;
            }

            .info-row {
                flex-direction: column;
                gap: 5px;
                margin-bottom: 12px;
            }

            .info-label {
                width: 100%;
                font-size: 13px;
            }

            .info-value {
                font-size: 13px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-group label {
                font-size: 13px;
                margin-bottom: 6px;
            }

            .form-control {
                padding: 10px 12px;
                font-size: 14px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .payment-methods {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .payment-method {
                padding: 12px;
            }

            .payment-method i {
                font-size: 20px;
            }

            .payment-method-label {
                font-size: 13px;
            }

            .order-summary {
                padding: 15px;
            }

            .summary-items {
                max-height: 250px;
            }

            .summary-item {
                padding: 12px 0;
                gap: 10px;
            }

            .item-image {
                width: 50px;
                height: 50px;
            }

            .item-name {
                font-size: 13px;
            }

            .item-price-quantity {
                font-size: 12px;
            }

            .summary-row {
                padding: 8px 0;
                font-size: 13px;
            }

            .summary-total {
                font-size: 16px;
            }

            .place-order-btn {
                padding: 12px;
                font-size: 13px;
                margin-top: 15px;
            }

            .alert {
                padding: 12px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <script src="indexjs.js"></script>
    <div class="container">
        <a href="cart.php" class="back-to-cart">
            <i class="fas fa-arrow-left"></i> Back to Cart
        </a>

        <div class="checkout-header">
            <h1>Checkout</h1>
        </div>

        <!-- Progress indicator -->
        <div class="checkout-progress">
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div class="progress-step completed">
                <div class="step-number">1</div>
                <div class="step-label">Shopping Cart</div>
            </div>
            <div class="progress-step active">
                <div class="step-number">2</div>
                <div class="step-label">Checkout</div>
            </div>
            <div class="progress-step">
                <div class="step-number">3</div>
                <div class="step-label">Confirmation</div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="checkout-content">
            <div class="checkout-form">
                <div class="checkout-section">
                    <h2 class="section-title"><i class="fas fa-truck"></i> Shipping Information</h2>
                    <div class="checkout-section-content">
                        <div class="customer-info">
                            <div class="info-row">
                                <div class="info-label">Name:</div>
                                <div class="info-value"><?php echo htmlspecialchars($user_data['firstname'] . ' ' . $user_data['lastname']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Email:</div>
                                <div class="info-value"><?php echo htmlspecialchars($user_data['email']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Phone:</div>
                                <div class="info-value"><?php echo htmlspecialchars($user_data['contactno'] ?? 'Not provided'); ?></div>
                            </div>
                        </div>
                        
                        <form method="post" action="checkout.php" id="checkout-form">
                            <div class="form-group">
                                <label for="shipping_address">Shipping Address *</label>
                                <textarea id="shipping_address" name="shipping_address" class="form-control" rows="4" required placeholder="Enter your full shipping address"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="city">City *</label>
                                        <input type="text" id="city" name="city" class="form-control" value="" required placeholder="Enter city">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="postal_code">Postal Code *</label>
                                        <input type="text" id="postal_code" name="postal_code" class="form-control" value="" required placeholder="Enter postal code">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="country">Country *</label>
                                <input type="text" id="country" name="country" class="form-control" value="<?php echo htmlspecialchars($user_data['country'] ?? ''); ?>" required placeholder="Enter country">
                            </div>
                    </div>
                </div>

                <div class="checkout-section">
                    <h2 class="section-title"><i class="fas fa-credit-card"></i> Payment Method</h2>
                    <div class="checkout-section-content">
                        <div class="payment-methods">
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="credit_card" required>
                                <i class="fas fa-credit-card"></i>
                                <div class="payment-method-label">Credit Card</div>
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="paypal">
                                <i class="fab fa-paypal"></i>
                                <div class="payment-method-label">PayPal</div>
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="bank_transfer">
                                <i class="fas fa-university"></i>
                                <div class="payment-method-label">Bank Transfer</div>
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="cash_on_delivery">
                                <i class="fas fa-money-bill-wave"></i>
                                <div class="payment-method-label">Cash on Delivery</div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="order-summary">
                <h2 class="section-title"><i class="fas fa-receipt"></i> Order Summary</h2>
                <div class="checkout-section-content">
                    <div class="summary-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="summary-item">
                                <div class="item-image">
                                    <?php if (!empty($item['image_path']) && file_exists(__DIR__ . '/' . $item['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-image"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="item-price-quantity">
                                        <span>$<?php echo number_format($item['price'], 2); ?></span>
                                        <span>Qty: <?php echo $item['quantity']; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-row">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-value">$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Tax (10%)</span>
                        <span class="summary-value">$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Shipping</span>
                        <span class="summary-value">$<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span class="summary-label">Total</span>
                        <span class="summary-value">$<?php echo number_format($total, 2); ?></span>
                    </div>

                    <button type="submit" name="place_order" class="place-order-btn">
                        <i class="fas fa-lock"></i> Place Order
                    </button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Remove selected class from all methods
                document.querySelectorAll('.payment-method').forEach(m => {
                    m.classList.remove('selected');
                });
                
                // Add selected class to clicked method
                this.classList.add('selected');
                
                // Check the radio button
                this.querySelector('input').checked = true;
            });
        });
        
        // Select first payment method by default
        document.addEventListener('DOMContentLoaded', function() {
            const firstMethod = document.querySelector('.payment-method');
            if (firstMethod) {
                firstMethod.classList.add('selected');
                firstMethod.querySelector('input').checked = true;
            }
        });

        // Form validation
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const city = document.getElementById('city').value.trim();
            const postalCode = document.getElementById('postal_code').value.trim();
            const country = document.getElementById('country').value.trim();
            const shippingAddress = document.getElementById('shipping_address').value.trim();
            
            if (!shippingAddress || !city || !postalCode || !country) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>