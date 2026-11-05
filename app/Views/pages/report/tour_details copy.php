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
                                        <h4 class="p-title">Voucher Report</h4>
                                    </div>
                                </div>



                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form id="destination_form" accept-charset="utf-8">
                                                    <div class="row">

                                                        <div class="col-md-5">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="drange1">Start Date</label>
                                                                <input class="form-control txt_nav date" type="text" data-provide="datepicker" data-date-autoclose="true" data-date-format="yyyy-mm-dd" id="drange1" name="drange1" placeholder="Select Date" value="<?php if (!empty($date1)) {
                                                                                                                                                                                                                                                                            echo $date1;
                                                                                                                                                                                                                                                                        } else {
                                                                                                                                                                                                                                                                            echo '';
                                                                                                                                                                                                                                                                        } ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="drange2">End Date</label>
                                                                <input class="form-control txt_nav default-date" type="text" data-provide="datepicker" data-date-autoclose="true" data-date-format="yyyy-mm-dd" id="drange2" name="drange2" placeholder="Select Date" value="<?php if (!empty($date2)) {
                                                                                                                                                                                                                                                                                    echo $date2;
                                                                                                                                                                                                                                                                                } else {
                                                                                                                                                                                                                                                                                    echo '';
                                                                                                                                                                                                                                                                                } ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2" style="padding-top: 10px; float:right;">
                                                            <br>
                                                            <button type="submit" class="btn btn-success w-100px me-5px"><i class="bi bi-search"></i>Search
                                                            </button>
                                                        </div>

                                                    </div>


                                                </form>



                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label" for="drange2">Voucher No</label>
                                                            <!-- <input type="text" name="v_no" id="v_no"> -->
                                                            <input class="form-control" type="text" id="v_no" name="v_no" />
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6"> <br><label></label><button class="btn btn-success w-100px me-5px" id="v_buton"><i class="bi bi-search"></i>Search Voucher
                                                        </button></div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>



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
                                                                <th>Manual Tour ID</th>
                                                                <th>Tour Name</th>
                                                                <th>Tour Agent</th>
                                                                <th>Total Amount</th>
                                                                <th>Voucher Count</th>
                                                                <th>Date</th>
                                                                <th>View Vouchers</th>
                                                                <th>View Tour</th>
                                                                <!-- <th>View</th> -->
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $x = 1;
                                                            foreach ($payments as $litem) {

                                                                if ($litem['W_Date'] != 0) {
                                                            ?>
                                                                    <tr style="width: 100%;">
                                                                        <td><?php echo $x ?></td>
                                                                        <td><?php echo $litem['C_Number'] ?></td>
                                                                        <td><?php echo $litem['manual_id'] ?></td>
                                                                        <td><?php echo $litem['consignee'] ?></td>
                                                                        <td><?php echo $litem['agent_name'] ?></td>
                                                                        <td><?php echo $litem['tot_amount'] ?></td>
                                                                        <td><?php echo $litem['v_count'] ?></td>
                                                                        <td><?php echo $litem['W_Date'] ?></td>
                                                                        <td>
                                                                            <?php if (!empty($date1)) {
                                                                                $date1 = $date1;
                                                                            } else {
                                                                                $date1 = "";
                                                                            }

                                                                            if (!empty($date2)) {
                                                                                $date2 = $date2;
                                                                            } else {
                                                                                $date2 = "";
                                                                            }


                                                                            ?>
                                                                            <a href="<?= base_url('report/voucher_report/voucher_details?tour_id=' . $litem['C_Number']) . '&startdate=' . $date1 . '&enddate=' . $date2 ?>" class="btn btn-primary" target="_blank">
                                                                                <i class="bi bi-eye-fill"></i>
                                                                            </a>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">

                                                                                <a href="<?= base_url('tour/tour_dashboard/monthly_dashboard?tour_id=' . $litem['C_Number']); ?>" class="btn btn-soft-success btn-sm" target="_blank" data-toggle="tooltip" title="Itinerary Print"><i class="bi bi-eye-fill"></i></a>

                                                                            </div>
                                                                        </td>


                                                                    </tr>
                                                            <?php
                                                                    $x++;
                                                                }
                                                            }
                                                            ?>
                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <th colspan="5">Other</th>
                                                                <!-- <th align="canter">Other</th> -->
                                                                <th><?php echo $other_vcount['g_tot']; ?></th>
                                                                <th><?php echo $other_vcount['other_count']; ?></th>
                                                                <th></th>

                                                                <!-- <th align="canter">Other</th> -->
                                                                <th>
                                                                    <a href="<?= base_url('report/voucher_report/voucher_details?tour_id=other' . '&startdate=' . $date1 . '&enddate=' . $date2) ?>" class="btn btn-primary" target="_blank">
                                                                        <i class="bi bi-eye-fill"></i>
                                                                    </a>
                                                                </th>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

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


        $("#destination_form").on('submit', function(e) {
            e.preventDefault();

            // var supplier = $("#supplier").val();
            var drange1 = $("#drange1").val();
            var drange2 = $("#drange2").val();

            window.location.href = "<?php echo base_url(); ?>report/voucher_report?startdate=" + drange1 + "&enddate=" + drange2;




        });

        // $("#v_buton")
        $(document).on('click', '#v_buton', function() {

            var v_no = $("#v_no").val();
            if (v_no) {
                window.location.href = "<?php echo base_url(); ?>report/voucher_report/voucher_details?v_no=" + v_no;
                return false;
            }
        });



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