<?php
// index.php — Homepage + Admin Login
// ============================================================
require_once __DIR__ . '/config.php';

// Already logged in → go to dashboard
if (isLoggedIn()) {
    header('Location: /membership-demo/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        // Demo: plain-text password comparison (NOT secure — demo only)
        if ($admin && $admin['password'] === $password) {
            $_SESSION['admin_id']  = $admin['id'];
            $_SESSION['username']  = $admin['username'];
            $_SESSION['role']      = $admin['role'];
            $_SESSION['branch_id'] = $admin['branch_id'];
            $_SESSION['full_name'] = $admin['full_name'];
            header('Location: /membership-demo/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Temple Street — Multi-Branch Membership Demo</title>
  <meta name="description" content="Multi-Branch Membership Management System — local XAMPP demo.">
  <link rel="stylesheet" href="/membership-demo/assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-container">

    <!-- Hero / Info -->
    <div class="login-hero fade-up">
      <h1>🏛️ Temple Street<br>Demo System</h1>
      <p>A simple multi-branch membership management demonstration built with PHP, MySQL, and vanilla CSS/JS. Runs entirely on XAMPP — no internet required.</p>

      <div class="feature-list">
        <div class="feature-item">
          <div class="fi-icon">📊</div>
          Real-time dashboard with member &amp; branch stats
        </div>
        <div class="feature-item">
          <div class="fi-icon">👥</div>
          Add, edit, search, and manage fictional members
        </div>
        <div class="feature-item">
          <div class="fi-icon">🏢</div>
          Two-branch setup with role-based view simulation
        </div>
        <div class="feature-item">
          <div class="fi-icon">💳</div>
          NFC card simulation — assign, copy, and preview URLs
        </div>
        <div class="feature-item">
          <div class="fi-icon">🔗</div>
          Public member profile pages via shareable slug URLs
        </div>
      </div>

      <div class="demo-banner mt-3">
        ⚠️ This is a local demo only. All data is fictional. Role simulation is NOT real authentication.
      </div>
    </div>

    <!-- Login Box -->
    <div class="login-box fade-up">
      <h2>🔐 Admin Login</h2>

      <div class="creds-hint">
        <strong>Demo credentials:</strong><br>
        Main Admin: <strong>admin</strong> / <strong>admin123</strong><br>
        North Admin: <strong>north_admin</strong> / <strong>north123</strong><br>
        South Admin: <strong>south_admin</strong> / <strong>south123</strong>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error" data-auto-dismiss>⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="/membership-demo/index.php" id="login-form">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="e.g. admin"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter password"
                 autocomplete="current-password" required>
        </div>

        <button type="submit" class="btn btn-primary w-full" id="login-btn" style="justify-content:center;padding:.75rem">
          🚀 Login to Dashboard
        </button>
      </form>

      <p class="text-sm text-muted mt-2" style="text-align:center">
        First time? Import <code>database.sql</code> in phpMyAdmin.
      </p>
    </div>

  </div>
</div>
<script src="/membership-demo/assets/js/app.js"></script>
</body>
</html>
