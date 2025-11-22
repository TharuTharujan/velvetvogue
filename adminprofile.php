
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

$seller_id = $_SESSION['user_id'];

// Check if seller exists in admin table
$stmt = $conn->prepare('SELECT * FROM admin WHERE admin_id = ?');
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
	// Fetch seller details from user table
	$stmt = $conn->prepare('SELECT * FROM user WHERE user_id = ? AND role = ?');
	$role = 'seller';
	$stmt->bind_param('is', $seller_id, $role);
	$stmt->execute();
	$user = $stmt->get_result()->fetch_assoc();
	$stmt->close();

	if ($user) {
	   // Insert into admin table (set both admin_id and user_id for FK constraint)
	   $stmt = $conn->prepare('INSERT INTO admin (admin_id, Name, title, image_path, user_id) VALUES (?, ?, ?, ?, ?)');
	   $title = $user['title'] ?? 'Seller';
	   $image_path = $user['image_path'] ?? '';
	   $name = (!empty($user['Name'])) ? $user['Name'] : $user['username'];
	   $stmt->bind_param('isssi', $user['user_id'], $name, $title, $image_path, $user['user_id']);
	   $stmt->execute();
	   $stmt->close();
		// Fetch the newly inserted admin
		$stmt = $conn->prepare('SELECT * FROM admin WHERE admin_id = ?');
		$stmt->bind_param('i', $seller_id);
		$stmt->execute();
		$admin = $stmt->get_result()->fetch_assoc();
		$stmt->close();
	} else {
		// Seller not found in user table
		session_destroy();
		header('Location: login.php');
		exit();
	}
}


// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
	$ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
	$allowed = ['jpg', 'jpeg', 'png', 'gif'];
	if (in_array($ext, $allowed)) {
		$image_name = 'admin_' . $admin['admin_id'] . '_' . uniqid() . '.' . $ext;
		$upload_dir = __DIR__ . '/images/';
		if (!is_dir($upload_dir)) {
			mkdir($upload_dir, 0777, true);
		}
		$target_path = $upload_dir . $image_name;
		if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_path)) {
			$image_path = 'images/' . $image_name;
			// Update admin table
			$stmt = $conn->prepare('UPDATE admin SET image_path = ? WHERE admin_id = ?');
			$stmt->bind_param('si', $image_path, $admin['admin_id']);
			$stmt->execute();
			$stmt->close();
			// Update $admin array for immediate display
			$admin['image_path'] = $image_path;
		}
	}
}

// Fetch username and email from user table for display
$stmt = $conn->prepare('SELECT username, email FROM user WHERE user_id = ?');
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$user_row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$display_username = $user_row['username'] ?? '';
$display_email = $user_row['email'] ?? '';
// Use admin image if available, else fallback
$profile_img = (!empty($admin['image_path']) && file_exists(__DIR__ . '/' . $admin['image_path']))
	? $admin['image_path']
	: 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($display_email))) . '?d=mp&s=160';
// Ensure session username is always up to date for sidebar
if (!isset($_SESSION['user_username']) || $_SESSION['user_username'] !== $display_username) {
	$_SESSION['user_username'] = $display_username;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin Profile - Velvet Vogue</title>
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
			background: linear-gradient(135deg, #9b59b6, #8e44ad);
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

		/* Profile Container */
		.profile-container {
			max-width: 1200px;
			margin: 0 auto;
			background: #fff;
			border-radius: 15px;
			box-shadow: 0 8px 30px rgba(0,0,0,0.12);
			overflow: hidden;
		}

		.profile-header {
			background: linear-gradient(135deg, #3498db, #2980b9);
			padding: 50px 40px;
			text-align: center;
			color: #fff;
			position: relative;
		}

		.profile-header::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(0,0,0,0.1);
			z-index: 1;
		}

		.profile-header > * {
			position: relative;
			z-index: 2;
		}

		.profile-avatar {
			width: 160px;
			height: 160px;
			border-radius: 50%;
			border: 6px solid rgba(255,255,255,0.3);
			margin: 0 auto 25px auto;
			background: #fff;
			overflow: hidden;
			display: flex;
			align-items: center;
			justify-content: center;
			position: relative;
			transition: all 0.3s ease;
			box-shadow: 0 8px 25px rgba(0,0,0,0.2);
		}

		.profile-avatar:hover {
			transform: scale(1.05);
		}

		.profile-avatar img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.profile-name {
			font-size: 2.3rem;
			font-weight: 700;
			margin-bottom: 10px;
			text-shadow: 0 3px 6px rgba(0,0,0,0.2);
			letter-spacing: 0.5px;
		}

		.profile-title {
			font-size: 1.2rem;
			opacity: 0.95;
			font-weight: 500;
			background: rgba(255,255,255,0.1);
			padding: 8px 20px;
			border-radius: 20px;
			display: inline-block;
		}

		.profile-body {
			padding: 50px;
		}

		.profile-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 25px;
			margin-bottom: 40px;
		}

		.profile-info {
			background: linear-gradient(135deg, #f8f9fa, #e9ecef);
			padding: 30px;
			border-radius: 15px;
			border-left: 5px solid #3498db;
			transition: all 0.3s ease;
			box-shadow: 0 4px 15px rgba(0,0,0,0.05);
		}

		.profile-info:hover {
			transform: translateY(-3px);
			box-shadow: 0 8px 25px rgba(52, 152, 219, 0.15);
			border-left-color: #2980b9;
		}

		.profile-info label {
			display: flex;
			align-items: center;
			gap: 10px;
			color: #5a6c7d;
			font-size: 0.95rem;
			font-weight: 600;
			margin-bottom: 12px;
			text-transform: uppercase;
			letter-spacing: 0.8px;
		}

		.profile-info label i {
			color: #3498db;
			font-size: 1.1rem;
		}

		.info-value {
			color: #2c3e50;
			font-size: 1.2rem;
			font-weight: 600;
			word-break: break-word;
			line-height: 1.4;
		}

		.profile-actions {
			text-align: center;
			display: flex;
			gap: 20px;
			justify-content: center;
			flex-wrap: wrap;
			padding-top: 20px;
			border-top: 2px solid #f1f3f4;
		}

		.btn {
			padding: 15px 35px;
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
			min-width: 160px;
			justify-content: center;
			box-shadow: 0 4px 15px rgba(0,0,0,0.1);
		}

		.btn-primary {
			background: linear-gradient(135deg, #3498db, #2980b9);
			color: #fff;
		}

		.btn-primary:hover {
			background: linear-gradient(135deg, #2980b9, #1f6391);
			transform: translateY(-3px);
			box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
		}

		.btn-secondary {
			background: linear-gradient(135deg, #34495e, #2c3e50);
			color: #fff;
		}

		.btn-secondary:hover {
			background: linear-gradient(135deg, #2c3e50, #1a252f);
			transform: translateY(-3px);
			box-shadow: 0 8px 25px rgba(52, 73, 94, 0.4);
		}

		.stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 20px;
			margin-bottom: 30px;
		}

		.stat-card {
			background: linear-gradient(135deg, #667eea, #764ba2);
			color: #fff;
			padding: 25px;
			border-radius: 12px;
			text-align: center;
			transition: transform 0.3s ease;
		}

		.stat-card:hover {
			transform: translateY(-5px);
		}

		.stat-icon {
			font-size: 2rem;
			margin-bottom: 10px;
			opacity: 0.9;
		}

		.stat-number {
			font-size: 1.8rem;
			font-weight: 700;
			margin-bottom: 5px;
		}

		.stat-label {
			font-size: 0.9rem;
			opacity: 0.8;
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

			.profile-container {
				margin: 0 10px;
			}

			.profile-header {
				padding: 40px 20px;
			}

			.profile-body {
				padding: 30px 20px;
			}

			.profile-grid {
				grid-template-columns: 1fr;
				gap: 20px;
			}

			.profile-actions {
				flex-direction: column;
				align-items: center;
				gap: 15px;
			}

			.btn {
				width: 100%;
				max-width: 280px;
				justify-content: center;
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
            <a href="adminprofile.php" class="active" style="background: rgba(52, 152, 219, 0.3); color: #3498db; margin-bottom: 10px; border-radius: 6px; text-decoration: none; padding: 10px 15px; display: flex; align-items: center; gap: 8px; font-weight: 600;">
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
                <i class="fas fa-user-circle"></i> Admin Profile
            </h1>
            <p>Manage your profile information and settings</p>
        </div>

        <!-- Profile Container -->
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="Profile Image">
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($admin['Name']); ?></div>
                <div class="profile-title"><?php echo htmlspecialchars($admin['title']); ?></div>
            </div>
            <div class="profile-body">
                <div class="profile-grid">
                    <div class="profile-info">
						<label><i class="fas fa-user"></i> Username</label>
						<div class="info-value"><?php echo htmlspecialchars($display_username); ?></div>
                    </div>
                    <div class="profile-info">
						<label><i class="fas fa-envelope"></i> Email</label>
						<div class="info-value"><?php echo htmlspecialchars($display_email); ?></div>
                    </div>
                </div>
                <div class="profile-actions">
					<a href="editadminprofile.php" class="btn btn-primary">
						<i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
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
</body>
</html>
