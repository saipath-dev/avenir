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
                                        <h4 class="p-title">Voucher Cancellation Report</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form id="destination_form" accept-charset="utf-8">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Bank</label>
                                                            <select name="supplier" id="supplier" class="js-example-basic-single form-control" style="width: 100%;">
                                                                <option value="">Select a Bank...</option>
                                                                <?php
                                                                $selected_bank = isset($selected_bank) ? $selected_bank : 0;
                                                                foreach ($load_bank as $bank) {

                                                                    if ($bank['L_Code'] == $selected_bank) {
                                                                        echo '<option value="' . $bank['L_Code'] . '" selected>' . $bank['PName'] . '</option>';
                                                                    } else {
                                                                        echo '<option value="' . $bank['L_Code'] . '">' . $bank['PName'] . '</option>';
                                                                    }
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="drange1">Start Date</label>
                                                                <input class="form-control txt_nav date" type="text" data-provide="datepicker" data-date-autoclose="true" data-date-format="yyyy-mm-dd" id="drange1" name="drange1" placeholder="Select Date" value="<?php if (!empty($selected_startdate)) {
                                                                                                                                                                                                                                                                            echo $selected_startdate;
                                                                                                                                                                                                                                                                        } else {
                                                                                                                                                                                                                                                                            echo '';
                                                                                                                                                                                                                                                                        } ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="drange2">End Date</label>
                                                                <input class="form-control txt_nav default-date" type="text" data-provide="datepicker" data-date-autoclose="true" data-date-format="yyyy-mm-dd" id="drange2" name="drange2" placeholder="Select Date" value="<?php if (!empty($selected_enddate)) {
                                                                                                                                                                                                                                                                                    echo $selected_enddate;
                                                                                                                                                                                                                                                                                } else {
                                                                                                                                                                                                                                                                                    echo '';
                                                                                                                                                                                                                                                                                } ?>" />
                                                            </div>
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
                                if (isset($resultlist) && !empty($resultlist) && $is_search) { ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <div class="table-responsive">
                                                        <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Type</th>
                                                                    <th>Date</th>
                                                                    <th>Voucher No</th>
                                                                    <th>Refference</th>
                                                                    <th>Amount</th>
                                                                    <th>Bank</th>
                                                                    <th>Account</th>
                                                                    <th>Cancel</th>
                                                                    <!-- <th>Age(days)</th> -->
                                                                </tr>
                                                            </thead>
                                                            <tbody>

                                                                <?php
                                                                $count = 0;
                                                                $acc_type = '';

                                                                foreach ($resultlist as $value) {
                                                                    $count++;

                                                                    if ($value['sup'] == 1) {
                                                                        $acc_type = 'Supplier Account';
                                                                    } else {
                                                                        $acc_type = 'Voucher Payment';
                                                                    }



                                                                ?>
                                                                    <tr>
                                                                        <td><?php echo $count; ?></td>

                                                                        <td><?php echo $acc_type; ?></td>
                                                                        <td><?php echo $value['C_Date']; ?></td>
                                                                        <td><?php echo $value['Vnum']; ?></td>
                                                                        <td><?php echo $value['C_Number']; ?></td>

                                                                        <td><?php echo number_format($value['Amount'], 2); ?></td>
                                                                        <td><?php echo $value['Bank']; ?></td>
                                                                        <td><?php echo $value['Pay']; ?></td>
                                                                        <td><button class="btn btn-danger btn_cancel" value="<?php echo $value['Vnum'] ?>" data-sup="<?php echo $value['Pay'] ?>"><i class="bi bi-x-square-fill"></i></button></td>
                                                                        </td>



                                                                    </tr>
                                                                <?php
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
        $("#destination_form").on('submit', function(e) {
            e.preventDefault();

            var supplier = $("#supplier").val();
            var drange1 = $("#drange1").val();
            var drange2 = $("#drange2").val();

            // if (supplier == '') {
            //     Swal.fire({
            //         icon: 'error',
            //         title: 'Oops...',
            //         text: 'Destination is required!',
            //     });
            // } else {
            window.location.href = "<?php echo base_url(); ?>report/voucher_report/cancel?bank=" + supplier + "&startdate=" + drange1 + "&enddate=" + drange2;
            // }

        });


        /******************************** Remove ***************************/

        $(".btn_cancel").click(function() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to cancel this voucher!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {

                    var id = $(this).val();
                    var status = $(this).attr('data-status');
                    var supplier = $(this).attr('data-sup');

                    var menu = $("#menu").val();

                    $.post("<?php echo base_url() ?>report/voucher_report/update", {
                        update: "data",
                        id: id,
                        status: status,
                        supplier: supplier
                    }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Successfully Canceled!',
                                showConfirmButton: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    if (status == 1) {
                                        window.location.href = "<?php echo base_url() ?>accounts/voucher_print/" + data.v_inv;
                                    } else {
                                        location.reload();
                                    }

                                    // location.reload();
                                }
                            });
                        } else if ($.trim(data.status) === 400) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Error!',
                                text: 'Error occurred.',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Oops...',
                                text: 'Something went wrong!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    }, "json");
                }

            });
        });
    </script>


</body>

</html>