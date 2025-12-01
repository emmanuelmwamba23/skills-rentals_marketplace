<?php
require 'config.php';
require_login();
ensure_csrf_post();
$type = trim($_POST['type'] ?? '');
$title = trim($_POST['title'] ?? '');
$seller = trim($_POST['seller'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$promo = strtoupper(trim($_POST['promo'] ?? ''));
if ($type === '' || $title === '' || $seller === '' || !valid_amount($amount)) { http_response_code(400); exit; }
$final = $promo !== '' ? promo_apply($promo, $amount) : $amount;
$id = ledger_add($type.' | '.$title, $seller, $final, 'Escrow');
$_SESSION['balances'][$seller] = ($_SESSION['balances'][$seller] ?? 0) + $final;
$_SESSION['last_tx'] = $id;
add_activity("Payment initiated • $type • $title • ZMW ".number_format($final,2));
$qs = $promo !== '' ? '?ok=1&promo=1' : '?ok=1';
header('Location: transactions.php' . $qs);
exit;