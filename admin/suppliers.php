<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$page_title = 'Suppliers';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add_supplier' && csrf_verify()) {
    $pdo->prepare("INSERT INTO suppliers (name,contact_person,email,phone,address,tin,payment_terms_days,credit_limit,notes) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([
            trim($_POST['name']??''), trim($_POST['contact_person']??''),
            trim($_POST['email']??''), trim($_POST['phone']??''),
            trim($_POST['address']??''), trim($_POST['tin']??''),
            (int)($_POST['payment_terms_days']??30), (float)($_POST['credit_limit']??0),
            trim($_POST['notes']??'')
        ]);
    flash('Supplier added.');
    header('Location: ' . admin_url('supplier-view.php?id='.$pdo->lastInsertId())); exit;
}

$search = trim($_GET['q'] ?? '');
$per = 20; $page = max(1,(int)($_GET['page']??1));
$where = $search ? "WHERE s.name LIKE ? OR s.contact_person LIKE ?" : '';
$args  = $search ? ["%$search%","%$search%"] : [];

$cnt_s = $pdo->prepare("SELECT COUNT(*) FROM suppliers s $where"); $cnt_s->execute($args);
$total = (int)$cnt_s->fetchColumn();
$pg = paginate($total,$per,$page);

$stmt = $pdo->prepare("
  SELECT s.*,
    (SELECT COALESCE(SUM(balance),0) FROM supplier_bills WHERE supplier_id=s.id AND status IN('pending','partial','overdue')) as owed
  FROM suppliers s $where ORDER BY s.name LIMIT {$per} OFFSET {$pg['offset']}
");
$stmt->execute($args); $suppliers = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-actions">
  <div class="page-actions-left">
    <div class="search-bar">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="tableSearch" placeholder="Search suppliers…" value="<?= h($search) ?>">
    </div>
  </div>
  <button class="btn btn-primary" data-modal-open="modalAddSupplier">+ Add Supplier</button>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Terms</th><th>Credit Limit</th><th class="num">Amount Owed</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($suppliers as $s): ?>
        <tr>
          <td><a href="<?= admin_url('supplier-view.php?id='.$s['id']) ?>" style="color:var(--orange);font-weight:600"><?= h($s['name']) ?></a></td>
          <td><?= h($s['contact_person']?:'—') ?></td>
          <td><?= h($s['phone']?:'—') ?></td>
          <td><?= $s['payment_terms_days'] ?> days</td>
          <td><?= fmt_ugx($s['credit_limit']) ?></td>
          <td class="num <?= $s['owed']>0?'text-red':'' ?>"><?= $s['owed']>0?fmt_ugx($s['owed']):'—' ?></td>
          <td><a href="<?= admin_url('supplier-view.php?id='.$s['id']) ?>" class="btn btn-sm btn-outline">View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$suppliers): ?><tr><td colspan="7" class="empty-state">No suppliers yet</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?= pagination_links($pg, admin_url('suppliers.php?q='.urlencode($search))) ?>

<div class="modal-overlay" id="modalAddSupplier">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">New Supplier</span><button class="modal-close" data-modal-close="modalAddSupplier">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_supplier">
        <div class="form-grid form-grid-2">
          <div class="form-group full"><label class="form-label">Supplier Name *</label><input class="form-control" type="text" name="name" required></div>
          <div class="form-group"><label class="form-label">Contact Person</label><input class="form-control" type="text" name="contact_person"></div>
          <div class="form-group"><label class="form-label">Phone</label><input class="form-control" type="tel" name="phone"></div>
          <div class="form-group"><label class="form-label">Email</label><input class="form-control" type="email" name="email"></div>
          <div class="form-group"><label class="form-label">TIN</label><input class="form-control" type="text" name="tin"></div>
          <div class="form-group"><label class="form-label">Payment Terms (days)</label><input class="form-control" type="number" name="payment_terms_days" value="30" min="0"></div>
          <div class="form-group"><label class="form-label">Credit Limit (UGX)</label><input class="form-control" type="number" name="credit_limit" value="0" min="0" step="1000"></div>
          <div class="form-group full"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2"></textarea></div>
          <div class="form-group full"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
        </div>
        <div class="mt-2"><button type="submit" class="btn btn-primary">Save Supplier</button></div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
