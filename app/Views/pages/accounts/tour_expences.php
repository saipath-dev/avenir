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
                                        <h4 class="p-title">Tour Expenses </h4>
                                    </div>
                                </div>

                                <?php
                                if (isset($expenses) && !empty($expenses)) {
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
                                                                    <th>Expenses Type</th>
                                                                    <th>Expenses Account</th>
                                                                    <th>Invoice No</th>
                                                                    <th>Date</th>
                                                                    <th>Amount</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $x = 1;
                                                                $totalAmount = 0;
                                                                foreach ($expenses as $litem) {
                                                                    $job = $litem['job'];

                                                                    if ($job == '4-8-106-10386') {
                                                                        $payment_type = 'Hotel';
                                                                    } else if ($job == '4-8-106-10385') {
                                                                        $payment_type = 'Transport';
                                                                    } else if ($job == '6-9-23-10640') {
                                                                        $payment_type = 'Entrance Fee';
                                                                    } else if ($job == '6-9-23-10641') {
                                                                        $payment_type = 'Other';
                                                                    } else {
                                                                        $payment_type = '';
                                                                    }
                                                                ?>
                                                                    <tr style="width: 100%;">
                                                                        <td><?php echo $x ?></td>
                                                                        <td><?php echo $payment_type ?></td>
                                                                        <td><?php echo ($litem['Name'] ?? $litem['name']) ?></td>
                                                                        <td><?php echo $litem['Invoice_Num'] ?></td>
                                                                        <td><?php echo $litem['Date'] ?></td>
                                                                        <td><?php echo $tot_amt = $litem['Cr'] ?></td>
                                                                    </tr>
                                                                    <?php
                                                                    $x++;
                                                                    $totalAmount += $tot_amt;
                                                                }
                                                                ?>
                                                                <tr>
                                                                    <td colspan="5" style="text-align: center;">Total</td>
                                                                    <td><?php echo number_format($totalAmount, 2); ?></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } else {
                                ?>
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