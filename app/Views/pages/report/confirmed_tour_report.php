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
                                        <h4 class="p-title">Confirmed Tour Report</h4>
                                    </div>
                                </div>

                                <!-- Filter Section -->
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form id="filter_form" accept-charset="utf-8">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="keyword">Search (Tour ID / Manual ID / Name)</label>
                                                                <input class="form-control" type="text" id="keyword" name="keyword" placeholder="Search..." value="<?php if (!empty($keyword)) {
                                                                    echo $keyword;
                                                                } else {
                                                                    echo '';
                                                                } ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="drange1">Start Date</label>
                                                                <input class="form-control" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" id="drange1" name="drange1" placeholder="Select Date" value="<?php if (!empty($date1)) {
                                                                    echo $date1;
                                                                } else {
                                                                    echo '';
                                                                } ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="drange2">End Date</label>
                                                                <input class="form-control" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" id="drange2" name="drange2" placeholder="Select Date" value="<?php if (!empty($date2)) {
                                                                    echo $date2;
                                                                } else {
                                                                    echo '';
                                                                } ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <br>
                                                            <div class="mb-3" style="padding-top: 10px;">
                                                                <button type="submit" class="btn btn-success w-100px me-2">
                                                                    <i class="bi bi-search"></i> Search
                                                                </button>
                                                                <button type="button" class="btn btn-secondary w-100px" id="reset_btn">
                                                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Results Section -->
                                <?php
                                if (isset($resultlist) && !empty($resultlist) && $is_search) { ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <!-- Export Buttons -->
                                                    <div class="mb-3 d-flex justify-content-end">
                                                        <button type="button" class="btn btn-success me-2" id="export_excel_btn">
                                                            <i class="bi bi-file-earmark-excel"></i> Export to Excel
                                                        </button>
                                                        <button type="button" class="btn btn-danger" id="export_pdf_btn">
                                                            <i class="bi bi-file-earmark-pdf"></i> Export to PDF
                                                        </button>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Tour ID</th>
                                                                    <th>Manual ID</th>
                                                                    <th>Tour Name</th>
                                                                    <th>Start Date</th>
                                                                    <th>End Date</th>
                                                                    <th>Confirmation Status</th>
                                                                    <th>Finalize Status</th>
                                                                    <th>View Tour</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $count = 0;
                                                                foreach ($resultlist as $value) {
                                                                    $count++;

                                                                    // Confirmation Status Badge
                                                                    if ($value['confirm_status'] == 1) {
                                                                        $confirm_badge = '<span class="badge text-bg-success">Confirmed</span>';
                                                                    } else if ($value['confirm_status'] == 2) {
                                                                        $confirm_badge = '<span class="badge text-bg-warning">Partially Confirmed</span>';
                                                                    } else if ($value['confirm_status'] == 3) {
                                                                        $confirm_badge = '<span class="badge text-bg-danger">Cancelled</span>';
                                                                    } else if ($value['confirm_status'] == 4) {
                                                                        $confirm_badge = '<span class="badge text-bg-info">Status 4</span>';
                                                                    } else {
                                                                        $confirm_badge = '<span class="badge text-bg-secondary">Unknown</span>';
                                                                    }

                                                                    // Finalize Status Badge
                                                                    if ($value['finalize_status'] == 1) {
                                                                        $finalize_badge = '<span class="badge text-bg-primary">Finalized</span>';
                                                                    } else {
                                                                        $finalize_badge = '<span class="badge text-bg-secondary">Pending</span>';
                                                                    }
                                                                ?>
                                                                    <tr>
                                                                        <td><?php echo $count; ?></td>
                                                                        <td><?php echo $value['tour_id']; ?></td>
                                                                        <td><?php echo $value['manual_id'] ?? '-'; ?></td>
                                                                        <td><?php echo $value['tour_name']; ?></td>
                                                                        <td><?php echo date('Y-m-d', strtotime($value['start_date'])); ?></td>
                                                                        <td><?php echo date('Y-m-d', strtotime($value['end_date'])); ?></td>
                                                                        <td><?php echo $confirm_badge; ?></td>
                                                                        <td><?php echo $finalize_badge; ?></td>
                                                                        <td>
                                                                            <a href="<?php echo base_url('tour/tour_dashboard/monthly_dashboard?tour_id=' . $value['tour_id']); ?>" class="btn btn-info btn-sm" target="_blank">
                                                                                <i class="bi bi-eye-fill"></i> View Itinerary
                                                                            </a>
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
                                                    <strong>Warning!</strong> No confirmed tours found.
                                                </div>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <div class="row clearfix mt-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-info" role="alert">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Info:</strong> Please apply filters to view confirmed tours.
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
        $("#filter_form").on('submit', function(e) {
            e.preventDefault();

            var keyword = $("#keyword").val();
            var drange1 = $("#drange1").val();
            var drange2 = $("#drange2").val();

            window.location.href = "<?php echo base_url(); ?>report/confirmed_tours?keyword=" + encodeURIComponent(keyword) + "&startdate=" + drange1 + "&enddate=" + drange2;
        });

        $("#reset_btn").on('click', function() {
            window.location.href = "<?php echo base_url(); ?>report/confirmed_tours";
        });

        $("#export_excel_btn").on('click', function() {
            var keyword = "<?php echo isset($keyword) ? $keyword : ''; ?>";
            var drange1 = "<?php echo isset($date1) ? $date1 : ''; ?>";
            var drange2 = "<?php echo isset($date2) ? $date2 : ''; ?>";

            window.location.href = "<?php echo base_url(); ?>report/confirmed_tours/export_excel?keyword=" + encodeURIComponent(keyword) + "&startdate=" + drange1 + "&enddate=" + drange2;
        });

        $("#export_pdf_btn").on('click', function() {
            var keyword = "<?php echo isset($keyword) ? $keyword : ''; ?>";
            var drange1 = "<?php echo isset($date1) ? $date1 : ''; ?>";
            var drange2 = "<?php echo isset($date2) ? $date2 : ''; ?>";

            window.location.href = "<?php echo base_url(); ?>report/confirmed_tours/export_pdf?keyword=" + encodeURIComponent(keyword) + "&startdate=" + drange1 + "&enddate=" + drange2;
        });
    </script>

</body>

</html>
