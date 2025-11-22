<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'seller') {
    header('Location: login.php');
    exit();
}

// Get order ID from URL parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: order_management.php');
    exit();
}

$order_id = intval($_GET['id']);

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'velvetvogue';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Fetch order details (without user restriction for admin)
$stmt = $conn->prepare('SELECT o.*, u.username, u.email, COUNT(oi.item_id) as item_count FROM orders o LEFT JOIN user u ON o.user_id = u.user_id LEFT JOIN order_items oi ON o.order_id = oi.order_id WHERE o.order_id = ? GROUP BY o.order_id');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

// If order doesn't exist
if (!$order) {
    header('Location: order_management.php');
    exit();
}

// Fetch order items with product details
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
    <title>Order Details - Velvet Vogue Admin</title>
    <link rel="icon" href="img/logo2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin-left: 280px;
            color: #333;
        }

        /* Vertical Sidebar Navigation */
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50, #34495e);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            /* Hide scrollbar */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }

        .admin-sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }

        .nav-container {
            flex: 1;
            overflow-y: auto;
            /* Hide scrollbar */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }

        .nav-container::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }

        .user-section {
            flex-shrink: 0;
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .logo {
            color: #3498db;
            font-size: 24px;
            font-weight: 800;
            text-decoration: none;
            letter-spacing: 1px;
            display: block;
        }

        .logo:hover {
            color: #5dade2;
        }

        .nav-section {
            padding: 20px 0;
            margin-bottom: 20px;
        }

        .nav-title {
            color: #bdc3c7;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 25px 15px 25px;
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-links a {
            color: #ecf0f1;
            text-decoration: none;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-links a:hover {
            background: rgba(52, 152, 219, 0.1);
            border-left-color: #3498db;
            padding-left: 30px;
        }

        .nav-links a.active {
            background: rgba(52, 152, 219, 0.2);
            border-left-color: #3498db;
            color: #3498db;
            font-weight: 600;
        }

        .nav-links a i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 16px;
        }

        .user-details h4 {
            color: #ecf0f1;
            margin: 0;
            font-size: 14px;
        }

        .user-details span {
            color: #bdc3c7;
            font-size: 12px;
        }

        .logout {
            background: #e74c3c;
            color: #fff;
            padding: 12px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
        }

        .logout:hover {
            background: #c0392b;
        }

        /* Main Content */
        .main-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .page-title {
            font-size: 2rem;
            color: #2c3e50;
            margin: 0;
        }

        .back-link {
            background: #3498db;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-link:hover {
            background: #2980b9;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .order-header h1 {
            font-size: 24px;
            color: #2c3e50;
            margin: 0;
        }

        .status-container {
            display: flex;
            gap: 10px;
        }

        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 14px;
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

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .info-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 25px;
        }

        .info-card h3 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-item strong {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .info-item span {
            color: #6c757d;
        }

        .items-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 30px;
        }

        .items-section h2 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .items-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .item-card {
            display: flex;
            gap: 15px;
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .item-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 6px;
            object-fit: cover;
            background-color: #f8f9fa;
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
            color: #6c757d;
            font-size: 24px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .item-price-quantity {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .item-total {
            font-weight: 700;
            color: #2c3e50;
            margin-top: 8px;
        }

        .summary-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 25px;
        }

        .summary-section h2 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }

        .summary-row:not(:last-child) {
            border-bottom: 1px solid #f8f9fa;
        }

        .summary-label {
            color: #6c757d;
        }

        .summary-value {
            font-weight: 500;
        }

        .summary-total {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
            }

            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }

            .mobile-menu-btn {
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: #2c3e50;
                color: #fff;
                border: none;
                padding: 12px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 18px;
            }

            .main-container {
                padding: 70px 15px 15px 15px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .items-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" onclick="toggleMobileMenu()" style="display: none;">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Vertical Sidebar Navigation -->
    <aside class="admin-sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index.html" class="logo">
                <i class="fas fa-gem"></i> Velvet Vogue
            </a>
        </div>
        <div class="nav-container">
            <nav class="nav-section">
                <div class="nav-title">Main</div>
                <ul class="nav-links">
                    <li><a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a></li>
                </ul>
            </nav>

            <nav class="nav-section">
                <div class="nav-title">Products</div>
                <ul class="nav-links">
                    <li><a href="add_product.php">
                        <i class="fas fa-plus-circle"></i> Add Product
                    </a></li>
                    <li><a href="view_product.php">
                        <i class="fas fa-box"></i> View Products
                    </a></li>
                    <li><a href="category.php">
                        <i class="fas fa-tags"></i> Categories
                    </a></li>
                </ul>
            </nav>

            <nav class="nav-section">
                <div class="nav-title">Management</div>
                <ul class="nav-links">
                    <li><a href="customer_management.php">
                        <i class="fas fa-users"></i> Customers
                    </a></li>
                    <li><a href="order_management.php" class="active">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a></li>
                    <li><a href="contact_management.php">
                        <i class="fas fa-envelope"></i> Messages
                    </a></li>
                </ul>
            </nav>
        </div>
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_username'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="user-details">
                    <h4><?php echo htmlspecialchars($_SESSION['user_username'] ?? 'Admin'); ?></h4>
                    <span>Administrator</span>
                </div>
            </div>
            <a href="adminprofile.php" style="background: rgba(52, 152, 219, 0.2); color: #3498db; margin-bottom: 10px; border-radius: 6px; text-decoration: none; padding: 10px 15px; display: flex; align-items: center; gap: 8px; font-weight: 500;">
                <i class="fas fa-user-circle"></i> View Profile
            </a>
            <a href="logout.php" class="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">Order Details</h1>
            <a href="order_management.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>

        <div class="order-header">
            <h1>Order #<?php echo htmlspecialchars($order['order_id']); ?></h1>
            <div class="status-container">
                <span class="status-badge status-<?php echo htmlspecialchars($order['order_status']); ?>">
                    <?php echo ucfirst(htmlspecialchars($order['order_status'])); ?>
                </span>
                <span class="status-badge status-<?php echo htmlspecialchars($order['payment_status']); ?>">
                    <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                </span>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
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
                <div class="info-item">
                    <strong>Total Amount</strong>
                    <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>

            <div class="info-card">
                <h3>Customer Information</h3>
                <div class="info-item">
                    <strong>Name</strong>
                    <span><?php echo htmlspecialchars($order['username'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <strong>Email</strong>
                    <span><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></span>
                </div>
            </div>

            <div class="info-card">
                <h3>Shipping Address</h3>
                <div class="info-item">
                    <span><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                </div>
            </div>
        </div>

        <div class="items-section">
            <h2>Order Items</h2>
            <div class="items-list">
                <?php 
                // Reset the result pointer to the beginning
                mysqli_data_seek($items_result, 0);
                while($item = $items_result->fetch_assoc()): ?>
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

        <div class="summary-section">
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

    <script>
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('mobile-open');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !menuBtn.contains(event.target)) {
                sidebar.classList.remove('mobile-open');
            }
        });

        // Show mobile menu button on small screens
        function handleResize() {
            const menuBtn = document.querySelector('.mobile-menu-btn');
            if (window.innerWidth <= 768) {
                menuBtn.style.display = 'block';
            } else {
                menuBtn.style.display = 'none';
                document.getElementById('sidebar').classList.remove('mobile-open');
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize();
    </script>
</body>
</html>