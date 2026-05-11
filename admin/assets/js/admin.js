/* Ambozy CRM — admin.js */
(function () {
  'use strict';

  /* ── Sidebar toggle ── */
  const sidebar = document.getElementById('sidebar');
  const toggle  = document.getElementById('sidebarToggle');
  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  /* ── Dynamic line-item rows (invoice / bill builder) ── */
  function initLineItems() {
    const addBtn = document.getElementById('addLineItem');
    const tbody  = document.getElementById('lineItemsBody');
    if (!addBtn || !tbody) return;

    function calcRow(row) {
      const qty   = parseFloat(row.querySelector('.qty').value)   || 0;
      const price = parseFloat(row.querySelector('.price').value) || 0;
      const total = qty * price;
      row.querySelector('.row-total').value = total.toFixed(2);
      calcTotals();
    }

    function calcTotals() {
      let sub = 0;
      tbody.querySelectorAll('tr').forEach(function (row) {
        sub += parseFloat(row.querySelector('.row-total').value) || 0;
      });
      const vatRateEl = document.getElementById('vatRate');
      const vatRate   = vatRateEl ? parseFloat(vatRateEl.value) || 0 : 0;
      const vat       = sub * vatRate / 100;
      const total     = sub + vat;
      setVal('subtotal',  sub.toFixed(2));
      setVal('vatAmount', vat.toFixed(2));
      setVal('grandTotal', total.toFixed(2));
    }

    function setVal(id, val) {
      const el = document.getElementById(id);
      if (el) el.textContent = 'UGX ' + parseFloat(val).toLocaleString('en-UG', {minimumFractionDigits: 2});
      const hi = document.getElementById(id + 'Hidden');
      if (hi) hi.value = val;
    }

    function makeRow(data) {
      data = data || {};
      const tr  = document.createElement('tr');
      const idx = Date.now();
      tr.innerHTML = `
        <td><input class="form-control qty" type="number" name="qty[]" min="0" step="0.01" value="${data.qty||1}" required></td>
        <td><input class="form-control description" type="text" name="desc[]" placeholder="Description" value="${data.desc||''}" required></td>
        <td><input class="form-control price" type="number" name="price[]" min="0" step="1" value="${data.price||0}" required></td>
        <td><input class="form-control row-total" type="number" name="total[]" readonly tabindex="-1" value="${data.total||0}"></td>
        <td><button type="button" class="rm-row btn btn-icon btn-danger" title="Remove">✕</button></td>`;
      tr.querySelector('.qty').addEventListener('input', function () { calcRow(tr); });
      tr.querySelector('.price').addEventListener('input', function () { calcRow(tr); });
      tr.querySelector('.rm-row').addEventListener('click', function () {
        if (tbody.querySelectorAll('tr').length > 1) { tr.remove(); calcTotals(); }
      });
      return tr;
    }

    // seed existing rows
    tbody.querySelectorAll('tr').forEach(function (tr) {
      tr.querySelector('.qty')  && tr.querySelector('.qty').addEventListener('input', function () { calcRow(tr); });
      tr.querySelector('.price') && tr.querySelector('.price').addEventListener('input', function () { calcRow(tr); });
      tr.querySelector('.rm-row') && tr.querySelector('.rm-row').addEventListener('click', function () {
        if (tbody.querySelectorAll('tr').length > 1) { tr.remove(); calcTotals(); }
      });
    });

    addBtn.addEventListener('click', function () { tbody.appendChild(makeRow()); });
    document.getElementById('vatRate') && document.getElementById('vatRate').addEventListener('input', calcTotals);
    calcTotals();
  }
  initLineItems();

  /* ── Modal ── */
  window.openModal = function (id) {
    const m = document.getElementById(id);
    if (m) m.classList.add('open');
  };
  window.closeModal = function (id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove('open');
  };
  document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });
  document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
    btn.addEventListener('click', function () { openModal(btn.dataset.modalOpen); });
  });
  document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () { closeModal(btn.dataset.modalClose); });
  });

  /* ── Auto-dismiss alerts ── */
  document.querySelectorAll('.alert').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .4s';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 400);
    }, 5000);
  });

  /* ── Confirm dangerous actions ── */
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
  });

  /* ── PAYE / NSSF live calculator (payroll form) ── */
  function initPayrollCalc() {
    const grossInput = document.getElementById('calcGross');
    if (!grossInput) return;
    grossInput.addEventListener('input', function () {
      const gross = parseFloat(grossInput.value) || 0;
      const paye  = computePAYE(gross);
      const nssf_emp = gross * 0.05;
      const nssf_er  = gross * 0.10;
      const net  = gross - paye - nssf_emp;
      setField('calcPAYE',      paye.toFixed(0));
      setField('calcNSSFEmp',   nssf_emp.toFixed(0));
      setField('calcNSSFEr',    nssf_er.toFixed(0));
      setField('calcNet',       net.toFixed(0));
    });

    function setField(id, val) {
      const el = document.getElementById(id);
      if (el) el.value = val;
    }

    function computePAYE(gross) {
      if (gross <= 235000) return 0;
      if (gross <= 335000) return (gross - 235000) * 0.10;
      if (gross <= 410000) return 10000 + (gross - 335000) * 0.20;
      return 25000 + (gross - 410000) * 0.30;
    }
  }
  initPayrollCalc();

  /* ── Search filter (client-side table filter) ── */
  const searchInput = document.getElementById('tableSearch');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      const q = searchInput.value.toLowerCase();
      document.querySelectorAll('.data-table tbody tr').forEach(function (tr) {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

})();
