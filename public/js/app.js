/* =====================================================
   Ashu Spare Part — Main JS
   ===================================================== */

document.addEventListener('DOMContentLoaded', function () {

    // ── Auto-dismiss alerts ───────────────────────────
    document.querySelectorAll('.alert.alert-success').forEach(el => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert && bsAlert.close();
        }, 4000);
    });

    // ── Confirm Delete (data-confirm attribute) ───────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // ── Tooltips ──────────────────────────────────────
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

});
