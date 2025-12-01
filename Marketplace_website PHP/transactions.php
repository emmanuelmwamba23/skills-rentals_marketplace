<?php
require 'config.php';
require_login();
$notifChats = unread_chat_count();
$notifReviews = pending_review_count();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensure_csrf_post();
    $id = intval($_POST['id'] ?? 0);
    $action = trim($_POST['action'] ?? '');
    $row = ledger_find($id);
    if ($row && ($_SESSION['role'] ?? '') === 'admin') {
        if ($action === 'complete' && $row['status'] === 'Escrow') {
            ledger_update_status($id, 'Payout');
            add_activity('Job completed • TX '.$id);
            $_SESSION['alerts'][] = 'Transaction '.$id.' marked as Payout';
        }
        if ($action === 'finalize' && $row['status'] === 'Payout') {
            ledger_update_status($id, 'Completed');
            add_activity('Payout finalized • TX '.$id);
            $_SESSION['alerts'][] = 'Transaction '.$id.' marked as Completed';
        }
    }
    header('Location: transactions.php');
    exit;
}

$rows = array_reverse(ledger_all());
$alerts = $_SESSION['alerts'] ?? [];
$_SESSION['alerts'] = [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Finance Ledger</title>
  <link rel="stylesheet" href="/CSS/index.css">
  <style>
    .table { width:100%; border-collapse:collapse; }
    .table th, .table td { padding:.6rem; border-bottom:1px solid #eee; text-align:left; }
    .badge { display:inline-block; background:#eef5ff; color:#0b5ed7; padding:.25rem .6rem; border-radius:999px; margin:.2rem; }
    .alert { margin:1rem auto; max-width:1000px; padding:.8rem 1rem; border-radius:6px; border:1px solid #b2e2b9; background:#e9f9ee; color:#1c7c3b; }
  </style>
  </head>
<body>
  <header class="navbar">
    <div class="navbar-left"><div class="logo"><span> Vestral </span></div></div>
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
        <?php $username = $_SESSION['username'] ?? 'User'; $initial = strtoupper(substr($username,0,1)); ?>
        <div class="profile" id="profileBtn">
          <div class="profile-initial"><?= $initial ?></div>
          <?php if (($notifChats + $notifReviews) > 0): ?><span class="profile-badge"><?= $notifChats + $notifReviews ?></span><?php endif; ?>
        </div>
        <div class="dropdown" id="profileMenu">
          <a href="buyer_dashboard.php">Buyer Hub<?php if($notifReviews>0): ?><span class="nav-pill"><?= $notifReviews ?></span><?php endif; ?></a>
          <a href="seller_dashboard.php">Seller Hub<?php if($notifChats>0): ?><span class="nav-pill"><?= $notifChats ?></span><?php endif; ?></a>
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

  <div class="container">
    <h2>Finance Ledger</h2>
    <div>
      <span class="badge">Mobile Money: MTN • Airtel • Zamtel</span>
      <span class="badge">ZRA Invoice</span>
      <?php if (!empty($_GET['promo'])): ?><span class="badge">Promo Applied</span><?php endif; ?>
    </div>

    <?php foreach ($alerts as $a): ?>
      <div class="alert"><?= htmlspecialchars($a) ?></div>
    <?php endforeach; ?>

    <?php if (empty($rows)): ?>
      <p>No transactions yet.</p>
    <?php else: ?>
      <table class="table">
        <tr>
          <th>Ref</th>
          <th>Seller</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['ref']) ?></td>
            <td><?= htmlspecialchars($r['seller']) ?></td>
            <td>ZMW <?= number_format($r['amount'],2) ?></td>
            <td><?= htmlspecialchars($r['status']) ?></td>
            <td>
              <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <?php if ($r['status'] === 'Escrow'): ?>
                  <form method="post" style="display:inline-block;margin-right:.4rem;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" value="<?= intval($r['id']) ?>">
                    <input type="hidden" name="action" value="complete">
                    <button class="btn btn-outline" type="submit">Complete Job</button>
                  </form>
                <?php endif; ?>
                <?php if ($r['status'] === 'Payout'): ?>
                  <form method="post" style="display:inline-block;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" value="<?= intval($r['id']) ?>">
                    <input type="hidden" name="action" value="finalize">
                    <button class="btn" type="submit" style="background:#28a745;color:#fff;">Finalize Payout</button>
                  </form>
                <?php endif; ?>
              <?php else: ?>
                <span style="color:#555;">No actions</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>

  <footer class="footer">&copy; <?= date('Y') ?> Vestral. All rights reserved.</footer>

  <script>
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');
    if (profileBtn && profileMenu) {
      profileBtn.addEventListener('click', e => { e.stopPropagation(); profileMenu.classList.toggle('show'); });
      document.addEventListener('click', e => { if (!profileMenu.contains(e.target) && !profileBtn.contains(e.target)) { profileMenu.classList.remove('show'); } });
    }
  </script>
  </body>
  </html>

