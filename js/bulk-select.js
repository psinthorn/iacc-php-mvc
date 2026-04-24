/**
 * BulkSelect — Reusable multi-row bulk selection component
 *
 * Usage:
 *   BulkSelect.init({
 *     tableId:   'tour-bookings-table',
 *     actions:   [{ key, label, icon, class, confirm?, modal? }],
 *     actionUrl: 'index.php?page=tour_booking_bulk',
 *     csrfToken: '...'
 *   });
 *
 * Action types:
 *   confirm: true        — shows a confirm dialog before dispatching
 *   modal: 'payment'     — opens a modal to collect extra fields before dispatching
 */
const BulkSelect = (function () {
  'use strict';

  let _cfg       = {};
  let _table     = null;
  let _toolbar   = null;
  let _selectAll = null;
  let _selected  = new Set();

  // ─── Helpers ──────────────────────────────────────────────────────────────
  function rows()       { return _table.querySelectorAll('tbody .bulk-select-row'); }
  function checkedRows(){ return _table.querySelectorAll('tbody .bulk-select-row:checked'); }

  // ─── Selection ────────────────────────────────────────────────────────────
  function syncHeaderCheckbox() {
    const total = rows().length, checked = checkedRows().length;
    _selectAll.checked       = checked > 0 && checked === total;
    _selectAll.indeterminate = checked > 0 && checked < total;
  }

  function syncToolbar() {
    const count   = _selected.size;
    const countEl = _toolbar.querySelector('.bulk-toolbar__count');
    if (countEl) countEl.textContent = count + ' selected';
    _toolbar.classList.toggle('bulk-toolbar--visible', count > 0);
  }

  function highlightRow(cb) {
    const tr = cb.closest('tr');
    if (tr) tr.classList.toggle('bulk-selected', cb.checked);
  }

  function handleRowChange(cb) {
    cb.checked ? _selected.add(cb.value) : _selected.delete(cb.value);
    highlightRow(cb);
    syncHeaderCheckbox();
    syncToolbar();
  }

  function handleSelectAll(e) {
    const checked = e.target.checked;
    rows().forEach(cb => {
      cb.checked = checked;
      checked ? _selected.add(cb.value) : _selected.delete(cb.value);
      highlightRow(cb);
    });
    syncToolbar();
  }

  function resetSelection() {
    _selected.clear();
    rows().forEach(cb => { cb.checked = false; highlightRow(cb); });
    if (_selectAll) { _selectAll.checked = false; _selectAll.indeterminate = false; }
    syncToolbar();
  }

  // ─── Flash ────────────────────────────────────────────────────────────────
  function showFlash(type, message) {
    const div = document.createElement('div');
    const bg  = type === 'success' ? '#059669' : type === 'warning' ? '#d97706' : '#dc2626';
    div.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;padding:12px 20px;border-radius:8px;font-size:14px;font-weight:600;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.2);background:' + bg;
    div.textContent = message;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 4000);
  }

  // ─── HTTP dispatch ────────────────────────────────────────────────────────
  async function postAction(extraParams) {
    const body = new URLSearchParams();
    body.append('csrf_token', _cfg.csrfToken);
    _selected.forEach(id => body.append('ids[]', id));
    Object.entries(extraParams).forEach(([k, v]) => body.append(k, v));

    const btns = _toolbar.querySelectorAll('.bulk-toolbar__actions button');
    btns.forEach(b => b.disabled = true);

    try {
      const r  = await fetch(_cfg.actionUrl, { method: 'POST', body });
      const ct = r.headers.get('content-type') || '';

      // CSV export streams directly — trigger download, no JSON response
      if (ct.includes('text/csv')) {
        const blob = await r.blob();
        const url  = URL.createObjectURL(blob);
        const a    = Object.assign(document.createElement('a'), { href: url, download: 'export.csv' });
        a.click();
        URL.revokeObjectURL(url);
        resetSelection();
        return;
      }

      const data = await r.json();
      let msg = data.message || 'Done';
      if (data.note) msg += data.note;
      showFlash(data.success ? 'success' : 'error', msg);
      if (data.errors?.length > 0) console.warn('Bulk action warnings:', data.errors);
      resetSelection();
      if (data.success && data.reload !== false) setTimeout(() => location.reload(), 900);

    } catch {
      showFlash('error', 'Request failed. Please try again.');
    } finally {
      btns.forEach(b => b.disabled = false);
    }
  }

  // ─── Action dispatch ──────────────────────────────────────────────────────
  function dispatchAction(actionCfg) {
    if (_selected.size === 0) return;

    // Confirm dialog for destructive actions
    if (actionCfg.confirm) {
      const n  = _selected.size;
      const ok = window.confirm(actionCfg.confirmMsg
        ? actionCfg.confirmMsg.replace('{n}', n)
        : 'Apply "' + actionCfg.label + '" to ' + n + ' item' + (n > 1 ? 's' : '') + '?');
      if (!ok) return;
    }

    // Modal for actions needing extra input
    if (actionCfg.modal === 'payment') {
      openPaymentModal(actionCfg);
      return;
    }

    postAction({ action: actionCfg.key });
  }

  // ─── Payment modal ────────────────────────────────────────────────────────
  function openPaymentModal(actionCfg) {
    const modal = document.getElementById('bulkPaymentModal');
    if (!modal) { postAction({ action: actionCfg.key }); return; }

    // Auto-fill balance: if exactly 1 row selected, read its data-balance attribute
    const amountInput = document.getElementById('bulkPayAmount');
    if (amountInput) {
      if (_selected.size === 1) {
        const id  = Array.from(_selected)[0];
        const cb  = _table.querySelector('.bulk-select-row[value="' + id + '"]');
        const bal = cb ? parseFloat(cb.dataset.balance || '0') : 0;
        amountInput.value = bal > 0 ? bal.toFixed(2) : '';
      } else {
        amountInput.value = '';
      }
    }

    modal.style.display = 'flex';

    document.getElementById('bulkPayCancel').onclick = () => {
      modal.style.display = 'none';
    };

    document.getElementById('bulkPayConfirm').onclick = () => {
      const amount = parseFloat(document.getElementById('bulkPayAmount').value);
      const method = document.getElementById('bulkPayMethod').value;
      const date   = document.getElementById('bulkPayDate').value;
      const notes  = document.getElementById('bulkPayNotes').value;

      if (!amount || amount <= 0) {
        showFlash('error', 'Please enter a valid amount.');
        return;
      }

      modal.style.display = 'none';
      postAction({ action: actionCfg.key, amount, method, payment_date: date, notes });
    };

    // Close on backdrop click
    modal.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };
  }

  // ─── Toolbar build ────────────────────────────────────────────────────────
  function buildToolbarButtons() {
    const actionsEl = _toolbar.querySelector('.bulk-toolbar__actions');
    if (!actionsEl) return;
    actionsEl.innerHTML = '';

    _cfg.actions.forEach(a => {
      const btn     = document.createElement('button');
      btn.type      = 'button';
      btn.className = 'btn btn-sm ' + (a.class || 'btn-secondary');
      btn.innerHTML = '<i class="fa ' + a.icon + '"></i> ' + a.label;
      btn.addEventListener('click', () => dispatchAction(a));
      actionsEl.appendChild(btn);
    });

    const closeBtn = _toolbar.querySelector('.bulk-toolbar__close');
    if (closeBtn) closeBtn.addEventListener('click', resetSelection);
  }

  // ─── Init ─────────────────────────────────────────────────────────────────
  function init(cfg) {
    _cfg     = cfg;
    _table   = document.getElementById(cfg.tableId);
    _toolbar = document.querySelector('.bulk-toolbar');

    if (!_table || !_toolbar) {
      console.warn('BulkSelect: table #' + cfg.tableId + ' or .bulk-toolbar not found.');
      return;
    }

    _selectAll = _table.querySelector('.bulk-select-all');
    if (_selectAll) _selectAll.addEventListener('change', handleSelectAll);

    _table.querySelector('tbody').addEventListener('change', function (e) {
      if (e.target.classList.contains('bulk-select-row')) handleRowChange(e.target);
    });

    buildToolbarButtons();
  }

  return { init, reset: resetSelection };
}());
