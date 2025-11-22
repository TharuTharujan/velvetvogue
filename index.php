<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Velvet Vogue | E-Commerce Website</title>
  <link rel="icon" href="img/logo2.png">
  <link rel="stylesheet" href="style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
</head>

<body>
  <div class="header">
    
    <div class="header-top">
      <div class="logo">
        <img src="img/logo2.png" alt="Velvet Vogue Logo" width="200px">
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
        echo '<a href="login.php">Login</a>';
      }
      
      $conn->close();
      ?>
    </nav>

  </div>

  <div id="home" class="hero">

    <div class="hero-text">
      <h1>Where Comfort<br><span>Meets Confidence</span></h1>
      <p>Discover the perfect blend of timeless elegance and contemporary flair with our handpicked pieces</p>
    </div>

    <div class="hero-image">
      <img src="img/home page.jpg" alt="Family wearing denim jackets">
      <a href="products.php" class="explore-button">Explore Now &#8594</a>
    </div>

  </div>

  <!--brand-->

  <div id="brand" class="about">
    <h1>About Velvet Vogue</h1>
    <p>
      At Velvet Vogue, we believe fashion is more than just clothing it's a way to express your identity,
      confidence, and creativity. Founded by passionate entrepreneur John Finlo, Velvet Vogue brings a fresh and
      modern approach to casual and formal wear for young adults. Our mission is to provide stylish, high-quality
      apparel and accessories that inspire individuality and self-expression. From everyday essentials to standout
      pieces for special occasions, each item in our collection is handpicked to reflect the latest trends and
      customer needs. As a brand committed to innovation and style, Velvet Vogue is constantly evolving, offering
      a seamless shopping experience both online and in-store. Join us on our fashion journey and discover the
      perfect outfit that speaks to your personality.
    </p>
  </div>
  <div class="brand">
    <div class="global">
      <h3>Global Partners</h3>
    </div>
    <div class="partners">
      <img src="images/logo-google.png" alt="Google Logo">
      <img src="images/logo-samsung.png" alt="Samsung Logo">
      <img src="images/logo-amazon.png" alt="Amazon Logo">
      <img src="images/logo-coca-cola.png" alt="Coca Cola Logo">
      <img src="images/logo-godrej.png" alt="Godrej Logo">
      <img src="images/logo-daraz.png" alt="Daraz Logo">
      <img src="images/logo-myntra.png" alt="Myntra Logo">
      <img src="images/logo-oppo.png" alt="Oppo Logo">
      <img src="images/logo-visa.png" alt="Visa Logo">
      <img src="images/logo-mastercard.png" alt="Mastercard Logo">
      <img src="images/logo-paypal.png" alt="PayPal Logo">
      <img src="images/logo-philips.png" alt="Philips Logo">
    </div>
  </div>

  <!--gallery-->

  <div class="image gallery">
    <div class="gallery">
      <h1>Trendsetters' Gallery</h1>
    </div>
    <div class="images">
      <img src="images/category-8.jpg" width="300px" height="330px" alt="gallery1">
      <img src="images/category-17.jpg" width="300px" height="330px" alt="gallery2">
      <img src="images/category-1.jpg" width="300px" height="330px" alt="gallery3">
      <img src="images/category-12.jpg" width="300px" height="330px" alt="gallery4">
      <img src="images/category-3.jpg" width="300px" height="330px" alt="gallery5">
      <img src="images/category-5.jpg" width="300px" height="330px" alt="gallery6">
      <img src="images/category-7.jpg" width="300px" height="330px" alt="gallery7">
      <img src="images/category-4.jpg" width="300px" height="330px" alt="gallery8">
      <img src="images/category-13.jpg" width="300px" height="330px" alt="gallery9">
      <img src="images/category-2.jpg" width="300px" height="330px" alt="gallery10">
    </div>

  </div>

<!--featured products-->

  <div class="featured-products" id="products">
     <h2 class="title">Featured Products</h2>
     <div class="product-gallery">
       <div class="one">
         <img src="images/Men/coat suit/dress_6.jpg" width="200px" alt="Grey Coat-Suit">
         <h4>Grey Coat-Suit</h4>
         <div class="rating">
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star-half-stroke"></i>
         </div>
         <p>Rs.15000</p>
       </div>

       <div class="one">
         <img src="images/Women/saree/dress_1.jpg" width="200px" alt="Marron Saree">
         <h4>Maroon Saree</h4>
         <div class="rating">
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
         </div>
         <p>Rs.10000</p>
       </div>

       <div class="one">
         <img src="images/watch/watch_17.jpg" width="200px" alt="Grey luxury watch">
         <h4>Luxury Watch</h4>
         <div class="rating">
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa fa-star-half" aria-hidden="true"></i>
         </div>
         <p>Rs.9000</p>
       </div>

       <div class="one">
         <img src="images/hoodie/hoodie_9.jpg" width="200px" alt="Black Hoodie">
         <h4>Black Hoodie</h4>
         <div class="rating">
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-regular fa-star"></i>
         </div>
         <p>Rs.8000</p>
       </div>

       <div class="one">
         <img src="images/shoe/shoe_13.jpg" width="200px" alt="Shoe">
         <h4>Nike Shoe</h4>
         <div class="rating">
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
         </div>
         <p>Rs.10000</p>
       </div>

       <div class="one">
         <img src="images/Women/blouse/dress_66.jpg" width="200px" alt="Blouse">
         <h4>Green Blouse</h4>
         <div class="rating">
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star-half-stroke"></i>
         </div>
         <p>Rs.7000</p>
       </div>

       <div class="one">
         <img src="images/cap/cap-7.jpg" width="200px" alt="Cap">
         <h4>Modern Cap</h4>
         <div class="rating">
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star-half-stroke"></i>
         </div>
         <p>Rs.1000</p>
       </div>

       <div class="one">
         <img src="images/Kids/girl/dress_1.jpg" width="200px" alt="dress">
         <h4>Trendy Kids dress</h4>
         <div class="rating">
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-solid fa-star"></i>
           <i class="fa-regular fa-star"></i>
         </div>
         <p>Rs.8000</p>
       </div>
     </div>
   </div>


  <div class="offer">
    <div class="offer-carousel">
      <!-- Product 1: Smart Band -->
      <div class="ad active">
        <div class="row">
          <div class="col2">
            <img src="images/exclusive.png" alt="Smart Band">
          </div>
          <div class="col2">
            <p>Exclusively available on Velvet Vogue</p>
            <h1>Smart Band</h1>
            <small>Experience the future of fitness tracking with our premium Smart Band. Featuring advanced health monitoring, sleep tracking, and seamless smartphone connectivity. Track your steps, heart rate, and daily activities while staying connected with notifications and calls. Available in multiple colors to match your style.</small>
            <a href="products.php" class="shop-now-btn">Shop Now</a>
          </div>
        </div>
      </div>

      <!-- Product 2: Luxury Watch -->
      <div class="ad">
        <div class="row">
          <div class="col2">
            <img src="images/watch/watch_47.png" alt="Luxury Watch">
          </div>
          <div class="col2">
            <p>Premium Collection</p>
            <h1>Luxury Watch</h1>
            <small>Elevate your style with our sophisticated luxury watch collection. Crafted with precision engineering and premium materials, these timepieces combine elegance with functionality. Features include water resistance, scratch-resistant sapphire crystal, and automatic movement. Perfect for both formal occasions and everyday wear.</small>
            <a href="products.php" class="shop-now-btn">Shop Now</a>
          </div>
        </div>
      </div>

      <!-- Product 3: Designer Shoes -->
      <div class="ad">
        <div class="row">
          <div class="col2">
            <img src="images/shoe/shoe_33.png" alt="Designer Shoes">
          </div>
          <div class="col2">
            <p>Trending Now</p>
            <h1>Designer Shoes</h1>
            <small>Step into comfort and style with our exclusive designer shoe collection. Made with premium leather and innovative cushioning technology, these shoes provide unmatched comfort for all-day wear. Available in various styles from casual sneakers to formal footwear, perfect for every occasion.</small>
            <a href="products.php" class="shop-now-btn">Shop Now</a>
          </div>
        </div>
      </div>

      <!-- Product 4: Premium Hoodie -->
      <div class="ad">
        <div class="row">
          <div class="col2">
            <img src="images/hoodie/hoodie_35.png" alt="Premium Hoodie">
          </div>
          <div class="col2">
            <p>Comfort Collection</p>
            <h1>Premium Hoodie</h1>
            <small>Stay cozy and stylish with our premium hoodie collection. Made from high-quality cotton blend fabric, these hoodies offer superior comfort and durability. Features include adjustable drawstrings, spacious pockets, and a soft inner lining. Perfect for casual outings and relaxed weekends.</small>
            <a href="products.php" class="shop-now-btn">Shop Now</a>
          </div>
        </div>
      </div>

      <!-- Product 5: Elegant Saree -->
      <div class="ad">
        <div class="row">
          <div class="col2">
            <img src="images/cap/cap-17.png" alt="Elegant Saree">
          </div>
          <div class="col2">
            <p>Minimalist Swagger</p>
            <h1>Alpha Vibe Cap</h1>
            <small>Show off your street style with this sleek black cap featuring a bold 'A' embroidery. Crafted for comfort and edge, it's perfect for casual wear, outings, or making a fashion statement. Designed with modern aesthetics and durable fabric to match every vibe.</small>
            <a href="products.php" class="shop-now-btn">Shop Now</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Dots -->
    <div class="carousel-dots">
      <span class="dot active" onclick="currentSlide(1)"></span>
      <span class="dot" onclick="currentSlide(2)"></span>
      <span class="dot" onclick="currentSlide(3)"></span>
      <span class="dot" onclick="currentSlide(4)"></span>
      <span class="dot" onclick="currentSlide(5)"></span>
    </div>
  </div>

  <!-- Testimonials Section -->
  <div class="testimonials">
    <div class="testimonials-header">
      <h2>What Our Customers Say</h2>
      <p>Real experiences from our valued customers</p>
    </div>
    
    <div class="testimonials-container">
      <!-- User 1 -->
      <div class="testimonial-card">
        <div class="testimonial-avatar">
          <img src="images/user-1.png" alt="User 1">
        </div>
        <div class="testimonial-content">
          <div class="stars">
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa fa-star-half"></i>
          </div>
          <p class="testimonial-text">"Velvet Vogue has completely transformed my wardrobe! The quality of their clothing is exceptional, and the fit is always perfect. I love how they combine comfort with style. Their customer service is outstanding too!"</p>
          <div class="testimonial-author">
            <h4>Sarah Johnson</h4>
            <span>Fashion Blogger</span>
          </div>
        </div>
      </div>

      <!-- User 2 -->
      <div class="testimonial-card">
        <div class="testimonial-avatar">
          <img src="images/user-2.png" alt="User 2">
        </div>
        <div class="testimonial-content">
          <div class="stars">
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
          </div>
          <p class="testimonial-text">"I've been shopping at Velvet Vogue for over a year now, and I'm always impressed by their latest collections. The Smart Band I bought is amazing - it tracks my fitness perfectly and looks stylish too!"</p>
          <div class="testimonial-author">
            <h4>Michael Chen</h4>
            <span>Fitness Enthusiast</span>
          </div>
        </div>
      </div>

      <!-- User 3 -->
      <div class="testimonial-card">
        <div class="testimonial-avatar">
          <img src="images/user-3.png" alt="User 3">
        </div>
        <div class="testimonial-content">
          <div class="stars">
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-regular fa-star"></i>
          </div>
          <p class="testimonial-text">"The luxury watch collection at Velvet Vogue is absolutely stunning! I bought one for my husband's birthday and he loves it. The craftsmanship is top-notch and the price is reasonable for such quality."</p>
          <div class="testimonial-author">
            <h4>Emily Rodriguez</h4>
            <span>Business Professional</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-content">
      <!-- Company Info -->
      <div class="footer-section">
        <div class="footer-logo">
          <img src="img/logo2.png" alt="Velvet Vogue Logo">
          <h3>Velvet Vogue</h3>
        </div>
        <p>Where comfort meets confidence. Discover the perfect blend of timeless elegance and contemporary flair with our handpicked pieces.</p>
        <div class="social-links">
          <a href="#" class="social-link" title="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-link" title="Twitter"><i class="fab fa-twitter"></i></a>
          <a href="#" class="social-link" title="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-link" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          <a href="#" class="social-link" title="YouTube"><i class="fab fa-youtube"></i></a>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="footer-section">
        <h4>Quick Links</h4>
        <ul class="footer-links">
          <li><a href="#home">Home</a></li>
          <li><a href="#brand">About Us</a></li>
          <li><a href="products.php">Products</a></li>
          <li><a href="#">Collections</a></li>
          <li><a href="#">New Arrivals</a></li>
          <li><a href="#">Sale</a></li>
        </ul>
      </div>

      <!-- Categories -->
      <div class="footer-section">
        <h4>Categories</h4>
        <ul class="footer-links">
          <li><a href="products.php?category_id=8">Men's Fashion</a></li>
          <li><a href="products.php?category_id=7">Women's Fashion</a></li>
          <li><a href="products.php?category_id=9">Kids Collection</a></li>
          <li><a href="products.php?category_id=11">Accessories</a></li>
          <li><a href="products.php?category_id=5">Footwear</a></li>
          <li><a href="products.php?category_id=6">Watches</a></li>
        </ul>
      </div>

      <!-- Customer Service -->
      <div class="footer-section">
        <h4>Customer Service</h4>
        <ul class="footer-links">
          <li><a href="contactus.php">Contact Us</a></li>
          <li><a href="#">Shipping Info</a></li>
          <li><a href="#">Returns & Exchanges</a></li>
          <li><a href="#">Size Guide</a></li>
          <li><a href="#">FAQ</a></li>
          <li><a href="#">Track Order</a></li>
        </ul>
      </div>

      
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
      <div class="footer-bottom-content">
        <p>&copy; 2024 Velvet Vogue. All rights reserved.</p>
        <div class="footer-bottom-links">
          <a href="#">Privacy Policy</a>
          <a href="#">Terms of Service</a>
          <a href="#">Cookie Policy</a>
        </div>
      </div>
    </div>
  </footer>

  <script src="indexjs.js"></script>
</body>

</html>