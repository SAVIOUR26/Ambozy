<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$page_title = 'Statutory Payments (URA / NSSF)';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add_statutory' && csrf_verify()) {
    $type_id   = (int)($_POST['statutory_type_id'] ?? 0);
    $period_m  = (int)($_POST['period_month'] ?? 0) ?: null;
    $period_y  = (int)($_POST['period_year']  ?? date('Y'));
    $amount    = (float)($_POST['amount'] ?? 0);
    $pay_date  = $_POST['payment_date'] ?? date('Y-m-d');
    $reference = trim($_POST['reference'] ?? '');
    $notes     = trim($_POST['notes'] ?? '');

    if ($type_id && $amount > 0) {
        $pdo->prepare("INSERT INTO statutory_payments (statutory_type_id,period_month,period_year,amount,payment_date,reference,notes,recorded_by) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$type_id,$period_m,$period_y,$amount,$pay_date,$reference,$notes,$_SESSION['admin_id']]);
        flash('Statutory payment recorded.');
    } else {
        flash('Please fill in all required fields.', 'error');
    }
    header('Location: ' . admin_url('statutory.php')); exit;
}

$year_filter = (int)($_GET['year'] ?? date('Y'));
$types = $pdo->query("SELECT * FROM statutory_types ORDER BY name")->fetchAll();

$payments = $pdo->prepare("
  SELECT sp.*, st.name as type_name, st.authority
  FROM statutory_payments sp JOIN statutory_types st ON st.id=sp.statutory_type_id
  WHERE sp.period_year=?
  ORDER BY sp.payment_date DESC
");
$payments->execute([$year_filter]); $payments = $payments->fetchAll();

// Totals by type
$by_type = $pdo->prepare("
  SELECT st.name, st.authority, COALESCE(SUM(sp.amount),0) as total
  FROM statutory_payments sp JOIN statutory_types st ON st.id=sp.statutory_type_id
  WHERE sp.period_year=? GROUP BY sp.statutory_type_id ORDER BY total DESC
");
$by_type->execute([$year_filter]); $by_type = $by_type->fetchAll();
$year_total = array_sum(array_column($by_type,'total'));

// Payroll runs for linking
$payroll_runs = $pdo->query("SELECT id,period_month,period_year FROM payroll_runs ORDER BY period_year DESC,period_month DESC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-actions">
  <div class="page-actions-left">
    <form method="GET">
      <select class="form-control" name="year" onchange="this.form.submit()" style="padding:.4rem .75rem;font-size:.8rem">
        <?php for($y=date('Y');$y>=date('Y')-4;$y--): ?><option value="<?= $y ?>" <?= $y===$year_filter?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
      </select>
    </form>
  </div>
  <button class="btn btn-primary" data-modal-open="modalAddStatutory">+ Record Payment</button>
</div>

<div class="kpi-grid" style="grid-template-columns:repeat(<?= min(4,count($by_type)+1) ?>,1fr);margin-bottom:1.25rem">
  <div class="kpi-card"><div class="kpi-label">Total Statutory <?= $year_filter ?></div><div class="kpi-value red"><?= fmt_ugx($year_total) ?></div></div>
  <?php foreach($by_type as $bt): ?>
  <div class="kpi-card"><div class="kpi-label"><?= h($bt['name']) ?></div><div class="kpi-value"><?= fmt_ugx($bt['total']) ?></div><div class="kpi-sub"><?= h($bt['authority']?:'') ?></div></div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Statutory Payments — <?= $year_filter ?></span></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Type</th><th>Authority</th><th>Period</th><th>Payment Date</th><th>Reference</th><th class="num">Amount</th></tr></thead>
      <tbody>
        <?php foreach($payments as $p): ?>
        <tr>
          <td style="font-weight:600"><?= h($p['type_name']) ?></td>
          <td class="muted"><?= h($p['authority']?:'—') ?></td>
          <td><?= $p['period_month'] ? month_name((int)$p['period_month']).' '.$p['period_year'] : $p['period_year'] ?></td>
          <td><?= fmt_date($p['payment_date']) ?></td>
          <td><?= h($p['reference']?:'—') ?></td>
          <td class="num text-red"><?= fmt_ugx($p['amount']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$payments): ?><tr><td colspan="6" class="empty-state">No statutory payments for <?= $year_filter ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Statutory Modal -->
<div class="modal-overlay" id="modalAddStatutory">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Record Statutory Payment</span><button class="modal-close" data-modal-close="modalAddStatutory">✕</button></div>
    <div class="modal-body">
      <div class="alert alert-info mb-2" style="font-size:.8rem">Record URA PAYE, VAT, NSSF, Local Service Tax or other statutory obligations.</div>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_statutory">
        <div class="form-grid form-grid-2">
          <div class="form-group full"><label class="form-label">Type *</label>
            <select class="form-control" name="statutory_type_id" required>
              <option value="">— Select type —</option>
              <?php foreach($types as $t): ?><option value="<?= $t['id'] ?>"><?= h($t['name']) ?> — <?= h($t['authority']?:'') ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Period Month</label>
            <select class="form-control" name="period_month">
              <option value="">— (Annual/Ad hoc) —</option>
              <?php for($i=1;$i<=12;$i++): ?><option value="<?= $i ?>" <?= $i==(int)date('n')?'selected':'' ?>><?= month_name($i) ?></option><?php endfor; ?>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Year *</label>
            <select class="form-control" name="period_year">
              <?php for($y=date('Y');$y>=date('Y')-4;$y--): ?><option value="<?= $y ?>"><?= $y ?></option><?php endfor; ?>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Amount (UGX) *</label><input class="form-control" type="number" name="amount" min="1" step="1" required></div>
          <div class="form-group"><label class="form-label">Payment Date *</label><input class="form-control" type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
          <div class="form-group full"><label class="form-label">URA / NSSF Reference No.</label><input class="form-control" type="text" name="reference" placeholder="e.g. URA acknowledgement number"></div>
          <div class="form-group full"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
        </div>
        <div class="mt-2"><button type="submit" class="btn btn-primary">Save</button></div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
