<?= view('layouts/head') ?>
<style type="text/css">
    body {
        background-color: #f8f9fa;
    }

    .card-header {
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

    .tour-info {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .tour-info h5 {
        margin-bottom: 10px;
    }

    .tour-info-item {
        display: inline-block;
        margin-right: 30px;
    }

    .tour-info-label {
        font-size: 0.85rem;
        opacity: 0.8;
    }

    .tour-info-value {
        font-weight: 600;
        font-size: 1rem;
    }

    .summary-card {
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        color: white;
    }

    .summary-card h6 {
        font-size: 0.85rem;
        opacity: 0.9;
        margin-bottom: 5px;
    }

    .summary-card h4 {
        font-weight: 700;
        margin-bottom: 0;
    }

    .driver-summary-table th, .driver-summary-table td {
        padding: 12px;
        vertical-align: middle;
    }

    .payable-positive {
        color: #78b848 !important;
        font-weight: bold;
    }

    .payable-negative {
        color: #dc3545 !important;
        font-weight: bold;
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
                            <h4 class="text-primary font-weight-bold mb-0">
                                <i class="bi bi-check-circle-fill text-success"></i> Approved Expenses Report
                            </h4>
                            <?php if (!empty($filterDriverCode) && !empty($driverSummary)): ?>
                                <span class="badge bg-primary mt-1">
                                    <i class="bi bi-person-fill me-1"></i> Filtered to: <?= esc(reset($driverSummary)['driver_name'] ?? $filterDriverCode) ?>
                                </span>
                            <?php endif; ?>
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
                            <button class="btn btn-outline-secondary btn-sm" onclick="window.location.href='<?= base_url('tour/tour_accounts/approved_expenses_dashboard') ?>'">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </button>
                        </div>
                    </div>

                    <!-- Tour Info -->
                    <?php if (!empty($tourInfo)): ?>
                        <div class="tour-info">
                            <h5><i class="bi bi-info-circle"></i> Tour Information</h5>
                            <div class="tour-info-item">
                                <div class="tour-info-label">Manual ID</div>
                                <div class="tour-info-value"><?= esc($tourInfo['manual_id']) ?></div>
                            </div>
                            <div class="tour-info-item">
                                <div class="tour-info-label">Tour Name</div>
                                <div class="tour-info-value"><?= esc($tourInfo['consignee']) ?></div>
                            </div>
                            <div class="tour-info-item">
                                <div class="tour-info-label">Start Date</div>
                                <div class="tour-info-value"><?= !empty($tourInfo['start_date']) ? date('d M, Y', strtotime($tourInfo['start_date'])) : '-' ?></div>
                            </div>
                            <div class="tour-info-item">
                                <div class="tour-info-label">End Date</div>
                                <div class="tour-info-value"><?= !empty($tourInfo['end_date']) ? date('d M, Y', strtotime($tourInfo['end_date'])) : '-' ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="summary-card bg-info">
                                <h6><i class="bi bi-cash-stack"></i> Total Advance Paid</h6>
                                <h4>Rs. <?= number_format($totalAdvanceAmount ?? 0, 2) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-card bg-warning">
                                <h6><i class="bi bi-receipt"></i> Total Expenses</h6>
                                <h4>Rs. <?= number_format($totalExpensesAmount ?? 0, 2) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-card <?= ($totalPayableAmount ?? 0) >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                <h6><i class="bi bi-wallet2"></i> Total Payable Amount</h6>
                                <h4>Rs. <?= number_format($totalPayableAmount ?? 0, 2) ?></h4>
                            </div>
                        </div>
                    </div>

                    <!-- Mileage / Transport Cost Estimate vs Actual -->
                    <div class="card shadow-lg border-0 rounded mb-4">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Mileage &amp; Transport Cost — Estimated vs Actual</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered driver-summary-table mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th></th>
                                            <th class="text-end">Estimated (Quotation)</th>
                                            <th class="text-end">Driver Meter Reading</th>
                                            <th class="text-end">Amended Mileage</th>
                                            <th class="text-end">Variance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><i class="bi bi-signpost-split text-primary"></i> Mileage (km)</td>
                                            <td class="text-end">
                                                <?= number_format($totalEstimatedMileageWithExtra ?? 0, 2) ?>
                                                <div class="text-muted" style="font-size:0.75rem;">
                                                    w/o extra: <?= number_format($totalEstimatedMileageNoExtra ?? 0, 2) ?>

                                                </div>
                                            </td>
                                            <td class="text-end"><?= number_format($totalLogSheetMileage ?? 0, 2) ?></td>
                                            <td class="text-end"><?= number_format($totalAmendedMileage ?? 0, 2) ?></td>
                                            <td class="text-end <?= ($mileageVariance ?? 0) <= 0 ? 'payable-positive' : 'payable-negative' ?>">
                                                <?= number_format($mileageVariance ?? 0, 2) ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><i class="bi bi-cash-coin text-primary"></i> Transport Cost (Rs.)</td>
                                            <td class="text-end"><?= number_format($totalEstimatedTransportCost ?? 0, 2) ?></td>
                                            <td class="text-end"><?= number_format($totalDriverEnteredTransportCost ?? 0, 2) ?></td>
                                            <td class="text-end"><?= number_format($totalLogSheetAmount ?? 0, 2) ?></td>
                                            <td class="text-end <?= ($transportCostVariance ?? 0) <= 0 ? 'payable-positive' : 'payable-negative' ?>">
                                                <?= number_format($transportCostVariance ?? 0, 2) ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Driver Summary Table -->
                    <?php if (!empty($driverSummary)): ?>
                    <div class="card shadow-lg border-0 rounded mb-4">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> Driver Payment Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered driver-summary-table">
                                    <thead class="bg-light">
                                        <tr>
                                            <th rowspan="2" class="align-middle">Driver</th>
                                            <th colspan="2" class="text-center">Bata</th>
                                            <th colspan="2" class="text-center">Mileage</th>
                                            <th rowspan="2" class="text-end align-middle">Log Sheet Amount</th>
                                            <th rowspan="2" class="text-end align-middle">Expenses</th>
                                            <th rowspan="2" class="text-end align-middle">Advance Paid</th>
                                            <th rowspan="2" class="text-end align-middle">Payable Amount</th>
                                        </tr>
                                        <tr>
                                            <th class="text-end">Rate/Day</th>
                                            <th class="text-end">Days</th>
                                            <th class="text-end">Vehicle Rate</th>
                                            <th class="text-end">Amended Mileage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($driverSummary as $driver): ?>
                                        <tr class="<?= !($driver['has_log_sheet'] ?? false) ? 'table-danger' : '' ?>">
                                            <td>
                                                <i class="bi bi-person-fill text-primary"></i> <?= esc($driver['driver_name']) ?>
                                                <?php if (!($driver['has_log_sheet'] ?? false)): ?>
                                                    <span class="badge bg-danger ms-2"><i class="bi bi-exclamation-triangle"></i> No Log Sheet</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">Rs. <?= number_format($driver['bata_rate'] ?? 0, 2) ?></td>
                                            <td class="text-end"><?= number_format($driver['bata_days'] ?? 0) ?></td>
                                            <td class="text-end">Rs. <?= number_format($driver['vehicle_rate'] ?? 0, 2) ?></td>
                                            <td class="text-end"><?= number_format($driver['amended_mileage'] ?? 0, 2) ?> km</td>
                                            <td class="text-end <?= !($driver['has_log_sheet'] ?? false) ? 'text-danger' : '' ?>">
                                                <?php if ($driver['already_settled'] ?? false): ?>
                                                    <span class="text-muted">Rs. 0.00</span>
                                                    <span class="badge bg-secondary ms-1" title="Mileage/bata already paid via a prior voucher — excluded to avoid double payment">
                                                        <i class="bi bi-check2-circle"></i> Settled
                                                    </span>
                                                    <div class="text-muted" style="font-size:0.72rem;">
                                                        Already paid Rs. <?= number_format($driver['settled_amount'] ?? 0, 2) ?><?= !empty($driver['settled_date']) ? ' on ' . esc(date('Y-m-d', strtotime($driver['settled_date']))) : '' ?>
                                                    </div>
                                                <?php else: ?>
                                                    Rs. <?= number_format($driver['log_sheet_amount'], 2) ?>
                                                    <?php if (!($driver['has_log_sheet'] ?? false)): ?>
                                                        <i class="bi bi-exclamation-circle text-danger"></i>
                                                    <?php endif; ?>
                                                    <div class="text-muted" style="font-size:0.72rem;">
                                                        (<?= number_format($driver['bata_rate'] ?? 0, 0) ?>&times;<?= number_format($driver['bata_days'] ?? 0) ?>) + (<?= number_format($driver['vehicle_rate'] ?? 0, 0) ?>&times;<?= number_format($driver['amended_mileage'] ?? 0, 1) ?>)
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">Rs. <?= number_format($driver['expenses_amount'], 2) ?></td>
                                            <td class="text-end">Rs. <?= number_format($driver['advance_amount'], 2) ?></td>
                                            <td class="text-end <?= $driver['payable_amount'] >= 0 ? 'payable-positive' : 'payable-negative' ?>">
                                                Rs. <?= number_format($driver['payable_amount'], 2) ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="bg-light">
                                        <tr>
                                            <th colspan="4">Total</th>
                                            <th class="text-end"><?= number_format($totalAmendedMileage ?? 0, 2) ?> km</th>
                                            <th class="text-end">Rs. <?= number_format($totalLogSheetAmount, 2) ?></th>
                                            <th class="text-end">Rs. <?= number_format($totalExpensesAmount, 2) ?></th>
                                            <th class="text-end">Rs. <?= number_format($totalAdvanceAmount, 2) ?></th>
                                            <th class="text-end <?= $totalPayableAmount >= 0 ? 'payable-positive' : 'payable-negative' ?>">
                                                Rs. <?= number_format($totalPayableAmount, 2) ?>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Warning for missing log sheet -->
                            <?php if (!($hasLogSheetMileage ?? false)): ?>
                            <div class="alert alert-danger d-flex align-items-center mt-3" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1.5rem;"></i>
                                <div>
                                    <strong>Cannot Accept:</strong> Log sheet mileage is required for all drivers before accepting expenses.
                                    Please add log sheet mileage in the Transport Details page first.
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Accept/Reject Buttons -->
                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button type="button" class="btn btn-danger btn-lg" onclick="rejectExpenses('<?= $tourInfo['tour_id'] ?>', '<?= $tourInfo['manual_id'] ?>')">
                                    <i class="bi bi-x-circle"></i> Reject
                                </button>
                                <?php if ($hasLogSheetMileage ?? false): ?>
                                <button type="button" class="btn btn-success btn-lg" onclick="acceptExpenses('<?= $tourInfo['tour_id'] ?>', '<?= $tourInfo['manual_id'] ?>', <?= $totalPayableAmount ?>)">
                                    <i class="bi bi-check-circle"></i> Accept & Post to Accounts
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-secondary btn-lg" disabled title="Log sheet mileage is required">
                                    <i class="bi bi-check-circle"></i> Accept & Post to Accounts
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Table -->
                    <div class="container-fluid py-4">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card shadow-lg border-0 rounded">
                                    <div class="card-header bg-gradient-success text-white d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><i class="bi bi-list-check"></i> Approved Expenses</h5>
                                    </div>

                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover align-middle" id="approvedExpensesTable">
                                                <thead class="bg-light text-primary">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Date</th>
                                                        <th>Agent</th>
                                                        <th>Supplier</th>
                                                        <th>Invoice No</th>
                                                        <th>Description</th>
                                                        <!-- <th class="text-end">$ Amount</th> -->
                                                        <th class="text-end">LKR Amount</th>
                                                        <th>Approved By</th>
                                                        <th>Approved At</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($approvedExpenses)): ?>
                                                        <?php
                                                        $i = 1;
                                                        $totalDollar = 0;
                                                        $totalLKR = 0;
                                                        ?>
                                                        <?php foreach ($approvedExpenses as $expense): ?>
                                                            <?php
                                                            $totalDollar += ($expense['dollar_amount'] ?? 0);
                                                            $totalLKR += ($expense['lkr_amount'] ?? 0);
                                                            ?>
                                                            <tr>
                                                                <td><?= $i++; ?></td>
                                                                <td><?= !empty($expense['expense_date']) ? date('d M, Y', strtotime($expense['expense_date'])) : '-'; ?></td>
                                                                <td><?= esc($expense['agent_name']); ?></td>
                                                                <td><?= esc($expense['supplier_name']); ?></td>
                                                                <td><?= esc($expense['invoice_no']); ?></td>
                                                                <td><?= esc($expense['description']); ?></td>
                                                                <!-- <td class="text-end">$ <?= number_format($expense['dollar_amount'] ?? 0, 2); ?></td> -->
                                                                <td class="text-end">Rs. <?= number_format($expense['lkr_amount'] ?? 0, 2); ?></td>
                                                                <td><?= esc($expense['approved_by']); ?></td>
                                                                <td><?= !empty($expense['approved_at']) ? date('d M, Y H:i', strtotime($expense['approved_at'])) : '-'; ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="10" class="text-center text-muted py-4">
                                                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                                                <p class="mb-0 mt-2">No approved expenses found</p>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                                <?php if (!empty($approvedExpenses)): ?>
                                                    <tfoot class="bg-light">
                                                        <tr>
                                                            <th colspan="6" class="text-end">Total:</th>
                                                            <!-- <th class="text-end">$ <?= number_format($totalDollar, 2); ?></th> -->
                                                            <th class="text-end">Rs. <?= number_format($totalLKR, 2); ?></th>
                                                            <th colspan="2"></th>
                                                        </tr>
                                                    </tfoot>
                                                <?php endif; ?>
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

    <div class="modal fade" id="AcceptExpensesBankModal" tabindex="-1" aria-labelledby="AcceptExpensesBankModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="AcceptExpensesBankModalLabel">Accept & Post to Accounts</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="javascript:void(0);" id="accept_expenses_bank_form">
                        <input type="hidden" id="aeb_tour_id">
                        <input type="hidden" id="aeb_manual_id">
                        <input type="hidden" id="aeb_payable_amount">
                        <div class="mb-3">
                            <label class="form-label">Payable Amount</label>
                            <input type="text" id="aeb_payable_amount_txt" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bank Account<font color="#FF0000"><strong>*</strong></font></label>
                            <select id="aeb_bank_acc" class="form-control" style="width: 100%;" required>
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
                            <input type="date" id="aeb_date" class="form-control" placeholder="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success" style="float: right;">
                                Accept & Post
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

    <script>
        $(document).ready(function() {
            $('#approvedExpensesTable').DataTable({
                order: [[1, 'asc']], // Sort by date
                pageLength: 25
            });
        });

        // Accept Expenses and Post to Accounts
        function acceptExpenses(tourId, manualId, payableAmount) {
            $("#aeb_tour_id").val(tourId);
            $("#aeb_manual_id").val(manualId);
            $("#aeb_payable_amount").val(payableAmount);
            $("#aeb_payable_amount_txt").val('Rs. ' + parseFloat(payableAmount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
            $("#aeb_bank_acc").val('');
            $("#AcceptExpensesBankModal").modal('show');
        }

        // Accept Expenses Bank modal submit
        $("#accept_expenses_bank_form").on('submit', function(e) {
            e.preventDefault();

            const tourId = $("#aeb_tour_id").val();
            const manualId = $("#aeb_manual_id").val();
            const payableAmount = $("#aeb_payable_amount").val();
            const bank = $("#aeb_bank_acc").val();
            const bankName = $("#aeb_bank_acc option:selected").text();
            const payDate = $("#aeb_date").val();

            if (!bank) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a bank account' });
                return;
            }
            if (!payDate) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a date' });
                return;
            }

            $("#AcceptExpensesBankModal").modal('hide');

            Swal.fire({
                title: 'Accept & Post to Accounts?',
                html: 'This will post the payable amount <strong>Rs. ' + parseFloat(payableAmount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') + '</strong> to accounts for tour <strong>' + manualId + '</strong>.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#78b848',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Accept & Post!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "<?= base_url() ?>tour/tour_accounts/accept_approved_expenses",
                        type: 'POST',
                        data: { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                            tour_id: tourId,
                            bank_acc: bank,
                            bank_name: bankName,
                            payable_amount: payableAmount,
                            payment_date: payDate
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: response.message
                            }).then(() => {
                                    if (window.opener) {
                                        window.opener.location.href = '<?= base_url('tour/tour_accounts/payment_requested_dashboard_new') ?>';
                                        window.close();
                                    } else {
                                        window.location.href = '<?= base_url('tour/tour_accounts/payment_requested_dashboard_new') ?>';
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function(x) {   // tf-paydesk-r7f: the server's reason
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong!'
                            });
                        }
                    });
                }
            });
        });

        // Reject Expenses
        function rejectExpenses(tourId, manualId) {
            Swal.fire({
                title: 'Reject Expenses?',
                html: 'This will reject all approved expenses for tour <strong>' + manualId + '</strong> and send them back for review.',
                input: 'textarea',
                inputLabel: 'Rejection Reason',
                inputPlaceholder: 'Enter reason for rejection...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Reject',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Please provide a reason for rejection';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "<?= base_url() ?>tour/tour_accounts/reject_approved_expenses",
                        type: 'POST',
                        data: { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                            tour_id: tourId,
                            reason: result.value
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Rejected!',
                                    text: response.message
                                }).then(() => {
                                    window.location.href = '<?= base_url('tour/tour_accounts/approved_expenses_dashboard') ?>';
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function(x) {   // tf-paydesk-r7f: the server's reason
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong!'
                            });
                        }
                    });
                }
            });
        }
    </script>

</body>

</html>
