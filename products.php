<?php
session_start();
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'velvetvogue';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle add to cart action from products page
if (isset($_POST['action']) && $_POST['action'] == 'add_to_cart' && isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
    $product_id = intval($_POST['product_id']);
    $quantity = 1; // Default quantity
    
    // Validate product exists and get price
    $query = 'SELECT product_id, product_name, price FROM product WHERE product_id = ?';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $price = $product['price'];
        
        // Check if item already exists in cart
        $check_query = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param('ii', $user_id, $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing cart item
            $existing_item = $check_result->fetch_assoc();
            $new_quantity = $existing_item['quantity'] + $quantity;
            $update_query = "UPDATE cart SET quantity = ?, price = ? WHERE cart_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('idi', $new_quantity, $price, $existing_item['cart_id']);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Insert new cart item
            $insert_query = "INSERT INTO cart (user_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param('iiid', $user_id, $product_id, $quantity, $price);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
        
        $check_stmt->close();
    }
    $stmt->close();
    
    // Refresh the page to show updated cart count
    header('Location: ' . $_SERVER['PHP_SELF'] . (isset($_GET['category_id']) ? '?category_id=' . $_GET['category_id'] : (isset($_GET['subcategory_id']) ? '?subcategory_id=' . $_GET['subcategory_id'] : '')));
    exit();
}

// Fetch all categories
$categories = $conn->query('SELECT * FROM category ORDER BY category_name');
if (!$categories) {
    $categories = array(); // Initialize as empty array if query fails
}

// Fetch all subcategories
$subcategories = $conn->query('SELECT * FROM subcategory ORDER BY subcategory_name');
if (!$subcategories) {
    $subcategories = array(); // Initialize as empty array if query fails
}

// Get cart item count for logged in user
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
    $cart_query = "SELECT SUM(quantity) as total_items FROM cart WHERE user_id = ?";
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->bind_param('i', $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    if ($cart_row = $cart_result->fetch_assoc()) {
        $cart_count = $cart_row['total_items'] ?? 0;
    }
    $cart_stmt->close();
}

// Function to check if a product is in user's wishlist
function isProductInWishlist($conn, $user_id, $product_id) {
    $query = "SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $in_wishlist = $result->num_rows > 0;
    $stmt->close();
    return $in_wishlist;
}

// Filtering logic
$where = '';
if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
    $cat_id = intval($_GET['category_id']);
    $where = "WHERE c.category_id = $cat_id";
} elseif (isset($_GET['subcategory_id']) && is_numeric($_GET['subcategory_id'])) {
    $subcat_id = intval($_GET['subcategory_id']);
    $where = "WHERE s.subcategory_id = $subcat_id";
}

// Add active filter to show only active products
if ($where == '') {
    $where = "WHERE p.active = 1";
} else {
    $where .= " AND p.active = 1";
}

$sql = "SELECT p.*, c.category_name, s.subcategory_name FROM product p 
        LEFT JOIN subcategory s ON p.subcategory_id = s.subcategory_id 
        LEFT JOIN category c ON s.category_id = c.category_id 
        $where
        ORDER BY p.product_id DESC";
$products = $conn->query($sql);
if (!$products) {
    $products = array(); // Initialize as empty array if query fails
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Velvet Vogue</title>
    <link rel="icon" href="img/logo2.png">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
    <style>
        body {
            background: #fdfdfd;
            padding-top: 170px;
            margin: 0;
            padding-bottom: 50px;
            font-family: 'Poppins', sans-serif;
        }
        
        .products-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
        }
        
        .products-header {
            text-align: left;
            margin: 30px 0 20px 0;
            color: #2c4c75;
            font-size: 2.2rem;
            font-weight: 600;
        }
        
        .products-content {
            display: flex;
            align-items: flex-start;
            gap: 30px;
            width: 100%;
        }

        .sidebar {
            width: 280px;
            min-width: 250px;
            max-width: 320px;
            flex-shrink: 0;
        }
        
        .filter-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .filter-card:hover {
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }
        
        .filter-title {
            margin-bottom: 18px;
            color: #2c4c75;
            font-size: 1.2rem;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }
        
        .filter-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(to right, #2c4c75, #00bfff);
            border-radius: 2px;
        }
        
        .filter-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            width: 100%;
            row-gap: 10px;
            column-gap: 8px;
            justify-content: flex-start;
            align-items: flex-start;
            margin-bottom: 8px;
            min-height: auto;
        }
        
        .filter-tag {
            padding: 8px 14px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
            max-width: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            box-sizing: border-box;
            flex: 0 0 auto;
            min-width: 0;
        }
        
        .filter-tag.all {
            background: #2196f3;
            color: #fff;
        }
        
        .filter-tag.all:hover {
            background: #1976d2;
            transform: translateY(-2px);
        }
        
        .filter-tag.category {
            background: #e3f2fd;
            color: #2196f3;
        }
        
        .filter-tag.category:hover {
            background: #bbdefb;
            transform: translateY(-2px);
        }
        
        .filter-tag.category.active {
            background: #2196f3;
            color: #fff;
        }
        
        .filter-tag.subcategory {
            background: #f4f4f4;
            color: #666;
        }
        
        .filter-tag.subcategory:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }
        
        .filter-tag.subcategory.active {
            background: #2196f3;
            color: #fff;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            flex: 1;
            width: 100%;
            justify-items: center;
            align-items: start;
        }

        /* CSS Grid layout - Primary method */
        .products-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)) !important;
            gap: 25px !important;
            flex: 1;
            width: 100% !important;
            min-width: 0;
            justify-items: center;
            align-items: start;
            margin: 0;
            padding: 0;
        }

        /* Force grid display for all browsers */
        .products-grid {
            display: -ms-grid !important;
            display: grid !important;
        }

        /* Alternative flexbox approach if grid fails */
        .products-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            gap: 30px;
        }

        /* Re-apply grid for modern browsers */
        @supports (display: grid) {
            .products-grid {
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
                gap: 30px !important;
                flex-wrap: nowrap !important;
                justify-content: unset !important;
            }
        }
        
        .product-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            padding: 25px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-width: 0;
            width: 100%;
            max-width: 350px;
            min-height: 450px;
            box-sizing: border-box;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }
        
        .wishlist-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 20px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
            background: rgba(255, 255, 255, 0.9);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .wishlist-icon:hover {
            color: #ff6b6b;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .wishlist-icon.added {
            color: #ff6b6b;
        }
        
        .product-image {
            width: 100%;
            height: 220px;
            margin-bottom: 20px;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            position: relative;
        }
        
        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.08);
        }
        
        .no-image {
            width: 100%;
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f4f4;
            border-radius: 12px;
        }
        
        .product-category {
            color: #2196f3;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .product-title {
            margin: 0 0 12px 0;
            color: #2c4c75;
            font-size: 1.15rem;
            font-weight: 600;
            line-height: 1.4;
            min-height: 2.8rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 18px;
            min-height: 42px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-grow: 1;
        }
        
        .product-price {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        
        .price {
            color: #e67e22;
            font-weight: bold;
            font-size: 1.4rem;
        }
        
        .old-price {
            color: #999;
            text-decoration: line-through;
            font-size: 1rem;
        }
        
        .discount {
            color: #e74c3c;
            font-size: 0.9rem;
            font-weight: 600;
            background: rgba(231, 76, 60, 0.1);
            padding: 3px 8px;
            border-radius: 12px;
        }
        
        .product-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .view-details-btn {
            display: inline-block;
            padding: 14px 20px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            border-radius: 30px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            border: none;
            position: relative;
            z-index: 10;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.2);
        }
        
        .view-details-btn:hover {
            background: linear-gradient(135deg, #2980b9, #1f6391);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.3);
        }
        
        .add-to-cart-btn {
            display: inline-block;
            padding: 14px 20px;
            background: linear-gradient(135deg, #2c4c75, #00bfff);
            color: #fff;
            border-radius: 30px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            border: none;
            position: relative;
            z-index: 10;
            box-shadow: 0 4px 10px rgba(44, 76, 117, 0.2);
        }
        
        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #1a365d, #0099cc);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(44, 76, 117, 0.3);
        }
        
        .no-products {
            text-align: center;
            padding: 80px 40px;
            color: #666;
            font-size: 1.1rem;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            grid-column: 1 / -1;
            margin-top: 20px;
            width: 100%;
        }
        
        .no-products i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 25px;
        }
        
        .no-products p {
            font-size: 1.2rem;
            margin-bottom: 25px;
            color: #777;
        }
        
        .no-products a {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, #2c4c75, #00bfff);
            color: #fff;
            border-radius: 30px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(44, 76, 117, 0.2);
        }
        
        .no-products a:hover {
            background: linear-gradient(135deg, #1a365d, #0099cc);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(44, 76, 117, 0.3);
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: #4CAF50;
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            display: none;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .cart-icon {
            position: relative;
            display: inline-block;
            margin-right: 20px;
            color: #1b1818;
            text-decoration: none;
            font-size: 18px;
        }
        
        .cart-icon:hover {
            color: #00bfff;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        @media (max-width: 1200px) {
            .sidebar {
                width: 260px;
                min-width: 230px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 240px;
                min-width: 200px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 18px;
            }

            .product-card {
                padding: 20px;
            }

            .product-image {
                height: 180px;
            }
        }
        
        /* Mobile Responsive - Tablets and Below (max-width: 965px) */
        @media (max-width: 965px) {
            body {
                padding-top: 180px;
            }

            .products-content {
                flex-direction: column;
                gap: 20px;
            }

            .sidebar {
                width: 100%;
                min-width: auto;
                max-width: none;
                order: 1;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 25px;
                order: 2;
            }

            .filter-tags {
                justify-content: flex-start;
                gap: 6px;
                row-gap: 8px;
                align-items: center;
            }

            .filter-tag {
                font-size: 12px;
                padding: 6px 12px;
                flex: 0 0 auto;
                max-width: calc(50% - 3px);
            }

            .filter-card {
                padding: 18px;
            }

            .products-header {
                font-size: 1.8rem;
                margin: 20px 0 15px 0;
            }
        }
        
        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .product-card {
                padding: 20px 15px;
            }
            
            .product-image {
                height: 160px;
            }
        }
        
        /* Additional e-commerce enhancements */
        .product-rating {
            color: #ff9800;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #777;
        }
        
        .product-badge {
            background: #e3f2fd;
            color: #2196f3;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.8rem;
        }
        
        .quick-view {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #2c4c75;
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #eee;
        }
        
        .product-card:hover .quick-view {
            opacity: 1;
            transform: translateX(-50%) translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        
        <div class="header-top">
            <div class="logo">
                <a href="index.php">
                    <img src="img/logo2.png" alt="Velvet Vogue Logo" width="200px">
                </a>
            </div>

            <div class="header-right">
                <!-- Mobile Menu Toggle -->
                <div class="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
        
        <div class="search-bar">
            <form action="search.php" method="GET">
                <input type="text" name="q" placeholder="Search products..." required>
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        
        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="index.php#brand">About Us</a>
            <a href="products.php" class="active">Products</a>
            <a href="contactus.php">Contact Us</a>
            <?php
            if (isset($_SESSION['user_username'])) {
                echo '<a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>';
                if ($cart_count > 0) {
                    echo '<span class="cart-count">' . $cart_count . '</span>';
                }
                echo '</a>';
                
                echo '<div class="modern-user-profile">
                        <a href="#" class="modern-profile-link">
                          <i class="fas fa-user-circle"></i>
                          <span class="username">' . htmlspecialchars($_SESSION['user_username']) . '</span>
                        </a>
                        <div class="modern-profile-dropdown">
                          <div class="dropdown-header">
                            Welcome back!
                            <div class="welcome-text">' . htmlspecialchars($_SESSION['user_username']) . '</div>
                          </div>
                          <a href="edit_profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
                          <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                      </div>';
            } else {
                echo '<a href="login.php">Login</a>';
            }
            ?>
        </nav>
    </div>
    
    <div class="products-container">
        <div id="notification" class="notification">Product added to cart successfully!</div>
        <h2 class="products-header">Our Products</h2>
        
        <div class="products-content">
            <!-- Sidebar for categories/subcategories -->
            <div class="sidebar">
                <div class="filter-card">
                    <h4 class="filter-title">Categories</h4>
                    <div class="filter-tags">
                        <a href="products.php" class="filter-tag all <?php echo (!isset($_GET['category_id']) && !isset($_GET['subcategory_id'])) ? 'active' : ''; ?>">All Products</a>
                        <?php if (!empty($categories) && $categories->num_rows > 0): ?>
                            <?php while($cat = $categories->fetch_assoc()): ?>
                                <a href="products.php?category_id=<?php echo $cat['category_id']; ?>" 
                                   class="filter-tag category <?php echo (isset($_GET['category_id']) && $_GET['category_id'] == $cat['category_id']) ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </a>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="filter-card">
                    <h4 class="filter-title">Subcategories</h4>
                    <div class="filter-tags">
                        <?php if (!empty($subcategories) && $subcategories->num_rows > 0): ?>
                            <?php while($subcat = $subcategories->fetch_assoc()): ?>
                                <a href="products.php?subcategory_id=<?php echo $subcat['subcategory_id']; ?>" 
                                   class="filter-tag subcategory <?php echo (isset($_GET['subcategory_id']) && $_GET['subcategory_id'] == $subcat['subcategory_id']) ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($subcat['subcategory_name']); ?>
                                </a>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Product grid -->
            <div class="products-grid">
                <?php if (!empty($products) && $products->num_rows > 0): ?>
                    <?php while($row = $products->fetch_assoc()): ?>
                    <div class="product-card" onclick="window.location.href='product_detail.php?id=<?php echo $row['product_id']; ?>'">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php
                            // Get user_id for wishlist check
                            $user_id = intval($_SESSION['user_id']);
                            ?>
                        <div class="wishlist-icon <?php echo (isProductInWishlist($conn, $user_id, $row['product_id']) ? 'added' : ''); ?>" data-product-id="<?php echo $row['product_id']; ?>">
                            <i class="<?php echo (isProductInWishlist($conn, $user_id, $row['product_id']) ? 'fas' : 'far'); ?> fa-heart"></i>
                        </div>
                        <?php endif; ?>
                        <div class="product-image">
                            <?php if (!empty($row['image_path']) && file_exists(__DIR__ . '/' . $row['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image" style="font-size:48px;color:#bbb;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($row['category_name'])): ?>
                        <div class="product-category"><?php echo htmlspecialchars($row['category_name']); ?></div>
                        <?php endif; ?>
                        
                        <h3 class="product-title" title="<?php echo htmlspecialchars($row['product_name']); ?>">
                            <?php echo htmlspecialchars($row['product_name']); ?>
                        </h3>
                        
                        <div class="product-meta">
                            <div class="product-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                                <span>(128)</span>
                            </div>
                            <div class="product-badge">New</div>
                        </div>
                        
                        <div class="product-description">
                            <?php echo nl2br(htmlspecialchars($row['description'])); ?>
                        </div>
                        
                        <div class="product-price">
                            <span class="price">$<?php echo number_format($row['price'], 2); ?></span>
                            <?php 
                                $old_price = $row['price'] * 1.25; // 25% higher as old price
                                $discount = round(100 * ($old_price - $row['price']) / $old_price);
                            ?>
                            <span class="old-price">$<?php echo number_format($old_price, 2); ?></span>
                            <span class="discount">-<?php echo $discount; ?>%</span>
                        </div>
                        
                        <div class="product-actions">
                            <a class="view-details-btn" href="product_detail.php?id=<?php echo $row['product_id']; ?>" 
                               onclick="event.stopPropagation();">View Details</a>
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="post" style="display:inline;" onclick="event.stopPropagation();">
                                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                <input type="hidden" name="action" value="add_to_cart">
                                <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <div class="quick-view">Quick View</div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fas fa-shopping-bag"></i>
                        <p>No products found in this category.</p>
                        <a href="products.php">View All Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="indexjs.js"></script>
    <script>
        // Show notification if product was added to cart
        <?php if (isset($_POST['action']) && $_POST['action'] == 'add_to_cart'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');
            notification.style.display = 'block';
            setTimeout(function() {
                notification.style.display = 'none';
            }, 3000);
        });
        <?php endif; ?>
        
        // Wishlist functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event to all wishlist icons
            const wishlistIcons = document.querySelectorAll('.wishlist-icon');
            
            wishlistIcons.forEach(icon => {
                icon.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent card click
                    
                    const productId = this.getAttribute('data-product-id');
                    const heartIcon = this.querySelector('i');
                    
                    // Toggle wishlist status
                    if (heartIcon.classList.contains('far')) {
                        // Add to wishlist
                        heartIcon.classList.remove('far');
                        heartIcon.classList.add('fas');
                        this.classList.add('added');
                        
                        // Send AJAX request to add to wishlist
                        fetch('wishlist_handler.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=add_to_wishlist&product_id=' + productId
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Product added to wishlist!');
                            } else {
                                showNotification('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            showNotification('Error adding to wishlist');
                        });
                    } else {
                        // Remove from wishlist
                        heartIcon.classList.remove('fas');
                        heartIcon.classList.add('far');
                        this.classList.remove('added');
                        
                        // Send AJAX request to remove from wishlist
                        fetch('wishlist_handler.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=remove_from_wishlist&product_id=' + productId
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Product removed from wishlist');
                            } else {
                                showNotification('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            showNotification('Error removing from wishlist');
                        });
                    }
                });
            });
            
            // Function to show notification
            function showNotification(message) {
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.textContent = message;
                notification.style.position = 'fixed';
                notification.style.top = '20px';
                notification.style.right = '20px';
                notification.style.padding = '15px 25px';
                notification.style.background = '#4CAF50';
                notification.style.color = 'white';
                notification.style.borderRadius = '8px';
                notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                notification.style.zIndex = '1000';
                notification.style.display = 'block';
                notification.style.animation = 'slideIn 0.3s ease';
                
                document.body.appendChild(notification);
                
                setTimeout(function() {
                    notification.style.opacity = '0';
                    setTimeout(function() {
                        document.body.removeChild(notification);
                    }, 300);
                }, 3000);
            }
        });
    </script>
</body>
</html>