<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$page_title = 'Invoices';

// Mark overdue
$pdo->exec("UPDATE invoices SET status='overdue' WHERE status IN('sent','partial') AND due_date < CURDATE()");

$status_filter = $_GET['status'] ?? '';
$search        = trim($_GET['q'] ?? '');
$per  = 25; $page = max(1,(int)($_GET['page']??1));

$where_parts = ["i.status != 'cancelled'"];
$args = [];
if ($status_filter) { $where_parts[] = "i.status = ?"; $args[] = $status_filter; }
if ($search) { $where_parts[] = "(i.invoice_number LIKE ? OR c.name LIKE ?)"; $args[] = "%$search%"; $args[] = "%$search%"; }
$where = 'WHERE ' . implode(' AND ', $where_parts);

$cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices i JOIN clients c ON c.id=i.client_id $where");
$cnt_stmt->execute($args); $total = (int)$cnt_stmt->fetchColumn();
$pg = paginate($total, $per, $page);

$stmt = $pdo->prepare("
  SELECT i.*, c.name as client_name
  FROM invoices i JOIN clients c ON c.id=i.client_id
  $where ORDER BY i.issue_date DESC
  LIMIT {$per} OFFSET {$pg['offset']}
");
$stmt->execute($args);
$invoices = $stmt->fetchAll();

// Summary totals
$sum = $pdo->query("SELECT COALESCE(SUM(total),0) as t, COALESCE(SUM(amount_paid),0) as p, COALESCE(SUM(balance),0) as b FROM invoices WHERE status!='cancelled'")->fetch();

include __DIR__ . '/includes/header.php';
?>

<div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.25rem">
  <div class="kpi-card"><div class="kpi-label">Total Billed</div><div class="kpi-value"><?= fmt_ugx($sum['t']) ?></div></div>
  <div class="kpi-card"><div class="kpi-label">Total Collected</div><div class="kpi-value green"><?= fmt_ugx($sum['p']) ?></div></div>
  <div class="kpi-card"><div class="kpi-label">Outstanding</div><div class="kpi-value orange"><?= fmt_ugx($sum['b']) ?></div></div>
</div>

<div class="page-actions">
  <div class="page-actions-left">
    <div class="search-bar">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="tableSearch" placeholder="Search…" value="<?= h($search) ?>">
    </div>
    <?php foreach ([''=>'All','sent'=>'Sent','partial'=>'Partial','paid'=>'Paid','overdue'=>'Overdue'] as $s=>$label): ?>
    <a href="<?= admin_url('invoices.php?status='.urlencode($s)) ?>" class="btn btn-sm <?= $status_filter===$s?'btn-primary':'btn-outline' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
  <a href="<?= admin_url('invoice-new.php') ?>" class="btn btn-primary">+ New Invoice</a>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>#</th><th>Client</th><th>Date</th><th>Due</th><th class="num">Total</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($invoices as $inv): ?>
        <tr>
          <td><a href="<?= admin_url('invoice-view.php?id='.$inv['id']) ?>" style="color:var(--orange)"><?= h($inv['invoice_number']) ?></a></td>
          <td><?= h($inv['client_name']) ?></td>
          <td><?= fmt_date($inv['issue_date']) ?></td>
          <td class="<?= $inv['status']==='overdue'?'text-red':'' ?>"><?= fmt_date($inv['due_date']) ?></td>
          <td class="num"><?= fmt_ugx($inv['total']) ?></td>
          <td class="num text-green"><?= fmt_ugx($inv['amount_paid']) ?></td>
          <td class="num <?= $inv['balance']>0?'text-orange':'' ?>"><?= fmt_ugx($inv['balance']) ?></td>
          <td><?= status_badge($inv['status']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$invoices): ?><tr><td colspan="8" class="empty-state">No invoices found</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?= pagination_links($pg, admin_url("invoices.php?status=$status_filter&q=".urlencode($search))) ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
