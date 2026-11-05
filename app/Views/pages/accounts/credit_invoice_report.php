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
                                        <h4 class="p-title">Creditor Invoice Report</h4>
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
                                                            <input class="form-control" type="text" id="drange1" name="drange1" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                                        </div>
                                                        <div class="mb-3 col-md-6">
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
                                                    <div class="table-responsive">
                                                        <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Tour ID</th>
                                                                    <th>Tour Name</th>
                                                                    <th>Tour Agent</th>
                                                                    <!-- <th>View Vouchers</th> -->
                                                                    <th>View</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $x = 1;
                                                                foreach ($payments as $litem) {
                                                                ?>
                                                                    <tr style="width: 100%;">
                                                                        <td><?php echo $x ?></td>
                                                                        <td><?php echo $litem['index_no'] ?></td>
                                                                        <td><?php echo $litem['consignee'] ?></td>
                                                                        <td><?php echo $litem['agent_name'] ?></td>
                                                                        <!-- <td>
                                                                            <a href="<?= base_url('accounts/voucher_auth?tour_id=' . $litem['index_no']) ?>" class="btn btn-primary" target="_blank">
                                                                               <i class="bi bi-eye-fill"></i>
                                                                           </a>
                                                                       </td> -->

                                                                        <td>
                                                                            <a href="<?= base_url('accounts/creditor_invoice/view_expences?tour_id=' . $litem['index_no']) ?>" class="btn btn-primary" target="_blank">
                                                                                Expenses
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                <?php
                                                                    $x++;
                                                                }
                                                                ?>
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
                window.location.href = "<?php echo base_url(); ?>accounts/creditor_invoice/report?cus=" + cus;
                return false;
            }

            if (date1 == '' || date2 == '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Start Date and End Date are required!',
                });
            } else {
                window.location.href = "<?php echo base_url(); ?>accounts/creditor_invoice/report?startdate=" + date1 + "&enddate=" + date2;
            }

        });
    </script>



</body>

</html>