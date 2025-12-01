<?php
require 'config.php';
require_once 'catalog.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

if (!current_user_id()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Please log in to book.']);
    exit;
}

if (!csrf_check($_POST['csrf'] ?? '')) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid session token.']);
    exit;
}

$typeMap = [
    'housing' => 'housing',
    'home' => 'housing',
    'homes' => 'housing',
    'car' => 'car',
    'cars' => 'car',
    'vehicle' => 'car',
    'freelance' => 'freelance',
    'gig' => 'freelance',
];

$rawType = strtolower(trim($_POST['type'] ?? ''));
if (!isset($typeMap[$rawType])) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Unknown listing type.']);
    exit;
}

$type = $typeMap[$rawType];
$listingId = intval($_POST['listing_id'] ?? 0);
$listing = catalog_find($type, $listingId);

if (!$listing) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Listing not found.']);
    exit;
}

$provider = trim($_POST['provider'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$promo = strtoupper(trim($_POST['promo'] ?? ''));
$notes = trim($_POST['notes'] ?? '');

$allowedProviders = ['MTN MoMo', 'Airtel Money', 'Zamtel Kwacha'];
if (!in_array($provider, $allowedProviders, true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Select a valid Mobile Money provider.']);
    exit;
}

if ($phone === '' || strlen($phone) < 9) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Enter a valid phone number.']);
    exit;
}

$unitCount = 1;
$startDate = null;
$endDate = null;
$deliverBy = null;
$guests = max(1, intval($_POST['guests'] ?? 1));

if ($type === 'freelance') {
    $deliverBy = trim($_POST['deliver_by'] ?? '');
    if ($deliverBy !== '' && !DateTime::createFromFormat('Y-m-d', $deliverBy)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Invalid delivery date.']);
        exit;
    }
} else {
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $startObj = DateTime::createFromFormat('Y-m-d', $startDate);
    if ($startDate === '' || !$startObj) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Check-in/start date is required.']);
        exit;
    }
    $endObj = $endDate !== '' ? DateTime::createFromFormat('Y-m-d', $endDate) : clone $startObj;
    if (!$endObj) {
        $endObj = clone $startObj;
    }
    if ($endObj <= $startObj) {
        $endObj = clone $startObj;
        $endObj->modify('+1 day');
    }
    $unitCount = max(1, (int) $startObj->diff($endObj)->format('%a'));
}

$basePrice = floatval($listing['price'] ?? 0);
if ($basePrice <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Listing price is not configured.']);
    exit;
}

$amount = $basePrice * $unitCount;
$finalAmount = $promo !== '' ? promo_apply($promo, $amount) : $amount;

$refPieces = [
    strtoupper($type),
    '#' . $listing['id'],
    $listing['title'] ?? 'Listing',
];
if ($type !== 'freelance') {
    $refPieces[] = $unitCount . ' ' . ($listing['price_unit'] ?? 'units');
}
$reference = implode(' • ', $refPieces);

$sellerName = $listing['seller'] ?? 'Marketplace Seller';
$ledgerId = ledger_add($reference, $sellerName, $finalAmount, 'Escrow');
$_SESSION['balances'][$sellerName] = ($_SESSION['balances'][$sellerName] ?? 0) + $finalAmount;
$threadKey = 'thread_' . $ledgerId;

if (!isset($_SESSION['bookings'])) {
    $_SESSION['bookings'] = [];
}
$_SESSION['bookings'][] = [
    'id' => $ledgerId,
    'type' => $type,
    'listing_id' => $listingId,
    'listing' => $listing['title'] ?? 'Listing',
    'seller' => $sellerName,
    'buyer_id' => current_user_id(),
    'buyer_name' => current_username() ?? 'Buyer',
    'amount' => $finalAmount,
    'units' => $unitCount,
    'start' => $startDate,
    'end' => $endDate,
    'deliver_by' => $deliverBy,
    'provider' => $provider,
    'phone' => $phone,
    'timestamp' => date('Y-m-d H:i:s'),
    'notes' => $notes,
    'promo' => $promo,
    'guests' => $guests,
    'status' => 'Escrow',
    'thread_key' => $threadKey,
];

$receipt = [
    'number' => 'ZRA-' . strtoupper(substr(md5($ledgerId . microtime(true)), 0, 8)),
    'provider' => $provider,
    'phone' => $phone,
    'timestamp' => date('Y-m-d H:i'),
];

add_activity("Booking created • {$listing['title']} • ZMW " . number_format($finalAmount, 2));
chat_thread_register($threadKey, [
    'thread' => $threadKey,
    'booking_id' => $ledgerId,
    'listing' => $listing['title'] ?? 'Listing',
    'listing_id' => $listingId,
    'type' => $type,
    'seller' => $sellerName,
    'buyer_id' => current_user_id(),
    'buyer_name' => current_username() ?? 'Buyer',
]);

echo json_encode([
    'ok' => true,
    'reference' => $reference,
    'amount' => number_format($finalAmount, 2),
    'units' => $unitCount,
    'status' => 'Escrow',
    'message' => 'Simulated payment captured. Escrow ticket #' . $ledgerId,
    'receipt' => $receipt,
]);

