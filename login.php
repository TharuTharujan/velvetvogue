<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Login & Register - Velvet Vogue</title>
	<link rel="icon" href="img/logo2.png">
	<link rel="stylesheet" href="loginstyle.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
	<style>
		.password-container {
			position: relative;
		}
		.toggle-password {
			position: absolute;
			right: 15px;
			top: 50%;
			transform: translateY(-50%);
			cursor: pointer;
			color: #95a5a6;
		}
		.toggle-password:hover {
			color: #3498db;
		}
	</style>
</head>
<body>
	<!-- Header Section -->
	<div class="header">
		
		<div class="header-top">
			<div class="logo">
				<a href="index.php">
					<img src="img/logo2.png" alt="Velvet Vogue Logo" width="200px">
				</a>
			</div>

			<div class="header-right">
				<!-- Mobile Menu Toggle -->
				<div class="mobile-menu-toggle">
					<span></span>
					<span></span>
					<span></span>
				</div>
			</div>
		</div>

		<div class="search-bar">
			<form action="search.php" method="GET">
				<input type="text" name="q" placeholder="Search products..." required>
				<button type="submit"><i class="fas fa-search"></i></button>
			</form>
		</div>

		<nav class="nav">
			<a href="index.php">Home</a>
			<a href="index.php#brand">About Us</a>
			<a href="products.php">Products</a>
			<a href="contactus.php">Contact Us</a>
			<?php
			session_start();
			// Database connection for cart count
			$host = 'localhost';
			$user = 'root';
			$pass = '';
			$db = 'velvetvogue';
			$conn = new mysqli($host, $user, $pass, $db);
			
			// Get cart item count for logged in user
			$cart_count = 0;
			if (isset($_SESSION['user_id'])) {
				$user_id = intval($_SESSION['user_id']);
				$cart_query = "SELECT SUM(quantity) as total_items FROM cart WHERE user_id = ?";
				$cart_stmt = $conn->prepare($cart_query);
				$cart_stmt->bind_param('i', $user_id);
				$cart_stmt->execute();
				$cart_result = $cart_stmt->get_result();
				if ($cart_row = $cart_result->fetch_assoc()) {
					$cart_count = $cart_row['total_items'] ?? 0;
				}
				$cart_stmt->close();
			}
			
			if (isset($_SESSION['user_username'])) {
				echo '<a href="cart.php" class="cart-icon">
						<i class="fas fa-shopping-cart"></i>';
				if ($cart_count > 0) {
					echo '<span class="cart-count">' . $cart_count . '</span>';
				}
				echo '</a>';
				
				echo '<div class="modern-user-profile">
						<a href="#" class="modern-profile-link">
						  <i class="fas fa-user-circle"></i>
						  <span class="username">' . htmlspecialchars($_SESSION['user_username']) . '</span>
						</a>
						<div class="modern-profile-dropdown">
						  <div class="dropdown-header">
							Welcome back!
							<div class="welcome-text">' . htmlspecialchars($_SESSION['user_username']) . '</div>
						  </div>
						  <a href="edit_profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
						  <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
						</div>
					  </div>';
			} else {
				echo '<a href="login.php" class="active">Login</a>';
			}
			
			$conn->close();
			?>
		</nav>
	</div>
<div class="container">
		<div id="login-form" class="form-container">
			<h2>Welcome Back</h2>
			<!-- Error message display -->
			<div id="error-message" class="error-message" style="display: none;">
				Invalid username or password! Please try again.
			</div>
			<?php 
			// Display success message if registration was successful
			if (isset($_GET['registration']) && $_GET['registration'] == 'success') {
				echo '<div class="success-message" style="background: rgba(40, 167, 69, 0.1); border: 2px solid #28a745; color: #28a745; padding: 15px; border-radius: 12px; text-align: center; margin-bottom: 20px; font-weight: 500;">';
				echo 'Registration successful! You can now login with your credentials.';
				echo '</div>';
			}
			?>
			<form method="post" action="user.php">
				<div class="form-group">
					<input type="text" name="username" placeholder="Username" required>
				</div>
				<div class="form-group">
					<div class="password-container">
						<input type="password" name="password" placeholder="Password" id="login-password" required>
						<span class="toggle-password" onclick="togglePassword('login-password')">
							<i class="fas fa-eye"></i>
						</span>
					</div>
				</div>
				<button type="submit">Sign In</button>
			</form>
			<div class="toggle">
				Don't have an account? <a onclick="showRegister()">Create Account</a>
			</div>
		</div>
	<div id="register-form" class="form-container">
			<h2>Join Velvet Vogue</h2>
			<form method="post" action="user_register.php">
				<div class="form-group">
					<input type="text" name="username" placeholder="Username" required>
				</div>
				<div class="form-group">
					<input type="email" name="email" placeholder="Email Address" required>
				</div>
				<div class="form-group">
					<div class="password-container">
						<input type="password" name="password" placeholder="Password" id="register-password" required>
						<span class="toggle-password" onclick="togglePassword('register-password')">
							<i class="fas fa-eye"></i>
						</span>
					</div>
				</div>
				<div class="form-group">
					<div class="password-container">
						<input type="password" name="confirm_password" placeholder="Confirm Password" id="confirm-password" required>
						<span class="toggle-password" onclick="togglePassword('confirm-password')">
							<i class="fas fa-eye"></i>
						</span>
					</div>
				</div>
				<div class="form-group">
					<label for="role">Select your role:</label>
					<select name="role" id="role" required>
						<option value="">-- Select Role --</option>
						<option value="buyer">Buyer - Shop for products</option>
						<option value="seller">Seller - List and manage products</option>
					</select>
				</div>
				<button type="submit" name="register">Create Account</button>
			</form>
			<div class="toggle">
				Already have an account? <a onclick="showLogin()">Sign In</a>
			</div>
		</div>
	</div>
	<script src="indexjs.js"></script>
	<script>
		function showRegister() {
			document.getElementById('login-form').style.display = 'none';
			document.getElementById('register-form').style.display = 'block';
		}
		function showLogin() {
			document.getElementById('register-form').style.display = 'none';
			document.getElementById('login-form').style.display = 'block';
		}
		
		// Toggle password visibility
		function togglePassword(fieldId) {
			const passwordField = document.getElementById(fieldId);
			const toggleIcon = passwordField.parentNode.querySelector('.toggle-password i');
			
			if (passwordField.type === 'password') {
				passwordField.type = 'text';
				toggleIcon.classList.remove('fa-eye');
				toggleIcon.classList.add('fa-eye-slash');
			} else {
				passwordField.type = 'password';
				toggleIcon.classList.remove('fa-eye-slash');
				toggleIcon.classList.add('fa-eye');
			}
		}
		
		// Check for error parameter in URL
		window.onload = function() {
			const urlParams = new URLSearchParams(window.location.search);
			if (urlParams.get('error') === 'invalid_credentials') {
				document.getElementById('error-message').style.display = 'block';
				// Clear the URL parameter
				history.replaceState({}, document.title, window.location.pathname);
			}
		};
	</script>
</body>
</html>