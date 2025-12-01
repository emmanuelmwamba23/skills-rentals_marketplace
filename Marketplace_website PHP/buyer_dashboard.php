<?php
require 'config.php';
require_once 'catalog.php';
require_login();
require_role('buyer');

$username = current_username() ?? 'Buyer';
$userId = intval(current_user_id());
$notifChats = unread_chat_count();
$notifReviews = pending_review_count();

$bookings = bookings_for_user($userId);
$alerts = $_SESSION['buyer_alerts'] ?? [];
$_SESSION['buyer_alerts'] = [];

$threadParam = $_GET['thread'] ?? '';
$activeThread = null;
$activeMessages = [];
if ($threadParam !== '') {
    $meta = chat_thread_meta($threadParam);
    if ($meta && (intval($meta['buyer_id'] ?? 0) === $userId || ($meta['seller'] ?? '') === $username)) {
        $activeThread = $meta;
        $activeMessages = chat_messages($threadParam);
        chat_mark_thread_read($threadParam, 'buyer');
    }
}
$activityFeed = array_reverse($_SESSION['activity'] ?? []);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Buyer Dashboard | Vestral</title>
  <link rel="stylesheet" href="/CSS/index.css">
  <style>
    .dashboard-grid{display:grid;grid-template-columns:2fr 1fr;gap:1.5rem}
    @media(max-width:1100px){.dashboard-grid{grid-template-columns:1fr}}
    .card-panel{background:#fff;border-radius:14px;box-shadow:0 15px 35px rgba(0,0,0,.08);padding:1.3rem}
    .status-badge{display:inline-block;padding:.2rem .6rem;border-radius:12px;font-size:.85rem;font-weight:600;margin-right:.4rem}
    .status-Escrow{background:#fff4d6;color:#a66b00}
    .status-Payout{background:#e7f3ff;color:#0b5ed7}
    .status-Completed{background:#e8f8ef;color:#198754}
    .message-bubble{padding:.6rem .8rem;border-radius:12px;margin-bottom:.4rem;max-width:85%;}
    .from-me{background:#e6f0ff;margin-left:auto;text-align:right;}
    .from-them{background:#f4f4f4;}
  </style>
</head>
<body>
  <header class="navbar">
    <div class="navbar-left"><div class="logo"><span>Vestral</span></div></div>
    <nav class="navbar-center">
      <a href="index.php">Home</a>
      <a href="housing.php">Housing</a>
      <a href="cars.php">Cars</a>
      <a href="freelance.php">Freelance</a>
      <?php if(current_role()==='seller'): ?><a href="seller_dashboard.php">Seller Hub<?php if($notifChats>0): ?><span class="nav-pill"><?= $notifChats ?></span><?php endif; ?></a><?php endif; ?>
    </nav>
    <div class="navbar-right">
      <div class="profile" id="profileBtn">
        <div class="profile-initial"><?= strtoupper(substr($username,0,1)) ?></div>
        <?php if (($notifChats + $notifReviews) > 0): ?><span class="profile-badge"><?= $notifChats + $notifReviews ?></span><?php endif; ?>
      </div>
      <div class="dropdown" id="profileMenu">
        <a href="buyer_dashboard.php">Buyer Hub<?php if($notifReviews>0): ?><span class="nav-pill"><?= $notifReviews ?></span><?php endif; ?></a>
        <?php if(current_role()==='seller'): ?><a href="seller_dashboard.php">Seller Hub<?php if($notifChats>0): ?><span class="nav-pill"><?= $notifChats ?></span><?php endif; ?></a><?php endif; ?>
        <?php if(current_role()==='buyer'): ?><a href="verify.php">Become a Seller</a><?php endif; ?>
        <a href="my_account.php">My Account</a>
        <a href="logout.php" style="color: var(--color-danger);">Logout</a>
      </div>
    </div>
  </header>

  <div class="container">
    <h2>Buyer Dashboard</h2>
    <p style="color:#555;">Track bookings, request invoices, and chat with sellers across Zambia.</p>

    <?php foreach ($alerts as $alert): ?>
      <div class="alert" style="margin:1rem 0;padding:.8rem 1rem;border-radius:10px;background:#eef5ff;border:1px solid #bcd8ff;color:#0b5ed7;">
        <?= htmlspecialchars($alert) ?>
      </div>
    <?php endforeach; ?>

    <div class="dashboard-grid">
      <div>
        <div class="card-panel">
          <h3>Bookings</h3>
          <?php if (empty($bookings)): ?>
            <p>No bookings yet. Explore listings and pay via Mobile Money to see them here.</p>
          <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
              <div style="border:1px solid #eef2ff;border-radius:12px;padding:1rem;margin-bottom:1rem;">
                <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:.6rem;">
                  <div>
                    <strong><?= htmlspecialchars($booking['listing'] ?? 'Listing') ?></strong>
                    <p style="margin:.2rem 0;color:#777;">Seller: <?= htmlspecialchars($booking['seller'] ?? 'Seller') ?></p>
                  </div>
                  <div>
                    <span class="status-badge status-<?= htmlspecialchars($booking['status'] ?? 'Escrow') ?>"><?= htmlspecialchars($booking['status'] ?? 'Escrow') ?></span>
                    <span class="badge">ZMW <?= number_format($booking['amount'],2) ?></span>
                  </div>
                </div>
                <?php if (!empty($booking['start'])): ?>
                  <p style="margin:.4rem 0;color:#666;">Stay: <?= htmlspecialchars($booking['start']) ?> → <?= htmlspecialchars($booking['end'] ?? $booking['start']) ?> • Guests: <?= intval($booking['guests'] ?? 1) ?></p>
                <?php endif; ?>
                <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.4rem;">
                  <a class="btn btn-outline" href="buyer_dashboard.php?thread=<?= urlencode($booking['thread_key']) ?>#chat">Open Chat</a>
                  <?php if (($booking['status'] ?? '') === 'Completed'): ?>
                  <form method="post" action="review_submit.php" style="display:flex;gap:.4rem;flex-wrap:wrap;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($booking['type']) ?>">
                    <input type="hidden" name="listing_id" value="<?= intval($booking['listing_id']) ?>">
                    <input type="number" name="rating" min="1" max="5" value="5" style="width:70px;padding:.4rem;border:1px solid #ddd;border-radius:8px;">
                    <input type="text" name="comment" placeholder="Leave a review" style="flex:1;min-width:180px;padding:.4rem;border:1px solid #ddd;border-radius:8px;">
                    <button class="btn" type="submit" style="background:#28a745;color:#fff;">Post</button>
                  </form>
                  <?php else: ?>
                    <span style="color:#777;">Complete booking to review</span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div id="chat" class="card-panel" style="margin-top:1.5rem;">
          <h3>Chat</h3>
          <?php if (!$activeThread): ?>
            <p>Select a booking to open the conversation.</p>
          <?php else: ?>
            <h4><?= htmlspecialchars($activeThread['listing'] ?? 'Listing') ?></h4>
            <div style="max-height:320px;overflow-y:auto;margin:1rem 0;">
              <?php if (empty($activeMessages)): ?>
                <p style="color:#777;">No messages yet.</p>
              <?php else: foreach ($activeMessages as $msg): ?>
                <div class="message-bubble <?= ($msg['from'] ?? '') === $username ? 'from-me' : 'from-them' ?>">
                  <small style="display:block;color:#555;"><?= htmlspecialchars($msg['from'] ?? 'User') ?> • <?= htmlspecialchars($msg['timestamp'] ?? '') ?></small>
                  <div><?= nl2br(htmlspecialchars($msg['body'] ?? '')) ?></div>
                </div>
              <?php endforeach; endif; ?>
            </div>
            <form method="post" action="message_send.php">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="thread" value="<?= htmlspecialchars($activeThread['thread_key'] ?? $threadParam) ?>">
              <input type="hidden" name="redirect" value="buyer_dashboard.php?thread=<?= urlencode($activeThread['thread_key'] ?? $threadParam) ?>#chat">
              <textarea name="message" class="input" placeholder="Type your message" required style="width:100%;padding:.7rem;border:1px solid #ddd;border-radius:10px;"></textarea>
              <button class="btn" type="submit" style="margin-top:.6rem;background:#007bff;color:#fff;">Send</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <div class="card-panel">
          <h3>Activity Feed</h3>
          <?php if (empty($activityFeed)): ?>
            <p>No activity yet.</p>
          <?php else: ?>
            <ul style="max-height:360px;overflow-y:auto;">
              <?php foreach (array_slice($activityFeed,0,15) as $entry): ?>
                <li style="padding:.3rem 0;border-bottom:1px solid #f1f1f1;"><?= htmlspecialchars($entry) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer">&copy; <?= date('Y') ?> Vestral. All rights reserved.</footer>
  <script>
    const profileBtn=document.getElementById('profileBtn'), profileMenu=document.getElementById('profileMenu');
    if(profileBtn&&profileMenu){
      profileBtn.addEventListener('click',e=>{e.stopPropagation();profileMenu.classList.toggle('show');});
      document.addEventListener('click',e=>{if(!profileMenu.contains(e.target)&&!profileBtn.contains(e.target)){profileMenu.classList.remove('show');}});
    }
  </script>
</body>
</html>

