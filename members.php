<?php
// members.php — Member Management (View, Search, Add, Edit, Delete)
// ============================================================
require_once __DIR__ . '/config.php';
requireLogin();
require_once __DIR__ . '/includes/layout.php';

$pdo      = getDB();
$role     = $_SESSION['role'];
$branchId = (int) sessionBranchId(); // cast to int — local admin's branch

// ---------- Handle POST actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    // ADD MEMBER
    if ($action === 'add') {
        // For main admin: take branch from form; for local admin: use session
        $bid = ($role === 'main') ? (int)($_POST['branch_id'] ?? 0) : $branchId;

        if ($bid <= 0) {
            flash('error', 'Branch is required. Please select a branch.');
            header('Location: /membership-demo/members.php');
            exit;
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $email     = trim($_POST['email']      ?? '');

        if ($firstName === '' || $lastName === '' || $email === '') {
            flash('error', 'First name, last name, and email are required.');
            header('Location: /membership-demo/members.php');
            exit;
        }

        // Build a safe unique slug from name + timestamp
        $rawSlug = strtolower($firstName . '-' . $lastName);
        $rawSlug = preg_replace('/[^a-z0-9]+/', '-', $rawSlug);
        $rawSlug = trim($rawSlug, '-');
        $slug    = $rawSlug . '-' . substr(uniqid(), -6);

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO members
                 (branch_id, first_name, last_name, email, phone, address,
                  membership_type, status, join_date, profile_slug, approved)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $bid,
                $firstName,
                $lastName,
                $email,
                trim($_POST['phone']   ?? ''),
                trim($_POST['address'] ?? ''),
                $_POST['membership_type'] ?? 'Regular',
                $_POST['status']          ?? 'Active',
                $_POST['join_date']       ?? date('Y-m-d'),
                $slug,
                isset($_POST['approved']) ? 1 : 0,
            ]);
            flash('success', 'Member "' . htmlspecialchars($firstName . ' ' . $lastName) . '" added successfully.');
        } catch (PDOException $e) {
            flash('error', 'Could not add member: ' . $e->getMessage());
        }
        header('Location: /membership-demo/members.php');
        exit;
    }

    // EDIT MEMBER
    if ($action === 'edit') {
        $mid = (int)($_POST['member_id'] ?? 0);
        if ($mid <= 0) {
            flash('error', 'Invalid member.');
            header('Location: /membership-demo/members.php');
            exit;
        }
        // Local admin: only allow editing members in their own branch
        if ($role === 'local' && $branchId > 0) {
            $check = $pdo->prepare('SELECT branch_id FROM members WHERE id = ?');
            $check->execute([$mid]);
            $memberBranch = (int)($check->fetchColumn() ?: 0);
            if ($memberBranch !== $branchId) {
                flash('error', 'You can only edit members in your own branch.');
                header('Location: /membership-demo/members.php');
                exit;
            }
        }
        try {
            $stmt = $pdo->prepare(
                'UPDATE members
                 SET first_name=?, last_name=?, email=?, phone=?, address=?,
                     membership_type=?, status=?, join_date=?, approved=?
                 WHERE id=?'
            );
            $stmt->execute([
                trim($_POST['first_name'] ?? ''),
                trim($_POST['last_name']  ?? ''),
                trim($_POST['email']      ?? ''),
                trim($_POST['phone']      ?? ''),
                trim($_POST['address']    ?? ''),
                $_POST['membership_type'] ?? 'Regular',
                $_POST['status']          ?? 'Active',
                $_POST['join_date']       ?? date('Y-m-d'),
                isset($_POST['approved']) ? 1 : 0,
                $mid,
            ]);
            flash('success', 'Member updated successfully.');
        } catch (PDOException $e) {
            flash('error', 'Could not update member: ' . $e->getMessage());
        }
        header('Location: /membership-demo/members.php');
        exit;
    }

    // DELETE MEMBER
    if ($action === 'delete') {
        $mid = (int)($_POST['member_id'] ?? 0);
        if ($mid > 0) {
            // Local admin: only allow deleting members in their own branch
            if ($role === 'local' && $branchId > 0) {
                $check = $pdo->prepare('SELECT branch_id FROM members WHERE id = ?');
                $check->execute([$mid]);
                $memberBranch = (int)($check->fetchColumn() ?: 0);
                if ($memberBranch !== $branchId) {
                    flash('error', 'You can only delete members in your own branch.');
                    header('Location: /membership-demo/members.php');
                    exit;
                }
            }
            try {
                $pdo->prepare('DELETE FROM members WHERE id = ?')->execute([$mid]);
                flash('success', 'Member deleted.');
            } catch (PDOException $e) {
                flash('error', 'Could not delete member: ' . $e->getMessage());
            }
        }
        header('Location: /membership-demo/members.php');
        exit;
    }
}

// ---------- Fetch branches for add/filter ----------
$branches = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();

// ---------- Fetch members ----------
$search       = trim($_GET['q']      ?? '');
$filterStatus = $_GET['status']      ?? '';
$filterBranch = ($role === 'main') ? (int)($_GET['branch'] ?? 0) : $branchId;

$sql    = 'SELECT m.*, b.name AS branch_name FROM members m JOIN branches b ON b.id = m.branch_id WHERE 1=1';
$params = [];

if ($role === 'local' && $branchId > 0) {
    $sql     .= ' AND m.branch_id = ?';
    $params[] = $branchId;
} elseif ($filterBranch > 0) {
    $sql     .= ' AND m.branch_id = ?';
    $params[] = $filterBranch;
}

if ($search !== '') {
    $sql     .= ' AND (m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ?)';
    $like     = '%' . $search . '%';
    $params   = array_merge($params, [$like, $like, $like]);
}

if ($filterStatus !== '') {
    $sql     .= ' AND m.status = ?';
    $params[] = $filterStatus;
}

$sql .= ' ORDER BY m.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Get local admin branch name for display
$localBranchName = '';
if ($role === 'local' && $branchId > 0) {
    foreach ($branches as $b) {
        if ((int)$b['id'] === $branchId) {
            $localBranchName = $b['name'];
            break;
        }
    }
}

$flash_s = getFlash('success');
$flash_e = getFlash('error');

layoutHead('Members');
layoutNav('members');
?>

<?php if ($flash_s): ?>
  <div class="alert alert-success" data-auto-dismiss>&#10003; <?= htmlspecialchars($flash_s) ?></div>
<?php endif; ?>
<?php if ($flash_e): ?>
  <div class="alert alert-error" data-auto-dismiss>&#9888; <?= htmlspecialchars($flash_e) ?></div>
<?php endif; ?>

<?php if ($role === 'local'): ?>
<div class="demo-banner">
  &#128274; <strong>Local Admin View:</strong> Showing members from <strong><?= htmlspecialchars($localBranchName) ?></strong> branch only.
</div>
<?php endif; ?>

<div class="section-head">
  <h2>&#128101; Member Management <span style="color:var(--text-muted);font-weight:400;font-size:.85rem">(<?= count($members) ?> records)</span></h2>
  <button class="btn btn-primary" id="btn-add-member" onclick="openModal('modal-add')">+ Add Member</button>
</div>

<!-- Filters -->
<div class="card mb-2 fade-up" style="padding:1rem">
  <form method="GET" action="/membership-demo/members.php" class="search-bar">
    <input type="search" name="q" placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>">
    <select name="status" style="max-width:160px">
      <option value="">All Statuses</option>
      <?php foreach (['Active','Inactive','Pending'] as $s): ?>
        <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($role === 'main'): ?>
    <select name="branch" style="max-width:200px">
      <option value="0">All Branches</option>
      <?php foreach ($branches as $b): ?>
        <option value="<?= $b['id'] ?>" <?= $filterBranch === (int)$b['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($b['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="/membership-demo/members.php" class="btn btn-ghost btn-sm">Clear</a>
  </form>
</div>

<!-- Members Table -->
<div class="card fade-up">
  <div class="table-wrap">
    <table id="members-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Branch</th>
          <th>Type</th>
          <th>Status</th>
          <th>Joined</th>
          <th>Public</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($members)): ?>
          <tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--text-muted)">No members found.</td></tr>
        <?php endif; ?>
        <?php foreach ($members as $i => $m): ?>
        <tr>
          <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
          <td><strong><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></strong></td>
          <td style="color:var(--text-muted)"><?= htmlspecialchars($m['email']) ?></td>
          <td style="color:var(--text-muted)"><?= htmlspecialchars($m['phone']) ?></td>
          <td style="color:var(--text-muted)"><?= htmlspecialchars($m['branch_name']) ?></td>
          <td><span class="badge badge-<?= strtolower($m['membership_type']) ?>"><?= $m['membership_type'] ?></span></td>
          <td><span class="badge badge-<?= strtolower($m['status']) ?>"><?= $m['status'] ?></span></td>
          <td style="color:var(--text-muted)"><?= $m['join_date'] ?></td>
          <td>
            <?php if ($m['approved']): ?>
              <a href="/membership-demo/profile.php?slug=<?= urlencode($m['profile_slug']) ?>"
                 target="_blank" class="btn btn-ghost btn-sm btn-icon" title="View public profile">&#128279;</a>
            <?php else: ?>
              <span style="color:var(--text-dim);font-size:.78rem">Not approved</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="flex gap-1">
              <button class="btn btn-ghost btn-sm btn-icon" title="Edit"
                id="edit-btn-<?= $m['id'] ?>"
                data-mid="<?= $m['id'] ?>"
                data-fname="<?= htmlspecialchars($m['first_name'], ENT_QUOTES) ?>"
                data-lname="<?= htmlspecialchars($m['last_name'], ENT_QUOTES) ?>"
                data-email="<?= htmlspecialchars($m['email'], ENT_QUOTES) ?>"
                data-phone="<?= htmlspecialchars($m['phone'], ENT_QUOTES) ?>"
                data-address="<?= htmlspecialchars($m['address'] ?? '', ENT_QUOTES) ?>"
                data-mtype="<?= $m['membership_type'] ?>"
                data-status="<?= $m['status'] ?>"
                data-jdate="<?= $m['join_date'] ?>"
                data-approved="<?= $m['approved'] ?>"
                onclick="openEditModal(this)">&#9999;</button>
              <form method="POST" action="/membership-demo/members.php" onsubmit="return confirm('Delete this member?')" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm btn-icon" title="Delete">&#128465;</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ===== ADD MEMBER MODAL ===== -->
<div class="modal-overlay" id="modal-add">
  <div class="modal">
    <div class="modal-header">
      <h3>&#43; Add New Member</h3>
      <button class="modal-close" onclick="closeModal('modal-add')" type="button">&#10005;</button>
    </div>
    <form method="POST" action="/membership-demo/members.php">
      <input type="hidden" name="action" value="add">

      <?php if ($role === 'local' && $branchId > 0): ?>
        <!-- Local admin: branch pre-set from session, hidden -->
        <input type="hidden" name="branch_id" value="<?= $branchId ?>">
        <div class="demo-banner" style="margin-bottom:1rem;font-size:.82rem">
          &#128274; Adding member to: <strong><?= htmlspecialchars($localBranchName) ?></strong>
        </div>
      <?php elseif ($role === 'main'): ?>
        <!-- Main admin: must select branch -->
        <div class="form-group">
          <label for="add-branch">Branch <span style="color:var(--danger)">*</span></label>
          <select id="add-branch" name="branch_id" required>
            <option value="">-- Select Branch --</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group">
          <label for="add-fname">First Name <span style="color:var(--danger)">*</span></label>
          <input type="text" id="add-fname" name="first_name" required placeholder="e.g. Juan">
        </div>
        <div class="form-group">
          <label for="add-lname">Last Name <span style="color:var(--danger)">*</span></label>
          <input type="text" id="add-lname" name="last_name" required placeholder="e.g. dela Cruz">
        </div>
      </div>

      <div class="form-group">
        <label for="add-email">Email <span style="color:var(--danger)">*</span></label>
        <input type="email" id="add-email" name="email" required placeholder="juan@mail.demo">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="add-phone">Phone</label>
          <input type="text" id="add-phone" name="phone" placeholder="09171234567">
        </div>
        <div class="form-group">
          <label for="add-jdate">Join Date <span style="color:var(--danger)">*</span></label>
          <input type="date" id="add-jdate" name="join_date" required value="<?= date('Y-m-d') ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="add-address">Address</label>
        <input type="text" id="add-address" name="address" placeholder="Street, City">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="add-mtype">Membership Type</label>
          <select id="add-mtype" name="membership_type">
            <?php foreach (['Regular','Premium','Student','Senior'] as $t): ?>
              <option value="<?= $t ?>"><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="add-status">Status</label>
          <select id="add-status" name="status">
            <?php foreach (['Active','Inactive','Pending'] as $s): ?>
              <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
          <input type="checkbox" name="approved" value="1" checked style="width:auto">
          Approved (visible on public profile)
        </label>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="btn-save-member">&#10003; Save Member</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== EDIT MEMBER MODAL ===== -->
<div class="modal-overlay" id="modal-edit">
  <div class="modal">
    <div class="modal-header">
      <h3>&#9999; Edit Member</h3>
      <button class="modal-close" onclick="closeModal('modal-edit')" type="button">&#10005;</button>
    </div>
    <form method="POST" action="/membership-demo/members.php" id="edit-form">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="member_id" id="edit-mid">

      <div class="form-row">
        <div class="form-group">
          <label for="edit-fname">First Name</label>
          <input type="text" id="edit-fname" name="first_name" required>
        </div>
        <div class="form-group">
          <label for="edit-lname">Last Name</label>
          <input type="text" id="edit-lname" name="last_name" required>
        </div>
      </div>

      <div class="form-group">
        <label for="edit-email">Email</label>
        <input type="email" id="edit-email" name="email" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="edit-phone">Phone</label>
          <input type="text" id="edit-phone" name="phone">
        </div>
        <div class="form-group">
          <label for="edit-jdate">Join Date</label>
          <input type="date" id="edit-jdate" name="join_date" required>
        </div>
      </div>

      <div class="form-group">
        <label for="edit-address">Address</label>
        <input type="text" id="edit-address" name="address">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="edit-mtype">Membership Type</label>
          <select id="edit-mtype" name="membership_type">
            <?php foreach (['Regular','Premium','Student','Senior'] as $t): ?>
              <option value="<?= $t ?>"><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="edit-status">Status</label>
          <select id="edit-status" name="status">
            <?php foreach (['Active','Inactive','Pending'] as $s): ?>
              <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
          <input type="checkbox" id="edit-approved" name="approved" value="1" style="width:auto">
          Approved (visible on public profile)
        </label>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#10003; Update Member</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(btn) {
  var d = btn.dataset;
  document.getElementById('edit-mid').value         = d.mid;
  document.getElementById('edit-fname').value       = d.fname;
  document.getElementById('edit-lname').value       = d.lname;
  document.getElementById('edit-email').value       = d.email;
  document.getElementById('edit-phone').value       = d.phone;
  document.getElementById('edit-address').value     = d.address;
  document.getElementById('edit-jdate').value       = d.jdate;
  document.getElementById('edit-mtype').value       = d.mtype;
  document.getElementById('edit-status').value      = d.status;
  document.getElementById('edit-approved').checked  = d.approved === '1';
  openModal('modal-edit');
}
</script>

<?php layoutClose(); ?>
