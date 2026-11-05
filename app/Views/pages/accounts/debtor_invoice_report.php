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
                                        <h4 class="p-title">Debtor Invoice Report </h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form id="date_form" accept-charset="utf-8" action="javascript:void(0)">
                                                    <div class="row">
                                                        <div class="mb-3 col-md-4">
                                                            <label class="form-label" for="customer_name">Customer </label>
                                                            <select name="customer_name" id="customer_name" class="js-example-basic-single form-control" style="width: 100%;">
                                                                <option value="">Select a Customer...</option>
                                                                <?php
                                                                foreach ($customers as $customer) {
                                                                    $selected = '';
                                                                    echo '<option value="' . $customer['Sup_Acc_No'] . '"' . $selected . '>' . $customer['Name'] . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3 col-md-4">
                                                            <label class="form-label" for="drange1">Start Date</label>
                                                            <input class="form-control" type="text" id="drange1" name="drange1" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                                        </div>
                                                        <div class="mb-3 col-md-4">
                                                            <label class="form-label" for="drange2">End Date</label>
                                                            <input class="form-control" type="text" id="drange2" name="drange2" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
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
                                                    <div class="mb-3">
                                                        <?php if (!empty($date1) && !empty($date2) && !empty($cus)) { ?>
                                                            <button type="button" class="btn btn-primary" onclick="printReport('<?= $date1 ?>', '<?= $date2 ?>', '<?= $cus ?>')">
                                                                <i class="bi bi-printer"></i> Print
                                                            </button>
                                                        <?php } ?>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <!-- <div class="mb-3">
                                                            <button type="button" class="btn btn-success btn-export"><i class="bi bi-file-earmark-excel"></i> Export</button>
                                                        </div> -->
                                                        <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Details </th>
                                                                    <th>Invoiced Date</th>
                                                                    <th>Customer</th>
                                                                    <th>Invoice No</th>
                                                                    <th>Exchange Rate ($)</th>
                                                                    <th>Amount US$</th>
                                                                    <th class="text-end">LKR Amount</th>
                                                                    <th>Tour Cancellation</th>
                                                                    <th>Amendments</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $x = 1;
                                                                $dollar_total = 0;
                                                                $rs_total = 0;
                                                                foreach ($payments as $litem) {
                                                                    $total_cr = +$litem['credit_balance'];
                                                                ?>
                                                                    <tr style="width: 100%;">
                                                                        <td><?php echo $x ?></td>
                                                                        <td><?php echo $litem['start_date'] . " - " . $litem['manual_id'] . "  "  . $litem['consignee']?></td>
                                                                        <!-- <td><?php //echo $litem['abc']['Date'] 
                                                                                    ?></td> -->
                                                                        <td><?php echo $litem['date'] ?></td>
                                                                        <td><?php echo $litem['cus_name'] ?></td>
                                                                        <td><?php echo $litem['invoice_no'] ?></td>
                                                                        <td><?php echo $litem['doller_rate'] ?></td>
                                                                        <td><?php echo $litem['dollar_amount'] ?></td>
                                                                        <td class="text-end"><?php echo $total_cr ?></td>
                                                                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-cancel" data-inv="<?php echo $litem['invoice_no']; ?>" data-status="<?php echo $litem['cancel']; ?>" data-flag="2"><i class="bi bi-trash"></i></button></td>
                                                                        <td class="text-center"><button type="button" class="btn btn-sm btn-info btn-cancel" data-inv="<?php echo $litem['invoice_no']; ?>" data-status="<?php echo $litem['cancel']; ?>" data-flag="5"><i class="bi bi-trash"></i></button></td>

                                                                    </tr>
                                                                <?php
                                                                    $dollar_total += $litem['dollar_amount'];
                                                                    $rs_total += $litem['credit_balance'];
                                                                    $x++;
                                                                }
                                                                ?>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr>
                                                                    <td colspan="5"></td>
                                                                    <td>Total</td>
                                                                    <td><?php echo $dollar_total ?></td>
                                                                    <td class="text-end"><?php echo $rs_total ?></td>
                                                                    <td></td>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>
    <script src="https://twitter.github.io/typeahead.js/js/handlebars.js"></script>
    <script src="https://twitter.github.io/typeahead.js/releases/latest/typeahead.bundle.js"></script>
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
 

        $("#date_form").on('submit', function(e) {
            e.preventDefault();

            var date1 = $("#drange1").val();
            var date2 = $("#drange2").val();
            var cus = $("#customer_name").val();
 
            window.location.href = "<?php echo base_url(); ?>accounts/debtor_invoice/report?startdate=" + date1 + "&enddate=" + date2 + "&cus=" + cus;
            return false;

        });

        $(document).on('click', '.btn-cancel', function() {
            var invoice_no = $(this).data('inv');
            var status = $(this).data('status');
            var flag = $(this).data('flag');
            cancellation(invoice_no, status, flag);
        });

        function cancellation(invoice_no, status, flag) {
            var id = invoice_no;
            var status = status;
            var flag = flag;
            // alert(id);

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this Expenses!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST", // tf-e8-access-r1: was GET
                        url: "<?php echo base_url(); ?>accounts/debtor_invoice/cancellation_request",
                        data: {
                            id: id,
                            status: status,
                            type: flag
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: response.message,
                                });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong!',
                            });
                        }
                    });
                }
            });
        }

         

        var report_title = 'Debtor Invoice Report';
        $('.js-basic-example').DataTable({
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
                        columns: '1,6'
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

        function printReport(date1, date2, cus) {
            window.open("<?= base_url(); ?>accounts/debtor_invoice/print_deptor_invoice_report?startdate=" + date1 + "&enddate=" + date2 + "&cus=" + cus, '_blank');
        }

    </script>



</body>

</html>