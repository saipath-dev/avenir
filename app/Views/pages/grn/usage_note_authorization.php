<?= view('layouts/head') ?>

<body>
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 p-title">Authorize Usage Notes</h4>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-status="pending" href="#" onclick="filterByStatus('pending'); return false;">
                                            <i class="fas fa-clock"></i> Pending
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-status="approved" href="#" onclick="filterByStatus('approved'); return false;">
                                            <i class="fas fa-check-circle"></i> Approved
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-status="rejected" href="#" onclick="filterByStatus('rejected'); return false;">
                                            <i class="fas fa-times-circle"></i> Rejected
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-status="all" href="#" onclick="filterByStatus('all'); return false;">
                                            <i class="fas fa-list"></i> All
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="authTable" class="table table-bordered table-striped table-hover nowrap" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>CN No</th>
                                                <th>Date</th>
                                                <th>Tour ID</th>
                                                <th>Manual ID</th>
                                                <th>Tour Name</th>
                                                <th>Items</th>
                                                <th>Total Qty</th>
                                                <th>Created By</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="authTableBody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?= view('layouts/brand') ?>
    </div>

    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Usage Note Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsModalBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Usage Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reject_usage_note_no" value="">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmReject()">
                        <i class="fas fa-times-circle"></i> Reject
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        let currentStatus = 'pending';
        let dataTable = null;

        function filterByStatus(status) {
            currentStatus = status;
            $('.nav-tabs .nav-link').removeClass('active');
            $(`.nav-tabs .nav-link[data-status="${status}"]`).addClass('active');
            loadUsageNotes();
        }

        function loadUsageNotes() {
            $.ajax({
                url: '<?= base_url('inventory/usage_note/get_pending_usage_notes') ?>',
                type: 'GET',
                data: { status: currentStatus },
                success: function(response) {
                    if (response.status === 'success') {
                        renderTable(response.data);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load usage notes' });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load usage notes' });
                }
            });
        }

        function renderTable(data) {
            if (dataTable) { dataTable.destroy(); }
            const tbody = $('#authTableBody');
            tbody.empty();

            if (data.length === 0) {
                tbody.append('<tr><td colspan="11" class="text-center">No records found</td></tr>');
            } else {
                data.forEach((item, index) => {
                    const statusBadge = getStatusBadge(item.status);
                    const actionButtons = getActionButtons(item);
                    const row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.usage_note_no}</td>
                            <td>${item.date}</td>
                            <td>${item.tour_id}</td>
                            <td>${item.manual_id || 'N/A'}</td>
                            <td>${item.tour_name || 'N/A'}</td>
                            <td>${item.item_count}</td>
                            <td class="text-end">${parseFloat(item.total_quantity).toLocaleString()}</td>
                            <td>${item.created_by_name || 'N/A'}</td>
                            <td>${statusBadge}</td>
                            <td>${actionButtons}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }

            dataTable = $('#authTable').DataTable({ pageLength: 25, order: [[1, 'desc']], responsive: true, scrollX: true });
        }

        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span>',
                'approved': '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Approved</span>',
                'rejected': '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Rejected</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
        }

        function getActionButtons(item) {
            let buttons = `<button class="btn btn-sm btn-info" onclick="viewDetails('${item.usage_note_no}')"><i class="fas fa-eye"></i> View</button>`;
            if (item.status === 'pending') {
                buttons += `
                    <button class="btn btn-sm btn-success ms-1" onclick="approveUsageNote('${item.usage_note_no}')"><i class="fas fa-check"></i> Approve</button>
                    <button class="btn btn-sm btn-danger ms-1" onclick="rejectUsageNote('${item.usage_note_no}')"><i class="fas fa-times"></i> Reject</button>
                `;
            } else if (item.status === 'approved') {
                buttons += `<a href="<?= base_url('inventory/usage_note/print/') ?>${item.usage_note_no}" target="_blank" class="btn btn-sm btn-primary ms-1"><i class="fas fa-print"></i> Print</a>`;
            }
            return buttons;
        }

        function viewDetails(usage_note_no) {
            $.ajax({
                url: `<?= base_url('inventory/usage_note/get_usage_note_details/') ?>${usage_note_no}`,
                type: 'GET',
                success: function(response) {
                    if (response.status === 'success' && response.data.length > 0) {
                        const details = response.data;
                        const firstItem = details[0];
                        let html = `
                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Tour:</strong> ${firstItem.tour_name} (${firstItem.tour_id})</p>
                                        <p><strong>Date:</strong> ${firstItem.date}</p>


                                        <p><strong>Pax:</strong> ${firstItem.passenger_count || 'N/A'}</p>
                                    </div>
                                    <div class="col-md-6">
                                                                            <p><strong>Manual ID:</strong> ${firstItem.manual_id || 'N/A'}</p>
                                        <p><strong>Created By:</strong> ${firstItem.created_by_name || 'N/A'}</p>
                                        <p><strong>Status:</strong> ${getStatusBadge(firstItem.status)}</p>
                        `;
                        if (firstItem.status === 'approved') {
                            html += `
                                        <p><strong>Approved By:</strong> ${firstItem.approved_by_name || 'N/A'}</p>
                                        <p><strong>Approved At:</strong> ${firstItem.approved_at || 'N/A'}</p>`;
                        } else if (firstItem.status === 'rejected') {
                            html += `
                                        <p><strong>Rejected By:</strong> ${firstItem.approved_by_name || 'N/A'}</p>
                                        <p><strong>Rejected At:</strong> ${firstItem.approved_at || 'N/A'}</p>
                                        <p><strong>Reason:</strong> ${firstItem.rejection_reason || 'N/A'}</p>`;
                        }
                        html += `
                                    </div>
                                </div>
                            </div>`;
                        html += `</div><hr><h6>Items:</h6><table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Activity Date</th><th>Activity</th><th>Item Code</th><th>Description</th><th class="text-end">Quantity</th><th class="text-end">Unit Cost</th><th class="text-end">Total Value</th></tr></thead><tbody>`;
                        let grandTotal = 0;
                        details.forEach((item, idx) => {
                            const unitCost = parseFloat(item.unit_cost || 0);
                            const totalValue = parseFloat(item.total_value || 0);
                            grandTotal += totalValue;
                            const activityDate = item.activity_date ? new Date(item.activity_date).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'}) : 'N/A';
                            html += `<tr><td>${idx + 1}</td><td>${activityDate}</td><td>${item.activity_name || 'N/A'}</td><td>${item.item_code}</td><td>${item.item_description || 'N/A'}</td><td class="text-end">${parseFloat(item.quantity).toLocaleString()}</td><td class="text-end">${unitCost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td><td class="text-end">${totalValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td></tr>`;
                        });
                        html += `</tbody><tfoot><tr><th colspan="7" class="text-end">Grand Total:</th><th class="text-end">${grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</th></tr></tfoot></table>`;
                        $('#detailsModalBody').html(html);
                        $('#detailsModal').modal('show');
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load details' });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load details' });
                }
            });
        }

        function approveUsageNote(usage_note_no) {
            Swal.fire({
                title: 'Approve Usage Note?',
                text: `Are you sure you want to approve ${usage_note_no}? Stock will be updated.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#78b848',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= base_url('inventory/usage_note/approve_usage_note') ?>',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ usage_note_no: usage_note_no }),
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({ title: 'Approved!', text: response.message, icon: 'success' }).then(() => { loadUsageNotes(); });
                            } else {
                                Swal.fire({ title: 'Error', text: response.message, icon: 'error' });
                            }
                        },
                        error: function() {
                            Swal.fire({ title: 'Error', text: 'Failed to approve usage note', icon: 'error' });
                        }
                    });
                }
            });
        }

        function rejectUsageNote(usage_note_no) {
            $('#reject_usage_note_no').val(usage_note_no);
            $('#rejection_reason').val('');
            $('#rejectModal').modal('show');
        }

        function confirmReject() {
            const usage_note_no = $('#reject_usage_note_no').val();
            const rejection_reason = $('#rejection_reason').val().trim();
            if (!rejection_reason) {
                Swal.fire({ icon: 'warning', title: 'Required', text: 'Please provide a rejection reason' });
                return;
            }
            $.ajax({
                url: '<?= base_url('inventory/usage_note/reject_usage_note') ?>',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ usage_note_no: usage_note_no, rejection_reason: rejection_reason }),
                success: function(response) {
                    $('#rejectModal').modal('hide');
                    if (response.status === 'success') {
                        Swal.fire({ title: 'Rejected', text: response.message, icon: 'success' }).then(() => { loadUsageNotes(); });
                    } else {
                        Swal.fire({ title: 'Error', text: response.message, icon: 'error' });
                    }
                },
                error: function() {
                    $('#rejectModal').modal('hide');
                    Swal.fire({ title: 'Error', text: 'Failed to reject usage note', icon: 'error' });
                }
            });
        }

        $(document).ready(function() {
            loadUsageNotes();
        });
    </script>

</body>
</html>
