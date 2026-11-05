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
                                        <h4 class="p-title">Cash In Hand</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="<?php echo base_url('accounts/cash_book') ?>" method="GET" accept-charset="utf-8">
                                                    <fieldset>
                                                        <!--         <div class="row clearfix">
                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label class="form-label">Invoice Number</label>
                                                    <input class="form-control txt_nav" class="form-control" id="invoice_num" name="invoice_num" value="<?php echo isset($invoice_number) ? $invoice_number : ""; ?>" />
                                                </div>
                                            </div>
                                        </div> -->


                                                        <div class="row clearfix">
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">From</label>
                                                                    <input class="form-control" data-provide="datepicker" data-provide="datepicker" data-date-format="yyyy-mm-dd" id="from" name="from" value="<?php echo isset($from) ? $from : ""; ?>" />
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">To</label>
                                                                    <input data-provide="datepicker" data-date-format="yyyy-mm-dd" class="form-control" id="to" name="to" value="<?php echo isset($to) ? $to : ""; ?>" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <br><br>
                                                        <button type="submit" class="btn btn-success  me-5px">Generate</button>
                                                        <button type="reset" class="btn btn-default reset">Cancel</button>
                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                if (isset($transactions) && !empty($transactions)) {
                                ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <div class="table-responsive">
                                                        <table id="mainTable" class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th style="width: 10%;" scope="col">#</th>
                                                                    <th style="width: 15%;" scope="col">Date</th>
                                                                    <th style="width: 15%;" scope="col">Invoice No.</th>
                                                                    <th scope="col">Description</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">DR</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">CR</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Balance</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $x = 1;
                                                                $daterange_total = 0;
                                                                foreach ($transactions as $transaction) {

                                                                    $bal = $transaction['Dr'] - $transaction['Cr'];
                                                                    $daterange_total +=  $bal;
                                                                ?>
                                                                    <tr>
                                                                        <td scope="row"><?= $x++; ?></td>
                                                                        <td><?= $transaction['Date']; ?></td>
                                                                        <td><?php echo $transaction['Invoice_Num'] ? $transaction['Invoice_Num'] : 'N/A'; ?></td>
                                                                        <td><?= $transaction['Discription']; ?></td>
                                                                        <td class="text-end"><?= $transaction['Dr']; ?></td>
                                                                        <td class="text-end"><?= $transaction['Cr']; ?></td>
                                                                        <td class="text-end"><?= $bal; ?></td>

                                                                    </tr>
                                                                <?php
                                                                    $x++;
                                                                } ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
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
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        // document.addEventListener("DOMContentLoaded", function() {
        //     flatpickr("#drange", {
        //         mode: "range",
        //         dateFormat: "Y-m-d"
        //     });
        // });

        $("#date_form").on('submit', function(e) {
            e.preventDefault();

            var date1 = $("#drange1").val();
            var date2 = $("#drange2").val();
            var cus = $("#customer_name").val();

            if (cus) {
                window.location.href = "<?php echo base_url(); ?>accounts/creditor_invoice/expenses_report?cus=" + cus;
                return false;
            }

            if (date1 == '' || date2 == '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Start Date and End Date are required!',
                });
            } else {
                window.location.href = "<?php echo base_url(); ?>accounts/creditor_invoice/expenses_report?startdate=" + date1 + "&enddate=" + date2;
            }

        });

        var report_title = 'Cash In Hand';

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
                    footer: true,
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
                    footer: true,
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
                    title: report_title,
                    footer: true,
                    postfixButtons: ['colvisRestore']
                },
            ]
        });
    </script>



</body>

</html>