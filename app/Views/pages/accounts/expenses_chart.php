<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>

        <head>
            <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
            <script type="text/javascript">
                google.charts.load("current", {
                    packages: ["corechart"]
                });
                google.charts.setOnLoadCallback(drawChart);

                function drawChart() {

                    var expenses = <?= json_encode($expenses) ?>;
                    console.log(parseFloat(expenses.hotel));
                    if (expenses?.total == 0) {
                        $('#piechart_3d').addClass('d-none');
                        $('#alert_d').removeClass('d-none');
                    } else {
                        $('#piechart_3d').removeClass('d-none');
                        $('#alert_d').addClass('d-none');
                        var data = google.visualization.arrayToDataTable([
                            ['Expense', 'Amount'],
                            ['Hotel - ' + expenses.hotel, parseFloat(expenses.hotel)],
                            ['Transport - ' + expenses.transport, parseFloat(expenses.transport)],
                            ['Entrance Fee - ' + expenses.entrance_fee, parseFloat(expenses.entrance_fee)],
                            ['Other - ' + expenses.other, parseFloat(expenses.other)]
                        ]);

                        var options = {
                            title: 'Expenses',
                            is3D: true,
                        };

                        var chart = new google.visualization.PieChart(document.getElementById('piechart_3d'));
                        chart.draw(data, options);
                    }
                }
            </script>
        </head>
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
                                <div class="row ">
                                    <div class="col-md-12 d-flex">
                                        <h4 class="p-title">Expenses Chart</h4>
                                    </div>
                                </div>
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="d-flex flex-column">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="drange1">Start Date</label>
                                                                <input class="form-control txt_nav date" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" data-date-autoclose="true" id="drange1" name="drange1" placeholder="Select Date" value="<?= isset($fromDate) ? $fromDate : date('Y-m-d') ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="drange2">End Date</label>
                                                                <input class="form-control txt_nav date" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" data-date-autoclose="true" id="drange2" name="drange2" placeholder="Select Date" value="<?= isset($toDate) ? $toDate : date('Y-m-d') ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <div class="mb-3">
                                                                <button class="btn btn-success search">
                                                                    <i class="fa fa-search"></i> Search
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div id="piechart_3d" style="width: 900px; height: 500px;"></div>
                                                    <div class="alert alert-danger" id="alert_d">
                                                        <strong>Warning!</strong> No data available for the given date range.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end .h-100-->
                            <div id="map" class="rounded d-none"></div>

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
        // .search button click event
        $('.search').click(function() {
            var fromDate = $('#drange1').val();
            var toDate = $('#drange2').val();
            window.location.href = '<?= base_url('accounts/expenses_chart') ?>?fromDate=' + fromDate + '&toDate=' + toDate;
        });
    </script>
</body>




</html>