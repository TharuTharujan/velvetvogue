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

// Handle add to cart action
if (isset($_POST['add_to_cart']) || isset($_POST['buy_now'])) {
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login if user is not logged in
        header('Location: login.php');
        exit();
    }
    
    $user_id = intval($_SESSION['user_id']);
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    // Validate product exists and get price
    $query = 'SELECT product_id, product_name, price, image_path FROM product WHERE product_id = ?';
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
        
        // If buy now, redirect to cart page
        if (isset($_POST['buy_now'])) {
            header('Location: cart.php');
            exit();
        }
        
        // Refresh cart count after adding item
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
    $stmt->close();
}

$product = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = 'SELECT p.*, s.subcategory_name, c.category_name 
              FROM product p 
              JOIN subcategory s ON p.subcategory_id = s.subcategory_id
              JOIN category c ON s.category_id = c.category_id 
              WHERE p.product_id = ? AND p.active = 1';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - Velvet Vogue</title>
    <link rel="icon" href="img/logo2.png">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
    <script src="indexjs.js"></script>
    <style>
        body {
            background: #fdfdfd;
            padding-top: 170px;
            margin: 0;
            padding-bottom: 50px;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }
        
        .breadcrumb {
            max-width: 1200px;
            margin: 0 auto 20px;
            padding: 0 20px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #2196f3;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb span {
            color: #666;
        }
        
        .product-detail-container {
            max-width: 1200px;
            margin: 0 auto 40px;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            display: flex;
            gap: 40px;
            align-items: flex-start;
        }
        
        .product-detail-image {
            flex: 0 0 400px;
            position: relative;
        }
        
        .product-detail-image img {
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .product-detail-image img:hover {
            transform: scale(1.02);
        }
        
        .product-detail-info {
            flex: 1;
        }
        
        .product-detail-info h1 {
            color: #2c4c75;
            margin-bottom: 15px;
            font-size: 2.2rem;
            font-weight: 600;
        }
        
        .product-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .product-meta span {
            padding: 6px 15px;
            border-radius: 20px;
            background: #e3f2fd;
            color: #2196f3;
            font-weight: 500;
        }
        
        .product-price {
            margin: 25px 0;
        }
        
        .price {
            color: #e67e22;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .old-price {
            color: #999;
            text-decoration: line-through;
            font-size: 1.4rem;
            margin-left: 15px;
        }
        
        .discount {
            color: #e74c3c;
            font-size: 1.2rem;
            margin-left: 15px;
            font-weight: 600;
        }
        
        .product-description {
            color: #555;
            margin: 25px 0;
            line-height: 1.7;
            font-size: 16px;
        }
        
        .product-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            align-items: center;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            background: #f5f5f5;
            border: none;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .quantity-btn:hover {
            background: #e0e0e0;
        }
        
        .quantity-input {
            width: 50px;
            height: 40px;
            border: none;
            text-align: center;
            font-size: 16px;
            outline: none;
        }
        
        .add-to-cart-btn {
            padding: 14px 32px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #2980b9, #1f6391);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }
        
        .add-to-cart-btn:active {
            transform: translateY(0);
        }
        
        .buy-now-btn {
            padding: 14px 32px;
            background: linear-gradient(135deg, #2c4c75, #00bfff);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(44, 76, 117, 0.3);
        }
        
        .buy-now-btn:hover {
            background: linear-gradient(135deg, #1a365d, #0099cc);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(44, 76, 117, 0.4);
        }
        
        .login-required {
            background: #fffbe6;
            border: 1px solid #ffe58f;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        
        .login-required a {
            color: #1890ff;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-required a:hover {
            text-decoration: underline;
        }
        
        .product-tags {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .product-tags h3 {
            color: #2c4c75;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .tag {
            padding: 6px 15px;
            background: #f4f4f4;
            border-radius: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .not-found {
            max-width: 1200px;
            margin: 60px auto;
            text-align: center;
            padding: 50px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .not-found i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .not-found h2 {
            color: #2c4c75;
            margin-bottom: 15px;
        }
        
        .not-found p {
            color: #666;
            margin-bottom: 25px;
            font-size: 18px;
        }
        
        .back-to-products {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .back-to-products:hover {
            background: linear-gradient(135deg, #2980b9, #1f6391);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: #4CAF50;
            color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            display: none;
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
        
        @media (max-width: 965px) {
            body {
                padding-top: 180px;
                overflow-x: hidden;
            }

            .breadcrumb {
                padding: 0 15px;
                max-width: 100%;
                box-sizing: border-box;
            }

            .product-detail-container {
                flex-direction: column;
                padding: 20px;
                margin: 0 15px 40px;
                max-width: calc(100% - 30px);
                box-sizing: border-box;
            }
            
            .product-detail-image {
                flex: 0 0 auto;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }

            .product-detail-image img {
                max-width: 100%;
                height: auto;
            }
            
            .product-detail-info {
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }

            .product-detail-info h1 {
                font-size: 1.8rem;
            }

            .product-meta {
                max-width: 100%;
                flex-wrap: wrap;
            }

            .product-meta span {
                max-width: 100%;
                box-sizing: border-box;
            }
            
            .price {
                font-size: 2rem;
            }
            
            .product-actions {
                flex-direction: column;
                align-items: stretch;
                max-width: 100%;
            }

            .quantity-selector {
                max-width: 100%;
                box-sizing: border-box;
            }

            .add-to-cart-btn, 
            .buy-now-btn,
            .wishlist-btn {
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }

            .product-tags {
                max-width: 100%;
            }

            .tags {
                max-width: 100%;
            }

            .tag {
                max-width: 100%;
                box-sizing: border-box;
            }

            .not-found {
                margin: 60px 15px;
                max-width: calc(100% - 30px);
                box-sizing: border-box;
            }
        }

        @media (max-width: 485px) {
            body {
                padding-top: 160px;
                padding-bottom: 30px;
                padding-left: 0;
                padding-right: 0;
            }

            .breadcrumb {
                padding: 0 15px;
                font-size: 12px;
                margin-bottom: 15px;
                max-width: 100%;
                box-sizing: border-box;
            }

            .product-detail-container {
                padding: 15px;
                margin: 0 15px 20px;
                gap: 20px;
                border-radius: 8px;
                max-width: calc(100% - 30px);
                box-sizing: border-box;
            }

            .product-detail-image {
                flex: 0 0 auto;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }

            .product-detail-image img {
                border-radius: 8px;
                max-width: 100%;
                height: auto;
            }

            .product-detail-info h1 {
                font-size: 1.3rem;
                margin-bottom: 12px;
            }

            .product-meta {
                flex-direction: column;
                gap: 8px;
                margin-bottom: 15px;
                max-width: 100%;
            }

            .product-meta span {
                padding: 5px 12px;
                font-size: 13px;
                display: inline-block;
                width: fit-content;
                max-width: 100%;
                box-sizing: border-box;
            }

            .product-price {
                margin: 15px 0;
            }

            .price {
                font-size: 1.8rem;
                display: block;
                margin-bottom: 8px;
            }

            .old-price {
                font-size: 1.1rem;
                margin-left: 0;
                display: inline-block;
            }

            .discount {
                font-size: 1rem;
                margin-left: 10px;
            }

            .product-description {
                font-size: 14px;
                margin: 15px 0;
                line-height: 1.6;
            }

            .product-actions {
                flex-direction: column;
                gap: 10px;
                margin-top: 20px;
                align-items: stretch;
            }

            .quantity-selector {
                width: 100%;
                max-width: 100%;
                justify-content: center;
                box-sizing: border-box;
            }

            .quantity-btn {
                width: 45px;
                height: 45px;
                font-size: 20px;
            }

            .quantity-input {
                width: 60px;
                height: 45px;
                font-size: 18px;
            }

            .add-to-cart-btn,
            .buy-now-btn {
                width: 100%;
                max-width: 100%;
                padding: 14px 20px;
                font-size: 14px;
                box-sizing: border-box;
            }

            .login-required {
                padding: 15px;
                margin: 15px 0;
                font-size: 14px;
            }

            .product-tags {
                margin-top: 20px;
                padding-top: 15px;
            }

            .product-tags h3 {
                font-size: 1rem;
                margin-bottom: 12px;
            }

            .tags {
                gap: 8px;
            }

            .tag {
                padding: 5px 12px;
                font-size: 12px;
            }

            .not-found {
                margin: 30px 15px;
                padding: 30px 20px;
                border-radius: 8px;
                max-width: calc(100% - 30px);
                box-sizing: border-box;
            }

            .not-found i {
                font-size: 50px;
                margin-bottom: 15px;
            }

            .not-found h2 {
                font-size: 1.3rem;
                margin-bottom: 12px;
            }

            .not-found p {
                font-size: 14px;
                margin-bottom: 20px;
            }

            .back-to-products {
                padding: 12px 25px;
                font-size: 14px;
            }

            .notification {
                top: 10px;
                right: 10px;
                left: 10px;
                padding: 12px 15px;
                font-size: 14px;
                text-align: center;
            }
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
            <a href="products.php">Products</a>
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
    
    <!-- Notification -->
    <div id="notification" class="notification">Product added to cart successfully!</div>
    
    <?php if ($product): ?>
    <div class="breadcrumb">
        <a href="index.php">Home</a> <span> / </span>
        <a href="products.php">Products</a> <span> / </span>
        <span><?php echo htmlspecialchars($product['product_name']); ?></span>
    </div>
    
    <div class="product-detail-container">
        <div class="product-detail-image">
            <?php if ($product['image_path'] && file_exists(__DIR__ . '/' . $product['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
            <?php else: ?>
                <div style="background: #f4f4f4; border-radius: 12px; display: flex; align-items: center; justify-content: center; height: 400px;">
                    <i class="fas fa-image" style="font-size: 80px; color: #bbb;"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="product-detail-info">
            <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>
            
            <div class="product-meta">
                <span>Category: <?php echo htmlspecialchars($product['category_name']); ?></span>
                <span>Subcategory: <?php echo htmlspecialchars($product['subcategory_name']); ?></span>
            </div>
            
            <div class="product-price">
                <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                <?php 
                    $old_price = $product['price'] * 1.25; // 25% higher as old price
                    $discount = round(100 * ($old_price - $product['price']) / $old_price);
                ?>
                <span class="old-price">$<?php echo number_format($old_price, 2); ?></span>
                <span class="discount">-<?php echo $discount; ?>%</span>
            </div>
            
            <div class="product-description">
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="login-required">
                    <p>Please <a href="login.php">login</a> to add items to your cart.</p>
                </div>
            <?php else: ?>
                <form method="post" action="product_detail.php?id=<?php echo $product['product_id']; ?>">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <div class="product-actions">
                        <div class="quantity-selector">
                            <button class="quantity-btn" type="button" onclick="changeQuantity(-1)">-</button>
                            <input type="text" class="quantity-input" id="quantity" name="quantity" value="1" readonly>
                            <button class="quantity-btn" type="button" onclick="changeQuantity(1)">+</button>
                        </div>
                        <button type="submit" name="add_to_cart" class="add-to-cart-btn">Add to Cart</button>
                        <button type="submit" name="buy_now" class="buy-now-btn">Buy Now</button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="product-tags">
                <h3>Product Tags</h3>
                <div class="tags">
                    <span class="tag"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    <span class="tag"><?php echo htmlspecialchars($product['subcategory_name']); ?></span>
                    <span class="tag">Fashion</span>
                    <span class="tag">Trendy</span>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="not-found">
        <i class="fas fa-shopping-bag"></i>
        <h2>Product Not Found</h2>
        <p>We couldn't find the product you're looking for. It may have been removed or the link is incorrect.</p>
        <a href="products.php" class="back-to-products">Back to Products</a>
    </div>
    <?php endif; ?>
    
    <script>
        function changeQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            let quantity = parseInt(quantityInput.value);
            quantity += change;
            if (quantity < 1) quantity = 1;
            quantityInput.value = quantity;
        }
        
        // Show notification if product was added to cart
        <?php if (isset($_POST['add_to_cart']) || isset($_POST['buy_now'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');
            notification.style.display = 'block';
            setTimeout(function() {
                notification.style.display = 'none';
            }, 3000);
        });
        <?php endif; ?>
        

    </script>
</body>
</html>
