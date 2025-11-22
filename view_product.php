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

// Handle session-based success messages
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    
    // Get product image path before deletion using prepared statement
    $stmt = $conn->prepare("SELECT image_path FROM product WHERE product_id = ?");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $image_row = $result->fetch_assoc()) {
        $image_path = $image_row['image_path'];
        
        // Try to delete the product using prepared statement
        $delete_stmt = $conn->prepare("DELETE FROM product WHERE product_id = ?");
        $delete_stmt->bind_param('i', $product_id);
        
        // Check if product has any orders that are not delivered
        $order_check_stmt = $conn->prepare("SELECT COUNT(*) as pending_orders FROM order_items oi JOIN orders o ON oi.order_id = o.order_id WHERE oi.product_id = ? AND o.order_status != 'delivered'");
        $order_check_stmt->bind_param('i', $product_id);
        $order_check_stmt->execute();
        $order_result = $order_check_stmt->get_result();
        $pending_orders = $order_result->fetch_assoc()['pending_orders'];
        $order_check_stmt->close();
        
        // Temporarily disable exception reporting to handle errors manually
        $old_report_mode = mysqli_report(MYSQLI_REPORT_OFF);
        mysqli_report(MYSQLI_REPORT_OFF);
        
        if ($pending_orders > 0) {
            // Product has undelivered orders, cannot delete
            $error = 'Cannot delete this product because it has ' . $pending_orders . ' order(s) that are not yet delivered. Products can only be deleted when all associated orders have been delivered. You can deactivate the product instead.';
        } else {
            // All orders are delivered, we can safely delete the product
            // Since we want to keep order history, we'll use a soft delete approach
            // But first, let's check if we can do a hard delete (which preserves FK constraints)
            if ($delete_stmt->execute()) {
                // Delete image file if exists
                if ($image_path && file_exists(__DIR__ . '/' . $image_path)) {
                    unlink(__DIR__ . '/' . $image_path);
                }
                $success = 'Product deleted successfully! All order history has been preserved.';
            } else {
                // If we can't delete due to FK constraints, even with all delivered orders,
                // it means we need to keep the product record for order history
                // In this case, we'll deactivate the product instead
                $deactivate_stmt = $conn->prepare("UPDATE product SET active = 0 WHERE product_id = ?");
                $deactivate_stmt->bind_param('i', $product_id);
                
                if ($deactivate_stmt->execute()) {
                    $success = 'Product deactivated successfully! All order history has been preserved. The product is no longer visible to customers.';
                } else {
                    $error = 'Error deactivating product: ' . $conn->error;
                }
                $deactivate_stmt->close();
            }
        }
        
        // Restore the previous reporting mode
        mysqli_report($old_report_mode);
        
        $delete_stmt->close();
    } else {
        $error = 'Product not found.';
    }
    $stmt->close();
}

// Handle product activation/deactivation
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $product_id = intval($_GET['toggle_status']);
    
    // First, check current status
    $stmt = $conn->prepare("SELECT active FROM product WHERE product_id = ?");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        $new_status = $row['active'] ? 0 : 1; // Toggle status
        
        // Update the status
        $update_stmt = $conn->prepare("UPDATE product SET active = ? WHERE product_id = ?");
        $update_stmt->bind_param('ii', $new_status, $product_id);
        
        if ($update_stmt->execute()) {
            $status_text = $new_status ? 'activated' : 'deactivated';
            $success = "Product {$status_text} successfully!";
        } else {
            $error = 'Error updating product status: ' . $conn->error;
        }
        $update_stmt->close();
    } else {
        $error = 'Product not found.';
    }
    $stmt->close();
}

// Filtering and search
$where_conditions = [];
$search = '';
$category_filter = '';
$sort_by = 'product_id DESC';
$params = [];
$param_types = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = trim($_GET['search']);
    $where_conditions[] = "(p.product_name LIKE ? OR p.description LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

if (isset($_GET['category']) && !empty($_GET['category']) && is_numeric($_GET['category'])) {
    $category_filter = intval($_GET['category']);
    $where_conditions[] = "c.category_id = ?";
    $params[] = $category_filter;
    $param_types .= 'i';
}

if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'name_asc':
            $sort_by = 'p.product_name ASC';
            break;
        case 'name_desc':
            $sort_by = 'p.product_name DESC';
            break;
        case 'price_asc':
            $sort_by = 'p.price ASC';
            break;
        case 'price_desc':
            $sort_by = 'p.price DESC';
            break;
        case 'newest':
            $sort_by = 'p.product_id DESC';
            break;
        case 'oldest':
            $sort_by = 'p.product_id ASC';
            break;
    }
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get all products with category and subcategory information
// Also include the active status
$sql = "SELECT p.*, c.category_name, s.subcategory_name 
        FROM product p
        LEFT JOIN subcategory s ON p.subcategory_id = s.subcategory_id
        LEFT JOIN category c ON s.category_id = c.category_id
        $where_clause
        ORDER BY $sort_by";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result();
    $stmt->close();
} else {
    $products = $conn->query($sql);
}

// Get categories for filter dropdown
$categories = $conn->query('SELECT * FROM category ORDER BY category_name');

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Products - Velvet Vogue Admin</title>
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

        .user-section {
            flex-shrink: 0;
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
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
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .add-product-btn {
            background: rgba(255,255,255,0.2);
            color: #fff;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .add-product-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
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
            border-color: #3498db;
        }

        .filter-btn {
            background: #3498db;
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
            background: #2980b9;
        }

        /* Products Grid */
        .products-container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .products-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .products-count {
            color: #6c757d;
            font-weight: 500;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            padding: 25px;
        }

        .product-card {
            background: #fff;
            border: 2px solid #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            border-color: #3498db;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-image .no-image {
            color: #bbb;
            font-size: 3rem;
        }

        .product-info h3 {
            color: #2c3e50;
            font-size: 1.2rem;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .product-category {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .product-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #27ae60;
            margin-bottom: 15px;
        }

        .product-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit {
            background: #f39c12;
            color: #fff;
        }

        .btn-edit:hover {
            background: #e67e22;
        }

        .btn-delete {
            background: #e74c3c;
            color: #fff;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        .no-products {
            text-align: center;
            padding: 60px;
            color: #6c757d;
        }

        .no-products i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ddd;
        }

        .no-products h3 {
            margin-bottom: 10px;
            color: #495057;
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

            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .filter-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .products-grid {
                grid-template-columns: 1fr;
                padding: 15px;
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
                    <li><a href="view_product.php" class="active">
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
                <i class="fas fa-box"></i> Product Management
            </h1>
            <a href="add_product.php" class="add-product-btn">
                <i class="fas fa-plus"></i> Add New Product
            </a>
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
                        <label for="search">Search Products</label>
                        <input type="text" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by name or description...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"
                                    <?php echo ($category_filter == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort">
                            <option value="newest" <?php echo (($_GET['sort'] ?? '') == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo (($_GET['sort'] ?? '') == 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="name_asc" <?php echo (($_GET['sort'] ?? '') == 'name_asc') ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="name_desc" <?php echo (($_GET['sort'] ?? '') == 'name_desc') ? 'selected' : ''; ?>>Name Z-A</option>
                            <option value="price_asc" <?php echo (($_GET['sort'] ?? '') == 'price_asc') ? 'selected' : ''; ?>>Price Low-High</option>
                            <option value="price_desc" <?php echo (($_GET['sort'] ?? '') == 'price_desc') ? 'selected' : ''; ?>>Price High-Low</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="filter-btn">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Products Container -->
        <div class="products-container">
            <div class="products-header">
                <div class="products-count">
                    <?php 
                    $total = $products ? $products->num_rows : 0;
                    echo $total . ' product' . ($total != 1 ? 's' : '') . ' found';
                    ?>
                </div>
            </div>

            <?php if ($products && $products->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while($product = $products->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if (!empty($product['image_path']) && file_exists(__DIR__ . '/' . $product['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-image no-image"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                
                                <div class="product-category">
                                    <i class="fas fa-tag"></i>
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?>
                                    <?php if ($product['subcategory_name']): ?>
                                        > <?php echo htmlspecialchars($product['subcategory_name']); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-description">
                                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                                </div>
                                
                                <div class="product-price">
                                    $<?php echo number_format($product['price'], 2); ?>
                                </div>
                                
                                <?php if (!$product['active']): ?>
                                    <div style="background: #f8d7da; color: #721c24; padding: 8px 12px; border-radius: 6px; margin-bottom: 10px; font-weight: 500;">
                                        <i class="fas fa-exclamation-circle"></i> Deactivated Product
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-actions">
                                    <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if ($product['active']): ?>
                                        <a href="?toggle_status=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-delete"
                                           onclick="return confirm('Are you sure you want to deactivate this product? It will no longer be visible to customers.')">
                                            <i class="fas fa-times-circle"></i> Deactivate
                                        </a>
                                    <?php else: ?>
                                        <a href="?toggle_status=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-edit"
                                           onclick="return confirm('Are you sure you want to activate this product? It will be visible to customers again.')">
                                            <i class="fas fa-check-circle"></i> Activate
                                        </a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $product['product_id']; ?>" 
                                       class="btn btn-delete"
                                       onclick="return confirm('Are you sure you want to delete this product? If the product has any orders (even delivered), it will be deactivated instead to preserve order history.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-products">
                    <i class="fas fa-box-open"></i>
                    <h3>No Products Found</h3>
                    <p>
                        <?php if ($search || $category_filter): ?>
                            Try adjusting your search criteria or <a href="view_product.php">view all products</a>.
                        <?php else: ?>
                            Start by adding your first product to the store.
                        <?php endif; ?>
                    </p>
                    <br>
                    <a href="add_product.php" class="btn" style="background: #3498db; color: #fff; text-decoration: none;">
                        <i class="fas fa-plus"></i> Add Product
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide success messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.querySelector('.alert.success');
            if (successAlert) {
                setTimeout(function() {
                    successAlert.style.transition = 'opacity 0.5s ease';
                    successAlert.style.opacity = '0';
                    setTimeout(function() {
                        successAlert.style.display = 'none';
                    }, 500);
                }, 4000); // Hide after 4 seconds, giving 0.5s for fade out
            }
        });
        
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
