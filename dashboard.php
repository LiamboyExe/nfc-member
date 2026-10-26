<?php
// dashboard.php — Summary statistics
// ============================================================
require_once __DIR__ . '/config.php';
requireLogin();
require_once __DIR__ . '/includes/layout.php';

$pdo      = getDB();
$role     = $_SESSION['role'];
$branchId = sessionBranchId();

// Build WHERE clause based on role
$branchWhere = ($role === 'local' && $branchId) ? 'WHERE m.branch_id = :bid' : '';
$cardWhere   = ($role === 'local' && $branchId) ? 'WHERE members.branch_id = :bid' : '';

// Total members visible
$sql = "SELECT COUNT(*) FROM members m $branchWhere";
$stmt = $pdo->prepare($sql);
if ($role === 'local' && $branchId) $stmt->bindValue(':bid', $branchId);
$stmt->execute();
$totalMembers = $stmt->fetchColumn();

// Active members
$sql2 = "SELECT COUNT(*) FROM members m $branchWhere" . ($branchWhere ? ' AND' : ' WHERE') . " m.status = 'Active'";
$stmt2 = $pdo->prepare($sql2);
if ($role === 'local' && $branchId) $stmt2->bindValue(':bid', $branchId);
$stmt2->execute();
$activeMembers = $stmt2->fetchColumn();

// Total branches visible
$totalBranches = ($role === 'main') ? $pdo->query('SELECT COUNT(*) FROM branches')->fetchColumn() : 1;

// NFC cards
$sql3 = "SELECT COUNT(*) FROM nfc_cards nc JOIN members ON members.id = nc.member_id $cardWhere";
$stmt3 = $pdo->prepare($sql3);
if ($role === 'local' && $branchId) $stmt3->bindValue(':bid', $branchId);
$stmt3->execute();
$totalNFC = $stmt3->fetchColumn();

// Active NFC cards
$sql4 = "SELECT COUNT(*) FROM nfc_cards nc JOIN members ON members.id = nc.member_id $cardWhere" . ($cardWhere ? ' AND' : ' WHERE') . " nc.card_status = 'Active'";
$stmt4 = $pdo->prepare($sql4);
if ($role === 'local' && $branchId) $stmt4->bindValue(':bid', $branchId);
$stmt4->execute();
$activeNFC = $stmt4->fetchColumn();

// Recent members (last 5)
$recentSql = "SELECT m.*, b.name AS branch_name FROM members m
              JOIN branches b ON b.id = m.branch_id
              $branchWhere
              ORDER BY m.created_at DESC LIMIT 5";
$recentStmt = $pdo->prepare($recentSql);
if ($role === 'local' && $branchId) $recentStmt->bindValue(':bid', $branchId);
$recentStmt->execute();
$recentMembers = $recentStmt->fetchAll();

// Branch breakdown (main admin only)
$branchStats = [];
if ($role === 'main') {
    $bs = $pdo->query("SELECT b.name, COUNT(m.id) as cnt,
                        SUM(m.status='Active') as active
                        FROM branches b
                        LEFT JOIN members m ON m.branch_id = b.id
                        GROUP BY b.id");
    $branchStats = $bs->fetchAll();
}

$flash_s = getFlash('success');
$flash_e = getFlash('error');

layoutHead('Dashboard');
layoutNav('dashboard');
?>

<?php if ($flash_s): ?>
  <div class="alert alert-success" data-auto-dismiss>✅ <?= htmlspecialchars($flash_s) ?></div>
<?php endif; ?>
<?php if ($flash_e): ?>
  <div class="alert alert-error" data-auto-dismiss>⚠️ <?= htmlspecialchars($flash_e) ?></div>
<?php endif; ?>

<?php if ($role === 'local'): ?>
<div class="demo-banner">
  🔒 <strong>Demo Role Simulation:</strong> You are logged in as a <strong>Local Admin</strong>.
  This is NOT real authentication — it only filters the view for demonstration purposes.
</div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card fade-up">
    <div class="stat-icon purple">👥</div>
    <div class="stat-info">
      <div class="label">Total Members</div>
      <div class="value text-primary"><?= $totalMembers ?></div>
    </div>
  </div>
  <div class="stat-card fade-up">
    <div class="stat-icon teal">✅</div>
    <div class="stat-info">
      <div class="label">Active Members</div>
      <div class="value text-accent"><?= $activeMembers ?></div>
    </div>
  </div>
  <div class="stat-card fade-up">
    <div class="stat-icon orange">🏢</div>
    <div class="stat-info">
      <div class="label">Branches</div>
      <div class="value" style="color:var(--warning)"><?= $totalBranches ?></div>
    </div>
  </div>
  <div class="stat-card fade-up">
    <div class="stat-icon blue">💳</div>
    <div class="stat-info">
      <div class="label">NFC Cards</div>
      <div class="value text-primary"><?= $totalNFC ?></div>
    </div>
  </div>
  <div class="stat-card fade-up">
    <div class="stat-icon teal">📶</div>
    <div class="stat-info">
      <div class="label">Active Cards</div>
      <div class="value text-accent"><?= $activeNFC ?></div>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:<?= $role==='main' ? '1fr 340px' : '1fr' ?>;gap:1.5rem;flex-wrap:wrap">

  <!-- Recent Members -->
  <div class="card fade-up">
    <div class="section-head">
      <h2>🕐 Recent Members</h2>
      <a href="/membership-demo/members.php" class="btn btn-ghost btn-sm">View All →</a>
    </div>
    <div class="table-wrap">
      <table id="recent-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Branch</th>
            <th>Type</th>
            <th>Status</th>
            <th>Joined</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentMembers as $m): ?>
          <tr>
            <td><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></td>
            <td style="color:var(--text-muted)"><?= htmlspecialchars($m['branch_name']) ?></td>
            <td><span class="badge badge-<?= strtolower($m['membership_type']) ?>"><?= $m['membership_type'] ?></span></td>
            <td>
              <span class="badge badge-<?= strtolower($m['status']) ?>">
                <?= $m['status'] ?>
              </span>
            </td>
            <td style="color:var(--text-muted)"><?= $m['join_date'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Branch Breakdown (main admin) -->
  <?php if ($role === 'main'): ?>
  <div class="card fade-up">
    <div class="section-head">
      <h2>🏢 Branch Breakdown</h2>
    </div>
    <?php foreach ($branchStats as $b): ?>
    <div style="margin-bottom:1.25rem">
      <div style="display:flex;justify-content:space-between;margin-bottom:.4rem">
        <span style="font-weight:600"><?= htmlspecialchars($b['name']) ?></span>
        <span style="color:var(--text-muted);font-size:.85rem"><?= $b['cnt'] ?> members</span>
      </div>
      <div style="background:var(--bg3);border-radius:20px;height:8px;overflow:hidden">
        <?php $pct = $totalMembers > 0 ? round(($b['cnt']/$totalMembers)*100) : 0; ?>
        <div style="background:linear-gradient(90deg,var(--primary),var(--accent));width:<?= $pct ?>%;height:100%;border-radius:20px;transition:width .6s ease"></div>
      </div>
      <div style="font-size:.75rem;color:var(--text-muted);margin-top:.3rem">
        <?= $b['active'] ?> active · <?= $pct ?>% of total
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<?php layoutClose(); ?>
