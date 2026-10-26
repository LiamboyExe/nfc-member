<?php
// profile.php — Public Member Profile Page
// Accessed via: /membership-demo/profile.php?slug=alice-reyes-b1m1
// ============================================================
require_once __DIR__ . '/config.php';

$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    http_response_code(400);
    die('Missing profile slug.');
}

$pdo  = getDB();
$stmt = $pdo->prepare(
    'SELECT m.*, b.name AS branch_name, b.location AS branch_location
     FROM members m
     JOIN branches b ON b.id = m.branch_id
     WHERE m.profile_slug = ? AND m.approved = 1
     LIMIT 1'
);
$stmt->execute([$slug]);
$member = $stmt->fetch();

if (!$member) {
    http_response_code(404);
    $initials = '?';
    $name     = 'Profile Not Found';
} else {
    $initials = strtoupper(substr($member['first_name'],0,1) . substr($member['last_name'],0,1));
    $name     = htmlspecialchars($member['first_name'] . ' ' . $member['last_name']);
}

// Also fetch NFC card info (if any)
$card = null;
if ($member) {
    $cs = $pdo->prepare('SELECT * FROM nfc_cards WHERE member_id = ? LIMIT 1');
    $cs->execute([$member['id']]);
    $card = $cs->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $member ? $name . ' — Member Profile' : '404 — Profile Not Found' ?></title>
  <meta name="description" content="Public membership profile page for <?= $member ? $name : 'unknown member' ?>.">
  <meta name="robots" content="noindex"><!-- demo: no search indexing -->
  <link rel="stylesheet" href="/membership-demo/assets/css/style.css">
</head>
<body>
<div class="profile-page">
  <div style="width:100%;max-width:520px">

    <?php if (!$member): ?>
    <!-- Not found -->
    <div class="profile-card">
      <div style="font-size:3rem;margin-bottom:1rem">❓</div>
      <h1 style="font-size:1.4rem;margin-bottom:.5rem">Profile Not Found</h1>
      <p style="color:var(--text-muted);font-size:.9rem">
        This profile does not exist, has not been approved, or the link may be incorrect.
      </p>
      <a href="/membership-demo/" class="btn btn-ghost mt-2">← Back to Home</a>
    </div>

    <?php else: ?>
    <!-- Member profile -->
    <div class="profile-card fade-up">
      <!-- Org badge -->
      <div class="profile-org-badge">
        🏛️ Temple Street Demo System
      </div>

      <!-- Avatar -->
      <div class="profile-avatar"><?= $initials ?></div>

      <!-- Name & type -->
      <h1 class="profile-name"><?= $name ?></h1>
      <div style="margin-bottom:.75rem">
        <span class="badge badge-<?= strtolower($member['membership_type']) ?>"><?= $member['membership_type'] ?> Member</span>
        &nbsp;
        <span class="badge badge-<?= strtolower($member['status']) ?>"><?= $member['status'] ?></span>
      </div>

      <div class="profile-detail-row">
        🏢 <?= htmlspecialchars($member['branch_name']) ?> &nbsp;·&nbsp; 📍 <?= htmlspecialchars($member['branch_location']) ?>
      </div>

      <hr class="profile-divider">

      <!-- Info grid -->
      <div class="profile-info-grid">
        <div class="profile-info-item">
          <div class="pi-label">📅 Member Since</div>
          <div class="pi-value"><?= date('d F Y', strtotime($member['join_date'])) ?></div>
        </div>
        <div class="profile-info-item">
          <div class="pi-label">📧 Email</div>
          <div class="pi-value" style="word-break:break-all"><?= htmlspecialchars($member['email']) ?></div>
        </div>
        <div class="profile-info-item">
          <div class="pi-label">📞 Phone</div>
          <div class="pi-value"><?= htmlspecialchars($member['phone']) ?></div>
        </div>
        <div class="profile-info-item">
          <div class="pi-label">🏠 Address</div>
          <div class="pi-value"><?= htmlspecialchars($member['address'] ?? 'N/A') ?></div>
        </div>
      </div>

      <?php if ($card): ?>
      <hr class="profile-divider">
      <div style="background:var(--bg3);border-radius:var(--radius-sm);padding:1rem;text-align:left">
        <div style="font-size:.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:.5rem">💳 NFC Card</div>
        <div style="font-family:monospace;font-size:.85rem;color:var(--text-muted)"><?= htmlspecialchars($card['card_uid']) ?></div>
        <div style="margin-top:.3rem">
          <span class="badge badge-<?= strtolower($card['card_status']) ?>"><?= $card['card_status'] ?></span>
        </div>
      </div>
      <?php endif; ?>

      <hr class="profile-divider">
      <p style="font-size:.72rem;color:var(--text-dim);line-height:1.6">
        ⚠️ This is a <strong>demo profile</strong>. All data is fictional. This system is for local XAMPP demonstration purposes only.
      </p>
    </div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:1.25rem">
      <a href="/membership-demo/" style="color:var(--text-muted);font-size:.8rem">🏛️ Temple Street Demo System</a>
    </div>
  </div>
</div>
<script src="/membership-demo/assets/js/app.js"></script>
</body>
</html>

