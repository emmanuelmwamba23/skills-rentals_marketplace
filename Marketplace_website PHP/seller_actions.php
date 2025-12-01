<?php
require 'config.php';
require_login();
require_role('seller');
ensure_csrf_post();

$action = $_POST['action'] ?? '';
$redirect = 'seller_dashboard.php';

if ($action === 'toggle_listing') {
    $productId = intval($_POST['product_id'] ?? 0);
    $next = $_POST['next_status'] ?? 'active';
    if ($productId > 0) {
        $_SESSION['listing_status'][$productId] = $next;
        $_SESSION['seller_alerts'][] = "Listing #{$productId} is now " . ucfirst($next) . '.';
        add_activity("Listing {$productId} set {$next}");
    }
    header("Location: {$redirect}");
    exit;
}

if ($action === 'booking_status') {
    $bookingId = intval($_POST['booking_id'] ?? 0);
    $next = $_POST['next_status'] ?? '';
    $booking = booking_find($bookingId);
    if ($booking && ($booking['seller'] ?? '') === (current_username() ?? '')) {
        booking_update_status($bookingId, $next);
        ledger_update_status($bookingId, $next);
        $_SESSION['seller_alerts'][] = "Booking {$bookingId} moved to {$next}.";
        add_activity("Booking {$bookingId} • {$next}");
    } else {
        $_SESSION['seller_alerts'][] = "Unable to update booking.";
    }
    header("Location: {$redirect}");
    exit;
}

$_SESSION['seller_alerts'][] = 'Unknown seller action.';
header("Location: {$redirect}");
exit;


