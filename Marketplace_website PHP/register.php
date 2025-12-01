<?php
require 'config.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username === '' || $email === '' || $password === '' ) {
        $errors[] = "Please fill in required fields.";
    }
    if (!preg_match('/^[A-Za-z0-9_]{3,24}$/', $username)) {
        $errors[] = "Username must be 3–24 characters, letters/numbers/underscore only.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }
    $strong = (strlen($password)>=8) && preg_match('/[A-Z]/',$password) && preg_match('/[a-z]/',$password) && preg_match('/\d/',$password);
    if (!$strong) { $errors[] = "Password must be at least 8 chars with upper, lower, and a digit."; }
    if ($password !== $password2) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // check existing
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u OR email = :e");
        $stmt->execute([':u'=>$username, ':e'=>$email]);
        if ($stmt->fetch()) {
            $errors[] = "Username or email already exists.";
        } else {
            $pwd_hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:u,:e,:p)");
            $ins->execute([':u'=>$username, ':e'=>$email, ':p'=>$pwd_hash]);
            // auto login
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header("Location: index.php");
            exit;
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Register</title>
  <link rel="stylesheet" href="/CSS/log.css">
</head>
<body>
  <div class="container">
    <h2>Register</h2>
    <?php if(!empty($errors)): ?>
      <div style="color:#b00020; margin-bottom:12px;">
        <?php foreach($errors as $e) echo "<div>" . htmlspecialchars($e) . "</div>"; ?>
      </div>
    <?php endif; ?>
    <form method="post" class="form" novalidate>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <label>Username</label>
      <input class="input" name="username" required>
      <label>Email</label>
      <input class="input" name="email" type="email" required>
      <label>Password</label>
      <input class="input" name="password" type="password" required>
      <label>Repeat Password</label>
      <input class="input" name="password2" type="password" required>
      <button class="btn" type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
  </div>
</body>
</html>
