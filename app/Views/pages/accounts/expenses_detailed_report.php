<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>

        <?= view('layouts/sidebar') ?>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col">

                            <div class="h-100">
                                <div class="row ">
                                    <div class="col-md-12 d-flex">
                                        <h4 class="p-title">Expenses Report</h4>
                                    </div>
                                </div>
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="d-flex flex-column">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="drange1">Start Date</label>
                                                                <input class="form-control txt_nav date" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" data-date-autoclose="true" id="drange1" name="drange1" placeholder="Select Date" value="<?= isset($fromDate) ? $fromDate : date('Y-m-d') ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="drange2">End Date</label>
                                                                <input class="form-control txt_nav date" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" data-date-autoclose="true" id="drange2" name="drange2" placeholder="Select Date" value="<?= isset($toDate) ? $toDate : date('Y-m-d') ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="account_filter">Expense Account</label>
                                                                <select class="form-control js-example-basic-single" id="account_filter" name="account_filter" style="width: 100%;">
                                                                    <option value="">All Accounts</option>
                                                                    <?php
                                                                    if (isset($expenseAccounts) && !empty($expenseAccounts)) {
                                                                        foreach ($expenseAccounts as $account) {
                                                                            $selected = (isset($selectedAccount) && $selectedAccount == $account['L_Code']) ? 'selected' : '';
                                                                            echo '<option value="' . $account['L_Code'] . '" ' . $selected . '>' . $account['L_Code'] . ' - ' . $account['PName'] . '</option>';
                                                                        }
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <div class="mb-3">
                                                                <button class="btn btn-success search">
                                                                    <i class="fa fa-search"></i> Search
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (isset($expenses['records']) && !empty($expenses['records'])): ?>
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table id="expensesTable" class="table table-striped table-bordered table-hover">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Date</th>
                                                                <th>L_Code</th>
                                                                <th>Expense Account</th>
                                                                <th>Description</th>
                                                                <th>DR</th>
                                                                <th>CR</th>
                                                                <th>Balance</th>
                                                                <th>Running Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $counter = 1;
                                                            foreach ($expenses['records'] as $record):
                                                            ?>
                                                            <tr>
                                                                <td><?= $counter++ ?></td>
                                                                <td><?= $record['Date'] ?></td>
                                                                <td><?= $record['L_Code'] ?></td>
                                                                <td><?= $record['ExpenseAccount'] ?></td>
                                                                <td><?= $record['Discription'] ?></td>
                                                                <td><?= number_format((float)$record['Dr'], 2) ?></td>
                                                                <td><?= number_format((float)$record['Cr'], 2) ?></td>
                                                                <td><?= number_format((float)$record['Balance'], 2) ?></td>
                                                                <td><?= number_format((float)$record['RunningTotal'], 2) ?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <td colspan="8" style="text-align: right;"><strong>Total Balance:</strong></td>
                                                                <td><strong><?= number_format((float)$expenses['totalBalance'], 2) ?></strong></td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="alert alert-warning" role="alert">
                                            <strong>Warning!</strong> No expense data found for the given date range.
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                            </div> <!-- end .h-100-->
                            <div id="map" class="rounded d-none"></div>

                        </div> <!-- end col -->
                    </div>

                </div>
                <!-- container-fluid -->

            </div>
            <!-- End Page-content -->

            <?= view('layouts/brand') ?>

        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <!-- DataTables Scripts -->
    <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>

    <script>
        // Initialize Select2 for account filter dropdown
        $('.js-example-basic-single').select2({
            placeholder: "Select an account",
            allowClear: true
        });

        // Search button click event
        $('.search').click(function() {
            var fromDate = $('#drange1').val();
            var toDate = $('#drange2').val();
            var account = $('#account_filter').val();

            var url = '<?= base_url('accounts/expenses_chart/report') ?>?fromDate=' + fromDate + '&toDate=' + toDate;

            if (account && account !== '') {
                url += '&account=' + account;
            }

            window.location.href = url;
        });

        <?php if (isset($expenses['records']) && !empty($expenses['records'])): ?>
        // Initialize DataTable
        var report_title = 'Expenses Report';

        $('#expensesTable').DataTable({
            "aaSorting": [],
            rowReorder: {
                selector: 'td:nth-child(2)'
            },
            responsive: true,
            dom: "Bfrtip",
            buttons: [
                {
                    extend: 'csvHtml5',
                    text: 'CSV',
                    titleAttr: 'CSV',
                    title: report_title,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    titleAttr: 'PDF',
                    title: report_title,
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: ':not(:hidden)'
                    }
                },
                {
                    extend: 'print',
                    text: 'Print',
                    titleAttr: 'Print',
                    title: report_title,
                    exportOptions: {
                        columns: ':not(:hidden)'
                    },
                    customize: function(win) {
                        var style = '<style type="text/css" media="print">@page { size: landscape; }</style>';
                        $(win.document.body).append(style);
                        $(win.document.body).find('table').append($('tfoot').clone());
                    }
                },
                {
                    extend: 'colvis',
                    text: 'Visibility',
                    titleAttr: 'Columns',
                    postfixButtons: ['colvisRestore']
                }
            ]
        });
        <?php endif; ?>
    </script>
</body>

</html>
