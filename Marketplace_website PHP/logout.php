<?php
require 'config.php';
$_SESSION = [];
if (session_id()) { session_unset(); }
session_destroy();
setcookie(session_name(), '', time()-3600, '/');
session_write_close();
header('Location: index.php');
echo '<script>location.href="index.php"</script>';
exit;