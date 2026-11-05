<?= view('layouts/head') ?>
<style type="text/css">
    body {

        background-color: #f8f9fa;
    }

    .card-header {
        /*background: linear-gradient(to right, #007bff, #6610f2);*/
        color: white;
        font-weight: bold;
        border-bottom: 2px solid #f8f9fa;
    }

    .table {
        border-collapse: collapse;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.1);
        cursor: pointer;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #f9f9f9;
    }

    .btn {
        transition: 0.3s ease;
    }

    .btn:hover {
        background-color: #0056b3;
        color: white;
    }
</style>

<body class="bg-light">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <!-- Start right Content here -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="row mb-4">
                        <div class="col-md-9">
                            <h4 class="text-primary font-weight-bold">Payment Request Dashboard</h4>
                        </div>
                        <div class="col-md-3 text-end">
                            <h6 class="text-secondary"><?= date('Y-m-d') ?></h6>
                        </div>
                    </div>


                    <!-- Posted Expenses Section -->
                    <?php if (!empty($postedExpenses)): ?>
                    <div class="container-fluid py-4">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card shadow-lg border-0 rounded">
                                    <div class="card-header bg-gradient-success text-white d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><i class="bi bi-check-circle-fill"></i> Posted Transport Expenses</h5>
                                        <span class="badge bg-light text-success"><?= count($postedExpenses) ?> Tours</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered align-middle" id="postedExpensesTable">
                                                <thead class="bg-light text-success">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Manual ID</th>
                                                        <th>Tour Name</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Posted By</th>
                                                        <th>Posted At</th>
                                                        <th>View</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $j = 1; foreach ($postedExpenses as $posted): ?>
                                                    <tr>
                                                        <td><?= $j++ ?></td>
                                                        <td><strong><?= esc($posted['manual_id'] ?? '-') ?></strong></td>
                                                        <td><?= esc($posted['consignee'] ?? '-') ?></td>
                                                        <td><?= !empty($posted['start_date']) ? date('d M Y', strtotime($posted['start_date'])) : '-' ?></td>
                                                        <td><?= !empty($posted['end_date']) ? date('d M Y', strtotime($posted['end_date'])) : '-' ?></td>
                                                        <td><?= esc($posted['posted_by'] ?? '-') ?></td>
                                                        <td><?= !empty($posted['posted_at']) ? date('d M Y H:i', strtotime($posted['posted_at'])) : '-' ?></td>
                                                        <td>
                                                            <button class="btn btn-info btn-sm" onclick="window.open('<?= base_url('tour/tour_accounts/posted_expenses_report/' . urlencode($posted['manual_id'])) ?>', '_blank')">
                                                                <i class="bi bi-eye"></i> View
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Table -->
                    <div class="container-fluid py-4">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card shadow-lg border-0 rounded">
                                    <div class="card-header bg-gradient-light text-dark d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Payment Requests</h5>
                                        <div class="d-flex ms-auto">
                                            <!-- <button id="viewAll" class="btn btn-success btn-sm fw-bold px-3 py-2 rounded shadow-sm"
                                                onclick="window.location.href='<? //= base_url('tour/tour_accounts/payment_requested_report') 
                                                                                ?>'">
                                                <i class="bi bi-arrow-left me-1"></i> Back
                                            </button> &nbsp;

                                            <button id="viewAll" class="btn btn-outline-success btn-success btn-sm me-2" onclick="window.location.href='<? //= base_url('tour/tour_accounts/payment_requested_report?view_all=true') 
                                                                                                                                                        ?>'">
                                                <b>View All</b>
                                            </button> -->


                                        </div>

                                    </div>


                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table js-basic-example table-hover dataTable table-custom align-middle" id="mainTable">
                                                <thead class="bg-light text-primary">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Manual ID</th>
                                                        <th>Tour Name</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <!-- <th>Hotel Name</th>
                                                        <th>Bank Account</th>
                                                        <th>Payable Payment</th>
                                                        <th>Advance Payment</th>
                                                        <th>Balance</th>
                                                        <th>Requested Amount</th>
                                                        <th>Complete</th> -->
                                                        <th>View</th>
                                                        <!-- <th>Tour Dashboard</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($paymentRequests)) : ?>
                                                        <?php $i = 1; ?>
                                                        <?php foreach ($paymentRequests as $request) : ?>
                                                            <tr>
                                                                <td><?= $i++; ?></td>
                                                                <td><?= esc($request['manual_id']); ?></td>
                                                                <td><?= esc($request['consignee']); ?><br><span class="text-muted">(<?= esc($request['create_user']); ?>)</span></td>
                                                                <td><?= $request['start_date']; ?></td>
                                                                <td><?= $request['end_date']; ?></td>
                                                                <td>
                                                                    <?php if (esc($request['tour_cancel']) == 1) {
                                                                        // Tour Cancelled - Show disabled button
                                                                        echo '<button class="btn btn-danger btn-sm" disabled></i> Cancelled</button>';
                                                                    } else { ?>
                                                                        <button class="btn btn-primary btn-sm" data-id="<?= esc($request['manual_id']); ?>" onclick="window.open('<?= base_url('tour/tour_accounts/payment_requested_report/' . esc($request['manual_id'])) ?>', '_blank')">
                                                                            <i class="bi bi-eye"></i> View
                                                                        </button>
                                                                    <?php } ?>

                                                                </td>
                                                                <!-- <td>
                                                                    <button class="btn btn-info btn-sm view" id="tour_dash" data-year="<?//= esc(date('Y', strtotime($request['start_date']))); ?>" data-month="<?//= esc(date('m', strtotime($request['start_date']))); ?>" data-tour_id="<?//= esc($request['tour_id']); ?>">
                                                                        <i class="bi bi-box-arrow-up-right"></i> Dashboard
                                                                    </button>
                                                                </td> -->
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else : ?>
                                                        <tr>
                                                            <td colspan="10" class="text-center text-muted">No payment requests found.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Accepted Utility Bills Table -->
                    <div class="container-fluid py-4">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card shadow-lg border-0 rounded">
                                    <div class="card-header bg-gradient-success text-white d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><i class="bi bi-lightning-charge-fill"></i> Accepted Utility Bill Payments</h5>
                                        <?php
                                        $acceptedUtilityBillTotal = array_sum(array_column($acceptedUtilityBills ?? [], 'amount'));
                                        ?>
                                        <span class="badge bg-white text-success fs-6 fw-bold">
                                            Total: Rs. <?= number_format($acceptedUtilityBillTotal, 2) ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table js-basic-example table-hover dataTable table-custom align-middle" id="acceptedUtilityBillTable">
                                                <thead class="bg-light text-primary">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Type</th>
                                                        <th>Date</th>
                                                        <th>Invoice No</th>
                                                        <th>Description</th>
                                                        <th>Liability Account</th>
                                                        <th>Expenses Account</th>
                                                        <th>Units</th>
                                                        <th>Amount</th>
                                                        <th>Req. User</th>
                                                        <th>Accepted By</th>
                                                        <th>Accepted At</th>
                                                        <th>Pay</th>
                                                        <th>Complete</th>
                                                        <th>Cancel</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($acceptedUtilityBills)) : ?>
                                                        <?php $au = 1; ?>
                                                        <?php foreach ($acceptedUtilityBills as $bill) : ?>
                                                            <?php
                                                                $typeLabels = [
                                                                    'utility_bill'    => '<span class="badge bg-primary">Utility Bill</span>',
                                                                    'special_payment' => '<span class="badge bg-warning text-dark">Special Payment</span>',
                                                                    'petty_cash'      => '<span class="badge bg-secondary">Petty Cash</span>',
                                                                ];
                                                                $typeLabel = $typeLabels[$bill['payment_type']] ?? '<span class="badge bg-light text-dark">' . esc($bill['payment_type']) . '</span>';
                                                            ?>
                                                            <tr>
                                                                <td><?= $au++; ?></td>
                                                                <td><?= $typeLabel ?></td>
                                                                <td><?= esc($bill['date']); ?></td>
                                                                <td><?= esc($bill['inv_no']); ?></td>
                                                                <td><?= esc($bill['description']); ?></td>
                                                                <td><?= esc($bill['acc_name_liability']); ?></td>
                                                                <td><?= esc($bill['acc_name_expenses']); ?></td>
                                                                <td><?= esc($bill['units']); ?></td>
                                                                <td class="text-success fw-bold">Rs. <?= number_format($bill['amount'], 2) ?></td>
                                                                <td><?= esc($bill['cashier']); ?></td>
                                                                <td><?= esc($bill['accepted_by']); ?></td>
                                                                <td><?= esc($bill['accepted_at']); ?></td>

                                                                <!-- Pay -->
                                                                <td>
                                                                    <?php if ($bill['pay_status'] == 3): ?>
                                                                        <button class="btn btn-primary btn-sm" disabled>Paid</button>
                                                                        <br><small class="text-muted"><?= esc($bill['pay_status_by']); ?></small>
                                                                        <br><small class="text-muted"><?= esc($bill['pay_status_at']); ?></small>
                                                                    <?php elseif (!$bill['pay_status']): ?>
                                                                        <button class="btn btn-primary btn-sm btn-ub-pay" data-id="<?= esc($bill['id']); ?>">
                                                                            <i class="bi bi-cash"></i> Pay
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </td>

                                                                <!-- Complete -->
                                                                <td>
                                                                    <?php if ($bill['pay_status'] == 4): ?>
                                                                        <button class="btn btn-success btn-sm" disabled>Completed</button>
                                                                        <br><small class="text-muted"><?= esc($bill['pay_status_by']); ?></small>
                                                                        <br><small class="text-muted"><?= esc($bill['pay_status_at']); ?></small>
                                                                    <?php elseif ($bill['pay_status'] == 3): ?>
                                                                        <button class="btn btn-success btn-sm btn-ub-complete" data-id="<?= esc($bill['id']); ?>">
                                                                            <i class="bi bi-check2-all"></i> Complete
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </td>

                                                                <!-- Cancel -->
                                                                <td>
                                                                    <?php if ($bill['pay_status'] == 5): ?>
                                                                        <button class="btn btn-danger btn-sm" disabled>Cancelled</button>
                                                                        <br><small class="text-muted"><?= esc($bill['pay_status_by']); ?></small>
                                                                        <br><small class="text-muted"><?= esc($bill['pay_status_at']); ?></small>
                                                                    <?php elseif (!$bill['pay_status']): ?>
                                                                        <button class="btn btn-danger btn-sm btn-ub-cancel" data-id="<?= esc($bill['id']); ?>">
                                                                            <i class="bi bi-x-circle"></i> Cancel
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else : ?>
                                                        <tr>
                                                            <td colspan="15" class="text-center text-muted">No accepted utility bill payments found.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?= view('layouts/brand') ?>

        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/scripts') ?>

    <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function downloadExcel(el) {
            const table = document.getElementById(el);
            const workbook = XLSX.utils.table_to_book(table, {
                raw: true
            });
            XLSX.writeFile(workbook, 'Tour_Details.xlsx');
        }

        $(".request-button").click(function() {

            const id = this.getAttribute('data-id');
            // const hotelAcc = this.getAttribute('data-hotel-acc');

            // Show a confirmation dialog using SweetAlert before proceeding
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to complete the request?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Complete!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If the user confirms, proceed with the action
                    $(this).prop("disabled", true).text("Processing...").hide(); // Disable and hide the button

                    $.post("<?php echo base_url() ?>tour/tour_accounts/payment_request_final", { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                        payment_request: "data",
                        id: id,
                        // hotelAcc: hotelAcc,

                    }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Successfully Completed!',
                                showConfirmButton: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload();
                                }
                            });
                        } else if ($.trim(data.status) === 400) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Error!',
                                text: 'Error occurred.',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Oops...',
                                text: 'Something went wrong!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    }, "json").fail(function (x) {   // tf-paydesk-r7f: a refused request shows the server's reason
                        Swal.fire({ icon: 'error', title: 'Not done', text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong. Please try again.' });
                    }).always(() => {
                        // Reset the flag or perform any cleanup here if needed
                    });
                }
            });
        });




        $(".complete-transport").click(function() {

            const tourId = this.getAttribute('data-tour-id');


            // Show a confirmation dialog using SweetAlert before proceeding
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to complete this payment?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, complete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If the user confirms, proceed with the action
                    $(this).prop("disabled", true).text("Processing...").hide(); // Disable and hide the button

                    $.post("<?php echo base_url() ?>tour/tour_accounts/update_transport", {
                        update: "data",
                        tourId: tourId,

                    }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Successfully Completed!',
                                showConfirmButton: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload(); // Reload the page on success
                                }
                            });
                        } else if ($.trim(data.status) === 400) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Error!',
                                text: 'Error occurred.',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Oops...',
                                text: 'Something went wrong!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    }, "json").always(() => {
                        // Reset the flag or perform any cleanup here if needed
                    });
                }
            });
        });

        // Initialize Posted Expenses DataTable
        $('#postedExpensesTable').DataTable({
            order: [[3, 'asc']],
            pageLength: 10
        });

        // Initialize Accepted Utility Bills DataTable
        $('#acceptedUtilityBillTable').DataTable({
            order: [[1, 'asc']],
            pageLength: 25
        });

        // Utility Bill - Pay (open modal)
        $(document).on('click', '.btn-ub-pay', function() {
            $('#ub_pay_id').val($(this).data('id'));
            $('#ub_pay_amount').val($(this).closest('tr').find('td:nth-child(8)').text().replace('Rs.', '').trim().replace(/,/g, ''));
            $('#ub_pay_acc').val($(this).closest('tr').find('td:nth-child(5)').text().trim());
            // reset form fields
            $('#ub_v_type').val('2');
            $('#ub_ref_no').val('');
            $('#ub_bank').val('');
            $('input[name="ub_p_method"][value="BT"]').prop('checked', true);
            $('#ub_bank_row').show();
            $('#btn_ub_pay_confirm').prop('disabled', false).text('Confirm Pay');
            $('#ubPayModal').modal('show');
        });

        // show/hide bank based on payment method
        $(document).on('change', 'input[name="ub_p_method"]', function() {
            if ($(this).val() === 'Petty Cash' || $(this).val() === 'Cash') {
                $('#ub_bank_row').hide();
                $('#ub_bank').removeAttr('required');
            } else {
                $('#ub_bank_row').show();
                $('#ub_bank').attr('required', 'required');
            }
        });


        // Utility Bill - Complete
        $(document).on('click', '.btn-ub-complete', function() {
            const btn = $(this);
            const id  = btn.data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: 'Mark this utility bill as Complete?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#78b848',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Complete!'
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.prop('disabled', true).text('Processing...');
                    $.post('<?= base_url('accounts/utility_bill/update_status') ?>', { id: id, status: 4 }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({ icon: 'success', title: 'Successfully Completed!', showConfirmButton: true })
                                .then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error!', text: data.message, timer: 2000, showConfirmButton: false });
                            btn.prop('disabled', false).text('Complete');
                        }
                    }, 'json');
                }
            });
        });

        // Utility Bill - Cancel
        $(document).on('click', '.btn-ub-cancel', function() {
            const btn = $(this);
            const id  = btn.data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: 'Cancel this utility bill payment?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Cancel!'
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.prop('disabled', true).text('Processing...');
                    $.post('<?= base_url('accounts/utility_bill/update_status') ?>', { id: id, status: 5 }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({ icon: 'success', title: 'Successfully Cancelled!', showConfirmButton: true })
                                .then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error!', text: data.message, timer: 2000, showConfirmButton: false });
                            btn.prop('disabled', false).text('Cancel');
                        }
                    }, 'json');
                }
            });
        });

        //tour_dash button click
        $(document).on('click', '.view', function(e) {
            e.preventDefault();
            var year = $(this).data('year');
            var month = $(this).data('month');
            var tour = $(this).data('tour_id');

            var url = '<?php echo base_url(); ?>tour/tour_dashboard/monthly_dashboard?year=' + year + '&month=' + month + '&tour_id=' + tour;
            var a = document.createElement('a');
            a.href = url;
            a.target = '_blank';
            a.click();
        });

        // Confirm Pay — delegated so it works regardless of DOM order
        $(document).on('click', '#btn_ub_pay_confirm', function() {
            const confirmBtn = $(this);
            const id        = $('#ub_pay_id').val();
            const v_type    = $('#ub_v_type').val();
            const ref_no    = $('#ub_ref_no').val().trim();
            const p_method  = $('input[name="ub_p_method"]:checked').val();
            const bank      = $('#ub_bank').val();
            const bank_name = $('#ub_bank option:selected').text();
            const amount    = $('#ub_pay_amount').val();

            if (!ref_no) {
                Swal.fire({ icon: 'warning', title: 'Required', text: 'Please enter a reference number.', timer: 1500, showConfirmButton: false });
                return;
            }
            if ((p_method === 'BT' || p_method === 'Cheque') && !bank) {
                Swal.fire({ icon: 'warning', title: 'Required', text: 'Please select a bank account.', timer: 1500, showConfirmButton: false });
                return;
            }

            confirmBtn.prop('disabled', true).text('Processing...');
            $('#ubPayModal').modal('hide');

            $.post('<?= base_url('accounts/utility_bill/pay') ?>', {
                id: id, v_type: v_type, ref_no: ref_no,
                p_method: p_method, bank: bank, bank_name: bank_name, amount: amount
            }, function(data) {
                if (data.status === 200) {
                    Swal.fire({ icon: 'success', title: 'Paid Successfully!', showConfirmButton: true })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error!', text: data.message });
                    confirmBtn.prop('disabled', false).text('Confirm Pay');
                    $('#ubPayModal').modal('show');
                }
            }, 'json').fail(function() {
                Swal.fire({ icon: 'error', title: 'Server Error', text: 'Failed to communicate with server.' });
                confirmBtn.prop('disabled', false).text('Confirm Pay');
                $('#ubPayModal').modal('show');
            });
        });

    </script>

<!-- Utility Bill Pay Modal -->
<div class="modal fade" id="ubPayModal" tabindex="-1" aria-labelledby="ubPayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ubPayModalLabel">Utility Bill Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ub_pay_id">
                <input type="hidden" id="ub_pay_amount">
                <input type="hidden" id="ub_pay_acc">

                <div class="mb-3 row">
                    <label class="col-md-4 col-form-label text-end">Voucher Type</label>
                    <div class="col-md-7">
                        <select class="form-control" id="ub_v_type">
                            <option value="1">Voucher Payment</option>
                            <option value="2" selected>Supplier Account</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label class="col-md-4 col-form-label text-end">Reference No <span class="text-danger">*</span></label>
                    <div class="col-md-7">
                        <input type="text" class="form-control" id="ub_ref_no" autocomplete="off">
                    </div>
                </div>

                <div class="mb-3 row">
                    <label class="col-md-4 col-form-label text-end">Payment Method</label>
                    <div class="col-md-7 pt-2">
                        <input type="radio" name="ub_p_method" value="BT" checked> <label class="me-3">BT</label>
                        <input type="radio" name="ub_p_method" value="Cheque"> <label class="me-3">Cheque</label>
                        <input type="radio" name="ub_p_method" value="Cash"> <label class="me-3">Cash</label>
                        <input type="radio" name="ub_p_method" value="Petty Cash"> <label>Petty Cash</label>
                    </div>
                </div>

                <div class="mb-3 row" id="ub_bank_row">
                    <label class="col-md-4 col-form-label text-end">Bank Account <span class="text-danger">*</span></label>
                    <div class="col-md-7">
                        <select class="form-control" id="ub_bank">
                            <option value=""></option>
                            <?php foreach ($load_bank ?? [] as $bank): ?>
                                <option value="<?= esc($bank['L_Code']) ?>"><?= esc($bank['PName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn_ub_pay_confirm">
                    <i class="bi bi-cash"></i> Confirm Pay
                </button>
            </div>
        </div>
    </div>
</div>

</body>

</html>