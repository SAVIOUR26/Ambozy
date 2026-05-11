<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$id  = (int)($_GET['id'] ?? 0);
$run = $pdo->prepare("SELECT * FROM payroll_runs WHERE id=?"); $run->execute([$id]); $run = $run->fetch();
if (!$run) { header('Location: ' . admin_url('payroll.php')); exit; }

$items = $pdo->prepare("SELECT pi.*, e.full_name, e.position, e.bank_name, e.bank_account, e.nssf_number FROM payroll_items pi JOIN employees e ON e.id=pi.employee_id WHERE pi.payroll_run_id=? ORDER BY e.full_name");
$items->execute([$id]); $items = $items->fetchAll();

$page_title = 'Payroll — ' . month_name($run['period_month']) . ' ' . $run['period_year'];
include __DIR__ . '/includes/header.php';
?>

<div class="page-actions">
  <a href="<?= admin_url('payroll.php') ?>" class="btn-ghost">← Payroll</a>
  <div class="page-actions-right">
    <?= status_badge($run['status']) ?>
    <?php if($run['status']==='draft'): ?>
    <form method="POST" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="run_id" value="<?= $id ?>"><button type="submit" class="btn btn-outline">Approve</button></form>
    <?php elseif($run['status']==='approved'): ?>
    <form method="POST" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="mark_paid"><input type="hidden" name="run_id" value="<?= $id ?>"><button type="submit" class="btn btn-primary">Mark as Paid</button></form>
    <?php endif; ?>
  </div>
</div>

<div class="kpi-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:1.25rem">
  <div class="kpi-card"><div class="kpi-label">Gross Payroll</div><div class="kpi-value"><?= fmt_ugx($run['total_gross']) ?></div></div>
  <div class="kpi-card"><div class="kpi-label">Total PAYE</div><div class="kpi-value red"><?= fmt_ugx($run['total_paye']) ?></div></div>
  <div class="kpi-card"><div class="kpi-label">NSSF Employee (5%)</div><div class="kpi-value red"><?= fmt_ugx($run['total_nssf_employee']) ?></div></div>
  <div class="kpi-card"><div class="kpi-label">NSSF Employer (10%)</div><div class="kpi-value orange"><?= fmt_ugx($run['total_nssf_employer']) ?></div></div>
  <div class="kpi-card"><div class="kpi-label">Net Pay to Staff</div><div class="kpi-value green"><?= fmt_ugx($run['total_net']) ?></div></div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Employee Payslips — <?= month_name($run['period_month']) ?> <?= $run['period_year'] ?></span></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Employee</th><th>Position</th>
          <th class="num">Gross</th><th class="num">PAYE</th>
          <th class="num">NSSF (Emp)</th><th class="num">NSSF (Er)</th>
          <th class="num">Other Deductions</th><th class="num">Net Pay</th>
          <th>Bank</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($items as $item): ?>
        <tr>
          <td style="font-weight:600"><?= h($item['full_name']) ?></td>
          <td class="muted"><?= h($item['position']?:'—') ?></td>
          <td class="num"><?= fmt_ugx($item['gross_salary']) ?></td>
          <td class="num text-red"><?= fmt_ugx($item['paye']) ?></td>
          <td class="num text-red"><?= fmt_ugx($item['nssf_employee']) ?></td>
          <td class="num text-orange"><?= fmt_ugx($item['nssf_employer']) ?></td>
          <td class="num"><?= fmt_ugx($item['other_deductions']) ?></td>
          <td class="num text-green" style="font-weight:700"><?= fmt_ugx($item['net_pay']) ?></td>
          <td class="muted" style="font-size:.75rem"><?= h($item['bank_name']?:'—') ?><br><?= h($item['bank_account']?:'') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background:rgba(255,255,255,.02);font-weight:700">
          <td colspan="2" style="padding:.75rem 1rem">TOTALS</td>
          <td class="num"><?= fmt_ugx($run['total_gross']) ?></td>
          <td class="num text-red"><?= fmt_ugx($run['total_paye']) ?></td>
          <td class="num text-red"><?= fmt_ugx($run['total_nssf_employee']) ?></td>
          <td class="num text-orange"><?= fmt_ugx($run['total_nssf_employer']) ?></td>
          <td class="num">—</td>
          <td class="num text-green"><?= fmt_ugx($run['total_net']) ?></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<div class="alert alert-info mt-2">
  <strong>Statutory reminder:</strong> After approving payroll, record PAYE remittance to URA and NSSF contribution (<?= fmt_ugx($run['total_nssf_employee'] + $run['total_nssf_employer']) ?> total) on the <a href="<?= admin_url('statutory.php') ?>" style="color:inherit;text-decoration:underline">Statutory Payments</a> page.
</div>

<?php
// Re-use approve/mark_paid POST handler from payroll.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action']??'',['approve','mark_paid']) && csrf_verify()) {
    $status = ($_POST['action']==='approve') ? 'approved' : 'paid';
    $pdo->prepare("UPDATE payroll_runs SET status=? WHERE id=?")->execute([$status,$id]);
    flash("Payroll " . ($status==='paid' ? 'marked as paid.' : 'approved.'));
    header('Location: ' . admin_url("payroll-view.php?id=$id")); exit;
}
include __DIR__ . '/includes/footer.php'; ?>
