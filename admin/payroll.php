<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$page_title = 'Payroll';

// ── Generate payroll run ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'generate_run' && csrf_verify()) {
    $period_month = (int)($_POST['period_month'] ?? date('n'));
    $period_year  = (int)($_POST['period_year']  ?? date('Y'));
    $run_date     = $_POST['run_date'] ?? date('Y-m-d');

    // Check not already generated
    $exists = $pdo->prepare("SELECT id FROM payroll_runs WHERE period_month=? AND period_year=?");
    $exists->execute([$period_month,$period_year]);
    if ($exists->fetch()) {
        flash("Payroll for " . month_name($period_month) . " $period_year already exists.", 'error');
        header('Location: ' . admin_url('payroll.php')); exit;
    }

    $employees = $pdo->query("SELECT * FROM employees WHERE active=1")->fetchAll();
    if (!$employees) { flash('No active employees.', 'error'); header('Location: ' . admin_url('payroll.php')); exit; }

    $total_gross = $total_paye = $total_nssf_emp = $total_nssf_er = $total_net = 0;

    // Insert run first
    $pdo->prepare("INSERT INTO payroll_runs (period_month,period_year,run_date,status) VALUES (?,?,?,'draft')")
        ->execute([$period_month,$period_year,$run_date]);
    $run_id = (int)$pdo->lastInsertId();

    $item_stmt = $pdo->prepare("INSERT INTO payroll_items (payroll_run_id,employee_id,gross_salary,paye,nssf_employee,nssf_employer,other_deductions,net_pay) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($employees as $emp) {
        $gross      = (float)$emp['gross_salary'];
        $paye       = calculate_paye($gross);
        $nssf_emp   = calculate_nssf_employee($gross);
        $nssf_er    = calculate_nssf_employer($gross);
        $net        = $gross - $paye - $nssf_emp;

        $item_stmt->execute([$run_id,$emp['id'],$gross,$paye,$nssf_emp,$nssf_er,0,$net]);

        $total_gross    += $gross;
        $total_paye     += $paye;
        $total_nssf_emp += $nssf_emp;
        $total_nssf_er  += $nssf_er;
        $total_net      += $net;
    }

    $pdo->prepare("UPDATE payroll_runs SET total_gross=?,total_paye=?,total_nssf_employee=?,total_nssf_employer=?,total_net=? WHERE id=?")
        ->execute([$total_gross,$total_paye,$total_nssf_emp,$total_nssf_er,$total_net,$run_id]);

    flash("Payroll generated for " . month_name($period_month) . " $period_year — " . count($employees) . " employees.");
    header('Location: ' . admin_url("payroll-view.php?id=$run_id")); exit;
}

// ── Approve / Mark Paid ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action']??'',['approve','mark_paid']) && csrf_verify()) {
    $run_id = (int)($_POST['run_id']??0);
    $status = ($_POST['action']==='approve') ? 'approved' : 'paid';
    $pdo->prepare("UPDATE payroll_runs SET status=? WHERE id=?")->execute([$status,$run_id]);
    flash("Payroll " . ($status==='paid' ? 'marked as paid.' : 'approved.'));
    header('Location: ' . admin_url("payroll-view.php?id=$run_id")); exit;
}

$runs = $pdo->query("SELECT * FROM payroll_runs ORDER BY period_year DESC, period_month DESC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-actions">
  <div></div>
  <button class="btn btn-primary" data-modal-open="modalGenerate">+ Generate Payroll Run</button>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Period</th><th>Run Date</th><th class="num">Gross</th><th class="num">PAYE</th><th class="num">NSSF (Emp)</th><th class="num">NSSF (Er)</th><th class="num">Net Pay</th><th>Status</th><th>View</th></tr></thead>
      <tbody>
        <?php foreach($runs as $r): ?>
        <tr>
          <td style="font-weight:700"><?= month_name($r['period_month']) ?> <?= $r['period_year'] ?></td>
          <td><?= fmt_date($r['run_date']) ?></td>
          <td class="num"><?= fmt_ugx($r['total_gross']) ?></td>
          <td class="num text-red"><?= fmt_ugx($r['total_paye']) ?></td>
          <td class="num text-red"><?= fmt_ugx($r['total_nssf_employee']) ?></td>
          <td class="num text-orange"><?= fmt_ugx($r['total_nssf_employer']) ?></td>
          <td class="num text-green"><?= fmt_ugx($r['total_net']) ?></td>
          <td><?= status_badge($r['status']) ?></td>
          <td><a href="<?= admin_url('payroll-view.php?id='.$r['id']) ?>" class="btn btn-sm btn-outline">View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$runs): ?><tr><td colspan="9" class="empty-state">No payroll runs yet</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- PAYE / NSSF Calculator Tool -->
<div class="card mt-2">
  <div class="card-header"><span class="card-title">Uganda PAYE / NSSF Calculator</span></div>
  <div class="card-body">
    <p class="text-muted mb-2" style="font-size:.82rem">Enter gross monthly salary to preview deductions (FY 2024/25 rates)</p>
    <div class="form-grid form-grid-3">
      <div class="form-group"><label class="form-label">Gross Salary (UGX)</label><input class="form-control" type="number" id="calcGross" placeholder="e.g. 800000" step="1000"></div>
      <div class="form-group"><label class="form-label">PAYE</label><input class="form-control" type="text" id="calcPAYE" readonly tabindex="-1"></div>
      <div class="form-group"><label class="form-label">NSSF Employee (5%)</label><input class="form-control" type="text" id="calcNSSFEmp" readonly tabindex="-1"></div>
      <div class="form-group"><label class="form-label">NSSF Employer (10%)</label><input class="form-control" type="text" id="calcNSSFEr" readonly tabindex="-1"></div>
      <div class="form-group"><label class="form-label">Net Pay</label><input class="form-control" type="text" id="calcNet" readonly tabindex="-1" style="color:var(--green);font-weight:700"></div>
    </div>
    <p class="form-hint mt-1">PAYE thresholds: 0% ≤235k | 10% 235k–335k | 20% 335k–410k | 30% &gt;410k (monthly, UGX)</p>
  </div>
</div>

<!-- Generate Payroll Modal -->
<div class="modal-overlay" id="modalGenerate">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Generate Payroll Run</span><button class="modal-close" data-modal-close="modalGenerate">✕</button></div>
    <div class="modal-body">
      <p class="text-muted mb-2" style="font-size:.82rem">This will auto-calculate PAYE and NSSF for all active employees at their current gross salaries.</p>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="generate_run">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label class="form-label">Month</label>
            <select class="form-control" name="period_month">
              <?php for($i=1;$i<=12;$i++): ?><option value="<?= $i ?>" <?= $i==(int)date('n')?'selected':'' ?>><?= month_name($i) ?></option><?php endfor; ?>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Year</label>
            <select class="form-control" name="period_year">
              <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?><option value="<?= $y ?>"><?= $y ?></option><?php endfor; ?>
            </select>
          </div>
          <div class="form-group full"><label class="form-label">Run Date</label><input class="form-control" type="date" name="run_date" value="<?= date('Y-m-d') ?>"></div>
        </div>
        <div class="mt-2"><button type="submit" class="btn btn-primary">Generate</button></div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
