<?php
require 'config.php';
require_admin();
$page_title = "Admin | Control Room";
$notifChats = unread_chat_count();
$notifReviews = pending_review_count();
$usersCount = 0; $sellersCount = 0; $gigsCount = 0; $suspendedCount = count($_SESSION['suspended'] ?? []);
try { $usersCount = intval($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0); } catch (Throwable $e) {}
try { $sellersCount = intval($pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn() ?: 0); } catch (Throwable $e) { $sellersCount = count(array_filter($_SESSION['user_roles'] ?? [], fn($r)=>$r==='seller')); }
try { $gigsCount = intval($pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0); } catch (Throwable $e) {}
$pendingStmt = null; $pendingVer = [];
try { $pendingStmt = $pdo->query("SELECT id, user_id, username, id_document, profile_image, description, created_at FROM seller_verifications WHERE status = 'pending' ORDER BY created_at DESC"); $pendingVer = $pendingStmt->fetchAll(); } catch (Throwable $e) {}
$admin_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) { http_response_code(400); exit; }
  if (isset($_POST['suspend_user'])) {
    $u = trim($_POST['username'] ?? '');
    if ($u !== '') { suspend_user($u); $_SESSION['alerts'][] = "User suspended: $u"; $admin_msg = "Suspended $u"; }
  }
  if (isset($_POST['promo_add'])) {
    $code = trim($_POST['code'] ?? '');
    $pct = floatval($_POST['percent'] ?? 0);
    if ($code !== '' && $pct > 0) { promo_add($code, $pct); $admin_msg = "Promo created: $code - $pct%"; }
  }
  if (isset($_POST['manual_payout'])) {
    $tx = intval($_POST['tx_id'] ?? 0);
    $row = ledger_find($tx);
    if ($row) { ledger_update_status($tx, 'Completed'); $_SESSION['balances'][$row['seller']] = 0; $admin_msg = "Manual payout completed: TX $tx"; }
  }
}
$productsStmt = $pdo->query("SELECT p.id, p.name, p.price, p.created_at, u.username AS owner FROM products p LEFT JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC");
$products = $productsStmt->fetchAll();
$listingStatuses = $_SESSION['listing_status'] ?? [];
$productApproval = $_SESSION['product_approval'] ?? [];
$bookings = array_reverse(bookings_all());
$threads = chat_threads_all();
$threadParam = $_GET['thread'] ?? '';
$activeThread = null;
$activeMessages = [];
if ($threadParam !== '') {
  $meta = chat_thread_meta($threadParam);
  if ($meta) {
    $activeThread = $meta;
    $activeMessages = chat_messages($threadParam);
  }
}
$reviews = reviews_latest(15);
$stats = [
  'listings' => $gigsCount,
  'bookings' => count($bookings),
  'escrow' => count(array_filter($bookings, fn($b)=>($b['status']??'')==='Escrow')),
  'unreadChats' => $notifChats,
  'pendingReviews' => $notifReviews,
];
$ledgerRows = array_reverse(ledger_all());
$alerts_count = count($_SESSION['alerts'] ?? []);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($page_title) ?> Vestral</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/CSS/index.css">
  <style>
    .admin-wrap{max-width:1200px;margin:2rem auto;padding:1rem;}
    .stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-top:1rem;}
    .stat-card{background:#f8f9ff;border-radius:12px;padding:1rem;}
    .stat-card span{display:block;color:#666;font-size:.85rem;}
    .stat-card strong{display:block;font-size:1.8rem;color:#0b5ed7;}
    .admin-grid{display:grid;grid-template-columns:2fr 1fr;gap:1rem;margin-top:1.5rem;}
    @media(max-width:1100px){.admin-grid{grid-template-columns:1fr}}
    .cardx{background:#fff;border-radius:14px;box-shadow:0 15px 35px rgba(0,0,0,.08);padding:1.25rem;}
    .cardx h3{margin-bottom:.8rem;color:#0b5ed7}
    table.table{width:100%;border-collapse:collapse}
    table.table th,table.table td{padding:.6rem;border-bottom:1px solid #eef0ff;text-align:left;font-size:.95rem;}
    .controls{display:flex;gap:.6rem;flex-wrap:wrap;}
    .controls input,.controls select{padding:.55rem;border:1px solid #dfe2ec;border-radius:10px;}
    .chat-panel{max-height:320px;overflow-y:auto;margin-bottom:1rem;}
    .message-bubble{padding:.6rem .8rem;border-radius:12px;margin-bottom:.4rem;max-width:85%;}
    .from-me{background:#e6f0ff;margin-left:auto;text-align:right;}
    .from-them{background:#f4f4f4;}
    .badge-alert{display:inline-block;background:#ffe0e0;color:#b42318;padding:.25rem .6rem;border-radius:999px;margin-left:.4rem;}
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
      <a href="buyer_dashboard.php">Buyer Hub<?php if($notifReviews>0): ?><span class="nav-pill"><?= $notifReviews ?></span><?php endif; ?></a>
      <a href="seller_dashboard.php">Seller Hub<?php if($notifChats>0): ?><span class="nav-pill"><?= $notifChats ?></span><?php endif; ?></a>
      <a href="admin.php">Admin</a>
    </nav>
    <div class="navbar-right">
      <?php if (current_user_id()): ?>
        <?php $initial = strtoupper(substr($_SESSION['username'] ?? 'U',0,1)); ?>
        <div class="profile" id="profileBtn">
          <div class="profile-initial"><?= $initial ?></div>
          <?php if (($notifChats + $notifReviews) > 0): ?><span class="profile-badge"><?= $notifChats + $notifReviews ?></span><?php endif; ?>
        </div>
        <div class="dropdown" id="profileMenu">
          <a href="buyer_dashboard.php">Buyer Hub</a>
          <a href="seller_dashboard.php">Seller Hub</a>
          <a href="my_account.php">My Account</a>
          <a href="add_product.php">Add Listing</a>
          <hr><a href="logout.php" style="color: var(--color-danger);">Logout</a>
        </div>
      <?php else: ?>
        <a href="login.php" class="btn-login">Login</a>
        <a href="register.php" class="btn-login">Register</a>
      <?php endif; ?>
    </div>
  </header>

  <div class="admin-wrap">
    <div style="display:flex;flex-wrap:wrap;gap:1rem;justify-content:space-between;align-items:center;">
      <div>
        <h2>Control Room</h2>
        <p style="color:#666;">Approve listings, advance escrow, moderate chats & reviews.</p>
      </div>
      <div>
        <?php if (!empty($admin_msg)): ?><span class="badge" style="background:#e8f8ef;color:#198754;border:1px solid #c8f0d2;"><?= htmlspecialchars($admin_msg) ?></span><?php endif; ?>
        <?php if ($alerts_count>0): ?><span class="badge-alert"><?= $alerts_count ?> alerts</span><?php endif; ?>
      </div>
    </div>

    <div class="stat-grid">
      <div class="stat-card"><span>Total Users</span><strong><?= $usersCount ?></strong></div>
      <div class="stat-card"><span>Total Sellers</span><strong><?= $sellersCount ?></strong></div>
      <div class="stat-card"><span>Pending Verifications</span><strong><?= count($pendingVer) ?></strong></div>
      <div class="stat-card"><span>Total Gigs</span><strong><?= $stats['listings'] ?></strong></div>
      <div class="stat-card"><span>Suspended Accounts</span><strong><?= $suspendedCount ?></strong></div>
    </div>

    <div class="admin-grid">
      <div>
        <div class="cardx">
          <h3>Listings Control</h3>
          <?php if (empty($products)): ?>
            <p>No listings yet.</p>
          <?php else: ?>
            <table class="table">
              <tr><th>Title</th><th>Owner</th><th>Price</th><th>Visibility</th><th>Actions</th></tr>
              <?php foreach ($products as $prod):
                $status = $listingStatuses[$prod['id']] ?? 'active';
                $toggle = $status === 'active' ? 'paused' : 'active';
                $approval = $productApproval[$prod['id']] ?? 'pending';
              ?>
              <tr>
                <td><?= htmlspecialchars($prod['name']) ?></td>
                <td><?= htmlspecialchars($prod['owner'] ?? 'Unknown') ?></td>
                <td>ZMW <?= number_format($prod['price'],2) ?></td>
                <td><span class="badge"><?= ucfirst($approval) ?></span></td>
                <td>
                  <form method="post" action="admin_actions.php" class="controls" style="gap:.3rem;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="toggle_listing">
                    <input type="hidden" name="product_id" value="<?= intval($prod['id']) ?>">
                    <input type="hidden" name="next_status" value="<?= $toggle ?>">
                    <input type="hidden" name="redirect" value="admin.php">
                    <button class="btn btn-outline" type="submit">Set <?= ucfirst($toggle) ?></button>
                  </form>
                  <form method="post" action="admin_actions.php" class="controls" style="gap:.3rem;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="approve_gig">
                    <input type="hidden" name="product_id" value="<?= intval($prod['id']) ?>">
                    <input type="hidden" name="redirect" value="admin.php">
                    <button class="btn" type="submit" style="background:#28a745;color:#fff;">Approve</button>
                  </form>
                  <form method="post" action="admin_actions.php" class="controls" style="gap:.3rem;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="hide_gig">
                    <input type="hidden" name="product_id" value="<?= intval($prod['id']) ?>">
                    <input type="hidden" name="redirect" value="admin.php">
                    <button class="btn" type="submit" style="background:#dc3545;color:#fff;">Hide</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </table>
          <?php endif; ?>
        </div>

        <div class="cardx" style="margin-top:1rem;">
          <h3>User Management</h3>
          <?php
            $users = [];
            try { $users = $pdo->query("SELECT id, username, email, role FROM users ORDER BY id DESC")->fetchAll(); } catch (Throwable $e) {}
          ?>
          <?php if (empty($users)): ?>
            <p>No users found.</p>
          <?php else: ?>
            <table class="table">
              <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Actions</th></tr>
              <?php foreach ($users as $u): ?>
              <tr>
                <td><?= intval($u['id']) ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge"><?= htmlspecialchars($u['role'] ?? 'buyer') ?></span></td>
                <td style="display:flex;gap:.3rem;">
                  <form method="post" action="admin_actions.php">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="change_role">
                    <input type="hidden" name="user_id" value="<?= intval($u['id']) ?>">
                    <select name="role">
                      <option value="buyer">Buyer</option>
                      <option value="seller">Seller</option>
                    </select>
                    <input type="hidden" name="redirect" value="admin.php">
                    <button class="btn btn-outline" type="submit">Update</button>
                  </form>
                  <form method="post" action="admin_actions.php" onsubmit="return confirm('Delete user?');">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?= intval($u['id']) ?>">
                    <input type="hidden" name="redirect" value="admin.php">
                    <button class="btn" type="submit" style="background:#dc3545;color:#fff;">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </table>
          <?php endif; ?>
        </div>

        <div class="cardx" style="margin-top:1rem;">
          <h3>Seller Verifications</h3>
          <?php if (empty($pendingVer)): ?>
            <p>No pending submissions.</p>
          <?php else: ?>
            <table class="table">
              <tr><th>User</th><th>ID</th><th>Profile</th><th>Description</th><th>Actions</th></tr>
              <?php foreach ($pendingVer as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?php if(!empty($row['id_document'])): ?><a href="<?= htmlspecialchars($row['id_document']) ?>" target="_blank">Open</a><?php else: ?>None<?php endif; ?></td>
                <td><?php if(!empty($row['profile_image'])): ?><a href="<?= htmlspecialchars($row['profile_image']) ?>" target="_blank">Open</a><?php else: ?>None<?php endif; ?></td>
                <td><?= htmlspecialchars($row['description'] ?? '') ?></td>
                <td style="display:flex;gap:.3rem;">
                  <form method="post" action="admin_actions.php">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="verify_approve">
                    <input type="hidden" name="id" value="<?= intval($row['id']) ?>">
                    <input type="hidden" name="redirect" value="admin.php">
                    <button class="btn" type="submit" style="background:#28a745;color:#fff;">Approve</button>
                  </form>
                  <form method="post" action="admin_actions.php">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="verify_reject">
                    <input type="hidden" name="id" value="<?= intval($row['id']) ?>">
                    <input type="hidden" name="redirect" value="admin.php">
                    <button class="btn" type="submit" style="background:#dc3545;color:#fff;">Reject</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </table>
          <?php endif; ?>
        </div>

        <div class="cardx" style="margin-top:1rem;">
          <h3>Bookings & Escrow</h3>
          <?php if (empty($bookings)): ?>
            <p>No bookings recorded.</p>
          <?php else: ?>
            <table class="table">
              <tr><th>Listing</th><th>Buyer</th><th>Seller</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
              <?php foreach ($bookings as $order): ?>
              <tr>
                <td><?= htmlspecialchars($order['listing'] ?? 'Listing') ?></td>
                <td><?= htmlspecialchars($order['buyer_name'] ?? 'Buyer') ?></td>
                <td><?= htmlspecialchars($order['seller'] ?? 'Seller') ?></td>
                <td>ZMW <?= number_format($order['amount'],2) ?></td>
                <td><span class="badge"><?= htmlspecialchars($order['status'] ?? 'Escrow') ?></span></td>
                <td>
                  <?php if (($order['status'] ?? '') === 'Escrow' || ($order['status'] ?? '') === 'Payout'): ?>
                  <form method="post" action="admin_actions.php" class="controls" style="gap:.3rem;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="booking_status">
                    <input type="hidden" name="booking_id" value="<?= intval($order['id']) ?>">
                    <input type="hidden" name="next_status" value="<?= ($order['status'] ?? '') === 'Escrow' ? 'Payout' : 'Completed' ?>">
                    <input type="hidden" name="redirect" value="admin.php">
                    <button class="btn <?= ($order['status'] ?? '') === 'Escrow' ? 'btn-outline' : '' ?>" type="submit" style="<?= ($order['status'] ?? '') === 'Payout' ? 'background:#28a745;color:#fff;' : '' ?>">
                      <?= ($order['status'] ?? '') === 'Escrow' ? 'Move to Payout' : 'Finalize' ?>
                    </button>
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

        <div class="cardx" style="margin-top:1rem;">
          <h3>Reviews & Moderation</h3>
          <?php if (empty($reviews)): ?>
            <p>No reviews yet.</p>
          <?php else: ?>
            <table class="table">
              <tr><th>Listing</th><th>Rating</th><th>Comment</th><th>Author</th><th>Actions</th></tr>
              <?php foreach ($reviews as $rev): ?>
              <tr>
                <td><?= htmlspecialchars(strtoupper($rev['listing_type']) . ' #' . $rev['listing_id']) ?></td>
                <td>★ <?= intval($rev['rating']) ?></td>
                <td><?= htmlspecialchars($rev['comment']) ?></td>
                <td><?= htmlspecialchars($rev['author']) ?></td>
                <td>
                  <form method="post" action="admin_actions.php">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="delete_review">
                    <input type="hidden" name="review_id" value="<?= intval($rev['id']) ?>">
                    <input type="hidden" name="redirect" value="admin.php">
                    <button class="btn" type="submit" style="background:#dc3545;color:#fff;">Remove</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </table>
          <?php endif; ?>
        </div>

        <div class="cardx" style="margin-top:1rem;">
          <h3>Manual Controls</h3>
          <div class="controls" style="flex-direction:column;gap:.6rem;">
            <form method="post" class="controls">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="text" name="username" placeholder="Username or Host">
              <button class="btn" name="suspend_user" style="background:#dc3545;color:#fff;">Suspend</button>
            </form>
            <form method="post" class="controls">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="text" name="code" placeholder="Promo code e.g. SAVE20">
              <input type="number" name="percent" placeholder="Percent e.g. 20">
              <button class="btn" name="promo_add" style="background:#28a745;color:#fff;">Create Promo</button>
            </form>
            <form method="post" class="controls">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="number" name="tx_id" placeholder="Transaction ID">
              <button class="btn" name="manual_payout" style="background:#007bff;color:#fff;">Manual Payout</button>
            </form>
          </div>
          <?php if (!empty($_SESSION['promos'])): ?>
            <p class="badge">Active promos: <?php foreach($_SESSION['promos'] as $k=>$v) echo htmlspecialchars($k)." (".intval($v)."%) "; ?></p>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <div class="cardx">
          <h3>Chats Overview</h3>
          <?php if (empty($threads)): ?>
            <p>No conversations yet.</p>
          <?php else: ?>
            <ul style="max-height:260px;overflow-y:auto;margin-bottom:1rem;">
              <?php foreach ($threads as $thread): ?>
                <li style="padding:.4rem 0;border-bottom:1px solid #f1f1f1;">
                  <strong><?= htmlspecialchars($thread['listing'] ?? 'Listing') ?></strong>
                  <div style="color:#777;font-size:.9rem;">Seller: <?= htmlspecialchars($thread['seller'] ?? '') ?> • Buyer: <?= htmlspecialchars($thread['buyer_name'] ?? '') ?></div>
                  <a class="btn btn-outline" href="admin.php?thread=<?= urlencode($thread['thread_key']) ?>#chatRoom" style="margin-top:.4rem;">Open</a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <?php if ($activeThread): ?>
            <div id="chatRoom">
              <h4>Thread: <?= htmlspecialchars($activeThread['listing'] ?? '') ?></h4>
              <div class="chat-panel">
                <?php if (empty($activeMessages)): ?>
                  <p style="color:#777;">No messages yet.</p>
                <?php else: foreach ($activeMessages as $msg): ?>
                  <div class="message-bubble <?= ($msg['from'] ?? '') === ($activeThread['seller'] ?? '') ? 'from-them' : 'from-me' ?>">
                    <small style="display:block;color:#555;"><?= htmlspecialchars($msg['from'] ?? 'User') ?> • <?= htmlspecialchars($msg['timestamp'] ?? '') ?></small>
                    <div><?= nl2br(htmlspecialchars($msg['body'] ?? '')) ?></div>
                  </div>
                <?php endforeach; endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div class="cardx" style="margin-top:1rem;">
          <h3>Alerts</h3>
          <?php if ($alerts_count > 0): ?>
            <ul>
              <?php foreach(array_reverse($_SESSION['alerts']) as $a): ?><li><?= htmlspecialchars($a) ?></li><?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>No active alerts.</p>
          <?php endif; ?>
        </div>

        <div class="cardx" style="margin-top:1rem;">
          <h3>Ledger Snapshot</h3>
          <?php if (empty($ledgerRows)): ?>
            <p>No ledger entries.</p>
          <?php else: ?>
            <table class="table">
              <tr><th>TX</th><th>Seller</th><th>Amount</th><th>Status</th></tr>
              <?php foreach (array_slice($ledgerRows,0,6) as $row): ?>
                <tr>
                  <td><?= htmlspecialchars($row['id']) ?></td>
                  <td><?= htmlspecialchars($row['seller']) ?></td>
                  <td>ZMW <?= number_format($row['amount'],2) ?></td>
                  <td><?= htmlspecialchars($row['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </table>
          <?php endif; ?>
          <a class="btn btn-outline" href="transactions.php" style="margin-top:.6rem;">Open Ledger</a>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer">&copy; <?= date("Y") ?> Vestral. All rights reserved.</footer>

  <script>
    const profileBtn=document.getElementById('profileBtn');
    const profileMenu=document.getElementById('profileMenu');
    if(profileBtn&&profileMenu){
      profileBtn.addEventListener('click',e=>{e.stopPropagation();profileMenu.classList.toggle('show');});
      document.addEventListener('click',e=>{if(!profileMenu.contains(e.target)&&!profileBtn.contains(e.target)) profileMenu.classList.remove('show');});
    }
  </script>
</body>
</html>
