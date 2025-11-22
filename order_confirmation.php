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

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Velvet Vogue</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .confirmation-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 40px;
            text-align: center;
            margin-top: 40px;
        }

        .confirmation-icon {
            width: 100px;
            height: 100px;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }

        .confirmation-icon i {
            font-size: 50px;
            color: var(--success);
        }

        .confirmation-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .confirmation-message {
            font-size: 18px;
            color: var(--gray);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .order-details {
            background: var(--light);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: var(--dark);
        }

        .detail-value {
            font-weight: 500;
            color: var(--primary);
        }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            border: none;
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
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .continue-shopping {
            display: block;
            margin-top: 25px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .continue-shopping:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .confirmation-card {
                padding: 30px 20px;
                margin-top: 20px;
            }

            .confirmation-title {
                font-size: 26px;
            }

            .confirmation-message {
                font-size: 16px;
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

            .confirmation-card {
                padding: 20px 15px;
                margin-top: 15px;
                border-radius: 8px;
            }

            .confirmation-icon {
                width: 80px;
                height: 80px;
                margin-bottom: 20px;
            }

            .confirmation-icon i {
                font-size: 40px;
            }

            .confirmation-title {
                font-size: 22px;
                margin-bottom: 12px;
            }

            .confirmation-message {
                font-size: 14px;
                margin-bottom: 20px;
            }

            .order-details {
                padding: 15px;
                margin-bottom: 20px;
            }

            .detail-row {
                flex-direction: column;
                gap: 5px;
                padding: 10px 0;
                align-items: flex-start;
            }

            .detail-label {
                font-size: 13px;
            }

            .detail-value {
                font-size: 14px;
            }

            .actions {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }

            .btn {
                width: 100%;
                padding: 12px 20px;
                font-size: 14px;
            }

            .continue-shopping {
                margin-top: 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <script src="indexjs.js"></script>
    <div class="container">
        <div class="confirmation-card">
            <div class="confirmation-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="confirmation-title">Order Confirmed!</h1>
            
            <p class="confirmation-message">
                Thank you for your purchase. Your order has been successfully placed and is now being processed.
            </p>
            
            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Order Number:</span>
                    <span class="detail-value">#<?php echo htmlspecialchars($order['order_id']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order Date:</span>
                    <span class="detail-value"><?php echo date('F j, Y H:i', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value">$<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Items:</span>
                    <span class="detail-value"><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">Processing</span>
                </div>
            </div>
            
            <div class="actions">
                <a href="edit_profile.php" class="btn btn-primary">
                    <i class="fas fa-user-circle"></i> View My Orders
                </a>
                <a href="products.php" class="btn btn-outline">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
            </div>
            
            <a href="index.php" class="continue-shopping">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
</body>
</html>