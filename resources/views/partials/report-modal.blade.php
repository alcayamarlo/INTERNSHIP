{{-- One "Report" dialog per page; buttons with data-report-type / data-report-id fill it in. Admins only see reports. --}}
<div class="modal fade" id="reportContentModal" tabindex="-1" aria-labelledby="reportContentTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('content-reports.store') }}">
                @csrf
                <input type="hidden" name="type" data-report-field="type">
                <input type="hidden" name="id" data-report-field="id">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="reportContentTitle">Report <span data-report-name>this</span></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Only administrators see reports. The person you report is not told who reported them.</p>
                    <label class="form-label" for="reportReason">What's wrong? <span class="text-danger" aria-hidden="true">*</span></label>
                    <select id="reportReason" name="reason" class="form-select mb-3" required>
                        <option value="">Choose a reason…</option>
                        @foreach(\App\Models\ContentReport::REASONS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="form-label" for="reportDetails">Details (optional)</label>
                    <textarea id="reportDetails" name="details" rows="3" maxlength="1000" class="form-control" placeholder="What happened? Include anything that helps an administrator check it."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Send report</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-report-type]');
        const modalElement = document.getElementById('reportContentModal');
        if (!button || !modalElement || !window.bootstrap) return;
        modalElement.querySelector('[data-report-field="type"]').value = button.dataset.reportType;
        modalElement.querySelector('[data-report-field="id"]').value = button.dataset.reportId;
        modalElement.querySelector('[data-report-name]').textContent = button.dataset.reportLabel || 'this';
        modalElement.querySelector('#reportReason').value = '';
        modalElement.querySelector('#reportDetails').value = '';
        window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
    });
</script>
