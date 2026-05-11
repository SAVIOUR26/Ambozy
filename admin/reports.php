<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$page_title = 'Financial Reports';

$year  = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? 0); // 0 = full year

// ── Revenue ───────────────────────────────────────────────────────
$rev_args   = [$year];
$rev_where  = $month ? " AND MONTH(payment_date)=?" : '';
if ($month) $rev_args[] = $month;

$revenue = (float)$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments_received WHERE YEAR(payment_date)=? $rev_where")->execute($rev_args) ? $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments_received WHERE YEAR(payment_date)=? $rev_where")->execute($rev_args) : 0;
// safe approach
$rev_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments_received WHERE YEAR(payment_date)=?" . ($month?" AND MONTH(payment_date)=?":''));
$rev_stmt->execute($month ? [$year,$month] : [$year]);
$revenue = (float)$rev_stmt->fetchColumn();

// ── Supplier payments (COGS / purchases) ─────────────────────────
$sup_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments_to_suppliers WHERE YEAR(payment_date)=?" . ($month?" AND MONTH(payment_date)=?":''));
$sup_stmt->execute($month ? [$year,$month] : [$year]);
$supplier_payments = (float)$sup_stmt->fetchColumn();

// ── Expenses ──────────────────────────────────────────────────────
$exp_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE YEAR(expense_date)=?" . ($month?" AND MONTH(expense_date)=?":''));
$exp_stmt->execute($month ? [$year,$month] : [$year]);
$expenses = (float)$exp_stmt->fetchColumn();

// ── Payroll (net paid) ────────────────────────────────────────────
$pay_stmt = $pdo->prepare("SELECT COALESCE(SUM(total_net),0) FROM payroll_runs WHERE period_year=? AND status='paid'" . ($month?" AND period_month=?":''));
$pay_stmt->execute($month ? [$year,$month] : [$year]);
$payroll_net = (float)$pay_stmt->fetchColumn();

// ── Statutory ─────────────────────────────────────────────────────
$stat_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM statutory_payments WHERE period_year=?" . ($month?" AND period_month=?":''));
$stat_stmt->execute($month ? [$year,$month] : [$year]);
$statutory = (float)$stat_stmt->fetchColumn();

// ── Loan repayments (principal only) ─────────────────────────────
$loan_stmt = $pdo->prepare("SELECT COALESCE(SUM(principal_paid),0) FROM loan_repayments WHERE YEAR(payment_date)=?" . ($month?" AND MONTH(payment_date)=?":''));
$loan_stmt->execute($month ? [$year,$month] : [$year]);
$loan_principal = (float)$loan_stmt->fetchColumn();
$loan_int_stmt = $pdo->prepare("SELECT COALESCE(SUM(interest_paid),0) FROM loan_repayments WHERE YEAR(payment_date)=?" . ($month?" AND MONTH(payment_date)=?":''));
$loan_int_stmt->execute($month ? [$year,$month] : [$year]);
$loan_interest = (float)$loan_int_stmt->fetchColumn();

$total_outgoings = $supplier_payments + $expenses + $payroll_net + $statutory + $loan_interest;
$net_profit      = $revenue - $total_outgoings;

// ── Monthly revenue trend ─────────────────────────────────────────
$monthly_trend = $pdo->prepare("
  SELECT MONTH(payment_date) as mo, COALESCE(SUM(amount),0) as revenue
  FROM payments_received WHERE YEAR(payment_date)=?
  GROUP BY MONTH(payment_date) ORDER BY mo
");
$monthly_trend->execute([$year]); $monthly_trend = $monthly_trend->fetchAll(PDO::FETCH_KEY_PAIR);

// ── Receivables aging ─────────────────────────────────────────────
$aging = $pdo->query("
  SELECT
    SUM(CASE WHEN due_date >= CURDATE() THEN balance ELSE 0 END) as current_due,
    SUM(CASE WHEN due_date < CURDATE() AND DATEDIFF(CURDATE(),due_date)<=30 THEN balance ELSE 0 END) as d30,
    SUM(CASE WHEN DATEDIFF(CURDATE(),due_date) BETWEEN 31 AND 60 THEN balance ELSE 0 END) as d60,
    SUM(CASE WHEN DATEDIFF(CURDATE(),due_date) > 60 THEN balance ELSE 0 END) as d60p
  FROM invoices WHERE status IN('sent','partial','overdue')
")->fetch();

// ── Top clients by revenue ────────────────────────────────────────
$top_clients = $pdo->prepare("
  SELECT c.name, COALESCE(SUM(pr.amount),0) as paid
  FROM payments_received pr JOIN clients c ON c.id=pr.client_id
  WHERE YEAR(pr.payment_date)=?
  GROUP BY pr.client_id ORDER BY paid DESC LIMIT 5
");
$top_clients->execute([$year]); $top_clients = $top_clients->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Year/Month filter -->
<div class="flex mb-2" style="gap:.75rem;flex-wrap:wrap">
  <form method="GET" class="flex" style="gap:.5rem">
    <select class="form-control" name="year" style="padding:.4rem .75rem;font-size:.8rem">
      <?php for($y=date('Y');$y>=date('Y')-4;$y--): ?><option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
    </select>
    <select class="form-control" name="month" style="padding:.4rem .75rem;font-size:.8rem">
      <option value="0" <?= !$month?'selected':'' ?>>Full Year</option>
      <?php for($i=1;$i<=12;$i++): ?><option value="<?= $i ?>" <?= $i===$month?'selected':'' ?>><?= month_name($i) ?></option><?php endfor; ?>
    </select>
    <button type="submit" class="btn btn-outline btn-sm">Apply</button>
  </form>
  <span class="text-muted" style="font-size:.82rem;align-self:center">Showing: <?= $month ? month_name($month).' ' : '' ?><?= $year ?></span>
</div>

<!-- P&L Summary -->
<div class="card mb-2">
  <div class="card-header"><span class="card-title">Profit & Loss Summary</span></div>
  <div class="card-body">
    <table class="totals-table" style="max-width:500px">
      <tr><td style="color:var(--text-muted);font-size:.8rem;padding-bottom:.5rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em" colspan="2">INCOME</td></tr>
      <tr><td>Revenue (Cash Collected)</td><td class="text-right text-green" style="font-weight:700"><?= fmt_ugx($revenue) ?></td></tr>
      <tr><td style="color:var(--text-muted);font-size:.8rem;padding:.75rem 0 .5rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em" colspan="2">EXPENDITURE</td></tr>
      <tr><td>Supplier Payments (Materials / COGS)</td><td class="text-right"><?= fmt_ugx($supplier_payments) ?></td></tr>
      <tr><td>Operating Expenses</td><td class="text-right"><?= fmt_ugx($expenses) ?></td></tr>
      <tr><td>Payroll — Net Staff Salaries</td><td class="text-right"><?= fmt_ugx($payroll_net) ?></td></tr>
      <tr><td>Statutory (PAYE, NSSF, VAT, etc.)</td><td class="text-right"><?= fmt_ugx($statutory) ?></td></tr>
      <tr><td>Loan Interest Paid</td><td class="text-right"><?= fmt_ugx($loan_interest) ?></td></tr>
      <tr style="border-top:1px solid var(--card-border)"><td style="padding-top:.6rem;color:var(--text-muted)">Total Expenditure</td><td class="text-right text-red" style="padding-top:.6rem"><?= fmt_ugx($total_outgoings) ?></td></tr>
      <tr class="total-row">
        <td class="total-label">Net <?= $net_profit>=0?'Profit':'Loss' ?></td>
        <td class="text-right <?= $net_profit>=0?'total-val text-green':'text-red' ?>" style="font-family:var(--font-display);font-size:1.2rem;font-weight:800"><?= fmt_ugx(abs($net_profit)) ?></td>
      </tr>
    </table>
  </div>
</div>

<div class="grid-2">
  <!-- Monthly Revenue Trend -->
  <div class="card">
    <div class="card-header"><span class="card-title">Monthly Revenue — <?= $year ?></span></div>
    <div class="card-body">
      <?php for($i=1;$i<=12;$i++):
        $mo_rev = (float)($monthly_trend[$i] ?? 0);
        $max_rev = max(1, max(array_values($monthly_trend) ?: [1]));
        $pct = $mo_rev > 0 ? min(100, ($mo_rev / $max_rev) * 100) : 0;
      ?>
      <div class="flex" style="justify-content:space-between;margin-bottom:.5rem;gap:.75rem">
        <span style="font-size:.75rem;color:var(--text-muted);width:30px;flex-shrink:0"><?= substr(month_name($i),0,3) ?></span>
        <div style="flex:1"><div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div></div>
        <span style="font-size:.75rem;width:130px;text-align:right"><?= $mo_rev>0?fmt_ugx($mo_rev):'—' ?></span>
      </div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- Receivables Aging -->
  <div class="card">
    <div class="card-header"><span class="card-title">Receivables Aging</span></div>
    <div class="card-body">
      <table class="totals-table">
        <tr><td>Current (not yet due)</td><td class="text-right text-green"><?= fmt_ugx($aging['current_due']) ?></td></tr>
        <tr><td>1–30 days overdue</td><td class="text-right text-yellow"><?= fmt_ugx($aging['d30']) ?></td></tr>
        <tr><td>31–60 days overdue</td><td class="text-right text-orange"><?= fmt_ugx($aging['d60']) ?></td></tr>
        <tr><td>60+ days overdue</td><td class="text-right text-red"><?= fmt_ugx($aging['d60p']) ?></td></tr>
        <tr class="total-row"><td class="total-label">Total Outstanding</td><td class="text-right total-val"><?= fmt_ugx($aging['current_due']+$aging['d30']+$aging['d60']+$aging['d60p']) ?></td></tr>
      </table>
    </div>

    <?php if($top_clients): ?>
    <div class="divider" style="margin:0"></div>
    <div class="card-header"><span class="card-title">Top Clients by Revenue — <?= $year ?></span></div>
    <div class="card-body">
      <?php foreach($top_clients as $tc): ?>
      <div class="flex" style="justify-content:space-between;margin-bottom:.5rem">
        <span style="font-size:.84rem"><?= h($tc['name']) ?></span>
        <span style="font-size:.84rem;font-weight:700;color:var(--green)"><?= fmt_ugx($tc['paid']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
