<?= view('layouts/head') ?>

<body>

    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <!-- Page Title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 p-title">Stock Return Report</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="<?= base_url('inventory/stock') ?>">Inventory</a></li>
                                    <li class="breadcrumb-item active">Stock Return Report</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-filter"></i> Filter Options
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="<?= base_url('inventory/stock/return_report') ?>" id="filterForm">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" name="startdate" class="form-control" value="<?= esc($startdate ?? '') ?>">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">End Date</label>
                                            <input type="date" name="enddate" class="form-control" value="<?= esc($enddate ?? '') ?>">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Item (Code/Description)</label>
                                            <select name="item_keyword" id="item_keyword" class="form-control select2" style="width: 100%;">
                                                <option value="">-- All Items --</option>
                                                <?php foreach ($stock_items as $item): ?>
                                                    <option value="<?= esc($item['Stock_Card']) ?>" <?= (isset($item_keyword) && $item_keyword == $item['Stock_Card']) ? 'selected' : '' ?>>
                                                        <?= esc($item['Descrip']) ?> (<?= esc($item['Stock_Card']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Return Reason</label>
                                            <select name="reason" class="form-control">
                                                <option value="">All Reasons</option>
                                                <option value="Damaged" <?= ($reason ?? '') === 'Damaged' ? 'selected' : '' ?>>Damaged</option>
                                                <option value="Expired" <?= ($reason ?? '') === 'Expired' ? 'selected' : '' ?>>Expired</option>
                                                <option value="Defective" <?= ($reason ?? '') === 'Defective' ? 'selected' : '' ?>>Defective</option>
                                                <option value="Overstocked" <?= ($reason ?? '') === 'Overstocked' ? 'selected' : '' ?>>Overstocked</option>
                                                <option value="Wrong Item" <?= ($reason ?? '') === 'Wrong Item' ? 'selected' : '' ?>>Wrong Item</option>
                                                <option value="Other" <?= ($reason ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Status</label>
                                            <select name="status_filter" class="form-control">
                                                <option value="">All Status</option>
                                                <option value="pending" <?= ($status_filter ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="processed" <?= ($status_filter ?? '') === 'processed' ? 'selected' : '' ?>>Processed</option>
                                                <option value="cancelled" <?= ($status_filter ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="col-md-9 mb-3">
                                            <label class="form-label">&nbsp;</label><br>
                                            <input type="hidden" name="submit" value="1">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Generate Report
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="window.location.href='<?= base_url('inventory/stock/return_report') ?>'">
                                                <i class="fas fa-undo"></i> Clear
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($error_message)): ?>
                <!-- Error Message -->
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Error:</strong> <?= esc($error_message) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($is_search && !empty($resultlist)): ?>

           
                <!-- Export Buttons -->
                <div class="row">
                    <div class="col-12 mb-3">
                        <button type="button" class="btn btn-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                        <button type="button" class="btn btn-danger" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>
                </div>

                <!-- Detailed Results Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Detailed Report (<?= count($resultlist) ?> records)</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="reportTable" class="table table-bordered table-striped table-hover nowrap" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Return Ref</th>
                                                <th>Return Date</th>
                                                <th>Item Code</th>
                                                <th>Item Description</th>
                                                <th>Quantity</th>
                                                <th>Unit</th>
                                                <th>Unit Cost</th>
                                                <th>Total Value</th>
                                                <th>Reason</th>
                                                <th>Notes</th>
                                                <th>Status</th>
                                                <th>Created By</th>
                                                <th>Created At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $serial = 1; foreach ($resultlist as $row): ?>
                                            <tr class="
                                                <?php if ($row['status'] === 'pending'): ?>table-warning
                                                <?php elseif ($row['status'] === 'cancelled'): ?>table-danger
                                                <?php endif; ?>
                                            ">
                                                <td><?= $serial++ ?></td>
                                                <td><strong><?= esc($row['return_ref']) ?></strong></td>
                                                <td><?= date('Y-m-d', strtotime($row['return_date'])) ?></td>
                                                <td><?= esc($row['stock_card']) ?></td>
                                                <td><?= esc($row['item_description'] ?? 'N/A') ?></td>
                                                <td class="text-end"><?= number_format($row['quantity'], 2) ?></td>
                                                <td><?= esc($row['unit_of_issue'] ?? '') ?></td>
                                                <td class="text-end">Rs. <?= number_format($row['unit_cost'], 2) ?></td>
                                                <td class="text-end"><strong>Rs. <?= number_format($row['total_value'], 2) ?></strong></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= esc($row['reason']) ?></span>
                                                </td>
                                                <td><?= esc($row['notes'] ?? '-') ?></td>
                                                <td>
                                                    <?php if ($row['status'] === 'processed'): ?>
                                                        <span class="badge bg-success">Processed</span>
                                                    <?php elseif ($row['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php elseif ($row['status'] === 'cancelled'): ?>
                                                        <span class="badge bg-danger">Cancelled</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= esc($row['created_by'] ?? '-') ?></td>
                                                <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-primary">
                                                <th colspan="5" class="text-end">Total:</th>
                                                <th class="text-end"><?= number_format($summary['total_quantity'], 2) ?></th>
                                                <th></th>
                                                <th></th>
                                                <th class="text-end"><strong>Rs. <?= number_format($summary['total_value'], 2) ?></strong></th>
                                                <th colspan="5"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php elseif ($is_search && empty($resultlist)): ?>
                <!-- No Results Message -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No stock returns found matching your criteria</h5>
                                <p class="text-muted">Try adjusting your filter parameters</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Initial State Message -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Select filter criteria and click "Generate Report"</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <?= view('layouts/scripts') ?>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for item dropdown
            $('#item_keyword').select2({
                placeholder: '-- All Items --',
                allowClear: true,
                width: '100%'
            });

            <?php if ($is_search && !empty($resultlist)): ?>
            // Initialize DataTable
            $('#reportTable').DataTable({
                dom: 'Bfrtip',
                buttons: [],
                pageLength: 25,
                order: [[2, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [10] } // Notes column
                ]
            });
            <?php endif; ?>
        });

        // Export to Excel function
        function exportToExcel() {
            var table = document.getElementById('reportTable');
            var wb = XLSX.utils.table_to_book(table, {sheet: "Stock Returns"});

            // Generate filename with current date
            var today = new Date();
            var filename = 'Stock_Return_Report_' + today.toISOString().slice(0, 10) + '.xlsx';

            XLSX.writeFile(wb, filename);

            toastr.success('Report exported successfully!');
        }

        // Print styles
        window.addEventListener('beforeprint', function() {
            document.querySelector('.main-content').style.marginLeft = '0';
            document.querySelector('.sidebar').style.display = 'none';
            document.querySelector('.navbar-header').style.display = 'none';
        });

        window.addEventListener('afterprint', function() {
            document.querySelector('.main-content').style.marginLeft = '';
            document.querySelector('.sidebar').style.display = '';
            document.querySelector('.navbar-header').style.display = '';
        });
    </script>

    <!-- XLSX Library for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

</body>
</html>
