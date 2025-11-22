<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'seller') {
    header('Location: login.php');
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

$success = '';
$error = '';

// Handle message status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $message_id = intval($_POST['message_id']);
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare('UPDATE contact_messages SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $new_status, $message_id);
    
    if ($stmt->execute()) {
        $success = 'Message status updated successfully!';
    } else {
        $error = 'Error updating message status: ' . $conn->error;
    }
    $stmt->close();
}

// Handle message deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    $message_id = intval($_POST['message_id']);
    
    $stmt = $conn->prepare('DELETE FROM contact_messages WHERE id = ?');
    $stmt->bind_param('i', $message_id);
    
    if ($stmt->execute()) {
        $success = 'Message deleted successfully!';
    } else {
        $error = 'Error deleting message: ' . $conn->error;
    }
    $stmt->close();
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $message_id = intval($_POST['message_id']);
    $reply_message = trim($_POST['reply_message']);
    
    if (!empty($reply_message)) {
        // In a real implementation, you would send an email here
        // For now, we'll just update the status to "replied"
        $stmt = $conn->prepare('UPDATE contact_messages SET status = ? WHERE id = ?');
        $status = 'replied';
        $stmt->bind_param('si', $status, $message_id);
        
        if ($stmt->execute()) {
            $success = 'Reply sent successfully!';
        } else {
            $error = 'Error sending reply: ' . $conn->error;
        }
        $stmt->close();
    } else {
        $error = 'Please enter a reply message.';
    }
}

// Filtering and search
$where_conditions = [];
$search = '';
$status_filter = '';
$sort_by = 'created_at DESC';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = trim($_GET['search']);
    $search_escaped = $conn->real_escape_string($search);
    $where_conditions[] = "(name LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%' OR subject LIKE '%$search_escaped%' OR message LIKE '%$search_escaped%')";
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status_filter = $_GET['status'];
    $status_escaped = $conn->real_escape_string($status_filter);
    $where_conditions[] = "status = '$status_escaped'";
}

if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'name_asc':
            $sort_by = 'name ASC';
            break;
        case 'name_desc':
            $sort_by = 'name DESC';
            break;
        case 'email_asc':
            $sort_by = 'email ASC';
            break;
        case 'email_desc':
            $sort_by = 'email DESC';
            break;
        case 'newest':
            $sort_by = 'created_at DESC';
            break;
        case 'oldest':
            $sort_by = 'created_at ASC';
            break;
    }
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get all contact messages
$sql = "SELECT * FROM contact_messages $where_clause ORDER BY $sort_by";
$messages = $conn->query($sql);

// Get statistics
$total_messages = $conn->query("SELECT COUNT(*) as count FROM contact_messages")->fetch_assoc()['count'];
$unread_messages = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'")->fetch_assoc()['count'];
$replied_messages = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'replied'")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Management - Velvet Vogue Admin</title>
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

        .user-section {
            flex-shrink: 0;
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
        }

        .page-header {
            background: linear-gradient(135deg, #e67e22, #f39c12);
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #e67e22;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Filters */
        .filters {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 600;
            color: #2c3e50;
        }

        .filter-group input,
        .filter-group select {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #e67e22;
        }

        .filter-btn {
            background: #e67e22;
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s ease;
            height: fit-content;
        }

        .filter-btn:hover {
            background: #d35400;
        }

        /* Messages Grid */
        .messages-grid {
            display: grid;
            gap: 25px;
        }

        .message-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .message-card:hover {
            transform: translateY(-3px);
        }

        .message-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message-info h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.2rem;
        }

        .message-info .meta {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .message-date {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .message-body {
            padding: 25px;
        }

        .message-subject {
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .message-content {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .message-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-unread {
            background: #fff3cd;
            color: #856404;
        }

        .status-read {
            background: #cce7ff;
            color: #004085;
        }

        .status-replied {
            background: #d4edda;
            color: #155724;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: #3498db;
            color: #fff;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: #fff;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-warning {
            background: #f39c12;
            color: #fff;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .btn-danger {
            background: #e74c3c;
            color: #fff;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .status-form {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .status-select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }

        .no-messages {
            text-align: center;
            padding: 60px;
            color: #6c757d;
        }

        .no-messages i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ddd;
        }

        /* Reply Form Overlay */
        .reply-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            display: none;
        }

        .reply-form-container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .reply-form-header {
            background: linear-gradient(135deg, #e67e22, #f39c12);
            color: #fff;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .reply-form-header h3 {
            margin: 0;
            font-size: 1.4rem;
        }

        .close-reply-form {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .close-reply-form:hover {
            background: rgba(255,255,255,0.2);
        }

        .reply-form-body {
            padding: 25px;
        }

        .reply-form-group {
            margin-bottom: 20px;
        }

        .reply-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .reply-form-group input,
        .reply-form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .reply-form-group input:focus,
        .reply-form-group textarea:focus {
            outline: none;
            border-color: #e67e22;
        }

        .reply-form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .reply-form-footer {
            padding: 0 25px 25px 25px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-cancel {
            background: #6c757d;
            color: #fff;
        }

        .btn-cancel:hover {
            background: #5a6268;
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

            .filter-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .message-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .message-actions {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
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
                    <li><a href="contact_management.php" class="active">
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

    <!-- Reply Form Overlay -->
    <div class="reply-overlay" id="replyOverlay">
        <div class="reply-form-container">
            <div class="reply-form-header">
                <h3><i class="fas fa-reply"></i> Reply to Message</h3>
                <button class="close-reply-form" onclick="closeReplyForm()">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="reply-form-body">
                    <input type="hidden" name="message_id" id="replyMessageId">
                    <div class="reply-form-group">
                        <label for="replyTo">To:</label>
                        <input type="text" id="replyTo" readonly>
                    </div>
                    <div class="reply-form-group">
                        <label for="replySubject">Subject:</label>
                        <input type="text" id="replySubject" name="reply_subject" placeholder="Enter subject">
                    </div>
                    <div class="reply-form-group">
                        <label for="replyMessage">Message:</label>
                        <textarea id="replyMessage" name="reply_message" placeholder="Enter your reply..."></textarea>
                    </div>
                </div>
                <div class="reply-form-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeReplyForm()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" name="send_reply" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-envelope"></i> Contact Management
            </h1>
            <p>Manage customer inquiries and messages</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-number"><?php echo $total_messages; ?></div>
                <div class="stat-label">Total Messages</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-envelope-open"></i>
                </div>
                <div class="stat-number"><?php echo $unread_messages; ?></div>
                <div class="stat-label">Unread Messages</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-reply"></i>
                </div>
                <div class="stat-number"><?php echo $replied_messages; ?></div>
                <div class="stat-label">Replied Messages</div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Search Messages</label>
                        <input type="text" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by name, email, subject, or content...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All Status</option>
                            <option value="unread" <?php echo ($status_filter == 'unread') ? 'selected' : ''; ?>>Unread</option>
                            <option value="read" <?php echo ($status_filter == 'read') ? 'selected' : ''; ?>>Read</option>
                            <option value="replied" <?php echo ($status_filter == 'replied') ? 'selected' : ''; ?>>Replied</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort">
                            <option value="newest" <?php echo (($_GET['sort'] ?? '') == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo (($_GET['sort'] ?? '') == 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="name_asc" <?php echo (($_GET['sort'] ?? '') == 'name_asc') ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="name_desc" <?php echo (($_GET['sort'] ?? '') == 'name_desc') ? 'selected' : ''; ?>>Name Z-A</option>
                            <option value="email_asc" <?php echo (($_GET['sort'] ?? '') == 'email_asc') ? 'selected' : ''; ?>>Email A-Z</option>
                            <option value="email_desc" <?php echo (($_GET['sort'] ?? '') == 'email_desc') ? 'selected' : ''; ?>>Email Z-A</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="filter-btn">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Messages Grid -->
        <div class="messages-grid">
            <?php if ($messages && $messages->num_rows > 0): ?>
                <?php while($message = $messages->fetch_assoc()): ?>
                    <div class="message-card">
                        <div class="message-header">
                            <div class="message-info">
                                <h3><?php echo htmlspecialchars($message['name']); ?></h3>
                                <div class="meta">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($message['email']); ?>
                                </div>
                            </div>
                            <div class="message-date">
                                <i class="fas fa-clock"></i>
                                <?php echo date('M j, Y H:i', strtotime($message['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="message-body">
                            <?php if (!empty($message['subject'])): ?>
                                <div class="message-subject">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($message['subject']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="message-content">
                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            </div>
                            
                            <div class="message-actions">
                                <span class="status-badge status-<?php echo $message['status']; ?>">
                                    <?php echo ucfirst($message['status']); ?>
                                </span>
                                
                                <form class="status-form" method="POST" action="">
                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                    <select name="status" class="status-select">
                                        <option value="unread" <?php echo ($message['status'] == 'unread') ? 'selected' : ''; ?>>Unread</option>
                                        <option value="read" <?php echo ($message['status'] == 'read') ? 'selected' : ''; ?>>Read</option>
                                        <option value="replied" <?php echo ($message['status'] == 'replied') ? 'selected' : ''; ?>>Replied</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update
                                    </button>
                                </form>
                                
                                <button type="button" class="btn btn-success" onclick="openReplyForm(
                                    <?php echo $message['id']; ?>, 
                                    '<?php echo htmlspecialchars($message['email']); ?>',
                                    '<?php echo htmlspecialchars($message['subject'] ?? 'Re: Your Inquiry'); ?>'
                                )">
                                    <i class="fas fa-reply"></i> Reply
                                </button>
                                
                                <form style="display: inline;" method="POST" action="" 
                                      onsubmit="return confirm('Are you sure you want to delete this message?');">
                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                    <button type="submit" name="delete_message" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-messages">
                    <i class="fas fa-envelope"></i>
                    <h3>No Messages Found</h3>
                    <p>
                        <?php if ($search || $status_filter): ?>
                            Try adjusting your search criteria or <a href="contact_management.php">view all messages</a>.
                        <?php else: ?>
                            No customer messages have been received yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
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

        // Reply form functions
        function openReplyForm(messageId, email, subject) {
            document.getElementById('replyMessageId').value = messageId;
            document.getElementById('replyTo').value = email;
            document.getElementById('replySubject').value = subject;
            document.getElementById('replyMessage').value = '';
            document.getElementById('replyOverlay').style.display = 'flex';
        }

        function closeReplyForm() {
            document.getElementById('replyOverlay').style.display = 'none';
        }

        // Close reply form when clicking outside
        document.getElementById('replyOverlay').addEventListener('click', function(event) {
            if (event.target === this) {
                closeReplyForm();
            }
        });
    </script>
</body>
</html>