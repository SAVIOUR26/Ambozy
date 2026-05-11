<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$page_title = 'Clients';
$errors = [];

// ── Add client ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add_client') {
    if (!csrf_verify()) { $errors[] = 'Invalid request.'; }
    else {
        $name    = trim($_POST['name'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $tin     = trim($_POST['tin'] ?? '');
        $credit  = (float)($_POST['credit_limit'] ?? 0);
        $notes   = trim($_POST['notes'] ?? '');

        if (!$name) $errors[] = 'Client name is required.';
        if (!$errors) {
            $pdo->prepare("INSERT INTO clients (name,company,email,phone,address,tin,credit_limit,notes) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$name,$company,$email,$phone,$address,$tin,$credit,$notes]);
            flash('Client added successfully.');
            header('Location: ' . admin_url('client-view.php?id=' . $pdo->lastInsertId()));
            exit;
        }
    }
}

// ── List ──────────────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$per    = 20;
$page   = max(1,(int)($_GET['page']??1));
$where  = $search ? "WHERE c.name LIKE ? OR c.company LIKE ? OR c.email LIKE ?" : '';
$args   = $search ? ["%$search%","%$search%","%$search%"] : [];

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM clients c $where")->execute($args) ? $pdo->prepare("SELECT COUNT(*) FROM clients c $where")->execute($args) : 0;
$cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM clients c $where"); $cnt_stmt->execute($args);
$total = (int)$cnt_stmt->fetchColumn();
$pg    = paginate($total, $per, $page);

$stmt = $pdo->prepare("
  SELECT c.*,
    (SELECT COUNT(*) FROM invoices WHERE client_id=c.id AND status!='cancelled') as inv_count,
    (SELECT COALESCE(SUM(balance),0) FROM invoices WHERE client_id=c.id AND status IN('sent','partial','overdue')) as receivable
  FROM clients c $where
  ORDER BY c.name ASC
  LIMIT {$per} OFFSET {$pg['offset']}
");
$stmt->execute($args);
$clients = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-actions">
  <div class="page-actions-left">
    <div class="search-bar">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="tableSearch" placeholder="Search clients…" value="<?= h($search) ?>">
    </div>
  </div>
  <div class="page-actions-right">
    <button class="btn btn-primary" data-modal-open="modalAddClient">+ Add Client</button>
  </div>
</div>

<?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>', array_map('h', $errors)) ?></div><?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Name</th><th>Company</th><th>Phone</th><th>Email</th>
          <th class="num">Receivable</th><th>Invoices</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clients as $c): ?>
        <tr>
          <td><a href="<?= admin_url('client-view.php?id='.$c['id']) ?>" style="color:var(--orange);font-weight:600"><?= h($c['name']) ?></a></td>
          <td><?= h($c['company'] ?: '—') ?></td>
          <td><?= h($c['phone'] ?: '—') ?></td>
          <td><?= h($c['email'] ?: '—') ?></td>
          <td class="num <?= $c['receivable']>0?'text-orange':'' ?>"><?= $c['receivable']>0 ? fmt_ugx($c['receivable']) : '—' ?></td>
          <td><?= $c['inv_count'] ?></td>
          <td class="actions">
            <a href="<?= admin_url('client-view.php?id='.$c['id']) ?>" class="btn btn-sm btn-outline">View</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$clients): ?><tr><td colspan="7" class="empty-state">No clients found</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?= pagination_links($pg, admin_url('clients.php?q='.urlencode($search))) ?>

<!-- Add Client Modal -->
<div class="modal-overlay" id="modalAddClient">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">New Client</span>
      <button class="modal-close" data-modal-close="modalAddClient">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_client">
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input class="form-control" type="text" name="name" required>
          </div>
          <div class="form-group">
            <label class="form-label">Company / Organisation</label>
            <input class="form-control" type="text" name="company">
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email">
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input class="form-control" type="tel" name="phone">
          </div>
          <div class="form-group">
            <label class="form-label">TIN (URA Tax ID)</label>
            <input class="form-control" type="text" name="tin">
          </div>
          <div class="form-group">
            <label class="form-label">Credit Limit (UGX)</label>
            <input class="form-control" type="number" name="credit_limit" min="0" step="1000" value="0">
          </div>
          <div class="form-group full">
            <label class="form-label">Address</label>
            <textarea class="form-control" name="address" rows="2"></textarea>
          </div>
          <div class="form-group full">
            <label class="form-label">Notes</label>
            <textarea class="form-control" name="notes" rows="2"></textarea>
          </div>
        </div>
        <div class="mt-2">
          <button type="submit" class="btn btn-primary">Save Client</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
