<?= view('layouts/head') ?>
<style type="text/css">
    :root {
        --pd-radius: 14px;
    }

    body {
        background: #f4f6fb;
    }

    .pd-page-title {
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .pd-bank-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
    }

    .pd-bank-chip {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        background: #fff;
        border: 1px solid #edf0f5;
        border-radius: 12px;
        padding: 0.5rem 0.9rem;
        box-shadow: 0 2px 8px rgba(17, 24, 39, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .pd-bank-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(17, 24, 39, 0.08);
    }

    .pd-bank-chip .pd-bank-icon {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #eef2ff, #e0e7ff);
        color: #4338ca;
        font-size: 0.95rem;
        flex-shrink: 0;
    }

    .pd-bank-chip .pd-bank-name {
        font-size: 0.68rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        line-height: 1.1;
    }

    .pd-bank-chip .pd-bank-amt {
        font-size: 0.92rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .pd-section {
        border: none;
        border-radius: var(--pd-radius);
        overflow: hidden;
        box-shadow: 0 6px 20px rgba(17, 24, 39, 0.06);
    }

    .pd-section-header {
        border: none;
        padding: 1rem 1.35rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
        color: #fff;
    }

    .pd-section-header h5 {
        margin: 0;
        font-weight: 700;
        font-size: 1.02rem;
        display: flex;
        align-items: center;
        gap: 0.55rem;
        color: #fff;
    }

    .pd-section-header h5 .opacity-75 {
        color: rgba(255, 255, 255, 0.85);
    }

    .pd-section-header .badge {
        border-radius: 30px;
        padding: 0.5em 0.9em;
        font-weight: 700;
        font-size: 0.8rem;
    }

    .pd-header-requests {
        background: linear-gradient(120deg, #2563eb, #4f46e5);
    }

    .pd-header-expenses {
        background: linear-gradient(120deg, #059669, #10b981);
    }

    .pd-header-utility {
        background: linear-gradient(120deg, #d97706, #f59e0b);
    }

    .pd-section .card-body {
        background: #fff;
        padding: 1.25rem;
    }

    .pd-totals-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1.5rem;
        justify-content: flex-end;
        margin-bottom: 1rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px dashed #e5e7eb;
    }

    .pd-totals-bar .pd-total-item {
        font-size: 0.85rem;
        color: #6b7280;
    }

    .pd-totals-bar .pd-total-item strong {
        margin-left: 0.35rem;
        font-size: 0.98rem;
    }

    .table {
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 10px;
        overflow: hidden;
    }

    .table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 700;
        border-bottom: 2px solid #eef1f6;
        white-space: nowrap;
    }

    .table tbody td {
        vertical-align: middle;
        font-size: 0.9rem;
        border-bottom: 1px solid #f1f3f7;
    }

    .table-hover tbody tr:hover {
        background-color: #f5f8ff;
        cursor: pointer;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #fbfcfe;
    }

    .btn-sm {
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.78rem;
    }

    .btn {
        transition: 0.2s ease;
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

                    <div class="row mb-4 align-items-center g-3">
                        <div class="col-md-auto">
                            <h4 class="pd-page-title text-dark mb-0">Payment Approval <span class="text-muted fw-normal">(SA)</span></h4>
                        </div>
                        <?php if (!empty($bank_balances)): ?>
                        <div class="col pd-bank-strip justify-content-md-end">
                            <?php foreach ($bank_balances as $bank): ?>
                            <div class="pd-bank-chip">
                                <div class="pd-bank-icon"><i class="bi bi-bank2"></i></div>
                                <div>
                                    <div class="pd-bank-name"><?= esc($bank['PName']) ?></div>
                                    <div class="pd-bank-amt <?= (float)$bank['balance'] < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= number_format((float)$bank['balance'], 2) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-auto text-end ms-auto">
                            <span class="badge rounded-pill bg-white text-secondary border px-3 py-2">
                                <i class="bi bi-calendar3 me-1"></i><?= date('Y-m-d') ?>
                            </span>
                        </div>
                    </div>


                    <!-- Table -->
                    <div class="container-fluid py-4">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card pd-section">
                                    <div class="card-header pd-section-header pd-header-requests">
                                        <h5><i class="bi bi-receipt"></i> Payment Requests</h5>
                                    </div>


                                    <div class="card-body">
                                        <div class="pd-totals-bar">
                                            <span class="pd-total-item">Total Payable ($)<strong class="text-primary">$ <?= number_format((float)($payment_requests_total_usd ?? 0), 2) ?></strong></span>
                                            <span class="pd-total-item">Total Payable (LKR)<strong class="text-success">LKR <?= number_format((float)($payment_requests_total_lkr ?? 0), 2) ?></strong></span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table js-basic-example table-hover dataTable table-custom align-middle" id="mainTable">
                                                <thead>
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
                                                        <!-- <th>Total Payable</th> -->
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
                                                                <!-- <td class="text-danger fw-bold">
                                                                    LKR <?= number_format($request['total_payable'] ?? 0, 0) ?>
                                                                </td> -->
                                                                <td>
                                                                    <?php if (esc($request['tour_cancel']) == 1) {
                                                                        echo '<button class="btn btn-danger btn-sm" disabled>Cancelled</button>';
                                                                    } else { ?>
                                                                        <button class="btn btn-primary btn-sm" data-id="<?= esc($request['manual_id']); ?>" onclick="window.open('<?= base_url('tour/tour_accounts/payment_requested_report_new/' . esc($request['manual_id'])) ?>', '_blank')">
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
                                                            <td colspan="6" class="text-center text-muted">No payment requests found.</td>
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

                    <!-- Approved Expenses Table -->
                    <div class="container-fluid py-4">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card pd-section">
                                    <div class="card-header pd-section-header pd-header-expenses">
                                        <h5><i class="bi bi-check-circle-fill"></i> Driver Settlement Requests <span class="fw-normal opacity-75">(Approved Expenses)</span></h5>
                                        <?php
                                        $grandTotalPayable = array_sum(array_column($approvedExpenses ?? [], 'total_lkr_payable'));
                                        ?>
                                        <span class="badge bg-white text-success">
                                            Total Payable: Rs. <?= number_format($grandTotalPayable, 2) ?>
                                        </span>
                                    </div>

                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover table-custom align-middle" id="approvedExpensesTable">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Manual ID</th>
                                                        <th>Tour Name</th>
                                                        <th>Driver(s)</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Total Payable</th>
                                                        <th>View</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($approvedExpenses)) : ?>
                                                        <?php $j = 1; ?>
                                                        <?php foreach ($approvedExpenses as $expense) : ?>
                                                            <tr>
                                                                <td><?= $j++; ?></td>
                                                                <td><strong class="text-danger"><?= esc($expense['manual_id']); ?></strong></td>
                                                                <td><?= esc($expense['consignee']); ?><br><span class="text-muted">(<?= esc($expense['create_user']); ?>)</span></td>
                                                                <td>
                                                                    <?php if (!empty($expense['driver_names'])): ?>
                                                                        <?php foreach (explode(', ', $expense['driver_names']) as $dname): ?>
                                                                            <div><i class="bi bi-person-fill text-primary"></i> <?= esc(trim($dname)); ?></div>
                                                                        <?php endforeach; ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">-</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?= $expense['start_date']; ?></td>
                                                                <td><?= $expense['end_date']; ?></td>
                                                                <td class="text-danger fw-bold">Rs. <?= number_format($expense['total_lkr_payable'] ?? 0, 0) ?></td>
                                                                <td>
                                                                    <?php if (esc($expense['tour_cancel']) == 1) {
                                                                        echo '<button class="btn btn-danger btn-sm" disabled>Cancelled</button>';
                                                                    } else { ?>
                                                                        <button class="btn btn-success btn-sm" onclick="window.open('<?= base_url('tour/tour_accounts/approved_expenses_report/' . esc($expense['manual_id'])) ?>', '_blank')">
                                                                            <i class="bi bi-eye"></i> View
                                                                        </button>
                                                                    <?php } ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else : ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center text-muted">No approved expenses found.</td>
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

                    <!-- Utility Bills Table -->
                    <div class="container-fluid py-4">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card pd-section">
                                    <div class="card-header pd-section-header pd-header-utility">
                                        <h5><i class="bi bi-lightning-charge-fill"></i> Utility Bill Payments <span class="fw-normal opacity-75">(Pending)</span></h5>
                                        <?php
                                        $utilityBillTotal = array_sum(array_column($utilityBills ?? [], 'amount'));
                                        ?>
                                        <span class="badge bg-white text-warning">
                                            Total: Rs. <?= number_format($utilityBillTotal, 2) ?>
                                        </span>
                                    </div>

                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover table-custom align-middle" id="utilityBillTable">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Type</th>
                                                        <th>Date</th>
                                                        <th>Invoice No</th>
                                                        <th>Description</th>
                                                        <th>Liability Account</th>
                                                        <th>Expenses Account</th>
                                                        <th>Amount</th>
                                                        <th>Req. User</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($utilityBills)) : ?>
                                                        <?php $k = 1; ?>
                                                        <?php foreach ($utilityBills as $bill) : ?>
                                                            <?php
                                                                $typeLabels = [
                                                                    'utility_bill'    => '<span class="badge bg-primary">Utility Bill</span>',
                                                                    'special_payment' => '<span class="badge bg-warning text-dark">Special Payment</span>',
                                                                    'petty_cash'      => '<span class="badge bg-secondary">Petty Cash</span>',
                                                                ];
                                                                $typeLabel = $typeLabels[$bill['payment_type']] ?? '<span class="badge bg-light text-dark">' . esc($bill['payment_type']) . '</span>';
                                                            ?>
                                                            <tr>
                                                                <td><?= $k++; ?></td>
                                                                <td><?= $typeLabel ?></td>
                                                                <td><?= esc($bill['date']); ?></td>
                                                                <td><?= esc($bill['inv_no']); ?></td>
                                                                <td><?= esc($bill['description']); ?></td>
                                                                <td><?= esc($bill['acc_name_liability']); ?></td>
                                                                <td><?= esc($bill['acc_name_expenses']); ?></td>
                                                                <td class="text-danger fw-bold">Rs. <?= number_format($bill['amount'], 2) ?></td>
                                                                <td><?= esc($bill['cashier']); ?></td>
                                                                <td>
                                                                    <button class="btn btn-success btn-sm btn-utility-accept" data-id="<?= esc($bill['id']); ?>" data-payment-type="<?= esc($bill['payment_type']); ?>">
                                                                        <i class="bi bi-check-lg"></i> Accept
                                                                    </button>
                                                                    <button class="btn btn-danger btn-sm btn-utility-reject" data-id="<?= esc($bill['id']); ?>">
                                                                        <i class="bi bi-x-lg"></i> Reject
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else : ?>
                                                        <tr>
                                                            <td colspan="10" class="text-center text-muted">No pending utility bill payments found.</td>
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

    <div class="modal fade" id="AcceptUtilityBillBankModal" tabindex="-1" aria-labelledby="AcceptUtilityBillBankModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="AcceptUtilityBillBankModalLabel">Accept Payment</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="javascript:void(0);" id="accept_utility_bill_bank_form">
                        <input type="hidden" id="aub_id">
                        <div class="mb-3">
                            <label class="form-label">Bank Account<font color="#FF0000"><strong>*</strong></font></label>
                            <select id="aub_bank_acc" class="form-control" style="width: 100%;" required>
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
                            <input type="date" id="aub_date" class="form-control" placeholder="<?= date('Y-m-d') ?>">
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

                    $.post("<?php echo base_url() ?>tour/tour_accounts/payment_request_final", {
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
                    }, "json").always(() => {
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

        //tour_dash button click
        $(document).on('click', '.view', function(e) {
            e.preventDefault();
            var year = $(this).data('year');
            var month = $(this).data('month');
            var tour = $(this).data('tour_id');

            // Open the URL in a new tab using anchor tag method (bypasses popup blockers)
            var url = '<?php echo base_url(); ?>tour/tour_dashboard/monthly_dashboard?year=' + year + '&month=' + month + '&tour_id=' + tour;
            var a = document.createElement('a');
            a.href = url;
            a.target = '_blank';
            a.click();
        });

        // Initialize DataTable for approved expenses
        try {
            $('#approvedExpensesTable').DataTable({
                order: [[3, 'asc']],
                pageLength: 25
            });
        } catch(e) { console.warn('approvedExpensesTable DataTable:', e); }

        // Initialize DataTable for utility bills
        try {
            $('#utilityBillTable').DataTable({
                order: [[1, 'asc']],
                pageLength: 25
            });
        } catch(e) { console.warn('utilityBillTable DataTable:', e); }


        // Utility bill accept
        function submitUtilityAccept(id, bank, bankName, payDate) {
            Swal.fire({
                title: 'Accept this payment?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#78b848',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Accept'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('<?= base_url('accounts/utility_bill/update_status') ?>', {
                        id: id,
                        status: 1,
                        bank: bank || '',
                        bank_name: bankName || '',
                        payment_date: payDate || '<?= date('Y-m-d') ?>'
                    }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({ icon: 'success', title: 'Accepted!', timer: 1500, showConfirmButton: false })
                                .then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message, timer: 2000, showConfirmButton: false });
                        }
                    }, 'json').fail(function(xhr) {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Permission denied or server error. (' + xhr.status + ')' });
                    });
                }
            });
        }

        $(document).on('click', '.btn-utility-accept', function() {
            const id = $(this).data('id');
            const paymentType = $(this).data('payment-type');

            if (paymentType === 'petty_cash') {
                submitUtilityAccept(id);
                return;
            }

            $("#aub_id").val(id);
            $("#aub_bank_acc").val('');
            $("#AcceptUtilityBillBankModal").modal('show');
        });

        // Accept Utility Bill Bank modal submit
        $("#accept_utility_bill_bank_form").on('submit', function(e) {
            e.preventDefault();

            const id = $("#aub_id").val();
            const bank = $("#aub_bank_acc").val();
            const bankName = $("#aub_bank_acc option:selected").text();
            const payDate = $("#aub_date").val() || new Date().toISOString().split('T')[0];

            if (!bank) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a bank account' });
                return;
            }

            $("#AcceptUtilityBillBankModal").modal('hide');
            submitUtilityAccept(id, bank, bankName, payDate);
        });

        // Utility bill reject
        $(document).on('click', '.btn-utility-reject', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Reject this payment?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Reject'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('<?= base_url('accounts/utility_bill/update_status') ?>', {
                        id: id,
                        status: 2
                    }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({ icon: 'success', title: 'Rejected!', timer: 1500, showConfirmButton: false })
                                .then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message, timer: 2000, showConfirmButton: false });
                        }
                    }, 'json');
                }
            });
        });
    </script>

</body>

</html>