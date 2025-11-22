<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'velvetvogue';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_payment':
            addPaymentMethod($conn, $user_id);
            break;
            
        case 'update_payment':
            updatePaymentMethod($conn, $user_id);
            break;
            
        case 'delete_payment':
            deletePaymentMethod($conn, $user_id);
            break;
            
        case 'set_default':
            setDefaultPayment($conn, $user_id);
            break;
            
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_payments') {
    getPaymentMethods($conn, $user_id);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

function addPaymentMethod($conn, $user_id) {
    // Get POST data
    $payment_type = $_POST['payment_type'] ?? '';
    $card_number = trim($_POST['card_number'] ?? '');
    $expiry_month = intval($_POST['expiry_month'] ?? 0);
    $expiry_year = intval($_POST['expiry_year'] ?? 0);
    $cvv = trim($_POST['cvv'] ?? '');
    $billing_address_id = intval($_POST['billing_address_id'] ?? 0);
    
    // Validation
    if (empty($payment_type)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Payment type is required']);
        return;
    }
    
    // For card payments, validate card details
    if (in_array($payment_type, ['credit_card', 'debit_card'])) {
        if (empty($card_number) || empty($expiry_month) || empty($expiry_year) || empty($cvv)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Card details are required']);
            return;
        }
        
        // Basic card number validation (you might want to add more robust validation)
        $card_number_clean = preg_replace('/\D/', '', $card_number);
        if (strlen($card_number_clean) < 13 || strlen($card_number_clean) > 19) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid card number']);
            return;
        }
        
        // Get last 4 digits
        $card_last_four = substr($card_number_clean, -4);
        
        // Determine card brand (simplified)
        $card_brand = getCardBrand($card_number_clean);
    } else {
        $card_last_four = null;
        $card_brand = null;
    }
    
    // Check if this should be the default payment method
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // If setting as default, unset other default payment methods for this user
    if ($is_default) {
        $stmt = $conn->prepare('UPDATE user_payment_methods SET is_default = 0 WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Insert new payment method
    $stmt = $conn->prepare('INSERT INTO user_payment_methods (user_id, payment_type, card_last_four, card_brand, expiry_month, expiry_year, billing_address_id, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssiiii', $user_id, $payment_type, $card_last_four, $card_brand, $expiry_month, $expiry_year, $billing_address_id, $is_default);
    
    if ($stmt->execute()) {
        $payment_id = $stmt->insert_id;
        $stmt->close();
        
        // Return the new payment method data
        $new_payment = [
            'payment_id' => $payment_id,
            'payment_type' => $payment_type,
            'card_last_four' => $card_last_four,
            'card_brand' => $card_brand,
            'expiry_month' => $expiry_month,
            'expiry_year' => $expiry_year,
            'billing_address_id' => $billing_address_id,
            'is_default' => $is_default
        ];
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Payment method added successfully', 'payment' => $new_payment]);
    } else {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to add payment method']);
    }
}

function updatePaymentMethod($conn, $user_id) {
    // Get POST data
    $payment_id = intval($_POST['payment_id'] ?? 0);
    $payment_type = $_POST['payment_type'] ?? '';
    $card_number = trim($_POST['card_number'] ?? '');
    $expiry_month = intval($_POST['expiry_month'] ?? 0);
    $expiry_year = intval($_POST['expiry_year'] ?? 0);
    $cvv = trim($_POST['cvv'] ?? '');
    $billing_address_id = intval($_POST['billing_address_id'] ?? 0);
    
    // Validation
    if (empty($payment_id) || empty($payment_type)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Payment ID and type are required']);
        return;
    }
    
    // Verify the payment method belongs to the user
    $stmt = $conn->prepare('SELECT payment_id FROM user_payment_methods WHERE payment_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $payment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Payment method not found or access denied']);
        return;
    }
    $stmt->close();
    
    // For card payments, validate card details if provided
    if (in_array($payment_type, ['credit_card', 'debit_card']) && !empty($card_number)) {
        // Basic card number validation
        $card_number_clean = preg_replace('/\D/', '', $card_number);
        if (strlen($card_number_clean) < 13 || strlen($card_number_clean) > 19) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid card number']);
            return;
        }
        
        // Get last 4 digits
        $card_last_four = substr($card_number_clean, -4);
        
        // Determine card brand (simplified)
        $card_brand = getCardBrand($card_number_clean);
    } else {
        $card_last_four = null;
        $card_brand = null;
    }
    
    // Check if this should be the default payment method
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // If setting as default, unset other default payment methods for this user
    if ($is_default) {
        $stmt = $conn->prepare('UPDATE user_payment_methods SET is_default = 0 WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Update payment method
    if (!empty($card_number)) {
        // If card number is provided, update card details
        $stmt = $conn->prepare('UPDATE user_payment_methods SET payment_type = ?, card_last_four = ?, card_brand = ?, expiry_month = ?, expiry_year = ?, billing_address_id = ?, is_default = ? WHERE payment_id = ?');
        $stmt->bind_param('ssiiiiii', $payment_type, $card_last_four, $card_brand, $expiry_month, $expiry_year, $billing_address_id, $is_default, $payment_id);
    } else {
        // If no card number provided, don't update card details
        $stmt = $conn->prepare('UPDATE user_payment_methods SET payment_type = ?, billing_address_id = ?, is_default = ? WHERE payment_id = ?');
        $stmt->bind_param('siii', $payment_type, $billing_address_id, $is_default, $payment_id);
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Return the updated payment method data
        $updated_payment = [
            'payment_id' => $payment_id,
            'payment_type' => $payment_type,
            'card_last_four' => $card_last_four,
            'card_brand' => $card_brand,
            'expiry_month' => $expiry_month,
            'expiry_year' => $expiry_year,
            'billing_address_id' => $billing_address_id,
            'is_default' => $is_default
        ];
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Payment method updated successfully', 'payment' => $updated_payment]);
    } else {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update payment method']);
    }
}

function deletePaymentMethod($conn, $user_id) {
    $payment_id = intval($_POST['payment_id'] ?? 0);
    
    if (empty($payment_id)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        return;
    }
    
    // Verify the payment method belongs to the user
    $stmt = $conn->prepare('SELECT payment_id FROM user_payment_methods WHERE payment_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $payment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Payment method not found or access denied']);
        return;
    }
    $stmt->close();
    
    // Delete payment method
    $stmt = $conn->prepare('DELETE FROM user_payment_methods WHERE payment_id = ?');
    $stmt->bind_param('i', $payment_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Payment method deleted successfully']);
    } else {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to delete payment method']);
    }
}

function setDefaultPayment($conn, $user_id) {
    $payment_id = intval($_POST['payment_id'] ?? 0);
    
    if (empty($payment_id)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        return;
    }
    
    // Verify the payment method belongs to the user
    $stmt = $conn->prepare('SELECT payment_id FROM user_payment_methods WHERE payment_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $payment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Payment method not found or access denied']);
        return;
    }
    $stmt->close();
    
    // Unset all default payment methods for this user
    $stmt = $conn->prepare('UPDATE user_payment_methods SET is_default = 0 WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Set this payment method as default
    $stmt = $conn->prepare('UPDATE user_payment_methods SET is_default = 1 WHERE payment_id = ?');
    $stmt->bind_param('i', $payment_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Default payment method updated successfully']);
    } else {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update default payment method']);
    }
}

function getPaymentMethods($conn, $user_id) {
    $stmt = $conn->prepare('SELECT p.*, a.address_line1, a.city, a.state FROM user_payment_methods p LEFT JOIN user_addresses a ON p.billing_address_id = a.address_id WHERE p.user_id = ? ORDER BY p.is_default DESC, p.created_at DESC');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'payments' => $payments]);
}

function getCardBrand($card_number) {
    // Simplified card brand detection
    if (preg_match('/^4/', $card_number)) {
        return 'Visa';
    } elseif (preg_match('/^5[1-5]/', $card_number)) {
        return 'Mastercard';
    } elseif (preg_match('/^3[47]/', $card_number)) {
        return 'American Express';
    } elseif (preg_match('/^6(?:011|5)/', $card_number)) {
        return 'Discover';
    } else {
        return 'Unknown';
    }
}

$conn->close();
?>