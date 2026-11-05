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
                                        <h4 class="p-title">Operational PNL</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form id="date_form" accept-charset="utf-8" action="javascript:void(0)">
                                                    <div class="row">
                                                        <!-- <div class="mb-3 col-md-4">
                                                            <label class="form-label" for="customer_name">Supplier</label>
                                                            <select name="customer_name" id="customer_name" class="js-example-basic-single form-control" style="width: 100%;">
                                                                <option value="">Select a Supplier...</option>
                                                                <?php
                                                                //foreach ($supplier as $customer) {
                                                                //$selected = '';
                                                                //echo '<option value="' . $customer['Sup_Acc_No'] . '"' . $selected . '>' . $customer['Name'] . '</option>';
                                                                //}
                                                                ?>
                                                            </select>
                                                        </div> -->
                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label" for="drange1">Start Date</label>
                                                            <input class="form-control" type="text" id="drange1" name="drange1"  value="<?= isset($date1) ? htmlspecialchars($date1) : '' ?>"   data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                                        </div>
                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label" for="drange2">End Date</label>
                                                            <input class="form-control" type="text" id="drange2" name="drange2" value="<?= isset($date2) ? htmlspecialchars($date2) : '' ?>" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" style="padding-top: 10px; float:right;">
                                                        <button type="submit" class="btn btn-success w-100px me-5px"><i class="bi bi-search"></i>Search
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                if (isset($payments) && !empty($payments) && $is_search) {
                                    ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <!-- Add Print Button -->
                                                    <!-- <div class="d-flex justify-content-end">
                                                        <button class="btn btn-dark mb-3" onclick="printTable()"><i class="bi bi-printer"></i></button>
                                                    </div> -->
                                                    <!-- <button class="btn btn-primary mb-3" onclick="printTable()"><i class="bi bi-printer"></i></button> -->

                                                    <div class="table-responsive" id="printableTable">
                                                        <table id="mainTable" class="table table-hover">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Tour ID</th>
                                                                    <th>Manual ID</th>
                                                                    <th>Date</th>
                                                                    <th>Tour Name</th>
                                                                    <th>Tour Agent</th>
                                                                    <th>Expenses</th>
                                                                    <th>Income</th>
                                                                    <th>Profit</th>
                                                                    <!-- <th>View Vouchers</th> -->
                                                                    <th class="hide-print"></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $x = 1;
                                                                $tot_income = 0;
                                                                $tot_expenses = 0;
                                                                $tot_profit = 0;
                                                                foreach ($payments as $litem) {

                                                                    

                                                                    $income = isset($litem['income']) ? (float)$litem['income'] : 0;
                                                                    $expenses = isset($litem['expenses']) ? (float)$litem['expenses'] : 0;

                                                                    $tot_income += $income;
                                                                    $tot_expenses += $expenses;
                                                                    $tot_profit += $income - $expenses;

                                                                    if (empty($income) && empty($expenses)) {
                                                                        continue; // Skip this iteration if both income and expenses are empty
                                                                        
                                                                    };


                                                                    ?>
                                                                    <tr style="width: 100%;">
                                                                        <td><?php echo $x ?></td>
                                                                        <td><?php echo $litem['index_no']; ?></td>
                                                                        <td><?php echo $litem['manual_id']; ?></td>
                                                                        <td><?php echo $litem['create_time'] ?></td>
                                                                        <td><?php echo $litem['consignee'] ?></td>
                                                                        <td><?php echo $litem['agent_name'] ?></td>
                                                                        <td><?php echo $expenses ?></td>
                                                                        <td><?php echo $income?></td>
                                                                        <td><?php echo number_format($income - $expenses, 2) ?></td>
                                                                        <td>
                                                                            <a href="<?= base_url('accounts/creditor_invoice/view_operational_expences?tour_id=' . $litem['index_no']) ?>" class="btn btn-primary hide-print" target="_blank">
                                                                                <i class="bi bi-eye"></i>
                                                                            </a>
                                                                        </td>
                                                                    </tr>

                                                                    <?php
                                                                    $x++;
                                                                }
                                                                ?>


                                                            </tbody>
                                                            <tfoot>
                                                                <tr>
                                                                    <td colspan="6" style="text-align: left;"><b>Total </b></td>
                                                                    <td><b><?php echo number_format($tot_expenses, 2); ?></b></td>
                                                                    <td><b><?php echo number_format($tot_income, 2); ?></b></td>
                                                                    <td><b><?php echo number_format($tot_profit, 2); ?></b></td>
                                                                </tr>
                                                            </tfoot>
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

    <?php if (!isset($date1) && !isset($date2)) : ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();

        // First day of the month
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        // Last day of the month
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

        // Set values to inputs
        document.getElementById('drange1').value = firstDay.toISOString().slice(0, 10);
        document.getElementById('drange2').value = lastDay.toISOString().slice(0, 10);
    });
</script>
<?php endif; ?>


<script>
    $('.js-example-basic-single').select2({
        placeholder: " Select",
        allowClear: true
    });


        // document.addEventListener('DOMContentLoaded', function() {
        //     // Get today's date
        //     const today = new Date();

        //     // Calculate the first and last date of the current month
        //     const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        //     const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

        //     // daterange picker
        //     $('#drange1').val(firstDay.toISOString().slice(0, 10));
        //     $('#drange2').val(lastDay.toISOString().slice(0, 10));
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

        var report_title = 'Operational PNL';

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

                        // Include table footer in print
                        $(win.document.body).find('table').append($('tfoot').clone()); // Append the footer

                        $(win.document.body).find('.hide-print').hide();
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

        // function printTable() {
        //     var printContent = document.getElementById('printableTable').innerHTML;
        //     var originalContent = document.body.innerHTML;

        //     document.body.innerHTML = printContent;
        //     window.print();
        //     document.body.innerHTML = originalContent;

        //     // Restore event listeners
        //     window.location.reload();
        // }
    </script>



</body>

</html>