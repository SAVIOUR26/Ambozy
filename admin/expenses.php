<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$page_title = 'Expenses';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add_expense' && csrf_verify()) {
    $pdo->prepare("INSERT INTO expenses (category_id,description,amount,expense_date,paid_by,receipt_ref,approved_by,notes,recorded_by) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([
            (int)($_POST['category_id']??0)?:(null),
            trim($_POST['description']??''),
            (float)($_POST['amount']??0),
            $_POST['expense_date']??date('Y-m-d'),
            $_POST['paid_by']??'cash',
            trim($_POST['receipt_ref']??''),
            trim($_POST['approved_by']??''),
            trim($_POST['notes']??''),
            $_SESSION['admin_id']
        ]);
    flash('Expense recorded.');
    header('Location: ' . admin_url('expenses.php')); exit;
}

$categories = $pdo->query("SELECT * FROM expense_categories ORDER BY name")->fetchAll();
$month_filter = $_GET['month'] ?? date('Y-m');
$cat_filter   = (int)($_GET['cat'] ?? 0);

[$y,$m] = explode('-', $month_filter . '-01');
$where_parts = ["YEAR(e.expense_date)=? AND MONTH(e.expense_date)=?"];
$args = [(int)$y,(int)$m];
if ($cat_filter) { $where_parts[] = "e.category_id=?"; $args[] = $cat_filter; }
$where = 'WHERE ' . implode(' AND ', $where_parts);

$per = 30; $page = max(1,(int)($_GET['page']??1));
$cnt_s = $pdo->prepare("SELECT COUNT(*) FROM expenses e $where"); $cnt_s->execute($args);
$total = (int)$cnt_s->fetchColumn(); $pg = paginate($total,$per,$page);

$stmt = $pdo->prepare("SELECT e.*,ec.name as cat_name FROM expenses e LEFT JOIN expense_categories ec ON ec.id=e.category_id $where ORDER BY e.expense_date DESC LIMIT {$per} OFFSET {$pg['offset']}");
$stmt->execute($args); $expenses = $stmt->fetchAll();

// Monthly total
$sum_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses e $where"); $sum_stmt->execute($args);
$month_total = (float)$sum_stmt->fetchColumn();

// By category this month
$cat_totals = $pdo->prepare("SELECT ec.name, COALESCE(SUM(e.amount),0) as total FROM expenses e LEFT JOIN expense_categories ec ON ec.id=e.category_id WHERE YEAR(e.expense_date)=? AND MONTH(e.expense_date)=? GROUP BY e.category_id ORDER BY total DESC");
$cat_totals->execute([(int)$y,(int)$m]); $cat_totals = $cat_totals->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.25rem">
  <div class="kpi-card"><div class="kpi-label">Total This Month</div><div class="kpi-value red"><?= fmt_ugx($month_total) ?></div><div class="kpi-sub"><?= date('F Y', mktime(0,0,0,(int)$m,1,(int)$y)) ?></div></div>
  <div class="kpi-card">
    <div class="kpi-label">Filter by Month</div>
    <form method="GET" style="margin-top:.5rem">
      <input class="form-control" type="month" name="month" value="<?= h($month_filter) ?>" onchange="this.form.submit()" style="padding:.4rem .7rem;font-size:.8rem">
      <?php if($cat_filter): ?><input type="hidden" name="cat" value="<?= $cat_filter ?>"><?php endif; ?>
    </form>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Filter by Category</div>
    <form method="GET" style="margin-top:.5rem">
      <select class="form-control" name="cat" onchange="this.form.submit()" style="padding:.4rem .7rem;font-size:.8rem">
        <option value="">All Categories</option>
        <?php foreach($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $cat_filter==$cat['id']?'selected':'' ?>><?= h($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="hidden" name="month" value="<?= h($month_filter) ?>">
    </form>
  </div>
</div>

<div class="page-actions">
  <div></div>
  <button class="btn btn-primary" data-modal-open="modalAddExpense">+ Record Expense</button>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-header"><span class="card-title">Expenses Log</span></div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Method</th><th class="num">Amount</th></tr></thead>
        <tbody>
          <?php foreach ($expenses as $exp): ?>
          <tr>
            <td><?= fmt_date($exp['expense_date']) ?></td>
            <td><?= h($exp['cat_name']?:'—') ?></td>
            <td><?= h($exp['description']) ?><?= $exp['receipt_ref']?'<div class="muted">Ref: '.h($exp['receipt_ref']).'</div>':'' ?></td>
            <td><?= ucfirst(str_replace('_',' ',$exp['paid_by'])) ?></td>
            <td class="num text-red"><?= fmt_ugx($exp['amount']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(!$expenses): ?><tr><td colspan="5" class="empty-state">No expenses</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">By Category</span></div>
    <div class="card-body">
      <?php foreach($cat_totals as $ct): ?>
      <div class="flex" style="justify-content:space-between;margin-bottom:.65rem">
        <span style="font-size:.85rem"><?= h($ct['name'] ?: 'Uncategorised') ?></span>
        <span style="font-size:.85rem;font-weight:700;color:var(--red)"><?= fmt_ugx($ct['total']) ?></span>
      </div>
      <?php endforeach; ?>
      <?php if(!$cat_totals): ?><div class="empty-state">No data</div><?php endif; ?>
    </div>
  </div>
</div>
<?= pagination_links($pg, admin_url("expenses.php?month=$month_filter&cat=$cat_filter")) ?>

<!-- Add Expense Modal -->
<div class="modal-overlay" id="modalAddExpense">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Record Expense</span><button class="modal-close" data-modal-close="modalAddExpense">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_expense">
        <div class="form-grid form-grid-2">
          <div class="form-group full"><label class="form-label">Category</label>
            <select class="form-control" name="category_id">
              <option value="">— Uncategorised —</option>
              <?php foreach($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group full"><label class="form-label">Description *</label><input class="form-control" type="text" name="description" placeholder="e.g. Fuel for delivery van" required></div>
          <div class="form-group"><label class="form-label">Amount (UGX) *</label><input class="form-control" type="number" name="amount" min="1" step="1" required></div>
          <div class="form-group"><label class="form-label">Date *</label><input class="form-control" type="date" name="expense_date" value="<?= date('Y-m-d') ?>" required></div>
          <div class="form-group"><label class="form-label">Paid By</label>
            <select class="form-control" name="paid_by">
              <option value="cash">Cash</option><option value="petty_cash">Petty Cash</option>
              <option value="mobile_money">Mobile Money</option><option value="bank_transfer">Bank Transfer</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Receipt / Ref No.</label><input class="form-control" type="text" name="receipt_ref"></div>
          <div class="form-group"><label class="form-label">Approved By</label><input class="form-control" type="text" name="approved_by"></div>
          <div class="form-group full"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
        </div>
        <div class="mt-2"><button type="submit" class="btn btn-primary">Save Expense</button></div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
