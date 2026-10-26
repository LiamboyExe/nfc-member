<?php
// ============================================================
// config.php — Database connection (PDO)
// Place this file in: htdocs/membership-demo/config.php
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'membership_demo');
define('DB_USER', 'root');      // Default XAMPP MySQL user
define('DB_PASS', '');          // Default XAMPP MySQL password (empty)
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'TempleStreet');
define('BASE_URL', 'http://localhost/membership-demo');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:2rem;background:#1a1a2e;color:#e94560;min-height:100vh">
                <h2>&#9888; Database Connection Error</h2>
                <p>Could not connect to <strong>' . DB_NAME . '</strong>.</p>
                <ul>
                  <li>Make sure XAMPP MySQL service is running.</li>
                  <li>Run <code>database.sql</code> in phpMyAdmin first.</li>
                  <li>Check DB_USER / DB_PASS in <code>config.php</code>.</li>
                </ul>
                <p style="color:#888;font-size:.85rem">Error: ' . htmlspecialchars($e->getMessage()) . '</p>
            </div>');
        }
    }
    return $pdo;
}

// ---- Session helpers ----
session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['admin_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function isMainAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'main';
}

function sessionBranchId(): ?int {
    return $_SESSION['branch_id'] ?? null;
}

function flash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function getFlash(string $key): string {
    $msg = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $msg;
}

