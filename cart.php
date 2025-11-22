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

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Handle remove item from cart
if (isset($_POST['remove_item'])) {
    $cart_id = intval($_POST['cart_id']);
    $delete_query = "DELETE FROM cart WHERE cart_id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('ii', $cart_id, $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();
}

// Handle update quantity
if (isset($_POST['update_quantity'])) {
    $cart_id = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($quantity <= 0) {
        // Remove item if quantity is 0 or less
        $delete_query = "DELETE FROM cart WHERE cart_id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param('ii', $cart_id, $user_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    } else {
        // Update quantity
        $update_query = "UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param('iii', $quantity, $cart_id, $user_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
}

// Handle clear cart
if (isset($_POST['clear_cart'])) {
    $clear_query = "DELETE FROM cart WHERE user_id = ?";
    $clear_stmt = $conn->prepare($clear_query);
    $clear_stmt->bind_param('i', $user_id);
    $clear_stmt->execute();
    $clear_stmt->close();
}

// Fetch cart items from database
$cart_query = "SELECT c.cart_id, c.product_id, c.quantity, c.price, c.created_at, p.product_name, p.image_path 
               FROM cart c 
               JOIN product p ON c.product_id = p.product_id 
               WHERE c.user_id = ? AND p.active = 1
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

// Calculate cart totals
$subtotal = 0;
$item_count = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $item_count += $item['quantity'];
}
$tax = $subtotal * 0.1; // 10% tax
$shipping = $subtotal > 0 ? 5.99 : 0; // $5.99 shipping if cart not empty
$total = $subtotal + $tax + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Velvet Vogue</title>
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
        
        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .cart-header h1 {
            color: #2c4c75;
            font-size: 2.2rem;
            font-weight: 600;
        }
        
        .cart-content {
            display: flex;
            gap: 30px;
        }
        
        .cart-items {
            flex: 3;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 30px;
        }
        
        .cart-summary {
            flex: 1;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 30px;
            height: fit-content;
        }
        
        .summary-title {
            color: #2c4c75;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px 0;
        }
        
        .summary-row:not(:last-child) {
            border-bottom: 1px dashed #f0f0f0;
        }
        
        .summary-label {
            color: #666;
            font-size: 1rem;
        }
        
        .summary-value {
            color: #333;
            font-weight: 500;
            font-size: 1rem;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            margin: 25px 0;
            padding: 20px 0;
            border-top: 2px solid #eee;
            border-bottom: 2px solid #eee;
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c4c75;
        }
        
        .checkout-btn {
            width: 100%;
            padding: 16px;
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
            margin-top: 20px;
        }
        
        .checkout-btn:hover {
            background: linear-gradient(135deg, #1a365d, #0099cc);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(44, 76, 117, 0.4);
        }
        
        .cart-item {
            display: flex;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            flex: 0 0 120px;
        }
        
        .item-image img {
            width: 100%;
            border-radius: 8px;
        }
        
        .item-details {
            flex: 2;
        }
        
        .item-details h3 {
            color: #2c4c75;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .item-price {
            color: #e67e22;
            font-weight: 600;
            font-size: 1.2rem;
            margin: 10px 0;
        }
        
        .item-actions {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
        }
        
        .item-actions form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .quantity-btn {
            width: 35px;
            height: 35px;
            background: #f5f5f5;
            border: none;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .quantity-btn:hover {
            background: #e0e0e0;
        }
        
        .quantity-input {
            width: 40px;
            height: 35px;
            border: none;
            text-align: center;
            font-size: 14px;
            outline: none;
        }
        
        .update-btn {
            padding: 8px 15px;
            background: #2196f3;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .update-btn:hover {
            background: #1976d2;
        }
        
        .remove-btn {
            padding: 8px 15px;
            background: #ff3b3b;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .remove-btn:hover {
            background: #d32f2f;
        }
        
        .empty-cart {
            text-align: center;
            padding: 80px 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            max-width: 2000px;
            margin: 40px auto;
        }
        
        .empty-cart-icon {
            font-size: 80px;
            color: #e0e0e0;
            margin-bottom: 30px;
            background: #f8f9fa;
            width: 160px;
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 30px;
            border: 1px dashed #e0e0e0;
        }
        
        .empty-cart h2 {
            color: #2c4c75;
            margin-bottom: 20px;
            font-size: 2rem;
            font-weight: 600;
        }
        
        .empty-cart p {
            color: #666;
            margin-bottom: 35px;
            font-size: 1.1rem;
            line-height: 1.6;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .continue-shopping {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #2c4c75, #00bfff);
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(44, 76, 117, 0.3);
        }
        
        .continue-shopping:hover {
            background: linear-gradient(135deg, #1a365d, #0099cc);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(44, 76, 117, 0.4);
        }
        
        .continue-shopping i {
            margin-right: 10px;
        }
        
        .suggested-products {
            margin-top: 60px;
            text-align: center;
        }
        
        .suggested-products h3 {
            color: #2c4c75;
            font-size: 1.5rem;
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        .suggested-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .suggested-product {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .suggested-product:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .suggested-image {
            width: 100%;
            height: 180px;
            margin-bottom: 15px;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }
        
        .suggested-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .suggested-title {
            margin: 15px 0 10px 0;
            color: #2c4c75;
            font-size: 1.1rem;
            font-weight: 600;
            line-height: 1.3;
            min-height: 2.6rem;
        }
        
        .suggested-price {
            color: #e67e22;
            font-weight: bold;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }
        
        .add-to-cart-small {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .add-to-cart-small:hover {
            background: linear-gradient(135deg, #2980b9, #1f6391);
            transform: translateY(-2px);
        }
        
        .clear-cart-btn {
            width: 100%;
            padding: 12px;
            background: #ff3b3b;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }
        
        .clear-cart-btn:hover {
            background: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.3);
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
        
        @media (max-width: 768px) {
            .cart-content {
                flex-direction: column;
            }
            
            .cart-item {
                flex-direction: column;
            }
            
            .item-image {
                flex: 0 0 auto;
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
            <input type="text" placeholder="Search...">
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
                if ($item_count > 0) {
                    echo '<span class="cart-count">' . $item_count . '</span>';
                }
                echo '</a>';
            } else {
                echo '<a href="login.php">Login</a>';
            }
            ?>
        </nav>
    </div>
    <!-- End of Header Section -->
    
    <script src="indexjs.js"></script>
    
    <div class="cart-container">
        <div class="cart-header">
            <h1>Your Shopping Cart</h1>
            <?php if (!empty($cart_items)): ?>
            <form method="post">
                <button type="submit" name="clear_cart" class="clear-cart-btn">Clear Cart</button>
            </form>
            <?php endif; ?>
        </div>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2>Your Shopping Cart is Empty</h2>
                <p>Looks like you haven't added any items to your cart yet. Discover our amazing products and find something you love!</p>
                <a href="products.php" class="continue-shopping"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
            </div>
            
            <!-- Suggested Products Section -->
            <div class="suggested-products">
                <h3>You Might Like These Products</h3>
                <div class="suggested-grid">
                    <?php
                    // Fetch some random products to suggest
                    $suggested_query = "SELECT product_id, product_name, price, image_path FROM product ORDER BY RAND() LIMIT 4";
                    $suggested_result = $conn->query($suggested_query);
                    if ($suggested_result && $suggested_result->num_rows > 0):
                        while($suggested = $suggested_result->fetch_assoc()):
                    ?>
                    <div class="suggested-product" onclick="window.location.href='product_detail.php?id=<?php echo $suggested['product_id']; ?>'">
                        <div class="suggested-image">
                            <?php if (!empty($suggested['image_path']) && file_exists(__DIR__ . '/' . $suggested['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($suggested['image_path']); ?>" alt="<?php echo htmlspecialchars($suggested['product_name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-image" style="font-size:40px;color:#bbb;"></i>
                            <?php endif; ?>
                        </div>
                        <h4 class="suggested-title"><?php echo htmlspecialchars($suggested['product_name']); ?></h4>
                        <div class="suggested-price">$<?php echo number_format($suggested['price'], 2); ?></div>
                        <a href="product_detail.php?id=<?php echo $suggested['product_id']; ?>" class="add-to-cart-small" onclick="event.stopPropagation();">View Product</a>
                    </div>
                    <?php 
                        endwhile;
                        else:
                    ?>
                    <p>No products available at the moment.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <img src="<?php echo $item['image_path']; ?>" alt="<?php echo $item['product_name']; ?>">
                            </div>
                            <div class="item-details">
                                <h3><?php echo $item['product_name']; ?></h3>
                                <p class="item-price">$<?php echo number_format($item['price'], 2); ?></p>
                                <div class="item-actions">
                                    <form method="post">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                        <div class="quantity-selector">
                                            <button type="button" class="quantity-btn" onclick="decreaseQuantity(this)">-</button>
                                            <input type="number" name="quantity" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="0">
                                            <button type="button" class="quantity-btn" onclick="increaseQuantity(this)">+</button>
                                        </div>
                                        <button type="submit" name="update_quantity" class="update-btn">Update</button>
                                    </form>
                                    <form method="post">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                        <button type="submit" name="remove_item" class="remove-btn">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="cart-summary">
                    <h2 class="summary-title">Order Summary</h2>
                    <div class="summary-row">
                        <span class="summary-label">Subtotal:</span>
                        <span class="summary-value">$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Tax (10%):</span>
                        <span class="summary-value">$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Shipping:</span>
                        <span class="summary-value">$<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="summary-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <form method="post" action="checkout.php">
                        <button type="submit" class="checkout-btn" onclick="return validateCheckout()">Proceed to Checkout</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function increaseQuantity(button) {
            const input = button.previousElementSibling;
            input.value = parseInt(input.value) + 1;
        }

        function decreaseQuantity(button) {
            const input = button.nextElementSibling;
            input.value = Math.max(0, parseInt(input.value) - 1);
        }
        
        // Function to validate checkout
        function validateCheckout() {
            // Check if cart is empty
            const cartItems = document.querySelectorAll('.cart-item');
            if (cartItems.length === 0) {
                alert('Your cart is empty. Add some items before checkout.');
                return false;
            }
            
            // Confirm checkout
            return confirm('Are you sure you want to proceed to checkout?');
        }
    </script>
</body>
</html>
