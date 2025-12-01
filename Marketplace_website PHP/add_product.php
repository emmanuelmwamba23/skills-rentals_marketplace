<?php // file: c:\Users\Administrator\OneDrive\Desktop\Marketplace_website\add_product.php
require 'config.php';
require_login();
require_role('seller');
require_verified_seller();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) $errors[] = "Invalid CSRF token.";
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    if ($name === '' || $price <= 0) $errors[] = "Name and valid price are required.";
    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO products (name, price, description, user_id) VALUES (:n,:p,:d,:u)");
        $stmt->execute([':n'=>$name, ':p'=>$price, ':d'=>$description, ':u'=>current_user_id()]);
        $id = $pdo->lastInsertId();
        $_SESSION['product_approval'][$id] = 'pending';
        header("Location: product.php?id=" . $id);
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Add Listing</title>
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
    <div class="navbar-right">
      <div class="profile" id="profileBtn"><div class="profile-initial"><?= strtoupper(substr(current_username() ?? 'U',0,1)) ?></div></div>
      <div class="dropdown" id="profileMenu">
        <a href="my_account.php">My Account</a>
        <a href="add_product.php">Add Listing</a>
        <hr><a href="logout.php" style="color: var(--color-danger);">Logout</a>
      </div>
    </div>
  </header>
  <div class="container" style="max-width:800px;">
    <h2>Add Listing</h2>
    <?php if($errors): ?>
      <div style="color:#b00020; margin-bottom:12px;"><?php foreach($errors as $e) echo "<div>" . htmlspecialchars($e) . "</div>"; ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <label>Name</label>
      <input type="text" name="name" style="width:100%;padding:.6rem;border:1px solid #ddd;border-radius:8px" required>
      <label style="margin-top:.6rem;">Price (ZMW)</label>
      <input type="number" name="price" step="0.01" style="width:100%;padding:.6rem;border:1px solid #ddd;border-radius:8px" required>
      <label style="margin-top:.6rem;">Description</label>
      <textarea name="description" rows="4" style="width:100%;padding:.6rem;border:1px solid #ddd;border-radius:8px"></textarea>
      <button class="btn" type="submit" style="background:#007bff;color:#fff;margin-top:.8rem">Create</button>
    </form>
  </div>
  <footer class="footer">&copy; <?php echo date("Y"); ?> Vestral. All rights reserved.</footer>
  <script>
    const profileBtn=document.getElementById('profileBtn'),profileMenu=document.getElementById('profileMenu');
    if(profileBtn&&profileMenu){profileBtn.addEventListener('click',e=>{e.stopPropagation();profileMenu.classList.toggle('show')});document.addEventListener('click',e=>{if(!profileMenu.contains(e.target)&&!profileBtn.contains(e.target)) profileMenu.classList.remove('show');});}
  </script>
</body>
</html>
