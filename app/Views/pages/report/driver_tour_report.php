<?= view('layouts/head') ?>

<style>
    @page {
        size: auto;
        margin: 6mm;
    }

    @media print {

        #layout-wrapper>*:not(.main-content),
        .app-menu,
        .navbar-header,
        .vertical-overlay,
        #tour_search_form,
        .no-print,
        .btn,
        footer {
            display: none !important;
        }

        html,
        body,
        #layout-wrapper,
        .main-content,
        .page-content,
        .container-fluid,
        .row,
        .col,
        .h-100,
        #report_area,
        #report_area>.col-md-12,
        .card,
        .card>.body {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            flex: 0 0 100% !important;
            left: 0 !important;
        }

        .main-content {
            margin-left: 0 !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .table-responsive {
            overflow: visible !important;
            width: 100% !important;
        }

        table.report-daily-log-table,
        table.report-detail-table {
            width: 100% !important;
        }

        body {
            font-size: 11px;
            line-height: 1.25;
            color: #000;
        }

        .print-report-header {
            display: block !important;
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }

        .print-report-header h2 {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
        }

        .print-report-header p {
            margin: 2px 0 0;
            font-size: 11px;
            color: #333;
        }

        table {
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        thead {
            display: table-header-group;
        }

        .table-bordered,
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000 !important;
        }

        /* Photo columns are hidden on print — give Date/KM/Planned less room */
        .report-daily-log-table th:nth-child(1),
        .report-daily-log-table td:nth-child(1) {
            width: 12% !important;
        }

        .report-daily-log-table th:nth-child(2),
        .report-daily-log-table td:nth-child(2) {
            width: 18% !important;
        }

        .report-daily-log-table th:nth-child(3),
        .report-daily-log-table td:nth-child(3) {
            width: 12% !important;
        }

        .report-daily-log-table th:nth-child(4),
        .report-daily-log-table td:nth-child(4) {
            width: 12% !important;
        }

        .report-daily-log-table th:nth-child(5),
        .report-daily-log-table td:nth-child(5) {
            width: 20% !important;
        }

        .badge {
            display: none !important;
        }

        .table-danger,
        .table-danger>th,
        .table-danger>td,
        td.table-danger,
        th.table-danger {
            background-color: transparent !important;
            color: inherit !important;
        }

        .report-meter-table th,
        .report-meter-table td {
            padding: 2px 5px !important;
        }

        h6 {
            page-break-after: avoid;
            margin-top: 5px !important;
            margin-bottom: 3px !important;
            font-size: 12px !important;
        }

        .mb-4 {
            margin-bottom: 5px !important;
        }

        .mb-3 {
            margin-bottom: 4px !important;
        }

        .mb-2 {
            margin-bottom: 2px !important;
        }

        .report-detail-table td {
            padding: 1px 6px !important;
        }

        .border.rounded.p-2 {
            padding: 4px 6px !important;
            margin-bottom: 4px !important;
        }

        table.report-meter-table:not(.report-daily-log-table) th:nth-child(1) {
            width: 10% !important;
        }

        table.report-meter-table:not(.report-daily-log-table) th:nth-child(2) {
            width: 32% !important;
        }

        table.report-meter-table:not(.report-daily-log-table) th:nth-child(3) {
            width: 14% !important;
        }

        table.report-meter-table:not(.report-daily-log-table) th:nth-child(4) {
            width: 14% !important;
        }

        table.report-meter-table:not(.report-daily-log-table) th:nth-child(5) {
            width: 14% !important;
        }

        table.report-meter-table:not(.report-daily-log-table) th:nth-child(7) {
            width: 16% !important;
        }
    }

    .report-detail-table td {
        width: 25%;
        vertical-align: top;
        padding: 6px 10px;
    }

    .report-daily-log-table {
        width: 100%;
        table-layout: fixed;
    }

    .report-daily-log-table th:nth-child(1),
    .report-daily-log-table td:nth-child(1) {
        width: 14%;
    }

    .report-daily-log-table th:nth-child(2),
    .report-daily-log-table td:nth-child(2) {
        width: 18%;
    }

    .report-daily-log-table th:nth-child(3),
    .report-daily-log-table td:nth-child(3) {
        width: 12%;
    }

    .report-daily-log-table th:nth-child(4),
    .report-daily-log-table td:nth-child(4) {
        width: 10%;
    }

    .report-daily-log-table th:nth-child(5),
    .report-daily-log-table td:nth-child(5) {
        width: 28%;
    }

    .report-daily-log-table th:nth-child(6),
    .report-daily-log-table td:nth-child(6),
    .report-daily-log-table th:nth-child(7),
    .report-daily-log-table td:nth-child(7) {
        width: 70px;
    }

    .report-daily-log-table th:last-child,
    .report-daily-log-table td:last-child {
        width: auto;
    }

    .print-report-header {
        display: none;
    }
</style>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col">
                            <div class="h-100">

                                <div class="row no-print">
                                    <div class="col-md-10 d-flex">
                                        <h4 class="p-title">Driver Log Report</h4>
                                    </div>
                                </div>

                                <!-- Search -->
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <!-- <div class="card p-4">
                                            <div class="body">
                                                <form id="tour_search_form" method="get" accept-charset="utf-8"
                                                    action="<?= base_url('dashboard2/transport/driver_report') ?>">
                                                    <div class="row align-items-end">
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-bold">Tour ID</label>
                                                            <input type="text" name="tour_id" id="tour_id" class="form-control"
                                                                placeholder="Enter Tour ID"
                                                                value="<?= esc($tour_id ?? '') ?>" required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <button type="submit" class="btn btn-primary mt-2">
                                                                <i class="bi bi-search me-1"></i> Search
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div> -->
                                    </div>
                                </div>

                                <?php if (!empty($error)): ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="alert alert-warning"><?= esc($error) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($report)): ?>
                                    <?php $r = $report; ?>

                                    <!-- Report -->
                                    <div class="row clearfix mt-3" id="report_area">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">

                                                    <!-- Print-only report header -->
                                                    <div class="print-report-header">
                                                        <h2>Driver Log Report</h2>
                                                        <p>
                                                            <?= !empty($r['manual_id']) ? 'Manual ID: ' . esc($r['manual_id']) : 'Manual ID: N/A' ?>
                                                            &nbsp;|&nbsp; <?= $r['is_finalized'] ? 'Finalized' : 'Not Finalized' ?>
                                                            &nbsp;|&nbsp; Generated on <?= date('d M Y, h:i A') ?>
                                                        </p>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                                                        <h5 class="mb-0">
                                                            Tour <?= esc($r['manual_id']) ?>
                                                           
                                                            <?php if ($r['is_finalized']): ?>
                                                                <span class="badge bg-success ms-2">Finalized</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning text-dark ms-2">Not Finalized</span>
                                                            <?php endif; ?>
                                                        </h5>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                                                            <i class="bi bi-printer me-1"></i> Print
                                                        </button>
                                                    </div>

                                                    <!-- Tour / driver details -->
                                                    <table class="table table-borderless table-sm report-detail-table mb-4">
                                                        <tbody>
                                                            <tr>
                                                                <td>
                                                                    <small class="text-muted d-block">Driver Name</small>
                                                                    <?php if (!empty($allocations) && count($allocations) > 1): ?>
                                                                        <select id="driver_select" class="form-select form-select-sm no-print" style="width:auto;display:inline-block;">
                                                                            <?php foreach ($allocations as $allocRow): ?>
                                                                                <option value="<?= esc($allocRow['driver']) ?>" <?= $allocRow['driver'] === $r['driver_acc'] ? 'selected' : '' ?>>
                                                                                    <?= esc($allocRow['driver_name'] ?: $allocRow['driver']) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                        <strong class="d-none d-print-inline"><?= esc($r['driver_name'] ?: '-') ?></strong>
                                                                    <?php else: ?>
                                                                        <strong><?= esc($r['driver_name'] ?: '-') ?></strong>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><small class="text-muted d-block">Consignee</small><strong><?= esc($r['consignee'] ?: '-') ?></strong></td>
                                                                <td><small class="text-muted d-block">Total Days</small><strong><?= (int) $r['total_days'] ?></strong></td>
                                                                <td><small class="text-muted d-block">Start Date</small><strong><?= esc($r['start_date'] ?: '-') ?></strong></td>
                                                            </tr>
                                                            <tr>
                                                                <td><small class="text-muted d-block">End Date</small><strong><?= esc($r['end_date'] ?: '-') ?></strong></td>
                                                                <td><small class="text-muted d-block">Flagged Days</small><strong class="<?= $r['flagged_day_count'] > 0 ? 'text-danger' : '' ?>"><?= (int) $r['flagged_day_count'] ?></strong></td>
                                                                <td></td>
                                                                <td></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>

                                                    <!-- Combined meter readings + expenses, one row per day -->
                                                    <h6 class="fw-bold mb-2"><i class="bi bi-journal-text me-1"></i>Daily Log</h6>
                                                    <div class="table-responsive mb-4">
                                                        <table class="table table-bordered table-sm align-middle report-meter-table report-daily-log-table">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Date</th>
                                                                    <th>Driver Mileage</th>
                                                                    <th>System Estimated Max Millage</th>
                                                                    <th>Similar Driver Mileage</th>
                                                                    <th>Amended Mileage</th>
                                                                    <th class="no-print">Start Photo</th>
                                                                    <th class="no-print">End Photo</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if (!empty($r['daily_rows'])): ?>
                                                                    <?php foreach ($r['daily_rows'] as $rowIndex => $row): ?>
                                                                        <tr>
                                                                            <td><strong><?= date('d M Y', strtotime($row['date'])) ?></strong></td>
                                                                            <td class="<?= !empty($row['is_flagged']) ? 'table-danger' : '' ?>">
                                                                                <?php if ($row['distance'] !== null): ?>
                                                                                    <button type="button" class="btn btn-link p-0 no-print mileage-detail-btn" data-day-index="<?= (int) $rowIndex ?>">
                                                                                        <?= number_format($row['distance'], 0) ?> km
                                                                                    </button>
                                                                                    <span class="d-none d-print-inline"><?= number_format($row['distance'], 0) ?> km</span>
                                                                                    <?php if (!empty($row['is_flagged'])): ?>
                                                                                        <span class="badge bg-danger ms-1">Over Predictable</span>
                                                                                    <?php endif; ?>
                                                                                <?php else: ?>
                                                                                    <span class="text-muted">-</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td class="<?= !empty($row['is_flagged']) ? 'table-danger' : '' ?>">
                                                                                <?php if ($row['predictable_km_max'] !== null): ?>
                                                                                    <?= number_format($row['predictable_km_max'], 0) ?> km
                                                                                <?php else: ?>
                                                                                    <span class="text-muted">No plan on file</span>
                                                                                <?php endif; ?>
                                                                                <?php if (!empty($row['garage_km'])): ?>
                                                                                    <small class="text-muted d-block">(incl. Garage: <?= number_format($row['garage_km'], 0) ?> km)</small>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td>
                                                                                <?php if ($row['similar_route_km'] !== null): ?>
                                                                                    <button type="button" class="btn btn-link p-0 no-print similar-mileage-detail-btn" data-day-index="<?= (int) $rowIndex ?>">
                                                                                        <?= number_format($row['similar_route_km'], 0) ?> km
                                                                                    </button>
                                                                                    <span class="d-none d-print-inline"><?= number_format($row['similar_route_km'], 0) ?> km</span>
                                                                                <?php else: ?>
                                                                                    <span class="text-muted">No data</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td>
                                                                                <div class="amended-mileage-cell no-print" data-date="<?= esc($row['date'], 'attr') ?>">
                                                                                    <div class="input-group input-group-sm">
                                                                                        <input type="number" step="0.01" class="form-control amended-km-input" placeholder="Amended km" value="<?= $row['amended_km'] !== null ? esc($row['amended_km'], 'attr') : '' ?>">
                                                                                        <button type="button" class="btn btn-outline-success save-amendment-btn" title="Save"><i class="bi bi-check-lg"></i></button>
                                                                                    </div>
                                                                                    <input type="text" class="form-control form-control-sm mt-1 amended-note-input" placeholder="Note (optional)" value="<?= $row['amended_note'] !== null ? esc($row['amended_note'], 'attr') : '' ?>">
                                                                                    <?php if (!empty($row['amended_by'])): ?>
                                                                                        <small class="text-muted d-block mt-1">
                                                                                            By <?= esc($row['amended_by']) ?><?= !empty($row['amended_at']) ? ' on ' . esc($row['amended_at']) : '' ?>
                                                                                        </small>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                                <?php if ($row['amended_km'] !== null): ?>
                                                                                    <span class="d-none d-print-inline"><?= number_format((float) $row['amended_km'], 2) ?> km</span>
                                                                                    <?php if (!empty($row['amended_note'])): ?>
                                                                                        <small class="d-none d-print-block text-muted"><?= esc($row['amended_note']) ?></small>
                                                                                    <?php endif; ?>
                                                                                    <?php if (!empty($row['amended_by'])): ?>
                                                                                        <small class="d-none d-print-block text-muted">By <?= esc($row['amended_by']) ?><?= !empty($row['amended_at']) ? ' on ' . esc($row['amended_at']) : '' ?></small>
                                                                                    <?php endif; ?>
                                                                                <?php else: ?>
                                                                                    <span class="d-none d-print-inline text-muted">-</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td class="no-print">
                                                                                <?php if (!empty($row['start_photo'])): ?>
                                                                                    <a href="<?= base_url('uploads/meter_readings/' . $r['tour_id'] . '/' . $row['start_photo']) ?>" target="_blank">
                                                                                        <img src="<?= base_url('uploads/meter_readings/' . $r['tour_id'] . '/' . $row['start_photo']) ?>" style="max-width:55px;max-height:45px;border-radius:4px;">
                                                                                    </a>
                                                                                <?php elseif (!empty($row['start_reading_id'])): ?>
                                                                                    <button type="button" class="btn btn-outline-secondary btn-sm add-reading-photo-btn" data-reading-id="<?= (int) $row['start_reading_id'] ?>" title="Add photo">
                                                                                        <i class="bi bi-camera"></i>
                                                                                    </button>
                                                                                <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                                                            </td>
                                                                            <td class="no-print">
                                                                                <?php if (!empty($row['end_photo'])): ?>
                                                                                    <a href="<?= base_url('uploads/meter_readings/' . $r['tour_id'] . '/' . $row['end_photo']) ?>" target="_blank">
                                                                                        <img src="<?= base_url('uploads/meter_readings/' . $r['tour_id'] . '/' . $row['end_photo']) ?>" style="max-width:55px;max-height:45px;border-radius:4px;">
                                                                                    </a>
                                                                                <?php elseif (!empty($row['end_reading_id'])): ?>
                                                                                    <button type="button" class="btn btn-outline-secondary btn-sm add-reading-photo-btn" data-reading-id="<?= (int) $row['end_reading_id'] ?>" title="Add photo">
                                                                                        <i class="bi bi-camera"></i>
                                                                                    </button>
                                                                                <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <tr><td colspan="7" class="text-center text-muted">No daily log entries</td></tr>
                                                                <?php endif; ?>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr class="table-light">
                                                                    <th class="text-end">Total</th>
                                                                    <th>Distance: <?= number_format($r['total_distance'], 0) ?> km</th>
                                                                    <th>Estimated Max Millage: <?= number_format($r['total_predictable_km_max'], 0) ?> km</th>
                                                                    <th></th>
                                                                    <th>
                                                                        <?php if (!empty($r['log_sheet_mileage_is_override'])): ?>
                                                                            <?php if ($r['log_sheet_mileage_current'] !== null): ?>
                                                                                Amended: <?= number_format((float) $r['log_sheet_mileage_current'], 0) ?> km
                                                                            <?php endif; ?>
                                                                            <?php if ($r['log_sheet_mileage_previous'] !== null): ?>
                                                                                <small class="text-muted d-block">(was <?= number_format((float) $r['log_sheet_mileage_previous'], 0) ?> km)</small>
                                                                            <?php endif; ?>
                                                                            <?php if (!empty($r['log_sheet_mileage_overridden_by'])): ?>
                                                                                <small class="text-muted d-block">by <?= esc($r['log_sheet_mileage_overridden_by']) ?></small>
                                                                            <?php endif; ?>
                                                                        <?php endif; ?>
                                                                    </th>
                                                                    <th colspan="2" class="no-print"></th>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>

                                                    <!-- Expenses — unified view over driver_portal_expenses (row of record, editable
                                                         while Pending) joined to its driver_expenses copy once TourFinalize() makes
                                                         one (Status becomes Posted once that copy is approved/posted to accounts —
                                                         see driverReport()'s is_posted). Edit/Delete keep both tables in sync via
                                                         admin_update_expense/admin_delete_expense. -->
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <h6 class="fw-bold mb-0"><i class="bi bi-receipt me-1"></i>Expenses</h6>
                                                        <div class="no-print">
                                                            <?php $hasUnsentExpenseRequest = !empty(array_filter($r['expenses'] ?? [], static fn($ex) => !empty($ex['has_unsent_request']))); ?>
                                                            <button type="button" class="btn btn-sm btn-primary <?= $hasUnsentExpenseRequest ? '' : 'd-none' ?>" id="send_all_btn" onclick="sendAllExpenseRequests()">
                                                                <i class="bi bi-send-fill me-1"></i> Send All Requests
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                                                                <i class="bi bi-plus-lg me-1"></i> Add Expense
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="table-responsive mb-4">
                                                        <table class="table table-bordered table-sm align-middle report-meter-table">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Date</th>
                                                                    <th>Details</th>
                                                                    <th>Created By</th>
                                                                    <th>Checked By</th>
                                                                    <th>Approved By</th>
                                                                    <th>Status</th>
                                                                    <th class="no-print">Invoice Image</th>
                                                                    <th class="text-end">LKR Amount</th>
                                                                    <th class="no-print">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if (!empty($r['expenses'])): ?>
                                                                    <?php foreach ($r['expenses'] as $ex): ?>
                                                                        <tr>
                                                                            <td><?= !empty($ex['expense_date']) ? date('d M Y', strtotime($ex['expense_date'])) : '-' ?></td>
                                                                            <td>
                                                                                <strong class="d-block"><?= esc($ex['category_label']) ?></strong>
                                                                                <small class="text-muted d-block"><?= esc($ex['supplier_name']) ?></small>
                                                                                <small class="text-muted d-block"><?= esc($ex['description']) ?></small>
                                                                            </td>
                                                                            <td><?= !empty($ex['created_by']) ? esc($ex['created_by']) : '<span class="text-muted">-</span>' ?></td>
                                                                            <td><?= !empty($ex['checked_by']) ? esc($ex['checked_by']) : '<span class="text-muted">-</span>' ?></td>
                                                                            <td><?= !empty($ex['approved_by']) ? esc($ex['approved_by']) : '<span class="text-muted">-</span>' ?></td>
                                                                            <td>
                                                                                <span class="badge <?= $ex['is_posted'] ? 'bg-success' : 'bg-warning text-dark' ?>"><?= esc($ex['status_label']) ?></span>
                                                                            </td>
                                                                            <td class="no-print">
                                                                                <?php if (!empty($ex['invoice_image_src'])): ?>
                                                                                    <a href="<?= $ex['invoice_image_src'] ?>" target="_blank">
                                                                                        <img src="<?= $ex['invoice_image_src'] ?>" style="max-width:55px;max-height:45px;border-radius:4px;">
                                                                                    </a>
                                                                                <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                                                            </td>
                                                                            <td class="text-end"><?= number_format($ex['lkr_amount'], 2) ?></td>
                                                                            <td class="no-print">
                                                                                <?php if (!$ex['is_posted'] && empty($ex['is_orphan'])): ?>
                                                                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick='openEditExpense(<?= (int) $ex['id'] ?>, <?= json_encode($ex) ?>)'><i class="bi bi-pencil"></i></button>
                                                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteExpense(<?= (int) $ex['id'] ?>)"><i class="bi bi-trash"></i></button>
                                                                                <?php else: ?>
                                                                                    -
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <tr><td colspan="9" class="text-center text-muted">No expenses entered</td></tr>
                                                                <?php endif; ?>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr class="table-light">
                                                                    <th colspan="6" class="text-end">Total</th>
                                                                    <th class="no-print"></th>
                                                                    <th class="text-end">LKR <?= number_format($r['total_lkr'], 2) ?></th>
                                                                    <th class="no-print"></th>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>

                                                    <!-- Edit Expense modal -->
                                                    <div class="modal fade no-print" id="editExpenseModal" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Edit Expense</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form id="edit_expense_form">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" id="edit_expense_id">
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Expenses Type <span class="text-danger">*</span></label>
                                                                            <select class="form-control" id="edit_expense_category" required>
                                                                                <option value="6-9-23-10640">Entrance Fee</option>
                                                                                <option value="6-9-23-10641">Other</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Expenses Acc <span class="text-danger">*</span></label>
                                                                            <select class="form-control" id="edit_expense_supplier" required>
                                                                                <option value="">Select a Supplier...</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Invoice No <span class="text-danger">*</span></label>
                                                                            <input type="text" class="form-control" id="edit_expense_invoice_no" required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Description</label>
                                                                            <input type="text" class="form-control" id="edit_expense_description">
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Date <span class="text-danger">*</span></label>
                                                                            <input type="date" class="form-control" id="edit_expense_date" required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">LKR Amount <span class="text-danger">*</span></label>
                                                                            <input type="text" class="form-control" id="edit_expense_lkr_amount" required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Invoice Image</label>
                                                                            <input type="file" class="form-control" id="edit_expense_invoice_image" accept="image/*">
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-success">Save Changes</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Add Expense modal — same admin_save_expense endpoint / driver_portal_expenses
                                                         flow as transport_expenses.php, so a back-office entry here behaves
                                                         identically to that screen (incl. post-finalize routing). -->
                                                    <div class="modal fade no-print" id="addExpenseModal" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Add Expense</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form id="expense_form_submit" accept-charset="utf-8">
                                                                    <div class="modal-body">
                                                                        <div class="mb-3">
                                                                            <label class="form-label" for="expense_tagent">Select Agent <span class="text-danger">*</span></label>
                                                                            <select name="tagent" id="expense_tagent" class="form-control" required>
                                                                                <option value=""></option>
                                                                                <?php foreach (($allocations ?? []) as $allocRow): ?>
                                                                                    <option value="<?= esc($allocRow['driver'], 'attr') ?>" <?= $allocRow['driver'] === $r['driver_acc'] ? 'selected' : '' ?>>
                                                                                        <?= esc($allocRow['driver_name'] ?: $allocRow['driver']) ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label" for="expense_category">Expenses Type <span class="text-danger">*</span></label>
                                                                            <select name="category" id="expense_category" class="form-control" required>
                                                                                <option value="">Select a Category...</option>
                                                                                <option value="6-9-23-10640">Entrance Fee</option>
                                                                                <option value="6-9-23-10641">Other</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label" for="expense_supplier">Expenses Acc <span class="text-danger">*</span></label>
                                                                            <select name="supplier" id="expense_supplier" class="form-control" required>
                                                                                <option value="">Select a Supplier...</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label" for="expense_inv">Invoice No <span class="text-danger">*</span></label>
                                                                            <input class="form-control" type="text" id="expense_inv" name="inv" required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label" for="expense_description">Description</label>
                                                                            <input class="form-control" type="text" id="expense_description" name="description">
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label" for="expense_date_input">Date <span class="text-danger">*</span></label>
                                                                            <input class="form-control" type="date" id="expense_date_input" name="date" required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label" for="expense_rsamount">LKR Amount <span class="text-danger">*</span></label>
                                                                            <input class="form-control" type="text" id="expense_rsamount" name="rsamount" required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label" for="expense_invoice_image">Invoice Image</label>
                                                                            <input class="form-control" type="file" id="expense_invoice_image" name="invoice_image" accept="image/*">
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-success" id="expense_save_btn">
                                                                            <i class="bi bi-check-lg me-1"></i>Save Expense
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Driver Log Sheet — same source/formula as approved_expenses_report.php's
                                                         Driver Payment Summary, scoped to this driver -->
                                                    <h6 class="fw-bold mb-2"><i class="bi bi-clipboard-data me-1"></i>Driver Log Sheet</h6>
                                                    <?php if (!($r['payment_has_log_sheet'] ?? false)): ?>
                                                        <div class="alert alert-warning py-2 mb-3">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>No log sheet mileage recorded yet for this driver.
                                                        </div>
                                                    <?php endif; ?>
                                                    <table class="table table-borderless table-sm report-detail-table mb-3">
                                                        <tbody>
                                                            <tr>
                                                                <td><small class="text-muted d-block">Driver Added Millage</small><strong><?= number_format($r['total_distance'], 2) ?> km</strong></td>
                                                                <td><small class="text-muted d-block">Amended Mileage</small><strong><?= number_format($r['payment_amended_mileage'], 2) ?> km</strong></td>
                                                                <td><small class="text-muted d-block">Full Quotation Mileage (incl. Extra KM)</small><strong><?= number_format($r['total_quotation_km_with_extras'], 2) ?> km</strong></td>
                                                                <td><small class="text-muted d-block">Bata Days</small><strong><?= (int) $r['payment_bata_days'] ?></strong></td>
                                                            </tr>
                                                            <tr>
                                                                <td><small class="text-muted d-block">Vehicle Rate/Day</small><strong><?= number_format($r['payment_vehicle_rate'], 2) ?></strong></td>
                                                                <td><small class="text-muted d-block">Expenses (Approved)</small><strong>Rs. <?= number_format($r['payment_expenses_amount'], 2) ?></strong></td>
                                                                <td><small class="text-muted d-block">Bata Rate/Day</small><strong><?= number_format($r['payment_bata_rate'], 2) ?></strong></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>

                                                    <div class="border rounded p-2 mb-3">
                                                        <small class="text-muted d-block mb-1" style="font-size:0.7rem;">Settlement Formula</small>
                                                        <strong class="d-block" style="font-size:0.78rem; word-break: break-word;">
                                                            (<?= number_format($r['payment_bata_rate'], 0) ?>&times;<?= (int) $r['payment_bata_days'] ?>)
                                                            + (<?= number_format($r['payment_vehicle_rate'], 0) ?>&times;<?= number_format($r['payment_amended_mileage'], 1) ?>)
                                                            + Rs. <?= number_format($r['payment_expenses_amount'], 2) ?>
                                                            &minus; Rs. <?= number_format($r['payment_advance_amount'], 2) ?>
                                                            = <span class="<?= $r['payment_payable_amount'] >= 0 ? 'text-success' : 'text-danger' ?>">Rs. <?= number_format($r['payment_payable_amount'], 2) ?></span>
                                                        </strong>
                                                        <small class="text-muted d-block mt-1" style="font-size:0.65rem;">
                                                            (Bata Rate&times;Bata Days) + (Vehicle Rate&times;Amended Mileage) + Expenses (Approved) &minus; Advance Paid = Settlement Amount
                                                        </small>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mileage Detail Modal: planned route legs + activities for the day, opened per-row -->
                                    <div class="modal fade no-print" id="mileageDetailModal" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="mileageDetailModalLabel">Mileage Detail</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body" id="mileageDetailModalBody"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Add Reading Photo Modal: attach a photo to an existing start/end reading that has none -->
                                    <div class="modal fade no-print" id="addReadingPhotoModal" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Add Photo</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" id="arp_reading_id">
                                                    <div class="mb-3">
                                                        <label for="arp_photo" class="form-label">Photo</label>
                                                        <input type="file" class="form-control" id="arp_photo" accept="image/*" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-success" id="addReadingPhotoSaveBtn">Upload</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Similar Mileage Detail Modal: the other tours/drivers/dates that contributed to the median -->
                                    <div class="modal fade no-print" id="similarMileageDetailModal" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="similarMileageDetailModalLabel">Similar Mileage Detail</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body" id="similarMileageDetailModalBody"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?= view('layouts/brand') ?>

        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <?php if (!empty($report)): ?>
        <script>
            $(document).on('change', '#driver_select', function() {
                const params = new URLSearchParams(window.location.search);
                params.set('tour_id', <?= json_encode($tour_id ?? '') ?>);
                params.set('driver', $(this).val());
                window.location.href = '<?= base_url('dashboard2/transport/driver_report') ?>?' + params.toString();
            });

            // Expenses — unified table over driver_portal_expenses joined to driver_expenses.
            // Add posts to admin_save_expense; Edit/Delete post to admin_update_expense/
            // admin_delete_expense (both keep driver_portal_expenses and, when it exists, the
            // matching driver_expenses row in sync); Send All posts to send_all_requests.
            const expenseTourId = <?= json_encode($tour_id ?? '') ?>;
            const expenseDriverAcc = <?= json_encode($r['driver_acc'] ?? '') ?>;

            function populateExpenseSupplierSelect($select, category, selectedValue) {
                $select.empty().append('<option value="">Select a Supplier...</option>');
                if (!category) {
                    return;
                }
                $.ajax({
                    url: '<?= base_url('accounts/creditor_invoice/get_details') ?>',
                    type: 'POST',
                    data: { category: category },
                    dataType: 'json',
                    success: function(response) {
                        $.each(response.data, function(index, value) {
                            const opt = $('<option></option>').attr('value', value.L_Code).text(value.PName);
                            if (selectedValue && value.L_Code == selectedValue) {
                                opt.prop('selected', true);
                            }
                            $select.append(opt);
                        });
                    }
                });
            }

            // Add Expense modal
            $('#addExpenseModal').on('show.bs.modal', function() {
                $('#expense_form_submit')[0].reset();
                $('#expense_tagent').val(expenseDriverAcc);
                $('#expense_supplier').empty().append('<option value="">Select a Supplier...</option>');
            });

            $('#expense_category').on('change', function() {
                populateExpenseSupplierSelect($('#expense_supplier'), $(this).val(), null);
            });

            $('#expense_form_submit').on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData();
                formData.append('tour_id', expenseTourId);
                formData.append('driver_acc', $('#expense_tagent').val());
                formData.append('expense_date', $('#expense_date_input').val());
                formData.append('category', $('#expense_category').val());
                formData.append('supplier', $('#expense_supplier').val());
                formData.append('invoice_no', $('#expense_inv').val());
                formData.append('lkr_amount', $('#expense_rsamount').val());
                formData.append('description', $('#expense_description').val());

                const invoiceImage = $('#expense_invoice_image')[0].files[0];
                if (invoiceImage) {
                    formData.append('invoice_image', invoiceImage);
                }

                const $btn = $('#expense_save_btn');
                $btn.prop('disabled', true);

                $.ajax({
                    url: '<?= base_url('dashboard2/transport/transport_portal/admin_save_expense') ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    cache: false,
                    dataType: 'json',
                    success: function(response) {
                        $btn.prop('disabled', false);
                        if (response.status == 200) {
                            Swal.fire({ icon: 'success', title: 'Success', text: response.message }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false);
                        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Something went wrong! Please try again later.' });
                    }
                });
            });

            // Edit Expense modal
            function openEditExpense(expenseId, expense) {
                $('#edit_expense_id').val(expenseId);
                $('#edit_expense_category').val(expense.category);
                $('#edit_expense_invoice_no').val(expense.invoice_no);
                $('#edit_expense_description').val(expense.description);
                $('#edit_expense_date').val(expense.expense_date);
                $('#edit_expense_lkr_amount').val(expense.lkr_amount);
                $('#edit_expense_invoice_image').val('');
                populateExpenseSupplierSelect($('#edit_expense_supplier'), expense.category, expense.supplier);
                new bootstrap.Modal(document.getElementById('editExpenseModal')).show();
            }

            $('#edit_expense_category').on('change', function() {
                populateExpenseSupplierSelect($('#edit_expense_supplier'), $(this).val(), null);
            });

            $('#edit_expense_form').on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData();
                formData.append('expense_id', $('#edit_expense_id').val());
                formData.append('tour_id', expenseTourId);
                formData.append('driver_acc', expenseDriverAcc);
                formData.append('category', $('#edit_expense_category').val());
                formData.append('supplier', $('#edit_expense_supplier').val());
                formData.append('invoice_no', $('#edit_expense_invoice_no').val());
                formData.append('expense_date', $('#edit_expense_date').val());
                formData.append('lkr_amount', $('#edit_expense_lkr_amount').val());
                formData.append('description', $('#edit_expense_description').val());

                const invoiceImage = $('#edit_expense_invoice_image')[0].files[0];
                if (invoiceImage) {
                    formData.append('invoice_image', invoiceImage);
                }

                $.ajax({
                    url: '<?= base_url('dashboard2/transport/transport_portal/admin_update_expense') ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    cache: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {
                            bootstrap.Modal.getInstance(document.getElementById('editExpenseModal')).hide();
                            Swal.fire({ icon: 'success', title: 'Success', text: response.message }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Something went wrong! Please try again later.' });
                    }
                });
            });

            function deleteExpense(expenseId) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You want to delete this expense!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: '<?= base_url('dashboard2/transport/transport_portal/admin_delete_expense') ?>',
                            data: { expense_id: expenseId, tour_id: expenseTourId, driver_acc: expenseDriverAcc },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({ icon: 'success', title: 'Deleted!', text: response.message }).then(function() {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                                }
                            },
                            error: function() {
                                Swal.fire({ icon: 'error', title: 'Oops...', text: 'Something went wrong! Please try again later.' });
                            }
                        });
                    }
                });
            }

            function sendAllExpenseRequests() {
                Swal.fire({
                    title: 'Send All Requests?',
                    text: 'Are you sure you want to send all pending expenses for approval?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, send all!'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: '<?= base_url('dashboard2/transport/driver_expense/send_all_requests') ?>',
                            data: { tour_id: expenseTourId },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({ icon: 'success', title: 'Sent!', text: response.message }).then(function() {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                                }
                            },
                            error: function() {
                                Swal.fire({ icon: 'error', title: 'Oops...', text: 'Something went wrong! Please try again later.' });
                            }
                        });
                    }
                });
            }

            const mileageDayData = <?= json_encode(array_map(static function ($row) {
                return [
                    'date'       => $row['date'],
                    'date_label' => date('d M Y', strtotime($row['date'])),
                    'distance'   => $row['distance'],
                    'planned_km' => $row['planned_km'],
                    'planned_km_base' => $row['planned_km_base'] ?? null,
                    'predictable_km_min' => $row['predictable_km_min'] ?? null,
                    'predictable_km_max' => $row['predictable_km_max'] ?? null,
                    'similar_route_km' => $row['similar_route_km'] ?? null,
                    'similar_route_match_count' => $row['similar_route_match_count'] ?? 0,
                    'similar_route_matches' => $row['similar_route_matches'] ?? [],
                    'is_flagged' => !empty($row['is_flagged']),
                    'activities' => $row['activities'] ?? [],
                    'route'      => $row['route'] ?? [],
                    'start_km' => $row['start_km'],
                    'end_km'   => $row['end_km'],
                    'start_reading_id' => $row['start_reading_id'] ?? null,
                    'end_reading_id'   => $row['end_reading_id'] ?? null,
                    'start_km_override' => $row['start_km_override'] ?? null,
                    'end_km_override'   => $row['end_km_override'] ?? null,
                ];
            }, $report['daily_rows'] ?? [])) ?>;

            function escapeHtmlLocal(text) {
                if (text === null || text === undefined) return '';
                return $('<div>').text(text).html();
            }

            // Renders one Start/End odometer row with an edit icon and, when applicable, the
            // override history (previous value + who changed it) preserved by overrideMeterReading().
            function odometerReadingRowHtml(label, readingId, km, override) {
                var kmText = km !== null ? Number(km).toLocaleString(undefined, {maximumFractionDigits: 2}) + ' km' : '-';
                var html = '<div class="d-flex justify-content-between align-items-center border-bottom py-1">';
                html += '<span>' + label + ': <strong>' + kmText + '</strong></span>';
                if (readingId) {
                    html += '<button type="button" class="btn btn-link btn-sm p-0 edit-reading-btn" data-reading-id="' + readingId + '" data-label="' + label + '" data-current-km="' + (km !== null ? km : '') + '"><i class="bi bi-pencil-square"></i></button>';
                }
                html += '</div>';
                if (override) {
                    html += '<small class="text-muted d-block mb-1">';
                    html += 'Overridden from ' + (override.previous_km !== null ? Number(override.previous_km).toLocaleString(undefined, {maximumFractionDigits: 2}) + ' km' : '-');
                    html += ' by ' + escapeHtmlLocal(override.overridden_by || '-');
                    if (override.overridden_at) {
                        html += ' on ' + escapeHtmlLocal(override.overridden_at);
                    }
                    html += '</small>';
                }
                return html;
            }

            $(document).on('click', '.mileage-detail-btn', function() {
                const day = mileageDayData[$(this).data('day-index')];
                if (!day) return;

                $('#mileageDetailModalLabel').text('Mileage Detail — ' + day.date_label);

                let html = '<div class="mb-3">';
                html += '<small class="text-muted d-block">Driver Mileage</small>';
                html += '<strong>' + (day.distance !== null ? day.distance.toLocaleString(undefined, {maximumFractionDigits: 0}) + ' km' : '-') + '</strong>';
                if (day.is_flagged) {
                    html += ' <span class="badge bg-danger ms-1">Over Predictable</span>';
                }
                html += '</div>';

                html += '<div class="mb-3">';
                html += '<small class="text-muted d-block mb-1">Odometer Readings</small>';
                html += odometerReadingRowHtml('Start', day.start_reading_id, day.start_km, day.start_km_override);
                html += odometerReadingRowHtml('End', day.end_reading_id, day.end_km, day.end_km_override);
                html += '</div>';

                html += '<div class="mb-3">';
                html += '<small class="text-muted d-block">Predictable Mileage Range</small>';
                if (day.predictable_km_min !== null && day.predictable_km_max !== null) {
                    html += '<strong>' + day.predictable_km_min.toLocaleString(undefined, {maximumFractionDigits: 0}) + ' – ' + day.predictable_km_max.toLocaleString(undefined, {maximumFractionDigits: 0}) + ' km</strong>';
                } else {
                    html += '<strong>No plan on file</strong>';
                }
                html += '<small class="text-muted d-block"></small>';
                html += '</div>';

                html += '<div class="mb-3">';
                html += '<small class="text-muted d-block">Similar Mileage</small>';
                if (day.similar_route_km !== null && day.similar_route_km !== undefined) {
                    html += '<strong>' + day.similar_route_km.toLocaleString(undefined, {maximumFractionDigits: 0}) + ' km</strong>';
                    html += '<small class="text-muted d-block">Median actual mileage from ' + day.similar_route_match_count + ' other ' + (day.similar_route_match_count === 1 ? 'trip' : 'trips') + ' with this exact same set of waypoints</small>';
                } else {
                    html += '<strong>No data</strong>';
                    html += '<small class="text-muted d-block">No other trip has driven this exact same set of waypoints</small>';
                }
                html += '</div>';

                if (day.activities && day.activities.length > 0) {
                    html += '<div class="mb-3">';
                    html += '<small class="text-muted d-block mb-1"><i class="bi bi-geo-alt"></i> Activities</small>';
                    html += escapeHtmlLocal(day.activities.join(', '));
                    html += '</div>';
                }

                if (day.route && day.route.length > 0) {
                    html += '<div>';
                    html += '<small class="text-muted d-block mb-1"><i class="bi bi-signpost-split"></i> Route Legs</small>';
                    html += '<table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th>From</th><th>To</th><th class="text-end">Estimate KM</th><th class="text-end">Extra KM</th></tr></thead><tbody>';
                    day.route.forEach(function(leg) {
                        html += '<tr>';
                        html += '<td>' + escapeHtmlLocal(leg.from_location || '-') + '</td>';
                        html += '<td>' + escapeHtmlLocal(leg.to_location || '-') + '</td>';
                        html += '<td class="text-end">' + (leg.gdistance !== null ? leg.gdistance.toLocaleString(undefined, {maximumFractionDigits: 0}) : '-') + '</td>';
                        html += '<td class="text-end">' + (leg.extra_kilo !== null ? leg.extra_kilo.toLocaleString(undefined, {maximumFractionDigits: 0}) : '-');
                        html += leg.is_activity_leg ? ' <span class="badge bg-success">Activity</span>' : ' <span class="badge bg-secondary">Transit</span>';
                        html += '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                    html += '</div>';
                } else {
                    html += '<div class="text-muted">No planned route on file for this day.</div>';
                }

                $('#mileageDetailModalBody').html(html);
                new bootstrap.Modal(document.getElementById('mileageDetailModal')).show();
            });

            $(document).on('click', '.similar-mileage-detail-btn', function() {
                const day = mileageDayData[$(this).data('day-index')];
                if (!day) return;

                $('#similarMileageDetailModalLabel').text('Similar Mileage — ' + day.date_label);

                let html = '<div class="mb-3">';
                html += '<small class="text-muted d-block">Similar Mileage (Median)</small>';
                if (day.similar_route_km !== null && day.similar_route_km !== undefined) {
                    html += '<strong>' + day.similar_route_km.toLocaleString(undefined, {maximumFractionDigits: 0}) + ' km</strong>';
                    html += '<small class="text-muted d-block">From ' + day.similar_route_match_count + ' other ' + (day.similar_route_match_count === 1 ? 'trip' : 'trips') + ' with this exact same set of waypoints</small>';
                } else {
                    html += '<strong>No data</strong>';
                }
                html += '</div>';

                if (day.similar_route_matches && day.similar_route_matches.length > 0) {
                    html += '<small class="text-muted d-block mb-1"><i class="bi bi-signpost-split"></i> Matching Trips</small>';
                    html += '<table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th>Tour</th><th>Driver</th><th>Date</th><th class="text-end">Distance</th></tr></thead><tbody>';
                    day.similar_route_matches.forEach(function(m) {
                        html += '<tr>';
                        html += '<td>' + escapeHtmlLocal(m.manual_id || '-') + '</td>';
                        html += '<td>' + escapeHtmlLocal(m.driver_name || '-') + '</td>';
                        html += '<td>' + escapeHtmlLocal(m.date || '-') + '</td>';
                        html += '<td class="text-end">' + Number(m.distance_km).toLocaleString(undefined, {maximumFractionDigits: 0}) + ' km</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<div class="text-muted">No other trip has driven this exact same set of waypoints.</div>';
                }

                $('#similarMileageDetailModalBody').html(html);
                new bootstrap.Modal(document.getElementById('similarMileageDetailModal')).show();
            });

            // Add a photo to an existing start/end reading that has none yet.
            $(document).on('click', '.add-reading-photo-btn', function() {
                $('#arp_reading_id').val($(this).data('reading-id'));
                $('#arp_photo').val('');
                new bootstrap.Modal(document.getElementById('addReadingPhotoModal')).show();
            });

            $('#addReadingPhotoSaveBtn').on('click', function() {
                const readingId = $('#arp_reading_id').val();
                const fileInput = document.getElementById('arp_photo');
                const file = fileInput.files[0];

                if (!file) {
                    Swal.fire({ icon: 'warning', title: 'Please choose a photo file' });
                    return;
                }

                const formData = new FormData();
                formData.append('reading_id', readingId);
                formData.append('photo', file);

                const $btn = $(this);
                $btn.prop('disabled', true);

                $.ajax({
                    url: "<?= base_url('dashboard2/transport/transport_portal/add_meter_reading_photo') ?>",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        $btn.prop('disabled', false);
                        if (response.status == 200) {
                            bootstrap.Modal.getInstance(document.getElementById('addReadingPhotoModal')).hide();
                            Swal.fire({
                                icon: 'success',
                                title: 'Photo Added',
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

            // Swap an odometer row's edit icon for an inline form instead of a plain prompt().
            $(document).on('click', '.edit-reading-btn', function() {
                const $btn = $(this);
                const readingId = $btn.data('reading-id');
                const label = $btn.data('label');
                const currentKm = $btn.data('current-km');

                const formHtml =
                    '<div class="input-group input-group-sm mt-1 edit-reading-form" data-reading-id="' + readingId + '">' +
                    '<input type="number" step="0.01" class="form-control edit-reading-input" value="' + currentKm + '">' +
                    '<button class="btn btn-success save-reading-btn" type="button" title="Save"><i class="bi bi-check-lg"></i></button>' +
                    '<button class="btn btn-outline-secondary cancel-reading-btn" type="button" title="Cancel"><i class="bi bi-x-lg"></i></button>' +
                    '</div>';

                $btn.closest('.d-flex').after(formHtml);
                $btn.closest('.d-flex').hide();
            });

            $(document).on('click', '.cancel-reading-btn', function() {
                const $form = $(this).closest('.edit-reading-form');
                $form.prev('.d-flex').show();
                $form.remove();
            });

            $(document).on('click', '.save-reading-btn', function() {
                const $form = $(this).closest('.edit-reading-form');
                const readingId = $form.data('reading-id');
                const newKm = $form.find('.edit-reading-input').val();

                if (newKm === '' || isNaN(newKm)) {
                    Swal.fire({ icon: 'warning', title: 'Enter a valid KM value' });
                    return;
                }

                $.ajax({
                    url: '<?= base_url('dashboard2/transport/driver_report/override_meter_reading') ?>',
                    type: 'POST',
                    data: { id: readingId, km: newKm },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Reading Updated',
                                text: response.message
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong!' });
                    }
                });
            });

            // Save a per-day Amended Mileage (with optional note) — recalculates and inserts a new
            // tour-total log_sheet_mile row on the server, so the payment figure stays in sync.
            $(document).on('click', '.save-amendment-btn', function() {
                const $cell = $(this).closest('.amended-mileage-cell');
                const date = $cell.data('date');
                const amendedKm = $cell.find('.amended-km-input').val();
                const note = $cell.find('.amended-note-input').val();

                if (amendedKm === '' || isNaN(amendedKm)) {
                    Swal.fire({ icon: 'warning', title: 'Enter a valid amended mileage' });
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true);

                $.ajax({
                    url: '<?= base_url('dashboard2/transport/driver_report/save_mileage_amendment') ?>',
                    type: 'POST',
                    data: {
                        tour_id: '<?= esc($r['tour_id']) ?>',
                        driver_acc: '<?= esc($r['driver_acc']) ?>',
                        reading_date: date,
                        amended_km: amendedKm,
                        note: note
                    },
                    dataType: 'json',
                    success: function(response) {
                        $btn.prop('disabled', false);
                        if (response.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Amendment Saved',
                                text: response.message
                            }).then(() => location.reload());
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
        </script>
    <?php endif; ?>
</body>

</html>
