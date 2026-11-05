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
                            <h4 class="text-primary font-weight-bold">Payment Request Report</h4>
                        </div>
                        <div class="col-md-3 text-end">
                            <h6 class="text-secondary"><?= date('Y-m-d') ?></h6>
                            <a href="<?= base_url('/tour/tour_accounts/payment_requested_dashboard') ?>" class="btn btn-outline-secondary btn-sm">Back</a>
                        </div>
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
                                        </div>
                                        <?php endif; ?>
                                    </div>


                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover align-middle" id="mainTable">
                                                <thead class="bg-light text-primary">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Tour Name</th>
                                                        <th>Hotel Name</th>
                                                        <!-- <th>Hotel Date</th> -->
                                                        <th>Bank Account</th>
                                                        <th>Payable Payment</th>
                                                        <th>Advance Payment</th>
                                                        <th>Balance</th>
                                                        <th>Requested Amount</th>
                                                        <th>Complete</th>
                                                        <th>Cancel</th>
                                                        <th>Pay</th>
                                                        <th>Payment Req Details</th>
                                                        <th>Payment Accepted Details</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($paymentRequests)) : ?>
                                                        <?php $i = 1; ?>
                                                        <?php foreach ($paymentRequests as $request) : ?>
                                                            <tr>
                                                                <td><?= $i++; ?></td>
                                                                <td><?= esc($request['consignee']); ?><br><span class="text-muted">(<?= esc($request['create_user']); ?>)</span></td>
                                                                <td><?= esc($request['hotel_name']); ?></td>
                                                                <td><?= esc($request['bank_name']); ?><br><?= esc($request['branch_name']); ?><br><?= esc($request['sup_bank_acc']); ?></td>
                                                                <?php $currency = (($request['type'] ?? '') === 'lunch' || ($request['create_user'] ?? '') === 'Transport Agent') ? 'LKR' : '$'; ?>
                                                                <td class="text-primary"><?= $currency ?> <?= $request['total_hotel_pay']; ?></td>
                                                                <td class="text-success"><?= $currency ?> <?= $request['total_hotel_advance']; ?></td>
                                                                <td class="text-danger"><?= $currency ?> <?= $request['due_amount']; ?></td>
                                                                <td class="text-danger"><?= $currency ?> <?= $request['requestAmount']; ?></td>

                                                                <td>
                                                                    <?php
                                                                    $hotel_paid     = ($request['hotel_payment_flag'] ?? 0) >= 1;
                                                                    $is_transport   = $request['create_user'] === 'Transport Agent';
                                                                    $transport_paid = $is_transport && ($request['transport_advance'] ?? 0) == 1;
                                                                    if ($request['payment_request'] == 2): ?>
                                                                        <button class="btn btn-success btn-sm" disabled>Completed</button>
                                                                    <?php elseif ($hotel_paid || $transport_paid): ?>
                                                                        <button class="btn btn-success btn-sm request-button" data-id="<?= $request['id']; ?>">Complete</button>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td> <button class="btn btn-danger btn-sm cancel-button" data-id="<?= $request['id']; ?>" data-tour="<?= $request['tour_id']; ?>" data-hotel="<?= $request['hotel_acc']; ?>" <?php if ($request['payment_request'] == 2) {
                                                                                                                                                                                                                                                    echo 'disabled';
                                                                                                                                                                                                                                                } else {
                                                                                                                                                                                                                                                    echo '';
                                                                                                                                                                                                                                                } ?>><?php if ($request['payment_request'] == 2) {
                                                                                                                                                                        echo 'Canceled';
                                                                                                                                                                    } else {
                                                                                                                                                                        echo 'Cancel';
                                                                                                                                                                    } ?></button></td>
                                                                <td>
                                                                    <?php if (($request['type'] ?? '') === 'lunch') : ?>
                                                                        <button class="btn btn-warning btn-sm complete-lunch"
                                                                            data-id="<?= $request['id']; ?>"
                                                                            data-tour-id="<?= $request['tour_id']; ?>"
                                                                            data-amount="<?= $request['requestAmount']; ?>"
                                                                            <?= $request['payment_request'] == 2 ? 'disabled' : '' ?>>
                                                                            <?= $request['payment_request'] == 2 ? 'Paid' : 'Pay' ?>
                                                                        </button>
                                                                    <?php elseif ($request['create_user'] == 'Transport Agent') : ?>
                                                                        <button class="btn btn-primary btn-sm complete-transport" data-tour-id="<?= $request['tour_id']; ?>" data-driver-id="<?= $request['hotel_acc']; ?>" data-amount="<?= $request['requestAmount']; ?>">Pay</button>
                                                                    <?php else: ?>
                                                                        <button class="btn btn-info btn-sm complete-hotel" data-tour-id="<?= $request['tour_id']; ?>" data-hotel-id="<?= $request['hotel_acc']; ?>" data-amount="<?= $request['requestAmount']; ?>">Pay</button>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?= $request['payment_request_date'] ? date('Y-m-d H:i:s', strtotime($request['payment_request_date'])) : ''; ?><br><?= esc($request['payment_request_user']); ?></td>
                                                                <td><?= $request['accept_date'] ? date('Y-m-d H:i:s', strtotime($request['accept_date'])) : ''; ?><br><?= esc($request['accept_user']); ?></td>
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


                </div>
            </div>

            <?= view('layouts/brand') ?>

        </div>
    </div>

    <!-- Bank Account Selection Modal (Lunch) -->
    <div class="modal fade" id="bankAccountModal" tabindex="-1" aria-labelledby="bankAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bankAccountModalLabel">Select Bank Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Bank Account <font color="#FF0000"><strong>*</strong></font></label>
                        <select id="modal_bank_acc" class="form-control" required>
                            <option value="">Select a Bank...</option>
                            <?php if (!empty($bank_acc)): ?>
                                <?php foreach ($bank_acc as $bank): ?>
                                    <option value="<?= $bank['L_Code'] ?>"><?= esc($bank['PName']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmBankBtn">Proceed to Pay</button>
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

        let _lunchId = null, _lunchTourId = null, _lunchAmount = null, _lunchBtn = null;

        $(".complete-lunch").click(function() {
            _lunchId = this.getAttribute('data-id');
            _lunchTourId = this.getAttribute('data-tour-id');
            _lunchAmount = this.getAttribute('data-amount');
            _lunchBtn = $(this);
            $('#modal_bank_acc').val('');
            $('#bankAccountModal').modal('show');
        });

        $('#confirmBankBtn').click(function() {
            const bankAcc = $('#modal_bank_acc').val();
            const bankName = $('#modal_bank_acc option:selected').text();
            if (!bankAcc) {
                Swal.fire({ icon: 'warning', title: 'Required', text: 'Please select a bank account.', showConfirmButton: false, timer: 1500 });
                return;
            }
            $('#bankAccountModal').modal('hide');

            Swal.fire({
                title: 'Are you sure?',
                text: 'Create lunch payment voucher for LKR ' + _lunchAmount + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Pay!'
            }).then((result) => {
                if (result.isConfirmed) {
                    _lunchBtn.prop('disabled', true).text('Processing...');
                    $.post('<?= base_url('tour/tour_accounts/lunch_pay') ?>', { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                        id: _lunchId,
                        tour_id: _lunchTourId,
                        amount: _lunchAmount,
                        pay_date: '<?= date('Y-m-d') ?>',
                        bank_acc: bankAcc,
                        bank_name: bankName
                    }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({ icon: 'success', title: 'Success!', text: data.message, showConfirmButton: true })
                                .then(() => window.location.href = '<?= base_url('tour/tour_accounts/payment_requested_dashboard') ?>');
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error!', text: data.message });
                            _lunchBtn.prop('disabled', false).text('Pay');
                        }
                    }, 'json').fail(function (x) {   // tf-paydesk-r7f: a refused request shows the server's reason
                        Swal.fire({ icon: 'error', title: 'Not done', text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong. Please try again.' });
                        _lunchBtn.prop('disabled', false).text('Pay');
                    });
                }
            });
        });
    </script>

</body>

</html>