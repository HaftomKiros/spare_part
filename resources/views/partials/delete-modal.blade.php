{{-- Reusable delete confirmation modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10"
                          style="width:56px;height:56px">
                        <i class="fa fa-trash text-danger fs-4"></i>
                    </span>
                </div>
                <h6 class="fw-bold mb-2">Delete Record</h6>
                <p class="text-muted small mb-4" id="deleteModalMessage">
                    Are you sure you want to delete this record? This action cannot be undone.
                </p>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-danger px-4">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-delete-url]').forEach(btn => {
    btn.addEventListener('click', function () {
        const url = this.dataset.deleteUrl;
        const msg = this.dataset.deleteMessage || 'Are you sure you want to delete this record?';
        document.getElementById('deleteForm').action = url;
        document.getElementById('deleteModalMessage').textContent = msg;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });
});
</script>
@endpush
