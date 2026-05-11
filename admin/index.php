<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_GET['logout'])) { auth_logout(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        if (auth_login($user, $pass)) {
            header('Location: ' . admin_url('dashboard.php'));
            exit;
        }
        $error = 'Incorrect username or password.';
    }
}

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('dashboard.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>Login — Ambozy CRM</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <img src="../assets/images/logo-white.png" alt="Ambozy Graphics" height="50">
      <h1>Ambozy CRM</h1>
      <p>Sign in to your account</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:1rem">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" type="text" id="username" name="username" autocomplete="username" required autofocus>
      </div>
      <div class="form-group" style="margin-bottom:1.5rem">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Sign In</button>
    </form>

    <p style="text-align:center;font-size:.72rem;color:var(--text-muted);margin-top:2rem">
      Ambozy Graphics Solutions Ltd — Internal Use Only
    </p>
  </div>
</div>
</body>
</html>
