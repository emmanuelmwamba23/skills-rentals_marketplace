<?php
require 'config.php';
require_admin();
ensure_csrf_post();

$action = $_POST['action'] ?? '';
$redirect = $_POST['redirect'] ?? 'admin.php';

switch ($action) {
    case 'toggle_listing':
        $productId = intval($_POST['product_id'] ?? 0);
        $next = $_POST['next_status'] ?? 'active';
        if ($productId > 0) {
            $_SESSION['listing_status'][$productId] = $next;
            $_SESSION['alerts'][] = "Listing {$productId} set to {$next}";
        }
        break;
    case 'booking_status':
        $bookingId = intval($_POST['booking_id'] ?? 0);
        $next = $_POST['next_status'] ?? '';
        if ($bookingId > 0 && $next !== '') {
            booking_update_status($bookingId, $next);
            ledger_update_status($bookingId, $next);
            $_SESSION['alerts'][] = "Booking {$bookingId} updated to {$next}";
        }
        break;
    case 'delete_review':
        $reviewId = intval($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            reviews_delete($reviewId);
            $_SESSION['alerts'][] = "Review {$reviewId} removed.";
        }
        break;
    case 'verify_approve':
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $rowStmt = $pdo->prepare('SELECT user_id, username FROM seller_verifications WHERE id = :id');
                $rowStmt->execute([':id' => $id]);
                $ver = $rowStmt->fetch();
                $pdo->prepare("UPDATE seller_verifications SET status = 'approved' WHERE id = :id")->execute([':id'=>$id]);
                if ($ver) {
                    $uid = intval($ver['user_id']);
                    $pdo->prepare('UPDATE users SET role = "seller" WHERE id = :id')->execute([':id'=>$uid]);
                    role_set($uid, 'seller');
                    $_SESSION['seller_verified'][$uid] = true;
                }
            } catch (Throwable $e) {}
            $_SESSION['alerts'][] = "Seller verification approved #{$id}.";
        }
        break;
    case 'verify_reject':
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try { $stmt = $pdo->prepare("UPDATE seller_verifications SET status = 'rejected' WHERE id = :id"); $stmt->execute([':id'=>$id]); } catch (Throwable $e) {}
            $_SESSION['alerts'][] = "Seller verification rejected #{$id}.";
        }
        break;
    case 'approve_gig':
        $productId = intval($_POST['product_id'] ?? 0);
        if ($productId > 0) { $_SESSION['product_approval'][$productId] = 'approved'; $_SESSION['alerts'][] = "Gig {$productId} approved."; }
        break;
    case 'hide_gig':
        $productId = intval($_POST['product_id'] ?? 0);
        if ($productId > 0) { $_SESSION['product_approval'][$productId] = 'hidden'; $_SESSION['alerts'][] = "Gig {$productId} hidden."; }
        break;
    case 'delete_user':
        $userId = intval($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $stmt->execute([':id'=>$userId]);
            } catch (Throwable $e) {}
            $_SESSION['alerts'][] = "User {$userId} deleted.";
        }
        break;
    case 'change_role':
        $userId = intval($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? '';
        if ($userId > 0 && in_array($role, ['buyer','seller','admin'], true)) { role_set($userId, $role); $_SESSION['alerts'][] = "User {$userId} role set to {$role}."; }
        break;
    default:
        $_SESSION['alerts'][] = 'Unknown admin action.';
        break;
}

header("Location: {$redirect}");
exit;

