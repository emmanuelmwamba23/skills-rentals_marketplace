<?php
require 'config.php';

// Fetch products
$stmt = $pdo->query("SELECT p.id, p.name, p.price, p.image_path, p.created_at, u.username, u.phone
                     FROM products p
                     LEFT JOIN users u ON p.user_id = u.id
                     ORDER BY p.id DESC");
$products = $stmt->fetchAll();
$approved = $_SESSION['product_approval'] ?? [];
$uid = intval(current_user_id() ?? 0);
$isAdmin = is_admin();
$notifChats = unread_chat_count();
$notifReviews = pending_review_count();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Marketplace</title>
  <link rel="stylesheet" href="/CSS/index.css">
</head>
<body>
  <!-- Navbar -->
  <header class="navbar">
    <div class="navbar-left">
      <div class="logo">
        <span> Vestral </span>
      </div>
    </div>

    <nav class="navbar-center">
      <a href="index.php">Home</a>
      <a href="housing.php">Housing</a>
      <a href="cars.php">Cars</a>
      <a href="freelance.php">Freelance</a>
      <a href="about.php">About Us</a>
      <a href="contact.php">Contact Us</a>
    </nav>

    <div class="navbar-right">
      <?php if (current_user_id()): ?>
        <?php
          $username = $_SESSION['username'] ?? 'User';
          $initial = strtoupper(substr($username, 0, 1));
        ?>
        <div class="profile" id="profileBtn">
          <div class="profile-initial"><?= $initial ?></div>
          <?php if (($notifChats + $notifReviews) > 0): ?><span class="profile-badge"><?= $notifChats + $notifReviews ?></span><?php endif; ?>
        </div>
        <div class="dropdown" id="profileMenu">
          <?php if (current_role()==='buyer'): ?>
            <a href="buyer_dashboard.php">Buyer Hub<?php if($notifReviews>0): ?><span class="nav-pill"><?= $notifReviews ?></span><?php endif; ?></a>
          <?php endif; ?>
          <?php if (current_role()==='seller'): ?>
            <a href="seller_dashboard.php">Seller Hub<?php if($notifChats>0): ?><span class="nav-pill"><?= $notifChats ?></span><?php endif; ?></a>
          <?php endif; ?>
          <a href="my_account.php">My Account</a>
          <?php if (current_role()==='buyer'): ?>
            <a href="verify.php">Become a Seller</a>
          <?php endif; ?>
          <?php if (current_role()==='seller' && is_verified_seller(intval(current_user_id()))): ?>
            <a href="add_product.php">Add Listing</a>
          <?php endif; ?>
          <?php if (is_admin()): ?>
            <a href="admin.php">Admin</a>
          <?php endif; ?>
          <hr>
          <a href="logout.php" style="color: var(--color-danger);">Logout</a>
        </div>
      <?php else: ?>
        <a href="login.php" class="btn-login">Login</a>
        <a href="register.php" class="btn-login">Register</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- Products Grid -->
  <section class="hero">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1>Find Your Dream Home & Unlock Freelance Opportunities</h1>
    <p>Discover homes and skills built for Zambia: Mobile Money payments (MTN, Airtel, Zamtel) with ZRA-compliant receipts, co-living + training bundles, and trusted Informal Skills Verification.</p>
    <div class="hero-buttons">
      <a href="#listings" class="btn btn-hero">Explore Listings</a>
      <a href="freelance.php" class="btn btn-hero btn-secondary">Browse Freelance Gigs</a>
      <a href="#listings" class="btn btn-hero" style="background:#28a745">Apprentice Housing & Training Bundles</a>
    </div>
    <p class="hero-tagline">Homes, Skills, and Zambia-first features — all in one place.</p>
  </div>
</section>

  <div id="listings" class="container">
    <h2>Available Listings</h2>
    <div style="text-align:center; margin:.5rem 0 1rem;">
      <span style="display:inline-block;background:#eef5ff;color:#0b5ed7;padding:.25rem .6rem;border-radius:999px;margin:.2rem;">Mobile Money Ready</span>
      <span style="display:inline-block;background:#eef5ff;color:#0b5ed7;padding:.25rem .6rem;border-radius:999px;margin:.2rem;">ZRA Invoice</span>
      <span style="display:inline-block;background:#eef5ff;color:#0b5ed7;padding:.25rem .6rem;border-radius:999px;margin:.2rem;">Apprentice Housing</span>
      <span style="display:inline-block;background:#eef5ff;color:#0b5ed7;padding:.25rem .6rem;border-radius:999px;margin:.2rem;">Trade Dorms</span>
    </div>

    <div class="grid">
      <?php if (empty($products)): ?>
        <p>No listings yet.</p>
      <?php else: foreach ($products as $p): 
        $visible = ($approved[$p['id']] ?? 'pending') === 'approved' || intval($p['user_id'] ?? 0) === $uid || $isAdmin;
        if(!$visible) continue;
      ?>
        <div class="card">
          <?php if (!empty($p['image_path']) && file_exists(__DIR__ . '/uploads/' . $p['image_path'])): ?>
            <img src="<?= 'uploads/' . htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
          <?php else: ?>
            <img src="https://via.placeholder.com/400x300?text=No+Image" alt="no image">
          <?php endif; ?>

          <div class="card-body">
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <div class="price">$<?= number_format($p['price'], 2) ?></div>
          </div>

          <div class="card-footer">
            <a class="btn" href="product.php?id=<?= $p['id'] ?>">View</a>
            <button class="btn btn-success book-btn"
                    data-product="<?= htmlspecialchars($p['name']) ?>"
                    data-seller="<?= htmlspecialchars($p['username']) ?>"
                    data-phone="<?= htmlspecialchars($p['phone'] ?? '') ?>">
              Book
            </button>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- Booking Modal -->
  <div class="modal" id="bookingModal">
    <div class="modal-content">
      <span class="modal-close" id="closeModal">&times;</span>
      <h3>Book this home</h3>
      <p id="productName"></p>
      <p style="font-size:.95rem;color:#333;">
        Payments via Mobile Money (MTN MoMo, Airtel Money, Zamtel Kwacha). Receive a ZRA-compliant receipt after confirmation.
      </p>
      <form id="bookingForm" method="post" action="send_booking.php">
        <input type="hidden" name="product_name" id="productField">
        
        <?php if (current_user_id()): ?>
          <!-- Auto-fill logged-in user's info -->
          <input type="hidden" name="name" value="<?= htmlspecialchars($_SESSION['username']) ?>">
          <input type="hidden" name="phone" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>">
          <p>You are booking as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
        <?php else: ?>
          <!-- Guests fill in details -->
          <label>Your Name</label>
          <input type="text" name="name" class="input" required>
          <label>Phone Number</label>
          <input type="text" name="phone" class="input" required>
        <?php endif; ?>

        <button type="submit" class="btn btn-success">Send Details to Seller</button>
      </form>
    </div>
  </div>

  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <div>
        <h2>Community Activity</h2>
        <?php $feed = array_reverse($_SESSION['activity'] ?? []); if ($feed): ?>
          <ul>
            <?php foreach(array_slice($feed,0,10) as $item): ?><li><?= htmlspecialchars($item) ?></li><?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p>No recent activity. Book a stay, hire a car, or complete a job to see updates here.</p>
        <?php endif; ?>
        <div style="margin-top:1rem;">
          <a class="btn btn-outline" href="transactions.php">View Finance Ledger</a>
        </div>
      </div>
      <div>
        <h2>Top Providers</h2>
        <?php $counts=[]; foreach(ledger_all() as $r){ $counts[$r['seller']] = ($counts[$r['seller']] ?? 0)+1; } arsort($counts); $top=array_slice($counts,0,6,true); ?>
        <?php if ($top): ?>
          <table style="width:100%;border-collapse:collapse;">
            <tr><th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Seller</th><th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Jobs</th></tr>
            <?php foreach($top as $s=>$n): ?>
              <tr>
                <td style="padding:.4rem;border-bottom:1px solid #eee;"><?= htmlspecialchars($s) ?></td>
                <td style="padding:.4rem;border-bottom:1px solid #eee;"><?= intval($n) ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php else: ?><p>No data yet.</p><?php endif; ?>
      </div>
    </div>
  </div>

  <footer class="footer">
    &copy; <?php echo date("Y"); ?> Vestral. All rights reserved.
  </footer>

  <script>
  // Profile dropdown
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

  // Booking modal logic
  const modal = document.getElementById('bookingModal');
  const closeModal = document.getElementById('closeModal');
  const productNameDisplay = document.getElementById('productName');
  const productField = document.getElementById('productField');

  document.querySelectorAll('.book-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      productNameDisplay.textContent = "Booking: " + btn.dataset.product;
      productField.value = btn.dataset.product;
      modal.classList.add('show');
    });
  });

  closeModal.addEventListener('click', () => modal.classList.remove('show'));
  window.addEventListener('click', e => {
    if (e.target === modal) modal.classList.remove('show');
  });
  </script>

</body>
</html>
