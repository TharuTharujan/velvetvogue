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

// Handle category addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    
    if (!empty($category_name)) {
        // Check if category already exists
        $check_stmt = $conn->prepare('SELECT category_id FROM category WHERE category_name = ?');
        $check_stmt->bind_param('s', $category_name);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Category already exists!';
        } else {
            $stmt = $conn->prepare('INSERT INTO category (category_name) VALUES (?)');
            $stmt->bind_param('s', $category_name);
            if ($stmt->execute()) {
                $success = 'Category added successfully!';
            } else {
                $error = 'Error adding category: ' . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $error = 'Category name is required.';
    }
}

// Handle category editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $category_id = intval($_POST['category_id']);
    $category_name = trim($_POST['category_name']);
    
    if (!empty($category_name) && $category_id > 0) {
        $stmt = $conn->prepare('UPDATE category SET category_name = ? WHERE category_id = ?');
        $stmt->bind_param('si', $category_name, $category_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Category updated successfully!';
            $stmt->close();
            $conn->close();
            header('Location: category.php');
            exit();
        } else {
            $error = 'Error updating category: ' . $conn->error;
        }
        $stmt->close();
    } else {
        $error = 'Category name is required.';
    }
}

// Handle subcategory addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subcategory'])) {
    $subcategory_name = trim($_POST['subcategory_name']);
    $category_id = intval($_POST['category_id']);
    
    if (!empty($subcategory_name) && $category_id > 0) {
        // Check if subcategory already exists in this category
        $check_stmt = $conn->prepare('SELECT subcategory_id FROM subcategory WHERE subcategory_name = ? AND category_id = ?');
        $check_stmt->bind_param('si', $subcategory_name, $category_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Subcategory already exists in this category!';
        } else {
            $stmt = $conn->prepare('INSERT INTO subcategory (subcategory_name, category_id) VALUES (?, ?)');
            $stmt->bind_param('si', $subcategory_name, $category_id);
            if ($stmt->execute()) {
                $success = 'Subcategory added successfully!';
            } else {
                $error = 'Error adding subcategory: ' . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $error = 'Subcategory name and category selection are required.';
    }
}

// Handle subcategory editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subcategory'])) {
    $subcategory_id = intval($_POST['subcategory_id']);
    $subcategory_name = trim($_POST['subcategory_name']);
    $category_id = intval($_POST['category_id']);
    
    if (!empty($subcategory_name) && $subcategory_id > 0 && $category_id > 0) {
        $stmt = $conn->prepare('UPDATE subcategory SET subcategory_name = ?, category_id = ? WHERE subcategory_id = ?');
        $stmt->bind_param('sii', $subcategory_name, $category_id, $subcategory_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Subcategory updated successfully!';
            $stmt->close();
            $conn->close();
            header('Location: category.php');
            exit();
        } else {
            $error = 'Error updating subcategory: ' . $conn->error;
        }
        $stmt->close();
    } else {
        $error = 'Subcategory name and category selection are required.';
    }
}

// Handle category deletion
if (isset($_GET['delete_category']) && is_numeric($_GET['delete_category'])) {
    $category_id = intval($_GET['delete_category']);
    
    // Check if category has products using prepared statement
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM product p 
                                 JOIN subcategory s ON p.subcategory_id = s.subcategory_id 
                                 WHERE s.category_id = ?");
    $check_stmt->bind_param('i', $category_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $product_count = $check_result->fetch_assoc()['count'];
    $check_stmt->close();
    
    if ($product_count > 0) {
        $error = "Cannot delete category. It has $product_count associated products.";
    } else {
        // Delete subcategories first using prepared statement
        $delete_subs_stmt = $conn->prepare("DELETE FROM subcategory WHERE category_id = ?");
        $delete_subs_stmt->bind_param('i', $category_id);
        $delete_subs_stmt->execute();
        $delete_subs_stmt->close();
        
        // Delete category using prepared statement
        $delete_cat_stmt = $conn->prepare("DELETE FROM category WHERE category_id = ?");
        $delete_cat_stmt->bind_param('i', $category_id);
        if ($delete_cat_stmt->execute()) {
            $success = 'Category and its subcategories deleted successfully!';
        } else {
            $error = 'Error deleting category: ' . $conn->error;
        }
        $delete_cat_stmt->close();
    }
}

// Handle subcategory deletion
if (isset($_GET['delete_subcategory']) && is_numeric($_GET['delete_subcategory'])) {
    $subcategory_id = intval($_GET['delete_subcategory']);
    
    // Check if subcategory has products using prepared statement
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM product WHERE subcategory_id = ?");
    $check_stmt->bind_param('i', $subcategory_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $product_count = $check_result->fetch_assoc()['count'];
    $check_stmt->close();
    
    if ($product_count > 0) {
        $error = "Cannot delete subcategory. It has $product_count associated products.";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM subcategory WHERE subcategory_id = ?");
        $delete_stmt->bind_param('i', $subcategory_id);
        if ($delete_stmt->execute()) {
            $success = 'Subcategory deleted successfully!';
        } else {
            $error = 'Error deleting subcategory: ' . $conn->error;
        }
        $delete_stmt->close();
    }
}

// Get all categories with subcategory count and product count
$categories_query = "
    SELECT c.*, 
           COUNT(DISTINCT s.subcategory_id) as subcategory_count,
           COUNT(DISTINCT p.product_id) as product_count
    FROM category c
    LEFT JOIN subcategory s ON c.category_id = s.category_id
    LEFT JOIN product p ON s.subcategory_id = p.subcategory_id
    GROUP BY c.category_id
    ORDER BY c.category_name
";
$categories = $conn->query($categories_query);

// Get all subcategories with category names and product count
$subcategories_query = "
    SELECT s.*, c.category_name,
           COUNT(p.product_id) as product_count
    FROM subcategory s
    JOIN category c ON s.category_id = c.category_id
    LEFT JOIN product p ON s.subcategory_id = p.subcategory_id
    GROUP BY s.subcategory_id
    ORDER BY c.category_name, s.subcategory_name
";
$subcategories = $conn->query($subcategories_query);

// Get categories for dropdown
$categories_for_dropdown = $conn->query('SELECT * FROM category ORDER BY category_name');

// Get category for editing if requested
$edit_category = null;
if (isset($_GET['edit_category']) && is_numeric($_GET['edit_category'])) {
    $category_id = intval($_GET['edit_category']);
    $stmt = $conn->prepare("SELECT * FROM category WHERE category_id = ?");
    $stmt->bind_param('i', $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_category = $result->fetch_assoc();
    }
    $stmt->close();
}

// Get subcategory for editing if requested
$edit_subcategory = null;
if (isset($_GET['edit_subcategory']) && is_numeric($_GET['edit_subcategory'])) {
    $subcategory_id = intval($_GET['edit_subcategory']);
    $stmt = $conn->prepare("SELECT s.*, c.category_name FROM subcategory s JOIN category c ON s.category_id = c.category_id WHERE s.subcategory_id = ?");
    $stmt->bind_param('i', $subcategory_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_subcategory = $result->fetch_assoc();
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Velvet Vogue Admin</title>
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
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: #fff;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }

        .page-title {
            font-size: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
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

        /* Forms */
        .forms-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .form-card {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .form-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: #3498db;
            color: #fff;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #27ae60;
            color: #fff;
        }

        .btn-success:hover {
            background: #229954;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #e74c3c;
            color: #fff;
            font-size: 12px;
            padding: 6px 12px;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-warning {
            background: #f39c12;
            color: #fff;
            font-size: 12px;
            padding: 6px 12px;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .btn-secondary {
            background: #95a5a6;
            color: #fff;
            font-size: 12px;
            padding: 6px 12px;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        /* Tables */
        .table-container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
        }

        .table-title {
            font-size: 1.3rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .table th,
        .table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Column width standardization for both tables */
        .table th:nth-child(1),
        .table td:nth-child(1) {
            width: 30%;
        }

        .table th:nth-child(2),
        .table td:nth-child(2) {
            width: 25%;
        }

        .table th:nth-child(3),
        .table td:nth-child(3) {
            width: 20%;
        }

        .table th:nth-child(4),
        .table td:nth-child(4) {
            width: 25%;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
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

            .forms-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .table-container {
                overflow-x: auto;
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
                    <li><a href="category.php" class="active">
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
                <i class="fas fa-tags"></i> Category Management
            </h1>
            <p>Organize your products with categories and subcategories</p>
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

        <!-- Add/Edit Forms -->
        <div class="forms-container">
            <!-- Add/Edit Category Form -->
            <div class="form-card">
                <?php if ($edit_category): ?>
                    <h2 class="form-title">
                        <i class="fas fa-edit"></i> Edit Category
                    </h2>
                    <form method="POST" action="">
                        <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
                        <div class="form-group">
                            <label for="category_name">Category Name</label>
                            <input type="text" id="category_name" name="category_name" 
                                   value="<?php echo htmlspecialchars($edit_category['category_name']); ?>"
                                   placeholder="Enter category name..." required>
                        </div>
                        <div class="action-buttons">
                            <button type="submit" name="edit_category" class="btn btn-success">
                                <i class="fas fa-save"></i> Update Category
                            </button>
                            <a href="category.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                <?php else: ?>
                    <h2 class="form-title">
                        <i class="fas fa-plus-circle"></i> Add New Category
                    </h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="category_name">Category Name</label>
                            <input type="text" id="category_name" name="category_name" 
                                   placeholder="Enter category name..." required>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Category
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Add/Edit Subcategory Form -->
            <div class="form-card">
                <?php if ($edit_subcategory): ?>
                    <h2 class="form-title">
                        <i class="fas fa-edit"></i> Edit Subcategory
                    </h2>
                    <form method="POST" action="">
                        <input type="hidden" name="subcategory_id" value="<?php echo $edit_subcategory['subcategory_id']; ?>">
                        <div class="form-group">
                            <label for="category_id">Parent Category</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select a category...</option>
                                <?php foreach ($categories_for_dropdown as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"
                                            <?php echo ($cat['category_id'] == $edit_subcategory['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="subcategory_name">Subcategory Name</label>
                            <input type="text" id="subcategory_name" name="subcategory_name" 
                                   value="<?php echo htmlspecialchars($edit_subcategory['subcategory_name']); ?>"
                                   placeholder="Enter subcategory name..." required>
                        </div>
                        <div class="action-buttons">
                            <button type="submit" name="edit_subcategory" class="btn btn-success">
                                <i class="fas fa-save"></i> Update Subcategory
                            </button>
                            <a href="category.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                <?php else: ?>
                    <h2 class="form-title">
                        <i class="fas fa-plus-circle"></i> Add New Subcategory
                    </h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="category_id">Parent Category</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select a category...</option>
                                <?php foreach ($categories_for_dropdown as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>">
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="subcategory_name">Subcategory Name</label>
                            <input type="text" id="subcategory_name" name="subcategory_name" 
                                   placeholder="Enter subcategory name..." required>
                        </div>
                        <button type="submit" name="add_subcategory" class="btn btn-success">
                            <i class="fas fa-save"></i> Add Subcategory
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="fas fa-list"></i> Categories
                </h2>
            </div>
            
            <?php if ($categories && $categories->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Subcategories</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($category = $categories->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $category['subcategory_count']; ?> subcategories
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo $category['product_count']; ?> products
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit_category=<?php echo $category['category_id']; ?>" 
                                           class="btn btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($category['product_count'] == 0): ?>
                                            <a href="?delete_category=<?php echo $category['category_id']; ?>" 
                                               class="btn btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this category and all its subcategories?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #6c757d; font-size: 12px;">
                                                Cannot delete (has products)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-tags"></i>
                    <h3>No Categories Found</h3>
                    <p>Start by adding your first category above.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Subcategories Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="fas fa-layer-group"></i> Subcategories
                </h2>
            </div>
            
            <?php if ($subcategories && $subcategories->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Subcategory Name</th>
                            <th>Parent Category</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($subcategory = $subcategories->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($subcategory['subcategory_name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($subcategory['category_name']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo $subcategory['product_count']; ?> products
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit_subcategory=<?php echo $subcategory['subcategory_id']; ?>" 
                                           class="btn btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($subcategory['product_count'] == 0): ?>
                                            <a href="?delete_subcategory=<?php echo $subcategory['subcategory_id']; ?>" 
                                               class="btn btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this subcategory?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #6c757d; font-size: 12px;">
                                                Cannot delete (has products)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-layer-group"></i>
                    <h3>No Subcategories Found</h3>
                    <p>Add categories first, then create subcategories for them.</p>
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
