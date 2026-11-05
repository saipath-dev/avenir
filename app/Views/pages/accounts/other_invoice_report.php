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
                                <div class="row">
                                    <div class="col-md-10 d-flex">
                                        <h4 class="p-title">Service Provider Invoice Report</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-3">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label" for="drange1">Start Date</label>
                                                            <input class="form-control txt_nav date" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" data-date-autoclose="true" id="drange1" name="drange1" placeholder="Select Date" value="<?= isset($date1) ? $date1 : date('Y-m-d') ?>" />
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label" for="drange2">End Date</label>
                                                            <input class="form-control txt_nav date" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" data-date-autoclose="true" id="drange2" name="drange2" placeholder="Select Date" value="<?= isset($date2) ? $date2 : date('Y-m-d') ?>" />
                                                        </div>
                                                    </div>

                                                    <div class="col-md-12 d-flex">
                                                        <button class="btn btn-success px-3 filter me-2">
                                                            Filter
                                                        </button>
                                                        <button class="btn btn-default px-3 reset">
                                                            Reset
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($is_search) { ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card">
                                                <div class="body p-3">
                                                    <div class="table-responsive">
                                                        <table id="mainTable" class="table table-hover">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Invoice No</th>
                                                                    <th>Date</th>
                                                                    <th>Income Type</th>
                                                                    <th>Description</th>
                                                                    <th class="text-end">Amount (JPY)</th>
                                                                    <th class="text-end">Amount (LKR)</th>
                                                                    <th>Invoice Type</th>
                                                                    <th class="text-center">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $row_count = 1;
                                                                $total_amount = 0;

                                                                if (isset($records) && !empty($records)) {
                                                                    foreach ($records as $record) {
                                                                        $total_amount += $record['amount_lkr'];
                                                                ?>
                                                                        <tr>
                                                                            <td><?= $row_count++ ?>.</td>
                                                                            <td><?= $record['invoice_no'] ?></td>
                                                                            <td><?= $record['date'] ?></td>
                                                                            <td><?= $record['income_type_name'] ?? 'N/A' ?></td>
                                                                            <td style="white-space: pre-line;"><?= htmlspecialchars($record['description']) ?></td>
                                                                            <td class="text-end"><?= number_format($record['amount_jpy'] ?? 0, 2) ?></td>
                                                                            <td class="text-end"><?= number_format($record['amount_lkr'], 2) ?></td>
                                                                            <td><?= $record['is_staff_invoice'] == 1 ? 'Staff' : 'Normal' ?></td>
                                                                            <td class="text-center">
                                                                                <button class="btn btn-sm btn-primary btn-print" data-inv="<?= $record['invoice_no'] ?>">
                                                                                    <i class="bi bi-printer"></i> Print
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                <?php
                                                                    }
                                                                } else {
                                                                ?>
                                                                    <tr>
                                                                        <td colspan="8" class="text-center">No records found for the selected date range.</td>
                                                                    </tr>
                                                                <?php } ?>
                                                            </tbody>
                                                            <?php if (isset($records) && !empty($records)) { ?>
                                                                <tfoot>
                                                                    <tr>
                                                                        <td colspan="6" class="text-end"><strong>Total:</strong></td>
                                                                        <td class="text-end"><strong><?= number_format($total_amount, 2) ?></strong></td>
                                                                        <td colspan="2"></td>
                                                                    </tr>
                                                                </tfoot>
                                                            <?php } ?>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>

                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?= view('layouts/brand') ?>

        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/scripts') ?>
    <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>

    <script>
        var report_title = 'Other Invoice Report';

        $('#mainTable').DataTable({
            "aaSorting": [],
            rowReorder: {
                selector: 'td:nth-child(2)'
            },
            responsive: 'true',
            dom: "Bfrtip",
            buttons: [{
                    extend: 'csvHtml5',
                    text: 'CSV',
                    titleAttr: 'CSV',
                    title: report_title,
                    footer: true,
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Exclude action column
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    titleAttr: 'PDF',
                    title: report_title,
                    orientation: 'landscape',
                    pageSize: 'A4',
                    footer: true,
                    exportOptions: {
                        columns: ':not(:last-child)' // Exclude action column
                    }
                },
                {
                    extend: 'print',
                    text: 'Print',
                    titleAttr: 'Print',
                    title: report_title,
                    footer: true,
                    exportOptions: {
                        columns: ':not(:last-child)' // Exclude action column
                    },
                    customize: function(win) {
                        var style = '<style type="text/css" media="print">@page { size: landscape; }</style>';
                        $(win.document.body).append(style);
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

        $(document).ready(function() {
            // Filter button handler
            $('.filter').click(function() {
                var drange1 = $('#drange1').val();
                var drange2 = $('#drange2').val();

                if (drange1 != '' && drange2 != '') {
                    window.location.href = '<?= base_url('accounts/other_invoice/report') ?>?date1=' + drange1 + '&date2=' + drange2;
                } else {
                    alert('Please select both start and end dates.');
                }
            });

            // Reset button handler
            $('.reset').click(function() {
                window.location.href = '<?= base_url('accounts/other_invoice/report') ?>';
            });

            // Print button handler for individual invoices
            $(document).on('click', '.btn-print', function() {
                var inv = $(this).data('inv');
                window.open('<?= base_url('accounts/other_invoice/print') ?>?inv=' + inv, '_blank');
            });
        });
    </script>

</body>

</html>
