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
                                        <h4 class="p-title">Debtor Invoice Amendment</h4>
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
                                                    </div>
                                                    <div class="row">
                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label" for="tour_id">Tour Id </label>
                                                            <input type="text" class="form-control" id="tour_id" name="tour_id" />
                                                        </div>
                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label" for="inv">Invoice No</label>
                                                            <input class="form-control" type="text" id="inv" name="inv" />
                                                        </div>
                                                        <!-- <div class="mb-3 col-md-4">
                                                            <label class="form-label" for="drange2">End Date</label>
                                                            <input class="form-control" type="text" id="drange2" name="drange2" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                                        </div> -->
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
                                                    <div class="table-responsive">
                                                        <!-- <div class="mb-3">
                                                            <button type="button" class="btn btn-success btn-export"><i class="bi bi-file-earmark-excel"></i> Export</button>
                                                        </div> -->
                                                        <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Tour Id</th>
                                                                    <th>Details </th>
                                                                    <th>Invoiced Date</th>
                                                                    <th>Customer</th>
                                                                    <th>Invoice No</th>
                                                                    <th>Exchange Rate ($)</th>
                                                                    <th>$ Amount</th>
                                                                    <th class="text-end">LKR Amount</th>
                                                                    <th>Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $x = 1;
                                                                $dollar_total = 0;
                                                                $rs_total = 0;
                                                                foreach ($payments as $litem) {
                                                                    $total_cr = +$litem['credit_balance'];
                                                                    $st = $litem['requested_flag'];
                                                                    if ($st == "") {
                                                                        $st = "Invoice";
                                                                        $btn = "btn-success";
                                                                    } else if ($st == 5) {
                                                                        $st = "Amendment";
                                                                        $btn = "btn-warning";
                                                                    } else if ($st == 2) {
                                                                        $st = "Cancelled";
                                                                        $btn = "btn-danger";
                                                                    }

                                                                ?>
                                                                    <tr style="width: 100%;">
                                                                        <td><?php echo $x ?></td>
                                                                        <td><?php echo $litem['manual_id'] ?></td>
                                                                        <td><?php echo $litem['start_date'] . " - "  . $litem['consignee'] ?></td>
                                                                        <!-- <td><?php //echo $litem['abc']['Date'] 
                                                                                    ?></td> -->
                                                                        <td><?php echo $litem['date'] ?></td>
                                                                        <td><?php echo $litem['cus_name'] ?></td>
                                                                        <td><?php echo $litem['invoice_no'] ?></td>
                                                                        <td><?php echo $litem['doller_rate'] ?></td>
                                                                        <td><?php echo $litem['dollar_amount'] ?></td>
                                                                        <td class="text-end"><?php echo $total_cr ?></td>
                                                                        <td class="text-center"><button type="button" class="btn btn-sm <?php echo $btn; ?>"><?php echo $st; ?></button></td>
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
                                                                    <td colspan="6"></td>
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
            var tour_id = $("#tour_id").val();
            var inv = $("#inv").val();

            window.location.href = "<?php echo base_url(); ?>accounts/debtor_invoice/get_cancellation?startdate=" + date1 + "&enddate=" + date2 + "&tour_id=" + tour_id + "&inv=" + inv;
            return false;

        });
    </script>



</body>

</html>