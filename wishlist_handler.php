<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'velvetvogue';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit();
    }
    
    switch ($action) {
        case 'add_to_wishlist':
            // Check if product already in wishlist
            $check_query = "SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param('ii', $user_id, $product_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Product already in wishlist']);
                exit();
            }
            
            // Add to wishlist
            $insert_query = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param('ii', $user_id, $product_id);
            
            if ($insert_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Product added to wishlist']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add product to wishlist']);
            }
            $insert_stmt->close();
            break;
            
        case 'remove_from_wishlist':
            // Remove from wishlist
            $delete_query = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param('ii', $user_id, $product_id);
            
            if ($delete_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Product removed from wishlist']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove product from wishlist']);
            }
            $delete_stmt->close();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>