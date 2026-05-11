<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$page_title = 'New Invoice';

// Pre-fill client if coming from client page
$pre_client = (int)($_GET['client_id'] ?? 0);

$clients  = $pdo->query("SELECT id,name,company FROM clients ORDER BY name")->fetchAll();
$projects = $pdo->query("SELECT id,project_number,title,client_id FROM projects WHERE status!='cancelled' ORDER BY title")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $errors[] = 'Invalid request.'; }
    else {
        $client_id   = (int)($_POST['client_id'] ?? 0);
        $project_id  = (int)($_POST['project_id'] ?? 0) ?: null;
        $issue_date  = $_POST['issue_date'] ?? date('Y-m-d');
        $due_date    = $_POST['due_date'] ?? '';
        $vat_rate    = (float)($_POST['vat_rate'] ?? 18);
        $notes       = trim($_POST['notes'] ?? '');
        $descs       = $_POST['desc'] ?? [];
        $qtys        = $_POST['qty'] ?? [];
        $prices      = $_POST['price'] ?? [];
        $totals_row  = $_POST['total'] ?? [];

        if (!$client_id)          $errors[] = 'Select a client.';
        if (!$due_date)           $errors[] = 'Due date is required.';
        if (empty(array_filter($descs))) $errors[] = 'Add at least one line item.';

        if (!$errors) {
            $subtotal = 0;
            foreach ($qtys as $k => $q) {
                $subtotal += (float)$q * (float)($prices[$k] ?? 0);
            }
            $vat_amount = $subtotal * $vat_rate / 100;
            $total      = $subtotal + $vat_amount;
            $inv_num    = next_invoice_number($pdo);

            $pdo->prepare("INSERT INTO invoices (invoice_number,client_id,project_id,issue_date,due_date,subtotal,vat_rate,vat_amount,total,balance,status,notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$inv_num,$client_id,$project_id,$issue_date,$due_date,$subtotal,$vat_rate,$vat_amount,$total,$total,'sent',$notes]);

            $inv_id = (int)$pdo->lastInsertId();
            $item_stmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id,description,quantity,unit_price,total) VALUES (?,?,?,?,?)");
            foreach ($descs as $k => $desc) {
                $desc = trim($desc);
                if (!$desc) continue;
                $q = (float)($qtys[$k] ?? 1);
                $p = (float)($prices[$k] ?? 0);
                $item_stmt->execute([$inv_id, $desc, $q, $p, $q*$p]);
            }
            flash("Invoice $inv_num created.");
            header('Location: ' . admin_url("invoice-view.php?id=$inv_id")); exit;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="page-actions">
  <a href="<?= admin_url('invoices.php') ?>" class="btn-ghost">← Invoices</a>
</div>

<?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>', array_map('h', $errors)) ?></div><?php endif; ?>

<form method="POST" id="invoiceForm">
  <?= csrf_field() ?>
  <div class="grid-2" style="align-items:start">
    <div class="card">
      <div class="card-header"><span class="card-title">Invoice Details</span></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-group full">
            <label class="form-label">Client *</label>
            <select class="form-control" name="client_id" id="clientSelect" required>
              <option value="">— Select client —</option>
              <?php foreach ($clients as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $c['id']==$pre_client?'selected':'' ?>><?= h($c['name']) ?><?= $c['company']?' — '.h($c['company']):'' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group full">
            <label class="form-label">Project (optional)</label>
            <select class="form-control" name="project_id">
              <option value="">— None —</option>
              <?php foreach ($projects as $p): ?>
              <option value="<?= $p['id'] ?>"><?= h($p['project_number'] ?: $p['id']) ?> — <?= h($p['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Issue Date *</label>
            <input class="form-control" type="date" name="issue_date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Due Date *</label>
            <input class="form-control" type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">VAT Rate (%)</label>
            <input class="form-control" type="number" name="vat_rate" id="vatRate" value="18" min="0" max="100" step="0.5">
            <span class="form-hint">Uganda standard VAT = 18%</span>
          </div>
          <div class="form-group full">
            <label class="form-label">Notes</label>
            <textarea class="form-control" name="notes" rows="2" placeholder="Payment terms, special instructions…"></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Totals -->
    <div class="card">
      <div class="card-header"><span class="card-title">Summary</span></div>
      <div class="card-body">
        <table class="totals-table">
          <tr><td>Subtotal</td><td class="text-right" id="subtotal">UGX 0.00</td></tr>
          <tr><td>VAT (18%)</td><td class="text-right" id="vatAmount">UGX 0.00</td></tr>
          <tr class="total-row">
            <td class="total-label">Total</td>
            <td class="text-right total-val" id="grandTotal">UGX 0.00</td>
          </tr>
        </table>
        <input type="hidden" name="subtotal_h" id="subtotalHidden">
        <input type="hidden" name="vat_amount_h" id="vatAmountHidden">
        <input type="hidden" name="grand_total_h" id="grandTotalHidden">
        <div class="mt-3">
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Create Invoice</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Line items -->
  <div class="card mt-2">
    <div class="card-header">
      <span class="card-title">Line Items</span>
      <button type="button" class="btn btn-sm btn-outline" id="addLineItem">+ Add Row</button>
    </div>
    <div class="card-body">
      <table class="items-table" style="width:100%">
        <thead>
          <tr>
            <th style="width:80px">Qty</th>
            <th>Description</th>
            <th style="width:150px">Unit Price (UGX)</th>
            <th style="width:150px">Total (UGX)</th>
            <th style="width:40px"></th>
          </tr>
        </thead>
        <tbody id="lineItemsBody">
          <tr>
            <td><input class="form-control qty" type="number" name="qty[]" value="1" min="0.01" step="0.01" required></td>
            <td><input class="form-control description" type="text" name="desc[]" placeholder="e.g. Branded T-shirts (50 pcs)" required></td>
            <td><input class="form-control price r" type="number" name="price[]" value="0" min="0" step="1" required></td>
            <td><input class="form-control row-total r" type="number" name="total[]" value="0" readonly tabindex="-1"></td>
            <td><button type="button" class="rm-row" title="Remove">✕</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
