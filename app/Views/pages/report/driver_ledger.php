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
                                        <h4 class="p-title">Driver Ledger Report</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form id="driver_form" accept-charset="utf-8">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Driver</label>
                                                            <select name="driver" id="driver" class="js-example-basic-single form-control" style="width: 100%;">
                                                                <option value="">Select a Driver...</option>
                                                                <?php
                                                                $selected_driver = isset($selected_driver) ? $selected_driver : '';
                                                                foreach ($drivers as $driver) {
                                                                    if ($driver['Sup_Acc_No'] == $selected_driver) {
                                                                        echo '<option value="' . $driver['Sup_Acc_No'] . '" selected>' . $driver['Name'] . '</option>';
                                                                        // echo '<option value="' . $driver['Sup_Acc_No'] . '" selected>' . $driver['Name'] . ' (' . $driver['Sup_Acc_No'] . ')</option>';
                                                                    } else {
                                                                        echo '<option value="' . $driver['Sup_Acc_No'] . '">' . $driver['Name'] . '</option>';
                                                                        // echo '<option value="' . $driver['Sup_Acc_No'] . '">' . $driver['Name'] . ' (' . $driver['Sup_Acc_No'] . ')</option>';
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
                                                        <button type="submit" class="btn btn-success w-100px me-5px">Search
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (isset($driver_info) && !empty($driver_info)) { ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <h5>Driver Information</h5>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <strong>Name:</strong> <?php echo $driver_info['Name']; ?>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <strong>Account No:</strong> <?php echo $driver_info['Sup_Acc_No']; ?>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <strong>Mobile:</strong> <?php echo $driver_info['Mobile']; ?>
                                                        </div>
                                                         <div class="col-md-4">
                                                            <strong>Address:</strong> <?php echo $driver_info['Address']; ?>
                                                        </div>
                                                    </div>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>

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
                                                                    <th>ID</th>
                                                                    <th>Date</th>
                                                                    <th>Description</th>
                                                                    <th>Manual ID</th>
                                                                    <th>Debit</th>
                                                                    <th>Credit</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $index = 0;
                                                                foreach ($resultlist as $value) {
                                                                    $index++;
                                                                ?>
                                                                    <tr>
                                                                        <td><?php echo $index; ?></td>
                                                                        <td><?php echo $value['ID']; ?></td>
                                                                        <td><?php echo date('Y-m-d', strtotime($value['Date'])); ?></td>
                                                                        <td><?php echo $value['Discription']; ?></td>
                                                                        <td><?php echo $value['manual_id'] ?? '-'; ?></td>
                                                                        <td style="text-align: right;"><?php echo number_format($value['Dr'], 2); ?></td>
                                                                        <td style="text-align: right;"><?php echo number_format($value['Cr'], 2); ?></td>
                                                                    </tr>
                                                                <?php } ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } else { ?>
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
        // Initialize Select2 with search functionality
        $('#driver').select2({
            placeholder: "Select a Driver...",
            allowClear: true,
            width: '100%'
        });

        $("#driver_form").on('submit', function(e) {
            e.preventDefault();

            var driver = $("#driver").val();
            var drange1 = $("#drange1").val();
            var drange2 = $("#drange2").val();

            if (driver == '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Driver is required!',
                });
            } else {
                window.location.href = "<?php echo base_url(); ?>report/driver_ledger/driver_details?driver=" + driver + "&startdate=" + drange1 + "&enddate=" + drange2;
            }
        });
    </script>

</body>

</html>