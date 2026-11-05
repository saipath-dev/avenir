<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <style>
            @media print {
                .hide-print {
                    display: none !important;
                }
            }
        </style>
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
                                        <h4 class="p-title">Bank Statement</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form id="date_form" accept-charset="utf-8" action="javascript:void(0)">
                                                    <div class="row">

                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label" for="drange1">Start Date</label>
                                                            <input class="form-control" type="text" id="drange1" name="drange1" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                                        </div>
                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label" for="drange2">End Date</label>
                                                            <input class="form-control" type="text" id="drange2" name="drange2" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                                        </div>
                                                        <?php if ($in_detail) { ?>
                                                            <div class="col-md-12">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="bank">Bank</label>
                                                                    <div id="the-chass">
                                                                        <select class="js-example-basic-single form-control txt_nav bank" id="bank" name="bank">
                                                                            <option value="">Select</option>
                                                                            <?php
                                                                            $selected = '';
                                                                            foreach ($banks as $cust) {
                                                                                if ($bank == $cust['L_Code']) {
                                                                                    $selected = 'selected';
                                                                                } else {
                                                                                    $selected = '';
                                                                                }
                                                                                echo '<option value="' . $cust['L_Code'] . '"' . $selected . '>' . $cust['PName'] . '</option>';
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                        <div class="col-md-12 d-flex">
                                                            <button class="btn btn-success px-3 filter me-2">
                                                                Filter
                                                            </button>
                                                            <button class="btn btn-default px-3 reset">
                                                                Reset
                                                            </button>
                                                        </div>

                                                    </div>

                                                </form>



                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($is_search) { ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <?php if ($in_detail) { ?>
                                                        <div class="table-responsive">
                                                            <table id="mainTable" class="table table-hover">
                                                                <thead class="thead-dark">
                                                                    <tr>
                                                                        <th>#</th>
                                                                        <th>Date</th>
                                                                        <th>Invoice No</th>
                                                                        <th>Voucher Number</th>
                                                                        <th>Description</th>
                                                                        <th style="width: 10%" class="text-end">Dr</th>
                                                                        <th style="width: 10%" class="text-end">Cr</th>
                                                                        <th style="width: 15%" class="text-end">Balance</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>

                                                                    <?php
                                                                    $op_bal = $opening_balance[0]['opening_bal'] ?? 0.00;
                                                                    ?>
                                                                    <!-- Opening Balance -->
                                                                    <tr>
                                                                        <td></td>
                                                                        <td></td>
                                                                        <td></td>
                                                                        <td></td>
                                                                        <td></td>
                                                                        <td></td>
                                                                        <td class="text-end">Opening Balance</td>
                                                                        <td class="text-end"><?= $op_bal; ?></td>
                                                                    </tr>

                                                                    <?php
                                                                    $row_count = 1;
                                                                    $totDr = $totCr = $totBal = 0;

                                                                    foreach ($records as $key => $value) {
                                                                        $dr = number_format($value['Dr'], 2, '.', '');
                                                                        $cr = number_format($value['Cr'], 2, '.', '');
                                                                    ?>
                                                                        <tr>
                                                                            <td><?php echo $row_count . '.'; ?></td>
                                                                            <td><?php echo $value['Date']; ?></td>
                                                                            <!-- <td><?php //echo $value['Chk_No']; 
                                                                                        ?></td>  -->
                                                                            <td><?php echo $value['Invoice_Num']; ?></td>
                                                                            <td><?php echo $value['Recpt_No']; ?></td>
                                                                            <td><?php echo $value['Discription']; ?></td>
                                                                            <td class="text-end"><?php echo $dr; ?></td>
                                                                            <td class="text-end"><?php echo $cr; ?></td>

                                                                            <?php
                                                                            $totDr += $dr;
                                                                            $totCr += $cr;
                                                                            $totBal = $op_bal + ($totDr - $totCr);
                                                                            ?>
                                                                            <td class="text-end"><?php echo number_format($totBal, 2, '.', ''); ?></td>

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
                                                                    <span class="fw-normal">Total DR</span>
                                                                    <span class="fw-bold"><?= number_format($totDr, 2) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="fw-normal">Total CR</span>
                                                                    <span class="fw-bold"><?= number_format($totCr, 2) ?></span>
                                                                </div>
                                                                <hr class="border-dark">
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="fw-normal">Balance</span>
                                                                    <span class="fw-bold"><?= number_format(($totBal  - $op_bal), 2) ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="table-responsive">
                                                            <table id="mainTable" class="table table-hover">
                                                                <thead class="thead-dark">
                                                                    <tr>
                                                                        <th>#</th>
                                                                        <th>Bank</th>
                                                                        <th style="width: 20%" class="text-end">Total Dr </th>
                                                                        <th style="width: 20%" class="text-end">Total Cr</th>
                                                                        <th style="width: 20%" class="text-end">Balance</th>
                                                                        <th style="width: 10%" class="text-center"></th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    $row_count = 1;
                                                                    foreach ($banks as $key => $bank) {
                                                                        $total_dr = $bank['total_dr'];
                                                                        $total_cr = $bank['total_cr'];
                                                                        $dr = number_format($total_dr, 2, '.', '');
                                                                        $cr = number_format($total_cr, 2, '.', '');
                                                                        $balance = $total_dr - $total_cr;
                                                                    ?>
                                                                        <tr>
                                                                            <td><?php echo $row_count . '.'; ?></td>
                                                                            <td class="fw-bold"><?php echo $bank['PName']; ?></td>
                                                                            <td class="text-end"><?php echo $dr; ?></td>
                                                                            <td class="text-end"><?php echo $cr; ?></td>
                                                                            <td class="text-end"><?php echo number_format($balance, 2, '.', ''); ?></td>
                                                                            <td class="text-center">
                                                                                <a class="btn btn-sm btn-success" href="<?= base_url('accounts/bank_statement?date1=' . $date1 . '&date2=' . $date2 . '&bank=' . $bank['L_Code']) ?>">
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
                                <?php } else {
                                ?>
                                    <?php if ($is_search) { ?>
                                        <div class="row clearfix mt-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-warning" role="alert">
                                                    <strong>Warning!</strong> No data found.
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
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
    <?= view('layouts/theme_settings') ?>
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
        var report_title = 'Bank Statement';

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
                var bank = $('#bank').val();
                var drange1 = $('#drange1').val();
                var drange2 = $('#drange2').val();

                var bank_text = '';
                if (bank != '' && bank != undefined) {
                    bank_text = '&bank=' + bank;
                }

                if (bank != '' || drange1 != '' || drange2 != '') {
                    window.location.href = '<?= base_url('accounts/bank_statement') ?>?date1=' + drange1 + '&date2=' + drange2 + bank_text;
                } else {
                    window.location.href = '<?= base_url('accounts/bank_statement') ?>';
                }
            });

            //. reset click
            $('.reset').click(function() {
                window.location.href = '<?= base_url('accounts/bank_statement') ?>';
            });
        });
    </script>


</body>

</html>