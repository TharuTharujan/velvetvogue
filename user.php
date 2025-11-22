<?php
// Start session
session_start();

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'velvetvogue';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $conn->prepare('SELECT user_id, username, password, role FROM user WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $db_username, $hashed_password, $role);
        $stmt->fetch();
        
        // Verify password (supporting both hashed and plain text for backward compatibility)
        if (password_verify($password, $hashed_password) || $password === $hashed_password) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_username'] = $db_username;
            $_SESSION['user_role'] = $role;
            
            // Redirect based on user role
            if ($role === 'seller') {
                header('Location: dashboard.php'); // Redirect to seller dashboard
            } else {
                header('Location: index.php'); // Redirect to home page for buyers
            }
            exit();
        } else {
            // Redirect back to login.php with error
            header('Location: login.php?error=invalid_credentials');
            exit();
        }
    } else {
        // Redirect back to login.php with error
        header('Location: login.php?error=invalid_credentials');
        exit();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Velvet Vogue</title>
    <link rel="icon" href="img/logo2.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="loginstyle.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <style>
        .error-message {
            background: rgba(255, 59, 59, 0.1);
            border: 2px solid #ff3b3b;
            color: #ff3b3b;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 500;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #2980b9;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        <div class="logo">
            <a href="index.php">
                <img src="img/logo2.png" alt="Velvet Vogue Logo" width="200px">
            </a>
        </div>

        <div class="search-bar">
            <input type="text" placeholder="Search...">
        </div>

        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="index.php#brand">About Us</a>
            <a href="products.php">Products</a>
            <a href="contactus.php">Contact Us</a>
            <a href="login.php" class="active">Login</a>
        </nav>
    </div>

    <div class="container">
        <div id="login-form" class="form-container">
            <h2>Welcome Back</h2>
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="post" action="user.php">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit">Sign In</button>
            </form>
            <div class="toggle">
                Don't have an account? <a href="login.php" class="back-link">Create Account</a>
                <br>
                <a href="login.php" class="back-link">← Back to Login Page</a>
            </div>
        </div>
    </div>
</body>
</html>