<?= view('layouts/head') ?>

<body>

    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 20mm;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .main-content {
                width: 100%;
            }
        }

        .tour-info-card .info-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin-bottom: 14px;
        }

        .tour-info-card .info-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6c757d;
        }

        .tour-info-card .info-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #212529;
        }

        .section-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .section-card .card-header h6 {
            font-weight: 600;
            color: #344054;
        }

        .required-mark {
            color: #dc3545;
        }
    </style>

    <?= view('layouts/topbar', ['topbar_back' => 'close', 'topbar_home' => false, 'topbar_logout' => false]) ?>

    <div class="container-fluid p-4 mt-5">

        <div class="row">
            <div class="col-md-12">
                <h2 class="p-title text-center">TRANSPORT EXPENSES</h2>
            </div>
        </div>

        <!-- Tour Info -->
        <div class="row clearfix mb-3 justify-content-center">
            <div class="col-md-10">
                <div class="card tour-info-card border-0 shadow-sm">
                    <div class="card-body">
                        <?php foreach ($tour_main_details as $detail): ?>
                            <input type="hidden" name="tour_id" id="tour_id" value="<?= esc($tour_id) ?>">
                            <input type="hidden" name="is_settled" id="is_settled" value="<?= esc($detail['transport_settled']) ?>">
                            <input type="hidden" name="finalize_txt" id="finalize_txt" value="<?= esc($detail['finalize_txt']) ?>">

                            <div class="row">
                                <div class="col-6 col-md-2">
                                    <div class="info-item">
                                        <span class="info-label">Manual ID</span>
                                        <span class="info-value" id="t_id"><?= esc($detail['manual_id']) ?></span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="info-item">
                                        <span class="info-label">Tour Name</span>
                                        <span class="info-value" id="t_consignee"><?= esc($detail['consignee']) ?></span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-2">
                                    <div class="info-item">
                                        <span class="info-label">Tour Agent</span>
                                        <span class="info-value" id="t_agent_name"><?= esc($detail['agent_name']) ?></span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-2">
                                    <div class="info-item">
                                        <span class="info-label">Transport Agent</span>
                                        <span id="t_transportagent" class="d-none"><?= esc($detail['transport_agent']) ?></span>
                                        <span class="info-value" id="t_transportagent_name"><?= esc($detail['transport_agent_name']) ?></span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-1">
                                    <div class="info-item">
                                        <span class="info-label">Pax</span>
                                        <span class="info-value" id="t_passenger_count"><?= esc($detail['passenger_count']) ?></span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-2">
                                    <div class="info-item">
                                        <span class="info-label">Dates</span>
                                        <span class="info-value" id="t_start_date"><?= esc($detail['start_date']) ?></span>
                                        <span class="text-muted small" id="t_end_date">to <?= esc($detail['end_date']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($detail['guide_name'])): ?>
                                <div class="row">
                                    <div class="col-6 col-md-2">
                                        <div class="info-item mb-0">
                                            <span class="info-label">Guide</span>
                                            <span id="t_guide" class="d-none"><?= esc($detail['guide_name']) ?></span>
                                            <span class="info-value" id="guide_name"><?= esc($detail['guide_name']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Section -->
        <div class="row">
            <!-- Left Side: Expenses Details (Main Section) -->
            <div class="col-md-8">
                <div class="card section-card border-0 shadow-sm">
                    <div class="card-header d-flex align-items-center">
                        <h6 class="mb-0"><i class="bi bi-receipt me-2"></i>Add Expense</h6>
                    </div>
                    <div class="card-body">
                        <?php if (isset($is_request_sent) && $is_request_sent): ?>
                            <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center" role="alert">
                                <i class="bi bi-lock-fill me-2"></i>
                                <div><strong>Form Locked!</strong> A request has already been sent for approval. You cannot add new expenses until the pending request is processed.</div>
                            </div>
                        <?php endif; ?>
                        <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                            <fieldset <?= (isset($is_request_sent) && $is_request_sent) ? 'disabled' : '' ?>>
                                <div class="row">
                                    <div class="mb-3 col-md-3">
                                        <label class="form-label" for="tagent">Select Agent <span class="required-mark">*</span></label>
                                        <select name="tagent" id="tagent" class="js-example-basic-single form-control" style="width: 100%;" required>
                                            <?php
                                            echo "<option></option>";
                                            foreach ($drivers as $agent) {
                                                $selected = '';
                                                if ($agent['driver'] == $detail['transport_agent']) {
                                                    $selected = 'selected';
                                                }
                                                echo "<option value='" . $agent['driver'] . "' " . $selected . " >" . $agent['driver_name'] . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                        <div class="mb-3 col-md-3">
                                        <label class="form-label" for="category">Expenses Type <span class="required-mark">*</span></label>
                                        <select name="category" id="category" class="js-example-basic-single form-control" style="width: 100%;" required>
                                            <option value="">Select a Category...</option>
                                            <option value="6-9-23-10640">Entrance Fee</option>
                                            <option value="6-9-23-10641">Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="supplier">Expenses Acc <span class="required-mark">*</span></label>
                                        <select name="supplier" id="supplier" class="js-example-basic-single form-control" style="width: 100%;" required>
                                            <option value="">Select a Supplier...</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="inv">Invoice No <span class="required-mark">*</span></label>
                                        <input class="form-control" type="text" id="inv" name="inv" placeholder="" required />
                                    </div>
                                    <div class="mb-3 col-md-2">
                                        <label class="form-label" for="date">Date <span class="required-mark">*</span></label>
                                        <input class="form-control" type="text" id="date" name="date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" required />
                                    </div>
                                     <div class="mb-3 col-md-2">
                                        <label class="form-label" for="rsamount">LKR Amount <span class="required-mark">*</span></label>
                                        <input class="form-control" type="text" id="rsamount" name="rsamount" placeholder="" required />
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="invoice_image">Invoice Image</label>
                                        <input class="form-control" type="file" id="invoice_image" name="invoice_image" accept="image/*" />
                                    </div>
                                </div>


                                <button type="submit" class="btn btn-success w-20" id="save_btn">
                                    <i class="bi bi-check-lg me-1"></i>Save Expense
                                </button>
                            </fieldset>
                        </form>
                    </div>
                </div>

                <!-- Pending/Requested Expenses Section (Dynamic) -->
                <div class="card section-card border-0 shadow-sm mt-4 d-none" id="pending_expenses_container">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-hourglass-split me-2"></i>Requested Expenses</h6>
                        <button type="button" class="btn btn-sm btn-primary" id="send_all_btn" onclick="send_all_requests()">
                            <i class="bi bi-send-fill me-1"></i>Send All Requests
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Driver</th>
                                        <th>Date</th>
                                        <th>Invoice No</th>
                                        <th>Supplier</th>
                                        <th>Description</th>
                                        <th class="text-end">LKR Amount</th>
                                        <th>Status</th>
                                        <th>Checked By</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="pending_expenses_tbl">
                                </tbody>
                                <tfoot class="table-light" id="pending_expenses_footer">
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Side: Tour Activities -->
            <div class="col-md-4">
                <div class="card section-card border-0 shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Tour Activities</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($tour_activities) && count($tour_activities) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Date</th>
                                            <th>Activity</th>
                                            <th>Code</th>
                                            <th class="text-end">Fee ($)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $total_activity_fee = 0;
                                        $i = 1;
                                        foreach ($tour_activities as $activity):
                                            $total_activity_fee += floatval($activity['fee'] ?? 0);
                                        ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td><?= date('Y-m-d', strtotime($activity['tour_date'])) ?></td>
                                                <td><?= esc($activity['place_name'] ?? 'N/A') ?></td>
                                                <td><?= esc($activity['code'] ?? '-') ?></td>
                                                <td class="text-end"><?= number_format(floatval($activity['fee'] ?? 0), 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="4" class="text-end">Total Activity Fee:</th>
                                            <th class="text-end">$ <?= number_format($total_activity_fee, 2) ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info py-2 mb-0">
                                <small><i class="bi bi-info-circle me-1"></i>No activities found for this tour.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });


        $(document).ready(function() {
            var tour_id = $("#tour_id").val();

            loadPendingExpenses(tour_id);

        });


        // Load Expenses Account based on Expenses Type
        $('#category').change(function() {
            var category = $(this).val();
            $.ajax({
                url: "<?php echo base_url() ?>accounts/creditor_invoice/get_details",
                type: 'POST',
                data: {
                    category: category
                },
                dataType: 'json',
                success: function(response) {
                    // Handle the response data here
                    $('#supplier').empty();
                    $.each(response.data, function(index, value) {

                        $('#supplier').append('<option value=""></option>');
                        $('#supplier').append('<option value="' + value.L_Code + '">' + value.PName + '</option>');
                    });
                },
                error: function(xhr, status, error) {
                    // Handle any errors here
                }
            });

        });

        function form_submit(e) {
            var tour_id = $('#tour_id').val();
            var description = $('#t_consignee').text();

            // Saved via the same admin_save_expense endpoint (and driver_portal_expenses table)
            // the driver portal itself uses, so this back-office entry behaves exactly like the
            // driver adding their own expense — including invoice image support, same upload
            // folder/naming. Field names below match what that endpoint expects.
            var formData = new FormData();
            formData.append('tour_id', tour_id);
            formData.append('driver_acc', $('#tagent').val());
            formData.append('expense_date', $('#date').val());
            formData.append('category', $('#category').val());
            formData.append('supplier', $('#supplier').val());
            formData.append('invoice_no', $('#inv').val());
            formData.append('lkr_amount', $('#rsamount').val());
            formData.append('description', description);

            var invoiceImage = $('#invoice_image')[0].files[0];
            if (invoiceImage) {
                formData.append('invoice_image', invoiceImage);
            }

            $.ajax({
                url: "<?php echo base_url() ?>dashboard2/transport/transport_portal/admin_save_expense",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                        }).then((result) => {
                            location.reload();
                        });
                    } else if (response.status == 400) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });

                }
            });
        }

        // Add Expenses
        $('#form_submit').submit(function(e) {
            e.preventDefault();

            var tagent = $('#tagent').val();
            var current_transport_agent = $('#t_transportagent').text();
            if (current_transport_agent != '' && tagent != current_transport_agent) {
                // confirm
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You changed the transport agent. Do you want to continue?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, change it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form_submit(this);
                    }
                });
            } else {
                form_submit(this);
            }

            // disble save button
            // $('#save_btn').prop('disabled', true);


        });

        // Send All Requests for Pending Expenses
        function send_all_requests() {
            var tour_id = $('#tour_id').val();

            Swal.fire({
                title: 'Send All Requests?',
                text: "Are you sure you want to send all pending expenses for approval?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, send all!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST",
                        url: "<?php echo base_url(); ?>dashboard2/transport/driver_expense/send_all_requests",
                        data: {
                            tour_id: tour_id
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sent!',
                                    text: response.message,
                                }).then((result) => {
                                    loadPendingExpenses(tour_id);
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message,
                                });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong! Please try again later.',
                            });
                        }
                    });
                }
            });
        }

        // Delete Pending Expense
        function delete_pending_expense(expense_id) {
            var tour_id = $('#tour_id').val();

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this pending expense!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST",
                        url: "<?php echo base_url(); ?>dashboard2/transport/driver_expense/delete_pending_expense",
                        data: {
                            expense_id: expense_id
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message,
                                }).then((result) => {
                                    loadPendingExpenses(tour_id);
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message,
                                });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong! Please try again later.',
                            });
                        }
                    });
                }
            });
        }

        // Load Pending Expenses Table (from driver_expenses)
        function loadPendingExpenses(tour_id) {
            $.ajax({
                url: "<?php echo base_url() ?>dashboard2/transport/driver_expense/get_tour_pending_expenses",
                type: 'POST',
                data: {
                    tour_id: tour_id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200 && response.data.length > 0) {
                        var totalLKR = 0;
                        var counter = 1;
                        var hasUnsentRequests = false;

                        $('#pending_expenses_tbl').html('');

                        $.each(response.data, function(index, expense) {
                            var lkrAmount = parseFloat(expense.lkr_amount) || 0;
                            totalLKR += lkrAmount;

                            // Check if there are any unsent pending requests
                            if (expense.status == 'pending' && expense.request_send != 1) {
                                hasUnsentRequests = true;
                            }

                            // Set badge class based on status
                            var badgeClass = 'bg-warning text-dark';
                            if (expense.status == 'approved') {
                                badgeClass = 'bg-success';
                            } else if (expense.status == 'rejected') {
                                badgeClass = 'bg-danger';
                            }

                            var rejectionInfo = '';
                            if (expense.status == 'rejected' && expense.rejection_reason) {
                                rejectionInfo = '<br><small class="text-danger">' + expense.rejection_reason + '</small>';
                            }

                            // Show delete button for pending expenses that haven't been sent OR rejected expenses
                            var deleteBtn = '';
                            if (expense.status == 'pending' && expense.request_send != 1) {
                                deleteBtn = '<button type="button" class="btn btn-sm btn-danger" onclick="delete_pending_expense(' + expense.id + ')"><i class="bi bi-trash"></i></button>';
                            } else if (expense.status == 'pending' && expense.request_send == 1) {
                                deleteBtn = '<span class="badge bg-info">Sent</span>';
                            } else if (expense.status == 'rejected') {
                                // Allow deletion of rejected expenses
                                deleteBtn = '<button type="button" class="btn btn-sm btn-danger" onclick="delete_pending_expense(' + expense.id + ')"><i class="bi bi-trash"></i></button>';
                            } else {
                                deleteBtn = '-';
                            }

                            $('#pending_expenses_tbl').append(
                                '<tr>' +
                                '<td>' + counter++ + '</td>' +
                                '<td>' + (expense.agent_name || '-') + '</td>' +
                                '<td>' + (expense.expense_date || '-') + '</td>' +
                                '<td>' + (expense.invoice_no || '-') + '</td>' +
                                '<td>' + (expense.supplier_name || '-') + '</td>' +
                                '<td>' + (expense.description || '-') + '</td>' +
                                '<td class="text-end">' + lkrAmount.toFixed(2) + '</td>' +
                                '<td><span class="badge ' + badgeClass + '">' + expense.status.charAt(0).toUpperCase() + expense.status.slice(1) + '</span>' + rejectionInfo + '</td>' +
                                '<td>' + (expense.created_by || '-') + '</td>' +
                                '<td>' + deleteBtn + '</td>' +
                                '</tr>'
                            );
                        });

                        $('#pending_expenses_footer').html(
                            '<tr>' +
                            '<th colspan="6" class="text-end">Total:</th>' +
                            '<th class="text-end">Rs. ' + totalLKR.toFixed(2) + '</th>' +
                            '<th colspan="3"></th>' +
                            '</tr>'
                        );

                        $('#pending_expenses_container').removeClass('d-none');

                        // Show/hide Send All button based on unsent requests
                        if (hasUnsentRequests) {
                            $('#send_all_btn').show();
                        } else {
                            $('#send_all_btn').hide();
                        }
                    } else {
                        $('#pending_expenses_container').addClass('d-none');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error loading pending expenses: ' + error);
                }
            });
        }

    </script>


</body>




</html>
