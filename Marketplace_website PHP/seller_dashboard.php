<?php
require 'config.php';
require_once 'catalog.php';
require_login();
require_role('seller');

$username = current_username() ?? 'Seller';
$userId = intval(current_user_id());
$notifChats = unread_chat_count();
$notifReviews = pending_review_count();

$stmt = $pdo->prepare("SELECT id, name, price, image_path, created_at FROM products WHERE user_id = :uid ORDER BY id DESC");
$stmt->execute([':uid' => $userId]);
$myListings = $stmt->fetchAll();
$listingStatuses = $_SESSION['listing_status'] ?? [];

$bookings = bookings_for_seller($username);
$threads = chat_threads_for_user($username, $userId);
$threadParam = $_GET['thread'] ?? '';
$activeThread = null;
$activeMessages = [];
if ($threadParam !== '') {
    $meta = chat_thread_meta($threadParam);
    if ($meta && (($meta['seller'] ?? '') === $username || intval($meta['buyer_id'] ?? 0) === $userId)) {
        $activeThread = $meta;
        $activeMessages = chat_messages($threadParam);
        chat_mark_thread_read($threadParam, 'seller');
    }
}

$alerts = $_SESSION['seller_alerts'] ?? [];
$_SESSION['seller_alerts'] = [];

$ledgerRows = array_reverse(ledger_all());
$balances = [
    'escrow' => 0,
    'payout' => 0,
    'completed' => 0,
];
foreach ($bookings as $order) {
    $status = $order['status'] ?? 'Escrow';
    $amount = $order['amount'] ?? 0;
    if ($status === 'Escrow') $balances['escrow'] += $amount;
    if ($status === 'Payout') $balances['payout'] += $amount;
    if ($status === 'Completed') $balances['completed'] += $amount;
}
$balances['current'] = $_SESSION['balances'][$username] ?? 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Seller Dashboard | Vestral</title>
  <link rel="stylesheet" href="/CSS/index.css">
  <style>
    .dashboard-grid{display:grid;grid-template-columns:2fr 1fr;gap:1.5rem}
    @media(max-width:1100px){.dashboard-grid{grid-template-columns:1fr}}
    .card-panel{background:#fff;border-radius:14px;box-shadow:0 15px 35px rgba(0,0,0,.08);padding:1.3rem}
    .card-panel h3{margin-bottom:.6rem;color:#0b5ed7}
    .status-badge{display:inline-block;padding:.2rem .6rem;border-radius:12px;font-size:.85rem;font-weight:600}
    .status-Escrow{background:#fff4d6;color:#a66b00}
    .status-Payout{background:#e7f3ff;color:#0b5ed7}
    .status-Completed{background:#e8f8ef;color:#198754}
    table.dashboard-table{width:100%;border-collapse:collapse}
    table.dashboard-table th,table.dashboard-table td{padding:.55rem;border-bottom:1px solid #eee;text-align:left}
    .chat-thread{border:1px solid #eef2ff;border-radius:12px;padding:.7rem;margin-bottom:.6rem;background:#f9fbff}
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
      <?php if(current_role()==='buyer'): ?><a href="buyer_dashboard.php">Buyer Hub<?php if($notifReviews>0): ?><span class="nav-pill"><?= $notifReviews ?></span><?php endif; ?></a><?php endif; ?>
    </nav>
    <div class="navbar-right">
      <div class="profile" id="profileBtn">
        <div class="profile-initial"><?= strtoupper(substr($username,0,1)) ?></div>
        <?php if (($notifChats + $notifReviews) > 0): ?><span class="profile-badge"><?= $notifChats + $notifReviews ?></span><?php endif; ?>
      </div>
      <div class="dropdown" id="profileMenu">
        <a href="seller_dashboard.php">Seller Hub<?php if($notifChats>0): ?><span class="nav-pill"><?= $notifChats ?></span><?php endif; ?></a>
        <?php if(current_role()==='buyer'): ?><a href="buyer_dashboard.php">Buyer Hub<?php if($notifReviews>0): ?><span class="nav-pill"><?= $notifReviews ?></span><?php endif; ?></a><?php endif; ?>
        <?php if(current_role()==='buyer'): ?><a href="verify.php">Become a Seller</a><?php endif; ?>
        <a href="my_account.php">My Account</a>
        <?php if(is_verified_seller($userId)): ?><a href="add_product.php">Add Listing</a><?php endif; ?>
        <hr><a href="logout.php" style="color: var(--color-danger);">Logout</a>
      </div>
    </div>
  </header>

  <div class="container">
    <h2>Seller Dashboard</h2>
    <p style="color:#555;">Monitor listings, manage escrow bookings, and answer buyer chats — all in one Zambia-first workspace.</p>

    <?php foreach ($alerts as $alert): ?>
      <div class="alert" style="margin:1rem 0;padding:.8rem 1rem;border-radius:10px;background:#f1fff5;border:1px solid #c9f0d5;color:#1c7c3a;">
        <?= htmlspecialchars($alert) ?>
      </div>
    <?php endforeach; ?>

    <div class="dashboard-grid">
      <div>
        <div class="card-panel">
          <h3>Listings</h3>
          <?php if (empty($myListings)): ?>
            <p>You have no custom listings yet. <a href="add_product.php">Add one now.</a></p>
          <?php else: ?>
            <table class="dashboard-table">
              <tr>
                <th>Title</th>
                <th>Price</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
              <?php foreach ($myListings as $item):
                $status = $listingStatuses[$item['id']] ?? 'active';
                $toggle = $status === 'active' ? 'paused' : 'active';
              ?>
                <tr>
                  <td><?= htmlspecialchars($item['name']) ?></td>
                  <td>ZMW <?= number_format($item['price'],2) ?></td>
                  <td><span class="status-badge status-<?= $status === 'active' ? 'Completed' : 'Escrow' ?>"><?= ucfirst($status) ?></span></td>
                  <td style="display:flex;gap:.4rem;flex-wrap:wrap;">
                    <a class="btn btn-outline" href="product.php?id=<?= intval($item['id']) ?>">View</a>
                    <form method="post" action="seller_actions.php">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                      <input type="hidden" name="action" value="toggle_listing">
                      <input type="hidden" name="product_id" value="<?= intval($item['id']) ?>">
                      <input type="hidden" name="next_status" value="<?= $toggle ?>">
                      <button class="btn" type="submit" style="background:#f5f5f5;color:#333;">Set <?= ucfirst($toggle) ?></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </table>
          <?php endif; ?>
        </div>

        <div class="card-panel" style="margin-top:1.5rem;">
          <h3>Orders & Escrow</h3>
          <?php if (empty($bookings)): ?>
            <p>No bookings yet. Once buyers pay via Mobile Money, they’ll show up here.</p>
          <?php else: ?>
            <table class="dashboard-table">
              <tr>
                <th>Ref</th>
                <th>Buyer</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
              <?php foreach ($bookings as $order): ?>
                <tr>
                  <td><?= htmlspecialchars($order['listing'] ?? 'Listing') ?></td>
                  <td><?= htmlspecialchars($order['buyer_name'] ?? 'Buyer') ?></td>
                  <td>ZMW <?= number_format($order['amount'],2) ?></td>
                  <td><span class="status-badge status-<?= htmlspecialchars($order['status'] ?? 'Escrow') ?>"><?= htmlspecialchars($order['status'] ?? 'Escrow') ?></span></td>
                  <td>
                    <?php if (($order['status'] ?? '') === 'Escrow'): ?>
                      <form method="post" action="seller_actions.php">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="booking_status">
                        <input type="hidden" name="booking_id" value="<?= intval($order['id']) ?>">
                        <input type="hidden" name="next_status" value="Payout">
                        <button class="btn btn-outline" type="submit">Mark Work Done</button>
                      </form>
                    <?php elseif (($order['status'] ?? '') === 'Payout'): ?>
                      <form method="post" action="seller_actions.php">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="booking_status">
                        <input type="hidden" name="booking_id" value="<?= intval($order['id']) ?>">
                        <input type="hidden" name="next_status" value="Completed">
                        <button class="btn" type="submit" style="background:#28a745;color:#fff;">Finalize</button>
                      </form>
                    <?php else: ?>
                      <span style="color:#777;">Complete</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <div class="card-panel">
          <h3>Finance Snapshot</h3>
          <div style="display:flex;flex-direction:column;gap:.4rem;">
            <span class="badge">Current Balance: ZMW <?= number_format($balances['current'],2) ?></span>
            <span class="badge">Escrow: ZMW <?= number_format($balances['escrow'],2) ?></span>
            <span class="badge">Payout: ZMW <?= number_format($balances['payout'],2) ?></span>
            <span class="badge">Completed: ZMW <?= number_format($balances['completed'],2) ?></span>
          </div>
          <a class="btn btn-outline" style="margin-top:.8rem;" href="transactions.php">Open Ledger</a>
        </div>

        <div class="card-panel" style="margin-top:1.5rem;">
          <h3>Inbox</h3>
          <?php if (empty($threads)): ?>
            <p>No chats yet.</p>
          <?php else: foreach ($threads as $thread):
            $threadKey = $thread['thread_key'];
            $threadMessages = chat_messages($threadKey);
            $last = end($threadMessages);
          ?>
            <div class="chat-thread">
              <strong><?= htmlspecialchars($thread['listing'] ?? 'Listing') ?></strong>
              <p style="margin:.2rem 0;color:#555;">Buyer: <?= htmlspecialchars($thread['buyer_name'] ?? 'Buyer') ?></p>
              <?php if ($last): ?>
                <p style="font-size:.9rem;color:#777;"><em><?= htmlspecialchars($last['from']) ?>:</em> <?= htmlspecialchars($last['body']) ?></p>
              <?php endif; ?>
              <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.4rem;">
                <a class="btn btn-outline" href="seller_dashboard.php?thread=<?= urlencode($threadKey) ?>#sellerChat">Open Thread</a>
                <form method="post" action="message_send.php" style="flex:1;min-width:180px;">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="thread" value="<?= htmlspecialchars($threadKey) ?>">
                  <input type="hidden" name="redirect" value="seller_dashboard.php?thread=<?= urlencode($threadKey) ?>#sellerChat">
                  <input type="text" name="message" class="input" placeholder="Quick reply" style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:8px;margin-bottom:.4rem;">
                  <button class="btn" type="submit" style="background:#007bff;color:#fff;width:100%;">Send</button>
                </form>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>

        <?php if ($activeThread): ?>
        <div class="card-panel" id="sellerChat" style="margin-top:1.5rem;">
          <h3>Chat • <?= htmlspecialchars($activeThread['listing'] ?? '') ?></h3>
          <p style="color:#777;margin-top:-.4rem;margin-bottom:.6rem;">Buyer: <?= htmlspecialchars($activeThread['buyer_name'] ?? '') ?></p>
          <div style="max-height:320px;overflow-y:auto;margin-bottom:1rem;">
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
            <input type="hidden" name="thread" value="<?= htmlspecialchars($activeThread['thread_key']) ?>">
            <input type="hidden" name="redirect" value="seller_dashboard.php?thread=<?= urlencode($activeThread['thread_key']) ?>#sellerChat">
            <textarea name="message" class="input" placeholder="Type your reply" required style="width:100%;padding:.7rem;border:1px solid #ddd;border-radius:10px;"></textarea>
            <button class="btn" type="submit" style="margin-top:.6rem;background:#007bff;color:#fff;">Send</button>
          </form>
        </div>
        <?php endif; ?>
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

