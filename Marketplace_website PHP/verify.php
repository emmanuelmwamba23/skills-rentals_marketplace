<?php // c:\Users\Administrator\OneDrive\Desktop\Marketplace_website\verify.php
require 'config.php';
require_login();
$page_title = "Verification |";
$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) { http_response_code(400); exit; }
  $uid = intval(current_user_id());
  $user = current_username() ?? 'User';
  $desc = trim($_POST['description'] ?? '');
  $idPath = null;
  $profilePath = null;
  if (!is_dir(__DIR__ . '/Uploads')) { mkdir(__DIR__ . '/Uploads', 0777, true); }
  if (!empty($_FILES['id_document']['name'])) {
    $name = bin2hex(random_bytes(8)) . '.' . pathinfo($_FILES['id_document']['name'], PATHINFO_EXTENSION);
    $dest = __DIR__ . '/Uploads/' . $name;
    if (is_uploaded_file($_FILES['id_document']['tmp_name'])) { move_uploaded_file($_FILES['id_document']['tmp_name'], $dest); $idPath = 'Uploads/' . $name; }
  }
  if (!empty($_FILES['profile_image']['name'])) {
    $name = bin2hex(random_bytes(8)) . '.' . pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $dest = __DIR__ . '/Uploads/' . $name;
    if (is_uploaded_file($_FILES['profile_image']['tmp_name'])) { move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest); $profilePath = 'Uploads/' . $name; }
  }
  try {
    $stmt = $pdo->prepare("INSERT INTO seller_verifications (user_id, username, id_document, profile_image, description, status) VALUES (:uid,:uname,:id,:pic,:desc,'pending')");
    $stmt->execute([':uid'=>$uid, ':uname'=>$user, ':id'=>$idPath, ':pic'=>$profilePath, ':desc'=>$desc]);
    $_SESSION['alerts'][] = "Verification submitted by $user";
    $msg = "Verification submitted. Await admin approval.";
  } catch (Throwable $e) { $msg = "Submission failed."; }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title><?php echo $page_title; ?> Vestral</title><link rel="stylesheet" href="/CSS/index.css"></head>
<body>
<header class="navbar">
  <div class="navbar-left"><div class="logo"><span>Vestral</span></div></div>
  <nav class="navbar-center">
    <a href="index.php">Home</a>
    <a href="housing.php">Housing</a>
    <a href="cars.php">Cars</a>
    <a href="freelance.php">Freelance</a>
    <a href="admin.php">Admin</a>
  </nav>
</header>
<div class="container" style="max-width:800px;">
  <h2>Seller Verification</h2>
  <?php if($msg): ?><div class="badge"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <label>National ID</label>
    <input type="file" name="id_document" required>
    <label style="display:block;margin-top:.6rem;">Profile Picture</label>
    <input type="file" name="profile_image" required>
    <label style="display:block;margin-top:.6rem;">Profile Description</label>
    <textarea name="description" rows="4" style="width:100%;padding:.6rem;border:1px solid #ddd;border-radius:8px"></textarea>
    <button class="btn" type="submit" style="background:#007bff;color:#fff;margin-top:.8rem">Submit</button>
  </form>
</div>
<footer class="footer">&copy; <?php echo date("Y"); ?> Vestral. All rights reserved.</footer>
</body></html>
