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
        background: linear-gradient(135deg, #78b848 0%, #78b848 100%);
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

    .driver-summary-table th,
    .driver-summary-table td {
        padding: 12px;
        vertical-align: middle;
    }

    .payable-positive {
        color: #78b848;
        font-weight: bold;
    }

    .payable-negative {
        color: #dc3545;
        font-weight: bold;
    }

    .posted-badge {
        background-color: #78b848;
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
    }
</style>

<body class="bg-light">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>

        <!-- Start right Content here -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">Posted Expenses Report</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="<?= base_url('tour/tour_accounts/payment_requested_dashboard') ?>">Payment Dashboard</a></li>
                                        <li class="breadcrumb-item active">Posted Expenses</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tour Info Header -->
                    <?php if (!empty($tourInfo)): ?>
                        <div class="tour-info">
                            <h5><i class="bi bi-check-circle-fill"></i> <?= esc($tourInfo['manual_id']) ?> - <?= esc($tourInfo['consignee']) ?> <span class="posted-badge ms-2">POSTED</span></h5>
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

                    <!-- Driver Summary Table -->
                    <?php if (!empty($driverSummary)): ?>
                        <div class="card shadow-lg border-0 rounded mb-4">
                            <div class="card-header bg-gradient-success text-white">
                                <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> Driver Payment Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered driver-summary-table">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Driver</th>
                                                <th class="text-end">Log Sheet Amount</th>
                                                <th class="text-end">Advance Paid</th>
                                                <th class="text-end">Expenses Posted</th>
                                                <th class="text-end">Payable Amount</th>
                                                <th class="text-center">Pay</th>
                                                <th class="text-center">Payment Finalize</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($driverSummary as $driver): ?>
                                                <tr>
                                                    <td>
                                                        <i class="bi bi-person-fill text-success"></i> <?= esc($driver['driver_name']) ?>
                                                    </td>
                                                    <td class="text-end">Rs. <?= number_format($driver['log_sheet_amount'], 2) ?></td>
                                                    <td class="text-end">Rs. <?= number_format($driver['advance_amount'], 2) ?></td>
                                                    <td class="text-end">Rs. <?= number_format($driver['expenses_amount'], 2) ?></td>
                                                    <td class="text-end <?= $driver['payable_amount'] >= 0 ? 'payable-positive' : 'payable-negative' ?>">
                                                        Rs. <?= number_format($driver['payable_amount'], 2) ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="<?= base_url('dashboard2/transport/driver_settlement?tour_id=' . ($tourInfo['tour_id'] ?? '') . '&driver=' . $driver['driver_code']) ?>" class="btn btn-success btn-sm">
                                                            <i class="bi bi-cash-stack"></i> Pay
                                                        </a>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if (($user_group == 1 || $user_group == 9) && $driver['has_paid_voucher']): ?>
                                                        <button class="btn btn-primary btn-sm btn-finalize"
                                                            data-tour="<?= esc($tourInfo['tour_id'] ?? '') ?>"
                                                            data-driver="<?= esc($driver['driver_code']) ?>">
                                                            <i class="bi bi-check2-circle"></i> Finalize
                                                        </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr>
                                                <th>Total</th>
                                                <th class="text-end">Rs. <?= number_format($totalLogSheetAmount, 2) ?></th>
                                                <th class="text-end">Rs. <?= number_format($totalAdvanceAmount, 2) ?></th>
                                                <th class="text-end">Rs. <?= number_format($totalExpensesAmount, 2) ?></th>
                                                <th class="text-end <?= $totalPayableAmount >= 0 ? 'payable-positive' : 'payable-negative' ?>">
                                                    Rs. <?= number_format($totalPayableAmount, 2) ?>
                                                </th>
                                                <th></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> No posted expenses found in driver_expenses table for this tour.
                            <br><small>Tour ID: <?= esc($tourInfo['tour_id'] ?? '-') ?></small>
                        </div>
                    <?php endif; ?>

                    <!-- Back Button -->
                    <div class="mt-3">
                        <a href="<?= base_url('tour/tour_accounts/payment_requested_dashboard') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>

                </div>
            </div>
            <?= view('layouts/inpage_footer') ?>
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $(document).on('click', '.btn-finalize', function () {
            var btn     = $(this);
            var tour_id = btn.data('tour');
            var driver  = btn.data('driver');

            if (!confirm('Are you sure you want to finalize the payment for this driver?')) return;

            btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');

            $.ajax({
                url: '<?= base_url('tour/tour_accounts/finalize_driver_expenses') ?>',
                type: 'POST',
                data: { '<?= csrf_token() ?>': '<?= csrf_hash() ?>', tour_id: tour_id, driver: driver },   // tf-paydesk-r7f: the page's security token
                dataType: 'json',
                success: function (res) {
                    if (res.status === 200) {
                        btn.removeClass('btn-primary').addClass('btn-success')
                           .html('<i class="bi bi-check-circle-fill"></i> Finalized').prop('disabled', true);
                    } else {
                        alert(res.message);
                        btn.prop('disabled', false).html('<i class="bi bi-check2-circle"></i> Finalize');
                    }
                },
                error: function (x) {   // tf-paydesk-r7f: the server's reason
                    alert((x && x.responseJSON && x.responseJSON.message) || 'An error occurred. Please try again.');
                    btn.prop('disabled', false).html('<i class="bi bi-check2-circle"></i> Finalize');
                }
            });
        });
    </script>

</body>

</html>