<?php
require 'config.php';
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, u.username FROM products p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = :id");
$stmt->execute([':id'=>$id]);
$product = $stmt->fetch();
if (!$product) {
    http_response_code(404);
    echo "Product not found.";
    exit;
}
$approval = $_SESSION['product_approval'][$id] ?? 'pending';
$isOwner = intval($product['user_id'] ?? 0) === intval(current_user_id() ?? 0);
if ($approval !== 'approved' && !$isOwner && !is_admin()) {
    http_response_code(403);
    echo "This gig is under review.";
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($product['name']) ?></title>
  <link rel="stylesheet" href="/CSS/index.css">
</head>
<body>
  <div class="header">
    <div><strong>My Marketplace</strong></div>
    <div><a href="index.php" style="color:#fff;">Home</a></div>
  </div>

  <div class="container">
    <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">
      <div style="flex:1; min-width:260px;">
        <?php if (!empty($product['image_path']) && file_exists(__DIR__ . '/uploads/' . $product['image_path'])): ?>
          <img style="width:100%; max-width:500px; border-radius:8px;" src="<?= 'uploads/' . htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        <?php elseif (!empty($product['image_blob'])): 
          // serve as inline base64
          $base = base64_encode($product['image_blob']);
          // try to guess type: for demo, assume jpeg
          $src = "data:image/jpeg;base64,$base";
        ?>
          <img style="width:100%; max-width:500px; border-radius:8px;" src="<?= $src ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        <?php else: ?>
          <img style="width:100%; max-width:500px; border-radius:8px;" src="https://via.placeholder.com/600x400?text=No+Image" alt="no image">
        <?php endif; ?>
      </div>

      <div style="flex:1; min-width:240px;">
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <div class="price">$<?= number_format($product['price'],2) ?></div>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin:.5rem 0;">
          <span style="display:inline-block;background:#eef5ff;color:#0b5ed7;padding:.25rem .6rem;border-radius:999px;">Mobile Money Accepted</span>
          <span style="display:inline-block;background:#eef5ff;color:#0b5ed7;padding:.25rem .6rem;border-radius:999px;">ZRA Invoice Ready</span>
          <span style="display:inline-block;background:#eef5ff;color:#0b5ed7;padding:.25rem .6rem;border-radius:999px;">Co-Living / Training Bundle</span>
        </div>
        <p><strong>Seller:</strong> <?= htmlspecialchars($product['username'] ?? 'Unknown') ?></p>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        <div style="display:flex; gap:.6rem; margin-top:.6rem; flex-wrap:wrap;">
          <a href="#" class="btn" id="payBtn" style="background:#28a745;color:#fff;">Pay via Mobile Money</a>
          <a href="#" class="btn" id="zraBtn" style="border-color:#007bff;color:#007bff;">Get ZRA Receipt</a>
        </div>
      </div>
    </div>
  </div>
  <div class="modal" id="payModal">
    <div class="modal-content">
      <span class="modal-close" id="payClose">&times;</span>
      <h3>Mobile Money Payment</h3>
      <label>Provider</label>
      <select id="mmProvider" style="width:100%;padding:.6rem;border:1px solid #ddd;border-radius:8px;">
        <option>MTN MoMo</option>
        <option>Airtel Money</option>
        <option>Zamtel Kwacha</option>
      </select>
      <label style="margin-top:.6rem;">Phone</label>
      <input id="mmPhone" type="text" style="width:100%;padding:.6rem;border:1px solid #ddd;border-radius:8px;" placeholder="e.g. 097XXXXXXX">
      <label style="margin-top:.6rem;">Amount</label>
      <input id="mmAmount" type="number" style="width:100%;padding:.6rem;border:1px solid #ddd;border-radius:8px;" value="<?= number_format($product['price'],2) ?>">
      <button id="mmPayBtn" class="btn" style="background:#28a745;color:#fff;margin-top:.8rem;width:100%">Simulate Pay</button>
    </div>
  </div>
  <div class="modal" id="zraModal">
    <div class="modal-content">
      <span class="modal-close" id="zraClose">&times;</span>
      <h3>ZRA Receipt</h3>
      <p id="zraInfo">Receipt ready</p>
      <button class="btn" id="zraOk" style="background:#007bff;color:#fff;width:100%">Done</button>
    </div>
  </div>
  <script>
    const payBtn=document.getElementById('payBtn');
    const zraBtn=document.getElementById('zraBtn');
    const payModal=document.getElementById('payModal');
    const zraModal=document.getElementById('zraModal');
    const payClose=document.getElementById('payClose');
    const zraClose=document.getElementById('zraClose');
    const zraOk=document.getElementById('zraOk');
    const mmPayBtn=document.getElementById('mmPayBtn');
    payBtn.addEventListener('click',e=>{e.preventDefault();payModal.classList.add('show')});
    zraBtn.addEventListener('click',e=>{e.preventDefault();zraModal.classList.add('show')});
    payClose.addEventListener('click',()=>payModal.classList.remove('show'));
    zraClose.addEventListener('click',()=>zraModal.classList.remove('show'));
    zraOk.addEventListener('click',()=>zraModal.classList.remove('show'));
    window.addEventListener('click',e=>{if(e.target===payModal) payModal.classList.remove('show'); if(e.target===zraModal) zraModal.classList.remove('show');});
    mmPayBtn.addEventListener('click',()=>{
      payModal.classList.remove('show');
      const prov=document.getElementById('mmProvider').value;
      const phone=document.getElementById('mmPhone').value;
      const amt=parseFloat(document.getElementById('mmAmount').value||'0').toFixed(2);
      const rec='ZRA-'+Math.random().toString(36).substring(2,8).toUpperCase();
      document.getElementById('zraInfo').textContent=`Provider: ${prov} | Phone: ${phone} | Amount: ZMW ${amt} | Receipt: ${rec}`;
      zraModal.classList.add('show');
    });
  </script>
</body>
</html>
