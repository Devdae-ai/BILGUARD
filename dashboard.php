<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/detect_recurring.php';
require_login();

$pdo = get_pdo();
$userId = current_user_id();
$activeUser = current_user();
$defaultCategories = [
  'Housing & Living',
  'Utilities',
  'Food & Groceries',
  'Transportation',
  'Health & Medical',
];

function ensure_category(PDO $pdo, int $userId, string $name): ?int {
    $name = trim($name);
    if ($name === '') {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id FROM categories WHERE user_id = ? AND name = ?');
    $stmt->execute([$userId, $name]);
    $existing = $stmt->fetchColumn();
    if ($existing !== false) {
        return (int) $existing;
    }

    $insert = $pdo->prepare('INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)');
    $insert->execute([$userId, $name, '#7dd3fc']);
    return (int) $pdo->lastInsertId();
}

  foreach ($defaultCategories as $defaultCategory) {
    ensure_category($pdo, $userId, $defaultCategory);
  }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_transaction') {
        $merchant = trim($_POST['merchant'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $date = $_POST['txn_date'] ?? '';
        $status = in_array($_POST['payment_status'] ?? 'paid', ['paid', 'pending'], true) ? $_POST['payment_status'] : 'paid';
        $dueDate = trim((string) ($_POST['due_date'] ?? ''));
        $categoryName = trim((string) ($_POST['new_category'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);

        if ($categoryId <= 0 && $categoryName === '') {
            $categoryName = trim((string) ($_POST['category'] ?? '')) ?: 'Uncategorized';
        }

        if ($merchant !== '' && $amount > 0 && strtotime($date)) {
            if ($categoryId > 0) {
                $resolvedCategoryId = $categoryId;
                $resolvedCategoryName = $pdo->prepare('SELECT name FROM categories WHERE id = ? AND user_id = ?');
                $resolvedCategoryName->execute([$categoryId, $userId]);
                $resolvedCategoryName = $resolvedCategoryName->fetchColumn() ?: 'Uncategorized';
            } else {
                $resolvedCategoryId = ensure_category($pdo, $userId, $categoryName ?: 'Uncategorized');
                $resolvedCategoryName = $categoryName ?: 'Uncategorized';
            }

            $stmt = $pdo->prepare('INSERT INTO transactions (user_id, merchant, merchant_key, amount, category, category_id, payment_status, due_date, txn_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $userId,
                $merchant,
                normalize_merchant($merchant),
                $amount,
                $resolvedCategoryName,
                $resolvedCategoryId,
                $status,
                $dueDate !== '' ? date('Y-m-d', strtotime($dueDate)) : null,
                date('Y-m-d', strtotime($date))
            ]);
            flash_set('success', 'Transaction added.');
        } else {
            flash_set('error', 'Fill in a merchant, amount, and valid date.');
        }
        header('Location: dashboard.php');
        exit;
    }

    if ($action === 'delete_transaction') {
        $txnId = (int) ($_POST['txn_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
        $stmt->execute([$txnId, $userId]);
        header('Location: dashboard.php');
        exit;
    }

    if ($action === 'dismiss_recurring') {
        $rpId = (int) ($_POST['rp_id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE recurring_payments SET status = "dismissed" WHERE id = ? AND user_id = ?');
        $stmt->execute([$rpId, $userId]);
        header('Location: dashboard.php');
        exit;
    }

    if ($action === 'reactivate_recurring') {
        $rpId = (int) ($_POST['rp_id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE recurring_payments SET status = "active" WHERE id = ? AND user_id = ?');
        $stmt->execute([$rpId, $userId]);
        header('Location: dashboard.php');
        exit;
    }
}

detect_recurring_for_user($pdo, $userId);

$recurring = $pdo->prepare('SELECT * FROM recurring_payments WHERE user_id = ? AND status = "active" ORDER BY next_expected_date ASC');
$recurring->execute([$userId]);
$recurring = $recurring->fetchAll();

$dismissed = $pdo->prepare('SELECT * FROM recurring_payments WHERE user_id = ? AND status = "dismissed" ORDER BY merchant ASC');
$dismissed->execute([$userId]);
$dismissed = $dismissed->fetchAll();

$categories = $pdo->prepare('SELECT * FROM categories WHERE user_id = ? ORDER BY name ASC');
$categories->execute([$userId]);
$categories = $categories->fetchAll();

$transactions = $pdo->prepare('SELECT t.*, c.name AS category_name FROM transactions t LEFT JOIN categories c ON c.id = t.category_id WHERE t.user_id = ? ORDER BY t.txn_date DESC, t.id DESC LIMIT 50');
$transactions->execute([$userId]);
$transactions = $transactions->fetchAll();

$monthlyRecurringTotal = 0;
foreach ($recurring as $r) {
    $factor = 30 / max($r['interval_days'], 1);
    $monthlyRecurringTotal += $r['avg_amount'] * $factor;
}

$pendingTotal = 0;
$deadlineCount = 0;
foreach ($transactions as $t) {
    if (($t['payment_status'] ?? 'paid') === 'pending') {
        $pendingTotal += (float) $t['amount'];
    }
    if (!empty($t['due_date']) && $t['due_date'] >= date('Y-m-d')) {
        $deadlineCount++;
    }
}

$currency = $activeUser['currency'] ?: '₱';
$today = new DateTime('today');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Dashboard — BillGuard</title>
  <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/includes/nav.php'; ?>

  <div class="page-shell">
    <div class="parallax-orb orb-one" data-parallax="0.08"></div>
    <div class="parallax-orb orb-two" data-parallax="0.12"></div>
    <div class="parallax-orb orb-three" data-parallax="0.18"></div>

    <main class="max-w-6xl mx-auto px-6 py-10 relative z-10">
    <?php if ($msg = flash_get('success')): ?>
      <div class="flash success"><?= h($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
      <div class="flash error"><?= h($msg) ?></div>
    <?php endif; ?>

    <div class="topbar">
      <div>
        <p class="eyebrow">Overview</p>
        <h1>Good to see you, <?= h(explode(' ', $activeUser['name'])[0]) ?>.</h1>
      </div>
      <div class="metric-box">
        <span>Monthly recurring</span>
        <strong><?= $currency ?><?= number_format($monthlyRecurringTotal, 2) ?></strong>
      </div>
    </div>

    <section class="summary-grid">
      <article class="summary-card">
        <div class="eyebrow">Payment history</div>
        <div class="summary-value"><?= count($transactions) ?></div>
        <div class="summary-sub">Recorded payments</div>
      </article>
      <article class="summary-card">
        <div class="eyebrow">Pending</div>
        <div class="summary-value"><?= $currency ?><?= number_format($pendingTotal, 2) ?></div>
        <div class="summary-sub">Awaiting payment</div>
      </article>
      <article class="summary-card">
        <div class="eyebrow">Deadline</div>
        <div class="summary-value"><?= $deadlineCount ?></div>
        <div class="summary-sub">Upcoming due dates</div>
      </article>
    </section>

    <section class="mb-14">
      <div class="section-header">
        <span class="signal-dot"></span>
        <h2>Recurring payments detected</h2>
      </div>

      <?php if (!$recurring): ?>
        <div class="empty-state">
          Nothing detected yet. Add a few transactions from the same merchant on a regular cadence and BillGuard will flag the pattern here.
        </div>
      <?php else: ?>
        <div class="stack-list">
          <?php foreach ($recurring as $r):
            $next = new DateTime($r['next_expected_date']);
            $daysUntil = (int) $today->diff($next)->format('%r%a');
            $dueLabel = $daysUntil < 0 ? abs($daysUntil) . 'd overdue' : ($daysUntil === 0 ? 'due today' : "in {$daysUntil}d");
          ?>
          <div class="list-row">
            <div class="list-main">
              <div class="title"><?= h($r['merchant']) ?></div>
              <div class="meta-line">
                <?= h(interval_label((int)$r['interval_days'])) ?> &middot; <?= (int) $r['occurrences'] ?> charges seen &middot; next expected <?= $next->format('M j') ?> (<?= $dueLabel ?>)
              </div>
            </div>
            <div class="list-actions">
              <div class="amount <?= $daysUntil <= 2 ? 'accent' : '' ?>"><?= $currency ?><?= number_format($r['avg_amount'], 2) ?></div>
              <form method="post">
                <input type="hidden" name="action" value="dismiss_recurring">
                <input type="hidden" name="rp_id" value="<?= (int) $r['id'] ?>">
                <button type="submit" class="text-button danger">Dismiss</button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($dismissed): ?>
        <details class="dismissed-panel">
          <summary>Dismissed patterns (<?= count($dismissed) ?>)</summary>
          <div class="stack-list mt-3">
            <?php foreach ($dismissed as $r): ?>
            <div class="list-row muted">
              <div class="title"><?= h($r['merchant']) ?></div>
              <div class="list-actions">
                <div class="amount"><?= $currency ?><?= number_format($r['avg_amount'], 2) ?></div>
                <form method="post">
                  <input type="hidden" name="action" value="reactivate_recurring">
                  <input type="hidden" name="rp_id" value="<?= (int) $r['id'] ?>">
                  <button type="submit" class="text-button">Restore</button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </details>
      <?php endif; ?>
    </section>

    <section class="content-grid">
      <div class="panel">
        <h2>Add transaction</h2>
        <form method="post" class="transaction-form">
          <input type="hidden" name="action" value="add_transaction">

          <div>
            <label>Merchant</label>
            <input type="text" name="merchant" placeholder="e.g. Netflix" required>
          </div>

          <div>
            <label>Amount (<?= $currency ?>)</label>
            <input type="number" step="0.01" min="0.01" name="amount" placeholder="499.00" required>
          </div>

          <div class="two-col">
            <div>
              <label>Date</label>
              <input type="date" name="txn_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div>
              <label>Due date</label>
              <input type="date" name="due_date">
            </div>
          </div>

          <div>
            <label>Category</label>
            <select name="category_id">
              <option value="">Choose a category</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>"><?= h($category['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>New category</label>
            <input type="text" name="new_category" placeholder="Add a custom category">
          </div>

          <div>
            <label>Status</label>
            <select name="payment_status">
              <option value="paid">Paid</option>
              <option value="pending">Pending</option>
            </select>
          </div>

          <button type="submit" class="primary-button">Record payment</button>
        </form>
      </div>

      <div class="panel">
        <h2>Payment history</h2>
        <?php if (!$transactions): ?>
          <div class="empty-state">No transactions yet. Add your first one on the left.</div>
        <?php else: ?>
          <div class="stack-list">
            <?php foreach ($transactions as $t):
              $status = ($t['payment_status'] ?? 'paid') === 'pending' ? 'pending' : 'paid';
              $categoryLabel = $t['category_name'] ?: ($t['category'] ?: 'Uncategorized');
            ?>
            <div class="history-row">
              <div class="list-main">
                <div class="title"><?= h($t['merchant']) ?></div>
                <div class="meta-line">
                  <span class="pill"><?= h($categoryLabel) ?></span>
                  <span><?= h(date('M j, Y', strtotime($t['txn_date']))) ?></span>
                  <?php if (!empty($t['due_date'])): ?>
                    <span>Deadline: <?= h(date('M j, Y', strtotime($t['due_date']))) ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="history-meta">
                <div class="amount"><?= $currency ?><?= number_format((float) $t['amount'], 2) ?></div>
                <span class="status-badge <?= $status ?>"><?= $status === 'pending' ? 'Pending' : 'Paid' ?></span>
                <form method="post" onsubmit="return confirm('Delete this transaction?');">
                  <input type="hidden" name="action" value="delete_transaction">
                  <input type="hidden" name="txn_id" value="<?= (int) $t['id'] ?>">
                  <button type="submit" class="text-button danger">Delete</button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const layers = document.querySelectorAll('[data-parallax]');
      if (!layers.length) return;

      const updateParallax = () => {
        const scrollY = window.scrollY || window.pageYOffset;
        layers.forEach((layer) => {
          const speed = Number(layer.dataset.parallax || 0);
          layer.style.transform = 'translate3d(0, ' + (scrollY * speed) + 'px, 0)';
        });
      };

      updateParallax();
      window.addEventListener('scroll', updateParallax, { passive: true });
    });
  </script>
</body>
</html>