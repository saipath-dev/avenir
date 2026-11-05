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

    .expense-details {
        font-size: 0.85rem;
        color: #555;
    }

    .btn-action {
        padding: 0.35rem 0.75rem;
        font-size: 0.875rem;
    }

    .driver-section {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .driver-section:last-child {
        margin-bottom: 0;
    }

    .driver-header {
        background-color: #e9ecef;
        padding: 8px 12px;
        border-bottom: 1px solid #dee2e6;
    }

    .driver-name {
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
    }

    .driver-amounts {
        display: flex;
        gap: 15px;
        margin-top: 4px;
    }

    .driver-amount-item {
        font-size: 0.8rem;
    }

    .driver-amount-label {
        color: #6c757d;
    }

    .driver-amount-value {
        font-weight: 600;
    }

    .advance-value {
        color: #0d6efd;
    }

    .logsheet-value {
        color: #78b848;
    }

    .driver-expenses {
        padding: 8px 12px;
        background-color: #fff;
    }

    .driver-expenses .expense-item {
        font-size: 0.8rem;
        color: #555;
        padding: 3px 0;
        border-bottom: 1px dashed #eee;
    }

    .driver-expenses .expense-item:last-child {
        border-bottom: none;
    }

    .driver-totals {
        background-color: #f8f9fa;
        padding: 6px 12px;
        font-size: 0.8rem;
        font-weight: 600;
        display: flex;
        justify-content: flex-end;
        gap: 15px;
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
                            <h4 class="text-primary font-weight-bold">
                                <i class="bi bi-hourglass-split"></i> Pending Expenses Dashboard
                            </h4>
                        </div>
                        <div class="col-md-3 text-end">
                            <button class="btn btn-outline-secondary btn-sm" onclick="window.location.href='<?= base_url('tour/tour_accounts') ?>'">
                                <i class="bi bi-arrow-left"></i> Back to Forecast
                            </button>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?= $total_tours ?? 0 ?></h3>
                                    <small>Tours with Pending Expenses</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?= $total_expenses ?? 0 ?></h3>
                                    <small>Total Pending Expenses</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0">$ <?= number_format($total_dollar_amount ?? 0, 2) ?></h3>
                                    <small>Total Dollar Amount</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0">Rs. <?= number_format($total_lkr_amount ?? 0, 2) ?></h3>
                                    <small>Total LKR Amount</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Expenses Table -->
                    <div class="container-fluid py-4">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card shadow-lg border-0 rounded">
                                    <div class="card-header bg-gradient-warning text-dark d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><i class="bi bi-list-check"></i> Pending Expenses (Awaiting Approval)</h5>
                                    </div>

                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover align-middle" id="pendingExpensesTable">
                                                <thead class="bg-light text-primary">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Manual ID</th>
                                                        <th>Tour Name</th>
                                                        <th>Tour Dates</th>
                                                        <th class="text-center">Count</th>
                                                        <th colspan="2">Driver Details & Expenses</th>
                                                        <th class="text-end">Total $</th>
                                                        <th class="text-end">Total LKR</th>
                                                        <th class="text-center" style="width: 140px;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($grouped_expenses)): ?>
                                                        <?php $rowNum = 1; ?>
                                                        <?php foreach ($grouped_expenses as $tour): ?>
                                                            <?php
                                                                // Calculate total expense count for this tour
                                                                $totalExpenseCount = 0;
                                                                foreach ($tour['drivers'] as $driver) {
                                                                    $totalExpenseCount += count($driver['expenses']);
                                                                }
                                                            ?>
                                                            <tr data-tour-id="<?= $tour['tour_id'] ?>">
                                                                <td><?= $rowNum++ ?></td>
                                                                <td><strong class="text-danger"><?= $tour['manual_id'] ?></strong></td>
                                                                <td><?= $tour['consignee'] ?></td>
                                                                <td>
                                                                    <?= !empty($tour['start_date']) ? date('d M', strtotime($tour['start_date'])) : '' ?>
                                                                    <?= (!empty($tour['start_date']) && !empty($tour['end_date'])) ? ' - ' : '' ?>
                                                                    <?= !empty($tour['end_date']) ? date('d M, Y', strtotime($tour['end_date'])) : '-' ?>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-warning text-dark"><?= $totalExpenseCount ?></span>
                                                                </td>
                                                                <td colspan="2">
                                                                    <?php if (!empty($tour['drivers'])): ?>
                                                                        <?php foreach ($tour['drivers'] as $driver): ?>
                                                                            <div class="driver-section <?= !($driver['has_log_sheet'] ?? false) ? 'border-danger' : '' ?>">
                                                                                <div class="driver-header <?= !($driver['has_log_sheet'] ?? false) ? 'bg-danger bg-opacity-25' : '' ?>">
                                                                                    <div class="driver-name">
                                                                                        <i class="bi bi-person-fill"></i> <?= $driver['driver_name'] ?>
                                                                                        <?php if (!($driver['has_log_sheet'] ?? false)): ?>
                                                                                            <span class="badge bg-danger ms-1" style="font-size: 0.7rem;"><i class="bi bi-exclamation-triangle"></i> No Log Sheet</span>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                    <div class="driver-amounts">
                                                                                        <span class="driver-amount-item">
                                                                                            <span class="driver-amount-label">Advance:</span>
                                                                                            <span class="driver-amount-value advance-value">Rs. <?= number_format($driver['advance_amount'], 2) ?></span>
                                                                                        </span>
                                                                                        <span class="driver-amount-item">
                                                                                            <span class="driver-amount-label">Log Sheet:</span>
                                                                                            <span class="driver-amount-value <?= !($driver['has_log_sheet'] ?? false) ? 'text-danger' : 'logsheet-value' ?>">
                                                                                                Rs. <?= number_format($driver['log_sheet_amount'], 2) ?>
                                                                                                <?php if (!($driver['has_log_sheet'] ?? false)): ?>
                                                                                                    <i class="bi bi-exclamation-circle"></i>
                                                                                                <?php endif; ?>
                                                                                            </span>
                                                                                        </span>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="driver-expenses">
                                                                                    <?php foreach ($driver['expenses'] as $index => $expense): ?>
                                                                                        <div class="expense-item">
                                                                                            <?= ($index + 1) ?>. <?= $expense['details'] ?>
                                                                                        </div>
                                                                                    <?php endforeach; ?>
                                                                                </div>
                                                                                <div class="driver-totals">
                                                                                    <span>$ <?= number_format($driver['total_dollar'], 2) ?></span>
                                                                                    <span>Rs. <?= number_format($driver['total_lkr'], 2) ?></span>
                                                                                </div>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted"><i class="bi bi-dash"></i> No drivers</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="text-end"><strong>$ <?= number_format($tour['total_dollar'], 2) ?></strong></td>
                                                                <td class="text-end"><strong>Rs. <?= number_format($tour['total_lkr'], 2) ?></strong></td>
                                                                <td class="text-center" style="position: relative; z-index: 100;">
                                                                    <button type="button" class="btn btn-success btn-action" title="Approve All" onclick="approveTourExpenses('<?= $tour['tour_id'] ?>', '<?= $tour['manual_id'] ?>', <?= $totalExpenseCount ?>)">
                                                                        <i class="bi bi-check-lg"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-danger btn-action" title="Reject All" onclick="rejectTourExpenses('<?= $tour['tour_id'] ?>', '<?= $tour['manual_id'] ?>', <?= $totalExpenseCount ?>)">
                                                                        <i class="bi bi-x-lg"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="10" class="text-center text-muted py-4">
                                                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                                                <p class="mb-0 mt-2">No pending expenses found</p>
                                                            </td>
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

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#pendingExpensesTable').DataTable({
                order: [[3, 'asc']], // Sort by tour dates
                pageLength: 25
            });
        });

        // Approve All Expenses for Tour
        function approveTourExpenses(tourId, manualId, count) {
            Swal.fire({
                title: 'Approve All Expenses?',
                html: 'This will approve <strong>' + count + '</strong> expense(s) for tour <strong>' + manualId + '</strong> and post them to accounts.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#78b848',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve All!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "<?= base_url() ?>dashboard2/transport/driver_expense/approve_tour_expenses",
                        type: 'POST',
                        data: { tour_id: tourId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Approved!',
                                    text: response.message
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Something went wrong!'
                            });
                        }
                    });
                }
            });
        }

        // Reject All Expenses for Tour
        function rejectTourExpenses(tourId, manualId, count) {
            Swal.fire({
                title: 'Reject All Expenses?',
                html: 'This will reject <strong>' + count + '</strong> expense(s) for tour <strong>' + manualId + '</strong>.',
                input: 'textarea',
                inputLabel: 'Rejection Reason',
                inputPlaceholder: 'Enter reason for rejection...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Reject All',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Please provide a reason for rejection';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "<?= base_url() ?>dashboard2/transport/driver_expense/reject_tour_expenses",
                        type: 'POST',
                        data: {
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
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Something went wrong!'
                            });
                        }
                    });
                }
            });
        }
    </script>

</body>

</html>
