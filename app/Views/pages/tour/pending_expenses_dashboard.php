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
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .driver-header-main {
        flex: 1;
        min-width: 0;
    }

    .driver-log-report-link {
        font-size: 0.75rem;
        white-space: nowrap;
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
        background-color: #fff;
    }

    .expense-detail-table {
        font-size: 0.8rem;
        color: #555;
    }

    .expense-detail-table thead th {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        color: #6c757d;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .expense-detail-table td,
    .expense-detail-table th {
        padding: 6px 12px;
        vertical-align: middle;
    }

    .driver-totals {
        background-color: #f8f9fa;
        padding: 6px 12px;
        font-size: 0.8rem;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .tour-summary-row {
        cursor: pointer;
    }

    .tour-summary-row .toggle-icon {
        transition: transform 0.15s ease;
    }

    .tour-detail-row td {
        background-color: #f8f9fa;
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
                        <div class="col-md-4">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?= $total_tours ?? 0 ?></h3>
                                    <small>Tours with Pending Expenses</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?= $total_expenses ?? 0 ?></h3>
                                    <small>Total Pending Expenses</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
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
                                                        <th style="width: 30px;"></th>
                                                        <th>#</th>
                                                        <th>Manual ID</th>
                                                        <th>Tour Name</th>
                                                        <th>Tour Dates</th>
                                                        <th>Driver Name</th>
                                                        <th class="text-center">Expenses</th>
                                                        <th class="text-center">Log Sheets</th>
                                                        <th class="text-end">Total Expenses</th>
                                                        <th class="text-center" style="width: 140px;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($grouped_expenses)): ?>
                                                        <?php $rowNum = 1; ?>
                                                        <?php foreach ($grouped_expenses as $tour): ?>
                                                            <?php
                                                                // Calculate total expense count and missing-log-sheet count for this tour
                                                                $totalExpenseCount = 0;
                                                                $missingLogSheetCount = 0;
                                                                foreach ($tour['drivers'] as $driver) {
                                                                    $totalExpenseCount += count($driver['expenses']);
                                                                    if (!($driver['has_log_sheet'] ?? false)) {
                                                                        $missingLogSheetCount++;
                                                                    }
                                                                }
                                                                $detailRowId = 'tourDetail' . $tour['tour_id'];
                                                            ?>
                                                            <tr data-tour-id="<?= $tour['tour_id'] ?>" class="tour-summary-row" data-bs-toggle="collapse" data-bs-target="#<?= $detailRowId ?>" role="button" aria-expanded="false">
                                                                <td class="text-center text-muted"><i class="bi bi-chevron-down toggle-icon"></i></td>
                                                                <td><?= $rowNum++ ?></td>
                                                                <td><strong class="text-danger"><?= $tour['manual_id'] ?></strong></td>
                                                                <td><?= $tour['consignee'] ?></td>
                                                                <td>
                                                                    <?= !empty($tour['start_date']) ? date('d M', strtotime($tour['start_date'])) : '' ?>
                                                                    <?= (!empty($tour['start_date']) && !empty($tour['end_date'])) ? ' - ' : '' ?>
                                                                    <?= !empty($tour['end_date']) ? date('d M, Y', strtotime($tour['end_date'])) : '-' ?>
                                                                    <?php if (!empty($tour['start_date']) && !empty($tour['end_date'])): ?>
                                                                        <?php $tourDayCount = (int) ((strtotime($tour['end_date']) - strtotime($tour['start_date'])) / 86400) + 1; ?>
                                                                        <small class="text-muted d-block"><?= $tourDayCount ?> day<?= $tourDayCount !== 1 ? 's' : '' ?></small>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php if (!empty($tour['drivers'])): ?>
                                                                        <?= esc(implode(', ', array_map(static fn($d) => $d['driver_name'], $tour['drivers']))) ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted"><i class="bi bi-dash"></i> No drivers</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-warning text-dark"><?= $totalExpenseCount ?></span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <?php if ($missingLogSheetCount > 0): ?>
                                                                        <span class="badge bg-danger" title="<?= $missingLogSheetCount ?> driver(s) missing a log sheet">
                                                                            <i class="bi bi-exclamation-triangle"></i> <?= $missingLogSheetCount ?> Missing
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i> OK</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="text-end"><strong>Rs. <?= number_format($tour['total_lkr'], 2) ?></strong></td>
                                                                <td class="text-center" style="position: relative; z-index: 100;">
                                                                    <button type="button" class="btn btn-success btn-action" title="Approve All" onclick="event.stopPropagation(); approveTourExpenses('<?= $tour['tour_id'] ?>', '<?= $tour['manual_id'] ?>', <?= $totalExpenseCount ?>)">
                                                                        <i class="bi bi-check-lg"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-danger btn-action" title="Reject All" onclick="event.stopPropagation(); rejectTourExpenses('<?= $tour['tour_id'] ?>', '<?= $tour['manual_id'] ?>', <?= $totalExpenseCount ?>)">
                                                                        <i class="bi bi-x-lg"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                            <tr class="tour-detail-row">
                                                                <td colspan="10" class="p-0 border-0">
                                                                    <div class="collapse" id="<?= $detailRowId ?>">
                                                                        <div class="p-3 bg-light">
                                                                            <?php if (!empty($tour['drivers'])): ?>
                                                                                <?php foreach ($tour['drivers'] as $driver): ?>
                                                                                    <div class="driver-section <?= !($driver['has_log_sheet'] ?? false) ? 'border-danger' : '' ?>">
                                                                                        <div class="driver-header <?= !($driver['has_log_sheet'] ?? false) ? 'bg-danger bg-opacity-25' : '' ?>">
                                                                                            <div class="driver-header-main">
                                                                                                <?php if (!($driver['has_log_sheet'] ?? false)): ?>
                                                                                                    <div class="driver-name">
                                                                                                        <span class="badge bg-danger" style="font-size: 0.7rem;"><i class="bi bi-exclamation-triangle"></i> No Log Sheet</span>
                                                                                                    </div>
                                                                                                <?php endif; ?>
                                                                                                <div class="driver-amounts">
                                                                                                    
                                                                                                    
                                                                                                     <?php if ($driver['has_log_sheet'] ?? false): ?>
                                                                                                        <span class="driver-amount-item">
                                                                                                            <span class="driver-amount-label">Driver LogSheet Mileage:</span>
                                                                                                            <span class="driver-amount-value logsheet-value"><?= number_format($driver['log_sheet_mileage'] ?? 0, 2) ?> km</span>
                                                                                                        </span>
                                                                                                    <?php endif; ?>
                                                                                                    <?php if ($driver['has_log_sheet'] ?? false): ?>
                                                                                                        <span class="driver-amount-item">
                                                                                                            <span class="driver-amount-label">Amended Payable Mileage:</span>
                                                                                                            <span class="driver-amount-value logsheet-value"><?= number_format($driver['log_sheet_mileage'] ?? 0, 2) ?> km</span>
                                                                                                        </span>
                                                                                                    <?php endif; ?>
                                                                                                   
                                                                                                    <button type="button" class="btn btn-outline-secondary btn-sm override-mileage-btn"
                                                                                                        title="Override mileage for this driver"
                                                                                                        data-tour-id="<?= esc($tour['tour_id'], 'attr') ?>"
                                                                                                        data-manual-id="<?= esc($tour['manual_id'], 'attr') ?>"
                                                                                                        data-agent="<?= esc($driver['driver_code'], 'attr') ?>"
                                                                                                        data-driver-name="<?= esc($driver['driver_name'], 'attr') ?>"
                                                                                                        data-mileage="<?= esc($driver['log_sheet_mileage'] ?? 0, 'attr') ?>"
                                                                                                        data-bata="<?= esc($driver['bata_day_rate'] ?? 0, 'attr') ?>"
                                                                                                        data-no-of-date="<?= esc($driver['bata_days'] ?? 0, 'attr') ?>"
                                                                                                        data-no-of-date-max="<?= esc($driver['bata_days_max'] ?? 0, 'attr') ?>"
                                                                                                        data-vehicle-rate="<?= esc($driver['vehicle_rate'] ?? 0, 'attr') ?>">
                                                                                                        <i class="bi bi-pencil-square"></i> Edit Mileage
                                                                                                    </button>
                                                                                                </div>
                                                                                            </div>
                                                                                            <a href="<?= base_url('dashboard2/transport/driver_report?tour_id=' . $tour['tour_id']) ?>" target="_blank" class="btn btn-outline-primary btn-sm driver-log-report-link" onclick="event.stopPropagation()">
                                                                                                <i class="bi bi-file-earmark-text"></i> Log Report
                                                                                            </a>
                                                                                        </div>
                                                                                        <div class="driver-expenses p-0">
                                                                                            <table class="table table-sm mb-0 expense-detail-table">
                                                                                                <thead>
                                                                                                    <tr>
                                                                                                        <th>#</th>
                                                                                                        <th>Date</th>
                                                                                                        <th>Invoice</th>
                                                                                                        <th>Description</th>
                                                                                                        <th class="text-end">LKR Amount</th>
                                                                                                        <th class="text-center">Actions</th>
                                                                                                    </tr>
                                                                                                </thead>
                                                                                                <tbody>
                                                                                                    <?php foreach ($driver['expenses'] as $index => $expense): ?>
                                                                                                        <tr>
                                                                                                            <td><?= $index + 1 ?></td>
                                                                                                            <td><?= !empty($expense['expense_date']) && $expense['expense_date'] !== '-' ? date('d M Y', strtotime($expense['expense_date'])) : '-' ?></td>
                                                                                                            <td><?= esc($expense['invoice_no'] ?? '-') ?></td>
                                                                                                            <td>
                                                                                                                <?= esc($expense['description'] ?? '-') ?>
                                                                                                                <?php if (!empty($expense['supplier_name']) && $expense['supplier_name'] !== '-'): ?>
                                                                                                                    <small class="text-muted d-block"><?= esc($expense['supplier_name']) ?></small>
                                                                                                                <?php endif; ?>
                                                                                                            </td>
                                                                                                            <td class="text-end">Rs. <?= number_format($expense['lkr_amount'] ?? 0, 2) ?></td>
                                                                                                            <td class="text-center text-nowrap">
                                                                                                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1 edit-expense-btn"
                                                                                                                    title="Edit expense"
                                                                                                                    data-expense-id="<?= esc($expense['id'], 'attr') ?>"
                                                                                                                    data-invoice-no="<?= esc($expense['invoice_no'] ?? '', 'attr') ?>"
                                                                                                                    data-description="<?= esc($expense['description'] ?? '', 'attr') ?>"
                                                                                                                    data-lkr-amount="<?= esc($expense['lkr_amount'] ?? 0, 'attr') ?>"
                                                                                                                    data-category="<?= esc($expense['category'] ?? '', 'attr') ?>"
                                                                                                                    data-supplier="<?= esc($expense['supplier'] ?? '', 'attr') ?>">
                                                                                                                    <i class="bi bi-pencil"></i>
                                                                                                                </button>
                                                                                                                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 delete-expense-btn"
                                                                                                                    title="Delete expense"
                                                                                                                    data-expense-id="<?= esc($expense['id'], 'attr') ?>">
                                                                                                                    <i class="bi bi-trash"></i>
                                                                                                                </button>
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    <?php endforeach; ?>
                                                                                                </tbody>
                                                                                            </table>
                                                                                        </div>
                                                                                        <div class="driver-totals">
                                                                                            <?php if ($driver['has_log_sheet'] ?? false): ?>
                                                                                                <div class="d-flex flex-wrap align-items-center gap-1 flex-grow-1" style="font-size:0.82rem;">
                                                                                                    <small class="text-muted me-1"><i class="bi bi-calculator"></i></small>
                                                                                                    <?php if ($driver['already_settled'] ?? false): ?>
                                                                                                        <span class="badge bg-secondary" title="Mileage/bata and prior advance already paid via a voucher on <?= !empty($driver['settled_date']) ? esc(date('Y-m-d', strtotime($driver['settled_date']))) : '' ?> &mdash; excluded here to avoid double payment">
                                                                                                            <i class="bi bi-check2-circle"></i> Settled (Rs. <?= number_format($driver['settled_amount'] ?? 0, 2) ?>)
                                                                                                        </span>
                                                                                                        <span class="text-muted">+</span>
                                                                                                    <?php else: ?>
                                                                                                        <span>Bata (<?= number_format($driver['bata_day_rate'], 0) ?>&times;<?= (int) $driver['bata_days'] ?>)</span>
                                                                                                        <span class="text-muted">+</span>
                                                                                                        <span>Mileage (<?= number_format($driver['vehicle_rate'], 0) ?>&times;<?= number_format($driver['log_sheet_mileage'] ?? 0, 1) ?>)</span>
                                                                                                        <span class="text-muted">=</span>
                                                                                                        <span class="fw-semibold">Rs. <?= number_format($driver['log_sheet_amount'], 2) ?></span>
                                                                                                        <span class="text-muted">+</span>
                                                                                                    <?php endif; ?>
                                                                                                    <span>Expenses <span class="fw-semibold">Rs. <?= number_format($driver['total_lkr'] ?? 0, 2) ?></span></span>
                                                                                                    <span class="text-muted">&minus;</span>
                                                                                                    <span>Advance <span class="fw-semibold">Rs. <?= number_format($driver['advance_amount'], 2) ?></span></span>
                                                                                                    <span class="text-muted">=</span>
                                                                                                    <span class="fw-bold <?= ($driver['payable_amount'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                                                                                        Payable: Rs. <?= number_format($driver['payable_amount'] ?? 0, 2) ?>
                                                                                                    </span>
                                                                                                </div>
                                                                                            <?php else: ?>
                                                                                                <span>Rs. <?= number_format($driver['total_lkr'], 2) ?></span>
                                                                                            <?php endif; ?>
                                                                                        </div>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            <?php else: ?>
                                                                                <span class="text-muted"><i class="bi bi-dash"></i> No drivers</span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
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

    <!-- Mileage Override Modal — reuses driver_logsheet/add_logsheet_mileage, the same office
         override flow as dashboard2/transport/driver_logsheet's "Add Logsheet Mileage" modal. -->
    <div class="modal fade" id="mileageOverrideModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Override Mileage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        <strong id="mileageOverrideTour"></strong><br>
                        <span class="text-muted" id="mileageOverrideDriver"></span>
                    </p>
                    <form id="mileageOverrideForm">
                        <input type="hidden" id="mo_tour_id" name="tour_id">
                        <input type="hidden" id="mo_agent" name="agent">
                        <input type="hidden" id="mo_no_max_day">
                        <div class="mb-3">
                            <label for="mo_mileage" class="form-label">Actual Mileage (km)<span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="mo_mileage" required>
                        </div>
                        <div class="mb-3">
                            <label for="mo_bata" class="form-label">Actual Bata (per day)</label>
                            <input type="number" step="0.01" class="form-control" id="mo_bata">
                        </div>
                        <div class="mb-3">
                            <label for="mo_no_of_date" class="form-label">No of Date</label>
                            <input type="number" class="form-control" id="mo_no_of_date"
                                onkeyup="if(this.value > Number($('#mo_no_max_day').val())) this.value = Number($('#mo_no_max_day').val());">
                        </div>
                        <div class="mb-3">
                            <label for="mo_vehicle_rate" class="form-label">Vehicle Rate</label>
                            <input type="number" step="0.01" class="form-control" id="mo_vehicle_rate">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="mileageOverrideSaveBtn">Save Override</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editExpenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editExpenseForm">
                        <input type="hidden" id="ee_expense_id">
                        <div class="mb-3">
                            <label for="ee_category" class="form-label">Expenses Type</label>
                            <select class="form-select" id="ee_category">
                                <option value="">Select a Category...</option>
                                <option value="6-9-23-10640">Entrance Fee</option>
                                <option value="6-9-23-10641">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="ee_supplier" class="form-label">Expenses Acc</label>
                            <select class="form-select" id="ee_supplier">
                                <option value="">Select a Category first...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="ee_invoice_no" class="form-label">Invoice No<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ee_invoice_no" required>
                        </div>
                        <div class="mb-3">
                            <label for="ee_description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="ee_description">
                        </div>
                        <div class="mb-3">
                            <label for="ee_lkr_amount" class="form-label">LKR Amount<span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="ee_lkr_amount" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="editExpenseSaveBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $(document).ready(function() {
            // Flip the chevron icon when a tour row is expanded/collapsed. Summary and detail
            // rows are rendered as plain sibling <tr>s (not a DataTable) so the collapse toggle
            // always stays paired with the row it belongs to, regardless of row count.
            $('.tour-summary-row').each(function() {
                var targetId = $(this).data('bs-target');
                var $icon = $(this).find('.toggle-icon');
                $(targetId).on('show.bs.collapse', function() {
                    $icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
                });
                $(targetId).on('hide.bs.collapse', function() {
                    $icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
                });
            });
        });

        // Open the mileage override modal, prefilled with this driver's current log sheet values.
        // Delegated handler (not inline onclick) so the JSON-in-HTML-attribute quoting problem
        // that broke the original version can't happen — values come from data-* attributes.
        $(document).on('click', '.override-mileage-btn', function(e) {
            e.stopPropagation();
            var $btn = $(this);

            $('#mo_tour_id').val($btn.data('tour-id'));
            $('#mo_agent').val($btn.data('agent'));
            $('#mo_no_max_day').val($btn.data('no-of-date-max'));
            $('#mo_mileage').val($btn.data('mileage') || '');
            $('#mo_bata').val($btn.data('bata') || '');
            $('#mo_no_of_date').val($btn.data('no-of-date') || '');
            $('#mo_vehicle_rate').val($btn.data('vehicle-rate') || '');
            $('#mileageOverrideTour').text($btn.data('manual-id'));
            $('#mileageOverrideDriver').text($btn.data('driver-name'));
            new bootstrap.Modal(document.getElementById('mileageOverrideModal')).show();
        });

        // Save mileage override — posts to the same driver_logsheet/add_logsheet_mileage endpoint
        // the admin's log sheet panel uses, so it stays the single source of truth for log_sheet_mile.
        $('#mileageOverrideSaveBtn').on('click', function() {
            var mileage = $('#mo_mileage').val();
            if (mileage === '' || isNaN(mileage)) {
                Swal.fire({ icon: 'warning', title: 'Actual Mileage is required and must be a number' });
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: "<?= base_url() ?>dashboard2/transport/driver_logsheet/add_logsheet_mileage",
                type: 'POST',
                data: {
                    tour_id: $('#mo_tour_id').val(),
                    agent: $('#mo_agent').val(),
                    totalactualmileage: mileage,
                    dara_rate: $('#mo_bata').val(),
                    noofdate: $('#mo_no_of_date').val(),
                    v_rate: $('#mo_vehicle_rate').val(),
                    v_rate_new: $('#mo_vehicle_rate').val()
                },
                dataType: 'json',
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.status == 200) {
                        bootstrap.Modal.getInstance(document.getElementById('mileageOverrideModal')).hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Mileage Updated',
                            text: response.message
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong!' });
                }
            });
        });

        // Load Expense Account options for the Edit modal's Supplier dropdown, same AJAX endpoint
        // the driver portal / transport_expenses.php use — keyed by category.
        function loadEditExpenseAccounts(category, selectedSupplier) {
            var $supplier = $('#ee_supplier');
            if (!category) {
                $supplier.html('<option value="">Select a Category first...</option>');
                return;
            }
            $.ajax({
                url: "<?= base_url() ?>dashboard2/transport/transport_portal/expense_accounts",
                type: 'GET',
                data: { category: category },
                dataType: 'json',
                success: function(response) {
                    var accounts = (response && response.data) ? response.data : [];
                    var options = '<option value="">Select a Supplier...</option>';
                    accounts.forEach(function(a) {
                        options += '<option value="' + a.L_Code + '">' + a.PName + '</option>';
                    });
                    $supplier.html(options);
                    if (selectedSupplier) {
                        $supplier.val(selectedSupplier);
                    }
                }
            });
        }

        $('#ee_category').on('change', function() {
            loadEditExpenseAccounts($(this).val(), null);
        });

        // Open Edit Expense modal, prefilled from the row's data-* attributes
        $(document).on('click', '.edit-expense-btn', function(e) {
            e.stopPropagation();
            var $btn = $(this);
            $('#ee_expense_id').val($btn.data('expense-id'));
            $('#ee_invoice_no').val($btn.data('invoice-no'));
            $('#ee_description').val($btn.data('description'));
            $('#ee_lkr_amount').val($btn.data('lkr-amount'));
            $('#ee_category').val($btn.data('category') || '');
            loadEditExpenseAccounts($btn.data('category'), $btn.data('supplier'));
            new bootstrap.Modal(document.getElementById('editExpenseModal')).show();
        });

        $('#editExpenseSaveBtn').on('click', function() {
            var invoiceNo = $('#ee_invoice_no').val();
            var lkrAmount = $('#ee_lkr_amount').val();

            if (!invoiceNo || lkrAmount === '' || isNaN(lkrAmount)) {
                Swal.fire({ icon: 'warning', title: 'Invoice No and a valid LKR Amount are required' });
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: "<?= base_url() ?>dashboard2/transport/driver_expense/admin_update_expense",
                type: 'POST',
                data: {
                    expense_id: $('#ee_expense_id').val(),
                    invoice_no: invoiceNo,
                    description: $('#ee_description').val(),
                    lkr_amount: lkrAmount,
                    category: $('#ee_category').val(),
                    supplier: $('#ee_supplier').val()
                },
                dataType: 'json',
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.status == 200) {
                        bootstrap.Modal.getInstance(document.getElementById('editExpenseModal')).hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Expense Updated',
                            text: response.message
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong!' });
                }
            });
        });

        // Delete Expense
        $(document).on('click', '.delete-expense-btn', function(e) {
            e.stopPropagation();
            var expenseId = $(this).data('expense-id');

            Swal.fire({
                title: 'Delete this expense?',
                text: "This cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: "<?= base_url() ?>dashboard2/transport/driver_expense/admin_delete_expense",
                        data: { expense_id: expenseId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                            }
                        },
                        error: function() {
                            Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong!' });
                        }
                    });
                }
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
