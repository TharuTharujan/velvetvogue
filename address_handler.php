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
        case 'add_address':
            addAddress($conn, $user_id);
            break;
            
        case 'update_address':
            updateAddress($conn, $user_id);
            break;
            
        case 'delete_address':
            deleteAddress($conn, $user_id);
            break;
            
        case 'set_default':
            setDefaultAddress($conn, $user_id);
            break;
            
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_addresses') {
    getAddresses($conn, $user_id);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

function addAddress($conn, $user_id) {
    // Get POST data
    $address_type = $_POST['address_type'] ?? 'home';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($address_line1) || 
        empty($city) || empty($state) || empty($postal_code) || empty($country)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        return;
    }
    
    // Check if this should be the default address
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // If setting as default, unset other default addresses for this user
    if ($is_default) {
        $stmt = $conn->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Insert new address
    $stmt = $conn->prepare('INSERT INTO user_addresses (user_id, address_type, first_name, last_name, company, address_line1, address_line2, city, state, postal_code, country, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssssssssssi', $user_id, $address_type, $first_name, $last_name, $company, $address_line1, $address_line2, $city, $state, $postal_code, $country, $phone, $is_default);
    
    if ($stmt->execute()) {
        $address_id = $stmt->insert_id;
        $stmt->close();
        
        // Return the new address data
        $new_address = [
            'address_id' => $address_id,
            'address_type' => $address_type,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'company' => $company,
            'address_line1' => $address_line1,
            'address_line2' => $address_line2,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postal_code,
            'country' => $country,
            'phone' => $phone,
            'is_default' => $is_default
        ];
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Address added successfully', 'address' => $new_address]);
    } else {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to add address']);
    }
}

function updateAddress($conn, $user_id) {
    // Get POST data
    $address_id = intval($_POST['address_id'] ?? 0);
    $address_type = $_POST['address_type'] ?? 'home';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if (empty($address_id) || empty($first_name) || empty($last_name) || empty($address_line1) || 
        empty($city) || empty($state) || empty($postal_code) || empty($country)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        return;
    }
    
    // Verify the address belongs to the user
    $stmt = $conn->prepare('SELECT address_id FROM user_addresses WHERE address_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $address_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Address not found or access denied']);
        return;
    }
    $stmt->close();
    
    // Check if this should be the default address
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // If setting as default, unset other default addresses for this user
    if ($is_default) {
        $stmt = $conn->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Update address
    $stmt = $conn->prepare('UPDATE user_addresses SET address_type = ?, first_name = ?, last_name = ?, company = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, postal_code = ?, country = ?, phone = ?, is_default = ? WHERE address_id = ?');
    $stmt->bind_param('ssssssssssiii', $address_type, $first_name, $last_name, $company, $address_line1, $address_line2, $city, $state, $postal_code, $country, $phone, $is_default, $address_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Return the updated address data
        $updated_address = [
            'address_id' => $address_id,
            'address_type' => $address_type,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'company' => $company,
            'address_line1' => $address_line1,
            'address_line2' => $address_line2,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postal_code,
            'country' => $country,
            'phone' => $phone,
            'is_default' => $is_default
        ];
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Address updated successfully', 'address' => $updated_address]);
    } else {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update address']);
    }
}

function deleteAddress($conn, $user_id) {
    $address_id = intval($_POST['address_id'] ?? 0);
    
    if (empty($address_id)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Address ID is required']);
        return;
    }
    
    // Verify the address belongs to the user
    $stmt = $conn->prepare('SELECT address_id FROM user_addresses WHERE address_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $address_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Address not found or access denied']);
        return;
    }
    $stmt->close();
    
    // Delete address
    $stmt = $conn->prepare('DELETE FROM user_addresses WHERE address_id = ?');
    $stmt->bind_param('i', $address_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
    } else {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to delete address']);
    }
}

function setDefaultAddress($conn, $user_id) {
    $address_id = intval($_POST['address_id'] ?? 0);
    
    if (empty($address_id)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Address ID is required']);
        return;
    }
    
    // Verify the address belongs to the user
    $stmt = $conn->prepare('SELECT address_id FROM user_addresses WHERE address_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $address_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Address not found or access denied']);
        return;
    }
    $stmt->close();
    
    // Unset all default addresses for this user
    $stmt = $conn->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Set this address as default
    $stmt = $conn->prepare('UPDATE user_addresses SET is_default = 1 WHERE address_id = ?');
    $stmt->bind_param('i', $address_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Default address updated successfully']);
    } else {
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update default address']);
    }
}

function getAddresses($conn, $user_id) {
    $stmt = $conn->prepare('SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $addresses = [];
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }
    
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'addresses' => $addresses]);
}

$conn->close();
?>