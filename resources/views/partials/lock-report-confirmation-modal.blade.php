<div class="modal fade" id="lock-report-modal-confirmation-{{ $report->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('qaqc.report.lock', $report->id) }}" method="get">
                <div class="modal-header">
                    <h5 class="modal-title">Lock Verification Report {{ $report->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure want to lock <strong>{{ $report->doc_num }}</strong>?
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="confirmApprove">Confirm</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
