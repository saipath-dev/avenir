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
                                        <h4 class="p-title">Tour Main Dashboard</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="<?php echo base_url(); ?>tour/tour_dashboard" method="GET">
                                                    <?php
                                                    $currentYear = date("Y");
                                                    $selectedYear = isset($s_year) ? $s_year : $currentYear;

                                                    foreach ($tour_years as $year) {
                                                        $btnClass = ($year['year'] == $selectedYear) ? 'btn btn-success mr-2' : 'btn btn-primary mr-2';
                                                        echo '<button type="submit" name="year" value="' . $year['year'] . '" class="' . $btnClass . '">' . $year['year'] . '</button> &nbsp;&nbsp;';
                                                    }
                                                    ?>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                if (isset($tour_details) && !empty($tour_details)) {
                                ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-10">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <div class="table-responsive">
                                                        <!-- <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                            <tbody>
                                                                <tr onclick="load_tours(this)">
                                                                    <th class="text-secondary">Templates</th>
                                                                </tr>
                                                            </tbody>
                                                           
                                                        </table> -->

                                                        <!-- <br><br> -->

                                                        <table class="table table-sm  table-hover dataTable table-custom align-middle">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>Months</th>

                                                                </tr>
                                                            </thead>
                                                            <tbody>

                                                                <?php
                                                                foreach ($tour_details as $row) {
                                                                ?>
                                                                    <tr>
                                                                        <td>
                                                                            <button class="btn btn-sm btn-secondary w-50" data-year="<?= $selectedYear; ?>" data-month="<?= $row['month']; ?>" onclick="load_tour_details(this)">
                                                                                <div class="d-flex justify-content-between">
                                                                                    <span>
                                                                                        <?= str_pad($row['month'], 2, '0', STR_PAD_LEFT) . '-' . $row['month_name'] . ' &nbsp;&nbsp;'; ?>
                                                                                    </span>
                                                                                    <span>
                                                                                        <?= $selectedYear; ?>
                                                                                    </span>
                                                                                </div>
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php } ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php }
                                ?>
                                <!-- <div class="row clearfix mt-3"> -->
                                <div class="col-md-10">
                                    <div class="card p-4">
                                        <div class="body">
                                            <form action="<?php echo base_url(); ?>tour/tour_dashboard" method="GET">
                                                <div class="row clearfix">
                                                    <div class="col-sm-12">
                                                        <div class="form-group">
                                                            <label class="form-label">Tour Search</label>
                                                            <input type="text" class="form-control" id="keyword" name="keyword" placeholder="Search by Manual ID or Tour Name" />
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
                                <!-- </div> -->


                                <?php
                                if (!empty($tour_details_search)) {
                                ?>
                                    <div class="row clearfix mt-3" id="search_table">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <div class="table-responsive">
                                                        <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Manual Id</th>
                                                                    <th>Tour Start & End Date</th>
                                                                    <th>Tour Name</th>
                                                                    <th>Agent Name</th>
                                                                    <th>Pax Count</th>
                                                                    <th>Tour Created By</th>
                                                                    <th>Status</th>
                                                                    <th>Finalize</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $i = 1;
                                                                foreach ($tour_details_search as $row) {
                                                                    $start_date = $row->start_date;
                                                                    // get $start_date year 
                                                                    $start_date_year = date('Y', strtotime($start_date));
                                                                    // get $start_date month
                                                                    $start_date_month = date('m', strtotime($start_date));
                                                                ?>
                                                                    <tr>
                                                                        <td><?= $i++; ?></td>
                                                                        <td><?= $row->manual_id; ?></td>
                                                                        <td><?= date('Y-m-d', strtotime($row->start_date)) . ' to ' . date('Y-m-d', strtotime($row->end_date)); ?></td>
                                                                        <td><?= $row->consignee; ?></td>
                                                                        <td><?= $row->agent_name; ?></td>
                                                                        <td><?= $row->passenger_count; ?></td>
                                                                        <td><?= $row->create_user; ?></td>

                                                                        <td>
                                                                            <?php if ($row->Confirm == 1) { ?>
                                                                                <h5><code>Confirmed</code></h5>
                                                                            <?php } else if ($row->Confirm == 3) { ?>
                                                                                <h5><code>Tour Cancel</code></h5>
                                                                            <?php } if ($row->Confirm == 4){ ?>
                                                                                <h5><code>Amend Inv</code></h5>
                                                                            <?php }else { ?>
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
                                                                            <button type="button" class="btn btn-success btn-sm view" title="Tour Dashboard" data-tour_id="<?= $row->tour_id; ?>" data-year="<?= $start_date_year; ?>" data-month="<?= $start_date_month; ?>" data-confirm="<?= $row->Confirm; ?>"><i class="bi bi-eye-fill"></i></button>
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


                        </div> <!-- end .h-100-->

                    </div> <!-- end col -->
                </div>
            </div>
        </div>
    </div>
    <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        function load_tour_details(e) {
            var year = $(e).data('year');
            var month = $(e).data('month');
            window.location.href = '<?= base_url(); ?>tour/tour_dashboard/monthly_dashboard?year=' + year + '&month=' + month;
        }

        function load_tours(e) {
            window.location.href = '<?= base_url(); ?>tour/tour_dashboard/tour_templates';
        }
    </script>
    <script>
        $(".view").on('click', function() {
            // alert('test');
            var year = $(this).data('year');
            var month = $(this).data('month');
            var tour = $(this).data('tour_id');
            var confirm = $(this).data('confirm');
            if (confirm == 0) {
                // add alert message 'you can't view this tour, because it is not confirmed. use to confirm working dashboard'
                Swal.fire({
                    icon: 'error',
                    title: 'Tour has not been Booked!',
                    text: 'You cannot view this tour because it has not been booked. Please book the tour to view the details. Use the Working Dashboard to proceed.',
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open('<?= base_url(); ?>tour/tour_details/monthly_dashboard?year=' + year + '&month=' + month + '&tour_id=' + tour, '_blank');
                    }
                });
            } else {
                window.open('<?= base_url(); ?>tour/tour_dashboard/monthly_dashboard?year=' + year + '&month=' + month + '&tour_id=' + tour, '_blank');
            }
        });

        $(document).ready(function() {
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('keyword')) {
                $('#keyword').val(urlParams.get('keyword'));
                // scoll down search_table
                $('html, body').animate({
                    scrollTop: $("#search_table").offset().top
                }, 1000);
            }
        });
    </script>

</body>

</html>