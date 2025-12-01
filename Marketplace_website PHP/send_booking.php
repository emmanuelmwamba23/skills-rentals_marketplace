<?php // file: c:\Users\Administrator\OneDrive\Desktop\Marketplace_website\send_booking.php
require 'config.php';
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$product = trim($_POST['product_name'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Booking Confirmation</title>
  <link rel="stylesheet" href="/CSS/index.css">
</head>
<body>
  <header class="navbar">
    <div class="navbar-left"><div class="logo"><span>Vestral</span></div></div>
    <nav class="navbar-center">
      <a href="index.php">Home</a>
      <a href="freelance.php">Freelance</a>
      <a href="about.php">About Us</a>
      <a href="contact.php">Contact Us</a>
      <a href="admin.php">Admin</a>
    </nav>
  </header>
  <div class="container" style="max-width:800px;">
    <h2>Booking Submitted</h2>
    <p>Thank you. Your details were received.</p>
    <div style="display:flex;gap:1rem;flex-wrap:wrap;margin:1rem 0;">
      <span class="badge">Name: <?= htmlspecialchars($name) ?></span>
      <span class="badge">Phone: <?= htmlspecialchars($phone) ?></span>
      <span class="badge">Listing: <?= htmlspecialchars($product) ?></span>
    </div>
    <p>You will receive notifications via your selected channels.</p>
    <a class="btn" href="index.php" style="background:#007bff;color:#fff">Back to Home</a>
  </div>
  <footer class="footer">&copy; <?php echo date("Y"); ?> Vestral. All rights reserved.</footer>
</body>
</html>