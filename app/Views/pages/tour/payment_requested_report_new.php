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

                    <div class="row mb-4 align-items-center">
                        <div class="col-md-auto">
                            <h4 class="text-primary font-weight-bold mb-0">Payment Approval Details Report (SA)</h4>
                        </div>
                        <?php if (!empty($bank_balances)): ?>
                        <div class="col d-flex flex-wrap gap-2 align-items-center justify-content-end">
                            <?php foreach ($bank_balances as $bank): ?>
                            <div class="border rounded px-3 py-1 bg-white shadow-sm d-flex align-items-center gap-2">
                                <i class="bi bi-bank text-secondary" style="font-size:0.8rem;"></i>
                                <div>
                                    <div class="text-muted" style="font-size:0.65rem;"><?= esc($bank['PName']) ?></div>
                                    <div class="fw-bold <?= (float)$bank['balance'] < 0 ? 'text-danger' : 'text-success' ?>" style="font-size:0.85rem;">
                                        <?= number_format((float)$bank['balance'], 2) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-auto text-end ms-auto">
                            <h6 class="text-secondary mb-0"><?= date('Y-m-d') ?></h6>
                            <a href="<?= base_url('/tour/tour_accounts/payment_requested_dashboard_new') ?>" class="btn btn-secondary btn-sm">
                                <i class="ri-arrow-left-line"></i> Back to Payment Approval Dashboard
                            </a>
                        </div>
                        <!-- Table -->
                        <div class="container-fluid py-4">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card shadow-lg border-0 rounded">
                                        <div class="card-header bg-gradient-light text-dark">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0">Payment Requests</h5>
                                            </div>
                                            <?php if (!empty($tourInfo)): ?>
                                                <div class="d-flex gap-4 mt-2">
                                                    <span><strong>Manual ID:</strong> <?= esc($tourInfo['manual_id']); ?></span>
                                                    <span><strong>Start Date:</strong> <?= $tourInfo['start_date']; ?></span>
                                                    <span><strong>End Date:</strong> <?= $tourInfo['end_date']; ?></span>
                                                    <span><strong>Tour Name:</strong> <?= $tourInfo['consignee']; ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="card-body">
                                            <!-- Bulk action bar -->
                                            <div class="d-flex gap-2 mb-3 align-items-center" id="bulkActionBar">
                                                <span class="text-muted me-2" id="selectedCount">0 selected</span>
                                                <button class="btn btn-success btn-sm" id="bulkAcceptBtn" disabled>
                                                    <i class="ri-check-line"></i> Accept Selected
                                                </button>
                                                <button class="btn btn-danger btn-sm" id="bulkRejectBtn" disabled>
                                                    <i class="ri-close-line"></i> Reject Selected
                                                </button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover align-middle" id="mainTable">
                                                    <thead class="bg-light text-primary">
                                                        <tr>
                                                            <th><input type="checkbox" id="selectAll" title="Select all"></th>
                                                            <th>#</th>
                                                            <th>Bulk</th>
                                                            <th>Tour Name</th>
                                                            <th>Payee</th>
                                                            <th>Quoted Payment</th>
                                                            <th>Advance Payment</th>
                                                            <th>Balance</th>
                                                            <th>Requested Amount</th>
                                                            <th>Payment Request Date</th>
                                                            <th>Payment Request User</th>
                                                            <!-- <th>Action</th> -->
                                                             <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (!empty($paymentRequests)) : ?>
                                                            <?php $i = 1; ?>
                                                            <?php foreach ($paymentRequests as $request) : ?>
                                                                <tr>
                                                                    <td><input type="checkbox" class="row-check" value="<?= $request['id'] ?>" data-type="<?= $request['type']; ?>"></td>
                                                                    <td><?= $i++; ?></td>
                                                                    <td><?= $request['is_bulk'] == 1 ? '<span class="badge bg-warning text-dark">Bulk</span>' : '' ?></td>
                                                                    <td><?= esc($tourInfo['consignee'] ?? $request['consignee']); ?></td>
                                                                    <td><?= esc(!empty($request['hotel_name']) ? $request['hotel_name'] : $request['consignee']); ?><br><span class="text-muted">(<?= esc($request['create_user']); ?>)</span></td>
                                                                    <?php $currency = (($request['type'] ?? '') === 'lunch' || ($request['create_user'] ?? '') === 'Transport Agent') ? 'LKR' : '$'; ?>
                                                                    <td class="text-primary"><?= $currency ?> <?= $request['total_hotel_pay']; ?></td>
                                                                    <td class="text-success"><?= $currency ?> <?= $request['total_hotel_advance']; ?></td>
                                                                    <td class="text-danger"><?= $currency ?> <?= $request['due_amount']; ?></td>
                                                                    <td class="text-danger">
                                                                        <?= $currency ?> <?= $request['requestAmount']; ?>
                                                                        <?php if (($request['type'] ?? '') === 'Hotel' && !empty($request['lkr_amount'])): ?>
                                                                            <br><small class="text-success fw-semibold">LKR <?= number_format($request['lkr_amount'], 2) ?> <span class="text-muted fw-normal">(@ <?= number_format($request['dollar_rate'], 2) ?>)</span></small>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?= $request['payment_request_date']; ?></td>
                                                                    <td><?= esc($request['payment_request_user']); ?></td>
                                                                    <!-- <td>
                                                                        <button class="btn btn-success btn-sm accept-button" data-id="<?//= $request['id']; ?>"
                                                                        data-type="<?//= $request['type']; ?>" data-bulk="<?//= $request['is_bulk']; ?>"
                                                                        data-request-amount="<?//= $request['requestAmount']; ?>">
                                                                            <i class="ri-check-line"></i> Accept
                                                                        </button>
                                                                        <button class="btn btn-danger btn-sm reject-button" data-id="<?//= $request['id']; ?>" data-type="<?//= $request['type']; ?>" data-bulk="<?//= $request['is_bulk']; ?>">
                                                                            <i class="ri-close-line"></i> Reject
                                                                        </button>
                                                                    </td> -->
<td></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else : ?>
                                                            <tr>
                                                                <td colspan="11" class="text-center text-muted">No payment requests found.</td>
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

        <div class="modal fade" id="AcceptHotelBankModal" tabindex="-1" aria-labelledby="AcceptHotelBankModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="AcceptHotelBankModalLabel">Accept Payment Request</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="javascript:void(0);" id="accept_hotel_bank_form">
                            <input type="hidden" id="ab_id">
                            <input type="hidden" id="ab_type">
                            <input type="hidden" id="ab_bulk">
                            <div class="mb-3">
                                <label class="form-label">Bank Account<font color="#FF0000"><strong>*</strong></font></label>
                                <select id="ab_bank_acc" class="form-control" style="width: 100%;" required>
                                    <option value="">Select a Bank...</option>
                                    <?php
                                    foreach ($bank_acc as $bank) {
                                        echo "<option value='" . $bank['L_Code'] . "'>" . $bank['PName'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date<font color="#FF0000"><strong>*</strong></font></label>
                                <input type="date" id="ab_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-success" style="float: right;">
                                    Accept
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="BulkAcceptBankModal" tabindex="-1" aria-labelledby="BulkAcceptBankModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="BulkAcceptBankModalLabel">Accept Payment Requests</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="javascript:void(0);" id="bulk_accept_bank_form">
                            <input type="hidden" id="bab_ids">
                            <input type="hidden" id="bab_status">
                            <div class="mb-3">
                                <label class="form-label">Bank Account<font color="#FF0000"><strong>*</strong></font></label>
                                <select id="bab_bank_acc" class="form-control" style="width: 100%;" required>
                                    <option value="">Select a Bank...</option>
                                    <?php
                                    foreach ($bank_acc as $bank) {
                                        echo "<option value='" . $bank['L_Code'] . "'>" . $bank['PName'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date<font color="#FF0000"><strong>*</strong></font></label>
                                <input type="date" id="bab_date" class="form-control" placeholder="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-success" style="float: right;">
                                    Accept
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
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
                        }, "json").fail(function (x) {   // tf-paydesk-r7f: a refused request shows the server's reason
                            Swal.fire({ icon: 'error', title: 'Not done', text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong. Please try again.' });
                        }).always(() => {
                            // Reset the flag or perform any cleanup here if needed
                        });
                    }
                });
            });

            // cancel request button click
            $(".cancel-button").click(function() {
                const id = this.getAttribute('data-id');
                const tourId = this.getAttribute('data-tour');
                const hotelAcc = this.getAttribute('data-hotel');

                // alert(id);
                // alert(tourId);
                // alert(hotelAcc);

                // Show a confirmation dialog using SweetAlert before proceeding
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to cancel this payment request?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, cancel it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // If the user confirms, proceed with the action
                        $(this).prop("disabled", true).text("Processing...").hide(); // Disable and hide the button

                        $.post("<?php echo base_url() ?>tour/tour_accounts/cancel_payment_request", { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                            id: id,
                            tour_id: tourId,
                            hotel_acc: hotelAcc
                        }, function(data) {
                            if (data.status === 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Successfully Canceled!',
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
                        }, "json").fail(function (x) {   // tf-paydesk-r7f: a refused request shows the server's reason
                            Swal.fire({ icon: 'error', title: 'Not done', text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong. Please try again.' });
                        }).always(() => {
                            // Reset the flag or perform any cleanup here if needed
                        });
                    }
                });
            });


            // $(".complete-transport").click(function() {

            //     const tourId = this.getAttribute('data-tour-id');


            //     // Show a confirmation dialog using SweetAlert before proceeding
            //     Swal.fire({
            //         title: 'Are you sure?',
            //         text: "You want to complete this payment?",
            //         icon: 'warning',
            //         showCancelButton: true,
            //         confirmButtonColor: '#3085d6',
            //         cancelButtonColor: '#d33',
            //         confirmButtonText: 'Yes, complete it!'
            //     }).then((result) => {
            //         if (result.isConfirmed) {
            //             // If the user confirms, proceed with the action
            //             $(this).prop("disabled", true).text("Processing...").hide(); // Disable and hide the button

            //             $.post("<?php echo base_url() ?>tour/tour_accounts/update_transport", {
            //                 update: "data",
            //                 tourId: tourId,

            //             }, function(data) {
            //                 if (data.status === 200) {
            //                     Swal.fire({
            //                         icon: 'success',
            //                         title: 'Successfully Completed!',
            //                         showConfirmButton: true
            //                     }).then((result) => {
            //                         if (result.isConfirmed) {
            //                             location.reload(); // Reload the page on success
            //                         }
            //                     });
            //                 } else if ($.trim(data.status) === 400) {
            //                     Swal.fire({
            //                         icon: 'warning',
            //                         title: 'Error!',
            //                         text: 'Error occurred.',
            //                         showConfirmButton: false,
            //                         timer: 1500
            //                     });
            //                 } else {
            //                     Swal.fire({
            //                         icon: 'warning',
            //                         title: 'Oops...',
            //                         text: 'Something went wrong!',
            //                         showConfirmButton: false,
            //                         timer: 1500
            //                     });
            //                 }
            //             }, "json").always(() => {
            //                 // Reset the flag or perform any cleanup here if needed
            //             });
            //         }
            //     });
            // });

            $(".complete-transport").click(function() {

                const tourId = this.getAttribute('data-tour-id');
                const driverId = this.getAttribute('data-driver-id');
                const amount = this.getAttribute('data-amount');

                window.open('<?= base_url('dashboard2/transport/driver_advance?tour_id=') ?>' + tourId + '&driver_id=' + driverId + '&amount=' + amount, '_blank');

            });

            $(".complete-hotel").click(function() {

                const tourId = this.getAttribute('data-tour-id');
                const hotelId = this.getAttribute('data-hotel-id');
                const amount = this.getAttribute('data-amount');

                window.open('<?= base_url('dashboard2/hotel_management/hotel_booking?tour_id=') ?>' + tourId + '&hotel_id=' + hotelId + '&amount=' + amount, '_blank');

            });

            function submitAccept(id, type, isBulk, bank, bankName, payDate) {
                $.post("<?php echo base_url() ?>tour/tour_accounts/update_payment_request_status", { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                    id: id,
                    type: type,
                    status: '4',
                    is_bulk: isBulk,
                    bank_acc: bank || '',
                    bank_name: bankName || '',
                    payment_date: payDate || '<?= date('Y-m-d') ?>'
                }, function(data) {
                    if (data.status === 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Accepted!',
                            text: data.message,
                            showConfirmButton: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'Something went wrong!',
                            showConfirmButton: true
                        });
                    }
                }, "json").fail(function(x) {   // tf-paydesk-r7f: the server's reason
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: (x && x.responseJSON && x.responseJSON.message) || 'Request failed. Please try again.',
                        showConfirmButton: true
                    });
                });
            }

            // Accept button click
            $(".accept-button").click(function() {
                const id = this.getAttribute('data-id');
                const type = this.getAttribute('data-type');
                const isBulk = this.getAttribute('data-bulk');
                const btn = this;

                if (type === 'Hotel' || type === 'lunch' || type === 'Transport Advance') {
                    $("#ab_id").val(id);
                    $("#ab_type").val(type);
                    $("#ab_bulk").val(isBulk);
                    $("#ab_bank_acc").val('');
                    $("#AcceptHotelBankModal").data('trigger-btn', btn).modal('show');
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to accept this payment request?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#78b848',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Accept!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).prop("disabled", true).text("Processing...");
                        submitAccept(id, type, isBulk);
                    }
                });
            });

            // Accept Hotel Bank modal submit
            $("#accept_hotel_bank_form").on('submit', function(e) {
                e.preventDefault();

                const id = $("#ab_id").val();
                const type = $("#ab_type").val();
                const isBulk = $("#ab_bulk").val();
                const bank = $("#ab_bank_acc").val();
                const bankName = $("#ab_bank_acc option:selected").text();
                const payDate = $("#ab_date").val() || new Date().toISOString().split('T')[0];

                if (!bank) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please select a bank account',
                    });
                    return;
                }

                const btn = $("#AcceptHotelBankModal").data('trigger-btn');
                $("#AcceptHotelBankModal").modal('hide');
                if (btn) {
                    $(btn).prop("disabled", true).text("Processing...");
                }
                submitAccept(id, type, isBulk, bank, bankName, payDate);
            });

            // Checkbox logic
            function getSelectedIds() {
                return $('.row-check:checked').map(function() { return $(this).val(); }).get();
            }

            function updateBulkBar() {
                const ids = getSelectedIds();
                $('#selectedCount').text(ids.length + ' selected');
                $('#bulkAcceptBtn, #bulkRejectBtn').prop('disabled', ids.length === 0);
                if (ids.length > 0) {
                    $('#bulkActionBar').show();
                } else {
                    $('#bulkActionBar').show(); // always visible
                }
            }

            $('#selectAll').on('change', function() {
                $('.row-check').prop('checked', this.checked);
                updateBulkBar();
            });

            $(document).on('change', '.row-check', function() {
                const total = $('.row-check').length;
                const checked = $('.row-check:checked').length;
                $('#selectAll').prop('checked', total === checked);
                updateBulkBar();
            });

            function submitBulk(ids, status, bank, bankName, payDate) {
                $.post("<?php echo base_url() ?>tour/tour_accounts/bulk_update_payment_request_status", { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                    ids: ids,
                    status: status,
                    bank_acc: bank || '',
                    bank_name: bankName || '',
                    payment_date: payDate || '<?= date('Y-m-d') ?>'
                }, function(data) {
                    if (data.status === 200) {
                        Swal.fire({ icon: 'success', title: 'Done!', text: data.message, showConfirmButton: false, timer: 1500 })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error!', text: data.message });
                    }
                }, 'json').fail(function (x) {   // tf-paydesk-r7f: a refused request shows the server's reason
                    Swal.fire({ icon: 'error', title: 'Not done', text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong. Please try again.' });
                });
            }

            function doBulkAction(status) {
                const ids = getSelectedIds();
                if (ids.length === 0) return;

                if (status === '5') {
                    Swal.fire({
                        title: `Reject ${ids.length} request(s)?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Reject!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            submitBulk(ids, status);
                        }
                    });
                    return;
                }

                const types = $('.row-check:checked').map(function() { return $(this).data('type'); }).get();
                const needsVoucher = types.some(t => t === 'Hotel' || t === 'lunch' || t === 'Transport Advance');

                if (!needsVoucher) {
                    Swal.fire({
                        title: `Accept ${ids.length} request(s)?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#78b848',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Accept!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            submitBulk(ids, status);
                        }
                    });
                    return;
                }

                $("#bab_ids").val(ids.join(','));
                $("#bab_status").val(status);
                $("#bab_bank_acc").val('');
                $("#BulkAcceptBankModal").modal('show');
            }

            $('#bulkAcceptBtn').on('click', function() { doBulkAction('4'); });
            $('#bulkRejectBtn').on('click', function() { doBulkAction('5'); });

            // Bulk accept bank modal submit
            $("#bulk_accept_bank_form").on('submit', function(e) {
                e.preventDefault();

                const ids = $("#bab_ids").val().split(',').filter(Boolean);
                const status = $("#bab_status").val();
                const bank = $("#bab_bank_acc").val();
                const bankName = $("#bab_bank_acc option:selected").text();
                const payDate = $("#bab_date").val() || new Date().toISOString().split('T')[0];

                if (!bank) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a bank account' });
                    return;
                }

                $("#BulkAcceptBankModal").modal('hide');
                submitBulk(ids, status, bank, bankName, payDate);
            });

            // Reject button click
            $(".reject-button").click(function() {
                const id = this.getAttribute('data-id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to reject this payment request?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Reject!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(this).prop("disabled", true).text("Processing...");

                        $.post("<?php echo base_url() ?>tour/tour_accounts/update_payment_request_status", { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                            id: id,
                            status: '5'
                        }, function(data) {
                            if (data.status === 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Rejected!',
                                    text: data.message,
                                    showConfirmButton: true
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: data.message || 'Something went wrong!',
                                    showConfirmButton: true
                                });
                            }
                        }, "json").fail(function(x) {   // tf-paydesk-r7f: the server's reason
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: (x && x.responseJSON && x.responseJSON.message) || 'Request failed. Please try again.',
                                showConfirmButton: true
                            });
                        });
                    }
                });
            });
        </script>

</body>

</html>