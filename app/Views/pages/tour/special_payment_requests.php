<?= view('layouts/head') ?>

<body>
    <div id="layout-wrapper">
        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- Page Title -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h4 class="text-primary font-weight-bold">Special Payment Requests</h4>
                           
                    </div>

                    <!-- Search Section -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <!-- <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="bi bi-search"></i> Search Tours</h5>
                                </div> -->
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <label for="search_input" class="form-label">Manual ID / Tour Name</label>
                                            <select id="search_input" class="form-select" style="width: 100%;">
                                                <option value="">Type to search...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button id="search_btn" class="btn btn-primary w-100">
                                                <i class="bi bi-search"></i> Search
                                            </button>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button id="clear_btn" class="btn btn-secondary w-100">
                                                <i class="bi bi-x-circle"></i> Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tour Details Display -->
                    <div class="row mb-4" id="tour_details_section" style="display: none;">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Tour Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th width="20%">Manual ID</th>
                                                    <td id="detail_manual_id" colspan="3"></td>
                                                </tr>
                                                <tr>
                                                    <th>Agent Name</th>
                                                    <td id="detail_agent_name" colspan="3"></td>
                                                </tr>
                                                <tr>
                                                    <th>Tour Name</th>
                                                    <td id="detail_tour_name" colspan="3"></td>
                                                </tr>
                                                <tr>
                                                    <th>Start Date</th>
                                                    <td id="detail_start_date"></td>
                                                    <th>End Date</th>
                                                    <td id="detail_end_date"></td>
                                                </tr>
                                                <tr>
                                                    <th>Passenger Count</th>
                                                    <td id="detail_pax_count"></td>
                                                    <th>Created By</th>
                                                    <td id="detail_create_user"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-3">
                                        <button id="request_payment_btn" class="btn btn-success btn-lg">
                                            <i class="bi bi-send-check"></i> Request Special Payment
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Section (Only visible to admins) -->
                    <?php if (session()->get('group_id') == 1): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Admin: Pending Requests</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="all_requests_table">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Manual ID</th>
                                                    <th>Tour Name</th>
                                                    <th>Agent</th>
                                                    <th>Start Date</th>
                                                    <th>Pax</th>
                                                    <th>Requested By</th>
                                                    <th>Requested Date</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($all_requests)): ?>
                                                    <?php $i = 1; foreach ($all_requests as $request): ?>
                                                    <tr id="request_row_<?= $request['id']; ?>">
                                                        <td><?= $i++; ?></td>
                                                        <td><strong><?= $request['manual_id']; ?></strong></td>
                                                        <td><?= $request['tour_name'] ?? $request['consignee']; ?></td>
                                                        <td><?= $request['agent_name']; ?></td>
                                                        <td><?= date('d M, Y', strtotime($request['start_date'])); ?></td>
                                                        <td><?= $request['pax_count']; ?></td>
                                                        <td><?= $request['requested_by']; ?></td>
                                                        <td><?= date('d M, Y H:i', strtotime($request['requested_date'])); ?></td>
                                                        <td>
                                                            <?php if ($request['status'] === 'pending'): ?>
                                                                <span class="badge bg-warning text-dark">Pending</span>
                                                            <?php elseif ($request['status'] === 'approved'): ?>
                                                                <span class="badge bg-success">Approved</span>
                                                                <br><small class="text-muted">By: <?= $request['admin_user']; ?></small>
                                                                <br><small class="text-muted"><?= date('d M, Y H:i', strtotime($request['admin_action_date'])); ?></small>
                                                            <?php elseif ($request['status'] === 'rejected'): ?>
                                                                <span class="badge bg-danger">Rejected</span>
                                                                <br><small class="text-muted">By: <?= $request['admin_user']; ?></small>
                                                                <br><small class="text-muted"><?= date('d M, Y H:i', strtotime($request['admin_action_date'])); ?></small>
                                                                <?php if (!empty($request['notes'])): ?>
                                                                    <br><small class="text-muted">Note: <?= $request['notes']; ?></small>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($request['status'] === 'pending'): ?>
                                                                <button class="btn btn-success btn-sm approve_btn" data-id="<?= $request['id']; ?>" title="Approve">
                                                                    <i class="bi bi-check-circle"></i> Approve
                                                                </button>
                                                                <button class="btn btn-danger btn-sm reject_btn" data-id="<?= $request['id']; ?>" title="Reject">
                                                                    <i class="bi bi-x-circle"></i> Reject
                                                                </button>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="11" class="text-center text-muted">No requests</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_notes" class="form-label">Rejection Notes (Optional)</label>
                        <textarea class="form-control" id="reject_notes" rows="3" placeholder="Enter reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm_reject_btn">Reject Request</button>
                </div>
            </div>
        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/scripts') ?>

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let selectedTourId = null;
        let currentRejectId = null;

        $(document).ready(function() {
            // Initialize Select2 with AJAX
            $('#search_input').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search by Manual ID or Tour Name',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: '<?= base_url('tour/special_payment_requests/autocomplete'); ?>',
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term
                        };
                    },
                    processResults: function(response) {
                        if (response.status === 200) {
                            return {
                                results: response.data.map(function(item) {
                                    return {
                                        id: item.tour_id,
                                        text: item.manual_id + ' - ' + item.consignee,
                                        data: item
                                    };
                                })
                            };
                        }
                        return { results: [] };
                    },
                    cache: true
                }
            });

            // Search button click
            $('#search_btn').on('click', function() {
                const tourId = $('#search_input').val();
                if (tourId) {
                    loadTourDetails(tourId);
                } else {
                    Swal.fire('Error', 'Please select a tour first', 'error');
                }
            });

            // Clear button click
            $('#clear_btn').on('click', function() {
                $('#search_input').val(null).trigger('change');
                $('#tour_details_section').hide();
                selectedTourId = null;
            });

            // Request Payment button
            $('#request_payment_btn').on('click', function() {
                if (!selectedTourId) {
                    Swal.fire('Error', 'No tour selected', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Confirm Request',
                    text: 'Submit special payment request for this tour?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#78b848',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Submit Request'
                }).then((result) => {
                    if (result.isConfirmed) {
                        createPaymentRequest();
                    }
                });
            });

            // Approve button
            $('.approve_btn').on('click', function() {
                const requestId = $(this).data('id');

                Swal.fire({
                    title: 'Approve Request',
                    text: 'Approve this special payment request?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#78b848',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Approve'
                }).then((result) => {
                    if (result.isConfirmed) {
                        approveRequest(requestId);
                    }
                });
            });

            // Reject button
            $('.reject_btn').on('click', function() {
                currentRejectId = $(this).data('id');
                $('#reject_notes').val('');
                $('#rejectModal').modal('show');
            });

            // Confirm reject
            $('#confirm_reject_btn').on('click', function() {
                const notes = $('#reject_notes').val();
                rejectRequest(currentRejectId, notes);
            });
        });

        function loadTourDetails(tourId) {
            $.ajax({
                url: '<?= base_url('tour/special_payment_requests/get_details'); ?>',
                type: 'POST',
                data: { tour_id: tourId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        const data = response.data;
                        selectedTourId = tourId;

                        $('#detail_tour_id').text(data.tour_id);
                        $('#detail_manual_id').text(data.manual_id || 'N/A');
                        $('#detail_agent_name').text(data.agent_full_name || data.agent_name || 'N/A');
                        $('#detail_tour_name').text(data.consignee || 'N/A');
                        $('#detail_start_date').text(data.start_date ? new Date(data.start_date).toLocaleDateString() : 'N/A');
                        $('#detail_end_date').text(data.end_date ? new Date(data.end_date).toLocaleDateString() : 'N/A');
                        $('#detail_pax_count').text(data.passenger_count || '0');
                        $('#detail_create_user').text(data.create_user || 'N/A');

                        $('#tour_details_section').slideDown();
                    } else {
                        Swal.fire('Error', response.message || 'Failed to load tour details', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to load tour details', 'error');
                }
            });
        }

        function createPaymentRequest() {
            $.ajax({
                url: '<?= base_url('tour/special_payment_requests/create'); ?>',
                type: 'POST',
                data: { tour_id: selectedTourId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: true
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to create request', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to create request', 'error');
                }
            });
        }

        function approveRequest(requestId) {
            $.ajax({
                url: '<?= base_url('tour/special_payment_requests/approve'); ?>',
                type: 'POST',
                data: { request_id: requestId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved!',
                            text: response.message,
                            showConfirmButton: true
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to approve request', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to approve request', 'error');
                }
            });
        }

        function rejectRequest(requestId, notes) {
            $.ajax({
                url: '<?= base_url('tour/special_payment_requests/reject'); ?>',
                type: 'POST',
                data: {
                    request_id: requestId,
                    notes: notes
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        $('#rejectModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Rejected!',
                            text: response.message,
                            showConfirmButton: true
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to reject request', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to reject request', 'error');
                }
            });
        }
    </script>
</body>
</html>
