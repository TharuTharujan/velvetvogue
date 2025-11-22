<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get order ID from URL parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: edit_profile.php');
    exit();
}

$order_id = intval($_GET['id']);
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

// Fetch order details for this user
$stmt = $conn->prepare('SELECT o.*, COUNT(oi.item_id) as item_count FROM orders o LEFT JOIN order_items oi ON o.order_id = oi.order_id WHERE o.order_id = ? AND o.user_id = ? GROUP BY o.order_id');
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

// If order doesn't exist or doesn't belong to this user
if (!$order) {
    header('Location: edit_profile.php');
    exit();
}

// Fetch order items
$stmt = $conn->prepare('SELECT oi.*, p.product_name as product_name, p.image_path as product_image FROM order_items oi LEFT JOIN product p ON oi.product_id = p.product_id WHERE oi.order_id = ?');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Velvet Vogue</title>
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

        /* Back to Profile Button */
        .back-to-profile {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-to-profile:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .back-to-profile i {
            margin-right: 8px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .order-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: var(--primary);
        }

        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .order-info-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 20px;
        }

        .order-info-card h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--primary);
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .info-item {
            margin-bottom: 12px;
        }

        .info-item strong {
            display: block;
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .info-item span {
            color: var(--gray);
            font-size: 14px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cce7ff;
            color: #004085;
        }

        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .order-items {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
        }

        .order-items h2 {
            font-size: 22px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .items-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .item-card {
            display: flex;
            gap: 15px;
            padding: 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .item-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 6px;
            object-fit: cover;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
        }

        .item-image i {
            color: var(--gray);
            font-size: 24px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .item-price-quantity {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: var(--gray);
        }

        .item-total {
            font-weight: 600;
            color: var(--primary);
            margin-top: 8px;
        }

        .order-summary {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 25px;
        }

        .order-summary h2 {
            font-size: 22px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
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

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .order-info-grid {
                grid-template-columns: 1fr;
            }
            
            .items-list {
                grid-template-columns: 1fr;
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

            .back-to-profile {
                font-size: 13px;
                padding: 8px 12px;
                width: 100%;
                text-align: center;
                display: block;
            }

            .back-to-profile i {
                margin-right: 6px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                margin-bottom: 20px;
                padding-bottom: 15px;
            }

            .order-header h1 {
                font-size: 1.3rem;
            }

            .order-header > div {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                width: 100%;
            }

            .status-badge {
                font-size: 11px;
                padding: 4px 10px;
            }

            .order-info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
                margin-bottom: 20px;
            }

            .order-info-card {
                padding: 15px;
                border-radius: 8px;
            }

            .order-info-card h3 {
                font-size: 16px;
                margin-bottom: 12px;
                padding-bottom: 8px;
            }

            .info-item {
                margin-bottom: 10px;
            }

            .info-item strong {
                font-size: 13px;
                margin-bottom: 3px;
            }

            .info-item span {
                font-size: 13px;
            }

            .order-items {
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 8px;
            }

            .order-items h2 {
                font-size: 18px;
                margin-bottom: 15px;
                padding-bottom: 12px;
            }

            .items-list {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .item-card {
                padding: 12px;
                gap: 12px;
            }

            .item-image {
                width: 60px;
                height: 60px;
            }

            .item-image i {
                font-size: 20px;
            }

            .item-name {
                font-size: 14px;
                margin-bottom: 6px;
            }

            .item-price-quantity {
                font-size: 12px;
                flex-direction: column;
                gap: 4px;
            }

            .item-total {
                font-size: 13px;
                margin-top: 6px;
            }

            .order-summary {
                padding: 15px;
                border-radius: 8px;
            }

            .order-summary h2 {
                font-size: 18px;
                margin-bottom: 15px;
                padding-bottom: 12px;
            }

            .summary-row {
                padding: 8px 0;
                font-size: 13px;
            }

            .summary-total {
                font-size: 16px;
                margin-top: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="edit_profile.php" class="back-to-profile">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>

        <div class="order-header">
            <h1>Order Details</h1>
            <div>
                <span class="status-badge status-<?php echo htmlspecialchars($order['order_status']); ?>">
                    <?php echo ucfirst(htmlspecialchars($order['order_status'])); ?>
                </span>
                <span class="status-badge status-<?php echo htmlspecialchars($order['payment_status']); ?>">
                    <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                </span>
            </div>
        </div>

        <div class="order-info-grid">
            <div class="order-info-card">
                <h3>Order Information</h3>
                <div class="info-item">
                    <strong>Order ID</strong>
                    <span>#<?php echo htmlspecialchars($order['order_id']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Order Date</strong>
                    <span><?php echo date('F j, Y H:i', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <strong>Items</strong>
                    <span><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?></span>
                </div>
            </div>

            <div class="order-info-card">
                <h3>Shipping Address</h3>
                <div class="info-item">
                    <span><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                </div>
            </div>
        </div>

        <div class="order-items">
            <h2>Order Items</h2>
            <div class="items-list">
                <?php while($item = $items_result->fetch_assoc()): ?>
                    <div class="item-card">
                        <div class="item-image">
                            <?php if (!empty($item['product_image']) && file_exists(__DIR__ . '/' . $item['product_image'])): ?>
                                <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
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
                            <div class="item-total">
                                Total: $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="summary-row">
                <span class="summary-label">Subtotal</span>
                <span class="summary-value">$<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Shipping</span>
                <span class="summary-value">$0.00</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Tax</span>
                <span class="summary-value">$0.00</span>
            </div>
            <div class="summary-row summary-total">
                <span class="summary-label">Total</span>
                <span class="summary-value">$<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>
    </div>
</body>
</html>