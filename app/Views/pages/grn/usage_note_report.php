<?= view('layouts/head') ?>

<body>

    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <!-- Page Title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 p-title">Usage Note Report</h4>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                          
                            <div class="card-body">
                                <form method="GET" action="<?= base_url('inventory/usage_note/report') ?>" id="filterForm">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" name="startdate" class="form-control" value="<?= esc($startdate ?? '') ?>">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">End Date</label>
                                            <input type="date" name="enddate" class="form-control" value="<?= esc($enddate ?? '') ?>">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Manual ID / Tour Name</label>
                                            <input type="text" name="tour_keyword" class="form-control" value="<?= esc($tour_keyword ?? '') ?>" placeholder="Search by Manual ID or Tour Name">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Item (Code/Description)</label>
                                            <select name="item_keyword" id="item_keyword" class="form-control select2" style="width: 100%;">
                                                <option value="">-- All Items --</option>
                                                <?php foreach ($stock_items as $item): ?>
                                                    <option value="<?= esc($item['Stock_Card']) ?>" <?= (isset($item_keyword) && $item_keyword == $item['Stock_Card']) ? 'selected' : '' ?>>
                                                        <?= esc($item['Stock_Card']) ?> - <?= esc($item['Descrip']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <input type="hidden" name="submit" value="1">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Generate Report
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="window.location.href='<?= base_url('inventory/usage_note/report') ?>'">
                                                <i class="fas fa-undo"></i> Clear
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($is_search && !empty($resultlist)): ?>

                <!-- Summary Statistics Section -->
                <!-- <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6 class="text-white-50 mb-2">Total Consumption Notes</h6>
                                <h3 class="mb-0"><?//= number_format($summary['total_cns']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6 class="text-white-50 mb-2">Total Items</h6>
                                <h3 class="mb-0"><?//= number_format($summary['total_items']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6 class="text-white-50 mb-2">Total Quantity</h6>
                                <h3 class="mb-0"><?//= number_format($summary['total_quantity']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h6 class="text-white-50 mb-2">Total Value</h6>
                                <h3 class="mb-0">Rs. <?//= number_format($summary['total_value'], 2) ?></h3>
                            </div>
                        </div>
                    </div>
                </div> -->

                <!-- Export Buttons -->
                <div class="row">
                    <div class="col-12 mb-3">
                        <a href="<?= base_url('inventory/usage_note/report_export_excel?' . http_build_query($_GET)) ?>"
                           class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </a>
                        <a href="<?= base_url('inventory/usage_note/report_export_pdf?' . http_build_query($_GET)) ?>"
                           class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Export to PDF
                        </a>
                    </div>
                </div>

                <!-- Detailed Results Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Detailed Report (<?= count($resultlist) ?> records)</h5>
                            </div>
                            <div class="card-body">
                                <div style="overflow-x: auto;">
                                    <table id="reportTable" class="table table-bordered table-striped table-hover" style="width:100%; min-width:1400px;">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Date</th>
                                                <th>Manual ID</th>
                                                <th>Tour Name</th>
                                                <th>Activity</th>
                                                <th>Activity Date</th>
                                                <th>Item Code</th>
                                                <th>Item Description</th>
                                                <th>Quantity</th>
                                                <th>Returned</th>
                                                <th>Returnable</th>
                                                <th>Unit Cost</th>
                                                <th>Total Value</th>
                                                <th>Status</th>
                                                <th>Created By</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $serial = 1; foreach ($resultlist as $row): ?>
                                            <tr class="
                                                <?php if ($row['status'] === 'pending'): ?>table-warning
                                                <?php elseif ($row['status'] === 'rejected'): ?>table-danger
                                                <?php endif; ?>
                                            ">
                                                <td><?= $serial++ ?></td>
                                                <td><?= date('Y-m-d', strtotime($row['date'])) ?></td>
                                                <td><?= esc($row['manual_id'] ?? 'N/A') ?></td>
                                                <td><?= esc($row['consignee']) ?></td>
                                                <td><?= esc($row['activity_name'] ?? 'N/A') ?></td>
                                                <td><?= $row['activity_date'] ? date('Y-m-d', strtotime($row['activity_date'])) : 'N/A' ?></td>
                                                <td><?= esc($row['item_code']) ?></td>
                                                <td><?= esc($row['item_description']) ?></td>
                                                <td class="text-end"><?= number_format($row['quantity']) ?></td>
                                                <td class="text-end"><?= number_format($row['returned_quantity'] ?? 0) ?></td>
                                                <td class="text-end"><strong><?= number_format($row['returnable_quantity'] ?? $row['quantity']) ?></strong></td>
                                                <td class="text-end">Rs. <?= number_format($row['unit_cost'], 2) ?></td>
                                                <td class="text-end">Rs. <?= number_format($row['total_value'], 2) ?></td>
                                                <td>
                                                    <?php if ($row['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span>
                                                    <?php elseif ($row['status'] === 'approved'): ?>
                                                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Approved</span>
                                                    <?php elseif ($row['status'] === 'rejected'): ?>
                                                        <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Rejected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= esc($row['created_by_name']) ?></td>
                                                <td>
                                                    <?php if ($row['status'] === 'approved' && ($row['returnable_quantity'] ?? 0) > 0): ?>
                                                        <button type="button"
                                                                class="btn btn-sm btn-warning btn-usage-return"
                                                                data-usage-note-id="<?= esc($row['usage_note_id']) ?>"
                                                                data-usage-note-no="<?= esc($row['usage_note_no']) ?>"
                                                                data-tour-id="<?= esc($row['tour_id']) ?>"
                                                                data-manual-id="<?= esc($row['manual_id'] ?? '') ?>"
                                                                data-tour-name="<?= esc($row['consignee']) ?>"
                                                                data-item-code="<?= esc($row['item_code']) ?>"
                                                                data-item-description="<?= esc($row['item_description']) ?>"
                                                                data-original-qty="<?= $row['quantity'] ?>"
                                                                data-returned-qty="<?= $row['returned_quantity'] ?? 0 ?>"
                                                                data-returnable-qty="<?= $row['returnable_quantity'] ?? $row['quantity'] ?>"
                                                                data-unit-cost="<?= $row['unit_cost'] ?>"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#usageNoteReturnModal">
                                                            <i class="fas fa-undo"></i> Return
                                                        </button>
                                                    <?php elseif ($row['status'] === 'pending'): ?>
                                                        <span class="text-muted">Pending</span>
                                                    <?php elseif ($row['status'] === 'rejected'): ?>
                                                        <span class="text-muted">Rejected</span>
                                                    <?php elseif (($row['returnable_quantity'] ?? 0) <= 0): ?>
                                                        <span class="badge bg-success">Fully Returned</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-primary fw-bold">
                                                <td colspan="9" class="text-end" style="padding-right: 10px;"><strong>GRAND TOTAL:</strong></td>
                                                <td class="text-end"><strong><?= number_format($summary['total_quantity']) ?></strong></td>
                                                <td class="text-end"><strong><?= number_format(array_sum(array_column($resultlist, 'returned_quantity'))) ?></strong></td>
                                                <td class="text-end"><strong><?= number_format($summary['total_quantity'] - array_sum(array_column($resultlist, 'returned_quantity'))) ?></strong></td>
                                                <td class="text-end"><strong>Rs. <?= number_format(array_sum(array_column($resultlist, 'unit_cost')) / count($resultlist), 2) ?></strong></td>
                                                <td class="text-end"><strong>Rs. <?= number_format($summary['total_value'], 2) ?></strong></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Item-wise Summary Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0"><i class="fas fa-box"></i> Item-wise Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="15%">Item Code</th>
                                                <th width="40%">Item Description</th>
                                                <th width="15%" class="text-end">Total Quantity</th>
                                                <th width="15%" class="text-end">Unit Cost</th>
                                                <th width="15%" class="text-end">Total Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Group results by item
                                            $grouped_items = [];
                                            foreach ($resultlist as $row) {
                                                $item_key = $row['item_code'];
                                                if (!isset($grouped_items[$item_key])) {
                                                    $grouped_items[$item_key] = [
                                                        'item_code' => $row['item_code'],
                                                        'item_description' => $row['item_description'],
                                                        'unit_cost' => $row['unit_cost'],
                                                        'total_quantity' => 0,
                                                        'total_value' => 0
                                                    ];
                                                }
                                                $grouped_items[$item_key]['total_quantity'] += $row['quantity'];
                                                $grouped_items[$item_key]['total_value'] += $row['total_value'];
                                            }
                                            $item_serial = 1;
                                            foreach ($grouped_items as $item):
                                            ?>
                                            <tr>
                                                <td><?= $item_serial++ ?></td>
                                                <td><strong><?= esc($item['item_code']) ?></strong></td>
                                                <td><?= esc($item['item_description']) ?></td>
                                                <td class="text-end"><strong><?= number_format($item['total_quantity']) ?></strong></td>
                                                <td class="text-end">Rs. <?= number_format($item['unit_cost'], 2) ?></td>
                                                <td class="text-end"><strong>Rs. <?= number_format($item['total_value'], 2) ?></strong></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-success fw-bold">
                                                <td colspan="3" class="text-end"><strong>TOTAL (<?= count($grouped_items) ?> Items):</strong></td>
                                                <td class="text-end"><strong><?= number_format($summary['total_quantity']) ?></strong></td>
                                                <td class="text-end">-</td>
                                                <td class="text-end"><strong>Rs. <?= number_format($summary['total_value'], 2) ?></strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php elseif ($is_search): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No records found for the selected criteria. Please adjust your filters and try again.
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
            <!-- container-fluid -->
        </div>
        <!-- End Page-content -->

        <?= view('layouts/brand') ?>

    </div>
    <!-- end main content-->

    <!-- Usage Note Return Modal -->
    <div class="modal fade" id="usageNoteReturnModal" tabindex="-1" role="dialog" aria-labelledby="usageNoteReturnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="usageNoteReturnModalLabel">Return Items to Stock - Tour Based</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="usageNoteReturnForm">
                    <div class="modal-body">
                        <input type="hidden" id="return_usage_note_id" name="usage_note_id">

                        <div class="alert alert-info">
                            <strong>Tour:</strong> <span id="return_tour_name"></span> (<span id="return_tour_id"></span>)
                            <br><strong>Usage Note:</strong> <span id="return_usage_note_no"></span>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Item Code</label>
                                    <input type="text" class="form-control" id="return_item_code" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Item Description</label>
                                    <input type="text" class="form-control" id="return_item_description" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Original Qty</label>
                                    <input type="text" class="form-control" id="return_original_qty" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Already Returned</label>
                                    <input type="text" class="form-control" id="return_returned_qty" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Available to Return</label>
                                    <input type="text" class="form-control bg-light" id="return_returnable_qty" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Return Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="return_quantity"
                                           name="return_quantity" min="1" required
                                           placeholder="Enter quantity to return">
                                    <small class="text-muted">Max: <span id="max_return_qty"></span></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Return Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="return_date"
                                           name="return_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Return Reason <span class="text-danger">*</span></label>
                            <select class="form-control" id="return_reason" name="return_reason" required>
                                <option value="">Select reason</option>
                                <option value="Unused/Excess">Unused/Excess</option>
                                <option value="Tour Cancelled">Tour Cancelled</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Wrong Item">Wrong Item</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="return_notes" name="notes" rows="2"
                                      placeholder="Enter any additional details about the return"></textarea>
                        </div>

                        <input type="hidden" id="return_unit_cost" name="unit_cost">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-undo"></i> Process Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for item dropdown
            $('#item_keyword').select2({
                placeholder: '-- All Items --',
                allowClear: true,
                width: '100%'
            });

            <?php if ($is_search && !empty($resultlist)): ?>
            // Initialize DataTable
            $('#reportTable').DataTable({
                "pageLength": 25,
                "lengthMenu": [10, 25, 50, 100],
                "order": [[1, "desc"]],
                "autoWidth": false,
                "language": {
                    "search": "Search in results:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
            <?php endif; ?>

            // Populate return modal with item data
            $('#usageNoteReturnModal').on('show.bs.modal', function(e) {
                var button = $(e.relatedTarget);

                var usageNoteId = button.attr('data-usage-note-id');
                var usageNoteNo = button.attr('data-usage-note-no');
                var tourId = button.attr('data-tour-id');
                var manualId = button.attr('data-manual-id');
                var tourName = button.attr('data-tour-name');
                var itemCode = button.attr('data-item-code');
                var itemDescription = button.attr('data-item-description');
                var originalQty = button.attr('data-original-qty');
                var returnedQty = button.attr('data-returned-qty');
                var returnableQty = button.attr('data-returnable-qty');
                var unitCost = button.attr('data-unit-cost');

                $('#return_usage_note_id').val(usageNoteId);
                $('#return_usage_note_no').text(usageNoteNo);
                $('#return_tour_id').text(manualId);
                $('#return_tour_name').text(tourName);
                $('#return_item_code').val(itemCode);
                $('#return_item_description').val(itemDescription);
                $('#return_original_qty').val(originalQty);
                $('#return_returned_qty').val(returnedQty);
                $('#return_returnable_qty').val(returnableQty);
                $('#max_return_qty').text(returnableQty);
                $('#return_unit_cost').val(unitCost);

                $('#return_quantity').attr('max', returnableQty);

                var today = new Date().toISOString().split('T')[0];
                $('#return_date').val(today);

                // Reset form
                $('#return_quantity').val('');
                $('#return_reason').val('');
                $('#return_notes').val('');
            });

            // Validate return quantity
            $('#return_quantity').on('input', function() {
                var maxQty = parseFloat($('#return_returnable_qty').val());
                var enteredQty = parseFloat($(this).val());

                if (enteredQty > maxQty) {
                    $(this).val(maxQty);
                    toastr.warning('Return quantity cannot exceed returnable quantity (' + maxQty + ')');
                }

                if (enteredQty < 0) {
                    $(this).val(0);
                }
            });

            // Handle return form submission
            $('#usageNoteReturnForm').on('submit', function(e) {
                e.preventDefault();

                var formData = {
                    usage_note_id: $('#return_usage_note_id').val(),
                    return_quantity: $('#return_quantity').val(),
                    return_date: $('#return_date').val(),
                    return_reason: $('#return_reason').val(),
                    notes: $('#return_notes').val(),
                    unit_cost: $('#return_unit_cost').val()
                };

                if (!formData.return_quantity || formData.return_quantity <= 0) {
                    toastr.error('Please enter a valid return quantity');
                    return;
                }

                if (!formData.return_reason) {
                    toastr.error('Please select a return reason');
                    return;
                }

                Swal.fire({
                    title: 'Confirm Stock Return',
                    html: 'Return <strong>' + formData.return_quantity + ' units</strong> from tour <strong>' +
                          $('#return_tour_id').text() + '</strong>?<br>' +
                          '<small>This will restore stock for item: ' + $('#return_item_code').val() + '</small>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, process return'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '<?= base_url('inventory/usage_note/process_tour_return') ?>',
                            method: 'POST',
                            data: formData,
                            dataType: 'json',
                            beforeSend: function() {
                                Swal.fire({
                                    title: 'Processing...',
                                    text: 'Please wait',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                            },
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Return Processed',
                                        html: response.message + '<br><strong>Reference:</strong> ' + response.reference,
                                        confirmButtonColor: '#78b848'
                                    }).then(() => {
                                        location.reload();
                                    });
                                    $('#usageNoteReturnModal').modal('hide');
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message || 'Failed to process return'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An error occurred: ' + (xhr.responseJSON?.message || error)
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>

</body>

</html>
