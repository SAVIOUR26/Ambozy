<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$page_title = 'Dashboard';

// ── KPIs ─────────────────────────────────────────────────────────
$month = (int)date('m'); $year = (int)date('Y');

// Revenue: total invoiced this month
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM invoices WHERE MONTH(issue_date)=? AND YEAR(issue_date)=? AND status!='cancelled'");
$stmt->execute([$month,$year]); $monthly_invoiced = (float)$stmt->fetchColumn();

// Collected this month
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments_received WHERE MONTH(payment_date)=? AND YEAR(payment_date)=?");
$stmt->execute([$month,$year]); $monthly_collected = (float)$stmt->fetchColumn();

// Total outstanding (all unpaid invoices)
$outstanding = (float)$pdo->query("SELECT COALESCE(SUM(balance),0) FROM invoices WHERE status IN ('sent','partial','overdue')")->fetchColumn();

// Total owed to suppliers
$payables = (float)$pdo->query("SELECT COALESCE(SUM(balance),0) FROM supplier_bills WHERE status IN ('pending','partial','overdue')")->fetchColumn();

// Expenses this month
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE MONTH(expense_date)=? AND YEAR(expense_date)=?");
$stmt->execute([$month,$year]); $monthly_expenses = (float)$stmt->fetchColumn();

// Payroll this month
$stmt = $pdo->prepare("SELECT COALESCE(total_net,0) FROM payroll_runs WHERE period_month=? AND period_year=? LIMIT 1");
$stmt->execute([$month,$year]); $monthly_payroll = (float)$stmt->fetchColumn();

// Loan total outstanding
$loan_balance = (float)$pdo->query("SELECT COALESCE(SUM(balance_outstanding),0) FROM loans WHERE status='active'")->fetchColumn();

// Active projects
$active_projects = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status IN ('new','in_progress')")->fetchColumn();

// Recent invoices
$recent_invoices = $pdo->query("
  SELECT i.invoice_number, c.name as client_name, i.total, i.balance, i.status, i.due_date
  FROM invoices i JOIN clients c ON c.id=i.client_id
  WHERE i.status != 'cancelled'
  ORDER BY i.created_at DESC LIMIT 8
")->fetchAll();

// Recent payments received
$recent_payments = $pdo->query("
  SELECT pr.amount, pr.payment_date, pr.method, c.name as client_name, i.invoice_number
  FROM payments_received pr
  JOIN clients c ON c.id=pr.client_id
  JOIN invoices i ON i.id=pr.invoice_id
  ORDER BY pr.created_at DESC LIMIT 6
")->fetchAll();

// Overdue invoices count
$overdue_count = (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE status='overdue' OR (status IN('sent','partial') AND due_date < CURDATE())")->fetchColumn();
// Update overdue status
if ($overdue_count > 0) {
    $pdo->exec("UPDATE invoices SET status='overdue' WHERE status IN('sent','partial') AND due_date < CURDATE()");
}

// Supplier bills overdue
$pdo->exec("UPDATE supplier_bills SET status='overdue' WHERE status IN('pending','partial') AND due_date < CURDATE()");

include __DIR__ . '/includes/header.php';
?>

<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-label">Invoiced This Month</div>
    <div class="kpi-value"><?= fmt_ugx($monthly_invoiced) ?></div>
    <div class="kpi-sub"><?= date('F Y') ?></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Collected This Month</div>
    <div class="kpi-value green"><?= fmt_ugx($monthly_collected) ?></div>
    <div class="kpi-sub">Cash in bank</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Client Receivables</div>
    <div class="kpi-value orange"><?= fmt_ugx($outstanding) ?></div>
    <div class="kpi-sub">Total outstanding from clients</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Supplier Payables</div>
    <div class="kpi-value red"><?= fmt_ugx($payables) ?></div>
    <div class="kpi-sub">Owed to suppliers on credit</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Expenses This Month</div>
    <div class="kpi-value"><?= fmt_ugx($monthly_expenses) ?></div>
    <div class="kpi-sub">Fuel, facilitation, etc.</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Payroll (Net) This Month</div>
    <div class="kpi-value"><?= fmt_ugx($monthly_payroll) ?></div>
    <div class="kpi-sub">Staff net salaries</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Loan Obligations</div>
    <div class="kpi-value red"><?= fmt_ugx($loan_balance) ?></div>
    <div class="kpi-sub">Active loan balances</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Active Projects</div>
    <div class="kpi-value"><?= $active_projects ?></div>
    <div class="kpi-sub">New + In Progress</div>
  </div>
</div>

<?php if ($overdue_count > 0): ?>
<div class="alert alert-warning">
  <strong><?= $overdue_count ?> invoice(s)</strong> are overdue.
  <a href="<?= admin_url('invoices.php?status=overdue') ?>" style="color:inherit;text-decoration:underline;margin-left:.5rem">View →</a>
</div>
<?php endif; ?>

<div class="grid-2">
  <!-- Recent Invoices -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Recent Invoices</span>
      <a href="<?= admin_url('invoices.php') ?>" class="btn-ghost btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th><th>Client</th><th class="num">Total</th><th class="num">Balance</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_invoices as $inv): ?>
          <tr>
            <td><a href="<?= admin_url('invoice-view.php?id=' . '') ?>" style="color:var(--orange)"><?= h($inv['invoice_number']) ?></a></td>
            <td><?= h($inv['client_name']) ?></td>
            <td class="num"><?= fmt_ugx($inv['total']) ?></td>
            <td class="num <?= $inv['balance']>0?'text-orange':'' ?>"><?= fmt_ugx($inv['balance']) ?></td>
            <td><?= status_badge($inv['status']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$recent_invoices): ?><tr><td colspan="5" class="empty-state">No invoices yet</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Payments -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Recent Payments Received</span>
    </div>
    <div class="card-body">
      <?php if ($recent_payments): ?>
      <div class="payment-list">
        <?php foreach ($recent_payments as $p): ?>
        <div class="payment-row">
          <div>
            <div style="font-size:.85rem;font-weight:600"><?= h($p['client_name']) ?></div>
            <div class="payment-row-meta"><?= h($p['invoice_number']) ?> · <?= ucfirst(str_replace('_',' ',$p['method'])) ?> · <?= fmt_date($p['payment_date']) ?></div>
          </div>
          <div class="payment-row-amount"><?= fmt_ugx($p['amount']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">No payments recorded yet</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
