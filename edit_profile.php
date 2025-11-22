<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_username'])) {
    header('Location: login.php');
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

// Get user data from both tables
$user_id = $_SESSION['user_id'];

// Get data from user table
$stmt = $conn->prepare('SELECT username, email FROM user WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$stmt->close();

// Get data from customer table
$stmt = $conn->prepare('SELECT firstname, lastname, contactno, address, country, image_path FROM customer WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$customer_result = $stmt->get_result();
$customer_data = $customer_result->fetch_assoc();
$stmt->close();

// Merge data
if ($customer_data) {
    $profile_data = array_merge($user_data, $customer_data);
} else {
    $profile_data = $user_data;
    // Initialize customer fields if not present
    $profile_data['firstname'] = '';
    $profile_data['lastname'] = '';
    $profile_data['contactno'] = '';
    $profile_data['address'] = '';
    $profile_data['country'] = '';
    $profile_data['image_path'] = '';
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_firstname = trim($_POST['firstname']);
    $new_lastname = trim($_POST['lastname']);
    $new_contactno = trim($_POST['contactno']);
    $new_address = trim($_POST['address']);
    $new_country = trim($_POST['country']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    // Update $profile_data with submitted values so form fields retain input after save
    $profile_data['username'] = $new_username;
    $profile_data['email'] = $new_email;
    $profile_data['firstname'] = $new_firstname;
    $profile_data['lastname'] = $new_lastname;
    $profile_data['contactno'] = $new_contactno;
    $profile_data['address'] = $new_address;
    $profile_data['country'] = $new_country;
    
    // Handle profile picture upload
    $image_path = $profile_data['image_path']; // Keep existing image by default
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'images/profiles/';
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = $_FILES['profile_picture']['name'];
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_type = $_FILES['profile_picture']['type'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($file_type, $allowed_types) && $file_size <= 2097152) { // 2MB limit
            // Generate unique file name
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = uniqid() . '_' . $user_id . '.' . $file_extension;
            $upload_path = $upload_dir . $new_file_name;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Delete old profile picture if exists
                if (!empty($image_path) && file_exists(__DIR__ . '/' . $image_path)) {
                    unlink(__DIR__ . '/' . $image_path);
                }
                $image_path = $upload_path;
                // Update profile_data with new image path for immediate preview
                $profile_data['image_path'] = $upload_path;
            } else {
                $message = 'Failed to upload profile picture!';
                $message_type = 'error';
            }
        } else {
            $message = 'Invalid file type or size too large! (Max 2MB, JPG/PNG/GIF only)';
            $message_type = 'error';
        }
    }

    // Check if user wants to change password
    $password_change_requested = !empty($current_password) || !empty($new_password);
    
    if ($password_change_requested) {
        // Verify current password from user table
        $stmt = $conn->prepare('SELECT password FROM user WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($current_password, $hashed_password) || $current_password === $hashed_password) {
            // Password verification successful, proceed with updates
            $password_verified = true;
        } else {
            $message = 'Current password is incorrect!';
            $message_type = 'error';
        }
    } else {
        // No password change requested, proceed with other updates
        $password_verified = true;
    }

    if (!isset($message_type) || $message_type !== 'error') {
        // Update user table fields
        $stmt = $conn->prepare('UPDATE user SET username = ?, email = ? WHERE user_id = ?');
        $stmt->bind_param('ssi', $new_username, $new_email, $user_id);
        $stmt->execute();
        $stmt->close();

        // Check if customer record exists
        $stmt = $conn->prepare('SELECT COUNT(*) FROM customer WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($customer_exists);
        $stmt->fetch();
        $stmt->close();

        if ($customer_exists > 0) {
            // Update existing customer record
            $stmt = $conn->prepare('UPDATE customer SET firstname = ?, lastname = ?, contactno = ?, address = ?, country = ?, image_path = ? WHERE user_id = ?');
            $stmt->bind_param('ssssssi', $new_firstname, $new_lastname, $new_contactno, $new_address, $new_country, $image_path, $user_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert new customer record
            $stmt = $conn->prepare('INSERT INTO customer (user_id, firstname, lastname, contactno, address, country, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('issssss', $user_id, $new_firstname, $new_lastname, $new_contactno, $new_address, $new_country, $image_path);
            $stmt->execute();
            $stmt->close();
        }

        // Update password if requested and verified
        if ($password_change_requested && $password_verified && !empty($new_password)) {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE user SET password = ? WHERE user_id = ?');
            $stmt->bind_param('si', $hashed_new_password, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        // Update session username
        $_SESSION['user_username'] = $new_username;

        $message = 'Profile updated successfully!';
        $message_type = 'success';
    }
}
// Don't close the database connection here as it's needed by the order component
// $conn->close();  // This line is moved to after the HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Velvet Vogue</title>
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
        
        /* Form Styles */
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px;
        }

        .form-col {
            flex: 1;
            padding: 0 15px;
            min-width: 300px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 76, 117, 0.1);
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

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
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

        .password-section {
            background: var(--light);
            border-radius: 8px;
            padding: 25px;
            margin-top: 20px;
        }

        .password-section h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--primary);
        }

        .section-divider {
            height: 1px;
            background: var(--border);
            margin: 30px 0;
        }

        .account-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }

        /* Profile Picture Styles */
        .profile-picture-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 25px;
            padding: 20px;
            background: var(--light);
            border-radius: 8px;
            text-align: center;
        }

        .profile-picture-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            margin-bottom: 15px;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .profile-picture-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-picture-preview i {
            font-size: 48px;
            color: var(--gray);
        }

        .profile-picture-upload {
            position: relative;
            display: inline-block;
        }

        .profile-picture-upload input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .upload-btn {
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .upload-btn:hover {
            background: var(--primary-dark);
        }

        .file-name {
            margin-top: 8px;
            font-size: 13px;
            color: var(--gray);
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
            align-items: center;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .modern-profile-dropdown a:last-child {
            border-bottom: none;
        }

        .modern-profile-dropdown a:hover {
            background-color: #f8f9fa;
            color: #2c4c75;
        }

        .modern-profile-dropdown a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .modern-profile-dropdown .dropdown-header {
            padding: 15px 20px;
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c4c75;
            border-bottom: 1px solid #eee;
        }

        .modern-profile-dropdown .dropdown-header .welcome-text {
            font-size: 14px;
            font-weight: normal;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 992px) {
            .account-layout {
                flex-direction: column;
            }
            
            .account-sidebar {
                flex: 0 0 auto;
            }
            
            .form-col {
                min-width: 100%;
            }
        }

        @media (max-width: 965px) {
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

            .account-header h1 {
                font-size: 1.8rem;
            }
            
            .account-footer {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }

            .sidebar-menu {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .sidebar-menu li a {
                font-size: 14px;
                padding: 12px;
            }

            .form-row {
                flex-direction: column;
            }

            .form-col {
                width: 100%;
            }

            .profile-picture-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-picture-preview {
                margin-bottom: 20px;
            }
        }

        @media (max-width: 480px) {
            .account-header h1 {
                font-size: 1.5rem;
            }

            .back-to-home {
                font-size: 14px;
                padding: 8px 15px;
            }

            .sidebar-menu {
                grid-template-columns: 1fr;
            }

            .sidebar-menu li a {
                font-size: 13px;
                padding: 10px;
            }

            .form-control, .form-select {
                font-size: 14px;
                padding: 10px;
            }

            .btn {
                padding: 12px;
                font-size: 14px;
            }

            .profile-picture-preview {
                width: 100px;
                height: 100px;
            }

            .profile-picture-preview i {
                font-size: 40px;
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
                    <li><a href="#profile-section" class="active"><i class="fas fa-user-edit"></i> Edit Profile</a></li>
                    <li><a href="my_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                    <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                    <li><a href="address_book.php"><i class="fas fa-map-marker-alt"></i> Address Book</a></li>
                    <li><a href="payment_methods.php"><i class="fas fa-credit-card"></i> Payment Methods</a></li>
                    <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                </ul>
            </div>

            <div class="account-content">
                <div id="profile-section" class="account-card">
                    <div class="account-card-header">
                        <h2>Personal Information</h2>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?>">
                            <i class="fas fa-<?php echo ($message_type == 'success') ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="edit_profile.php" enctype="multipart/form-data">
                        <div class="profile-picture-container">
                            <div class="profile-picture-preview">
                                <?php if (!empty($profile_data['image_path']) && file_exists(__DIR__ . '/' . $profile_data['image_path'])): ?>
                                    <?php $img_time = file_exists(__DIR__ . '/' . $profile_data['image_path']) ? filemtime(__DIR__ . '/' . $profile_data['image_path']) : time(); ?>
                                    <img src="<?php echo htmlspecialchars($profile_data['image_path']) . '?v=' . time(); ?>" alt="Profile Picture">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div class="profile-picture-upload">
                                <div class="upload-btn">
                                    <i class="fas fa-upload"></i> Choose Picture
                                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                                </div>
                                <div class="file-name" id="file-name">No file chosen</div>
                            </div>
                            <small class="form-text text-muted">Max file size: 2MB. Supported formats: JPG, PNG, GIF</small>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="firstname">First Name</label>
                                    <input type="text" id="firstname" name="firstname" class="form-control" value="<?php echo isset($profile_data['firstname']) ? htmlspecialchars($profile_data['firstname']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="lastname">Last Name</label>
                                    <input type="text" id="lastname" name="lastname" class="form-control" value="<?php echo isset($profile_data['lastname']) ? htmlspecialchars($profile_data['lastname']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="username">Username *</label>
                                    <input type="text" id="username" name="username" class="form-control" value="<?php echo isset($profile_data['username']) ? htmlspecialchars($profile_data['username']) : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($profile_data['email']) ? htmlspecialchars($profile_data['email']) : ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="contactno">Phone Number</label>
                                    <input type="text" id="contactno" name="contactno" class="form-control" value="<?php echo isset($profile_data['contactno']) ? htmlspecialchars($profile_data['contactno']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <input type="text" id="address" name="address" class="form-control" value="<?php echo isset($profile_data['address']) ? htmlspecialchars($profile_data['address']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <input type="text" id="country" name="country" class="form-control" value="<?php echo isset($profile_data['country']) ? htmlspecialchars($profile_data['country']) : ''; ?>">
                                </div>
                                
                                <div class="password-section">
                                    <h3>Change Password</h3>
                                    <div class="form-group">
                                        <label for="current_password">Current Password</label>
                                        <input type="password" id="current_password" name="current_password" class="form-control">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <input type="password" id="new_password" name="new_password" class="form-control">
                                        <small class="form-text text-muted">Leave blank to keep current password</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="section-divider"></div>
                        
                        <div class="account-footer">
                            <button type="reset" class="btn btn-outline">Reset</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
                
                
            </div>
        </div>
    </div>
    
    <script>
        // Profile picture file name display
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
            
            // Preview the selected image immediately
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.querySelector('.profile-picture-preview');
                    // Clear existing content
                    preview.innerHTML = '';
                    // Create new image element
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    preview.appendChild(img);
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // After form submission, ensure the image preview shows the newly uploaded image
        window.addEventListener('DOMContentLoaded', function() {
            // Add a timestamp to the image URL to prevent caching
            const profileImg = document.querySelector('.profile-picture-preview img');
            if (profileImg) {
                const src = profileImg.src;
                const timestamp = new Date().getTime();
                if (src.indexOf('?v=') === -1) {
                    profileImg.src = src + '?v=' + timestamp;
                } else {
                    profileImg.src = src.split('?v=')[0] + '?v=' + timestamp;
                }
            }
            
            // Handle form submission to maintain image preview
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    // If there's a selected file, keep showing it during submission
                    const fileInput = document.getElementById('profile_picture');
                    const preview = document.querySelector('.profile-picture-preview');
                    
                    if (fileInput.files && fileInput.files[0]) {
                        // Store the current preview in sessionStorage
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            try {
                                sessionStorage.setItem('profilePreview', e.target.result);
                            } catch (e) {
                                // Ignore storage errors
                            }
                        };
                        reader.readAsDataURL(fileInput.files[0]);
                    }
                });
            }
            
            // Restore preview from sessionStorage if available
            try {
                const savedPreview = sessionStorage.getItem('profilePreview');
                if (savedPreview) {
                    const preview = document.querySelector('.profile-picture-preview');
                    if (preview && !preview.querySelector('img')) {
                        preview.innerHTML = '<img src="' + savedPreview + '" alt="Profile Picture">';
                    }
                    sessionStorage.removeItem('profilePreview');
                }
            } catch (e) {
                // Ignore storage errors
            }
            
            // Handle navigation for profile section only
            const profileLink = document.querySelector('.sidebar-menu a[href="#profile-section"]');
            const profileSection = document.getElementById('profile-section');
            
            // Show profile section by default
            if (profileSection) {
                profileSection.style.display = 'block';
            }
            
            // Add click event listener to profile link only
            if (profileLink) {
                profileLink.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent default anchor behavior
                    
                    // Remove active class from all links
                    document.querySelectorAll('.sidebar-menu a').forEach(l => l.classList.remove('active'));
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Show the profile section
                    if (profileSection) {
                        profileSection.style.display = 'block';
                        
                        // Scroll to the section
                        profileSection.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            }
        });
    </script>
    <?php
    // Close the database connection at the end of the page
    if (isset($conn) && $conn) {
        $conn->close();
    }
    ?>
</body>
</html>
