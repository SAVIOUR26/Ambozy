<?php
define('AMBOZY_CRM', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
auth_check(); auth_require_admin();

$pdo = get_pdo();
$page_title = 'Employees';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'save_employee' && csrf_verify()) {
    $emp_id = (int)($_POST['emp_id'] ?? 0);
    $data = [
        trim($_POST['full_name']??''), trim($_POST['position']??''), trim($_POST['email']??''),
        trim($_POST['phone']??''), trim($_POST['national_id']??''), trim($_POST['nssf_number']??''),
        trim($_POST['tin']??''), (float)($_POST['gross_salary']??0),
        trim($_POST['bank_name']??''), trim($_POST['bank_account']??''),
        $_POST['start_date']??null, trim($_POST['notes']??'')
    ];
    if ($emp_id) {
        $pdo->prepare("UPDATE employees SET full_name=?,position=?,email=?,phone=?,national_id=?,nssf_number=?,tin=?,gross_salary=?,bank_name=?,bank_account=?,start_date=?,notes=? WHERE id=?")
            ->execute([...$data,$emp_id]);
        flash('Employee updated.');
    } else {
        $pdo->prepare("INSERT INTO employees (full_name,position,email,phone,national_id,nssf_number,tin,gross_salary,bank_name,bank_account,start_date,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute($data);
        flash('Employee added.');
    }
    header('Location: ' . admin_url('employees.php')); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'toggle_active' && csrf_verify()) {
    $emp_id = (int)($_POST['emp_id']??0);
    $pdo->prepare("UPDATE employees SET active = NOT active WHERE id=?")->execute([$emp_id]);
    header('Location: ' . admin_url('employees.php')); exit;
}

$employees = $pdo->query("SELECT * FROM employees ORDER BY full_name")->fetchAll();
$edit_emp  = null;
if (isset($_GET['edit'])) { foreach($employees as $e) { if($e['id']==(int)$_GET['edit']) { $edit_emp=$e; break; } } }

include __DIR__ . '/includes/header.php';
?>

<div class="page-actions">
  <div></div>
  <button class="btn btn-primary" data-modal-open="modalEmployee" onclick="clearForm()">+ Add Employee</button>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Name</th><th>Position</th><th>Phone</th><th class="num">Gross Salary</th><th>NSSF No.</th><th>TIN</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($employees as $emp): ?>
        <tr>
          <td style="font-weight:600"><?= h($emp['full_name']) ?></td>
          <td><?= h($emp['position']?:'—') ?></td>
          <td><?= h($emp['phone']?:'—') ?></td>
          <td class="num"><?= fmt_ugx($emp['gross_salary']) ?></td>
          <td><?= h($emp['nssf_number']?:'—') ?></td>
          <td><?= h($emp['tin']?:'—') ?></td>
          <td><?= $emp['active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-grey">Inactive</span>' ?></td>
          <td class="actions">
            <button class="btn btn-sm btn-outline" onclick="editEmployee(<?= htmlspecialchars(json_encode($emp)) ?>)">Edit</button>
            <form method="POST" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_active"><input type="hidden" name="emp_id" value="<?= $emp['id'] ?>"><button type="submit" class="btn btn-sm btn-ghost"><?= $emp['active']?'Deactivate':'Activate' ?></button></form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$employees): ?><tr><td colspan="8" class="empty-state">No employees</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="alert alert-info mt-2" style="font-size:.82rem">
  <strong>PAYE Reminder:</strong> Uganda PAYE is deducted at source and remitted to URA by the 15th of the following month.
  <strong>NSSF:</strong> Employee contribution 5% + Employer 10% = 15% of gross, remitted by the 15th.
</div>

<!-- Employee Modal -->
<div class="modal-overlay" id="modalEmployee">
  <div class="modal">
    <div class="modal-header"><span class="modal-title" id="modalEmpTitle">New Employee</span><button class="modal-close" data-modal-close="modalEmployee">✕</button></div>
    <div class="modal-body">
      <form method="POST" id="empForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_employee">
        <input type="hidden" name="emp_id" id="empId" value="0">
        <div class="form-grid form-grid-2">
          <div class="form-group full"><label class="form-label">Full Name *</label><input class="form-control" type="text" name="full_name" id="empName" required></div>
          <div class="form-group"><label class="form-label">Position / Role</label><input class="form-control" type="text" name="position" id="empPosition"></div>
          <div class="form-group"><label class="form-label">Phone</label><input class="form-control" type="tel" name="phone" id="empPhone"></div>
          <div class="form-group"><label class="form-label">Email</label><input class="form-control" type="email" name="email" id="empEmail"></div>
          <div class="form-group"><label class="form-label">National ID</label><input class="form-control" type="text" name="national_id" id="empNationalId"></div>
          <div class="form-group"><label class="form-label">NSSF Number</label><input class="form-control" type="text" name="nssf_number" id="empNssf"></div>
          <div class="form-group"><label class="form-label">TIN (URA)</label><input class="form-control" type="text" name="tin" id="empTin"></div>
          <div class="form-group"><label class="form-label">Gross Monthly Salary (UGX) *</label><input class="form-control" type="number" name="gross_salary" id="empSalary" min="0" step="1000" required></div>
          <div class="form-group"><label class="form-label">Bank Name</label><input class="form-control" type="text" name="bank_name" id="empBankName"></div>
          <div class="form-group"><label class="form-label">Account Number</label><input class="form-control" type="text" name="bank_account" id="empBankAcct"></div>
          <div class="form-group"><label class="form-label">Start Date</label><input class="form-control" type="date" name="start_date" id="empStartDate"></div>
          <div class="form-group full"><label class="form-label">Notes</label><textarea class="form-control" name="notes" id="empNotes" rows="2"></textarea></div>
        </div>
        <div class="mt-2"><button type="submit" class="btn btn-primary">Save Employee</button></div>
      </form>
    </div>
  </div>
</div>
<script>
function clearForm() {
  document.getElementById('modalEmpTitle').textContent = 'New Employee';
  document.getElementById('empId').value = 0;
  ['empName','empPosition','empPhone','empEmail','empNationalId','empNssf','empTin','empBankName','empBankAcct','empNotes'].forEach(function(id){document.getElementById(id).value='';});
  document.getElementById('empSalary').value = 0;
  document.getElementById('empStartDate').value = '';
}
function editEmployee(emp) {
  document.getElementById('modalEmpTitle').textContent = 'Edit Employee';
  document.getElementById('empId').value = emp.id;
  document.getElementById('empName').value = emp.full_name || '';
  document.getElementById('empPosition').value = emp.position || '';
  document.getElementById('empPhone').value = emp.phone || '';
  document.getElementById('empEmail').value = emp.email || '';
  document.getElementById('empNationalId').value = emp.national_id || '';
  document.getElementById('empNssf').value = emp.nssf_number || '';
  document.getElementById('empTin').value = emp.tin || '';
  document.getElementById('empSalary').value = emp.gross_salary || 0;
  document.getElementById('empBankName').value = emp.bank_name || '';
  document.getElementById('empBankAcct').value = emp.bank_account || '';
  document.getElementById('empStartDate').value = emp.start_date || '';
  document.getElementById('empNotes').value = emp.notes || '';
  openModal('modalEmployee');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
