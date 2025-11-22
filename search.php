<?php
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

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$products = [];

if (!empty($search_query)) {
    // Search products by name or description
    $stmt = $conn->prepare("SELECT * FROM product WHERE product_name LIKE ? OR description LIKE ? ORDER BY product_name ASC");
    $search_param = '%' . $search_query . '%';
    $stmt->bind_param('ss', $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
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
    <title>Search Results - Velvet Vogue</title>
    <link rel="icon" href="img/logo2.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }

        .search-page-wrapper {
            min-height: 100vh;
            padding-top: 0;
        }
        
        .search-header-section {
            background: linear-gradient(135deg, #2c4c75 0%, #00bfff 100%);
            padding: 60px 20px 40px;
            text-align: center;
            color: white;
            margin-bottom: 40px;
            margin-top: 0;
        }

        .search-header-section h1 {
            font-size: 42px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .search-header-section .search-subtitle {
            font-size: 18px;
            opacity: 0.95;
            margin-bottom: 30px;
        }

        .search-form-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .search-form-container form {
            display: flex;
            background: white;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .search-form-container input {
            flex: 1;
            padding: 18px 25px;
            border: none;
            font-size: 16px;
            outline: none;
            font-family: 'Poppins', sans-serif;
        }

        .search-form-container button {
            padding: 18px 35px;
            background: #2c4c75;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .search-form-container button:hover {
            background: #1a365d;
        }
        
        .search-results-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 60px;
        }

        .search-info {
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .search-info h2 {
            font-size: 24px;
            color: #2c4c75;
            margin-bottom: 10px;
        }

        .search-query {
            color: #00bfff;
            font-weight: 600;
        }

        .results-count {
            color: #666;
            font-size: 16px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
            background: #f8f9fa;
        }

        .product-info {
            padding: 25px;
        }

        .product-name {
            font-size: 19px;
            font-weight: 600;
            color: #2c4c75;
            margin-bottom: 12px;
            line-height: 1.4;
            min-height: 50px;
        }

        .product-price {
            font-size: 26px;
            color: #00bfff;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .view-btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2c4c75, #00bfff);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .view-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(44, 76, 117, 0.3);
        }

        .no-results {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .no-results i {
            font-size: 80px;
            color: #e0e0e0;
            margin-bottom: 25px;
        }

        .no-results h2 {
            font-size: 28px;
            color: #2c4c75;
            margin-bottom: 15px;
        }

        .no-results p {
            color: #666;
            font-size: 16px;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .back-btn {
            display: inline-block;
            padding: 14px 35px;
            background: linear-gradient(135deg, #2c4c75, #00bfff);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            margin-top: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(44, 76, 117, 0.3);
        }

        .back-btn i {
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .search-header-section h1 {
                font-size: 32px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .product-name {
                font-size: 17px;
                min-height: auto;
            }
        }

        @media (max-width: 480px) {
            .search-header-section {
                padding: 40px 15px 30px;
            }

            .search-header-section h1 {
                font-size: 26px;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <script src="indexjs.js"></script>
    
    <div class="search-page-wrapper">
        <!-- Search Header Section -->
        <div class="search-header-section">
            <h1><i class="fas fa-search"></i> Search Products</h1>
            <p class="search-subtitle">Find your perfect style from our collection</p>
            
            <div class="search-form-container">
                <form action="search.php" method="GET">
                    <input type="text" name="q" placeholder="Search for products..." value="<?php echo htmlspecialchars($search_query); ?>" required>
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
        </div>

        <div class="search-results-container">
            <?php if (!empty($search_query)): ?>
                <div class="search-info">
                    <h2>Search Results</h2>
                    <p class="results-count">
                        Found <strong><?php echo count($products); ?></strong> product<?php echo count($products) != 1 ? 's' : ''; ?> 
                        matching <span class="search-query">"<?php echo htmlspecialchars($search_query); ?>"</span>
                    </p>
                </div>
            <?php endif; ?>

        <?php if (!empty($products)): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <?php if (!empty($product['image_path']) && file_exists(__DIR__ . '/' . $product['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="product-image">
                        <?php else: ?>
                            <div class="product-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image" style="font-size: 48px; color: #ccc;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                            <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" class="view-btn">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h2>No products found</h2>
                <p>We couldn't find any products matching "<?php echo htmlspecialchars($search_query); ?>"</p>
                <p>Try searching with different keywords or browse our products.</p>
                <a href="products.php" class="back-btn">Browse All Products</a>
            </div>
        <?php endif; ?>
        
            <div style="text-align: center; margin-top: 50px;">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
