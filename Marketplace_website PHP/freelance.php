<?php
require 'config.php';
require_once 'catalog.php';
$page_title = "Freelance |";
$notifChats = unread_chat_count();
$notifReviews = pending_review_count();
$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$sort = $_GET['sort'] ?? '';
$gigs = catalog_all('freelance');
$categories = array_values(array_unique(array_map(fn($g) => $g['category'], $gigs)));
sort($categories);
$filtered = array_filter($gigs, function($g) use ($q,$category,$min_price,$max_price){
  if ($q && stripos($g['title'],$q)===false && stripos($g['seller'],$q)===false) return false;
  if ($category && $category!=='' && $g['category']!==$category) return false;
  if ($min_price!==null && $g['price']<$min_price) return false;
  if ($max_price!==null && $g['price']>$max_price) return false;
  if (function_exists('is_suspended') && is_suspended($g['seller'])) return false;
  return true;
});
if ($sort==='price_asc') usort($filtered, fn($a,$b)=>$a['price']<=>$b['price']);
if ($sort==='price_desc') usort($filtered, fn($a,$b)=>$b['price']<=>$a['price']);
if ($sort==='rating') usort($filtered, fn($a,$b)=>$b['rating']<=>$a['rating']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $page_title; ?> Vestral</title>
  <link rel="stylesheet" href="/CSS/index.css">
  <style>
    .freelance-hero{background:#0a0a0a url('https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=1600&auto=format&fit=crop') center/cover no-repeat; height:60vh; position:relative; display:flex; align-items:center; justify-content:center; color:#fff}
    .freelance-hero::after{content:'';position:absolute;inset:0;background:linear-gradient(rgba(0,0,0,.55),rgba(0,0,0,.35))}
    .freelance-hero .inner{position:relative;z-index:2;max-width:1100px;padding:2rem;text-align:center}
    .searchbar{display:flex;gap:.8rem;flex-wrap:wrap;justify-content:center;margin-top:1rem}
    .searchbar input,.searchbar select{padding:.8rem 1rem;border:1px solid #ddd;border-radius:8px;min-width:260px}
    .searchbar .btn{background:#28a745;color:#fff}
    .layout{display:grid;grid-template-columns:300px 1fr;gap:1.5rem}
    .sidebar{background:#fff;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.1);padding:1rem}
    .sidebar h4{margin-bottom:.8rem;color:#007bff}
    .sidebar label{display:block;margin:.6rem 0 .3rem;font-weight:600}
    .sidebar input,.sidebar select{width:100%;padding:.6rem;border:1px solid #ddd;border-radius:8px}
    .toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;gap:.8rem;flex-wrap:wrap}
    .badge{display:inline-block;background:#eef5ff;color:#0b5ed7;padding:.25rem .6rem;border-radius:999px;margin:.2rem;font-size:.85rem}
    @media(max-width:900px){.layout{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <header class="navbar">
    <div class="navbar-left">
      <div class="logo"><span>Vestral</span></div>
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

  <section class="freelance-hero">
    <div class="inner">
      <h1>Find Talent, Get Work</h1>
      <p>Search gigs by skill, budget, and delivery like Fiverr and Upwork.</p>
      <form class="searchbar" method="get" action="freelance.php">
        <input type="text" name="q" placeholder="Search services, e.g. logo design" value="<?= htmlspecialchars($q) ?>">
        <select name="category">
          <option value="">All Categories</option>
          <?php foreach ($categories as $c): $sel = $category===$c ? 'selected' : ''; ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $sel ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Search</button>
      </form>
      <div>
        <?php foreach ($categories as $c): ?>
          <span class="badge"><?= htmlspecialchars($c) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <div class="container">
    <div class="layout">
      <aside class="sidebar">
        <h4>Filters</h4>
        <form method="get" action="freelance.php">
          <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
          <label>Category</label>
          <select name="category">
            <option value="">All</option>
            <?php foreach ($categories as $c): $sel = $category===$c ? 'selected' : ''; ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= $sel ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
          <label>Min Price</label>
          <input type="number" name="min_price" step="1" value="<?= $min_price!==null? htmlspecialchars($min_price):'' ?>">
          <label>Max Price</label>
          <input type="number" name="max_price" step="1" value="<?= $max_price!==null? htmlspecialchars($max_price):'' ?>">
          <label>Sort</label>
          <select name="sort">
            <option value="">Featured</option>
            <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price: Low to High</option>
            <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price: High to Low</option>
            <option value="rating" <?= $sort==='rating'?'selected':'' ?>>Top Rated</option>
          </select>
          <button class="btn" style="margin-top:.8rem;width:100%" type="submit">Apply</button>
        </form>
      </aside>

      <main>
        <div class="toolbar">
          <div><strong><?= count($filtered) ?></strong> services found</div>
          <form method="get" action="freelance.php" style="display:flex; gap:.6rem; align-items:center; flex-wrap:wrap;">
            <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
            <input type="hidden" name="min_price" value="<?= $min_price!==null?htmlspecialchars($min_price):'' ?>">
            <input type="hidden" name="max_price" value="<?= $max_price!==null?htmlspecialchars($max_price):'' ?>">
            <label>Sort</label>
            <select name="sort" style="padding:.5rem;border:1px solid #ddd;border-radius:8px">
              <option value="">Featured</option>
              <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price: Low to High</option>
              <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price: High to Low</option>
              <option value="rating" <?= $sort==='rating'?'selected':'' ?>>Top Rated</option>
            </select>
            <button class="btn" type="submit" style="background:#007bff;color:#fff">Apply</button>
            <button class="btn" type="button" id="loadMore" style="border-color:#007bff;color:#007bff">Load More</button>
            <a href="add_product.php" class="btn" style="background:#28a745;color:#fff">Post a Job</a>
          </form>
        </div>
        <div class="grid">
          <?php if (empty($filtered)): ?>
            <p>No services match your filters.</p>
          <?php else: foreach ($filtered as $g):
            $meta = reviews_summary('freelance', $g['id'], $g['rating'] ?? 4.8, $g['reviews'] ?? 0);
          ?>
            <div class="card">
              <img src="<?= htmlspecialchars($g['image']) ?>" alt="<?= htmlspecialchars($g['title']) ?>">
              <div class="card-body">
                <h3><?= htmlspecialchars($g['title']) ?></h3>
                <div style="display:flex;justify-content:space-between;align-items:center;margin:.4rem 0">
                  <span><?= htmlspecialchars($g['seller']) ?></span>
                  <span>★ <?= number_format($meta['rating'],1) ?> (<?= intval($meta['count']) ?>)</span>
                </div>
                <div class="price">Starting at ZMW <?= number_format($g['price'],2) ?></div>
                <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-top:.4rem;">
                  <span class="badge">Reputation <?= intval(($g['rating'] ?? 4.5)*20) ?>%</span>
                  <?php foreach($g['tags'] ?? [] as $tag): ?>
                    <span class="badge"><?= htmlspecialchars($tag) ?></span>
                  <?php endforeach; ?>
                  <?php if (($g['price'] ?? 0) >= 5000): ?>
                    <span class="badge">ISV Verified</span>
                  <?php endif; ?>
                  <span class="badge">Mobile Money ✔</span>
                </div>
              </div>
              <div class="card-footer">
                <a class="btn btn-outline btn-view" href="#"
                   data-title="<?= htmlspecialchars($g['title']) ?>"
                   data-seller="<?= htmlspecialchars($g['seller']) ?>"
                   data-rating="<?= number_format($g['rating'],1) ?>"
                   data-reviews="<?= intval($g['reviews']) ?>"
                   data-price="ZMW <?= number_format($g['price'],2) ?>"
                   data-image="<?= htmlspecialchars($g['image']) ?>"
                >View Details</a>
                <a class="btn btn-offer" href="#" style="background:#28a745;color:#fff">Quick Pay</a>
                <a class="btn btn-outline" href="listing_detail.php?type=freelance&id=<?= intval($g['id']) ?>">Open Page</a>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </main>
    </div>
  </div>

  <script>
    const btn=document.getElementById('profileBtn');
    const menu=document.getElementById('profileMenu');
    if(btn){btn.addEventListener('click',()=>{menu.classList.toggle('show')});}
    window.addEventListener('click',e=>{if(menu&& !menu.contains(e.target) && !btn.contains(e.target)){menu.classList.remove('show')}});
    // Gig details modal
    (function(){
      const modal=document.createElement('div');
      modal.id='gigModal';
      modal.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;z-index:1000';
      modal.innerHTML=`<div style="background:#fff;border-radius:12px;max-width:640px;width:92%;box-shadow:0 10px 30px rgba(0,0,0,.2);overflow:hidden">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
          <h2 id="gigTitle" style="margin:0;font-size:1.25rem"></h2>
          <button id="gigClose" style="border:none;background:#f5f5f5;padding:.4rem .6rem;border-radius:8px;cursor:pointer">Close</button>
        </div>
        <div style="padding:1rem 1.25rem">
          <img id="gigImage" alt="" style="width:100%;height:220px;object-fit:cover;border-radius:8px;margin-bottom:.75rem">
          <div id="gigMeta" style="color:#555;margin-bottom:.5rem"></div>
          <div id="gigPrice" style="font-weight:700;color:#0b5ed7"></div>
        </div>
        <div style="padding:1rem 1.25rem;border-top:1px solid #eee;display:flex;gap:.6rem;justify-content:flex-end">
          <button id="gigOk" class="btn" style="background:#28a745;color:#fff;border:none;padding:.5rem .9rem;border-radius:8px;cursor:pointer">Request Offer</button>
        </div>
      </div>`;
      document.body.appendChild(modal);
      const show=()=>modal.style.display='flex';
      const hide=()=>modal.style.display='none';
      modal.addEventListener('click',e=>{if(e.target===modal) hide();});
      modal.querySelector('#gigClose').addEventListener('click',hide);
      modal.querySelector('#gigOk').addEventListener('click',hide);
      document.querySelectorAll('.btn-view').forEach(el=>{
        el.addEventListener('click',e=>{
          e.preventDefault();
          const t=el.dataset.title;
          const s=el.dataset.seller;
          const r=el.dataset.rating;
          const v=el.dataset.reviews;
          const p=el.dataset.price;
          const img=el.dataset.image;
          document.getElementById('gigTitle').textContent=t;
          document.getElementById('gigMeta').textContent=`Seller: ${s} • ★ ${r} (${v})`;
          document.getElementById('gigPrice').textContent=`Starting at ${p}`;
          const im=document.getElementById('gigImage');
          im.src=img; im.alt=t;
          show();
        });
      });
    })();
    (function(){
      const modal=document.createElement('div');
      modal.id='offerModal';
      modal.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;z-index:1000';
      modal.innerHTML=`<div style="background:#fff;border-radius:12px;max-width:520px;width:92%;box-shadow:0 10px 30px rgba(0,0,0,.2);overflow:hidden">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
          <h3 style="margin:0;font-size:1.1rem">Mobile Money Payment</h3>
          <button id="offerClose" style="border:none;background:#f5f5f5;padding:.4rem .6rem;border-radius:8px;cursor:pointer">Close</button>
        </div>
        <div style="padding:1rem 1.25rem">
          <label>Provider</label>
          <select id="offerProv" style="width:100%;padding:.6rem;border:1px solid #ddd;border-radius:8px;">
            <option>MTN MoMo</option><option>Airtel Money</option><option>Zamtel Kwacha</option>
          </select>
          <label style="margin-top:.6rem;">Phone</label>
          <input id="offerPhone" type="text" style="width:100%;padding:.6rem;border:1px solid #ddd;border-radius:8px;" placeholder="e.g. 097XXXXXXX">
          <label style="margin-top:.6rem;">Amount</label>
          <input id="offerAmt" type="number" style="width:100%;padding:.6rem;border:1px solid #ddd;border-radius:8px;" placeholder="Enter amount">
          <label style="margin-top:.6rem;">Promo Code</label>
          <input id="offerPromo" type="text" style="width:100%;padding:.6rem;border:1px solid #ddd;border-radius:8px;" placeholder="e.g. SAVE20">
          <button id="offerPay" class="btn" style="background:#28a745;color:#fff;margin-top:.8rem;width:100%">Simulate Pay</button>
        </div>
      </div>`;
      document.body.appendChild(modal);
      const show=()=>modal.style.display='flex';
      const hide=()=>modal.style.display='none';
      modal.addEventListener('click',e=>{if(e.target===modal) hide();});
      modal.querySelector('#offerClose').addEventListener('click',hide);
      document.querySelectorAll('.btn-offer').forEach(el=>el.addEventListener('click',e=>{e.preventDefault();show();}));
      modal.querySelector('#offerPay').addEventListener('click',()=>{
        const amt=parseFloat(document.getElementById('offerAmt').value||'0');
        const code=(document.getElementById('offerPromo').value||'').trim().toUpperCase();
        const final = code==='SAVE20' ? (amt*0.8) : amt;
        alert('Payment simulated. Amount ZMW '+final.toFixed(2)+(code==='SAVE20'?' • Promo applied':'')+'\nZRA Receipt: '+('ZRA-'+Math.random().toString(36).substring(2,8).toUpperCase()));
        hide();
      });
    })();
    (function(){
      const btn=document.getElementById('loadMore'); if(!btn) return;
      btn.addEventListener('click',()=>{
        const grid=document.querySelector('.grid'); if(!grid) return;
        const cards=Array.from(grid.querySelectorAll('.card')).slice(0,4);
        cards.forEach(c=>grid.appendChild(c.cloneNode(true)));
      });
    })();
  </script>
</body>
</html>