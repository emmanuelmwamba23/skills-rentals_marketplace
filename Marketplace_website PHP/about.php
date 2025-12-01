<?php
require 'config.php';
$page_title = "About Us |";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> Vestral</title>
    <link rel="stylesheet" href="/CSS/index.css">
    <link rel="stylesheet" href="/CSS/about.css">
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
      <a href="about.php" >About Us</a>
      <a href="contact.php">Contact Us</a>
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
          <?php if (current_role()==='seller' && is_verified_seller(intval(current_user_id()))): ?><a href="add_product.php">Add Listing</a><?php endif; ?>
          <?php if (is_admin()): ?><a href="admin.php">Admin</a><?php endif; ?>
          <hr>
          <a href="logout.php" style="color: var(--color-danger);">Logout</a>
        </div>
      <?php else: ?>
        <a href="login.php" class="btn-login">Login</a>
        <a href="register.php" class="btn-login">Register</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- About Page Content -->
  <div class="about-container">
      <h1>About Us</h1>
      <p>
          At <strong>Vestral</strong>, we believe in making everyday management simpler, smarter, and more connected.
          Our mission is to empower property owners, freelancers, and clients with easy-to-use digital tools that
          streamline their operations and help them focus on what truly matters.
      </p>

      <h2>What We Do</h2>
      <p>
          We specialize in developing innovative web solutions designed for two key sectors:
      </p>
      <ul>
          <li><strong>Boarding House Management:</strong> Our platform helps landlords and managers handle tenant information,
              room availability, billing, and maintenance—all in one seamless dashboard.</li>
          <li><strong>Freelance Gigs Marketplace:</strong> We connect skilled freelancers with clients looking for reliable talent.
              Our system makes it easy to post projects, manage offers, and track progress securely.</li>
      </ul>

      <h2>Our Vision</h2>
      <p>
          Driven by technology and inspired by real-world challenges, our team is dedicated to building user-friendly,
          scalable, and efficient web systems that make life easier for both property managers and freelancers.
      </p>

      <h2>Zambia-First Features</h2>
      <ul>
        <li><strong>Mobile Money & ZRA Compliance:</strong> Payments via MTN MoMo, Airtel Money, Zamtel Kwacha, with ZRA-compliant receipts.</li>
        <li><strong>Informal Skills Verification (ISV):</strong> Community or peer vetting for artisans without formal diplomas.</li>
        <li><strong>Agri-Tech & Green Skills:</strong> Filters and categories for solar, hydro, beehive construction, climate-smart agriculture, and water harvesting.</li>
        <li><strong>Co-Living & Training Bundles:</strong> Hosts can list Apprentice Housing or Trade Dorms bundled with short-term training.</li>
      </ul>

      <h2>Gamification & Reputation</h2>
      <ul>
        <li><strong>Skill Mastery Badges & Levels:</strong> Earn points and unlock badges like “Copperbelt Artisan” or “Lusaka SuperHost.”</li>
        <li><strong>Reputation Score:</strong> Transparent metrics on timeliness, quality, communication, honesty, safety displayed on profiles.</li>
        <li><strong>Weekly Quests:</strong> Rotating goals to boost engagement and reward activity.</li>
        <li><strong>Community Mentorship:</strong> Level 5 mentors offer paid guidance to upskill others.</li>
      </ul>

      <h2>Admin Panel Excellence</h2>
      <ul>
        <li><strong>Automated Review Fraud Detector:</strong> Flags suspicious patterns for manual review.</li>
        <li><strong>Geo-Fencing & Local Priority:</strong> Fine-tune search to prioritize nearby results with optional GPS check-ins.</li>
        <li><strong>SMS/WhatsApp Notifications:</strong> Automated updates for bookings and payments.</li>
        <li><strong>Dispute Resolution Workflow:</strong> Guided, auditable resolution process for fair outcomes.</li>
      </ul>

      <p>
          At <strong>Vestral</strong>, we’re not just building websites—we’re creating digital solutions that connect people,
          simplify work, and shape the future of modern living and working.
      </p>
  </div>

  <footer class="footer">
    &copy; <?php echo date("Y"); ?> Vestral. All rights reserved.
  </footer>
  <script>
  // Profile dropdown toggle
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
