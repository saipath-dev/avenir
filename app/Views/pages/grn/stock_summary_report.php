<?= view('layouts/head') ?>

<body>

    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>

    <style>
        .opening-row { background-color: #f8f9fa; font-style: italic; }
        .credit-cell { color: #dc3545; }
        .debit-cell { color: #78b848; }
        .balance-cell { font-weight: 600; }
    </style>

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <!-- Page Title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 p-title">Stock Summary Report</h4>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" action="<?= base_url('inventory/stock/summary_report') ?>" id="filterForm">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" name="startdate" class="form-control" value="<?= esc($startdate ?? '') ?>">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">End Date</label>
                                            <input type="date" name="enddate" class="form-control" value="<?= esc($enddate ?? '') ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Item (Code/Description)</label>
                                            <select name="item_keyword" id="item_keyword" class="form-control select2" style="width: 100%;">
                                                <option value="">-- All Items --</option>
                                                <?php foreach ($stock_items as $item): ?>
                                                    <option value="<?= esc($item['Stock_Card']) ?>" <?= (isset($item_keyword) && $item_keyword == $item['Stock_Card']) ? 'selected' : '' ?>>
                                                        <?= esc($item['Stock_Card']) ?> - <?= esc($item['Descrip']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <input type="hidden" name="submit" value="1">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Generate Report
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="window.location.href='<?= base_url('inventory/stock/summary_report') ?>'">
                                                <i class="fas fa-undo"></i> Clear
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($is_search && !empty($error_message)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-warning"><?= esc($error_message) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($is_search && empty($error_message)): ?>

                <!-- Export Button -->
                <div class="row">
                    <div class="col-12 mb-3">
                        <a href="<?= base_url('inventory/stock/summary_report_export_excel?' . http_build_query($_GET)) ?>"
                           class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export to CSV
                        </a>
                    </div>
                </div>

                <!-- Ledger Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Item Code</th>
                                                <th>Date</th>
                                                <th>Tour</th>
                                                <th>Description</th>
                                                <th class="text-end">Debit</th>
                                                <th class="text-end">Credit</th>
                                                <th class="text-end">Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($resultlist)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No data found for the selected filters.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($resultlist as $row): ?>
                                                    <?php if (!empty($row['is_opening_row'])) continue; ?>
                                                    <tr class>
                                                        <td><?= esc($row['Stock_Card'] ?? '') ?></td>
                                                        <td><?= (!empty($row['txn_date']) && strtotime($row['txn_date']) > 0) ? date('Y-m-d H:i', strtotime($row['txn_date'])) : '-' ?></td>
                                                        <td><?= esc($row['tour']) ?></td>
                                                        <td><?= esc($row['description']) ?></td>
                                                        <td class="text-end debit-cell"><?= $row['debit'] !== null ? number_format($row['debit']) : '' ?></td>
                                                        <td class="text-end credit-cell"><?= $row['credit'] !== null ? number_format($row['credit']) : '' ?></td>
                                                        <td class="text-end balance-cell"><?= number_format($row['running_balance']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                        <?php if (!empty($resultlist)): ?>
                                        <tfoot>
                                            <tr class="table-primary fw-bold">
                                                <td colspan="4" class="text-end">TOTAL:</td>
                                                <td class="text-end"><?= number_format($totals['total_debit']) ?></td>
                                                <td class="text-end"><?= number_format($totals['total_credit']) ?></td>
                                                <td class="text-end"><?= number_format($totals['grand_closing_balance']) ?></td>
                                            </tr>
                                            <tr class="table-warning fw-bold">
                                                <td colspan="6" class="text-end">DIFFERENCE (Debit - Credit):</td>
                                                <td class="text-end"><?= number_format($totals['total_difference']) ?></td>
                                            </tr>
                                        </tfoot>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php endif; ?>

            </div>
            <!-- container-fluid -->
        </div>
        <!-- End Page-content -->

        <?= view('layouts/brand') ?>

    </div>
    <!-- end main content-->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $(document).ready(function() {
            $('#item_keyword').select2({
                placeholder: '-- All Items --',
                allowClear: true,
                width: '100%'
            });
        });
    </script>

</body>
</html>
