<?php
defined('AMBOZY_CRM') or die('Direct access not permitted.');

session_name('ambozy_crm');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function auth_check(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . admin_url('index.php'));
        exit;
    }
}

function auth_login(string $username, string $password): bool {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('SELECT id, password_hash, full_name, role FROM admin_users WHERE username = ? AND active = 1 LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_name'] = $user['full_name'];
        $_SESSION['admin_role'] = $user['role'];
        $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
        return true;
    }
    return false;
}

function auth_logout(): void {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . admin_url('index.php'));
    exit;
}

function auth_require_admin(): void {
    auth_check();
    if ($_SESSION['admin_role'] !== 'admin') {
        die('<p>Access denied. Admin role required.</p>');
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}
