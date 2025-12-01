<?php
require 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf'] ?? '')) {
  header('Location: my_account.php');
  exit;
}
$_SESSION['role'] = 'admin';
add_activity('Admin role granted');
$_SESSION['alerts'][] = 'Admin role granted';
session_write_close();
header('Location: admin.php');
echo '<script>location.href="admin.php"</script>';
exit;