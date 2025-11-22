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
$success = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare('SELECT user_id FROM user WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or email already exists! Please choose different credentials or <a href="login.php">login</a>.';
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_stmt = $conn->prepare('INSERT INTO user (username, email, password, role) VALUES (?, ?, ?, "buyer")');
            $insert_stmt->bind_param('sss', $username, $email, $hashed_password);
            
            if ($insert_stmt->execute()) {
                // Redirect to login page immediately after successful registration
                header('Location: login.php?registration=success');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link rel="icon" href="img/logo2.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="loginstyle.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <style>
        /* Additional styles to position card to the left */
        body {
            justify-content: flex-start;
            padding-left: 80px;
            padding-top: 140px;
        }
        
        .container {
            width: 550px;
            display: block;
        }
        
        #register-form {
            display: block;
        }
        
        #login-form {
            display: none;
        }
        
        .form-container {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
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
        
        .success-message {
            background: rgba(40, 167, 69, 0.1);
            border: 2px solid #28a745;
            color: #28a745;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            body {
                padding-left: 20px;
                padding-right: 20px;
                justify-content: center;
            }
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
        <div id="register-form" class="form-container">
            <h2>Join Velvet Vogue</h2>
            <?php 
            if ($error) echo '<div class="error-message">' . $error . '</div>'; 
            ?>
            <form method="post" action="user_register.php">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($username); ?>">
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email Address" required value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <button type="submit" name="register">Create Account</button>
            </form>
            <div class="toggle">
                Already have an account? <a href="login.php">Sign In</a>
            </div>
        </div>
    </div>
    
    <script>
        // Prevent any JavaScript errors from freezing the page
        window.addEventListener('error', function(e) {
            console.error('JavaScript error:', e.error);
            // Don't let errors freeze the page
            e.preventDefault();
        });
    </script>
</body>
</html>