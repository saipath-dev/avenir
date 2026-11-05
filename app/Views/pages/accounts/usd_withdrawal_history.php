<?= view('layouts/head') ?>

<body>

    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col">
                            <div class="h-100">

                                <div class="row mb-3">
                                    <div class="col-md-10 d-flex">
                                        <h4 class="p-title">USD Withdrawal History</h4>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <a href="<?= base_url('accounts/usd_withdrawal') ?>" class="btn btn-success btn-sm">+ New Withdrawal</a>
                                    </div>
                                </div>

                                <!-- Filter -->
                                <div class="row clearfix mb-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-4">
                                                <form action="<?= base_url('accounts/usd_withdrawal/history') ?>" method="GET">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label class="form-label">From</label>
                                                                <input type="text" class="form-control"
                                                                    data-provide="datepicker"
                                                                    data-date-autoclose="true"
                                                                    data-date-format="yyyy/mm/dd"
                                                                    name="from" value="<?= esc($from ?? '') ?>" placeholder="From date" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label class="form-label">To</label>
                                                                <input type="text" class="form-control"
                                                                    data-provide="datepicker"
                                                                    data-date-autoclose="true"
                                                                    data-date-format="yyyy/mm/dd"
                                                                    name="to" value="<?= esc($to ?? '') ?>" placeholder="To date" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4 d-flex align-items-end gap-2">
                                                            <button type="submit" class="btn btn-primary">Search</button>
                                                            <a href="<?= base_url('accounts/usd_withdrawal/history') ?>" class="btn btn-default">Clear</a>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Results -->
                                <?php if ($is_search): ?>
                                <div class="row clearfix">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-4">
                                                <?php if (!empty($records)): ?>
                                                <table class="table table-striped table-bordered" id="history_table">
                                                    <thead class="table-dark">
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>USD Account</th>
                                                            <th>Description</th>
                                                            <th class="text-end">USD Amount</th>
                                                            <th class="text-end">Exchange Rate</th>
                                                            <th class="text-end">LKR Amount</th>
                                                            <th>Created By</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $total_usd = 0;
                                                        $total_lkr = 0;
                                                        foreach ($records as $rec):
                                                            $lkr = $rec['Cr'] * $rec['dollarrate_cr'];
                                                            $total_usd += $rec['Cr'];
                                                            $total_lkr += $lkr;
                                                        ?>
                                                        <tr>
                                                            <td><?= esc($rec['Date']) ?></td>
                                                            <td><?= esc($rec['account_name'] ?? $rec['L_Code']) ?></td>
                                                            <td><?= esc($rec['Discription']) ?></td>
                                                            <td class="text-end"><?= number_format($rec['Cr'], 2) ?></td>
                                                            <td class="text-end"><?= number_format($rec['dollarrate_cr'], 2) ?></td>
                                                            <td class="text-end"><?= number_format($lkr, 2) ?></td>
                                                            <td><?= esc($rec['Cashier']) ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="table-secondary fw-bold">
                                                            <td colspan="3" class="text-end">Total</td>
                                                            <td class="text-end"><?= number_format($total_usd, 2) ?></td>
                                                            <td></td>
                                                            <td class="text-end"><?= number_format($total_lkr, 2) ?></td>
                                                            <td></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                                <?php else: ?>
                                                <div class="alert alert-warning">No withdrawal records found.</div>
                                                <?php endif; ?>
                                            </div>
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
    <script src="<?= base_url() ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="<?= base_url() ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
    <script src="<?= base_url() ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url() ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
    <script src="<?= base_url() ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>

    <script>
        <?php if ($is_search && !empty($records)): ?>
        $('#history_table').DataTable({
            "paging": false,
            "info": false,
            "searching": false,
            "ordering": true,
            "scrollX": true,
            "dom": 'Bfrtip',
            "buttons": [{
                extend: 'csv',
                text: '<i class="bi bi-file-earmark-spreadsheet-fill"></i> Export to CSV',
                className: 'btn btn-primary border-0 btn-sm'
            }]
        });
        <?php endif; ?>
    </script>

</body>
</html>
