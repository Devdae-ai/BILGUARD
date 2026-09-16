<?php
$active = $active ?? '';
?>
<header class="border-b border-line">
  <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
    <a href="dashboard.php" class="font-mono-data text-sm tracking-wide text-paper">BILLGUARD</a>
    <nav class="flex items-center gap-6 font-mono-data text-xs text-muted">
      <a href="dashboard.php" class="hover:text-signal transition-colors <?= $active === 'dashboard' ? 'text-signal' : '' ?>">Dashboard</a>
      <a href="profile.php" class="hover:text-signal transition-colors <?= $active === 'profile' ? 'text-signal' : '' ?>">Profile</a>
      <a href="logout.php" class="hover:text-signal transition-colors">Log out</a>
      <a href="profile.php" class="block w-7 h-7 rounded-full overflow-hidden avatar-ring">
        <img src="<?= $activeUser['avatar'] ? h($activeUser['avatar']) : 'assets/img/default-avatar.svg' ?>" alt="" class="w-full h-full object-cover">
      </a>
    </nav>
  </div>
</header>