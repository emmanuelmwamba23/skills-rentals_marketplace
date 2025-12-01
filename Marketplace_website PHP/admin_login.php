<?php
require 'config.php';
$errors = [];
$mode = $_GET['mode'] ?? 'login';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) { $errors[] = 'Invalid CSRF token.'; }
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') { $errors[] = 'Please fill all fields.'; }
    if (!$errors && $mode === 'setup') {
        try {
            $exists = $pdo->prepare('SELECT id FROM admins WHERE username = :u');
            $exists->execute([':u' => $username]);
            if ($exists->fetch()) { $errors[] = 'Admin already exists.'; }
            else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (:u,:p)');
                $ins->execute([':u' => $username, ':p' => $hash]);
                $_SESSION['admin_auth'] = true;
                $_SESSION['admin_user'] = $username;
                header('Location: admin.php');
                exit;
            }
        } catch (Throwable $e) { $errors[] = 'Setup failed.'; }
    }
    if (!$errors && $mode !== 'setup') {
        try {
            $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE username = :u');
            $stmt->execute([':u' => $username]);
            $row = $stmt->fetch();
            if ($row && password_verify($password, $row['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_auth'] = true;
                $_SESSION['admin_user'] = $username;
                header('Location: admin.php');
                exit;
            } else {
                $errors[] = 'Invalid admin credentials.';
            }
        } catch (Throwable $e) { $errors[] = 'Login failed.'; }
    }
}
try { $countAdmins = intval($pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() ?: 0); } catch (Throwable $e) { $countAdmins = 0; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Login</title>
  <link rel="stylesheet" href="/CSS/log.css">
  <style>.switch{margin:.6rem 0;color:#555}</style>
  </head>
<body>
  <div class="container">
    <h2><?= $countAdmins>0 ? 'Admin Login' : 'Setup Admin' ?></h2>
    <?php if($errors): ?>
      <div style="color:#b00020; margin-bottom:12px;">
        <?php foreach($errors as $e) echo "<div>" . htmlspecialchars($e) . "</div>"; ?>
      </div>
    <?php endif; ?>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <label>Username</label>
      <input class="input" name="username" required>
      <label>Password</label>
      <input class="input" name="password" type="password" required>
      <button class="btn" type="submit"><?= $countAdmins>0 ? 'Login' : 'Create Admin' ?></button>
    </form>
    <p class="switch">
      <?php if ($countAdmins>0): ?>
        <a href="login.php">Back to user login</a>
      <?php endif; ?>
    </p>
  </div>
</body>
</html>
