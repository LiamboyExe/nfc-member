<?php
// nfc.php — NFC Card Management
// ============================================================
require_once __DIR__ . '/config.php';
requireLogin();
require_once __DIR__ . '/includes/layout.php';

$pdo      = getDB();
$role     = $_SESSION['role'];
$branchId = sessionBranchId();

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ASSIGN or UPDATE card
    if ($action === 'assign') {
        $mid = (int)$_POST['member_id'];
        $url = trim($_POST['profile_url']);
        $uid = trim($_POST['card_uid']);
        $status = $_POST['card_status'];
        $notes  = trim($_POST['notes'] ?? '');
        $date   = $_POST['issued_date'];

        // Check if card exists for this member
        $exists = $pdo->prepare('SELECT id FROM nfc_cards WHERE member_id = ?');
        $exists->execute([$mid]);
        if ($exists->fetch()) {
            $stmt = $pdo->prepare('UPDATE nfc_cards SET card_uid=?,profile_url=?,card_status=?,issued_date=?,notes=? WHERE member_id=?');
            $stmt->execute([$uid, $url, $status, $date, $notes, $mid]);
            flash('success', 'NFC card updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO nfc_cards (member_id,card_uid,profile_url,card_status,issued_date,notes) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$mid, $uid, $url, $status, $date, $notes]);
            flash('success', 'NFC card assigned.');
        }
        header('Location: /membership-demo/nfc.php');
        exit;
    }

    // DELETE card
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM nfc_cards WHERE id = ?')->execute([(int)$_POST['card_id']]);
        flash('success', 'NFC card removed.');
        header('Location: /membership-demo/nfc.php');
        exit;
    }

    // UPDATE STATUS only
    if ($action === 'status') {
        $pdo->prepare('UPDATE nfc_cards SET card_status=? WHERE id=?')
            ->execute([$_POST['card_status'], (int)$_POST['card_id']]);
        flash('success', 'Card status updated.');
        header('Location: /membership-demo/nfc.php');
        exit;
    }
}

// ---------- Fetch cards ----------
$cardSql = 'SELECT nc.*, m.first_name, m.last_name, m.profile_slug, b.name AS branch_name
            FROM nfc_cards nc
            JOIN members m ON m.id = nc.member_id
            JOIN branches b ON b.id = m.branch_id';
$cardParams = [];
if ($role === 'local' && $branchId) {
    $cardSql .= ' WHERE m.branch_id = ?';
    $cardParams[] = $branchId;
}
$cardSql .= ' ORDER BY nc.issued_date DESC';
$cardStmt = $pdo->prepare($cardSql);
$cardStmt->execute($cardParams);
$cards = $cardStmt->fetchAll();

// ---------- Members without NFC cards (for assign dropdown) ----------
$noCardSql = 'SELECT m.id, m.first_name, m.last_name, m.profile_slug, b.name AS branch_name
              FROM members m
              JOIN branches b ON b.id = m.branch_id
              WHERE m.id NOT IN (SELECT member_id FROM nfc_cards)';
if ($role === 'local' && $branchId) {
    $noCardSql .= ' AND m.branch_id = ' . (int)$branchId;
}
$noCardSql .= ' ORDER BY m.first_name';
$noCards = $pdo->query($noCardSql)->fetchAll();

$flash_s = getFlash('success');
layoutHead('NFC Cards');
layoutNav('nfc');
?>

<?php if ($flash_s): ?>
  <div class="alert alert-success" data-auto-dismiss>✅ <?= htmlspecialchars($flash_s) ?></div>
<?php endif; ?>

<div class="demo-banner">
  📲 <strong>NFC Simulation:</strong> Actual NFC writing is NOT performed. This demo lets you assign profile URLs, copy them, and simulate a write. Use a real NFC app to physically write the URL to a card.
</div>

<div class="section-head">
  <h2>💳 NFC Card Management <span style="color:var(--text-muted);font-weight:400;font-size:.85rem">(<?= count($cards) ?> cards)</span></h2>
  <?php if (!empty($noCards)): ?>
  <button class="btn btn-primary" onclick="openModal('modal-assign')">+ Assign Card</button>
  <?php endif; ?>
</div>

<!-- NFC Cards Grid -->
<?php if (empty($cards)): ?>
  <div class="card" style="text-align:center;padding:3rem;color:var(--text-muted)">
    💳 No NFC cards assigned yet. Click "Assign Card" to get started.
  </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1.25rem">
<?php foreach ($cards as $c): ?>
  <?php
    $statusClass = strtolower($c['card_status']);
    $memberName  = htmlspecialchars($c['first_name'] . ' ' . $c['last_name']);
    $initials    = strtoupper(substr($c['first_name'],0,1) . substr($c['last_name'],0,1));
    $profileUrl  = htmlspecialchars($c['profile_url']);
  ?>
  <div class="card fade-up" style="padding:0;overflow:hidden">
    <!-- Visual Card -->
    <div class="nfc-card-visual">
      <div style="display:flex;align-items:center;gap:.75rem">
        <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;color:#fff;flex-shrink:0">
          <?= $initials ?>
        </div>
        <div>
          <div class="card-name"><?= $memberName ?></div>
          <div style="color:var(--text-muted);font-size:.78rem"><?= htmlspecialchars($c['branch_name']) ?></div>
        </div>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:1rem">
        <div class="card-uid"><?= htmlspecialchars($c['card_uid']) ?></div>
        <div style="display:flex;align-items:center;gap:.5rem">
          <div class="nfc-chip"></div>
          <span class="nfc-wave">📶</span>
        </div>
      </div>
      <!-- Status badge overlay -->
      <div style="position:absolute;top:1rem;right:1rem">
        <span class="badge badge-<?= $statusClass ?>"><?= $c['card_status'] ?></span>
      </div>
    </div>

    <!-- Card details & actions -->
    <div style="padding:1.25rem">
      <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:.75rem;word-break:break-all">
        🔗 <a href="<?= $profileUrl ?>" target="_blank" style="color:var(--accent)"><?= $profileUrl ?></a>
      </div>
      <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:1rem">
        📅 Issued: <?= $c['issued_date'] ?>
        <?php if ($c['notes']): ?> &nbsp;·&nbsp; 📝 <?= htmlspecialchars($c['notes']) ?><?php endif; ?>
      </div>

      <div class="flex gap-1 flex-wrap">
        <!-- Copy URL -->
        <button class="btn btn-accent btn-sm"
          onclick="copyToClipboard('<?= addslashes($c['profile_url']) ?>', this)">
          📋 Copy URL
        </button>
        <!-- Preview Profile -->
        <a href="<?= $profileUrl ?>" target="_blank" class="btn btn-ghost btn-sm">👤 Preview</a>
        <!-- Simulate Write -->
        <button class="btn btn-ghost btn-sm"
          onclick="simulateNFCWriteInline(this, '<?= addslashes($c['profile_url']) ?>')">
          📲 Simulate Write
        </button>
        <!-- Edit/Update card -->
        <button class="btn btn-ghost btn-sm btn-icon" title="Edit card"
          onclick="fillNFCEdit(<?= htmlspecialchars(json_encode($c)) ?>)">✏️</button>
        <!-- Delete -->
        <form method="POST" onsubmit="confirmDelete(this); return false;" style="display:inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="card_id" value="<?= $c['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm btn-icon" title="Remove card">🗑️</button>
        </form>
      </div>

      <!-- NFC sim status inline -->
      <div id="nfc-status-<?= $c['id'] ?>" class="alert alert-info mt-1 hidden" style="font-size:.78rem;padding:.5rem .8rem"></div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<!-- ASSIGN CARD MODAL -->
<div class="modal-overlay" id="modal-assign">
  <div class="modal">
    <div class="modal-header">
      <h3>💳 Assign NFC Card</h3>
      <button class="modal-close" onclick="closeModal('modal-assign')">✕</button>
    </div>
    <form method="POST" id="assign-form">
      <input type="hidden" name="action" value="assign">
      <div class="form-group">
        <label>Member</label>
        <select name="member_id" id="assign-member" required onchange="autoFillUrl(this)">
          <option value="">-- Select member --</option>
          <?php foreach ($noCards as $m): ?>
            <option value="<?= $m['id'] ?>"
              data-slug="<?= htmlspecialchars($m['profile_slug'] ?? '') ?>"
              data-url="<?= htmlspecialchars(BASE_URL . '/profile.php?slug=' . rawurlencode($m['profile_slug'] ?? '')) ?>">
              <?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?> — <?= htmlspecialchars($m['branch_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Profile URL <span style="color:var(--text-muted);font-size:.75rem">(paste or auto-filled)</span></label>
        <input type="text" name="profile_url" id="assign-url" placeholder="http://localhost/membership-demo/profile.php?slug=...">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Card UID <span style="color:var(--text-muted);font-size:.75rem">(simulated)</span></label>
          <input type="text" name="card_uid" id="assign-uid" placeholder="NFC-XXXXXXXX">
        </div>
        <div class="form-group">
          <label>Card Status</label>
          <select name="card_status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Lost">Lost</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Issued Date</label>
          <input type="date" name="issued_date" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label>Notes</label>
          <input type="text" name="notes" placeholder="Optional note">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-assign')">Cancel</button>
        <button type="submit" class="btn btn-primary">Assign Card</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT CARD MODAL -->
<div class="modal-overlay" id="modal-edit-nfc">
  <div class="modal">
    <div class="modal-header">
      <h3>✏️ Edit NFC Card</h3>
      <button class="modal-close" onclick="closeModal('modal-edit-nfc')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="assign">
      <input type="hidden" name="member_id" id="en-member_id">
      <div class="form-group">
        <label>Profile URL</label>
        <input type="text" name="profile_url" id="en-url">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Card UID</label>
          <input type="text" name="card_uid" id="en-uid">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="card_status" id="en-status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Lost">Lost</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Issued Date</label>
          <input type="date" name="issued_date" id="en-date">
        </div>
        <div class="form-group">
          <label>Notes</label>
          <input type="text" name="notes" id="en-notes">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit-nfc')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
// Auto-fill URL when member is selected
function autoFillUrl(sel) {
  const opt = sel.options[sel.selectedIndex];
  const url = opt.dataset.url || '';
  document.getElementById('assign-url').value = url;
  // Generate random NFC UID
  const uid = 'NFC-' + Math.random().toString(16).slice(2,10).toUpperCase();
  document.getElementById('assign-uid').value = uid;
}

// Fill edit NFC modal
function fillNFCEdit(c) {
  document.getElementById('en-member_id').value = c.member_id;
  document.getElementById('en-url').value        = c.profile_url;
  document.getElementById('en-uid').value        = c.card_uid;
  document.getElementById('en-status').value     = c.card_status;
  document.getElementById('en-date').value       = c.issued_date;
  document.getElementById('en-notes').value      = c.notes || '';
  openModal('modal-edit-nfc');
}

// Per-card inline NFC simulation
function simulateNFCWriteInline(btn, url) {
  const card = btn.closest('.card');
  const statusEl = card.querySelector('[id^="nfc-status-"]');
  btn.disabled = true;
  btn.textContent = '📡 Writing...';
  statusEl.className = 'alert alert-info mt-1';
  statusEl.textContent = '📡 Simulating NFC write... hold card near reader.';

  setTimeout(() => {
    statusEl.className = 'alert alert-success mt-1';
    statusEl.textContent = '✅ Simulated! URL written to card: ' + url;
    btn.textContent = '✅ Done';
    setTimeout(() => {
      btn.textContent = '📲 Simulate Write';
      btn.disabled = false;
    }, 3500);
  }, 2200);
}
</script>

<?php layoutClose(); ?>
