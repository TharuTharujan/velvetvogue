<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_username'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Velvet Vogue</title>
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

        .notification-settings {
            padding: 20px 0;
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid var(--border);
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-item h4 {
            margin-bottom: 5px;
            color: var(--dark);
        }

        .setting-item p {
            color: var(--gray);
            font-size: 14px;
            margin: 0;
        }

        /* Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .account-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }

        @media (max-width: 992px) {
            .account-layout {
                flex-direction: column;
            }
            
            .account-sidebar {
                flex: 0 0 auto;
            }
        }

        @media (max-width: 768px) {
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
            
            .account-footer {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .setting-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .setting-item div {
                width: 100%;
            }
        }

        /* Mobile Responsive Styles */
        @media (max-width: 965px) {
            .container {
                padding: 15px;
            }

            .account-header h1 {
                font-size: 1.8rem;
            }

            .sidebar-menu {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }

        @media (max-width: 485px) {
            body {
                overflow-x: hidden;
            }

            .container {
                padding: 10px;
                max-width: 100%;
            }

            .back-to-home {
                font-size: 13px;
                padding: 8px 12px;
                width: 100%;
                text-align: center;
                display: block;
            }

            .account-header {
                margin-bottom: 20px;
                padding-bottom: 15px;
            }

            .account-header h1 {
                font-size: 1.3rem;
            }

            .breadcrumb {
                font-size: 12px;
            }

            .account-sidebar {
                padding: 15px;
                border-radius: 8px;
            }

            .account-sidebar h3 {
                font-size: 16px;
                margin-bottom: 15px;
                padding-bottom: 12px;
            }

            .sidebar-menu {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .sidebar-menu a {
                padding: 10px 12px;
                font-size: 14px;
            }

            .sidebar-menu a i {
                margin-right: 10px;
                font-size: 14px;
            }

            .account-card {
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 8px;
            }

            .account-card-header {
                margin-bottom: 20px;
                padding-bottom: 12px;
            }

            .account-card-header h2 {
                font-size: 18px;
            }

            .notification-settings {
                padding: 15px 0;
            }

            .setting-item {
                padding: 15px 0;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .setting-item h4 {
                font-size: 15px;
            }

            .setting-item p {
                font-size: 13px;
            }

            .switch {
                align-self: flex-start;
            }

            .account-footer {
                margin-top: 15px;
            }

            .btn {
                width: 100%;
                padding: 12px 20px;
                font-size: 14px;
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
                    <li><a href="edit_profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a></li>
                    <li><a href="my_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                    <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                    <li><a href="address_book.php"><i class="fas fa-map-marker-alt"></i> Address Book</a></li>
                    <li><a href="payment_methods.php"><i class="fas fa-credit-card"></i> Payment Methods</a></li>
                    <li><a href="notifications.php" class="active"><i class="fas fa-bell"></i> Notifications</a></li>
                </ul>
            </div>

            <div class="account-content">
                <div class="account-card">
                    <div class="account-card-header">
                        <h2>Notification Settings</h2>
                    </div>
                    
                    <div class="notification-settings">
                        <div class="setting-item">
                            <div>
                                <h4>Order Updates</h4>
                                <p>Receive notifications about your order status</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked id="order-updates">
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div>
                                <h4>Product Recommendations</h4>
                                <p>Get personalized product suggestions</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked id="product-recommendations">
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div>
                                <h4>Promotional Offers</h4>
                                <p>Receive information about sales and special offers</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="promotional-offers">
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div>
                                <h4>Newsletter</h4>
                                <p>Subscribe to our monthly newsletter</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked id="newsletter">
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="account-footer">
                            <button class="btn btn-primary" id="save-preferences">Save Preferences</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Load saved preferences from localStorage if available
        window.addEventListener('DOMContentLoaded', function() {
            // Load saved preferences
            const preferences = JSON.parse(localStorage.getItem('notificationPreferences')) || {
                orderUpdates: true,
                productRecommendations: true,
                promotionalOffers: false,
                newsletter: true
            };
            
            // Set checkbox states
            document.getElementById('order-updates').checked = preferences.orderUpdates;
            document.getElementById('product-recommendations').checked = preferences.productRecommendations;
            document.getElementById('promotional-offers').checked = preferences.promotionalOffers;
            document.getElementById('newsletter').checked = preferences.newsletter;
        });
        
        // Save preferences to localStorage
        document.getElementById('save-preferences').addEventListener('click', function() {
            const preferences = {
                orderUpdates: document.getElementById('order-updates').checked,
                productRecommendations: document.getElementById('product-recommendations').checked,
                promotionalOffers: document.getElementById('promotional-offers').checked,
                newsletter: document.getElementById('newsletter').checked
            };
            
            localStorage.setItem('notificationPreferences', JSON.stringify(preferences));
            
            // Show success message
            alert('Notification preferences saved successfully!');
        });
    </script>
</body>
</html>