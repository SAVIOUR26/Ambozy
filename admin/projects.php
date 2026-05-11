<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$page_title = 'Projects';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add_project' && csrf_verify()) {
    $proj_num = next_project_number($pdo);
    $pdo->prepare("INSERT INTO projects (client_id,project_number,title,description,service_type,status,priority,deadline,notes) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([(int)$_POST['client_id'],$proj_num,trim($_POST['title']??''),trim($_POST['description']??''),trim($_POST['service_type']??''),$_POST['status']??'new',$_POST['priority']??'medium',$_POST['deadline']??null,trim($_POST['notes']??'')]);
    flash("Project $proj_num created.");
    header('Location: ' . admin_url('projects.php')); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'update_status' && csrf_verify()) {
    $completed = ($_POST['status']??'') === 'completed' ? date('Y-m-d') : null;
    $pdo->prepare("UPDATE projects SET status=?,completed_at=? WHERE id=?")->execute([$_POST['status'],$completed,(int)$_POST['proj_id']]);
    header('Location: ' . admin_url('projects.php')); exit;
}

$clients  = $pdo->query("SELECT id,name FROM clients ORDER BY name")->fetchAll();
$status_f = $_GET['status'] ?? '';
$per = 20; $page = max(1,(int)($_GET['page']??1));
$where = $status_f ? "WHERE p.status=?" : '';
$args  = $status_f ? [$status_f] : [];
$cnt_s = $pdo->prepare("SELECT COUNT(*) FROM projects p $where"); $cnt_s->execute($args);
$total = (int)$cnt_s->fetchColumn(); $pg = paginate($total,$per,$page);

$stmt = $pdo->prepare("SELECT p.*,c.name as client_name FROM projects p JOIN clients c ON c.id=p.client_id $where ORDER BY p.deadline ASC,p.created_at DESC LIMIT {$per} OFFSET {$pg['offset']}");
$stmt->execute($args); $projects = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-actions">
  <div class="page-actions-left">
    <?php foreach([''=>'All','new'=>'New','in_progress'=>'In Progress','on_hold'=>'On Hold','completed'=>'Completed'] as $s=>$l): ?>
    <a href="<?= admin_url('projects.php?status='.urlencode($s)) ?>" class="btn btn-sm <?= $status_f===$s?'btn-primary':'btn-outline' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <button class="btn btn-primary" data-modal-open="modalAddProject">+ New Project</button>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Title</th><th>Client</th><th>Service</th><th>Priority</th><th>Deadline</th><th>Status</th><th>Update</th></tr></thead>
      <tbody>
        <?php foreach($projects as $p):
          $dl_class = $p['deadline'] && $p['deadline'] < date('Y-m-d') && $p['status']!=='completed' ? 'text-red' : '';
        ?>
        <tr>
          <td style="font-size:.78rem;color:var(--text-muted)"><?= h($p['project_number']?:'#'.$p['id']) ?></td>
          <td style="font-weight:600"><?= h($p['title']) ?></td>
          <td><?= h($p['client_name']) ?></td>
          <td><?= h($p['service_type']?:'—') ?></td>
          <td><?= status_badge($p['priority']) ?></td>
          <td class="<?= $dl_class ?>"><?= fmt_date($p['deadline']) ?></td>
          <td><?= status_badge($p['status']) ?></td>
          <td>
            <form method="POST" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="proj_id" value="<?= $p['id'] ?>">
              <select class="form-control" name="status" onchange="this.form.submit()" style="padding:.3rem .6rem;font-size:.75rem;width:130px">
                <?php foreach(['new'=>'New','in_progress'=>'In Progress','on_hold'=>'On Hold','completed'=>'Completed','cancelled'=>'Cancelled'] as $s=>$l): ?>
                <option value="<?= $s ?>" <?= $p['status']===$s?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$projects): ?><tr><td colspan="8" class="empty-state">No projects</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Project Modal -->
<div class="modal-overlay" id="modalAddProject">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">New Project</span><button class="modal-close" data-modal-close="modalAddProject">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_project">
        <div class="form-grid form-grid-2">
          <div class="form-group full"><label class="form-label">Client *</label>
            <select class="form-control" name="client_id" required>
              <option value="">— Select client —</option>
              <?php foreach($clients as $c): ?><option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group full"><label class="form-label">Project Title *</label><input class="form-control" type="text" name="title" required></div>
          <div class="form-group"><label class="form-label">Service Type</label><input class="form-control" type="text" name="service_type" placeholder="e.g. Printing, Branding"></div>
          <div class="form-group"><label class="form-label">Priority</label>
            <select class="form-control" name="priority"><option value="medium">Medium</option><option value="low">Low</option><option value="high">High</option><option value="urgent">Urgent</option></select>
          </div>
          <div class="form-group"><label class="form-label">Deadline</label><input class="form-control" type="date" name="deadline"></div>
          <div class="form-group"><label class="form-label">Status</label>
            <select class="form-control" name="status"><option value="new">New</option><option value="in_progress">In Progress</option></select>
          </div>
          <div class="form-group full"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
          <div class="form-group full"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
        </div>
        <div class="mt-2"><button type="submit" class="btn btn-primary">Create Project</button></div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
