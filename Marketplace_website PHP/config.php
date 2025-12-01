<?php
session_start();

// --------------------------
// Database Configuration
// --------------------------
$DB_HOST = 'localhost';
$DB_NAME = 'marketplace';
$DB_USER = 'root';
$DB_PASS = '';

$opts = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        $opts
    );
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(64) UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS seller_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(64) NOT NULL,
        id_document VARCHAR(255) DEFAULT NULL,
        profile_image VARCHAR(255) DEFAULT NULL,
        description TEXT,
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {}

// --------------------------
// CSRF Helpers
// --------------------------
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
function ensure_csrf_post() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit;
    }
}
function valid_amount($x) {
    return is_numeric($x) && floatval($x) >= 0;
}

function is_verified_seller(int $userId): bool {
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT status FROM seller_verifications WHERE user_id = :id ORDER BY id DESC LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $status = $stmt->fetchColumn();
        if ($status) return $status === 'approved';
    } catch (Throwable $e) {}
    return !empty($_SESSION['seller_verified'][$userId]);
}
function require_verified_seller() {
    $uid = current_user_id();
    if (!$uid || current_role() !== 'seller' || !is_verified_seller($uid)) {
        header('Location: verify.php');
        exit;
    }
}

// --------------------------
// Authentication Helpers
// --------------------------
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_username() {
    return $_SESSION['username'] ?? null;
}

function current_role() {
    $uid = $_SESSION['user_id'] ?? null;
    if (!$uid) return 'guest';
    if (isset($_SESSION['user_roles'][$uid])) return $_SESSION['user_roles'][$uid];
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $uid]);
        $role = $stmt->fetchColumn();
        if ($role) { $_SESSION['user_roles'][$uid] = $role; return $role; }
    } catch (Throwable $e) {}
    return $_SESSION['role'] ?? 'buyer';
}

// --------------------------
// Require Login Helper
// --------------------------
// Use this ONLY on pages that need authentication
function require_login() {
    if (!current_user_id()) {
        header("Location: login.php");
        exit;
    }
}
function is_admin() { return !empty($_SESSION['admin_auth']); }
function require_admin() {
    if (!is_admin()) {
        header("Location: admin_login.php");
        exit;
    }
}
function require_role(string $role) {
    if (current_role() !== $role) {
        header("Location: index.php");
        exit;
    }
}
function role_set(int $userId, string $role) {
    $_SESSION['user_roles'][$userId] = $role;
    $_SESSION['role'] = $role;
}
function add_activity($msg) {
    if (!isset($_SESSION['activity'])) $_SESSION['activity'] = [];
    $_SESSION['activity'][] = date('H:i') . ' • ' . $msg;
}

function ledger_add($ref, $seller, $amount, $status) {
    if (empty($_SESSION['ledger'])) $_SESSION['ledger'] = [];
    $id = count($_SESSION['ledger']) + 1;
    $_SESSION['ledger'][] = ['id'=>$id,'ref'=>$ref,'seller'=>$seller,'amount'=>$amount,'status'=>$status];
    return $id;
}
function ledger_update_status($id, $status) {
    if (empty($_SESSION['ledger'])) return;
    foreach ($_SESSION['ledger'] as &$r) {
        if ($r['id'] === $id) { $r['status'] = $status; break; }
    }
    unset($r);
}
function ledger_all() { return $_SESSION['ledger'] ?? []; }
function ledger_find($id) {
    foreach ($_SESSION['ledger'] ?? [] as $r) { if ($r['id'] === $id) return $r; }
    return null;
}
function suspend_user($name) { if ($name!=='') $_SESSION['suspended'][$name] = true; }
function is_suspended($name) { return !empty($_SESSION['suspended'][$name]); }
function promo_add($code, $percent) { if ($code!=='') $_SESSION['promos'][$code] = floatval($percent); }
function promo_apply($code, $amount) {
    $p = $_SESSION['promos'][$code] ?? 0;
    return $p>0 ? max(0, $amount * (1 - ($p/100))) : $amount;
}

// --------------------------
// SQLite Storage (chats/reviews)
// --------------------------
define('SQLITE_PATH', __DIR__ . '/storage/app.sqlite');
if (!is_dir(__DIR__ . '/storage')) {
    mkdir(__DIR__ . '/storage', 0777, true);
}

function storage_sqlite_available(): bool {
    static $available = null;
    if ($available === null) {
        $available = function_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers(), true);
    }
    return $available;
}

function sqlite_conn(): ?PDO {
    static $sqlite = null;
    if (!storage_sqlite_available()) {
        return null;
    }
    if ($sqlite instanceof PDO) {
        return $sqlite;
    }
    $sqlite = new PDO('sqlite:' . SQLITE_PATH);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlite->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            listing_type TEXT NOT NULL,
            listing_id INTEGER NOT NULL,
            rating INTEGER NOT NULL,
            comment TEXT,
            author TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ");
    $sqlite->exec("
        CREATE TABLE IF NOT EXISTS chat_threads (
            thread_key TEXT PRIMARY KEY,
            booking_id INTEGER,
            listing TEXT,
            listing_id INTEGER,
            listing_type TEXT,
            seller TEXT,
            buyer_id INTEGER,
            buyer_name TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ");
    $sqlite->exec("
        CREATE TABLE IF NOT EXISTS chat_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            thread_key TEXT NOT NULL,
            sender TEXT,
            role TEXT,
            body TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            read_by_seller INTEGER DEFAULT 0,
            read_by_buyer INTEGER DEFAULT 0
        );
    ");
    $sqlite->exec("CREATE INDEX IF NOT EXISTS idx_reviews_lookup ON reviews(listing_type, listing_id);");
    $sqlite->exec("CREATE INDEX IF NOT EXISTS idx_chat_messages_thread ON chat_messages(thread_key);");
    return $sqlite;
}

// --------------------------
// Booking / Review / Chat Helpers
// --------------------------
function bookings_all(): array {
    return $_SESSION['bookings'] ?? [];
}
function bookings_for_seller(string $seller): array {
    return array_values(array_filter(bookings_all(), fn($b) => ($b['seller'] ?? '') === $seller));
}
function bookings_for_user(int $userId): array {
    return array_values(array_filter(bookings_all(), fn($b) => intval($b['buyer_id'] ?? 0) === $userId));
}
function booking_find(int $bookingId): ?array {
    foreach (bookings_all() as $b) {
        if (intval($b['id']) === $bookingId) return $b;
    }
    return null;
}
function booking_update_status(int $bookingId, string $status): void {
    if (empty($_SESSION['bookings'])) return;
    foreach ($_SESSION['bookings'] as &$b) {
        if (intval($b['id']) === $bookingId) {
            $b['status'] = $status;
            break;
        }
    }
    unset($b);
}

function reviews_for(string $type, int $listingId): array {
    if (!storage_sqlite_available()) {
        return $_SESSION['reviews_fallback'][$type][$listingId] ?? [];
    }
    $db = sqlite_conn();
    $stmt = $db->prepare("SELECT id, rating, comment, author, created_at AS timestamp FROM reviews WHERE listing_type = :type AND listing_id = :id ORDER BY id DESC");
    $stmt->execute([':type' => $type, ':id' => $listingId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function reviews_add(string $type, int $listingId, int $rating, string $comment, string $author): void {
    $payload = [
        'id' => uniqid('rv_', true),
        'rating' => max(1, min(5, $rating)),
        'comment' => $comment,
        'author' => $author,
        'timestamp' => date('Y-m-d H:i'),
        'listing_type' => $type,
        'listing_id' => $listingId,
    ];
    if (!storage_sqlite_available()) {
        $_SESSION['reviews_fallback'][$type][$listingId][] = $payload;
        return;
    }
    $db = sqlite_conn();
    $stmt = $db->prepare("INSERT INTO reviews (listing_type, listing_id, rating, comment, author) VALUES (:type, :id, :rating, :comment, :author)");
    $stmt->execute([
        ':type' => $type,
        ':id' => $listingId,
        ':rating' => $payload['rating'],
        ':comment' => $comment,
        ':author' => $author,
    ]);
}
function reviews_has_by(string $type, int $listingId, string $author): bool {
    if (!storage_sqlite_available()) {
        foreach ($_SESSION['reviews_fallback'][$type][$listingId] ?? [] as $row) {
            if (($row['author'] ?? '') === $author) return true;
        }
        return false;
    }
    $db = sqlite_conn();
    $stmt = $db->prepare("SELECT 1 FROM reviews WHERE listing_type = :type AND listing_id = :id AND author = :author LIMIT 1");
    $stmt->execute([':type' => $type, ':id' => $listingId, ':author' => $author]);
    return (bool)$stmt->fetchColumn();
}
function reviews_delete(int $id): void {
    if (!storage_sqlite_available()) {
        foreach ($_SESSION['reviews_fallback'] ?? [] as $type => &$listings) {
            foreach ($listings as $lid => &$rows) {
                $rows = array_values(array_filter($rows, fn($r) => ($r['id'] ?? '') !== $id));
            }
        }
        unset($listings, $rows);
        return;
    }
    $db = sqlite_conn();
    $stmt = $db->prepare("DELETE FROM reviews WHERE id = :id");
    $stmt->execute([':id' => $id]);
}
function reviews_latest(int $limit = 20): array {
    if (!storage_sqlite_available()) {
        $all = [];
        foreach ($_SESSION['reviews_fallback'] ?? [] as $type => $listings) {
            foreach ($listings as $lid => $rows) {
                foreach ($rows as $row) {
                    $all[] = [
                        'id' => $row['id'] ?? uniqid('rv_', true),
                        'listing_type' => $type,
                        'listing_id' => $lid,
                        'rating' => $row['rating'] ?? 5,
                        'comment' => $row['comment'] ?? '',
                        'author' => $row['author'] ?? 'Guest',
                        'created_at' => $row['timestamp'] ?? date('Y-m-d H:i'),
                    ];
                }
            }
        }
        usort($all, fn($a,$b)=>strcmp($b['created_at'],$a['created_at']));
        return array_slice($all, 0, $limit);
    }
    $db = sqlite_conn();
    $stmt = $db->prepare("SELECT id, listing_type, listing_id, rating, comment, author, created_at FROM reviews ORDER BY id DESC LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function reviews_summary(string $type, int $listingId, float $fallbackRating = 4.8, int $fallbackCount = 0): array {
    if (!storage_sqlite_available()) {
        $list = $_SESSION['reviews_fallback'][$type][$listingId] ?? [];
        if (empty($list)) {
            return ['rating' => $fallbackRating, 'count' => $fallbackCount];
        }
        $sum = array_reduce($list, fn($carry,$item)=>$carry + ($item['rating'] ?? 0), 0);
        $count = count($list);
        $totalCount = $fallbackCount + $count;
        $weighted = ($fallbackRating * $fallbackCount + $sum) / max(1, $totalCount);
        return ['rating' => round($weighted,2), 'count' => $totalCount];
    }
    $db = sqlite_conn();
    $stmt = $db->prepare("SELECT COUNT(*) as qty, AVG(rating) as avg_rating FROM reviews WHERE listing_type = :type AND listing_id = :id");
    $stmt->execute([':type' => $type, ':id' => $listingId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['qty' => 0, 'avg_rating' => null];
    $count = intval($row['qty'] ?? 0);
    $avg = $row['avg_rating'] !== null ? floatval($row['avg_rating']) : null;
    if ($count === 0) {
        return ['rating' => $fallbackRating, 'count' => $fallbackCount];
    }
    $totalCount = $fallbackCount + $count;
    $weighted = ($fallbackRating * $fallbackCount + ($avg ?? $fallbackRating) * $count) / max(1, $totalCount);
    return [
        'rating' => round($weighted, 2),
        'count' => $totalCount,
    ];
}

function chat_thread_register(string $threadKey, array $meta): void {
    $payload = [
        'thread_key' => $threadKey,
        'booking_id' => $meta['booking_id'] ?? null,
        'listing' => $meta['listing'] ?? null,
        'listing_id' => $meta['listing_id'] ?? null,
        'listing_type' => $meta['type'] ?? null,
        'seller' => $meta['seller'] ?? null,
        'buyer_id' => $meta['buyer_id'] ?? null,
        'buyer_name' => $meta['buyer_name'] ?? null,
    ];
    if (!storage_sqlite_available()) {
        if (!isset($_SESSION['chat_threads'])) $_SESSION['chat_threads'] = [];
        $_SESSION['chat_threads'][$threadKey] = $payload;
        if (!isset($_SESSION['messages'][$threadKey])) $_SESSION['messages'][$threadKey] = [];
        return;
    }
    $db = sqlite_conn();
    $stmt = $db->prepare("
        INSERT INTO chat_threads (thread_key, booking_id, listing, listing_id, listing_type, seller, buyer_id, buyer_name)
        VALUES (:thread, :booking, :listing, :listing_id, :listing_type, :seller, :buyer_id, :buyer_name)
        ON CONFLICT(thread_key) DO UPDATE SET
            booking_id = excluded.booking_id,
            listing = excluded.listing,
            listing_id = excluded.listing_id,
            listing_type = excluded.listing_type,
            seller = excluded.seller,
            buyer_id = excluded.buyer_id,
            buyer_name = excluded.buyer_name
    ");
    $stmt->execute([
        ':thread' => $payload['thread_key'],
        ':booking' => $payload['booking_id'],
        ':listing' => $payload['listing'],
        ':listing_id' => $payload['listing_id'],
        ':listing_type' => $payload['listing_type'],
        ':seller' => $payload['seller'],
        ':buyer_id' => $payload['buyer_id'],
        ':buyer_name' => $payload['buyer_name'],
    ]);
}
function chat_thread_meta(string $threadKey): ?array {
    if (!storage_sqlite_available()) {
        return $_SESSION['chat_threads'][$threadKey] ?? null;
    }
    $db = sqlite_conn();
    $stmt = $db->prepare("SELECT * FROM chat_threads WHERE thread_key = :thread LIMIT 1");
    $stmt->execute([':thread' => $threadKey]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
function chat_messages(string $threadKey): array {
    if (!storage_sqlite_available()) {
        return $_SESSION['messages'][$threadKey] ?? [];
    }
    $db = sqlite_conn();
    $stmt = $db->prepare("SELECT sender as `from`, role, body, created_at as timestamp FROM chat_messages WHERE thread_key = :thread ORDER BY id ASC");
    $stmt->execute([':thread' => $threadKey]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function chat_add_message(string $threadKey, string $from, string $role, string $body): void {
    if (trim($body) === '') return;
    if (!storage_sqlite_available()) {
        if (!isset($_SESSION['messages'][$threadKey])) {
            $_SESSION['messages'][$threadKey] = [];
        }
        $_SESSION['messages'][$threadKey][] = [
            'from' => $from,
            'role' => $role,
            'body' => $body,
            'timestamp' => date('Y-m-d H:i'),
            'read_by_seller' => $role === 'seller',
            'read_by_buyer' => $role === 'buyer',
        ];
        return;
    }
    $db = sqlite_conn();
    $stmt = $db->prepare("
        INSERT INTO chat_messages (thread_key, sender, role, body, read_by_seller, read_by_buyer)
        VALUES (:thread, :sender, :role, :body, :seller_read, :buyer_read)
    ");
    $stmt->execute([
        ':thread' => $threadKey,
        ':sender' => $from,
        ':role' => $role,
        ':body' => $body,
        ':seller_read' => $role === 'seller' ? 1 : 0,
        ':buyer_read' => $role === 'buyer' ? 1 : 0,
    ]);
}
function chat_threads_for_user(string $username, int $userId): array {
    if (!storage_sqlite_available()) {
        $threads = $_SESSION['chat_threads'] ?? [];
        return array_values(array_filter($threads, function($meta) use ($username, $userId) {
            return (($meta['seller'] ?? '') === $username) || (intval($meta['buyer_id'] ?? 0) === $userId);
        }));
    }
    $db = sqlite_conn();
    $stmt = $db->prepare("
        SELECT * FROM chat_threads
        WHERE seller = :seller OR buyer_id = :buyer
        ORDER BY datetime(created_at) DESC
    ");
    $stmt->execute([':seller' => $username, ':buyer' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function chat_threads_all(): array {
    if (!storage_sqlite_available()) {
        return array_values($_SESSION['chat_threads'] ?? []);
    }
    $db = sqlite_conn();
    $stmt = $db->query("SELECT * FROM chat_threads ORDER BY datetime(created_at) DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function chat_mark_thread_read(string $threadKey, string $role): void {
    if (!storage_sqlite_available()) {
        if (empty($_SESSION['messages'][$threadKey])) return;
        foreach ($_SESSION['messages'][$threadKey] as &$msg) {
            if ($role === 'seller') {
                $msg['read_by_seller'] = 1;
            } elseif ($role === 'buyer') {
                $msg['read_by_buyer'] = 1;
            }
        }
        unset($msg);
        return;
    }
    $db = sqlite_conn();
    if ($role === 'seller') {
        $stmt = $db->prepare("UPDATE chat_messages SET read_by_seller = 1 WHERE thread_key = :thread AND read_by_seller = 0");
        $stmt->execute([':thread' => $threadKey]);
    } elseif ($role === 'buyer') {
        $stmt = $db->prepare("UPDATE chat_messages SET read_by_buyer = 1 WHERE thread_key = :thread AND read_by_buyer = 0");
        $stmt->execute([':thread' => $threadKey]);
    }
}
function unread_chat_count(): int {
    $username = current_username();
    $userId = current_user_id();
    if (!$username || !$userId) {
        return 0;
    }
    if (!storage_sqlite_available()) {
        $count = 0;
        foreach ($_SESSION['chat_threads'] ?? [] as $key => $meta) {
            $messages = $_SESSION['messages'][$key] ?? [];
            foreach ($messages as $msg) {
                if (($meta['seller'] ?? '') === $username && empty($msg['read_by_seller'])) {
                    $count++;
                }
                if (intval($meta['buyer_id'] ?? 0) === $userId && empty($msg['read_by_buyer'])) {
                    $count++;
                }
            }
        }
        return $count;
    }
    $db = sqlite_conn();
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM chat_messages m
        JOIN chat_threads t ON t.thread_key = m.thread_key
        WHERE (t.seller = :seller AND m.read_by_seller = 0)
           OR (t.buyer_id = :buyer AND m.read_by_buyer = 0)
    ");
    $stmt->execute([':seller' => $username, ':buyer' => $userId]);
    return intval($stmt->fetchColumn() ?: 0);
}
function pending_review_count(): int {
    $userId = current_user_id();
    $username = current_username();
    if (!$userId || !$username) {
        return 0;
    }
    $count = 0;
    foreach (bookings_for_user($userId) as $booking) {
        if (($booking['status'] ?? '') !== 'Completed') {
            continue;
        }
        $type = $booking['type'] ?? '';
        $listingId = intval($booking['listing_id'] ?? 0);
        if ($type === '' || $listingId <= 0) {
            continue;
        }
        if (!reviews_has_by($type, $listingId, $username)) {
            $count++;
        }
    }
    return $count;
}
