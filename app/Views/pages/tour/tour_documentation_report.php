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
                                        <h4 class="p-title">Tour Documentation</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="<?php echo base_url(); ?>tour/tour_dashboard/documentation/documentation_report" method="GET">
                                                    <div class="row clearfix">
                                                        <div class="col-sm-12">
                                                            <div class="form-group">
                                                                <label class="form-label">Keyword</label>
                                                                <input type="text" class="form-control" id="keyword" name="keyword" placeholder="Tour id, Tour Name, Tour Manual Id" />
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
                                if (!empty($tour_details)) {
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
                                                                    <th>Q. Id</th>
                                                                    <th>Q. Manual Id</th>
                                                                    <th>Tour Start & End Date</th>
                                                                    <th>Tour Name</th>
                                                                    <th>Tour Created By</th>
                                                                    <th>Status</th>
                                                                    <th>Finalize</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $i = 1;
                                                                foreach ($tour_details as $row) {
                                                                    $start_date = $row->start_date;
                                                                    // get $start_date year 
                                                                    $start_date_year = date('Y', strtotime($start_date));
                                                                    // get $start_date month
                                                                    $start_date_month = date('m', strtotime($start_date));
                                                                ?>
                                                                    <tr>
                                                                        <td><?= $i++; ?></td>
                                                                        <td><?= $row->tour_id; ?></td>
                                                                        <td><?= $row->manual_id; ?></td>
                                                                        <td><?= date('Y-m-d', strtotime($row->start_date)) . ' to ' . date('Y-m-d', strtotime($row->end_date)); ?></td>
                                                                        <td><?= $row->consignee; ?></td>
                                                                        <td><?= $row->create_user; ?></td>

                                                                        <td>
                                                                            <?php if ($row->Confirm == 1) { ?>
                                                                                <h5><code>Confirmed</code></h5>
                                                                            <?php } else if ($row->Confirm == 3) { ?>
                                                                                <h5><code>Tour Cancel</code></h5>
                                                                            <?php } else { ?>
                                                                                <h5><code>Not Confirmed</code></h5>
                                                                            <?php } ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php if ($row->Finalize == 1) { ?>
                                                                                <h5><code>Finalized</code></h5>
                                                                            <?php } else { ?>
                                                                                <h5><code>Not Finalized</code></h5>
                                                                            <?php } ?>
                                                                        </td>

                                                                        <td>
                                                                            <button type="button" class="btn btn-success btn-sm view" title="Tour Documentation" data-tour_id="<?= $row->tour_id; ?>" data-year="<?= $start_date_year; ?>" data-month="<?= $start_date_month; ?>" data-confirm="<?= $row->Confirm; ?>"><i class="bi bi-eye-fill"></i></button>
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
                                <?php } else { ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <div class="alert alert-danger" role="alert">
                                                        No Records Found!
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
        $(".view").on('click', function() {

            var tour = $(this).data('tour_id');
            var confirm = $(this).data('confirm');

            window.open('<?= base_url(); ?>tour/tour_dashboard/documentation/documentation_report/get_tour_documentation?t=' + tour, '_blank');

        });
    </script>



</body>

</html>