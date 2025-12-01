<?php
require 'config.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    }
    $_SESSION['login_times'] = array_values(array_filter($_SESSION['login_times'] ?? [], fn($t)=>$t>time()-60));
    if (count($_SESSION['login_times']) >= 5) {
        $errors[] = "Too many attempts. Try again in a minute.";
    }
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = "Please fill all fields.";
    } else if (empty($errors)) {
        $_SESSION['login_times'][] = time();
        $stmt = $pdo->prepare("SELECT id, username, role, password_hash FROM users WHERE username = :u OR email = :u");
        $stmt->execute([':u'=>$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            if (function_exists('is_suspended') && is_suspended($username)) {
                $errors[] = "Your account is suspended.";
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'] ?? $username;
                $role = $user['role'] ?? null;
                if ($role) { role_set(intval($user['id']), $role); }
                if (!isset($_SESSION['user_roles'][$user['id']])) { role_set(intval($user['id']), 'buyer'); }
                header("Location: index.php");
                exit;
            }
        } else {
            $errors[] = "Invalid credentials.";
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="/CSS/log.css">
</head>
<body>
  <div class="container">
    <h2>Login</h2>
    <?php if($errors): ?>
      <div style="color:#b00020; margin-bottom:12px;">
        <?php foreach($errors as $e) echo "<div>" . htmlspecialchars($e) . "</div>"; ?>
      </div>
    <?php endif; ?>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <label>Username or Email</label>
      <input class="input" name="username" required>
      <label>Password</label>
      <input class="input" name="password" type="password" required>
      <button class="btn" type="submit">Login</button>
    </form>
    <p>No account? <a href="register.php">Register</a></p>
  </div>
</body>
</html>
