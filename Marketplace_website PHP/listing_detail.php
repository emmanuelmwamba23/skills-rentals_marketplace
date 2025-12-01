<?php
require 'config.php';
require_once 'catalog.php';

$typeMap = [
    'housing' => 'housing',
    'home' => 'housing',
    'homes' => 'housing',
    'stay' => 'housing',
    'car' => 'car',
    'cars' => 'car',
    'vehicle' => 'car',
    'freelance' => 'freelance',
    'gig' => 'freelance',
    'service' => 'freelance',
];

$rawType = strtolower(trim($_GET['type'] ?? 'housing'));
$type = $typeMap[$rawType] ?? null;
$id = intval($_GET['id'] ?? 0);

if (!$type || $id <= 0) {
    http_response_code(404);
    echo "Listing not found.";
    exit;
}

$listing = catalog_find($type, $id);
if (!$listing) {
    http_response_code(404);
    echo "Listing not found.";
    exit;
}

$pageTitle = $listing['title'] . ' | Vestral Zambia';
$calendar = $listing['calendar'] ?? catalog_generate_calendar();
$currency = $listing['currency'] ?? 'ZMW';
$priceUnitLabels = [
    'housing' => 'per night',
    'car' => 'per day',
    'freelance' => 'per project',
];
$unitLabel = $priceUnitLabels[$type] ?? 'per booking';
$isLoggedIn = (bool) current_user_id();
$today = (new DateTimeImmutable('today', new DateTimeZone('Africa/Lusaka')))->format('Y-m-d');
$phonePrefill = htmlspecialchars($_SESSION['phone'] ?? '', ENT_QUOTES, 'UTF-8');
$guestsMax = intval($listing['max_guests'] ?? 12);
$notifChats = unread_chat_count();
$notifReviews = pending_review_count();
$ratingBase = $listing['rating'] ?? 4.8;
$ratingCountBase = intval($listing['reviews'] ?? 0);
$reviewSummary = reviews_summary($type, $listing['id'], $ratingBase, $ratingCountBase);
$reviewsList = reviews_for($type, $listing['id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="/CSS/index.css">
  <style>
    .detail-hero {
      position: relative;
      min-height: 320px;
      display: flex;
      align-items: flex-end;
      padding: 2rem;
      border-radius: 0 0 24px 24px;
      overflow: hidden;
      background: #111;
      color: #fff;
    }
    .detail-hero::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(0,0,0,.05), rgba(0,0,0,.8));
    }
    .detail-hero img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: 0;
    }
    .detail-hero-content {
      position: relative;
      z-index: 1;
      max-width: 780px;
    }
    .detail-container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1.5rem 2rem;
    }
    .detail-grid {
      display: grid;
      grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
      gap: 1.5rem;
    }
    .detail-card {
      background: #fff;
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 10px 30px rgba(0,0,0,.05);
      margin-bottom: 1.5rem;
    }
    .detail-card h3 {
      margin-bottom: .75rem;
      color: #0b5ed7;
    }
    .amenities, .tag-list {
      display: flex;
      flex-wrap: wrap;
      gap: .5rem;
    }
    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: .75rem;
    }
    .calendar-pill {
      border-radius: 12px;
      padding: .8rem;
      border: 1px solid #eef0ff;
      background: #f8f9ff;
    }
    .calendar-pill strong {
      display: block;
      font-size: .95rem;
    }
    .calendar-open { border-color:#bfe6c5; background:#f3fdf5; }
    .calendar-booked { border-color:#ffe0a6; background:#fff9ec; }
    .calendar-blocked { border-color:#ffc9c9; background:#fff2f2; }
    .booking-panel {
      background: #ffffff;
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 15px 35px rgba(0,0,0,.08);
      position: sticky;
      top: 90px;
      height: fit-content;
    }
    .booking-panel h4 {
      margin-bottom: 1rem;
    }
    .booking-panel label {
      font-size: .9rem;
      font-weight: 600;
      display: block;
      margin-top: 1rem;
      margin-bottom: .2rem;
    }
    .booking-panel input,
    .booking-panel select,
    .booking-panel textarea {
      width: 100%;
      padding: .65rem .75rem;
      border: 1px solid #dfe2ec;
      border-radius: 10px;
      font-size: .95rem;
    }
    .booking-panel textarea {
      min-height: 90px;
      resize: vertical;
    }
    .checkout-total {
      margin-top: 1rem;
      background: #f4f9ff;
      padding: .75rem 1rem;
      border-radius: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-weight: 600;
    }
    .receipt-card {
      margin-top: 1.2rem;
      border-radius: 12px;
      border: 1px dashed #a6d5ff;
      background: #f5fbff;
      padding: 1rem;
    }
    .alert-inline {
      margin-top: 1rem;
      padding: .7rem .9rem;
      border-radius: 10px;
      font-size: .9rem;
    }
    .alert-inline.error { background:#fff2f0; color:#b42318; border:1px solid #ffcdc2; }
    .alert-inline.success { background:#f1fff3; color:#1d7a32; border:1px solid #c8f0d2; }
    .alert-inline.info { background:#eef5ff; color:#0b5ed7; border:1px solid #bcd8ff; }
    @media (max-width: 992px) {
      .detail-grid {
        grid-template-columns: 1fr;
      }
      .booking-panel {
        position: static;
      }
    }
  </style>
</head>
<body>
  <header class="navbar">
    <div class="navbar-left">
      <div class="logo"><span>Vestral</span></div>
    </div>
    <nav class="navbar-center">
      <a href="index.php">Home</a>
      <a href="housing.php">Housing</a>
      <a href="cars.php">Cars</a>
      <a href="freelance.php">Freelance</a>
      <a href="about.php">About Us</a>
      <a href="contact.php">Contact Us</a>
    </nav>
    <div class="navbar-right">
      <?php if ($isLoggedIn): ?>
        <?php $initial = strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
        <div class="profile" id="profileBtn">
          <div class="profile-initial"><?= $initial ?></div>
          <?php if (($notifChats + $notifReviews) > 0): ?><span class="profile-badge"><?= $notifChats + $notifReviews ?></span><?php endif; ?>
        </div>
        <div class="dropdown" id="profileMenu">
          <a href="buyer_dashboard.php">Buyer Hub<?php if($notifReviews>0): ?><span class="nav-pill"><?= $notifReviews ?></span><?php endif; ?></a>
          <a href="seller_dashboard.php">Seller Hub<?php if($notifChats>0): ?><span class="nav-pill"><?= $notifChats ?></span><?php endif; ?></a>
          <a href="my_account.php">My Account</a>
          <a href="add_product.php">Add Listing</a>
          <a href="admin.php">Admin</a>
          <hr>
          <a href="logout.php" style="color: var(--color-danger);">Logout</a>
        </div>
      <?php else: ?>
        <a href="login.php" class="btn-login">Login</a>
        <a href="register.php" class="btn-login">Register</a>
      <?php endif; ?>
    </div>
  </header>

  <section class="detail-hero">
    <img src="<?= htmlspecialchars($listing['image'] ?? ($listing['gallery'][0] ?? 'https://via.placeholder.com/1200x600')) ?>" alt="<?= htmlspecialchars($listing['title']) ?>">
    <div class="detail-hero-content">
      <p style="opacity:.85;letter-spacing:.08em;text-transform:uppercase;">Zambia • <?= htmlspecialchars($listing['province'] ?? $listing['category'] ?? 'Marketplace') ?></p>
      <h1 style="font-size:2.8rem;margin:.3rem 0 1rem;"><?= htmlspecialchars($listing['title']) ?></h1>
      <div style="display:flex;gap:1rem;flex-wrap:wrap;">
        <span class="badge">★ <?= number_format($reviewSummary['rating'] ?? $ratingBase, 2) ?> · <?= intval($reviewSummary['count'] ?? $ratingCountBase) ?> reviews</span>
        <span class="badge"><?= strtoupper($currency) ?> <?= number_format($listing['price'], 2) ?> <?= $unitLabel ?></span>
        <span class="badge">Mobile Money • MTN | Airtel | Zamtel</span>
      </div>
    </div>
  </section>

  <div class="detail-container">
    <div class="detail-grid">
      <div>
        <div class="detail-card">
          <h3>About this <?= $type === 'freelance' ? 'service' : ($type === 'car' ? 'vehicle' : 'stay') ?></h3>
          <p style="margin-bottom:1rem;"><?= nl2br(htmlspecialchars($listing['description'] ?? 'No description provided.')) ?></p>
          <div style="display:flex;flex-wrap:wrap;gap:1.5rem;margin-bottom:1rem;">
            <div>
              <strong>Seller</strong>
              <p><?= htmlspecialchars($listing['seller'] ?? ($listing['username'] ?? 'Marketplace Partner')) ?></p>
            </div>
            <?php if (!empty($listing['address'])): ?>
            <div>
              <strong>Location</strong>
              <p><?= htmlspecialchars($listing['address']) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($listing['contact_phone'])): ?>
            <div>
              <strong>Contact</strong>
              <p><?= htmlspecialchars($listing['contact_phone']) ?></p>
            </div>
            <?php endif; ?>
          </div>
          <?php if (!empty($listing['amenities']) || !empty($listing['tags'])): ?>
          <div style="margin-top:1rem;">
            <?php if (!empty($listing['amenities'])): ?>
              <h4 style="margin-bottom:.5rem;">Amenities & Features</h4>
              <div class="amenities">
                <?php foreach ($listing['amenities'] as $amenity): ?>
                  <span class="badge"><?= htmlspecialchars($amenity) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($listing['tags'])): ?>
              <h4 style="margin:1rem 0 .4rem;">Marketplace Tags</h4>
              <div class="tag-list">
                <?php foreach ($listing['tags'] as $tag): ?>
                  <span class="badge" style="background:#f2fff4;color:#1c7c3a;"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>

        <div class="detail-card">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
            <h3>Availability</h3>
            <span class="badge">Africa/Lusaka</span>
          </div>
          <p style="margin-bottom:1rem;color:#555;">Live calendar simulation for the next 3 weeks. Markings auto-refresh on each page load.</p>
          <div class="calendar-grid">
            <?php foreach ($calendar as $slot): ?>
              <?php $status = $slot['status']; ?>
              <div class="calendar-pill calendar-<?= htmlspecialchars($status) ?>">
                <strong><?= htmlspecialchars($slot['label']) ?></strong>
                <small style="display:block;margin-top:.2rem;text-transform:capitalize;"><?= htmlspecialchars($status) ?></small>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="detail-card">
          <h3>What’s included</h3>
          <ul style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.8rem;">
            <li>✔ ZRA-compliant receipt instantly after payment.</li>
            <li>✔ Escrow until seller marks work complete.</li>
            <li>✔ Mobile Money via MTN, Airtel, Zamtel.</li>
            <li>✔ Activity feed + ledger updates in your dashboard.</li>
            <li>✔ Optional promo codes managed by admins.</li>
          </ul>
        </div>

        <div class="detail-card">
          <h3>Community Reviews</h3>
          <?php if (empty($reviewsList)): ?>
            <p>No reviews yet. Be the first to book and share feedback via the buyer dashboard.</p>
          <?php else: ?>
            <?php foreach (array_slice(array_reverse($reviewsList), 0, 4) as $review): ?>
              <div style="border-bottom:1px solid #f1f1f1;padding:.7rem 0;">
                <strong><?= htmlspecialchars($review['author'] ?? 'Guest') ?></strong>
                <span style="color:#f5a623;margin-left:.3rem;">★ <?= intval($review['rating'] ?? 5) ?></span>
                <p style="margin:.3rem 0;color:#555;"><?= nl2br(htmlspecialchars($review['comment'] ?? '')) ?></p>
                <small style="color:#999;"><?= htmlspecialchars($review['timestamp'] ?? '') ?></small>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <aside class="booking-panel">
        <div>
          <div style="display:flex;align-items:flex-end;gap:.4rem;">
            <span style="font-size:2rem;font-weight:700;"><?= htmlspecialchars($currency) ?> <?= number_format($listing['price'], 2) ?></span>
            <span style="color:#666;"><?= $unitLabel ?></span>
          </div>
          <p style="color:#0b5ed7;font-weight:600;margin:.4rem 0;">Escrow-enabled · Mobile Money Ready</p>
        </div>

        <?php if (!$isLoggedIn): ?>
          <p>You need an account to place bookings. Sign in to simulate payments and receive receipts.</p>
          <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <a class="btn" href="login.php">Login</a>
            <a class="btn btn-outline" href="register.php">Register</a>
          </div>
        <?php else: ?>
          <form id="checkoutForm"
                data-type="<?= htmlspecialchars($type) ?>"
                data-price="<?= htmlspecialchars($listing['price']) ?>"
                data-currency="<?= htmlspecialchars($currency) ?>"
                data-unit="<?= htmlspecialchars($listing['price_unit'] ?? $unitLabel) ?>">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
            <input type="hidden" name="listing_id" value="<?= intval($listing['id']) ?>">

            <?php if ($type === 'freelance'): ?>
              <label>Delivery deadline</label>
              <input type="date" name="deliver_by" min="<?= $today ?>">
              <label>Project scope</label>
              <textarea name="notes" placeholder="Share quick context for the freelancer"></textarea>
            <?php else: ?>
              <label>Check-in date</label>
              <input type="date" name="start_date" value="<?= $today ?>" min="<?= $today ?>" required>
              <label><?= $type === 'car' ? 'Return date' : 'Check-out date' ?></label>
              <input type="date" name="end_date" min="<?= $today ?>">
              <?php if ($type === 'housing'): ?>
                <label>Guests</label>
                <input type="number" name="guests" min="1" max="<?= max(1, $guestsMax) ?>" value="1">
              <?php endif; ?>
              <label>Notes for host</label>
              <textarea name="notes" placeholder="Arrival time, delivery route, or special requests"></textarea>
            <?php endif; ?>

            <label>Mobile Money provider</label>
            <select name="provider" required>
              <option value="">Choose provider</option>
              <option value="MTN MoMo">MTN MoMo</option>
              <option value="Airtel Money">Airtel Money</option>
              <option value="Zamtel Kwacha">Zamtel Kwacha</option>
            </select>

            <label>Mobile number</label>
            <input type="tel" name="phone" placeholder="e.g. 097XXXXXXX" value="<?= $phonePrefill ?>" required>

            <label>Promo code (optional)</label>
            <input type="text" name="promo" placeholder="Enter code">

            <?php if ($type !== 'freelance'): ?>
              <p style="margin-top:.4rem;color:#777;font-size:.9rem;">Pricing is <?= $unitLabel ?>. We auto-calc total nights/days.</p>
            <?php endif; ?>

            <div class="checkout-total">
              <span>Estimated total</span>
              <strong id="amountTotal"><?= htmlspecialchars($currency) ?> <?= number_format($listing['price'], 2) ?></strong>
            </div>

            <button type="submit" class="btn btn-success" style="width:100%;margin-top:1rem;">Simulate Mobile Money Payment</button>

            <div id="checkoutAlert" class="alert-inline" style="display:none;"></div>
            <div id="receiptCard" class="receipt-card" style="display:none;"></div>
          </form>
        <?php endif; ?>
      </aside>
    </div>
  </div>

  <footer class="footer">&copy; <?= date('Y') ?> Vestral. All rights reserved.</footer>

  <script>
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');
    if (profileBtn && profileMenu) {
      profileBtn.addEventListener('click', e => {
        e.stopPropagation();
        profileMenu.classList.toggle('show');
      });
      document.addEventListener('click', e => {
        if (!profileMenu.contains(e.target) && !profileBtn.contains(e.target)) {
          profileMenu.classList.remove('show');
        }
      });
    }

    const form = document.getElementById('checkoutForm');
    if (form) {
      const amountLabel = document.getElementById('amountTotal');
      const receiptCard = document.getElementById('receiptCard');
      const alertBox = document.getElementById('checkoutAlert');
      const basePrice = parseFloat(form.dataset.price || '0');
      const type = form.dataset.type;
      const currency = form.dataset.currency || 'ZMW';
      const startInput = form.querySelector('input[name="start_date"]');
      const endInput = form.querySelector('input[name="end_date"]');
      const deliveryInput = form.querySelector('input[name="deliver_by"]');

      const today = new Date().toISOString().split('T')[0];
      if (startInput && !startInput.value) startInput.value = today;
      if (startInput) startInput.min = today;
      if (endInput) endInput.min = today;
      if (deliveryInput) deliveryInput.min = today;

      const computeUnits = () => {
        if (type === 'freelance') {
          return 1;
        }
        const start = startInput?.value;
        const end = endInput?.value;
        if (!start) return 1;
        const startDate = new Date(start);
        let endDate = end ? new Date(end) : new Date(start);
        if (endDate <= startDate) {
          endDate = new Date(startDate);
          endDate.setDate(startDate.getDate() + 1);
        }
        const diffMs = endDate - startDate;
        const days = Math.ceil(diffMs / (1000 * 60 * 60 * 24));
        return Math.max(1, days);
      };

      const refreshTotal = () => {
        const units = computeUnits();
        const total = (basePrice * units).toFixed(2);
        amountLabel.textContent = `${currency} ${total}`;
      };

      ['change', 'input'].forEach(evt => {
        form.addEventListener(evt, e => {
          if (e.target === startInput && endInput && endInput.value && endInput.value < startInput.value) {
            endInput.value = startInput.value;
          }
          refreshTotal();
        });
      });
      refreshTotal();

      form.addEventListener('submit', async e => {
        e.preventDefault();
        alertBox.style.display = 'block';
        alertBox.className = 'alert-inline info';
        alertBox.textContent = 'Processing Mobile Money simulation...';
        receiptCard.style.display = 'none';
        receiptCard.innerHTML = '';

        try {
          const res = await fetch('simulate_checkout.php', {
            method: 'POST',
            body: new FormData(form)
          });
          const data = await res.json();
          if (!res.ok || !data.ok) {
            throw new Error(data.error || 'Unable to create booking.');
          }
          alertBox.className = 'alert-inline success';
          alertBox.textContent = data.message || 'Escrow booking created successfully.';
          receiptCard.style.display = 'block';
          receiptCard.innerHTML = `
            <h4 style="margin-bottom:.4rem;">Receipt ${data.receipt.number}</h4>
            <p style="margin:0;">Reference: <strong>${data.reference}</strong></p>
            <p style="margin:0;">Amount: ${currency} ${data.amount}</p>
            <p style="margin:0;">Provider: ${data.receipt.provider}</p>
            <p style="margin:0;">Phone: ${data.receipt.phone}</p>
            <p style="margin:0;">Status: ${data.status}</p>
            <p style="margin-top:.5rem;font-size:.9rem;color:#555;">Timestamp: ${data.receipt.timestamp}</p>
          `;
        } catch (err) {
          alertBox.className = 'alert-inline error';
          alertBox.textContent = err.message;
        }
      });
    }
  </script>
</body>
</html>

