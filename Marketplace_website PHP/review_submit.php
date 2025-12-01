<?php
require 'config.php';
require_login();
ensure_csrf_post();

$type = trim($_POST['type'] ?? '');
$listingId = intval($_POST['listing_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 5);
$comment = trim($_POST['comment'] ?? '');
$username = current_username() ?? 'User';
$redirect = $_SERVER['HTTP_REFERER'] ?? 'buyer_dashboard.php';

if ($type === '' || $listingId <= 0) {
    $_SESSION['buyer_alerts'][] = 'Invalid listing.';
    header("Location: {$redirect}");
    exit;
}

$hasBooking = false;
foreach (bookings_for_user(current_user_id()) as $booking) {
    if (($booking['type'] ?? '') === $type && intval($booking['listing_id'] ?? 0) === $listingId) {
        $hasBooking = true;
        break;
    }
}

if (!$hasBooking) {
    $_SESSION['buyer_alerts'][] = 'You need a booking before leaving a review.';
    header("Location: {$redirect}");
    exit;
}

if (reviews_has_by($type, $listingId, $username)) {
    $_SESSION['buyer_alerts'][] = 'You already reviewed this listing.';
    header("Location: {$redirect}");
    exit;
}

if ($comment === '') {
    $comment = 'Great experience!';
}

reviews_add($type, $listingId, $rating, $comment, $username);
add_activity("Review posted • {$type} #{$listingId}");
$_SESSION['buyer_alerts'][] = 'Review submitted.';

header("Location: {$redirect}");
exit;

