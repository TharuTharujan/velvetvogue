<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_username'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'velvetvogue';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Fetch orders for the user
$stmt = $conn->prepare('SELECT o.*, COUNT(oi.item_id) as item_count FROM orders o LEFT JOIN order_items oi ON o.order_id = oi.order_id WHERE o.user_id = ? GROUP BY o.order_id ORDER BY o.created_at DESC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Velvet Vogue</title>
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

        /* Back to Home Button */
        .back-to-home {
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

        .back-to-home:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .back-to-home i {
            margin-right: 8px;
        }

        .account-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .account-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: var(--primary);
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            color: var(--gray);
            font-size: 14px;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        .breadcrumb span {
            margin: 0 10px;
        }

        .account-layout {
            display: flex;
            gap: 30px;
        }

        .account-sidebar {
            flex: 0 0 280px;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 25px;
            height: fit-content;
        }

        .account-sidebar h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--primary);
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 10px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--gray);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover, 
        .sidebar-menu a.active {
            background: rgba(44, 76, 117, 0.1);
            color: var(--primary);
        }

        .sidebar-menu a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .account-content {
            flex: 1;
        }

        .account-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 30px;
        }

        .account-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .account-card-header h2 {
            font-size: 22px;
            font-weight: 600;
            color: var(--primary);
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .orders-container {
            margin-top: 20px;
        }

        .order-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 15px;
        }

        .order-info {
            display: flex;
            flex-direction: column;
        }

        .order-id {
            font-weight: 600;
            color: var(--primary);
            font-size: 18px;
        }

        .order-date {
            color: var(--gray);
            font-size: 14px;
            margin-top: 4px;
        }

        .order-status {
            display: flex;
            gap: 10px;
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

        .order-details {
            margin-bottom: 15px;
        }

        .order-summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .order-total {
            font-weight: 600;
            color: var(--primary);
        }

        .order-address {
            font-size: 14px;
            color: var(--gray);
        }

        .order-actions {
            display: flex;
            justify-content: flex-end;
        }

        .order-details-btn {
            font-size: 13px;
            padding: 8px 15px;
        }

        .no-orders {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .no-orders i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #e9ecef;
        }

        .no-orders h3 {
            margin-bottom: 10px;
            color: var(--dark);
        }

        .no-orders p {
            margin-bottom: 20px;
        }

        @media (max-width: 992px) {
            .account-layout {
                flex-direction: column;
            }
            
            .account-sidebar {
                flex: 0 0 auto;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .account-card {
                padding: 20px;
            }
            
            .account-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .order-status {
                align-self: flex-start;
            }
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 965px) {
            .container {
                padding: 15px;
            }

            .orders-header h1 {
                font-size: 1.8rem;
            }

            .order-card {
                padding: 20px 15px;
            }

            .order-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .orders-header h1 {
                font-size: 1.5rem;
            }

            .back-to-home {
                font-size: 14px;
                padding: 8px 15px;
            }
        }
    </style>
</head>
<body>
    <script src="indexjs.js"></script>
    <div class="container">
        <a href="index.php" class="back-to-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <div class="account-header">
            <h1>My Account</h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <span>My Account</span>
            </div>
        </div>

        <div class="account-layout">
            <div class="account-sidebar">
                <h3>Account Settings</h3>
                <ul class="sidebar-menu">
                    <li><a href="edit_profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a></li>
                    <li><a href="my_orders.php" class="active"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                    <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                    <li><a href="address_book.php"><i class="fas fa-map-marker-alt"></i> Address Book</a></li>
                    <li><a href="payment_methods.php"><i class="fas fa-credit-card"></i> Payment Methods</a></li>
                    <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                </ul>
            </div>

            <div class="account-content">
                <div class="account-card">
                    <div class="account-card-header">
                        <h2>My Orders</h2>
                    </div>
                    
                    <?php if (isset($orders_result) && $orders_result && $orders_result->num_rows > 0): ?>
                        <div class="orders-container">
                            <?php while($order = $orders_result->fetch_assoc()): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-info">
                                            <span class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></span>
                                            <span class="order-date"><?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></span>
                                        </div>
                                        <div class="order-status">
                                            <span class="status-badge status-<?php echo htmlspecialchars($order['order_status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($order['order_status'])); ?>
                                            </span>
                                            <span class="status-badge status-<?php echo htmlspecialchars($order['payment_status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="order-details">
                                        <div class="order-summary">
                                            <span><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?></span>
                                            <span class="order-total">Total: $<?php echo number_format($order['total_amount'], 2); ?></span>
                                        </div>
                                        <div class="order-address">
                                            <strong>Shipping to:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <a href="order_detail.php?id=<?php echo $order['order_id']; ?>" class="btn btn-outline order-details-btn">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-orders">
                            <i class="fas fa-shopping-cart"></i>
                            <h3>No Orders Found</h3>
                            <p>You haven't placed any orders yet.</p>
                            <a href="products.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>