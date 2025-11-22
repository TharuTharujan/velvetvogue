<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us | Velvet Vogue</title>
  <link rel="icon" href="img/logo2.png">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="contactusstyle.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
  <style>
    /* Popup message styles */
    .popup-message {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 20px;
      border-radius: 5px;
      color: white;
      font-weight: bold;
      z-index: 1000;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      display: none;
      min-width: 250px;
    }
    
    .popup-success {
      background-color: #4CAF50;
    }
    
    .popup-error {
      background-color: #f44336;
    }
    
    .close-popup {
      float: right;
      cursor: pointer;
      font-size: 20px;
      margin-left: 10px;
      line-height: 1;
    }
  </style>
</head>

<body>
  <!-- Header Section -->
  <div class="header">
    
    <div class="header-top">
      <div class="logo">
        <a href="index.php">
          <img src="img/logo2.png" alt="Velvet Vogue Logo" width="200px">
        </a>
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
      <a href="contactus.php" class="active">Contact Us</a>
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

  <!-- Popup Message Container -->
  <div id="popupMessage" class="popup-message">
    <span class="close-popup" onclick="closePopup()">&times;</span>
    <span id="popupText"></span>
  </div>

  <!-- Contact Hero Section -->
  <div class="contact-hero">
    <h1>Get In Touch</h1>
    <p>We'd love to hear from you! Reach out to us with any questions or feedback.</p>
  </div>

  <!-- Contact Content -->
  <div class="contact-container">
    <div class="contact-form">
      <h2>Send Us a Message</h2>
      <form action="contact_handler.php" method="POST">
        <div class="form-group">
          <input type="text" id="name" name="name" placeholder="Your Name" required>
        </div>
        <div class="form-group">
          <input type="email" id="email" name="email" placeholder="Your Email" required>
        </div>
        <div class="form-group">
          <input type="text" id="subject" name="subject" placeholder="Subject">
        </div>
        <div class="form-group">
          <textarea id="message" name="message" rows="6" placeholder="Your Message" required></textarea>
        </div>
        <button type="submit" class="submit-btn">Send Message</button>
      </form>
    </div>

    <div class="contact-info">
      <div class="info-box">
        <i class="fas fa-map-marker-alt"></i>
        <div>
          <h3>Our Location</h3>
          <p>123 Fashion Street, Style District</p>
          <p>New York, NY 10001</p>
        </div>
      </div>
      
      <div class="info-box">
        <i class="fas fa-phone"></i>
        <div>
          <h3>Phone Number</h3>
          <p>+1 (555) 123-4567</p>
          <p>+1 (555) 987-6543</p>
        </div>
      </div>
      
      <div class="info-box">
        <i class="fas fa-envelope"></i>
        <div>
          <h3>Email Address</h3>
          <p>info@velvetvogue.com</p>
          <p>support@velvetvogue.com</p>
        </div>
      </div>
      
      <div class="info-box">
        <i class="fas fa-clock"></i>
        <div>
          <h3>Working Hours</h3>
          <p>Monday - Friday: 9:00 AM - 8:00 PM</p>
          <p>Saturday - Sunday: 10:00 AM - 6:00 PM</p>
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
          <li><a href="index.php">Home</a></li>
          <li><a href="index.php#brand">About Us</a></li>
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
  <script>
    // Display popup message if exists
    <?php
    if (isset($_SESSION['contact_success'])) {
        echo "showPopup('" . addslashes($_SESSION['contact_success']) . "', 'success');";
        unset($_SESSION['contact_success']);
    } elseif (isset($_SESSION['contact_error'])) {
        echo "showPopup('" . addslashes($_SESSION['contact_error']) . "', 'error');";
        unset($_SESSION['contact_error']);
    }
    ?>
    
    function showPopup(message, type) {
        const popup = document.getElementById('popupMessage');
        const popupText = document.getElementById('popupText');
        popupText.textContent = message;
        popup.className = 'popup-message popup-' + type;
        popup.style.display = 'block';
    }
    
    function closePopup() {
        const popup = document.getElementById('popupMessage');
        popup.style.display = 'none';
    }
  </script>
</body>

</html>