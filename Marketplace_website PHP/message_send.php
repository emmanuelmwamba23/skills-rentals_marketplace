<?php
require 'config.php';
require_login();
ensure_csrf_post();

$threadKey = trim($_POST['thread'] ?? '');
$body = trim($_POST['message'] ?? '');
$redirect = $_POST['redirect'] ?? 'buyer_dashboard.php';

if ($threadKey === '' || $body === '') {
    $_SESSION['buyer_alerts'][] = 'Message cannot be empty.';
    header("Location: {$redirect}");
    exit;
}

$meta = chat_thread_meta($threadKey);
if (!$meta) {
    $_SESSION['buyer_alerts'][] = 'Conversation not found.';
    header("Location: {$redirect}");
    exit;
}

$user = current_username() ?? 'User';
$role = 'buyer';
$userId = current_user_id();

if (($meta['seller'] ?? '') === $user) {
    $role = 'seller';
} elseif (intval($meta['buyer_id'] ?? 0) === $userId) {
    $role = 'buyer';
} else {
    $_SESSION['buyer_alerts'][] = 'You are not part of this conversation.';
    header("Location: {$redirect}");
    exit;
}

chat_add_message($threadKey, $user, $role, $body);
chat_mark_thread_read($threadKey, $role);
add_activity("Message sent • {$meta['listing']} • {$role}");

if ($role === 'seller') {
    $_SESSION['seller_alerts'][] = 'Message delivered.';
} else {
    $_SESSION['buyer_alerts'][] = 'Message sent.';
}

header("Location: {$redirect}");
exit;

