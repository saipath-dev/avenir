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
                                        <h4 class="p-title">Cancelled Voucher Details</h4>
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
                                                                <option value="">Select a Supplier...</option>
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
                                                                <input class="form-control" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" id="drange1" name="drange1" placeholder="Select Date" value="<?php if (!empty($selected_startdate)) {
                                                                    echo $selected_startdate;
                                                                    } else {
                                                                        echo '';
                                                                    } ?>" />

                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="drange2">End Date</label>
                                                                    <input class="form-control" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" id="drange2" name="drange2" placeholder="Select Date" value="<?php if (!empty($selected_enddate)) {
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
                                                                            <th>Amount</th>
                                                                            <th>Bank</th>
                                                                            <th>Account</th>
                                                                            <th>Status</th>
                                                                            <th>View</th>
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

                                                                            if ($value['Aflag'] == 1) {
                                                                                $st = '<span class="badge text-bg-success">Approved</span>';
                                                                            } else if ($value['Aflag'] == 2) {
                                                                        // $st = "Rejected";
                                                                                $st = '<span class="badge text-bg-warning">Rejected</span>';
                                                                            } else if ($value['Aflag'] == 3) {
                                                                        // $st = "Canceled";
                                                                                $st = '<span class="badge text-bg-danger">Canceled</span>';
                                                                            }



                                                                            ?>
                                                                            <tr>
                                                                                <td><?php echo $count; ?></td>

                                                                                <td><?php echo $acc_type; ?></td>
                                                                                <td><?php echo $value['W_Date']; ?></td>
                                                                                <td><?php echo $value['Vnum']; ?></td>

                                                                                <td><?php echo number_format($value['Amount'], 2); ?></td>
                                                                                <td><?php echo $value['Bank']; ?></td>
                                                                                <td><?php echo $value['Pay']; ?></td>
                                                                                <td><?php echo $st; ?></td>
                                                                                <td><a href="<?php echo base_url(); ?>accounts/voucher_print/<?php echo $value['Vnum']; ?>?tourid=<?php echo $value['C_Number']; ?>" class="btn btn-info "><i class="bi bi-search"></i></a></td>
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
                window.location.href = "<?php echo base_url(); ?>report/voucher_report/voucher_details?bank=" + supplier + "&startdate=" + drange1 + "&enddate=" + drange2;
            // }

        });
    </script>



</body>

</html>