<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$id  = (int)($_GET['id'] ?? 0);

$inv  = $pdo->prepare("SELECT i.*,c.name as client_name,c.company,c.address as client_address,c.email as client_email FROM invoices i JOIN clients c ON c.id=i.client_id WHERE i.id=?");
$inv->execute([$id]); $inv = $inv->fetch();
if (!$inv) { header('Location: ' . admin_url('invoices.php')); exit; }

// ── Record payment ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'record_payment') {
    if (csrf_verify()) {
        $amount = (float)($_POST['amount'] ?? 0);
        $date   = $_POST['payment_date'] ?? date('Y-m-d');
        $method = $_POST['method'] ?? 'cash';
        $ref    = trim($_POST['reference'] ?? '');
        $notes  = trim($_POST['notes'] ?? '');

        if ($amount > 0 && $amount <= $inv['balance']) {
            $pay_num = next_payment_number($pdo);
            $pdo->prepare("INSERT INTO payments_received (payment_number,invoice_id,client_id,amount,payment_date,method,reference,notes,recorded_by) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$pay_num,$id,$inv['client_id'],$amount,$date,$method,$ref,$notes,$_SESSION['admin_id']]);
            sync_invoice_balance($id, $pdo);
            flash("Payment of " . fmt_ugx($amount) . " recorded.");
        } else {
            flash('Invalid payment amount.', 'error');
        }
        header('Location: ' . admin_url("invoice-view.php?id=$id")); exit;
    }
}

// Re-fetch
$inv_r = $pdo->prepare("SELECT i.*,c.name as client_name,c.company,c.address as client_address,c.email as client_email FROM invoices i JOIN clients c ON c.id=i.client_id WHERE i.id=?");
$inv_r->execute([$id]); $inv = $inv_r->fetch();

$items    = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id=?"); $items->execute([$id]); $items = $items->fetchAll();
$payments = $pdo->prepare("SELECT * FROM payments_received WHERE invoice_id=? ORDER BY payment_date DESC"); $payments->execute([$id]); $payments = $payments->fetchAll();

$pct_paid = $inv['total'] > 0 ? min(100, ($inv['amount_paid'] / $inv['total']) * 100) : 0;
$page_title = 'Invoice ' . h($inv['invoice_number']);

include __DIR__ . '/includes/header.php';
?>

<div class="page-actions">
  <div class="page-actions-left">
    <a href="<?= admin_url('invoices.php') ?>" class="btn-ghost">← Invoices</a>
    <a href="<?= admin_url('client-view.php?id='.$inv['client_id']) ?>" class="btn-ghost"><?= h($inv['client_name']) ?></a>
  </div>
  <div class="page-actions-right">
    <?= status_badge($inv['status']) ?>
    <?php if ($inv['balance'] > 0): ?>
    <button class="btn btn-primary" data-modal-open="modalPayment">Record Payment</button>
    <?php endif; ?>
  </div>
</div>

<div class="detail-grid">
  <div>
    <!-- Invoice meta -->
    <div class="card">
      <div class="card-header"><span class="card-title"><?= h($inv['invoice_number']) ?></span></div>
      <div class="card-body">
        <div class="detail-item"><div class="detail-label">Billed To</div>
          <div class="detail-value"><strong><?= h($inv['client_name']) ?></strong><?= $inv['company']?'<br>'.h($inv['company']):'' ?></div></div>
        <div class="detail-item"><div class="detail-label">Issue Date</div><div class="detail-value"><?= fmt_date($inv['issue_date']) ?></div></div>
        <div class="detail-item"><div class="detail-label">Due Date</div><div class="detail-value <?= $inv['status']==='overdue'?'text-red':'' ?>"><?= fmt_date($inv['due_date']) ?></div></div>
        <?php if ($inv['notes']): ?><div class="detail-item"><div class="detail-label">Notes</div><div class="detail-value"><?= h($inv['notes']) ?></div></div><?php endif; ?>
      </div>
    </div>

    <!-- Payment progress -->
    <div class="card mt-2">
      <div class="card-header"><span class="card-title">Payment Status</span></div>
      <div class="card-body">
        <div class="flex" style="justify-content:space-between;margin-bottom:.75rem">
          <span class="text-muted" style="font-size:.8rem"><?= fmt_ugx($inv['amount_paid']) ?> paid of <?= fmt_ugx($inv['total']) ?></span>
          <span style="font-size:.8rem;font-weight:700"><?= number_format($pct_paid,0) ?>%</span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct_paid ?>%"></div></div>
        <div class="flex mt-2" style="justify-content:space-between">
          <div><div class="detail-label">Total Invoice</div><div class="detail-value"><?= fmt_ugx($inv['total']) ?></div></div>
          <div><div class="detail-label">Amount Paid</div><div class="detail-value text-green"><?= fmt_ugx($inv['amount_paid']) ?></div></div>
          <div><div class="detail-label">Balance Due</div><div class="detail-value <?= $inv['balance']>0?'text-orange':'' ?>"><?= fmt_ugx($inv['balance']) ?></div></div>
        </div>
      </div>
    </div>

    <!-- Payment history -->
    <?php if ($payments): ?>
    <div class="card mt-2">
      <div class="card-header"><span class="card-title">Payment History</span></div>
      <div class="card-body">
        <div class="payment-list">
          <?php foreach ($payments as $p): ?>
          <div class="payment-row">
            <div>
              <div style="font-size:.8rem;font-weight:600"><?= h($p['payment_number'] ?: '#'.$p['id']) ?></div>
              <div class="payment-row-meta"><?= ucfirst(str_replace('_',' ',$p['method'])) ?> · <?= fmt_date($p['payment_date']) ?><?= $p['reference']?' · Ref: '.h($p['reference']):'' ?></div>
            </div>
            <div class="payment-row-amount"><?= fmt_ugx($p['amount']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Line items + totals -->
  <div class="card">
    <div class="card-header"><span class="card-title">Line Items</span></div>
    <div class="table-wrap">
      <table class="items-table">
        <thead>
          <tr><th>Description</th><th class="r">Qty</th><th class="r">Unit Price</th><th class="r">Total</th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td><?= h($item['description']) ?></td>
            <td class="r"><?= $item['quantity'] ?></td>
            <td class="r"><?= fmt_ugx($item['unit_price']) ?></td>
            <td class="r"><?= fmt_ugx($item['total']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-body" style="border-top:1px solid var(--card-border)">
      <table class="totals-table">
        <tr><td>Subtotal</td><td class="text-right"><?= fmt_ugx($inv['subtotal']) ?></td></tr>
        <tr><td>VAT (<?= $inv['vat_rate'] ?>%)</td><td class="text-right"><?= fmt_ugx($inv['vat_amount']) ?></td></tr>
        <tr class="total-row"><td class="total-label">Total</td><td class="text-right total-val"><?= fmt_ugx($inv['total']) ?></td></tr>
      </table>
    </div>
  </div>
</div>

<!-- Record Payment Modal -->
<?php if ($inv['balance'] > 0): ?>
<div class="modal-overlay" id="modalPayment">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Record Payment</span>
      <button class="modal-close" data-modal-close="modalPayment">✕</button>
    </div>
    <div class="modal-body">
      <p class="text-muted mb-2" style="font-size:.85rem">Balance due: <strong style="color:var(--orange)"><?= fmt_ugx($inv['balance']) ?></strong></p>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="record_payment">
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label class="form-label">Amount (UGX) *</label>
            <input class="form-control" type="number" name="amount" min="1" max="<?= $inv['balance'] ?>" step="1" value="<?= $inv['balance'] ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Date *</label>
            <input class="form-control" type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Method</label>
            <select class="form-control" name="method">
              <option value="cash">Cash</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="mobile_money">Mobile Money</option>
              <option value="cheque">Cheque</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Reference / Receipt No.</label>
            <input class="form-control" type="text" name="reference">
          </div>
          <div class="form-group full">
            <label class="form-label">Notes</label>
            <textarea class="form-control" name="notes" rows="2"></textarea>
          </div>
        </div>
        <div class="mt-2"><button type="submit" class="btn btn-primary">Save Payment</button></div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
