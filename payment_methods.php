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
    <title>Payment Methods - Velvet Vogue</title>
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

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: var(--gray);
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 13px;
        }

        .payment-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .payment-item {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            background: white;
            transition: all 0.3s ease;
        }

        .payment-item:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .payment-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }

        .payment-card {
            background: linear-gradient(135deg, #2c4c75, #00bfff);
            color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .card-brand {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .card-number {
            font-size: 16px;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .card-expiry {
            font-size: 14px;
            opacity: 0.9;
        }

        .payment-type {
            font-size: 16px;
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 15px;
        }

        .billing-address {
            font-size: 13px;
            color: var(--gray);
        }

        .billing-address strong {
            display: block;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .payment-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-primary {
            background: var(--primary);
            color: white;
        }

        .badge-secondary {
            background: var(--light-gray);
            color: var(--gray);
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

        .no-content {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .no-content i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #e9ecef;
        }

        .no-content h3 {
            margin-bottom: 10px;
            color: var(--dark);
        }

        .no-content p {
            margin-bottom: 20px;
            color: var(--gray);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: none;
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
            }

            .sidebar-menu li a {
                font-size: 14px;
                padding: 12px;
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

            .btn {
                width: 100%;
                padding: 10px;
                font-size: 13px;
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
                    <li><a href="payment_methods.php" class="active"><i class="fas fa-credit-card"></i> Payment Methods</a></li>
                    <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                </ul>
            </div>

            <div class="account-content">
                <div class="account-card">
                    <div class="account-card-header">
                        <h2>Payment Methods</h2>
                        <button class="btn btn-primary" id="add-payment-btn">
                            <i class="fas fa-plus"></i> Add Payment Method
                        </button>
                    </div>
                    
                    <div class="alert alert-success" id="success-alert">
                        <i class="fas fa-check-circle"></i>
                        <span id="success-message"></span>
                    </div>
                    
                    <div class="alert alert-error" id="error-alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span id="error-message"></span>
                    </div>
                    
                    <div id="payment-list">
                        <!-- Payment methods will be loaded here dynamically -->
                    </div>
                    
                    <!-- Payment Form (Hidden by default) -->
                    <div id="payment-form-container" style="display: none;">
                        <form id="payment-form">
                            <input type="hidden" id="payment-id" name="payment_id">
                            <input type="hidden" name="action" value="add_payment">
                            
                            <div class="form-group">
                                <label for="payment-type">Payment Method *</label>
                                <select id="payment-type" name="payment_type" class="form-control" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="debit_card">Debit Card</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="bank_account">Bank Account</option>
                                </select>
                            </div>
                            
                            <!-- Card Details (Shown for card payments) -->
                            <div id="card-details" style="display: none;">
                                <div class="form-group">
                                    <label for="card-number">Card Number *</label>
                                    <input type="text" id="card-number" name="card_number" class="form-control" placeholder="1234 5678 9012 3456">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="expiry-month">Expiry Month *</label>
                                            <select id="expiry-month" name="expiry_month" class="form-control">
                                                <option value="">Month</option>
                                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                                    <option value="<?php echo $i; ?>"><?php echo sprintf('%02d', $i); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="expiry-year">Expiry Year *</label>
                                            <select id="expiry-year" name="expiry_year" class="form-control">
                                                <option value="">Year</option>
                                                <?php 
                                                $currentYear = date('Y');
                                                for ($i = 0; $i <= 10; $i++): 
                                                    $year = $currentYear + $i;
                                                    ?>
                                                    <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="cvv">CVV *</label>
                                    <input type="text" id="cvv" name="cvv" class="form-control" placeholder="123" maxlength="4">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="billing-address">Billing Address *</label>
                                <select id="billing-address" name="billing_address_id" class="form-control" required>
                                    <option value="">Select Billing Address</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment-default">
                                    <input type="checkbox" id="payment-default" name="is_default" value="1">
                                    Set as default payment method
                                </label>
                            </div>
                            
                            <div class="account-footer">
                                <button type="button" class="btn btn-outline" id="cancel-payment-btn">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Payment Method</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Function to show notification
        function showNotification(message, type = 'success') {
            const alert = document.getElementById(type === 'success' ? 'success-alert' : 'error-alert');
            const messageElement = document.getElementById(type === 'success' ? 'success-message' : 'error-message');
            
            messageElement.textContent = message;
            alert.style.display = 'flex';
            
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                    alert.style.opacity = '1';
                }, 300);
            }, 3000);
        }
        
        // Payment Methods Functions
        document.getElementById('add-payment-btn').addEventListener('click', function() {
            // Reset form
            document.getElementById('payment-form').reset();
            document.getElementById('payment-id').value = '';
            document.querySelector('#payment-form [name="action"]').value = 'add_payment';
            
            // Hide payment list and show form
            document.getElementById('payment-list').style.display = 'none';
            document.getElementById('payment-form-container').style.display = 'block';
            
            // Load addresses for billing address dropdown
            loadBillingAddresses();
        });
        
        document.getElementById('cancel-payment-btn').addEventListener('click', function() {
            // Hide form and show payment list
            document.getElementById('payment-form-container').style.display = 'none';
            document.getElementById('payment-list').style.display = 'block';
        });
        
        // Show/hide card details based on payment type
        document.getElementById('payment-type').addEventListener('change', function() {
            const cardDetails = document.getElementById('card-details');
            if (['credit_card', 'debit_card'].includes(this.value)) {
                cardDetails.style.display = 'block';
            } else {
                cardDetails.style.display = 'none';
            }
        });
        
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate card details if card payment is selected
            const paymentType = document.getElementById('payment-type').value;
            if (['credit_card', 'debit_card'].includes(paymentType)) {
                const cardNumber = document.getElementById('card-number').value;
                const expiryMonth = document.getElementById('expiry-month').value;
                const expiryYear = document.getElementById('expiry-year').value;
                const cvv = document.getElementById('cvv').value;
                
                if (!cardNumber || !expiryMonth || !expiryYear || !cvv) {
                    showNotification('Please fill in all card details', 'error');
                    return;
                }
            }
            
            const formData = new FormData(this);
            formData.append('action', document.querySelector('#payment-form [name="action"]').value);
            
            fetch('payment_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    loadPaymentMethods();
                    // Hide form and show payment list
                    document.getElementById('payment-form-container').style.display = 'none';
                    document.getElementById('payment-list').style.display = 'block';
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error saving payment method', 'error');
            });
        });
        
        // Load payment methods when page loads
        window.addEventListener('DOMContentLoaded', function() {
            loadPaymentMethods();
        });
        
        function loadPaymentMethods() {
            fetch('payment_handler.php?action=get_payments')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayPaymentMethods(data.payments);
                } else {
                    document.getElementById('payment-list').innerHTML = '<p>Error loading payment methods</p>';
                }
            })
            .catch(error => {
                document.getElementById('payment-list').innerHTML = '<p>Error loading payment methods</p>';
            });
        }
        
        function displayPaymentMethods(payments) {
            const paymentList = document.getElementById('payment-list');
            
            if (payments.length === 0) {
                paymentList.innerHTML = `
                    <div class="no-content">
                        <i class="fas fa-credit-card"></i>
                        <h3>No Saved Payment Methods</h3>
                        <p>You haven't saved any payment methods yet.</p>
                        <button class="btn btn-primary" id="add-payment-btn-empty">Add Payment Method</button>
                    </div>
                `;
                // Attach event listener to the new button
                setTimeout(function() {
                    const emptyStateButton = document.getElementById('add-payment-btn-empty');
                    if (emptyStateButton) {
                        emptyStateButton.addEventListener('click', function() {
                            document.getElementById('payment-form').reset();
                            document.getElementById('payment-id').value = '';
                            document.querySelector('#payment-form [name="action"]').value = 'add_payment';
                            document.getElementById('payment-list').style.display = 'none';
                            document.getElementById('payment-form-container').style.display = 'block';
                            loadBillingAddresses();
                        });
                    }
                }, 0);
                return;
            }
            
            let html = '<div class="payment-items">';
            payments.forEach(payment => {
                let paymentDetails = '';
                if (['credit_card', 'debit_card'].includes(payment.payment_type)) {
                    paymentDetails = `
                        <div class="payment-card">
                            <div class="card-brand">${payment.card_brand || 'Card'}</div>
                            <div class="card-number">**** **** **** ${payment.card_last_four}</div>
                            <div class="card-expiry">Expires: ${payment.expiry_month}/${payment.expiry_year}</div>
                        </div>
                    `;
                } else {
                    paymentDetails = `<div class="payment-type">${formatPaymentType(payment.payment_type)}</div>`;
                }
                
                html += `
                    <div class="payment-item" data-payment-id="${payment.payment_id}">
                        <div class="payment-header">
                            <h3>${formatPaymentType(payment.payment_type)}</h3>
                            ${payment.is_default == 1 ? '<span class="badge badge-primary">Default</span>' : ''}
                        </div>
                        <div class="payment-details">
                            ${paymentDetails}
                            ${payment.address_line1 ? `
                                <div class="billing-address">
                                    <strong>Billing Address:</strong>
                                    <p>${payment.address_line1}, ${payment.city}, ${payment.state}</p>
                                </div>
                            ` : ''}
                        </div>
                        <div class="payment-actions">
                            <button class="btn btn-outline btn-sm edit-payment" data-payment-id="${payment.payment_id}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm delete-payment" data-payment-id="${payment.payment_id}">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            ${payment.is_default != 1 ? 
                            `<button class="btn btn-secondary btn-sm set-default-payment" data-payment-id="${payment.payment_id}">
                                <i class="fas fa-star"></i> Set as Default
                            </button>` : ''}
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            paymentList.innerHTML = html;
            
            // Attach event listeners to action buttons
            document.querySelectorAll('.edit-payment').forEach(button => {
                button.addEventListener('click', function() {
                    const paymentId = this.getAttribute('data-payment-id');
                    editPaymentMethod(paymentId);
                });
            });
            
            document.querySelectorAll('.delete-payment').forEach(button => {
                button.addEventListener('click', function() {
                    const paymentId = this.getAttribute('data-payment-id');
                    deletePaymentMethod(paymentId);
                });
            });
            
            document.querySelectorAll('.set-default-payment').forEach(button => {
                button.addEventListener('click', function() {
                    const paymentId = this.getAttribute('data-payment-id');
                    setDefaultPaymentMethod(paymentId);
                });
            });
        }
        
        function formatPaymentType(type) {
            const types = {
                'credit_card': 'Credit Card',
                'debit_card': 'Debit Card',
                'paypal': 'PayPal',
                'bank_account': 'Bank Account'
            };
            return types[type] || type;
        }
        
        function editPaymentMethod(paymentId) {
            fetch('payment_handler.php?action=get_payments')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const payment = data.payments.find(p => p.payment_id == paymentId);
                    if (payment) {
                        // Populate form with payment data
                        document.getElementById('payment-id').value = payment.payment_id;
                        document.getElementById('payment-type').value = payment.payment_type;
                        document.getElementById('billing-address').value = payment.billing_address_id || '';
                        document.getElementById('payment-default').checked = payment.is_default == 1;
                        
                        // Show/hide card details
                        const cardDetails = document.getElementById('card-details');
                        if (['credit_card', 'debit_card'].includes(payment.payment_type)) {
                            cardDetails.style.display = 'block';
                            // Note: We don't populate card details for security reasons
                            document.getElementById('card-number').value = '';
                            document.getElementById('expiry-month').value = payment.expiry_month || '';
                            document.getElementById('expiry-year').value = payment.expiry_year || '';
                            document.getElementById('cvv').value = '';
                        } else {
                            cardDetails.style.display = 'none';
                        }
                        
                        // Change form action to update
                        document.querySelector('#payment-form [name="action"]').value = 'update_payment';
                        
                        // Show form and hide payment list
                        document.getElementById('payment-list').style.display = 'none';
                        document.getElementById('payment-form-container').style.display = 'block';
                        
                        // Load addresses for billing address dropdown
                        loadBillingAddresses();
                    }
                }
            });
        }
        
        function deletePaymentMethod(paymentId) {
            if (confirm('Are you sure you want to delete this payment method?')) {
                const formData = new FormData();
                formData.append('action', 'delete_payment');
                formData.append('payment_id', paymentId);
                
                fetch('payment_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        loadPaymentMethods();
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error deleting payment method', 'error');
                });
            }
        }
        
        function setDefaultPaymentMethod(paymentId) {
            const formData = new FormData();
            formData.append('action', 'set_default');
            formData.append('payment_id', paymentId);
            
            fetch('payment_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    loadPaymentMethods();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error setting default payment method', 'error');
            });
        }
        
        function loadBillingAddresses() {
            fetch('address_handler.php?action=get_addresses')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('billing-address');
                    // Clear existing options except the first one
                    select.innerHTML = '<option value="">Select Billing Address</option>';
                    
                    data.addresses.forEach(address => {
                        const option = document.createElement('option');
                        option.value = address.address_id;
                        option.textContent = `${address.first_name} ${address.last_name}, ${address.address_line1}, ${address.city}`;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading billing addresses:', error);
            });
        }
    </script>
</body>
</html>