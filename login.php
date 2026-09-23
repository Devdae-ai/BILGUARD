<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
redirect_if_logged_in();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = get_pdo()->prepare('SELECT id, name, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = (int) $user['id'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'That email and password combination doesn\'t match our records.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Log in — BillGuard</title>
  <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body class="min-h-screen flex items-center justify-center px-6">
  <div class="w-full max-w-sm">
    <a href="index.php" class="font-mono-data text-xs text-muted hover:text-signal">&larr; BillGuard</a>
    <h1 class="font-serif text-3xl mt-6 mb-1">Welcome back</h1>
    <p class="text-muted text-sm mb-8">Log in to see what's due.</p>

    <?php if ($error): ?>
      <div class="border border-leak text-leak text-xs font-mono-data px-4 py-3 mb-6"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <div>
        <label class="block font-mono-data text-xs text-muted mb-1">Email</label>
        <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" class="w-full px-3 py-2 text-sm" required autofocus>
      </div>
      <div>
        <label class="block font-mono-data text-xs text-muted mb-1">Password</label>
        <input type="password" name="password" class="w-full px-3 py-2 text-sm" required>
      </div>
      <button type="submit" class="w-full btn-signal py-2.5 text-sm mt-2">Log in</button>
    </form>

    <p class="text-muted text-xs font-mono-data mt-6">
      No account yet? <a href="register.php" class="text-paper hover:text-signal">Create one</a>
    </p>
  </div>
</body>
</html>