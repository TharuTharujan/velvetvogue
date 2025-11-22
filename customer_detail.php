<?php
// customer_detail.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'seller') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid customer ID.";
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'velvetvogue';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$user_id = intval($_GET['id']);
$stmt = $conn->prepare('SELECT * FROM user WHERE user_id = ? AND role = "buyer"');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$customer) {
    echo "Customer not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Detail</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            min-height: 420px;
            margin: 60px auto 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 32px rgba(44,62,80,0.10);
            padding: 48px 56px 44px 56px;
            text-align: left;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 24px;
            text-align: center;
        }
        p {
            font-size: 1.08rem;
            color: #34495e;
            margin: 16px 0 0 0;
            padding: 0;
        }
        strong {
            color: #2980b9;
        }
        a {
            display: inline-block;
            margin-top: 32px;
            padding: 10px 28px;
            background: #3498db;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        a:hover {
            background: #217dbb;
        }
        .status-active {
            color: #27ae60;
            font-weight: bold;
        }
        .status-inactive {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Customer Detail</h1>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($customer['username']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
    <p><strong>Status:</strong> <span class="status-<?php echo strtolower($customer['status'] ?? 'active'); ?>"><?php echo htmlspecialchars(ucfirst($customer['status'] ?? 'Active')); ?></span></p>
    <p><strong>Joined:</strong> <?php echo isset($customer['created_at']) ? date('M j, Y', strtotime($customer['created_at'])) : 'N/A'; ?></p>
        <a href="customer_management.php">Back to Customer Management</a>
    </div>
</body>
</html>
