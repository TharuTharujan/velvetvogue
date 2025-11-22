<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_username'])) {
    header('Location: login.html');
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

// Fetch wishlist items
$wishlist_query = "SELECT w.wishlist_id, w.created_at, p.product_id, p.product_name, p.price, p.image_path 
                   FROM wishlist w 
                   JOIN product p ON w.product_id = p.product_id 
                   WHERE w.user_id = ? 
                   ORDER BY w.created_at DESC";
$wishlist_stmt = $conn->prepare($wishlist_query);
$wishlist_stmt->bind_param('i', $user_id);
$wishlist_stmt->execute();
$wishlist_result = $wishlist_stmt->get_result();
$wishlist_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Velvet Vogue</title>
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

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .wishlist-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .wishlist-item {
            display: flex;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .wishlist-item:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .item-image {
            flex: 0 0 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }

        .item-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .item-image .no-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-image .no-image i {
            font-size: 24px;
            color: #ccc;
        }

        .item-details {
            flex: 1;
            padding: 15px;
        }

        .item-details h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
        }

        .item-price {
            color: var(--primary);
            font-weight: 600;
            margin: 5px 0;
        }

        .item-date {
            color: var(--gray);
            font-size: 12px;
        }

        .item-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 15px;
            background: #f8f9fa;
        }

        .no-content {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .no-content i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #e9ecef;
        }

        .no-content h3 {
            margin-bottom: 10px;
            color: var(--dark);
        }

        .no-content p {
            margin-bottom: 20px;
            color: var(--gray);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: none;
            align-items: center;
        }

        .alert i {
            margin-right: 12px;
            font-size: 20px;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid var(--danger);
            color: var(--danger);
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
            .wishlist-items {
                grid-template-columns: 1fr;
            }

            .wishlist-grid {
                grid-template-columns: 1fr;
            }

            .back-to-home {
                font-size: 14px;
                padding: 8px 15px;
            }

            .wishlist-header h1 {
                font-size: 1.5rem;
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
                    <li><a href="my_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                    <li><a href="wishlist.php" class="active"><i class="fas fa-heart"></i> Wishlist</a></li>
                    <li><a href="address_book.php"><i class="fas fa-map-marker-alt"></i> Address Book</a></li>
                    <li><a href="payment_methods.php"><i class="fas fa-credit-card"></i> Payment Methods</a></li>
                    <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                </ul>
            </div>

            <div class="account-content">
                <div class="account-card">
                    <div class="account-card-header">
                        <h2>My Wishlist</h2>
                    </div>
                    
                    <div class="alert alert-success" id="success-alert">
                        <i class="fas fa-check-circle"></i>
                        <span id="success-message"></span>
                    </div>
                    
                    <div class="alert alert-error" id="error-alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span id="error-message"></span>
                    </div>
                    
                    <?php if ($wishlist_result && $wishlist_result->num_rows > 0): ?>
                        <div class="wishlist-items">
                            <?php while($item = $wishlist_result->fetch_assoc()): ?>
                            <div class="wishlist-item" data-wishlist-id="<?php echo $item['wishlist_id']; ?>">
                                <div class="item-image">
                                    <?php if (!empty($item['image_path']) && file_exists(__DIR__ . '/' . $item['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                    <p class="item-price">$<?php echo number_format($item['price'], 2); ?></p>
                                    <p class="item-date">Added: <?php echo date('M j, Y', strtotime($item['created_at'])); ?></p>
                                </div>
                                <div class="item-actions">
                                    <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" class="btn btn-outline">View Details</a>
                                    <button class="btn btn-primary" onclick="addToCart(<?php echo $item['product_id']; ?>)">Add to Cart</button>
                                    <button class="btn btn-danger" onclick="removeFromWishlist(<?php echo $item['wishlist_id']; ?>)">Remove</button>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-content">
                            <i class="fas fa-heart"></i>
                            <h3>Your Wishlist is Empty</h3>
                            <p>Save items that you like for later by clicking the heart icon on product pages.</p>
                            <a href="products.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Function to show notification
        function showNotification(message, type = 'success') {
            const alert = document.getElementById(type === 'success' ? 'success-alert' : 'error-alert');
            const messageElement = document.getElementById(type === 'success' ? 'success-message' : 'error-message');
            
            messageElement.textContent = message;
            alert.style.display = 'flex';
            
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                    alert.style.opacity = '1';
                }, 300);
            }, 3000);
        }
        
        function addToCart(productId) {
            fetch('wishlist_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error adding to cart', 'error');
            });
        }
        
        function removeFromWishlist(wishlistId) {
            if (confirm('Are you sure you want to remove this item from your wishlist?')) {
                fetch('remove_from_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'wishlist_id=' + wishlistId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        // Remove the item from the DOM
                        const itemElement = document.querySelector(`[data-wishlist-id="${wishlistId}"]`);
                        if (itemElement) {
                            itemElement.remove();
                        }
                        // If no items left, show empty message
                        const wishlistItems = document.querySelector('.wishlist-items');
                        if (wishlistItems && wishlistItems.children.length === 0) {
                            wishlistItems.innerHTML = `
                                <div class="no-content" style="grid-column: 1 / -1; justify-self: center;">
                                    <i class="fas fa-heart"></i>
                                    <h3>Your Wishlist is Empty</h3>
                                    <p>Save items that you like for later by clicking the heart icon on product pages.</p>
                                    <a href="products.php" class="btn btn-primary">Start Shopping</a>
                                </div>
                            `;
                        }
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error removing from wishlist', 'error');
                });
            }
        }
    </script>
</body>
</html>