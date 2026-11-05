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
                                        <h4 class="p-title">Outstanding Report</h4>
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
                                                    <?php if ($in_detail) { ?>
                                                        <div class="table-responsive">
                                                            <table id="mainTable" class="table table-hover">
                                                                <thead class="thead-dark">
                                                                    <tr>
                                                                        <th>#</th>
                                                                        <th>Invoice No</th>
                                                                        <th>Tour ID</th>
                                                                        <!-- <th>Manual Tour ID</th> -->
                                                                        <th style="width: 10%" class="text-end">Debit</th>
                                                                        <th style="width: 10%" class="text-end">Credit</th>
                                                                        <th style="width: 15%" class="text-end">Balance</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>

                                                                    <?php
                                                                    $row_count = 1;
                                                                    $totDr = $totCr = $totBal = 0;

                                                                    foreach ($records as $key => $value) {
                                                                        $dr = number_format($value['dr'], 2, '.', '');
                                                                        $cr = number_format($value['cr'], 2, '.', '');
                                                                    ?>
                                                                        <tr>
                                                                            <td><?php echo $row_count . '.'; ?></td>
                                                                            <td><?php echo $value['Invoice_Num']; ?></td>
                                                                            <td><?php echo $value['index_no']; ?></td>
                                                                            <td class="text-end"><?php echo $dr; ?></td>
                                                                            <td class="text-end"><?php echo $cr; ?></td>


                                                                            <?php
                                                                            $totDr += $dr;
                                                                            $totCr += $cr;
                                                                            $totBal = ($totDr - $totCr);
                                                                            ?>
                                                                            <td class="text-end"><?php echo number_format($totBal, 2); ?></td>

                                                                        </tr>
                                                                    <?php
                                                                        $row_count++;
                                                                    }
                                                                    ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="alert alert-secondary bg-body-tertiary mt-3">
                                                            <div class="d-flex flex-column">
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="fw-normal">Total CR</span>
                                                                    <span class="fw-bold"><?= number_format($totCr, 2) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="fw-normal">Total DR</span>
                                                                    <span class="fw-bold"><?= number_format($totDr, 2) ?></span>
                                                                </div>
                                                                <hr class="border-dark">
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="fw-normal">Balance</span>
                                                                    <span class="fw-bold"><?= number_format($totBal, 2) ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="table-responsive">
                                                            <table id="mainTable" class="table table-hover">
                                                                <thead class="thead-dark">
                                                                    <tr>
                                                                        <th>#</th>
                                                                        <th>Customer</th>
                                                                        <th style="width: 20%" class="text-end">Debit</th>
                                                                        <th style="width: 20%" class="text-end">Credit</th>
                                                                        <th style="width: 20%" class="text-end">Balance</th>
                                                                        <th style="width: 10%" class="text-center"></th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    $row_count = 1;
                                                                    $totBal = 0;
                                                                    foreach ($records as $key => $value) {
                                                                        $dr = number_format($value['dr'], 2, '.', '');
                                                                        $cr = number_format($value['cr'], 2, '.', '');


                                                                        // $totDr += $dr;
                                                                        // $totCr += $cr;
                                                                        $bal = ($dr - $cr);
                                                                        $totBal += $bal;
                                                                    ?>
                                                                        <tr>
                                                                            <td><?= $row_count . '.'; ?></td>

                                                                            <td><?= $value['customerName']; ?></td>
                                                                            <td class="text-end"><?= number_format($value['dr'], 2); ?></td>
                                                                            <td class="text-end"><?= number_format($value['cr'], 2); ?></td>
                                                                            <td class="text-end"><?= number_format($bal, 2); ?></td>
                                                                            <td class="text-center">
                                                                                <a class="btn btn-sm btn-success" href="<?= base_url('accounts/outstanding_report?date1=' . $date1 . '&date2=' . $date2 . '&customer=' . $value['L_Code']) ?>">
                                                                                    <i class="bi bi-arrow-down-up"></i>
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    <?php
                                                                        $row_count++;
                                                                    }
                                                                    ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div> <!-- end .h-100-->

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
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        var report_title = 'Liability Report';

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
                    title: $('.download_label').html(),
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    titleAttr: 'PDF',
                    title: report_title,
                    orientation: 'landscape', // Set orientation to landscape
                    pageSize: 'A4', // Set page size to A4
                    exportOptions: {
                        columns: ':not(:hidden)' // Export all columns, including hidden ones
                    },
                    customize: function(win) {}
                },
                {
                    extend: 'print',
                    text: 'Print',
                    titleAttr: 'Print',
                    title: report_title,
                    exportOptions: {
                        columns: ':not(:hidden)' // Export all columns, including hidden ones
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
                    title: $('.download_label').html(),
                    postfixButtons: ['colvisRestore']
                },
            ]
        });

        $(document).ready(function() {
            // .filter click
            $('.filter').click(function() {
                // var supplier = $('#supplier').val();
                var drange1 = $('#drange1').val();
                var drange2 = $('#drange2').val();
                // var tour = $('#tour').val();





                if (drange1 != '' || drange2 != '') {
                    window.location.href = '<?= base_url('accounts/outstanding_report') ?>?date1=' + drange1 + '&date2=' + drange2;
                } else {
                    window.location.href = '<?= base_url('accounts/outstanding_report') ?>';
                }
            });

            //. reset click
            $('.reset').click(function() {
                window.location.href = '<?= base_url('accounts/outstanding_report') ?>';
            });
        });
    </script>



</body>

</html>