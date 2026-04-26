/* ==========================================================================
   InvenTrack — main.js
   Shared behaviours: sidebar toggle, alert close, delete-confirm modal,
   password show/hide, tab switching, dynamic item rows, line totals.
   All code runs inside DOMContentLoaded.
   ========================================================================== */

document.addEventListener('DOMContentLoaded', function () {

  /* ------------------------------------------------------------------ */
  /* 1. Sidebar toggle                                                   */
  /* ------------------------------------------------------------------ */

  var hamburger = document.querySelector('.btn-hamburger');

  if (hamburger) {
    hamburger.addEventListener('click', function () {
      document.body.classList.toggle('sidebar-open');
    });
  }

  // Close drawer when user clicks the translucent overlay (::after pseudo).
  // The pseudo-element is not directly clickable, so we detect a click on
  // <body> that lands outside the .sidebar element.
  document.body.addEventListener('click', function (e) {
    if (!document.body.classList.contains('sidebar-open')) return;

    var sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    // If click target is outside the sidebar AND not the hamburger button
    if (!sidebar.contains(e.target) && !e.target.closest('.btn-hamburger')) {
      document.body.classList.remove('sidebar-open');
    }
  });

  /* ------------------------------------------------------------------ */
  /* 2. Alert close buttons                                              */
  /* ------------------------------------------------------------------ */

  document.querySelectorAll('.alert').forEach(function (alert) {
    var closeBtn = alert.querySelector('.alert-close');
    if (!closeBtn) return;

    closeBtn.addEventListener('click', function () {
      alert.style.display = 'none';
    });
  });

  /* ------------------------------------------------------------------ */
  /* 3. Delete confirmation modal                                        */
  /* ------------------------------------------------------------------ */

  var deleteBackdrop = document.getElementById('deleteModal');

  // The form that will be submitted when user confirms deletion
  var pendingDeleteForm = null;

  if (deleteBackdrop) {
    var confirmDeleteBtn  = document.getElementById('confirmDeleteBtn');
    var cancelDeleteBtns  = deleteBackdrop.querySelectorAll('[data-modal-close]');
    var modalItemName     = document.getElementById('modalItemName');

    // Open modal when any [data-confirm-delete] button is clicked
    document.querySelectorAll('[data-confirm-delete]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();

        // Store reference to the form this button belongs to
        pendingDeleteForm = btn.closest('form') || btn.form || null;

        // Show item name in the modal message if provided
        if (modalItemName) {
          var name = btn.getAttribute('data-item-name') || 'this record';
          modalItemName.textContent = name;
        }

        openModal(deleteBackdrop);
      });
    });

    // Confirm: submit the stored form
    if (confirmDeleteBtn) {
      confirmDeleteBtn.addEventListener('click', function () {
        if (pendingDeleteForm) {
          pendingDeleteForm.submit();
        }
        closeModal(deleteBackdrop);
      });
    }

    // Cancel buttons and backdrop click
    cancelDeleteBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        closeModal(deleteBackdrop);
        pendingDeleteForm = null;
      });
    });

    deleteBackdrop.addEventListener('click', function (e) {
      if (e.target === deleteBackdrop) {
        closeModal(deleteBackdrop);
        pendingDeleteForm = null;
      }
    });
  }

  // Escape key closes any open modal
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-backdrop.is-open').forEach(function (m) {
        closeModal(m);
        pendingDeleteForm = null;
      });
    }
  });

  /**
   * openModal(backdrop) — adds .is-open, traps focus inside .modal
   */
  function openModal(backdrop) {
    backdrop.classList.add('is-open');

    // Move focus into the modal
    var modal = backdrop.querySelector('.modal');
    if (modal) {
      var firstFocusable = modal.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      if (firstFocusable) {
        firstFocusable.focus();
      }
    }

    // Basic focus trap: keep Tab inside the modal
    backdrop._focusTrapHandler = function (e) {
      if (e.key !== 'Tab' || !modal) return;

      var focusable = Array.from(
        modal.querySelectorAll(
          'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )
      );

      if (focusable.length === 0) { e.preventDefault(); return; }

      var first = focusable[0];
      var last  = focusable[focusable.length - 1];

      if (e.shiftKey) {
        if (document.activeElement === first) {
          e.preventDefault();
          last.focus();
        }
      } else {
        if (document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    };

    document.addEventListener('keydown', backdrop._focusTrapHandler);
  }

  /**
   * closeModal(backdrop) — removes .is-open, releases focus trap
   */
  function closeModal(backdrop) {
    backdrop.classList.remove('is-open');

    if (backdrop._focusTrapHandler) {
      document.removeEventListener('keydown', backdrop._focusTrapHandler);
      backdrop._focusTrapHandler = null;
    }
  }

  /* ------------------------------------------------------------------ */
  /* 4. Password show / hide toggle                                      */
  /* ------------------------------------------------------------------ */

  document.querySelectorAll('.password-toggle').forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      var wrapper = toggle.closest('.password-wrapper');
      if (!wrapper) return;

      var input = wrapper.querySelector('input');
      if (!input) return;

      var icon = toggle.querySelector('i');

      if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
          icon.classList.remove('fa-eye');
          icon.classList.add('fa-eye-slash');
        }
      } else {
        input.type = 'password';
        if (icon) {
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
        }
      }
    });
  });

  /* ------------------------------------------------------------------ */
  /* 5. Tab switching (dashboard / reports chart period)                */
  /* ------------------------------------------------------------------ */

  document.querySelectorAll('.tab-group').forEach(function (group) {
    group.querySelectorAll('.tab-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        // Deactivate all siblings in this group
        group.querySelectorAll('.tab-btn').forEach(function (b) {
          b.classList.remove('active');
        });

        // Activate clicked tab
        btn.classList.add('active');

        // Dispatch custom event so chart code can react
        var period = btn.getAttribute('data-period') || btn.textContent.trim();
        document.dispatchEvent(new CustomEvent('tabChange', {
          detail: { period: period }
        }));
      });
    });
  });

  /* ------------------------------------------------------------------ */
  /* 6. Dynamic sale / purchase item rows                               */
  /* ------------------------------------------------------------------ */

  var addRowBtn = document.querySelector('.btn-add-row');
  var itemsBody = document.querySelector('.items-table tbody');

  if (addRowBtn && itemsBody) {
    addRowBtn.addEventListener('click', function () {
      var rows = itemsBody.querySelectorAll('.item-row');
      if (rows.length === 0) return;

      // Clone the last row
      var lastRow = rows[rows.length - 1];
      var newRow  = lastRow.cloneNode(true);

      // Clear all input values in the clone
      newRow.querySelectorAll('input, select, textarea').forEach(function (field) {
        field.value = '';
        field.classList.remove('invalid');
      });

      // Clear calculated subtotal cell
      var totalCell = newRow.querySelector('.row-total');
      if (totalCell) totalCell.textContent = '0.00';

      // Update name indices so each row's inputs have a unique array index
      updateRowIndices(itemsBody);

      itemsBody.appendChild(newRow);

      // Re-attach remove listeners
      attachRemoveListeners();

      // Re-attach calculation listeners
      attachCalcListeners();
    });
  }

  // Remove row button
  function attachRemoveListeners() {
    if (!itemsBody) return;

    itemsBody.querySelectorAll('.btn-remove-row').forEach(function (btn) {
      // Clone to remove any previously attached listener
      var fresh = btn.cloneNode(true);
      btn.parentNode.replaceChild(fresh, btn);

      fresh.addEventListener('click', function () {
        var rows = itemsBody.querySelectorAll('.item-row');
        if (rows.length <= 1) return; // Keep minimum 1 row

        var row = fresh.closest('.item-row');
        if (row) {
          row.remove();
          updateRowIndices(itemsBody);
          recalcGrandTotal();
        }
      });
    });
  }

  // Update name="items[N][field]" indices so PHP receives a clean array
  function updateRowIndices(tbody) {
    tbody.querySelectorAll('.item-row').forEach(function (row, index) {
      row.querySelectorAll('[name]').forEach(function (field) {
        // Replace the numeric index: items[0][qty] → items[1][qty]
        field.name = field.name.replace(/\[\d+\]/, '[' + index + ']');
      });
    });
  }

  // Run on page load to wire up any pre-existing rows
  attachRemoveListeners();

  /* ------------------------------------------------------------------ */
  /* 7. Line total calculation                                           */
  /* ------------------------------------------------------------------ */

  function attachCalcListeners() {
    if (!itemsBody) return;

    itemsBody.querySelectorAll('.item-row').forEach(function (row) {
      var qtyInput   = row.querySelector('.row-qty');
      var priceInput = row.querySelector('.row-price');

      if (qtyInput)   qtyInput.addEventListener('input', function () { calcRowTotal(row); });
      if (priceInput) priceInput.addEventListener('input', function () { calcRowTotal(row); });
    });
  }

  function calcRowTotal(row) {
    var qtyInput   = row.querySelector('.row-qty');
    var priceInput = row.querySelector('.row-price');
    var totalCell  = row.querySelector('.row-total');

    if (!qtyInput || !priceInput || !totalCell) return;

    var qty   = parseFloat(qtyInput.value)   || 0;
    var price = parseFloat(priceInput.value) || 0;
    var sub   = qty * price;

    totalCell.textContent = sub.toFixed(2);
    recalcGrandTotal();
  }

  function recalcGrandTotal() {
    if (!itemsBody) return;

    var total = 0;
    itemsBody.querySelectorAll('.row-total').forEach(function (cell) {
      total += parseFloat(cell.textContent) || 0;
    });

    var grandTotalEl = document.querySelector('.grand-total');
    if (grandTotalEl) grandTotalEl.textContent = total.toFixed(2);
  }

  // Wire up any rows already present on page load
  attachCalcListeners();

});
