<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check();

$pdo = get_pdo();
$page_title = 'Enquiries';

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'update_status' && csrf_verify()) {
    $pdo->prepare("UPDATE enquiries SET status=?,notes=? WHERE id=?")
        ->execute([$_POST['status']??'new', trim($_POST['notes']??''), (int)($_POST['id']??0)]);
    flash('Enquiry updated.');
    header('Location: ' . admin_url('enquiries.php')); exit;
}

$per = 25; $page = max(1,(int)($_GET['page']??1));
$status_f = $_GET['status'] ?? '';
$where = $status_f ? "WHERE e.status=?" : '';
$args  = $status_f ? [$status_f] : [];
$cnt_s = $pdo->prepare("SELECT COUNT(*) FROM enquiries e $where"); $cnt_s->execute($args);
$total = (int)$cnt_s->fetchColumn(); $pg = paginate($total,$per,$page);

$stmt = $pdo->prepare("SELECT e.*,c.name as client_name FROM enquiries e LEFT JOIN clients c ON c.id=e.client_id $where ORDER BY e.created_at DESC LIMIT {$per} OFFSET {$pg['offset']}");
$stmt->execute($args); $enquiries = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-actions">
  <div class="page-actions-left">
    <?php foreach([''=>'All','new'=>'New','in_progress'=>'In Progress','converted'=>'Converted','closed'=>'Closed'] as $s=>$l): ?>
    <a href="<?= admin_url('enquiries.php?status='.urlencode($s)) ?>" class="btn btn-sm <?= $status_f===$s?'btn-primary':'btn-outline' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Date</th><th>Name</th><th>Company</th><th>Service</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($enquiries as $e): ?>
        <tr>
          <td><?= fmt_date($e['created_at']) ?></td>
          <td style="font-weight:600"><?= h($e['name']) ?></td>
          <td><?= h($e['company']?:'—') ?></td>
          <td><?= h($e['service']?:'—') ?></td>
          <td><?= status_badge($e['status']) ?></td>
          <td>
            <button class="btn btn-sm btn-outline" onclick="openEnquiry(<?= htmlspecialchars(json_encode($e)) ?>)">View / Update</button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$enquiries): ?><tr><td colspan="6" class="empty-state">No enquiries found</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Enquiry Detail Modal -->
<div class="modal-overlay" id="modalEnquiry">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Enquiry Detail</span><button class="modal-close" data-modal-close="modalEnquiry">✕</button></div>
    <div class="modal-body">
      <div id="enquiryDetail" style="margin-bottom:1.25rem;font-size:.875rem;line-height:1.7;color:var(--text-muted)"></div>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="id" id="enquiryId">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label class="form-label">Status</label>
            <select class="form-control" name="status" id="enquiryStatus">
              <option value="new">New</option><option value="in_progress">In Progress</option>
              <option value="converted">Converted</option><option value="closed">Closed</option>
            </select>
          </div>
          <div class="form-group full"><label class="form-label">Internal Notes</label><textarea class="form-control" name="notes" id="enquiryNotes" rows="3"></textarea></div>
        </div>
        <div class="mt-2"><button type="submit" class="btn btn-primary">Update</button></div>
      </form>
    </div>
  </div>
</div>
<script>
function openEnquiry(e) {
  document.getElementById('enquiryId').value = e.id;
  document.getElementById('enquiryStatus').value = e.status;
  document.getElementById('enquiryNotes').value = e.notes || '';
  document.getElementById('enquiryDetail').innerHTML =
    '<strong>' + (e.name||'') + '</strong> — ' + (e.company||'') +
    '<br>Phone: ' + (e.phone||'—') + ' | Email: ' + (e.email||'—') +
    '<br>Service: ' + (e.service||'—') +
    (e.message ? '<br><br><em>' + e.message + '</em>' : '');
  openModal('modalEnquiry');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
