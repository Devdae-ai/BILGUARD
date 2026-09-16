<?php
require_once __DIR__ . '/includes/auth.php';
redirect_if_logged_in();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $pdo = get_pdo();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'An account with that email already exists.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            flash_set('success', 'Welcome to BillGuard, ' . $name . '.');
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Create account — BillGuard</title>
  <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body class="min-h-screen flex items-center justify-center px-6">
  <div class="w-full max-w-sm">
    <a href="index.php" class="font-mono-data text-xs text-muted hover:text-signal">&larr; BillGuard</a>
    <h1 class="font-serif text-3xl mt-6 mb-1">Create your account</h1>
    <p class="text-muted text-sm mb-8">Start tracking what leaves quietly.</p>

    <?php if ($errors): ?>
      <div class="border border-leak text-leak text-xs font-mono-data px-4 py-3 mb-6">
        <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <div>
        <label class="block font-mono-data text-xs text-muted mb-1">Name</label>
        <input type="text" name="name" value="<?= h($_POST['name'] ?? '') ?>" class="w-full px-3 py-2 text-sm" required>
      </div>
      <div>
        <label class="block font-mono-data text-xs text-muted mb-1">Email</label>
        <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" class="w-full px-3 py-2 text-sm" required>
      </div>
      <div>
        <label class="block font-mono-data text-xs text-muted mb-1">Password</label>
        <input type="password" name="password" class="w-full px-3 py-2 text-sm" required minlength="8">
      </div>
      <div>
        <label class="block font-mono-data text-xs text-muted mb-1">Confirm password</label>
        <input type="password" name="confirm" class="w-full px-3 py-2 text-sm" required minlength="8">
      </div>
      <button type="submit" class="w-full btn-signal py-2.5 text-sm mt-2">Create free account</button>
    </form>

    <p class="text-muted text-xs font-mono-data mt-6">
      Already have an account? <a href="login.php" class="text-paper hover:text-signal">Log in</a>
    </p>
  </div>
</body>
</html>