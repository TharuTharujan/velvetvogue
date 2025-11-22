<?php
// This component displays a user's orders and can be included in other pages like edit_profile.php

// Initialize orders result
$orders_result = null;

// Check if we're in the edit_profile context (where $conn is already established)
// If not, create a new connection
if (!isset($conn) || !$conn || $conn->connect_errno) {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'velvetvogue';
    
    // Only proceed if user_id is provided
    if (!isset($user_id)) {
        // Try to get user_id from session
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        } else {
            return; // Exit if no user_id is available
        }
    }
    
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }
    
    // Fetch orders for the user
    $stmt = $conn->prepare('SELECT o.*, COUNT(oi.item_id) as item_count FROM orders o LEFT JOIN order_items oi ON o.order_id = oi.order_id WHERE o.user_id = ? GROUP BY o.order_id ORDER BY o.created_at DESC');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $orders_result = $stmt->get_result();
    $stmt->close();
    
    // Close the connection if we created it
    $conn->close();
} else {
    // If we're in edit_profile context, make sure we have user_id
    if (!isset($user_id)) {
        // Try to get user_id from session (as in edit_profile.php)
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        } else {
            return; // Exit if no user_id is available
        }
    }
    
    // Check if the connection is still valid before using it
    if ($conn && !$conn->connect_errno) {
        // Fetch orders for the user using the existing connection
        $stmt = $conn->prepare('SELECT o.*, COUNT(oi.item_id) as item_count FROM orders o LEFT JOIN order_items oi ON o.order_id = oi.order_id WHERE o.user_id = ? GROUP BY o.order_id ORDER BY o.created_at DESC');
        if ($stmt) { // Check if prepare was successful
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $orders_result = $stmt->get_result();
            $stmt->close();
        }
    }
    // Don't close the connection here as it might be needed elsewhere in the page
}
?>

<div class="orders-section">
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

<style>
.orders-section {
    margin-top: 30px;
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
    text-decoration: none;
    display: inline-block;
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
</style>