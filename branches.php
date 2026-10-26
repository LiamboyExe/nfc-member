<?php
// branches.php — Branch Management
// ============================================================
require_once __DIR__ . '/config.php';
requireLogin();
require_once __DIR__ . '/includes/layout.php';

$pdo  = getDB();
$role = $_SESSION['role'];
$bid  = sessionBranchId();

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'main') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $pdo->prepare('INSERT INTO branches (name,location,manager,phone,email) VALUES (?,?,?,?,?)');
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['location']),
            trim($_POST['manager']),
            trim($_POST['phone']),
            trim($_POST['email']),
        ]);
        flash('success', 'Branch added.');
        header('Location: /membership-demo/branches.php');
        exit;
    }

    if ($action === 'edit') {
        $stmt = $pdo->prepare('UPDATE branches SET name=?,location=?,manager=?,phone=?,email=? WHERE id=?');
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['location']),
            trim($_POST['manager']),
            trim($_POST['phone']),
            trim($_POST['email']),
            (int)$_POST['branch_id'],
        ]);
        flash('success', 'Branch updated.');
        header('Location: /membership-demo/branches.php');
        exit;
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM branches WHERE id = ?')->execute([(int)$_POST['branch_id']]);
        flash('success', 'Branch deleted.');
        header('Location: /membership-demo/branches.php');
        exit;
    }
}

// ---------- Fetch branches with member counts ----------
$sql = 'SELECT b.*,
        COUNT(m.id) AS total_members,
        SUM(m.status = \'Active\') AS active_members,
        SUM(m.status = \'Inactive\') AS inactive_members,
        SUM(m.status = \'Pending\') AS pending_members
        FROM branches b
        LEFT JOIN members m ON m.branch_id = b.id';
if ($role === 'local' && $bid) {
    $sql .= ' WHERE b.id = ' . (int)$bid;
}
$sql .= ' GROUP BY b.id ORDER BY b.name';
$branchList = $pdo->query($sql)->fetchAll();

$flash_s = getFlash('success');
layoutHead('Branches');
layoutNav('branches');
?>

<?php if ($flash_s): ?>
  <div class="alert alert-success" data-auto-dismiss>✅ <?= htmlspecialchars($flash_s) ?></div>
<?php endif; ?>

<?php if ($role === 'local'): ?>
<div class="demo-banner">
  🔒 <strong>Demo Role:</strong> As a Local Admin, you can only view your assigned branch. Branch editing requires Main Admin access.
</div>
<?php endif; ?>

<div class="section-head">
  <h2>🏢 Branch Management</h2>
  <?php if ($role === 'main'): ?>
  <button class="btn btn-primary" onclick="openModal('modal-add-branch')">+ Add Branch</button>
  <?php endif; ?>
</div>

<!-- Branch Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.25rem">
<?php foreach ($branchList as $b): ?>
<div class="card fade-up" style="position:relative">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem">
    <div>
      <h3 style="font-size:1.05rem;font-weight:700"><?= htmlspecialchars($b['name']) ?></h3>
      <div style="color:var(--text-muted);font-size:.83rem;margin-top:.25rem">📍 <?= htmlspecialchars($b['location']) ?></div>
    </div>
    <?php if ($role === 'main'): ?>
    <div class="flex gap-1">
      <button class="btn btn-ghost btn-sm btn-icon" title="Edit branch"
        onclick="fillBranchEdit(<?= htmlspecialchars(json_encode($b)) ?>)">✏️</button>
      <form method="POST" onsubmit="confirmDelete(this); return false;" style="display:inline">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="branch_id" value="<?= $b['id'] ?>">
        <button type="submit" class="btn btn-ghost btn-sm btn-icon" title="Delete branch">🗑️</button>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem">
    <div style="background:var(--bg3);border-radius:8px;padding:.75rem;text-align:center">
      <div style="font-size:1.5rem;font-weight:800;color:var(--primary)"><?= $b['total_members'] ?></div>
      <div style="font-size:.72rem;color:var(--text-muted)">Total Members</div>
    </div>
    <div style="background:var(--bg3);border-radius:8px;padding:.75rem;text-align:center">
      <div style="font-size:1.5rem;font-weight:800;color:var(--accent)"><?= $b['active_members'] ?></div>
      <div style="font-size:.72rem;color:var(--text-muted)">Active</div>
    </div>
    <div style="background:var(--bg3);border-radius:8px;padding:.75rem;text-align:center">
      <div style="font-size:1.5rem;font-weight:800;color:var(--danger)"><?= $b['inactive_members'] ?></div>
      <div style="font-size:.72rem;color:var(--text-muted)">Inactive</div>
    </div>
    <div style="background:var(--bg3);border-radius:8px;padding:.75rem;text-align:center">
      <div style="font-size:1.5rem;font-weight:800;color:var(--warning)"><?= $b['pending_members'] ?></div>
      <div style="font-size:.72rem;color:var(--text-muted)">Pending</div>
    </div>
  </div>

  <div style="font-size:.83rem;color:var(--text-muted);line-height:1.9">
    <div>👤 Manager: <span style="color:var(--text)"><?= htmlspecialchars($b['manager']) ?></span></div>
    <div>📞 <?= htmlspecialchars($b['phone']) ?></div>
    <div>📧 <?= htmlspecialchars($b['email']) ?></div>
    <div>🗓️ Since: <?= date('d M Y', strtotime($b['created_at'])) ?></div>
  </div>

  <div style="margin-top:1rem">
    <a href="/membership-demo/members.php?branch=<?= $b['id'] ?>" class="btn btn-ghost btn-sm">View Members →</a>
  </div>
</div>
<?php endforeach; ?>
</div>

<?php if ($role === 'main'): ?>
<!-- ADD BRANCH MODAL -->
<div class="modal-overlay" id="modal-add-branch">
  <div class="modal">
    <div class="modal-header">
      <h3>➕ Add New Branch</h3>
      <button class="modal-close" onclick="closeModal('modal-add-branch')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group"><label>Branch Name</label><input type="text" name="name" required placeholder="East Side Branch"></div>
      <div class="form-group"><label>Location</label><input type="text" name="location" required placeholder="123 Main St, City"></div>
      <div class="form-group"><label>Manager</label><input type="text" name="manager" required placeholder="Full Name"></div>
      <div class="form-row">
        <div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="(02) 8xxx-xxxx"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="branch@mail.demo"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add-branch')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Branch</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT BRANCH MODAL -->
<div class="modal-overlay" id="modal-edit-branch">
  <div class="modal">
    <div class="modal-header">
      <h3>✏️ Edit Branch</h3>
      <button class="modal-close" onclick="closeModal('modal-edit-branch')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="branch_id" id="eb-id">
      <div class="form-group"><label>Branch Name</label><input type="text" name="name" id="eb-name" required></div>
      <div class="form-group"><label>Location</label><input type="text" name="location" id="eb-location" required></div>
      <div class="form-group"><label>Manager</label><input type="text" name="manager" id="eb-manager" required></div>
      <div class="form-row">
        <div class="form-group"><label>Phone</label><input type="text" name="phone" id="eb-phone"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" id="eb-email"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit-branch')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Branch</button>
      </div>
    </form>
  </div>
</div>

<script>
function fillBranchEdit(b) {
  document.getElementById('eb-id').value       = b.id;
  document.getElementById('eb-name').value     = b.name;
  document.getElementById('eb-location').value = b.location;
  document.getElementById('eb-manager').value  = b.manager;
  document.getElementById('eb-phone').value    = b.phone;
  document.getElementById('eb-email').value    = b.email;
  openModal('modal-edit-branch');
}
</script>
<?php endif; ?>

<?php layoutClose(); ?>
