<?php
require 'config.php';
$page_title = "Contact Us |";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $message && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $message]);
            $success = true;
        } catch (Exception $e) {
            $error = "Error saving message: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = "Please fill in all fields with a valid email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $page_title; ?> Vestral</title>
  <link rel="stylesheet" href="/CSS/contact.css">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>

  <!-- Navbar -->
  <header class="navbar">
    <div class="navbar-left">
      <div class="logo">
        <span>Vestral</span>
      </div>
    </div>

    <nav class="navbar-center">
      <a href="index.php">Home</a>
      <a href="housing.php">Housing</a>
      <a href="cars.php">Cars</a>
      <a href="freelance.php">Freelance</a>
      <a href="about.php">About Us</a>
      <a href="contact.php" class="active">Contact Us</a>
    </nav>

    <div class="navbar-right">
      <?php if (function_exists('current_user_id') && current_user_id()): ?>
        <?php
          $username = $_SESSION['username'] ?? 'User';
          $initial = strtoupper(substr($username, 0, 1));
        ?>
        <div class="profile" id="profileBtn">
          <div class="profile-initial"><?= $initial ?></div>
        </div>
        <div class="dropdown" id="profileMenu">
          <a href="my_account.php">My Account</a>
          <a href="add_product.php">Add Listing</a>
          <a href="admin.php">Admin</a>
          <hr>
          <a href="logout.php" style="color: var(--color-danger);">Logout</a>
        </div>
      <?php else: ?>
        <a href="login.php" class="btn-login">Login</a>
        <a href="register.php" class="btn-login">Register</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- Success/Error Messages -->
  <?php if (!empty($success)): ?>
    <div class="success-message">✅ Your message has been sent successfully!</div>
  <?php elseif (!empty($error)): ?>
    <div class="error-message">❌ <?php echo $error; ?></div>
  <?php endif; ?>

  <!-- Contact Section -->
  <div class="contact-container">
    <div class="contact-header">
      <h1>Get in Touch</h1>
      <p>We’d love to hear from you. Reach out for inquiries, support, or collaboration opportunities!</p>
    </div>

    <div class="contact-content">
      <!-- Contact Information -->
      <div class="contact-info">
        <div><i class="fas fa-map-marker-alt"></i><p>123 Vestral Avenue, Tech City, World 45678</p></div>
        <div><i class="fas fa-phone-alt"></i><p>+260 (098)567-8901</p></div>
        <div><i class="fas fa-envelope"></i><p>support@vestral.com</p></div>
        <div><i class="fas fa-clock"></i><p>Mon - Fri: 9:00 AM – 6:00 PM</p></div>
      </div>

      <!-- Contact Form -->
      <div class="contact-form">
        <form method="post" action="">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" required>

          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required>

          <label for="message">Your Message</label>
          <textarea id="message" name="message" rows="5" required></textarea>

          <div style="margin:1rem 0;">
            <p style="font-weight:600; margin-bottom:.3rem;">Notifications</p>
            <label style="display:block; margin:.2rem 0;"><input type="checkbox" name="notify_sms"> Receive SMS updates for bookings</label>
            <label style="display:block; margin:.2rem 0;"><input type="checkbox" name="notify_whatsapp"> Receive WhatsApp updates for bookings</label>
          </div>

          <button type="submit">Send Message</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    &copy; <?php echo date("Y"); ?> Vestral. All rights reserved.
  </footer>

  <!-- Navbar dropdown toggle -->
  <script>
  const profileBtn = document.getElementById('profileBtn');
  const profileMenu = document.getElementById('profileMenu');
  if (profileBtn && profileMenu) {
    profileBtn.addEventListener('click', e => {
      e.stopPropagation();
      profileMenu.classList.toggle('show');
    });
    document.addEventListener('click', e => {
      if (!profileMenu.contains(e.target) && !profileBtn.contains(e.target)) {
        profileMenu.classList.remove('show');
      }
    });
  }
  </script>

</body>
</html>
