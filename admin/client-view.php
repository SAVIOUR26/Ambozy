<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$id  = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();
if (!$client) { header('Location: ' . admin_url('clients.php')); exit; }

// ── Update client ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'update') {
    if (csrf_verify()) {
        $pdo->prepare("UPDATE clients SET name=?,company=?,email=?,phone=?,address=?,tin=?,credit_limit=?,notes=? WHERE id=?")
            ->execute([
                trim($_POST['name']??''), trim($_POST['company']??''),
                trim($_POST['email']??''), trim($_POST['phone']??''),
                trim($_POST['address']??''), trim($_POST['tin']??''),
                (float)($_POST['credit_limit']??0), trim($_POST['notes']??''), $id
            ]);
        flash('Client updated.');
        header('Location: ' . admin_url("client-view.php?id=$id")); exit;
    }
}

// Re-fetch after update
$stmt->execute([$id]);
$client = $stmt->fetch();

// ── Invoices ──────────────────────────────────────────────────────
$invoices = $pdo->prepare("SELECT * FROM invoices WHERE client_id=? ORDER BY issue_date DESC");
$invoices->execute([$id]); $invoices = $invoices->fetchAll();

// ── Receivables ───────────────────────────────────────────────────
$totals = $pdo->prepare("
  SELECT COALESCE(SUM(total),0) as total_billed,
         COALESCE(SUM(amount_paid),0) as total_paid,
         COALESCE(SUM(balance),0) as total_balance
  FROM invoices WHERE client_id=? AND status!='cancelled'");
$totals->execute([$id]); $totals = $totals->fetch();

$page_title = h($client['name']);
include __DIR__ . '/includes/header.php';
?>

<div class="page-actions">
  <div class="page-actions-left">
    <a href="<?= admin_url('clients.php') ?>" class="btn-ghost">← Clients</a>
  </div>
  <div class="page-actions-right">
    <a href="<?= admin_url('invoice-new.php?client_id='.$id) ?>" class="btn btn-primary">+ New Invoice</a>
  </div>
</div>

<div class="detail-grid">
  <!-- Client info -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Client Details</span>
      <button class="btn btn-sm btn-outline" data-modal-open="modalEditClient">Edit</button>
    </div>
    <div class="card-body">
      <div class="detail-item"><div class="detail-label">Company</div><div class="detail-value"><?= h($client['company'] ?: '—') ?></div></div>
      <div class="detail-item"><div class="detail-label">Phone</div><div class="detail-value"><?= h($client['phone'] ?: '—') ?></div></div>
      <div class="detail-item"><div class="detail-label">Email</div><div class="detail-value"><?= h($client['email'] ?: '—') ?></div></div>
      <div class="detail-item"><div class="detail-label">Address</div><div class="detail-value"><?= h($client['address'] ?: '—') ?></div></div>
      <div class="detail-item"><div class="detail-label">TIN (URA)</div><div class="detail-value"><?= h($client['tin'] ?: '—') ?></div></div>
      <div class="detail-item"><div class="detail-label">Credit Limit</div><div class="detail-value <?= $client['credit_limit']>0?'text-orange':'' ?>"><?= fmt_ugx($client['credit_limit']) ?></div></div>
      <?php if ($client['notes']): ?><div class="detail-item"><div class="detail-label">Notes</div><div class="detail-value"><?= h($client['notes']) ?></div></div><?php endif; ?>
    </div>

    <!-- Account summary -->
    <div class="divider" style="margin:0"></div>
    <div class="card-body">
      <div class="detail-label" style="margin-bottom:.75rem">Account Summary</div>
      <table class="totals-table">
        <tr><td>Total Billed</td><td class="text-right"><?= fmt_ugx($totals['total_billed']) ?></td></tr>
        <tr><td>Total Paid</td><td class="text-right text-green"><?= fmt_ugx($totals['total_paid']) ?></td></tr>
        <tr class="total-row"><td class="total-label">Balance Due</td><td class="text-right total-val"><?= fmt_ugx($totals['total_balance']) ?></td></tr>
      </table>
    </div>
  </div>

  <!-- Invoices -->
  <div class="card">
    <div class="card-header"><span class="card-title">Invoices</span></div>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>#</th><th>Date</th><th>Due</th><th class="num">Total</th><th class="num">Balance</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($invoices as $inv): ?>
          <tr>
            <td><a href="<?= admin_url('invoice-view.php?id='.$inv['id']) ?>" style="color:var(--orange)"><?= h($inv['invoice_number']) ?></a></td>
            <td><?= fmt_date($inv['issue_date']) ?></td>
            <td><?= fmt_date($inv['due_date']) ?></td>
            <td class="num"><?= fmt_ugx($inv['total']) ?></td>
            <td class="num <?= $inv['balance']>0?'text-orange':'' ?>"><?= fmt_ugx($inv['balance']) ?></td>
            <td><?= status_badge($inv['status']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$invoices): ?><tr><td colspan="6" class="empty-state">No invoices</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Edit Client Modal -->
<div class="modal-overlay" id="modalEditClient">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Client</span>
      <button class="modal-close" data-modal-close="modalEditClient">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input class="form-control" type="text" name="name" value="<?= h($client['name']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Company</label>
            <input class="form-control" type="text" name="company" value="<?= h($client['company']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" value="<?= h($client['email']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input class="form-control" type="tel" name="phone" value="<?= h($client['phone']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">TIN</label>
            <input class="form-control" type="text" name="tin" value="<?= h($client['tin']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Credit Limit (UGX)</label>
            <input class="form-control" type="number" name="credit_limit" value="<?= $client['credit_limit'] ?>">
          </div>
          <div class="form-group full">
            <label class="form-label">Address</label>
            <textarea class="form-control" name="address"><?= h($client['address']??'') ?></textarea>
          </div>
          <div class="form-group full">
            <label class="form-label">Notes</label>
            <textarea class="form-control" name="notes"><?= h($client['notes']??'') ?></textarea>
          </div>
        </div>
        <div class="mt-2"><button type="submit" class="btn btn-primary">Save Changes</button></div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
