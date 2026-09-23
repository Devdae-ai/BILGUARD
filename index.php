<?php require_once __DIR__ . '/includes/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>BillGuard — recurring payment detection</title>
  <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body class="welcome-page">

  <header class="welcome-header">
    <div class="welcome-container welcome-nav">
      <a href="index.php" class="brand-mark">BILLGUARD</a>
      <nav class="welcome-links">
        <?php if (current_user_id()): ?>
          <a href="dashboard.php">Dashboard</a>
        <?php else: ?>
          <a href="login.php">Log in</a>
          <a href="register.php" class="welcome-button secondary">Create account</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <section class="welcome-hero">
    <div class="welcome-container welcome-hero-grid">
      <div class="welcome-hero-copy">
        <p class="eyebrow">Recurring payment detection</p>
        <h1>See the payments that keep coming back.</h1>
        <p class="welcome-lede">Track everyday spending and let BillGuard surface recurring charges, upcoming deadlines, and pending payments.</p>
        <div class="welcome-actions">
          <a href="register.php" class="welcome-button primary">Create free account</a>
          <a href="#how" class="welcome-button secondary">See how it works</a>
        </div>
      </div>
      <div class="welcome-preview" aria-label="Example recurring payment summary">
        <div class="preview-heading"><span class="signal-dot"></span><span>Recurring payments</span></div>
        <div class="preview-row"><span>Internet</span><strong>₱1,699 <small>monthly</small></strong></div>
        <div class="preview-row"><span>Insurance</span><strong>₱1,200 <small>monthly</small></strong></div>
        <div class="preview-row"><span>Streaming</span><strong>₱499 <small>monthly</small></strong></div>
        <p class="preview-note">3 patterns detected</p>
      </div>
    </div>
  </section>

  <section class="welcome-section">
    <div class="welcome-container welcome-two-column">
      <div>
        <p class="eyebrow">What it finds</p>
        <h2>Clarity without the extra work.</h2>
        <p class="muted-copy">BillGuard compares merchants, amounts, and dates to identify a steady rhythm. You get the signal without manually tagging every charge.</p>
      </div>
      <div class="welcome-points">
        <div><strong>Payment history</strong><span>Keep every charge in one simple timeline.</span></div>
        <div><strong>Pending and deadlines</strong><span>Know what needs attention next.</span></div>
        <div><strong>Recurring patterns</strong><span>Spot repeat payments before they surprise you.</span></div>
      </div>
    </div>
  </section>

  <section id="how" class="welcome-section welcome-section-last">
    <div class="welcome-container">
    <p class="eyebrow">How it works</p>
    <div class="welcome-steps">
      <div>
        <div class="step-number">01</div>
        <h3>Log transactions</h3>
        <p class="muted-copy">Add the merchant, amount, and date as charges happen.</p>
      </div>
      <div>
        <div class="step-number">02</div>
        <h3>BillGuard studies the history</h3>
        <p class="muted-copy">It checks the gaps between dates for a steady rhythm.</p>
      </div>
      <div>
        <div class="step-number">03</div>
        <h3>You get the signal</h3>
        <p class="muted-copy">Confirmed patterns appear on your dashboard with the next expected date.</p>
      </div>
    </div>
    </div>
  </section>

  <footer class="welcome-footer">
    <div class="welcome-container"><p class="eyebrow">BillGuard</p></div>
  </footer>

  <script>
  </script>
</body>
</html>