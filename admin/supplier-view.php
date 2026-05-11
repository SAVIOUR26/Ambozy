<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$id  = (int)($_GET['id'] ?? 0);
$s   = $pdo->prepare("SELECT * FROM suppliers WHERE id=?"); $s->execute([$id]); $supplier = $s->fetch();
if (!$supplier) { header('Location: ' . admin_url('suppliers.php')); exit; }

// ── Add bill ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add_bill' && csrf_verify()) {
    $bill_date  = $_POST['bill_date'] ?? date('Y-m-d');
    $due_date   = $_POST['due_date']  ?? date('Y-m-d', strtotime('+30 days'));
    $subtotal   = (float)($_POST['subtotal_h'] ?? 0);
    $vat        = (float)($_POST['vat_h'] ?? 0);
    $total      = $subtotal + $vat;
    $notes      = trim($_POST['notes'] ?? '');
    $descs      = $_POST['desc']  ?? [];
    $qtys       = $_POST['qty']   ?? [];
    $prices     = $_POST['price'] ?? [];

    $bn = next_bill_number($pdo);
    $pdo->prepare("INSERT INTO supplier_bills (bill_number,supplier_id,bill_date,due_date,subtotal,vat_amount,total,balance,status,notes) VALUES (?,?,?,?,?,?,?,?,?,?)")
        ->execute([$bn,$id,$bill_date,$due_date,$subtotal,$vat,$total,$total,'pending',$notes]);
    $bill_id = (int)$pdo->lastInsertId();
    $is = $pdo->prepare("INSERT INTO supplier_bill_items (bill_id,description,quantity,unit_price,total) VALUES (?,?,?,?,?)");
    foreach ($descs as $k => $d) {
        $d = trim($d); if (!$d) continue;
        $q = (float)($qtys[$k]??1); $p = (float)($prices[$k]??0);
        $is->execute([$bill_id,$d,$q,$p,$q*$p]);
    }
    flash("Bill $bn added.");
    header('Location: ' . admin_url("supplier-view.php?id=$id")); exit;
}

// ── Pay bill ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'pay_bill' && csrf_verify()) {
    $bill_id = (int)($_POST['bill_id'] ?? 0);
    $amount  = (float)($_POST['amount'] ?? 0);
    $date    = $_POST['payment_date'] ?? date('Y-m-d');
    $method  = $_POST['method'] ?? 'cash';
    $ref     = trim($_POST['reference'] ?? '');
    if ($bill_id && $amount > 0) {
        $pdo->prepare("INSERT INTO payments_to_suppliers (bill_id,supplier_id,amount,payment_date,method,reference,recorded_by) VALUES (?,?,?,?,?,?,?)")
            ->execute([$bill_id,$id,$amount,$date,$method,$ref,$_SESSION['admin_id']]);
        sync_bill_balance($bill_id, $pdo);
        flash('Payment recorded.');
    }
    header('Location: ' . admin_url("supplier-view.php?id=$id")); exit;
}

$bills = $pdo->prepare("SELECT * FROM supplier_bills WHERE supplier_id=? ORDER BY bill_date DESC"); $bills->execute([$id]); $bills = $bills->fetchAll();
$totals = $pdo->prepare("SELECT COALESCE(SUM(total),0) t, COALESCE(SUM(amount_paid),0) p, COALESCE(SUM(balance),0) b FROM supplier_bills WHERE supplier_id=?"); $totals->execute([$id]); $totals = $totals->fetch();

$page_title = h($supplier['name']);
include __DIR__ . '/includes/header.php';
?>

<div class="page-actions">
  <a href="<?= admin_url('suppliers.php') ?>" class="btn-ghost">← Suppliers</a>
  <div class="page-actions-right">
    <button class="btn btn-primary" data-modal-open="modalAddBill">+ Add Bill</button>
  </div>
</div>

<div class="detail-grid">
  <div class="card">
    <div class="card-header"><span class="card-title">Supplier Details</span></div>
    <div class="card-body">
      <div class="detail-item"><div class="detail-label">Contact Person</div><div class="detail-value"><?= h($supplier['contact_person']?:'—') ?></div></div>
      <div class="detail-item"><div class="detail-label">Phone</div><div class="detail-value"><?= h($supplier['phone']?:'—') ?></div></div>
      <div class="detail-item"><div class="detail-label">Email</div><div class="detail-value"><?= h($supplier['email']?:'—') ?></div></div>
      <div class="detail-item"><div class="detail-label">Payment Terms</div><div class="detail-value"><?= $supplier['payment_terms_days'] ?> days</div></div>
      <div class="detail-item"><div class="detail-label">Credit Limit</div><div class="detail-value"><?= fmt_ugx($supplier['credit_limit']) ?></div></div>
      <?php if($supplier['tin']): ?><div class="detail-item"><div class="detail-label">TIN</div><div class="detail-value"><?= h($supplier['tin']) ?></div></div><?php endif; ?>
    </div>
    <div class="divider" style="margin:0"></div>
    <div class="card-body">
      <div class="detail-label" style="margin-bottom:.75rem">Account Summary</div>
      <table class="totals-table">
        <tr><td>Total Bills</td><td class="text-right"><?= fmt_ugx($totals['t']) ?></td></tr>
        <tr><td>Total Paid</td><td class="text-right text-green"><?= fmt_ugx($totals['p']) ?></td></tr>
        <tr class="total-row"><td class="total-label">Balance Owed</td><td class="text-right total-val"><?= fmt_ugx($totals['b']) ?></td></tr>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Bills (Credit Purchases)</span></div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>#</th><th>Date</th><th>Due</th><th class="num">Total</th><th class="num">Balance</th><th>Status</th><th>Pay</th></tr></thead>
        <tbody>
          <?php foreach ($bills as $b): ?>
          <tr>
            <td style="font-weight:600"><?= h($b['bill_number']) ?></td>
            <td><?= fmt_date($b['bill_date']) ?></td>
            <td class="<?= $b['status']==='overdue'?'text-red':'' ?>"><?= fmt_date($b['due_date']) ?></td>
            <td class="num"><?= fmt_ugx($b['total']) ?></td>
            <td class="num <?= $b['balance']>0?'text-red':'' ?>"><?= fmt_ugx($b['balance']) ?></td>
            <td><?= status_badge($b['status']) ?></td>
            <td>
              <?php if ($b['balance']>0): ?>
              <button class="btn btn-sm btn-outline" onclick="openPayBill(<?= $b['id'] ?>,<?= $b['balance'] ?>)">Pay</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$bills): ?><tr><td colspan="7" class="empty-state">No bills</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Bill Modal -->
<div class="modal-overlay" id="modalAddBill">
  <div class="modal" style="max-width:680px">
    <div class="modal-header"><span class="modal-title">New Supplier Bill</span><button class="modal-close" data-modal-close="modalAddBill">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_bill">
        <div class="form-grid form-grid-2" style="margin-bottom:1rem">
          <div class="form-group"><label class="form-label">Bill Date</label><input class="form-control" type="date" name="bill_date" value="<?= date('Y-m-d') ?>"></div>
          <div class="form-group"><label class="form-label">Due Date</label><input class="form-control" type="date" name="due_date" value="<?= date('Y-m-d',strtotime('+'.$supplier['payment_terms_days'].' days')) ?>"></div>
        </div>
        <table class="items-table" style="width:100%;margin-bottom:1rem">
          <thead><tr><th style="width:70px">Qty</th><th>Description</th><th style="width:130px">Unit Price</th><th style="width:130px">Total</th><th style="width:36px"></th></tr></thead>
          <tbody id="lineItemsBody">
            <tr>
              <td><input class="form-control qty" type="number" name="qty[]" value="1" step="0.01" min="0"></td>
              <td><input class="form-control description" type="text" name="desc[]" placeholder="Material / service description"></td>
              <td><input class="form-control price" type="number" name="price[]" value="0" step="1" min="0"></td>
              <td><input class="form-control row-total" type="number" name="total[]" value="0" readonly tabindex="-1"></td>
              <td><button type="button" class="rm-row">✕</button></td>
            </tr>
          </tbody>
        </table>
        <button type="button" class="btn btn-sm btn-outline" id="addLineItem">+ Add Row</button>
        <div style="margin-top:1rem;text-align:right">
          <table class="totals-table" style="margin-left:auto;width:260px">
            <tr><td>Subtotal</td><td class="text-right" id="subtotal">UGX 0.00</td></tr>
            <tr><td>VAT (18%)</td><td class="text-right" id="vatAmount">UGX 0.00</td></tr>
            <tr class="total-row"><td class="total-label">Total</td><td class="text-right total-val" id="grandTotal">UGX 0.00</td></tr>
          </table>
          <input type="hidden" name="subtotal_h" id="subtotalHidden">
          <input type="hidden" name="vat_h" id="vatAmountHidden">
          <input type="hidden" name="grand_total_h" id="grandTotalHidden">
          <input type="hidden" name="vat_rate" id="vatRate" value="18">
        </div>
        <div class="form-group mt-2"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
        <div class="mt-2"><button type="submit" class="btn btn-primary">Save Bill</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Pay Bill Modal -->
<div class="modal-overlay" id="modalPayBill">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Pay Supplier Bill</span><button class="modal-close" data-modal-close="modalPayBill">✕</button></div>
    <div class="modal-body">
      <form method="POST" id="payBillForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="pay_bill">
        <input type="hidden" name="bill_id" id="payBillId">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label class="form-label">Amount (UGX)</label><input class="form-control" type="number" name="amount" id="payBillAmount" min="1" step="1" required></div>
          <div class="form-group"><label class="form-label">Date</label><input class="form-control" type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
          <div class="form-group"><label class="form-label">Method</label>
            <select class="form-control" name="method"><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="mobile_money">Mobile Money</option><option value="cheque">Cheque</option></select>
          </div>
          <div class="form-group"><label class="form-label">Reference</label><input class="form-control" type="text" name="reference"></div>
        </div>
        <div class="mt-2"><button type="submit" class="btn btn-primary">Record Payment</button></div>
      </form>
    </div>
  </div>
</div>
<script>
function openPayBill(billId, balance) {
  document.getElementById('payBillId').value = billId;
  document.getElementById('payBillAmount').value = balance;
  openModal('modalPayBill');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
