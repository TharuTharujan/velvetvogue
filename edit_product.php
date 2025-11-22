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
$product = null;

// Get product ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: view_product.php');
    exit();
}

$product_id = intval($_GET['id']);

// Get product details
$stmt = $conn->prepare('SELECT p.*, s.subcategory_id, s.category_id FROM product p LEFT JOIN subcategory s ON p.subcategory_id = s.subcategory_id WHERE p.product_id = ?');
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = 'Product not found.';
} else {
    $product = $result->fetch_assoc();
}
$stmt->close();

// Get categories for dropdown
$categories = $conn->query('SELECT * FROM category ORDER BY category_name');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $subcategory_id = intval($_POST['subcategory_id']);
    $active = isset($_POST['active']) ? 1 : 0; // Get active status
    $image_path = $product['image_path']; // Keep existing image by default
    
    // Handle image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $image_name = 'product_' . uniqid() . '.' . $ext;
            $upload_dir = __DIR__ . '/images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $target_path = $upload_dir . $image_name;
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_path)) {
                // Delete old image if exists
                if ($image_path && file_exists(__DIR__ . '/' . $image_path)) {
                    unlink(__DIR__ . '/' . $image_path);
                }
                $image_path = 'images/' . $image_name;
            }
        }
    }
    
    // Update product
    $stmt = $conn->prepare('UPDATE product SET product_name = ?, description = ?, price = ?, subcategory_id = ?, image_path = ?, active = ? WHERE product_id = ?');
    $stmt->bind_param('ssdisii', $product_name, $description, $price, $subcategory_id, $image_path, $active, $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Product updated successfully!';
        $stmt->close();
        $conn->close();
        header('Location: view_product.php');
        exit();
    } else {
        $error = 'Error updating product: ' . $conn->error;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Velvet Vogue Admin</title>
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
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .admin-sidebar::-webkit-scrollbar {
            display: none;
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
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .nav-container::-webkit-scrollbar {
            display: none;
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
        }

        .page-header {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: #fff;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: #fff;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
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

        /* Form Container */
        .form-container {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 40px;
        }

        .form-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group label:has(input[type="checkbox"]) {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .form-group input[type="checkbox"] {
            margin: 0;
            cursor: pointer;
        }

        .form-hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        .current-image {
            margin-bottom: 15px;
            text-align: center;
        }

        .current-image img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .current-image .no-image {
            color: #bbb;
            font-size: 3rem;
            padding: 50px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: #3498db;
            color: #fff;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #95a5a6;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
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
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .form-row {
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
                <i class="fas fa-edit"></i> Edit Product
            </h1>
            <a href="view_product.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
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

        <?php if ($product): ?>
        <!-- Product Form -->
        <div class="form-container">
            <h2 class="form-title">
                <i class="fas fa-box"></i> Product Information
            </h2>

            <form method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="product_name">Product Name</label>
                        <input type="text" id="product_name" name="product_name" 
                               value="<?php echo htmlspecialchars($product['product_name']); ?>" 
                               placeholder="Enter product name..." required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price ($)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($product['price']); ?>" 
                               placeholder="0.00" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Enter product description..." required><?php 
                        echo htmlspecialchars($product['description']); 
                    ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"
                                    <?php echo ($product['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="subcategory_id">Subcategory</label>
                        <select id="subcategory_id" name="subcategory_id" required>
                            <option value="">Select Subcategory</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="active" value="1" <?php echo ($product['active'] ?? 1) ? 'checked' : ''; ?>>
                        Active Product (visible to customers)
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="product_image">Product Image</label>
                    
                    <div class="current-image" id="imageContainer">
                        <?php if ($product['image_path'] && file_exists(__DIR__ . '/' . $product['image_path'])): ?>
                            <p><strong>Current Image:</strong></p>
                            <img id="displayedImage" src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-image no-image"></i>
                            <p>No current image</p>
                        <?php endif; ?>
                    </div>
                    
                    <input type="file" id="product_image" name="product_image" accept="image/*">
                    <small class="form-hint">Leave empty to keep current image. Accepted formats: JPG, PNG, GIF (Max: 2MB)</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Product
                    </button>
                    <a href="view_product.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category_id');
        const subcategorySelect = document.getElementById('subcategory_id');
        const currentSubcategoryId = <?php echo json_encode($product['subcategory_id'] ?? ''); ?>;
        
        // Handle category change
        categorySelect.addEventListener('change', function() {
            const categoryId = this.value;
            
            if (!categoryId) {
                subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                subcategorySelect.disabled = true;
                return;
            }
            
            // Show loading state
            subcategorySelect.innerHTML = '<option value="">Loading subcategories...</option>';
            subcategorySelect.disabled = false;
            
            // Fetch subcategories
            fetch(`add_product.php?get_subcategories=1&category_id=${categoryId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.length > 0) {
                        let options = '<option value="">Select Subcategory</option>';
                        data.forEach(subcat => {
                            const selected = (subcat.subcategory_id == currentSubcategoryId) ? 'selected' : '';
                            options += `<option value="${subcat.subcategory_id}" ${selected}>${subcat.subcategory_name}</option>`;
                        });
                        subcategorySelect.innerHTML = options;
                    } else {
                        subcategorySelect.innerHTML = '<option value="">No subcategories found</option>';
                    }
                    subcategorySelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error fetching subcategories:', error);
                    subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                    subcategorySelect.disabled = true;
                });
        });
        
        // Trigger change event if category is already selected
        if (categorySelect.value) {
            categorySelect.dispatchEvent(new Event('change'));
        }
        
        // Image preview functionality
        const imageInput = document.getElementById('product_image');
        const imageContainer = document.getElementById('imageContainer');
        
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Check if there's already an image displayed
                    let displayedImage = document.getElementById('displayedImage');
                    if (!displayedImage) {
                        // Create image element if it doesn't exist
                        imageContainer.innerHTML = '<p><strong>Preview:</strong></p>';
                        displayedImage = document.createElement('img');
                        displayedImage.id = 'displayedImage';
                        displayedImage.style.maxWidth = '200px';
                        displayedImage.style.maxHeight = '200px';
                        displayedImage.style.borderRadius = '8px';
                        displayedImage.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
                        imageContainer.appendChild(displayedImage);
                    } else {
                        // Update the heading text
                        const heading = imageContainer.querySelector('p');
                        if (heading) {
                            heading.innerHTML = '<strong>Preview:</strong>';
                        }
                    }
                    displayedImage.src = e.target.result;
                    displayedImage.alt = 'Preview';
                }
                reader.readAsDataURL(file);
            } else {
                // Reset to show current image if no file is selected
                <?php if ($product['image_path'] && file_exists(__DIR__ . '/' . $product['image_path'])): ?>
                    imageContainer.innerHTML = '<p><strong>Current Image:</strong></p>' +
                        '<img id="displayedImage" src="<?php echo htmlspecialchars($product['image_path']); ?>" ' +
                        'alt="<?php echo htmlspecialchars($product['product_name']); ?>" ' +
                        'style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">';
                <?php else: ?>
                    imageContainer.innerHTML = '<i class="fas fa-image no-image"></i><p>No current image</p>';
                <?php endif; ?>
            }
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
</body>
</html>
