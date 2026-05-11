<?php
defined('AMBOZY_CRM') or die();
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title><?= h($page_title ?? 'CRM') ?> — Ambozy CRM</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= admin_url('assets/css/admin.css') ?>">
</head>
<body>

<div class="crm-layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <img src="<?= admin_url('../assets/images/logo-white.png') ?>" alt="Ambozy" height="38">
      <span class="sidebar-tag">CRM</span>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Overview</div>
      <a href="<?= admin_url('dashboard.php') ?>" class="nav-item <?= $current_page==='dashboard'?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>

      <div class="nav-section-label">Sales</div>
      <a href="<?= admin_url('clients.php') ?>" class="nav-item <?= in_array($current_page,['clients','client-view'])?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Clients
      </a>
      <a href="<?= admin_url('invoices.php') ?>" class="nav-item <?= in_array($current_page,['invoices','invoice-new','invoice-view'])?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Invoices
      </a>
      <a href="<?= admin_url('enquiries.php') ?>" class="nav-item <?= $current_page==='enquiries'?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Enquiries
      </a>
      <a href="<?= admin_url('projects.php') ?>" class="nav-item <?= in_array($current_page,['projects','project-view'])?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Projects
      </a>

      <div class="nav-section-label">Procurement</div>
      <a href="<?= admin_url('suppliers.php') ?>" class="nav-item <?= in_array($current_page,['suppliers','supplier-view'])?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        Suppliers
      </a>

      <div class="nav-section-label">Finance</div>
      <a href="<?= admin_url('expenses.php') ?>" class="nav-item <?= $current_page==='expenses'?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Expenses
      </a>
      <a href="<?= admin_url('payroll.php') ?>" class="nav-item <?= in_array($current_page,['payroll','payroll-view'])?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        Payroll
      </a>
      <a href="<?= admin_url('statutory.php') ?>" class="nav-item <?= $current_page==='statutory'?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Statutory (URA/NSSF)
      </a>
      <a href="<?= admin_url('loans.php') ?>" class="nav-item <?= in_array($current_page,['loans','loan-view'])?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Loans
      </a>
      <a href="<?= admin_url('reports.php') ?>" class="nav-item <?= $current_page==='reports'?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Reports
      </a>

      <?php if(($_SESSION['admin_role']??'')==='admin'): ?>
      <div class="nav-section-label">Settings</div>
      <a href="<?= admin_url('employees.php') ?>" class="nav-item <?= in_array($current_page,['employees','employee-view'])?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
        Employees
      </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <span><?= h($_SESSION['admin_name'] ?? '') ?></span>
      <a href="<?= admin_url('index.php?logout=1') ?>" class="logout-link" title="Logout">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    </div>
  </aside>

  <!-- Main content -->
  <main class="crm-main">
    <header class="crm-topbar">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <h1 class="page-title"><?= h($page_title ?? '') ?></h1>
      <div class="topbar-right">
        <a href="<?= admin_url('../index.php') ?>" target="_blank" class="btn-sm btn-ghost">View Site ↗</a>
      </div>
    </header>
    <div class="crm-content">
      <?= flash_render() ?>
