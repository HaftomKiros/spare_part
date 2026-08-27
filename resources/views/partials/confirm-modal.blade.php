{{--
    Reusable confirmation modal.
    Include once per page. Trigger via window.confirmModal(message, onConfirm, options).
    Options: { title, icon, iconColor, confirmText, confirmClass }
--}}
<div id="confirmModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
<div style="background:#fff;border-radius:16px;width:100%;max-width:420px;margin:16px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden">

    {{-- Header --}}
    <div id="confirmModalHeader" style="padding:20px 24px 16px;display:flex;align-items:center;gap:12px">
        <div id="confirmModalIconWrap" style="width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i id="confirmModalIcon" class="fa fa-triangle-exclamation" style="font-size:1.1rem;color:#fff"></i>
        </div>
        <div>
            <div id="confirmModalTitle" style="font-weight:700;font-size:1rem;color:#1e293b">Confirm Action</div>
            <div style="font-size:.8rem;color:#64748b">This action cannot be undone.</div>
        </div>
    </div>

    {{-- Body --}}
    <div style="padding:0 24px 20px">
        <p id="confirmModalMessage" style="margin:0;color:#374151;font-size:.95rem;line-height:1.6"></p>
    </div>

    {{-- Footer --}}
    <div style="padding:0 24px 20px;display:flex;gap:10px;justify-content:flex-end">
        <button id="confirmModalCancel" type="button"
                style="padding:9px 22px;border-radius:8px;border:1.5px solid #e2e6f0;background:#fff;color:#64748b;font-weight:600;cursor:pointer;font-size:.9rem">
            Cancel
        </button>
        <button id="confirmModalOk" type="button"
                style="padding:9px 22px;border-radius:8px;border:none;font-weight:600;cursor:pointer;font-size:.9rem;color:#fff">
            Confirm
        </button>
    </div>
</div>
</div>

<script>
window.confirmModal = function(message, onConfirm, opts) {
    opts = opts || {};
    const modal       = document.getElementById('confirmModal');
    const title       = document.getElementById('confirmModalTitle');
    const msg         = document.getElementById('confirmModalMessage');
    const icon        = document.getElementById('confirmModalIcon');
    const iconWrap    = document.getElementById('confirmModalIconWrap');
    const header      = document.getElementById('confirmModalHeader');
    const btnOk       = document.getElementById('confirmModalOk');
    const btnCancel   = document.getElementById('confirmModalCancel');

    // Apply options
    title.textContent         = opts.title       || 'Confirm Action';
    msg.textContent           = message;
    icon.className            = 'fa ' + (opts.icon || 'fa-triangle-exclamation');
    const color               = opts.iconColor   || '#ef4444';
    iconWrap.style.background = color;
    header.style.background   = hexToRgba(color, 0.06);
    btnOk.textContent         = opts.confirmText  || 'Confirm';
    btnOk.style.background    = opts.confirmClass === 'danger' ? '#ef4444'
                               : opts.confirmClass === 'warning' ? '#f59e0b'
                               : '#6366f1';

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    function close() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        btnOk.onclick      = null;
        btnCancel.onclick  = null;
    }
    btnOk.onclick     = function() { close(); onConfirm(); };
    btnCancel.onclick = close;
    modal.onclick     = function(e) { if (e.target === modal) close(); };
};

function hexToRgba(hex, alpha) {
    const r = parseInt(hex.slice(1,3),16);
    const g = parseInt(hex.slice(3,5),16);
    const b = parseInt(hex.slice(5,7),16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
}
</script>
