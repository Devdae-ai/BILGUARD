<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo();
$userId = current_user_id();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $currency = trim($_POST['currency'] ?? '₱') ?: '₱';

    if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Enter a valid name.';
    if (mb_strlen($currency) > 5) $errors[] = 'Currency symbol is too long.';

    $avatarPath = null;
    if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['avatar']['tmp_name'];
        $size = $_FILES['avatar']['size'];
        $info = @getimagesize($tmp);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

        if (!$info || !isset($allowed[$info['mime']])) {
            $errors[] = 'Profile picture must be a JPG, PNG, WEBP, or GIF image.';
        } elseif ($size > 3 * 1024 * 1024) {
            $errors[] = 'Profile picture must be under 3MB.';
        } else {
            $ext = $allowed[$info['mime']];
            $filename = 'avatar_' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = __DIR__ . '/uploads/' . $filename;
            if (move_uploaded_file($tmp, $dest)) {
                $avatarPath = 'uploads/' . $filename;
            } else {
                $errors[] = 'Could not save the uploaded image. Try again.';
            }
        }
    }

    if (!$errors) {
        if ($avatarPath) {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, currency = ?, avatar = ? WHERE id = ?');
            $stmt->execute([$name, $currency, $avatarPath, $userId]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, currency = ? WHERE id = ?');
            $stmt->execute([$name, $currency, $userId]);
        }
        flash_set('success', 'Profile updated.');
        header('Location: profile.php');
        exit;
    }
}

$activeUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Profile — BillGuard</title>
  <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body class="min-h-screen">
  <?php $active = 'profile'; include __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-sm mx-auto px-6 py-14">
    <h1 class="font-serif text-3xl mb-1">Your profile</h1>
    <p class="text-muted text-sm mb-8">Update how you appear in BillGuard.</p>

    <?php if ($msg = flash_get('success')): ?>
      <div class="border border-signal text-signal text-xs font-mono-data px-4 py-3 mb-6"><?= h($msg) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="border border-leak text-leak text-xs font-mono-data px-4 py-3 mb-6">
        <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="space-y-5">
      <div class="flex items-center gap-4">
        <img src="<?= $activeUser['avatar'] ? h($activeUser['avatar']) : 'assets/img/default-avatar.svg' ?>" alt="" class="w-16 h-16 rounded-full object-cover avatar-ring">
        <div>
          <label class="font-mono-data text-xs text-muted mb-1 block">Profile picture</label>
          <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif" class="text-xs font-mono-data text-muted file:mr-3 file:py-1.5 file:px-3 file:border file:border-line file:bg-transparent file:text-paper file:text-xs file:font-mono-data">
        </div>
      </div>
      <div>
        <label class="block font-mono-data text-xs text-muted mb-1">Name</label>
        <input type="text" name="name" value="<?= h($activeUser['name']) ?>" class="w-full px-3 py-2 text-sm" required>
      </div>
      <div>
        <label class="block font-mono-data text-xs text-muted mb-1">Email</label>
        <input type="text" value="<?= h($activeUser['email']) ?>" class="w-full px-3 py-2 text-sm opacity-50" disabled>
      </div>
      <div>
        <label class="block font-mono-data text-xs text-muted mb-1">Currency symbol</label>
        <input type="text" name="currency" value="<?= h($activeUser['currency']) ?>" maxlength="5" class="w-24 px-3 py-2 text-sm">
      </div>
      <button type="submit" class="w-full btn-signal py-2.5 text-sm mt-2">Save changes</button>
    </form>
  </main>
</body>
</html>