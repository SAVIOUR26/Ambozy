<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$page_title = 'Loans';

// ── Add loan ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add_loan' && csrf_verify()) {
    $principal = (float)($_POST['principal']??0);
    $pdo->prepare("INSERT INTO loans (lender_name,loan_type,principal,interest_rate,term_months,disbursement_date,maturity_date,monthly_installment,balance_outstanding,purpose,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            trim($_POST['lender_name']??''),
            $_POST['loan_type']??'bank',
            $principal,
            (float)($_POST['interest_rate']??0),
            (int)($_POST['term_months']??12),
            $_POST['disbursement_date']??date('Y-m-d'),
            $_POST['maturity_date']??null,
            (float)($_POST['monthly_installment']??0),
            $principal,  // initial balance = full principal
            trim($_POST['purpose']??''),
            trim($_POST['notes']??'')
        ]);
    flash('Loan recorded.');
    header('Location: ' . admin_url('loans.php')); exit;
}

// ── Record repayment ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add_repayment' && csrf_verify()) {
    $loan_id = (int)($_POST['loan_id']??0);
    $principal_paid = (float)($_POST['principal_paid']??0);
    $interest_paid  = (float)($_POST['interest_paid']??0);
    $total_paid     = $principal_paid + $interest_paid;
    if ($loan_id && $total_paid > 0) {
        $pdo->prepare("INSERT INTO loan_repayments (loan_id,payment_date,principal_paid,interest_paid,total_paid,method,reference,recorded_by) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$loan_id,$_POST['payment_date']??date('Y-m-d'),$principal_paid,$interest_paid,$total_paid,$_POST['method']??'bank_transfer',trim($_POST['reference']??''),$_SESSION['admin_id']]);
        sync_loan_balance($loan_id, $pdo);
        flash('Repayment recorded.');
    }
    header('Location: ' . admin_url('loans.php')); exit;
}

$loans = $pdo->query("
  SELECT l.*,
    (SELECT COALESCE(SUM(total_paid),0) FROM loan_repayments WHERE loan_id=l.id) as repaid,
    (SELECT COUNT(*) FROM loan_repayments WHERE loan_id=l.id) as payment_count
  FROM loans l ORDER BY l.disbursement_date DESC
")->fetchAll();

$summary = $pdo->query("SELECT COALESCE(SUM(principal),0) t, COALESCE(SUM(balance_outstanding),0) b FROM loans WHERE status='active'")->fetch();

include __DIR__ . '/includes/header.php';
?>

<div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.25rem">
  <div class="kpi-card"><div class="kpi-label">Total Active Loan Balances</div><div class="kpi-value red"><?= fmt_ugx($summary['b']) ?></div><div class="kpi-sub">Outstanding principal</div></div>
  <div class="kpi-card"><div class="kpi-label">Original Principal (Active)</div><div class="kpi-value"><?= fmt_ugx($summary['t']) ?></div></div>
  <div class="kpi-card"><div class="kpi-label">Active Loans</div><div class="kpi-value"><?= count(array_filter($loans,fn($l)=>$l['status']==='active')) ?></div></div>
</div>

<div class="page-actions">
  <div></div>
  <button class="btn btn-primary" data-modal-open="modalAddLoan">+ Add Loan</button>
</div>

<?php foreach($loans as $loan): ?>
<?php $pct = $loan['principal'] > 0 ? min(100, ($loan['total_repaid']/$loan['principal'])*100) : 0; ?>
<div class="card mb-2">
  <div class="card-header">
    <div>
      <span class="card-title"><?= h($loan['lender_name']) ?></span>
      <span class="muted" style="margin-left:.75rem;font-size:.8rem"><?= ucfirst($loan['loan_type']) ?> Loan</span>
      <?php if($loan['purpose']): ?><span class="muted" style="margin-left:.5rem;font-size:.75rem">· <?= h($loan['purpose']) ?></span><?php endif; ?>
    </div>
    <div class="flex">
      <?= status_badge($loan['status']) ?>
      <?php if($loan['status']==='active'): ?>
      <button class="btn btn-sm btn-outline" onclick="openRepayModal(<?= $loan['id'] ?>,<?= $loan['balance_outstanding'] ?>,<?= $loan['monthly_installment'] ?>)">Record Repayment</button>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-body">
    <div class="grid-2">
      <div>
        <div class="flex" style="justify-content:space-between;margin-bottom:.65rem">
          <span style="font-size:.82rem;color:var(--text-muted)">Principal: <strong style="color:var(--text)"><?= fmt_ugx($loan['principal']) ?></strong></span>
          <span style="font-size:.82rem;color:var(--text-muted)">Rate: <strong style="color:var(--text)"><?= $loan['interest_rate'] ?>% p.a.</strong></span>
          <span style="font-size:.82rem;color:var(--text-muted)">Term: <strong style="color:var(--text)"><?= $loan['term_months'] ?> months</strong></span>
        </div>
        <div class="flex" style="justify-content:space-between;margin-bottom:.5rem">
          <span style="font-size:.8rem;color:var(--text-muted)">Disbursed: <?= fmt_date($loan['disbursement_date']) ?></span>
          <?php if($loan['maturity_date']): ?><span style="font-size:.8rem;color:var(--text-muted)">Matures: <?= fmt_date($loan['maturity_date']) ?></span><?php endif; ?>
          <?php if($loan['monthly_installment']>0): ?><span style="font-size:.8rem;color:var(--text-muted)">Installment: <strong><?= fmt_ugx($loan['monthly_installment']) ?>/mo</strong></span><?php endif; ?>
        </div>
        <div class="progress-bar mt-1"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
        <div class="flex mt-1" style="justify-content:space-between">
          <span style="font-size:.75rem;color:var(--green)">Repaid: <?= fmt_ugx($loan['total_repaid']) ?></span>
          <span style="font-size:.75rem;font-weight:700;color:var(--red)">Balance: <?= fmt_ugx($loan['balance_outstanding']) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php if(!$loans): ?><div class="card"><div class="empty-state">No loans recorded</div></div><?php endif; ?>

<!-- Add Loan Modal -->
<div class="modal-overlay" id="modalAddLoan">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Record New Loan</span><button class="modal-close" data-modal-close="modalAddLoan">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_loan">
        <div class="form-grid form-grid-2">
          <div class="form-group full"><label class="form-label">Lender Name *</label><input class="form-control" type="text" name="lender_name" placeholder="e.g. Centenary Bank" required></div>
          <div class="form-group"><label class="form-label">Loan Type</label>
            <select class="form-control" name="loan_type"><option value="bank">Bank</option><option value="microfinance">Microfinance</option><option value="sacco">SACCO</option><option value="personal">Personal</option><option value="other">Other</option></select>
          </div>
          <div class="form-group"><label class="form-label">Principal (UGX) *</label><input class="form-control" type="number" name="principal" min="1" step="1000" required></div>
          <div class="form-group"><label class="form-label">Interest Rate (% p.a.)</label><input class="form-control" type="number" name="interest_rate" value="0" step="0.1" min="0"></div>
          <div class="form-group"><label class="form-label">Term (months)</label><input class="form-control" type="number" name="term_months" value="12" min="1"></div>
          <div class="form-group"><label class="form-label">Monthly Installment (UGX)</label><input class="form-control" type="number" name="monthly_installment" value="0" step="1000" min="0"></div>
          <div class="form-group"><label class="form-label">Disbursement Date *</label><input class="form-control" type="date" name="disbursement_date" value="<?= date('Y-m-d') ?>" required></div>
          <div class="form-group"><label class="form-label">Maturity Date</label><input class="form-control" type="date" name="maturity_date"></div>
          <div class="form-group full"><label class="form-label">Purpose</label><input class="form-control" type="text" name="purpose" placeholder="e.g. Purchase of printing equipment"></div>
          <div class="form-group full"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
        </div>
        <div class="mt-2"><button type="submit" class="btn btn-primary">Save Loan</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Repayment Modal -->
<div class="modal-overlay" id="modalRepayment">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Record Loan Repayment</span><button class="modal-close" data-modal-close="modalRepayment">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_repayment">
        <input type="hidden" name="loan_id" id="repayLoanId">
        <p class="text-muted mb-2" style="font-size:.82rem">Balance outstanding: <strong class="text-orange" id="repayBalance">—</strong></p>
        <div class="form-grid form-grid-2">
          <div class="form-group"><label class="form-label">Principal Paid (UGX)</label><input class="form-control" type="number" name="principal_paid" id="repayPrincipal" min="0" step="1000"></div>
          <div class="form-group"><label class="form-label">Interest Paid (UGX)</label><input class="form-control" type="number" name="interest_paid" value="0" step="100" min="0"></div>
          <div class="form-group"><label class="form-label">Payment Date</label><input class="form-control" type="date" name="payment_date" value="<?= date('Y-m-d') ?>"></div>
          <div class="form-group"><label class="form-label">Method</label>
            <select class="form-control" name="method"><option value="bank_transfer">Bank Transfer</option><option value="cash">Cash</option><option value="mobile_money">Mobile Money</option><option value="cheque">Cheque</option></select>
          </div>
          <div class="form-group full"><label class="form-label">Reference</label><input class="form-control" type="text" name="reference"></div>
        </div>
        <div class="mt-2"><button type="submit" class="btn btn-primary">Save Repayment</button></div>
      </form>
    </div>
  </div>
</div>
<script>
function openRepayModal(loanId, balance, installment) {
  document.getElementById('repayLoanId').value = loanId;
  document.getElementById('repayBalance').textContent = 'UGX ' + balance.toLocaleString();
  document.getElementById('repayPrincipal').value = installment || '';
  openModal('modalRepayment');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
