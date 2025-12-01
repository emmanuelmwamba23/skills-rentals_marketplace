<?php // file: c:\Users\Administrator\OneDrive\Desktop\Marketplace_website\my_account.php
require 'config.php';
require_login();
$username = current_username() ?? 'User';
$notifChats = unread_chat_count();
$notifReviews = pending_review_count();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Account</title>
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
      <div class="profile" id="profileBtn">
        <div class="profile-initial"><?= strtoupper(substr($username,0,1)) ?></div>
        <?php if (($notifChats + $notifReviews) > 0): ?><span class="profile-badge"><?= $notifChats + $notifReviews ?></span><?php endif; ?>
      </div>
      <div class="dropdown" id="profileMenu">
        <?php if(current_role()==='buyer'): ?><a href="buyer_dashboard.php">Buyer Hub<?php if($notifReviews>0): ?><span class="nav-pill"><?= $notifReviews ?></span><?php endif; ?></a><?php endif; ?>
        <?php if(current_role()==='seller'): ?><a href="seller_dashboard.php">Seller Hub<?php if($notifChats>0): ?><span class="nav-pill"><?= $notifChats ?></span><?php endif; ?></a><?php endif; ?>
        <?php if(current_role()==='buyer'): ?><a href="verify.php">Become a Seller</a><?php endif; ?>
        <a href="my_account.php">My Account</a>
        <?php if(current_role()==='seller' && is_verified_seller(intval(current_user_id()))): ?><a href="add_product.php">Add Listing</a><?php endif; ?>
        <?php if(current_role()==='buyer'): ?><a href="verify.php" class="btn" style="background:#28a745;color:#fff">Become a Seller</a><?php endif; ?>
        <hr><a href="logout.php" style="color: var(--color-danger);">Logout</a>
      </div>
    </div>
  </header>
  <div class="container" style="max-width:900px;">
    <h2>Welcome, <?= htmlspecialchars($username) ?></h2>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin:.8rem 0;">
      <span class="badge">Reputation 85%</span>
      <span class="badge">ISV Verified</span>
      <span class="badge">Copperbelt Artisan</span>
      <span class="badge">Mobile Money ✔</span>
    </div>
    <div style="display:flex;gap:.8rem;flex-wrap:wrap;margin:1rem 0;">
      <a href="add_product.php" class="btn" style="background:#007bff;color:#fff">Add Listing</a>
      <a href="index.php#listings" class="btn" style="border-color:#007bff;color:#007bff">View My Listings</a>
      <a href="freelance.php" class="btn" style="background:#28a745;color:#fff">Browse Gigs</a>
      <a href="logout.php" class="btn" style="background:#dc3545;color:#fff">Logout</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:1rem;">
        <h3 style="color:#007bff;">Activity Feed</h3>
        <?php $feed = array_reverse($_SESSION['activity'] ?? []); if ($feed): ?>
          <ul><?php foreach($feed as $item): ?><li><?= htmlspecialchars($item) ?></li><?php endforeach; ?></ul>
        <?php else: ?><p>No recent activity.</p><?php endif; ?>
      </div>
      <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:1rem;">
        <h3 style="color:#007bff;">Admin Access (Demo)</h3>
        <form method="post" action="toggle_admin.php" style="display:flex;gap:.6rem;flex-wrap:wrap;">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <button class="btn" style="background:#007bff;color:#fff">Grant Admin</button>
          <a class="btn" href="admin.php" style="border-color:#007bff;color:#007bff">Go to Admin</a>
        </form>
      </div>
    </div>

    <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:1rem;margin-top:1rem;">
      <h3 style="color:#007bff;">Earnings & Balance</h3>
      <?php $me=$username; $rows=ledger_all(); $my=array_values(array_filter($rows, fn($r)=>($r['seller']??'')===$me)); $esc=0;$pay=0;$comp=0; foreach($my as $r){ if(($r['status']??'')==='Escrow') $esc+=$r['amount']; elseif(($r['status']??'')==='Payout') $pay+=$r['amount']; elseif(($r['status']??'')==='Completed') $comp+=$r['amount']; } $bal=$_SESSION['balances'][$me] ?? 0; ?>
      <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin:.6rem 0;">
        <span class="badge">Current Balance: ZMW <?= number_format($bal,2) ?></span>
        <span class="badge">Escrow: ZMW <?= number_format($esc,2) ?></span>
        <span class="badge">Payout: ZMW <?= number_format($pay,2) ?></span>
        <span class="badge">Completed: ZMW <?= number_format($comp,2) ?></span>
      </div>
      <h4>Recent Transactions</h4>
      <?php if ($my): ?>
        <table style="width:100%;border-collapse:collapse;">
          <tr><th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Ref</th><th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Amount</th><th style="text-align:left;padding:.4rem;border-bottom:1px solid #eee;">Status</th></tr>
          <?php foreach(array_slice(array_reverse($my),0,5) as $r): ?>
            <tr>
              <td style="padding:.4rem;border-bottom:1px solid #eee;"><?= htmlspecialchars($r['ref']) ?></td>
              <td style="padding:.4rem;border-bottom:1px solid #eee;">ZMW <?= number_format($r['amount'],2) ?></td>
              <td style="padding:.4rem;border-bottom:1px solid #eee;"><?= htmlspecialchars($r['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <p>No transactions under your provider name.</p>
      <?php endif; ?>
      <div style="margin-top:.8rem;display:flex;gap:.6rem;flex-wrap:wrap;">
        <a class="btn" href="transactions.php" style="border-color:#007bff;color:#007bff">Open Finance Ledger</a>
      </div>
    </div>
  </div>
  <footer class="footer">&copy; <?php echo date("Y"); ?> Vestral. All rights reserved.</footer>
  <script>
    const profileBtn=document.getElementById('profileBtn'),profileMenu=document.getElementById('profileMenu');
    if(profileBtn&&profileMenu){profileBtn.addEventListener('click',e=>{e.stopPropagation();profileMenu.classList.toggle('show')});document.addEventListener('click',e=>{if(!profileMenu.contains(e.target)&&!profileBtn.contains(e.target)) profileMenu.classList.remove('show');});}
  </script>
</body>
</html>
