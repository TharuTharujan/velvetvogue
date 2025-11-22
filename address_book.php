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
    <title>Address Book - Velvet Vogue</title>
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

        .address-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .address-item {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            background: white;
            transition: all 0.3s ease;
        }

        .address-item:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .address-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .address-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }

        .address-details p {
            margin: 5px 0;
            color: var(--gray);
            font-size: 14px;
        }

        .address-actions {
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

        .no-content .btn {
            padding: 10px 20px;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .no-content .btn i {
            font-size: 12px;
        }

        .btn-empty-state {
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 6px;
        }

        .btn-empty-state i {
            font-size: 12px;
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
                gap: 15px;
            .address-items {
                grid-template-columns: 1fr;
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

            .address-items {
                grid-template-columns: 1fr;
            }

            .sidebar-menu {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
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
                    <li><a href="address_book.php" class="active"><i class="fas fa-map-marker-alt"></i> Address Book</a></li>
                    <li><a href="payment_methods.php"><i class="fas fa-credit-card"></i> Payment Methods</a></li>
                    <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                </ul>
            </div>

            <div class="account-content">
                <div class="account-card">
                    <div class="account-card-header">
                        <h2>Address Book</h2>
                        <button class="btn btn-primary add-address-btn">
                            <i class="fas fa-plus"></i> Add New Address
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
                    
                    <div id="address-list">
                        <!-- Addresses will be loaded here dynamically -->
                    </div>
                    
                    <!-- Address Form (Hidden by default) -->
                    <div id="address-form-container" style="display: none;">
                        <form id="address-form">
                            <input type="hidden" id="address-id" name="address_id">
                            <input type="hidden" name="action" value="add_address">
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="address-type">Address Type *</label>
                                        <select id="address-type" name="address_type" class="form-control" required>
                                            <option value="home">Home</option>
                                            <option value="work">Work</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="is-default">
                                            <input type="checkbox" id="is-default" name="is_default" value="1">
                                            Set as default address
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="first-name">First Name *</label>
                                        <input type="text" id="first-name" name="first_name" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="last-name">Last Name *</label>
                                        <input type="text" id="last-name" name="last_name" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="company">Company (Optional)</label>
                                <input type="text" id="company" name="company" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="address-line1">Address Line 1 *</label>
                                <input type="text" id="address-line1" name="address_line1" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="address-line2">Address Line 2 (Optional)</label>
                                <input type="text" id="address-line2" name="address_line2" class="form-control">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="city">City *</label>
                                        <input type="text" id="city" name="city" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="state">State/Province *</label>
                                        <input type="text" id="state" name="state" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="postal-code">Postal Code *</label>
                                        <input type="text" id="postal-code" name="postal_code" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="country">Country *</label>
                                        <input type="text" id="country" name="country" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number (Optional)</label>
                                <input type="text" id="phone" name="phone" class="form-control">
                            </div>
                            
                            <div class="account-footer">
                                <button type="button" class="btn btn-outline" id="cancel-address-btn">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Address</button>
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
        
        // Address Book Functions
        // Use class selector instead of ID for add address buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.add-address-btn')) {
                // Reset form
                document.getElementById('address-form').reset();
                document.getElementById('address-id').value = '';
                document.querySelector('[name="action"]').value = 'add_address';
                
                // Hide address list and show form
                document.getElementById('address-list').style.display = 'none';
                document.getElementById('address-form-container').style.display = 'block';
            }
        });
        
        document.getElementById('cancel-address-btn').addEventListener('click', function() {
            // Hide form and show address list
            document.getElementById('address-form-container').style.display = 'none';
            document.getElementById('address-list').style.display = 'block';
        });
        
        // Also handle the case when form is submitted successfully
        document.getElementById('address-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', document.querySelector('[name="action"]').value);
            
            fetch('address_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    loadAddresses();
                    // Hide form and show address list
                    document.getElementById('address-form-container').style.display = 'none';
                    document.getElementById('address-list').style.display = 'block';
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error saving address', 'error');
            });
        });
        
        // Load addresses when page loads
        window.addEventListener('DOMContentLoaded', function() {
            loadAddresses();
        });
        
        function loadAddresses() {
            fetch('address_handler.php?action=get_addresses')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayAddresses(data.addresses);
                } else {
                    document.getElementById('address-list').innerHTML = '<p>Error loading addresses</p>';
                }
            })
            .catch(error => {
                document.getElementById('address-list').innerHTML = '<p>Error loading addresses</p>';
            });
        }
        
        function displayAddresses(addresses) {
            const addressList = document.getElementById('address-list');
            
            if (addresses.length === 0) {
                addressList.innerHTML = `
                    <div class="no-content">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>No Saved Addresses</h3>
                        <p>You haven't saved any addresses yet.</p>
                        <button class="btn btn-primary btn-empty-state add-address-btn">
                            Add New Address
                        </button>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="address-items">';
            addresses.forEach(address => {
                html += `
                    <div class="address-item" data-address-id="${address.address_id}">
                        <div class="address-header">
                            <h3>${address.first_name} ${address.last_name}</h3>
                            ${address.is_default == 1 ? '<span class="badge badge-primary">Default</span>' : ''}
                            <span class="address-type badge badge-secondary">${address.address_type}</span>
                        </div>
                        <div class="address-details">
                            <p>${address.address_line1}</p>
                            ${address.address_line2 ? `<p>${address.address_line2}</p>` : ''}
                            <p>${address.city}, ${address.state} ${address.postal_code}</p>
                            <p>${address.country}</p>
                            ${address.phone ? `<p>Phone: ${address.phone}</p>` : ''}
                            ${address.company ? `<p>Company: ${address.company}</p>` : ''}
                        </div>
                        <div class="address-actions">
                            <button class="btn btn-outline btn-sm edit-address" data-address-id="${address.address_id}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm delete-address" data-address-id="${address.address_id}">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            ${address.is_default != 1 ? 
                            `<button class="btn btn-secondary btn-sm set-default" data-address-id="${address.address_id}">
                                <i class="fas fa-star"></i> Set as Default
                            </button>` : ''}
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            addressList.innerHTML = html;
            
            // Attach event listeners to action buttons
            document.querySelectorAll('.edit-address').forEach(button => {
                button.addEventListener('click', function() {
                    const addressId = this.getAttribute('data-address-id');
                    editAddress(addressId);
                });
            });
            
            document.querySelectorAll('.delete-address').forEach(button => {
                button.addEventListener('click', function() {
                    const addressId = this.getAttribute('data-address-id');
                    deleteAddress(addressId);
                });
            });
            
            document.querySelectorAll('.set-default').forEach(button => {
                button.addEventListener('click', function() {
                    const addressId = this.getAttribute('data-address-id');
                    setDefaultAddress(addressId);
                });
            });
        }
        
        function editAddress(addressId) {
            fetch('address_handler.php?action=get_addresses')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const address = data.addresses.find(a => a.address_id == addressId);
                    if (address) {
                        // Populate form with address data
                        document.getElementById('address-id').value = address.address_id;
                        document.getElementById('address-type').value = address.address_type;
                        document.getElementById('is-default').checked = address.is_default == 1;
                        document.getElementById('first-name').value = address.first_name;
                        document.getElementById('last-name').value = address.last_name;
                        document.getElementById('company').value = address.company || '';
                        document.getElementById('address-line1').value = address.address_line1;
                        document.getElementById('address-line2').value = address.address_line2 || '';
                        document.getElementById('city').value = address.city;
                        document.getElementById('state').value = address.state;
                        document.getElementById('postal-code').value = address.postal_code;
                        document.getElementById('country').value = address.country;
                        document.getElementById('phone').value = address.phone || '';
                        
                        // Change form action to update
                        document.querySelector('[name="action"]').value = 'update_address';
                        
                        // Show form and hide address list
                        document.getElementById('address-list').style.display = 'none';
                        document.getElementById('address-form-container').style.display = 'block';
                    }
                }
            });
        }
        
        function deleteAddress(addressId) {
            if (confirm('Are you sure you want to delete this address?')) {
                const formData = new FormData();
                formData.append('action', 'delete_address');
                formData.append('address_id', addressId);
                
                fetch('address_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        loadAddresses();
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error deleting address', 'error');
                });
            }
        }
        
        function setDefaultAddress(addressId) {
            const formData = new FormData();
            formData.append('action', 'set_default');
            formData.append('address_id', addressId);
            
            fetch('address_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    loadAddresses();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error setting default address', 'error');
            });
        }
    </script>
</body>
</html>