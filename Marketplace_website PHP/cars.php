<?php // c:\Users\Administrator\OneDrive\Desktop\Marketplace_website\cars.php
require 'config.php';
require_once 'catalog.php';
$page_title = "Car Rental |";
$notifChats = unread_chat_count();
$notifReviews = pending_review_count();
$q = trim($_GET['q'] ?? ''); $province = trim($_GET['province'] ?? '');
$min = isset($_GET['min']) ? floatval($_GET['min']) : null;
$max = isset($_GET['max']) ? floatval($_GET['max']) : null;
$rating = isset($_GET['rating']) ? floatval($_GET['rating']) : null;
$vehicles = catalog_all('car');
$list = array_filter($vehicles, function($c) use($q,$province,$min,$max,$rating){
  if ($q && stripos($c['title'],$q)===false) return false;
  if ($province && $c['province']!==$province) return false;
  if ($min!==null && $c['price']<$min) return false;
  if ($max!==null && $c['price']>$max) return false;
  if ($rating!==null && $c['rating']<$rating) return false;
  if (function_exists('is_suspended') && is_suspended($c['seller'] ?? '')) return false;
  return true;
});
$provinces = array_values(array_unique(array_map(fn($x)=>$x['province'], $vehicles)));
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $page_title; ?> Vestral</title>
<link rel="stylesheet" href="/CSS/index.css">
</head><body>
<header class="navbar">
  <div class="navbar-left"><div class="logo"><span>Vestral</span></div></div>
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
      <?php $initial = strtoupper(substr($_SESSION['username'] ?? 'U',0,1)); ?>
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
        <hr><a href="logout.php" style="color: var(--color-danger);">Logout</a>
      </div>
    <?php else: ?>
      <a href="login.php" class="btn-login">Login</a>
      <a href="register.php" class="btn-login">Register</a>
    <?php endif; ?>
  </div>
</header>

<div class="container">
  <h2>Car Hire</h2>
  <form method="get" style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem;">
    <input name="q" placeholder="Search brand/model" value="<?= htmlspecialchars($q) ?>" style="padding:.6rem;border:1px solid #ddd;border-radius:8px;">
    <select name="province" style="padding:.6rem;border:1px solid #ddd;border-radius:8px;"><option value="">All Provinces</option>
      <?php foreach($provinces as $p): $sel=$province===$p?'selected':''; ?><option value="<?= htmlspecialchars($p) ?>" <?= $sel ?>><?= htmlspecialchars($p) ?></option><?php endforeach; ?>
    </select>
    <input name="min" type="number" placeholder="Min price" value="<?= $min!==null?htmlspecialchars($min):'' ?>" style="padding:.6rem;border:1px solid #ddd;border-radius:8px;">
    <input name="max" type="number" placeholder="Max price" value="<?= $max!==null?htmlspecialchars($max):'' ?>" style="padding:.6rem;border:1px solid #ddd;border-radius:8px;">
    <input name="rating" type="number" step="0.1" placeholder="Min rating" value="<?= $rating!==null?htmlspecialchars($rating):'' ?>" style="padding:.6rem;border:1px solid #ddd;border-radius:8px;">
    <button class="btn" type="submit" style="background:#007bff;color:#fff">Filter</button>
  </form>

  <div class="grid">
    <?php if(empty($list)): ?><p>No cars match your filters.</p>
    <?php else: foreach($list as $c):
      $meta = reviews_summary('car', $c['id'], $c['rating'] ?? 4.6, $c['reviews'] ?? 0);
    ?>
      <div class="card">
        <img src="<?= htmlspecialchars($c['image']) ?>" alt="<?= htmlspecialchars($c['title']) ?>">
        <div class="card-body">
          <h3><?= htmlspecialchars($c['title']) ?> • <?= htmlspecialchars($c['province']) ?></h3>
          <div style="display:flex;justify-content:space-between;align-items:center;margin:.4rem 0;">
            <span>★ <?= number_format($meta['rating'],1) ?> (<?= intval($meta['count']) ?>)</span>
            <span class="price">ZMW <span class="priceValue" data-id="<?= $c['id'] ?>"><?= number_format($c['price'],2) ?></span>/day</span><?php if(($_SESSION['role']??'')==='admin'): ?><button class="btn editRateBtn" type="button" data-id="<?= $c['id'] ?>" data-price="<?= htmlspecialchars($c['price']) ?>" style="border-color:#007bff;color:#007bff">Edit</button><?php endif; ?>
          </div>
          <div class="amen" style="display:flex;gap:.4rem;flex-wrap:wrap;">
            <?php foreach($c['amenities'] ?? [] as $feat): ?><span class="badge"><?= htmlspecialchars($feat) ?></span><?php endforeach; ?>
            <?php foreach($c['tags'] ?? [] as $tag): ?><span class="badge" style="background:#f2fff6;color:#1d8c54"><?= htmlspecialchars($tag) ?></span><?php endforeach; ?>
          </div>
        </div>
        <div class="card-footer">
          <form method="post" action="simulate_payment.php" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="type" value="car">
            <input type="hidden" name="title" value="<?= htmlspecialchars($c['title']) ?>">
            <input type="hidden" name="seller" value="<?= htmlspecialchars($c['seller']) ?>">
            <input type="hidden" name="amount" value="<?= htmlspecialchars($c['price']) ?>">
            <input type="text" name="promo" placeholder="Promo code" style="padding:.5rem;border:1px solid #ddd;border-radius:8px">
            <a class="btn btn-outline" href="listing_detail.php?type=car&id=<?= intval($c['id']) ?>">Details</a>
            <button class="btn" type="submit" style="background:#28a745;color:#fff">Hire & Pay</button>
          </form>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<footer class="footer">&copy; <?php echo date("Y"); ?> Vestral. All rights reserved.</footer>
<script>
  const b=document.getElementById('profileBtn'),m=document.getElementById('profileMenu');
  if(b&&m){b.addEventListener('click',e=>{e.stopPropagation();m.classList.toggle('show')});document.addEventListener('click',e=>{if(!m.contains(e.target)&&!b.contains(e.target)) m.classList.remove('show');});}
  document.querySelectorAll('.editRateBtn').forEach(btn=>{
    btn.addEventListener('click',()=>{
      const id=btn.dataset.id; const current=parseFloat(btn.dataset.price||'0');
      const np=prompt('New daily rate (ZMW):', current);
      if(np && !isNaN(parseFloat(np))){
        fetch('update_car_rate.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`id=${encodeURIComponent(id)}&price=${encodeURIComponent(np)}`})
          .then(r=>r.json()).then(j=>{
            if(j.ok){
              const el=document.querySelector(`.priceValue[data-id="${id}"]`);
              if(el){ el.textContent = parseFloat(j.price).toFixed(2); }
              alert('Rate updated');
            } else { alert('Update failed: '+(j.error||'unknown')); }
          }).catch(()=>alert('Network error'));
      }
    });
  });
</script>
</body></html>