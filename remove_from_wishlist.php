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
    $wishlist_id = intval($_POST['wishlist_id'] ?? 0);
    
    if ($wishlist_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid wishlist ID']);
        exit();
    }
    
    // Remove from wishlist (ensure it belongs to the user)
    $delete_query = "DELETE FROM wishlist WHERE wishlist_id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('ii', $wishlist_id, $user_id);
    
    if ($delete_stmt->execute()) {
        if ($delete_stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Product removed from wishlist']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Wishlist item not found or does not belong to user']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove product from wishlist']);
    }
    
    $delete_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>