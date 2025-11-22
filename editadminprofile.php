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

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'seller') {
    header('Location: login.php');
    exit();
}


$admin_id = $_SESSION['user_id'];

// Fetch current admin details
$stmt = $conn->prepare('SELECT * FROM admin WHERE admin_id = ?');
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch password from user table for this seller
$user_password = '';
$stmt = $conn->prepare('SELECT password FROM user WHERE user_id = ?');
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($user_password);
$stmt->fetch();
$stmt->close();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $title = trim($_POST['title']);
    $password = trim($_POST['password']);
    $image_path = $admin['image_path'];

    // Handle profile photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $image_name = 'admin_' . $admin_id . '_' . uniqid() . '.' . $ext;
            $upload_dir = __DIR__ . '/images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $target_path = $upload_dir . $image_name;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_path)) {
                $image_path = 'images/' . $image_name;
            }
        }
    }

    // Update user table for username, email, password
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE user SET username=?, email=?, password=? WHERE user_id=?');
        $stmt->bind_param('sssi', $username, $email, $hashed_password, $admin_id);
    } else {
        $stmt = $conn->prepare('UPDATE user SET username=?, email=? WHERE user_id=?');
        $stmt->bind_param('ssi', $username, $email, $admin_id);
    }
    $user_update_success = $stmt->execute();
    $stmt->close();

    // Update admin table for Name, title, image_path only
    $stmt = $conn->prepare('UPDATE admin SET Name=?, title=?, image_path=? WHERE admin_id=?');
    $stmt->bind_param('sssi', $name, $title, $image_path, $admin_id);
    $admin_update_success = $stmt->execute();
    $stmt->close();

    if ($user_update_success && $admin_update_success) {
        $success = 'Profile updated successfully!';
        // Refresh admin data
        $admin['username'] = $username;
        $admin['Name'] = $name;
        $admin['email'] = $email;
        $admin['title'] = $title;
        $admin['image_path'] = $image_path;
        if (!empty($password)) {
            $admin['password'] = $hashed_password;
        }
        // Update session username for sidebar display
        $_SESSION['user_username'] = $username;
    } else {
        $error = 'Error updating profile: ' . $conn->error;
    }
}

$stmt = $conn->prepare('SELECT username, email FROM user WHERE user_id = ?');
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$user_row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$display_email = $user_row['email'] ?? '';
$display_username = $user_row['username'] ?? '';
$profile_img = (!empty($admin['image_path']) && file_exists(__DIR__ . '/' . $admin['image_path']))
    ? $admin['image_path']
    : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($display_email))) . '?d=mp&s=160';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Velvet Vogue Admin</title>
    <link rel="icon" href="img/logo2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin-left: 280px;
            color: #333;
        }

        /* Vertical Sidebar Navigation */
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50, #34495e);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            /* Hide scrollbar */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }

        .admin-sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .logo {
            color: #3498db;
            font-size: 24px;
            font-weight: 800;
            text-decoration: none;
            letter-spacing: 1px;
            display: block;
        }

        .logo:hover {
            color: #5dade2;
        }

        .nav-container {
            flex: 1;
            overflow-y: auto;
            /* Hide scrollbar */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }

        .nav-container::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }

        .nav-section {
            padding: 20px 0;
            margin-bottom: 20px;
        }

        .nav-title {
            color: #bdc3c7;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 25px 15px 25px;
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-links a {
            color: #ecf0f1;
            text-decoration: none;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-links a:hover {
            background: rgba(52, 152, 219, 0.1);
            border-left-color: #3498db;
            padding-left: 30px;
        }

        .nav-links a.active {
            background: rgba(52, 152, 219, 0.2);
            border-left-color: #3498db;
            color: #3498db;
            font-weight: 600;
        }

        .nav-links a i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .user-section {
            flex-shrink: 0;
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 16px;
        }

        .user-details h4 {
            color: #ecf0f1;
            margin: 0;
            font-size: 14px;
        }

        .user-details span {
            color: #bdc3c7;
            font-size: 12px;
        }

        .logout {
            background: #e74c3c;
            color: #fff;
            padding: 12px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
        }

        .logout:hover {
            background: #c0392b;
        }

        /* Main Content */
        .main-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 30px;
        }

        .page-header {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: #fff;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }

        .page-title {
            font-size: 2.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
        }

        /* Edit Profile Container */
        .edit-profile-container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            overflow: hidden;
        }

        .profile-preview {
            background: linear-gradient(135deg, #3498db, #2980b9);
            padding: 40px;
            text-align: center;
            color: #fff;
            position: relative;
        }

        .profile-preview::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.1);
            z-index: 1;
        }

        .profile-preview > * {
            position: relative;
            z-index: 2;
        }

        .preview-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
            margin: 0 auto 20px auto;
            background: #fff;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }

        .preview-avatar:hover {
            transform: scale(1.05);
        }

        .preview-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-name {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .preview-title {
            font-size: 1rem;
            opacity: 0.9;
            background: rgba(255,255,255,0.1);
            padding: 6px 16px;
            border-radius: 15px;
            display: inline-block;
        }

        .form-section {
            padding: 40px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #5a6c7d;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group label i {
            color: #3498db;
            font-size: 1rem;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #3498db;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 18px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #3498db;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            display: none;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px dashed #ced4da;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #6c757d;
            font-weight: 500;
        }

        .file-input-label:hover {
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
            border-color: #3498db;
            color: #3498db;
        }

        .file-selected {
            background: linear-gradient(135deg, #d4edda, #c3e6cb) !important;
            border-color: #28a745 !important;
            color: #155724 !important;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            padding-top: 20px;
            border-top: 2px solid #f1f3f4;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            min-width: 150px;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9, #1f6391);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: #fff;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        }

        .helper-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
            }

            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }

            .mobile-menu-btn {
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: #2c3e50;
                color: #fff;
                border: none;
                padding: 12px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 18px;
            }

            .main-container {
                padding: 70px 15px 15px 15px;
            }

            .page-header {
                padding: 30px 20px;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .edit-profile-container {
                margin: 0 10px;
            }

            .profile-preview {
                padding: 30px 20px;
            }

            .form-section {
                padding: 30px 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .form-actions {
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }

            .btn {
                width: 100%;
                max-width: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" onclick="toggleMobileMenu()" style="display: none;">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Vertical Sidebar Navigation -->
    <aside class="admin-sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index.html" class="logo">
                <i class="fas fa-gem"></i> Velvet Vogue
            </a>
        </div>
        
        <div class="nav-container">
            <nav class="nav-section">
                <div class="nav-title">Main</div>
                <ul class="nav-links">
                    <li><a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a></li>
                </ul>
            </nav>

            <nav class="nav-section">
                <div class="nav-title">Products</div>
                <ul class="nav-links">
                    <li><a href="add_product.php">
                        <i class="fas fa-plus-circle"></i> Add Product
                    </a></li>
                    <li><a href="view_product.php">
                        <i class="fas fa-box"></i> View Products
                    </a></li>
                    <li><a href="category.php">
                        <i class="fas fa-tags"></i> Categories
                    </a></li>
                </ul>
            </nav>

            <nav class="nav-section">
                <div class="nav-title">Management</div>
                <ul class="nav-links">
                    <li><a href="customer_management.php">
                        <i class="fas fa-users"></i> Customers
                    </a></li>
                    <li><a href="order_management.php">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a></li>
                    <li><a href="contact_management.php">
                        <i class="fas fa-envelope"></i> Messages
                    </a></li>
                </ul>
            </nav>
        </div>
        
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_username'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="user-details">
                    <h4><?php echo htmlspecialchars($_SESSION['user_username'] ?? 'Admin'); ?></h4>
                    <span>Administrator</span>
                </div>
            </div>
            <a href="adminprofile.php" style="background: rgba(52, 152, 219, 0.2); color: #3498db; margin-bottom: 10px; border-radius: 6px; text-decoration: none; padding: 10px 15px; display: flex; align-items: center; gap: 8px; font-weight: 500;">
                <i class="fas fa-user-circle"></i> View Profile
            </a>
            <a href="logout.php" class="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-user-edit"></i> Edit Profile
            </h1>
            <p>Update your profile information and settings</p>
        </div>

        <!-- Edit Profile Container -->
        <div class="edit-profile-container">
            <!-- Profile Preview -->
            <div class="profile-preview">
                <div class="preview-avatar">
                    <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="Profile Image" id="preview-img">
                </div>
                <div class="preview-name" id="preview-name"><?php echo htmlspecialchars($admin['Name']); ?></div>
                <div class="preview-title" id="preview-title"><?php echo htmlspecialchars($admin['title']); ?></div>
            </div>

            <!-- Form Section -->
            <div class="form-section">
                <!-- Alerts -->
                <?php if ($success): ?>
                    <div class="alert success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" class="form-input" 
                    value="<?php echo htmlspecialchars($username ?? $display_username); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="name"><i class="fas fa-id-card"></i> Full Name</label>
                            <input type="text" id="name" name="name" class="form-input" 
                                   value="<?php echo htmlspecialchars($admin['Name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email" class="form-input" 
                    value="<?php echo htmlspecialchars($email ?? $user_row['email'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="title"><i class="fas fa-briefcase"></i> Job Title</label>
                            <input type="text" id="title" name="title" class="form-input" 
                                   value="<?php echo htmlspecialchars($admin['title']); ?>">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="password"><i class="fas fa-lock"></i> New Password</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" class="form-input" 
                                   autocomplete="new-password" placeholder="Leave blank to keep current password">
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <div class="helper-text">Leave blank to keep your current password</div>
                    </div>

                    <div class="form-group full-width">
                        <label for="profile_photo"><i class="fas fa-camera"></i> Profile Photo</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="profile_photo" name="profile_photo" class="file-input" 
                                   accept="image/*">
                            <label for="profile_photo" class="file-input-label" id="file-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Choose new profile photo</span>
                            </label>
                        </div>
                        <div class="helper-text">Supported formats: JPG, PNG, GIF (Max 5MB)</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="adminprofile.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Profile
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
<script>
// Enhanced password toggle functionality
const passwordInput = document.getElementById('password');
const toggleBtn = document.getElementById('togglePassword');
const toggleIcon = document.getElementById('toggleIcon');

if (toggleBtn && passwordInput && toggleIcon) {
    toggleBtn.addEventListener('click', function() {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        toggleIcon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    });
}

// File input enhancement
const fileInput = document.getElementById('profile_photo');
const fileLabel = document.getElementById('file-label');
const previewImg = document.getElementById('preview-img');

if (fileInput && fileLabel) {
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Update label text and style
            fileLabel.querySelector('span').textContent = file.name;
            fileLabel.classList.add('file-selected');
            
            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                if (previewImg) {
                    previewImg.src = e.target.result;
                }
            };
            reader.readAsDataURL(file);
        } else {
            fileLabel.querySelector('span').textContent = 'Choose new profile photo';
            fileLabel.classList.remove('file-selected');
        }
    });
}

// Live preview updates
const nameInput = document.getElementById('name');
const titleInput = document.getElementById('title');
const previewName = document.getElementById('preview-name');
const previewTitle = document.getElementById('preview-title');

if (nameInput && previewName) {
    nameInput.addEventListener('input', function() {
        previewName.textContent = this.value || 'Name';
    });
}

if (titleInput && previewTitle) {
    titleInput.addEventListener('input', function() {
        previewTitle.textContent = this.value || 'Title';
    });
}

// Form validation enhancement
const form = document.querySelector('form');
if (form) {
    form.addEventListener('submit', function(e) {
        const inputs = form.querySelectorAll('input[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.style.borderColor = '#e74c3c';
                input.style.backgroundColor = '#fdf2f2';
                isValid = false;
            } else {
                input.style.borderColor = '#e9ecef';
                input.style.backgroundColor = '#f8f9fa';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
}

// Input focus effects
const formInputs = document.querySelectorAll('.form-input');
formInputs.forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.querySelector('label').style.color = '#3498db';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.querySelector('label').style.color = '#5a6c7d';
    });
});

// Mobile menu functionality
function toggleMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('mobile-open');
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuBtn = document.querySelector('.mobile-menu-btn');
    
    if (window.innerWidth <= 768 && 
        !sidebar.contains(event.target) && 
        !menuBtn.contains(event.target)) {
        sidebar.classList.remove('mobile-open');
    }
});

// Show mobile menu button on small screens
function handleResize() {
    const menuBtn = document.querySelector('.mobile-menu-btn');
    if (window.innerWidth <= 768) {
        menuBtn.style.display = 'block';
    } else {
        menuBtn.style.display = 'none';
        document.getElementById('sidebar').classList.remove('mobile-open');
    }
}

window.addEventListener('resize', handleResize);
handleResize();
</script>
</html>
