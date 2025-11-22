<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'seller') {
    header('Location: login.php');
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'velvetvogue';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$success = '';
$error = '';

// Handle customer deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_customer']) && isset($_POST['delete_user_id'])) {
    $delete_user_id = intval($_POST['delete_user_id']);
    // Prevent deleting yourself or other admins
    if ($delete_user_id !== $_SESSION['user_id']) {
        // Check if customer has any orders
        $order_check_stmt = $conn->prepare('SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?');
        $order_check_stmt->bind_param('i', $delete_user_id);
        $order_check_stmt->execute();
        $order_result = $order_check_stmt->get_result();
        $order_data = $order_result->fetch_assoc();
        $order_check_stmt->close();
        
        if ($order_data['order_count'] > 0) {
            // Customer has orders, so we can't delete them due to foreign key constraints
            $error = 'Cannot delete customer with existing orders. Please delete all orders first.';
        } else {
            // Customer has no orders, safe to delete
            // Also delete related records in other tables that reference user_id
            // Delete from wishlist
            $wishlist_stmt = $conn->prepare('DELETE FROM wishlist WHERE user_id = ?');
            $wishlist_stmt->bind_param('i', $delete_user_id);
            $wishlist_stmt->execute();
            $wishlist_stmt->close();
            
            // Delete from cart
            $cart_stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ?');
            $cart_stmt->bind_param('i', $delete_user_id);
            $cart_stmt->execute();
            $cart_stmt->close();
            
            // Delete from user_addresses
            $address_stmt = $conn->prepare('DELETE FROM user_addresses WHERE user_id = ?');
            $address_stmt->bind_param('i', $delete_user_id);
            $address_stmt->execute();
            $address_stmt->close();
            
            // Delete from user_payment_methods
            $payment_stmt = $conn->prepare('DELETE FROM user_payment_methods WHERE user_id = ?');
            $payment_stmt->bind_param('i', $delete_user_id);
            $payment_stmt->execute();
            $payment_stmt->close();
            
            // Now delete the user
            $stmt = $conn->prepare('DELETE FROM user WHERE user_id = ? AND role = "buyer"');
            $stmt->bind_param('i', $delete_user_id);
            if ($stmt->execute()) {
                $success = 'Customer deleted successfully!';
            } else {
                $error = 'Error deleting customer: ' . $conn->error;
            }
            $stmt->close();
        }
    } else {
        $error = 'You cannot delete your own account.';
    }
}

// Handle customer status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $user_id = intval($_POST['user_id']);
    $new_status = $_POST['status'];
    // For this example, we'll add a status column to user table if needed
    // You might want to create a separate customer_status table
    $stmt = $conn->prepare('UPDATE user SET status = ? WHERE user_id = ?');
    $stmt->bind_param('si', $new_status, $user_id);
    if ($stmt->execute()) {
        $success = 'Customer status updated successfully!';
    } else {
        $error = 'Error updating customer status: ' . $conn->error;
    }
    $stmt->close();
}

// Filtering and search
$where_conditions = ['role = "buyer"']; // Only show customers/buyers
$search = '';
$status_filter = '';
$sort_by = 'user_id DESC';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = trim($_GET['search']);
    $search_escaped = $conn->real_escape_string($search);
    $where_conditions[] = "(username LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%')";
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status_filter = $_GET['status'];
    $status_escaped = $conn->real_escape_string($status_filter);
    $where_conditions[] = "status = '$status_escaped'";
}

if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'name_asc':
            $sort_by = 'username ASC';
            break;
        case 'name_desc':
            $sort_by = 'username DESC';
            break;
        case 'email_asc':
            $sort_by = 'email ASC';
            break;
        case 'email_desc':
            $sort_by = 'email DESC';
            break;
        case 'newest':
            $sort_by = 'user_id DESC';
            break;
        case 'oldest':
            $sort_by = 'user_id ASC';
            break;
    }
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get all customers with order count
$sql = "SELECT u.*, 
               COUNT(DISTINCT o.order_id) as order_count,
               COALESCE(SUM(o.total_amount), 0) as total_spent
        FROM user u
        LEFT JOIN orders o ON u.user_id = o.user_id
        $where_clause
        GROUP BY u.user_id
        ORDER BY $sort_by";

$customers = $conn->query($sql);

// Get total customers count
$total_customers = $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'buyer'")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Velvet Vogue Admin</title>
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
            overflow-y: auto;
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
        }

        .page-header {
            background: linear-gradient(135deg, #16a085, #1abc9c);
            color: #fff;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }

        .page-title {
            font-size: 2.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #16a085;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Filters */
        .filters {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 600;
            color: #2c3e50;
        }

        .filter-group input,
        .filter-group select {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #16a085;
        }

        .filter-btn {
            background: #16a085;
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s ease;
            height: fit-content;
        }

        .filter-btn:hover {
            background: #138d75;
        }

        /* Customers Table */
        .customers-container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .customers-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .customers-count {
            color: #6c757d;
            font-weight: 500;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #16a085, #1abc9c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
        }

        .customer-details h4 {
            margin: 0;
            color: #2c3e50;
            font-size: 14px;
        }

        .customer-details span {
            color: #6c757d;
            font-size: 12px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-primary {
            background: #3498db;
            color: #fff;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: #fff;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-warning {
        }
        .btn-danger {
            background: #e74c3c;
            color: #fff;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
            background: #f39c12;
            color: #fff;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .no-customers {
            text-align: center;
            padding: 60px;
            color: #6c757d;
        }

        .no-customers i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ddd;
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

            .filter-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .customers-container {
                overflow-x: auto;
            }

            .stats-grid {
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
                    <li><a href="customer_management.php" class="active">
                        <i class="fas fa-users"></i> Customers
                    </a></li>
                    <li><a href="order_management.php">
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
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-users"></i> Customer Management
            </h1>
            <p>Manage your customers and track their activities</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $total_customers; ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-number">
                    <?php 
                    // Get new customers this month
                    $conn = new mysqli('localhost', 'root', '', 'velvetvogue');
                    $new_customers = $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'buyer' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetch_assoc()['count'] ?? 0;
                    echo $new_customers;
                    $conn->close();
                    ?>
                </div>
                <div class="stat-label">New This Month</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-number">
                    <?php 
                    // Get active customers (with orders)
                    $conn = new mysqli('localhost', 'root', '', 'velvetvogue');
                    $active_customers = $conn->query("SELECT COUNT(DISTINCT o.user_id) as count FROM orders o JOIN user u ON o.user_id = u.user_id WHERE u.role = 'buyer' AND u.status = 'active'")->fetch_assoc()['count'] ?? 0;
                    echo $active_customers;
                    $conn->close();
                    ?>
                </div>
                <div class="stat-label">Active Customers</div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Search Customers</label>
                        <input type="text" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by name, email...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort">
                            <option value="newest" <?php echo (($_GET['sort'] ?? '') == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo (($_GET['sort'] ?? '') == 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="name_asc" <?php echo (($_GET['sort'] ?? '') == 'name_asc') ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="name_desc" <?php echo (($_GET['sort'] ?? '') == 'name_desc') ? 'selected' : ''; ?>>Name Z-A</option>
                            <option value="email_asc" <?php echo (($_GET['sort'] ?? '') == 'email_asc') ? 'selected' : ''; ?>>Email A-Z</option>
                            <option value="email_desc" <?php echo (($_GET['sort'] ?? '') == 'email_desc') ? 'selected' : ''; ?>>Email Z-A</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="filter-btn">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Customers Container -->
        <div class="customers-container">
            <div class="customers-header">
                <div class="customers-count">
                    <?php 
                    $total = $customers ? $customers->num_rows : 0;
                    echo $total . ' customer' . ($total != 1 ? 's' : '') . ' found';
                    ?>
                </div>
            </div>

            <?php if ($customers && $customers->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($customer = $customers->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-avatar">
                                            <?php echo strtoupper(substr($customer['username'], 0, 1)); ?>
                                        </div>
                                        <div class="customer-details">
                                            <h4><?php echo htmlspecialchars($customer['username']); ?></h4>
                                            <span><?php echo 'No name'; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo $customer['order_count']; ?></td>
                                <td>$<?php echo number_format($customer['total_spent'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo isset($customer['status']) ? $customer['status'] : 'active'; ?>">
                                        <?php echo ucfirst(isset($customer['status']) ? $customer['status'] : 'Active'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($customer['created_at']) {
                                        echo date('M j, Y', strtotime($customer['created_at']));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="customer_detail.php?id=<?php echo $customer['user_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <form style="display: inline;" method="POST" action="">
                                        <input type="hidden" name="user_id" value="<?php echo $customer['user_id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo (isset($customer['status']) && $customer['status'] === 'active') ? 'inactive' : 'active'; ?>">
                                        <button type="submit" name="update_status" class="btn <?php echo (isset($customer['status']) && $customer['status'] === 'active') ? 'btn-warning' : 'btn-success'; ?>">
                                            <i class="fas fa-<?php echo (isset($customer['status']) && $customer['status'] === 'active') ? 'ban' : 'check'; ?>"></i>
                                            <?php echo (isset($customer['status']) && $customer['status'] === 'active') ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                        <form style="display: inline;" method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                            <input type="hidden" name="delete_user_id" value="<?php echo $customer['user_id']; ?>">
                                            <button type="submit" name="delete_customer" class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-customers">
                    <i class="fas fa-users"></i>
                    <h3>No Customers Found</h3>
                    <p>
                        <?php if ($search || $status_filter): ?>
                            Try adjusting your search criteria or <a href="customer_management.php">view all customers</a>.
                        <?php else: ?>
                            No customers have registered yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
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
