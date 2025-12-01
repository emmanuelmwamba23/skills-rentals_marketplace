<?php // c:\Users\Administrator\OneDrive\Desktop\Marketplace_website\housing.php
require 'config.php';
require_once 'catalog.php';
$page_title = "Housing |";
$notifChats = unread_chat_count();
$notifReviews = pending_review_count();
$q = trim($_GET['q'] ?? '');
$province = trim($_GET['province'] ?? '');
$min = isset($_GET['min']) ? floatval($_GET['min']) : null;
$max = isset($_GET['max']) ? floatval($_GET['max']) : null;
$rating = isset($_GET['rating']) ? floatval($_GET['rating']) : null;
$start = trim($_GET['start'] ?? '');
$end = trim($_GET['end'] ?? '');
$guests = isset($_GET['guests']) ? intval($_GET['guests']) : null;
$listings = catalog_all('housing');
$list = array_filter($listings, function($a) use($q,$province,$min,$max,$rating,$guests){
  if ($q && stripos($a['title'],$q)===false && stripos(join(',',$a['amenities'] ?? []),$q)===false) return false;
  if ($province && $a['province']!==$province) return false;
  if ($min!==null && $a['price']<$min) return false;
  if ($max!==null && $a['price']>$max) return false;
  if ($rating!==null && $a['rating']<$rating) return false;
  if ($guests!==null && intval($a['max_guests'] ?? 0) < $guests) return false;
  return true;
});
$provinces = array_values(array_unique(array_map(fn($x)=>$x['province'], $listings)));
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $page_title; ?> Vestral</title>
<link rel="stylesheet" href="/CSS/index.css">
<style>
.container {
  max-width: 1280px;
  margin: 1.5rem auto;
  padding: 0 1rem;
  background: transparent;
  box-shadow: none;
  border-radius: 0;
}

.searchbar {
  display: flex;
  gap: 0.6rem;
  background: #fff;
  border-radius: 999px;
  box-shadow: 0 10px 30px rgba(2, 6, 23, 0.08);
  padding: 0.8rem; /* Increased padding for better visibility */
  align-items: center;
  margin: 1rem auto;
  max-width: 980px;
}

.search-item {
  flex: 1;
  padding: 0.6rem 1rem; /* Adjusted padding for better spacing */
  border-right: 1px solid #eef2ff;
}

.search-item:last-child {
  border-right: none;
}

.search-item label {
  display: block;
  font-size: 0.85rem; /* Increased font size for better readability */
  color: #64748b;
  font-weight: 600;
}

.search-item input {
  width: 100%;
  border: none;
  outline: none;
  padding: 0.4rem; /* Adjusted padding for better spacing */
  font-size: 1rem; /* Increased font size */
  color: #0f172a;
}

.search-btn {
  background: #ff385c;
  color: #fff;
  border-radius: 50%;
  width: 48px; /* Increased size for better visibility */
  height: 48px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  cursor: pointer;
}

.chipbar {
  display: flex;
  gap: 0.8rem; /* Increased gap for better spacing */
  flex-wrap: wrap;
  margin: 0 auto 1rem;
  max-width: 980px;
}

.chip {
  padding: 0.5rem 1rem; /* Adjusted padding for better spacing */
  background: #f3f6ff;
  color: #0b5ed7;
  border-radius: 999px;
  font-size: 0.9rem; /* Increased font size */
}

.layout {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); /* Adjusted for better scaling */
  gap: 1.5rem; /* Increased gap for better spacing */
}

.cards-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); /* Adjusted for better scaling */
  gap: 1.5rem; /* Increased gap for better spacing */
}

.card {
  min-width: 260px; /* Adjusted for better scaling */
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); /* Added shadow for better visibility */
  overflow: hidden;
}

.card img {
  border-radius: 12px 12px 0 0; /* Adjusted for better design */
  height: 180px; /* Increased height for better visibility */
  object-fit: cover;
}

.card-body {
  padding: 1rem; /* Increased padding for better spacing */
}

.card h3 {
  font-size: 1.2rem; /* Increased font size for better readability */
  margin: 0.5rem 0;
}

.card .meta {
  display: flex;
  justify-content: space-between;
  font-size: 1rem; /* Increased font size */
  color: #555;
}

.card .meta .price {
  font-weight: bold;
  color: #ff385c;
}

.fav {
  position: absolute;
  top: 10px;
  right: 10px;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 999px;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.badge-pill {
  position: absolute;
  left: 10px;
  top: 10px;
  background: #eef5ff;
  color: #0b5ed7;
  border-radius: 999px;
  padding: 0.25rem 0.5rem;
  font-size: 0.85rem;
}

.hero {
  position: relative;
  text-align: center;
  padding: 4rem 1rem; /* Increased padding for better spacing */
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)), url('/images/hero-bg.jpg'); /* Add a background image */
  background-size: cover;
  background-position: center;
  color: #fff;
  z-index: 1; /* Ensure the hero section is above other elements */
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.5); /* Ensure text is readable over the background */
  z-index: 2; /* Ensure the overlay is above the background */
}

.hero-content {
  position: relative;
  z-index: 3; /* Ensure the content is above the overlay */
  max-width: 800px;
  margin: 0 auto;
}

header.navbar {
  z-index: 10; /* Ensure the navbar is above the hero section */
  position: relative; /* Prevent overlapping */
  background: rgba(255, 255, 255, 0.9); /* Add transparency to the navbar */
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Add shadow for better visibility */
}

@media (max-width: 900px) {
  .layout {
    grid-template-columns: 1fr; /* Adjusted for smaller screens */
  }

  .searchbar {
    flex-direction: column; /* Stack search items vertically on smaller screens */
    gap: 1rem;
  }

  .search-item {
    padding: 0.5rem; /* Adjusted padding for smaller screens */
  }
}
</style>
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
        <?php if (current_role()==='buyer'): ?>
          <a href="buyer_dashboard.php">Buyer Hub<?php if($notifReviews>0): ?><span class="nav-pill"><?= $notifReviews ?></span><?php endif; ?></a>
          <a href="verify.php">Become a Seller</a>
        <?php endif; ?>
        <?php if (current_role()==='seller'): ?>
          <a href="seller_dashboard.php">Seller Hub<?php if($notifChats>0): ?><span class="nav-pill"><?= $notifChats ?></span><?php endif; ?></a>
          <?php if(is_verified_seller(intval(current_user_id()))): ?><a href="add_product.php">Add Listing</a><?php endif; ?>
        <?php endif; ?>
        <a href="my_account.php">My Account</a>
        <?php if (is_admin()): ?><a href="admin.php">Admin</a><?php endif; ?>
        <hr><a href="logout.php" style="color: var(--color-danger);">Logout</a>
      </div>
    <?php else: ?>
      <a href="login.php" class="btn-login">Login</a>
      <a href="register.php" class="btn-login">Register</a>
    <?php endif; ?>
  </div>
</header>

<section class="hero">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1>Homes, Skills, and Zambia-first features — all in one place</h1>
    <p>Where, when, and who — find stays with Zambia-first features.</p>
    <form class="searchbar" method="get" action="housing.php">
      <div class="search-item" style="flex:2">
        <label>Where</label>
        <input name="q" placeholder="Search destinations" value="<?= htmlspecialchars($q) ?>">
      </div>
      <div class="search-item">
        <label>When</label>
        <input name="start" type="date" value="<?= htmlspecialchars($start) ?>">
      </div>
      <div class="search-item">
        <label>To</label>
        <input name="end" type="date" value="<?= htmlspecialchars($end) ?>">
      </div>
      <div class="search-item">
        <label>Who</label>
        <input name="guests" type="number" min="1" placeholder="Add guests" value="<?= $guests!==null?intval($guests):'' ?>">
      </div>
      <button class="search-btn" aria-label="Search" type="submit">🔍</button>
    </form>
    <div class="chipbar">
      <span class="chip">Guest favorite</span>
      <span class="chip">Solar backup</span>
      <span class="chip">Co‑living</span>
      <span class="chip">Workshop access</span>
    </div>
  </div>
</section>

<div class="container">
  <div class="layout" style="grid-template-columns:1fr">
    <main id="results">
      <div class="toolbar">
        <div><strong><?= count($list) ?></strong> stays found</div>
        <div>
          <span class="badge">Mobile Money: MTN • Airtel • Zamtel</span>
          <span class="badge">ZRA Invoice</span>
        </div>
      </div>
      <div class="cards-row">
        <?php if(empty($list)): ?><p>No stays match your filters.</p>
        <?php else: foreach($list as $a):
          $meta = reviews_summary('housing', $a['id'], $a['rating'] ?? 4.8, $a['reviews'] ?? 0);
        ?>
          <a class="card" href="listing_detail.php?type=housing&id=<?= intval($a['id']) ?>" title="View more">
            <img src="<?= htmlspecialchars($a['image']) ?>" alt="<?= htmlspecialchars($a['title']) ?>">
            <div class="card-body">
              <div class="fav" title="Save">♡</div>
              <h3><?= htmlspecialchars($a['title']) ?></h3>
              <div class="meta">
                <span>★ <?= number_format($meta['rating'],1) ?></span>
                <span class="price">ZMW <?= number_format($a['price'],2) ?>/night</span>
              </div>
            </div>
          </a>
        <?php endforeach; endif; ?>
      </div>
    </main>
  </div>
</div>

<footer class="footer">&copy; <?php echo date("Y"); ?> Vestral. All rights reserved.</footer>
<script>
  const b=document.getElementById('profileBtn'),m=document.getElementById('profileMenu');
  if(b&&m){b.addEventListener('click',e=>{e.stopPropagation();m.classList.toggle('show')});document.addEventListener('click',e=>{if(!m.contains(e.target)&&!b.contains(e.target)) m.classList.remove('show');});}
  document.querySelectorAll('.editPriceBtn').forEach(btn=>{
    btn.addEventListener('click',()=>{
      const id=btn.dataset.id; const current=parseFloat(btn.dataset.price||'0');
      const np=prompt('New nightly price (ZMW):', current);
      if(np && !isNaN(parseFloat(np))){
        fetch('update_price.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`id=${encodeURIComponent(id)}&price=${encodeURIComponent(np)}`})
          .then(r=>r.json()).then(j=>{
            if(j.ok){
              const el=document.querySelector(`.priceValue[data-id="${id}"]`);
              if(el){ el.textContent = parseFloat(j.price).toFixed(2); }
              alert('Price updated');
            } else { alert('Update failed: '+(j.error||'unknown')); }
          }).catch(()=>alert('Network error'));
      }
    });
  });
  (function(){
    const modal=document.createElement('div');
    modal.id='mapModal';
    modal.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;z-index:1000';
    modal.innerHTML=`<div style="background:#fff;border-radius:12px;max-width:640px;width:92%;box-shadow:0 10px 30px rgba(0,0,0,.2);overflow:hidden">
      <div style="padding:1rem 1.25rem;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
        <h2 id="mapTitle" style="margin:0;font-size:1.25rem"></h2>
        <button id="mapClose" style="border:none;background:#f5f5f5;padding:.4rem .6rem;border-radius:8px;cursor:pointer">Close</button>
      </div>
      <div style="padding:1rem 1.25rem">
        <div id="mapMeta" style="color:#555;margin-bottom:.5rem"></div>
        <img id="mapImage" alt="" style="width:100%;height:260px;object-fit:cover;border-radius:8px;" src="https://images.unsplash.com/photo-1502920514313-52581002a659?q=80&w=1200&auto=format&fit=crop">
      </div>
    </div>`;
    document.body.appendChild(modal);
    const show=()=>modal.style.display='flex';
    const hide=()=>modal.style.display='none';
    modal.addEventListener('click',e=>{if(e.target===modal) hide();});
    modal.querySelector('#mapClose').addEventListener('click',hide);
    document.querySelectorAll('.viewMapBtn').forEach(el=>el.addEventListener('click',e=>{
      e.preventDefault();
      document.getElementById('mapTitle').textContent=el.dataset.title;
      document.getElementById('mapMeta').textContent='Province: '+el.dataset.province+' • Zambia';
      show();
    }));
  })();
</script>
</body></html>
