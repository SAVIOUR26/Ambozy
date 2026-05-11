<?php
defined('AMBOZY_CRM') or die('Direct access not permitted.');

// ── URL helpers ──────────────────────────────────────────────────
function admin_url(string $path = ''): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    // When called from a nested page keep going up to admin root
    if (strpos($base, '/admin') === false) $base .= '/admin';
    return $base . '/' . ltrim($path, '/');
}

// ── Currency formatting (UGX) ────────────────────────────────────
function fmt_ugx(float $amount): string {
    return 'UGX ' . number_format($amount, 2);
}

function fmt_num(float $n, int $dp = 0): string {
    return number_format($n, $dp);
}

// ── Date helpers ─────────────────────────────────────────────────
function fmt_date(?string $d): string {
    return $d ? date('d M Y', strtotime($d)) : '—';
}

function month_name(int $m): string {
    return date('F', mktime(0, 0, 0, $m, 1));
}

// ── Uganda PAYE (Income Tax Act — monthly thresholds, UGX) ───────
// Thresholds (FY 2024/25): 0–235k @0%, 235k–335k @10%, 335k–410k @20%, >410k @30%
function calculate_paye(float $gross): float {
    if ($gross <= 235000)      return 0;
    if ($gross <= 335000)      return ($gross - 235000) * 0.10;
    if ($gross <= 410000)      return 10000 + ($gross - 335000) * 0.20;
    return 25000 + ($gross - 410000) * 0.30;
}

// ── Uganda NSSF ──────────────────────────────────────────────────
function calculate_nssf_employee(float $gross): float { return $gross * 0.05; }
function calculate_nssf_employer(float $gross): float { return $gross * 0.10; }

// ── Auto-generate reference numbers ──────────────────────────────
function next_invoice_number(PDO $pdo): string {
    $y = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE YEAR(created_at) = ?");
    $stmt->execute([$y]);
    $n = (int)$stmt->fetchColumn() + 1;
    return "INV-{$y}-" . str_pad($n, 4, '0', STR_PAD_LEFT);
}

function next_project_number(PDO $pdo): string {
    $y    = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE YEAR(created_at) = ?");
    $stmt->execute([$y]);
    $n = (int)$stmt->fetchColumn() + 1;
    return "PRJ-{$y}-" . str_pad($n, 4, '0', STR_PAD_LEFT);
}

function next_bill_number(PDO $pdo): string {
    $y    = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM supplier_bills WHERE YEAR(created_at) = ?");
    $stmt->execute([$y]);
    $n = (int)$stmt->fetchColumn() + 1;
    return "BILL-{$y}-" . str_pad($n, 4, '0', STR_PAD_LEFT);
}

function next_payment_number(PDO $pdo): string {
    $stmt = $pdo->query("SELECT COUNT(*) FROM payments_received");
    $n = (int)$stmt->fetchColumn() + 1;
    return "PAY-" . str_pad($n, 5, '0', STR_PAD_LEFT);
}

// ── Recalculate invoice balance after payment ─────────────────────
function sync_invoice_balance(int $invoice_id, PDO $pdo): void {
    $paid = (float)$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments_received WHERE invoice_id = ?")
        ->execute([$invoice_id]) ? $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments_received WHERE invoice_id = {$invoice_id}")->fetchColumn() : 0;
    // use safe approach
    $stmt = $pdo->prepare("SELECT total FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
    $total = (float)($stmt->fetchColumn() ?: 0);

    $stmt2 = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments_received WHERE invoice_id = ?");
    $stmt2->execute([$invoice_id]);
    $paid  = (float)$stmt2->fetchColumn();
    $bal   = $total - $paid;
    if ($bal < 0) $bal = 0;

    $status = $paid <= 0 ? 'sent' : ($bal <= 0 ? 'paid' : 'partial');
    $pdo->prepare("UPDATE invoices SET amount_paid=?, balance=?, status=? WHERE id=?")
        ->execute([$paid, $bal, $status, $invoice_id]);
}

// ── Recalculate supplier bill balance after payment ───────────────
function sync_bill_balance(int $bill_id, PDO $pdo): void {
    $stmt = $pdo->prepare("SELECT total FROM supplier_bills WHERE id = ?");
    $stmt->execute([$bill_id]);
    $total = (float)($stmt->fetchColumn() ?: 0);

    $stmt2 = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments_to_suppliers WHERE bill_id = ?");
    $stmt2->execute([$bill_id]);
    $paid  = (float)$stmt2->fetchColumn();
    $bal   = max(0, $total - $paid);

    $status = $paid <= 0 ? 'pending' : ($bal <= 0 ? 'paid' : 'partial');
    $pdo->prepare("UPDATE supplier_bills SET amount_paid=?, balance=?, status=? WHERE id=?")
        ->execute([$paid, $bal, $status, $bill_id]);
}

// ── Recalculate loan balance after repayment ──────────────────────
function sync_loan_balance(int $loan_id, PDO $pdo): void {
    $stmt = $pdo->prepare("SELECT principal FROM loans WHERE id = ?");
    $stmt->execute([$loan_id]);
    $principal = (float)($stmt->fetchColumn() ?: 0);

    $stmt2 = $pdo->prepare("SELECT COALESCE(SUM(principal_paid),0) FROM loan_repayments WHERE loan_id = ?");
    $stmt2->execute([$loan_id]);
    $repaid = (float)$stmt2->fetchColumn();
    $bal    = max(0, $principal - $repaid);

    $stmt3 = $pdo->prepare("SELECT COALESCE(SUM(total_paid),0) FROM loan_repayments WHERE loan_id = ?");
    $stmt3->execute([$loan_id]);
    $total_repaid = (float)$stmt3->fetchColumn();

    $status = $bal <= 0 ? 'settled' : 'active';
    $pdo->prepare("UPDATE loans SET total_repaid=?, balance_outstanding=?, status=? WHERE id=?")
        ->execute([$total_repaid, $bal, $status, $loan_id]);
}

// ── Status badge HTML ─────────────────────────────────────────────
function status_badge(string $status): string {
    $map = [
        'draft'       => ['Draft',       'badge-grey'],
        'sent'        => ['Sent',         'badge-blue'],
        'partial'     => ['Partial',      'badge-orange'],
        'paid'        => ['Paid',         'badge-green'],
        'overdue'     => ['Overdue',      'badge-red'],
        'cancelled'   => ['Cancelled',    'badge-grey'],
        'pending'     => ['Pending',      'badge-blue'],
        'new'         => ['New',          'badge-blue'],
        'in_progress' => ['In Progress',  'badge-orange'],
        'on_hold'     => ['On Hold',      'badge-grey'],
        'completed'   => ['Completed',    'badge-green'],
        'active'      => ['Active',       'badge-green'],
        'settled'     => ['Settled',      'badge-green'],
        'defaulted'   => ['Defaulted',    'badge-red'],
        'approved'    => ['Approved',     'badge-green'],
    ];
    [$label, $cls] = $map[$status] ?? [ucfirst($status), 'badge-grey'];
    return "<span class=\"badge {$cls}\">{$label}</span>";
}

// ── Flash messages ────────────────────────────────────────────────
function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function flash_render(): string {
    if (!isset($_SESSION['flash'])) return '';
    ['msg' => $msg, 'type' => $type] = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $icon = $type === 'success' ? '✓' : '✕';
    return "<div class=\"alert alert-{$type}\"><span>{$icon}</span> " . htmlspecialchars($msg) . "</div>";
}

// ── Pagination ───────────────────────────────────────────────────
function paginate(int $total, int $per_page, int $page): array {
    $pages = max(1, (int)ceil($total / $per_page));
    $page  = max(1, min($page, $pages));
    return ['total' => $total, 'pages' => $pages, 'page' => $page, 'offset' => ($page - 1) * $per_page];
}

function pagination_links(array $pg, string $base_url): string {
    if ($pg['pages'] <= 1) return '';
    $html = '<div class="pagination">';
    for ($i = 1; $i <= $pg['pages']; $i++) {
        $active = $i === $pg['page'] ? ' active' : '';
        $html  .= "<a href=\"{$base_url}&page={$i}\" class=\"page-link{$active}\">{$i}</a>";
    }
    return $html . '</div>';
}

// ── Safe HTML output ──────────────────────────────────────────────
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
