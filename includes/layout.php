<?php
// includes/layout.php
// Usage: include this file, call layoutHead($title) and layoutNav($active)
// ============================================================

function layoutHead(string $title = 'Dashboard'): void { ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?> — Temple Street</title>
  <meta name="description" content="Multi-Branch Membership Management System Demo — local XAMPP demo only.">
  <link rel="stylesheet" href="/membership-demo/assets/css/style.css">
</head>
<body>
<div id="sidebar-overlay" class="hidden" style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99;"></div>
<?php }

function layoutNav(string $active = ''): void {
  $role      = $_SESSION['role']      ?? 'local';
  $fullName  = $_SESSION['full_name'] ?? 'Admin';
  $branchId  = $_SESSION['branch_id'] ?? null;
  ?>
<div class="wrapper">
  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="brand">
        <span>🏛️</span>
        <span>TempleStreet</span>
      </div>
      <div style="margin-top:.3rem;font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($fullName) ?></div>
      <span class="role-badge <?= $role ?>">
        <?= $role === 'main' ? '⭐ Main Admin' : '🏢 Local Admin' ?>
      </span>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-label">Main</div>
      <a href="/membership-demo/dashboard.php" class="nav-item <?= $active === 'dashboard' ? 'active' : '' ?>" data-page="dashboard.php">
        <span class="icon">📊</span> Dashboard
      </a>

      <div class="nav-label">Management</div>
      <a href="/membership-demo/members.php" class="nav-item <?= $active === 'members' ? 'active' : '' ?>" data-page="members.php">
        <span class="icon">👥</span> Members
      </a>
      <a href="/membership-demo/branches.php" class="nav-item <?= $active === 'branches' ? 'active' : '' ?>" data-page="branches.php">
        <span class="icon">🏢</span> Branches
      </a>
      <a href="/membership-demo/nfc.php" class="nav-item <?= $active === 'nfc' ? 'active' : '' ?>" data-page="nfc.php">
        <span class="icon">💳</span> NFC Cards
      </a>

      <div class="nav-label">Links</div>
      <a href="/membership-demo/index.php" class="nav-item" data-page="index.php">
        <span class="icon">🏠</span> Homepage
      </a>
    </nav>

    <div class="sidebar-footer">
      <div style="font-size:.75rem">
        <?= $role === 'main' ? '✅ All branches visible' : '🔒 Branch-restricted (demo)' ?>
      </div>
      <a href="/membership-demo/logout.php" class="logout-btn">
        <span>🚪</span> Logout
      </a>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main-content">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:.75rem">
        <button class="hamburger" id="hamburger" aria-label="Toggle menu">☰</button>
        <div class="topbar-title" id="page-title"><?= htmlspecialchars(ucfirst($active)) ?></div>
      </div>
      <div class="topbar-meta">
        <?php if ($role === 'local' && $branchId): ?>
          🏢 Branch-view only &nbsp;|&nbsp;
        <?php endif; ?>
        <?= date('D, d M Y') ?>
      </div>
    </div>
    <div class="page-body">
<?php }

function layoutClose(): void { ?>
    </div><!-- /page-body -->
  </div><!-- /main-content -->
</div><!-- /wrapper -->
<script src="/membership-demo/assets/js/app.js"></script>
</body>
</html>
<?php }

