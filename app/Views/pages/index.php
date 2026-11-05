<?= view('layouts/head') ?>
<style>
    .year-carousel {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 20px 0;
    }

    .year-display {
        margin: 0 10px;
        font-size: 1.5rem;
    }

    .btn-light {
        cursor: pointer;
    }

    .small-font {
        font-size: 1rem;
    }
</style>

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

                    <?php if (session()->get('group_id') == 1) { ?>
                        <div class="row">
                            <div class="col">

                                <div class="h-100">
                                    <div class="row mb-3 pb-1">
                                        <div class="col-12">
                                            <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                                                <?php
                                                $greeting_text = "";
                                                $greeting_img = '';
                                                // time zone
                                                date_default_timezone_set('Asia/Colombo');
                                                // if morning, say good morning and so on
                                                if (date("H") < 12) {
                                                    $greeting_text = "Good Morning";
                                                } else if (date("H") >= 12 && date("H") < 18) {
                                                    $greeting_text = "Good Afternoon";
                                                } else if (date("H") >= 18) {
                                                    $greeting_text = "Good Evening";
                                                }
                                                ?>
                                                <div class="flex-grow-1">
                                                    <h4 class="fs-16 mb-1"><?php echo $greeting_text ?></h4>
                                                    <p class="text-muted mb-0">Here's what's happening today.</p>
                                                </div>
                                                <div class="mt-3 mt-lg-0">
                                                    <form action="javascript:void(0);">
                                                        <div class="row g-3 mb-0 align-items-center">
                                                            <div class="col-sm-auto">
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control border-0 dash-filter-picker" data-range-date="true" data-date-format="yyyy-mm-dd" value="<?php echo date('Y-m-d') ?>" readonly>
                                                                    <div class="input-group-text bg-primary border-primary text-white">
                                                                        <i class="ri-calendar-2-line"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!--end row-->
                                                    </form>
                                                </div>
                                            </div><!-- end card header -->
                                        </div>
                                        <!--end col-->
                                    </div>
                                    <!--end row-->

                                    <!-- Employee Activity Section -->
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-header bg-primary text-white">
                                                    <h5 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Employee Activity Today</h5>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (!empty($employee_activity)): ?>
                                                    <div class="row g-2">
                                                        <?php foreach ($employee_activity as $emp): ?>
                                                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                                            <div class="card border shadow-sm h-100">
                                                                <div class="card-body text-center p-2">
                                                                    <div class="mb-2">
                                                                        <img src="<?= base_url('assets/icons/' . ($emp['gender'] == 'male' ? 'man.png' : 'women.png')) ?>"
                                                                             alt="<?= $emp['name'] ?>"
                                                                             class="rounded-circle"
                                                                             width="50"
                                                                             height="50">
                                                                    </div>
                                                                    <h6 class="fw-bold text-truncate mb-2" style="font-size: 0.85rem;" title="<?= $emp['name'] ?>">
                                                                        <?php
                                                                        // Extract first word (username) from full name
                                                                        $nameParts = explode(' ', $emp['name']);
                                                                        echo $nameParts[0];
                                                                        ?>
                                                                    </h6>
                                                                    <div class="mt-2">
                                                                        <div class="mb-1">
                                                                            <small class="text-muted d-block" style="font-size: 0.7rem;">Login</small>
                                                                            <span class="badge bg-success" style="font-size: 0.7rem;"><?= $emp['login_time'] ?></span>
                                                                        </div>
                                                                        <div class="mb-1">
                                                                            <small class="text-muted d-block" style="font-size: 0.7rem;">Last Activity</small>
                                                                            <span class="badge bg-info" style="font-size: 0.7rem;"><?= $emp['last_activity'] ?></span>
                                                                        </div>
                                                                        <div>
                                                                            <small class="text-muted d-block" style="font-size: 0.7rem;">Duration</small>
                                                                            <span class="badge bg-primary" style="font-size: 0.7rem;"><?= $emp['duration'] ?></span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <?php else: ?>
                                                    <div class="text-center py-4">
                                                        <i class="bi bi-info-circle text-muted" style="font-size: 48px;"></i>
                                                        <p class="text-muted mt-2">No employee activity recorded today</p>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End Employee Activity Section -->

                                    <div class="row">
                                        <div class="col-xl-3 col-md-6">
                                            <!-- card -->
                                            <div class="card card-animate">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1 overflow-hidden">
                                                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Inquery</p>
                                                        </div>
                                                        <!-- <div class="flex-shrink-0">
                                                        <h5 class="text-success fs-14 mb-0">
                                                            <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +16.24 %
                                                        </h5>
                                                    </div> -->
                                                    </div>
                                                    <div class="d-flex align-items-end justify-content-between mt-4">
                                                        <div>
                                                            <!-- inq_count -->
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <span>
                                                                        <i class="bi bi-hourglass-split"></i>
                                                                        <h4 class="fs-22 fw-semibold ff-secondary mb-4"><span class="counter-value" data-target="<?php echo $inq_pending_count; ?>"></span></h4>
                                                                    </span>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <i class="bi bi-check-circle"></i>
                                                                    <h4 class="fs-22 fw-semibold ff-secondary mb-4"><span class="counter-value" data-target="<?php echo $inq_count; ?>"></span></h4>
                                                                </div>
                                                            </div>
                                                            <!-- <a href="#" class="text-decoration-underline">View net earnings</a> -->
                                                        </div>
                                                        <div class="avatar-sm flex-shrink-0">
                                                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                                                <i class="bx bx-phone-incoming text-success"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div><!-- end card body -->
                                            </div><!-- end card -->
                                        </div><!-- end col -->

                                        <div class="col-xl-3 col-md-6">
                                            <!-- card -->
                                            <div class="card card-animate">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1 overflow-hidden">
                                                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Quotations</p>
                                                        </div>
                                                        <!-- <div class="flex-shrink-0">
                                                        <h5 class="text-danger fs-14 mb-0">
                                                            <i class="fs-13 align-middle"></i> +0.00 %
                                                        </h5>
                                                    </div> -->
                                                    </div>
                                                    <div class="d-flex align-items-end justify-content-between mt-4">

                                                        <div class="w-100">
                                                            <div class="row w-100">
                                                                <div class="col-md-6">
                                                                    <span>
                                                                        <i class="bi bi-hourglass-split"></i>
                                                                        <h4 class="fs-22 fw-semibold ff-secondary mb-4"><span class="counter-value" data-target="<?php echo $quotation_pending_count; ?>"></span></h4>
                                                                    </span>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <i class="bi bi-check-circle"></i>
                                                                    <h4 class="fs-22 fw-semibold ff-secondary mb-4"><span class="counter-value" data-target="<?php echo $quotation_count; ?>"></span></h4>
                                                                </div>
                                                            </div>

                                                            <!-- <a href="#" class="text-decoration-underline">View all orders</a> -->
                                                        </div>
                                                        <div class="avatar-sm flex-shrink-0">
                                                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                                                <i class="bx bx-area text-info"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div><!-- end card body -->
                                            </div><!-- end card -->
                                        </div><!-- end col -->

                                        <div class="col-xl-3 col-md-6">
                                            <div class="card card-animate">
                                                <div class="card-body" style="min-height: 145px;">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1 overflow-hidden">
                                                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Live Operation</p>
                                                        </div>
                                                        <div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-xl-3 col-md-6">
                                            <!-- card -->
                                            <div class="card card-animate">
                                                <div class="card-body" style="min-height: 143px;">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1 overflow-hidden">
                                                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Complete Tours</p>
                                                        </div>
                                                        <!-- <div class="flex-shrink-0">
                                                        <h5 class="text-muted fs-14 mb-0">
                                                            +0.00 %
                                                        </h5>
                                                    </div> -->
                                                    </div>
                                                    <div class="d-flex align-items-end justify-content-between mt-4">
                                                        <div>
                                                            <h4 class="fs-22 fw-semibold ff-secondary mb-4"><span class="counter-value" data-target="0">0</span></h4>
                                                        </div>
                                                        <div class="avatar-sm flex-shrink-0">
                                                            <span class="avatar-title bg-primary-subtle rounded fs-3">
                                                                <i class="bx bx-calendar-check text-primary"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div><!-- end card body -->
                                            </div><!-- end card -->
                                        </div><!-- end col -->
                                    </div> <!-- end row-->

                                    <div class="row">
                                        <div class="col-xl-12">
                                            <div class="card">
                                                <div class="card-header border-0 align-items-center d-flex">
                                                    <h4 class="card-title mb-0 flex-grow-1">Active Tours</h4>
                                                </div><!-- end card header -->

                                                <div class="card-body p-3 pt-0 pb-4">
                                                    <div class="w-100">
                                                        <style>
                                                            #map {
                                                                height: 800px;
                                                                width: 100%;
                                                                border-radius: 10px;
                                                            }
                                                        </style>
                                                        <div id="map"></div>
                                                    </div>
                                                </div><!-- end card body -->
                                            </div><!-- end card -->
                                        </div><!-- end col -->
                                        <!-- end col -->
                                    </div>

                                    <div class="row">
                                        <div class="col-xl-8 d-none">
                                            <div class="card">
                                                <div class="card-header border-0 align-items-center d-flex">
                                                    <h4 class="card-title mb-0 flex-grow-1">Revenue</h4>
                                                    <!-- <div>
                                                    <button type="button" class="btn btn-soft-secondary btn-sm">
                                                        ALL
                                                    </button>
                                                    <button type="button" class="btn btn-soft-secondary btn-sm">
                                                        1M
                                                    </button>
                                                    <button type="button" class="btn btn-soft-secondary btn-sm">
                                                        6M
                                                    </button>
                                                    <button type="button" class="btn btn-soft-primary btn-sm">
                                                        1Y
                                                    </button>
                                                </div> -->
                                                </div><!-- end card header -->

                                                <!-- <div class="card-header p-0 border-0 bg-light-subtle">
                                                <div class="row g-0 text-center">
                                                    <div class="col-6 col-sm-3">
                                                        <div class="p-3 border border-dashed border-start-0">
                                                            <h5 class="mb-1"><span class="counter-value" data-target="7585">0</span></h5>
                                                            <p class="text-muted mb-0">Orders</p>
                                                        </div>
                                                    </div> -->
                                                <!--end col-->
                                                <!-- <div class="col-6 col-sm-3">
                                                        <div class="p-3 border border-dashed border-start-0">
                                                            <h5 class="mb-1">$<span class="counter-value" data-target="22.89">0</span>k</h5>
                                                            <p class="text-muted mb-0">Earnings</p>
                                                        </div>
                                                    </div> -->
                                                <!--end col-->
                                                <!-- <div class="col-6 col-sm-3">
                                                        <div class="p-3 border border-dashed border-start-0">
                                                            <h5 class="mb-1"><span class="counter-value" data-target="367">0</span></h5>
                                                            <p class="text-muted mb-0">Refunds</p>
                                                        </div>
                                                    </div> -->
                                                <!--end col-->
                                                <!-- <div class="col-6 col-sm-3">
                                                        <div class="p-3 border border-dashed border-start-0 border-end-0">
                                                            <h5 class="mb-1 text-success"><span class="counter-value" data-target="18.92">0</span>%</h5>
                                                            <p class="text-muted mb-0">Conversation Ratio</p>
                                                        </div>
                                                    </div> -->
                                                <!--end col-->
                                                <!-- </div>
                                            </div> -->
                                                <!-- end card header -->

                                                <div class="card-body p-0 pb-2">
                                                    <div class="w-100">
                                                        <div id="customer_impression_charts" data-colors='["--vz-primary", "--vz-success", "--vz-danger"]' class="apex-charts" dir="ltr"></div>
                                                    </div>
                                                </div><!-- end card body -->
                                            </div><!-- end card -->
                                        </div><!-- end col -->

                                        <div class="col-lg-3 col-md-12 h-100">
                                            <div class="card card-height-100">
                                                <div class="card-header align-items-center d-flex">
                                                    <h4 class="card-title mb-0 flex-grow-1">Live Operation
                                                        <br>
                                                        <small class="text-muted">
                                                            Count of All Tour Agents
                                                        </small>
                                                    </h4>
                                                </div><!-- end card header -->
                                                <div class="card-body" style="min-height: 500px;">
                                                    <!-- <div class="sparkline-pie text-center"></div> -->
                                                    <div class="d-flex flex-column">
                                                        <div class="year-carousel small-font" style="margin-top: 1px;">
                                                            <button class="btn btn-light small-font" onclick="changeYear(-1)" style="padding: 2px 5px;">&#8249;</button>
                                                            <div class="year-display small-font">
                                                                <span id="yearOutput"><?= $selected_year; ?></span>
                                                            </div>
                                                            <button class="btn btn-light small-font" onclick="changeYear(1)" style="padding: 2px 5px;">&#8250;</button>
                                                        </div>

                                                        <div class="d-flex align-items-end justify-content-between mt-2">
                                                            <table id="monthlyData" cellpadding="2" cellspacing="0" style="border-collapse: collapse; text-align: center; width: 100%; font-size: 0.8rem;">
                                                                <tbody>
                                                                    <?php
                                                                    $months = [
                                                                        ['Jan', 'Feb', 'Mar'],
                                                                        ['Apr', 'May', 'Jun'],
                                                                        ['Jul', 'Aug', 'Sep'],
                                                                        ['Oct', 'Nov', 'Dec']
                                                                    ];
                                                                    foreach ($months as $quarter) { ?>
                                                                        <tr style="background-color: #f0f8ff;">
                                                                            <?php foreach ($quarter as $month) { ?>
                                                                                <th class="border" style="color: #08808e;"><?= $month ?></th>
                                                                            <?php } ?>
                                                                        </tr>
                                                                        <tr style="background-color: #f0f8ff;">
                                                                            <?php foreach ($quarter as $month) {
                                                                                $monthIndex = date('n', strtotime($month)); ?>
                                                                                <td class="border"><?= $monthly_data[$monthIndex]['started_tours'] . '/' . $monthly_data[$monthIndex]['total_tours']; ?></td>
                                                                            <?php } ?>
                                                                        </tr>
                                                                    <?php } ?>
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-3 col-md-12 h-100">
                                            <div class="card card-height-100">
                                                <div class="card-header align-items-center d-flex">
                                                    <h4 class="card-title mb-0 flex-grow-1">Agent Tours <br>
                                                        <small class="text-muted">
                                                            Count of All Tour Agents
                                                        </small>
                                                    </h4>
                                                </div><!-- end card header -->
                                                <div class="card-body" style="min-height: 500px;">
                                                    <!-- <div class="sparkline-pie text-center"></div> -->
                                                    <div class="d-flex flex-column">
                                                        <div class="d-flex flex-column overflow-y-auto" style="height: 100%; min-height: 400px" id="agent_list">
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-12 h-100">
                                            <div class="card card-height-100">
                                                <div class="card-header align-items-center d-flex">
                                                    <h4 class="card-title mb-0 flex-grow-1">Transport Agent <br>
                                                        <small class="text-muted">
                                                            Count of All Transport Agents
                                                        </small>
                                                    </h4>
                                                </div><!-- end card header -->
                                                <div class="card-body" style="min-height: 500px;">
                                                    <!-- <div class="sparkline-pie text-center"></div> -->
                                                    <div class="d-flex flex-column">
                                                        <div class="d-flex flex-column overflow-y-auto" style="height: 100%; min-height: 400px" id="transport_agent_list">
                                                        </div>
                                                        <div class="mt-auto">
                                                            <button class="btn btn-sm btn-light fw-normal w-100" data-bs-toggle="modal" data-bs-target="#transportAgentsModal">View All Transport Agents</button>

                                                            <!-- Modal -->
                                                            <div class="modal fade" id="transportAgentsModal" tabindex="-1" aria-labelledby="transportAgentsModalLabel" aria-hidden="true">
                                                                <div class="modal-dialog modal-lg">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="transportAgentsModalLabel">Transport Agents</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <div class="d-flex flex-column overflow-y-auto" style="height: 400px;" id="transport_agent_list_modal">
                                                                                <!-- Transport agents list will be populated here -->
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="modal fade" id="viewNAModal" tabindex="-1" aria-labelledby="viewNAModalLabel" aria-hidden="true">
                                                                <div class="modal-dialog modal-lg">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="viewNAModalLabel">Tours</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <div class="d-flex flex-column overflow-y-auto" style="height: 400px;" id="tour_list_modal">
                                                                                <!-- Tours list will be populated here -->
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3 col-sm-12">
                                            <div class="card card-height-100">
                                                <div class="card-header align-items-center d-flex">
                                                    <div class="d-flex justify-content-between w-100">
                                                        <div class="">
                                                            Supplier Liabilities <br> <small>Data between <span class="morris_dates"></span></small>
                                                        </div>
                                                        <div class="d-flex">
                                                            <a href=" <?= base_url('accounts/liability') ?>" class="btn btn-sm btn-light fw-normal me-1" style="height: fit-content;">Liability Report</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <h6 class="text-muted fw-normal">Total Liability : <span class="fw-bold" id="total_liability"></span></h6>
                                                    </div>
                                                    <div class="chart" id="echart-doughnut" style="height: 325px;"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-xl-4 d-none">
                                            <div class="card card-height-100">
                                                <div class="card-header align-items-center d-flex">
                                                    <h4 class="card-title mb-0 flex-grow-1">Process</h4>
                                                </div><!-- end card header -->

                                                <div class="card-body">
                                                    <div id="store-visits-source" data-colors='["--vz-primary", "--vz-success", "--vz-warning", "--vz-danger", "--vz-info"]' class="apex-charts" dir="ltr"></div>
                                                </div>
                                            </div> <!-- .card-->
                                        </div> <!-- .col-->
                                        <!-- end col -->
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="card card-height-100">
                                                <div class="card-header align-items-center d-flex">
                                                    <div class="d-flex justify-content-between w-100">
                                                        <div class="d-flex justify-content-between w-100">
                                                            <div class="">
                                                                Monthly Supplier Payments <br> <small>Payments between <span class="morris_dates"></span></small>
                                                            </div>
                                                            <div class="float-end">
                                                                <a href="<?= base_url('accounts/liability') ?>" class="btn btn-sm btn-light fw-normal" style="height: fit-content;">Liability Report</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div id="bar_chart_2" class="graph"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div> <!-- end .h-100-->

                            </div> <!-- end col -->
                            <!-- end col -->
                        </div>
                    <?php } else { ?>
                        <div class="row pt-4">
                            <div class="col-md-3 d-flex flex-column">
                                <img src="<?= $profile_picture;  ?>" class="rounded-circle shadow" alt="user" width="150px">
                                <!-- <a href="<?= base_url('my_profile') ?>" class="btn btn-light mt-1" style="width: 200px;">Change</a> -->
                            </div>
                            <div class="col-md-5">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex flex-column">
                                            <h5 class="fw-bold">Profile Details</h5>
                                            <hr>
                                            <span class="fw-normal my-1">Username : <strong class="badge text-bg-light"><?= session()->get('username') ?></strong></span>
                                            <span class="fw-normal my-1">User Role : <strong class="badge text-bg-success"><?= $user_group_name ?></strong></span>
                                            <hr>
                                            <span class="fw-normal my-1">Full Name : <strong><?= $staff['fullname'] ?></strong></span>
                                            <span class="fw-normal my-1">Employee No. : <strong><?= $staff['empno'] ?></strong></span>
                                            <span class="fw-normal my-1">A/C No. : <strong><?= $staff['Acc_No'] ?></strong></span>
                                            <span class="fw-normal my-1">Email : <strong><?= $staff['email'] ?></strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1"></div>
                            <div class="col-md-3 d-flex flex-column">
                                <a href="<?= base_url('my_profile') ?>" class="btn btn-primary chn_pass">Change Password</a>
                                <button id="sign_out" onclick="logout()" class="btn btn-danger mt-2">Sign Out</button>
                            </div>
                        </div>
                    <?php } ?>

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
    <script src="<?php echo base_url(); ?>assets/bundles/morrisscripts.bundle.js"></script>
    <script src="<?php echo base_url(); ?>assets/vendor/echart/echarts.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/pages/charts/echart.js"></script>
    <?php if (session()->get('group_id') == 1) { ?>
        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBW7n1LFvH3Gjn3gWTXzmuBDePpLeBNFIk&libraries=places"></script>
        <script>
            //document on ready
            $(document).ready(function() {
                function initMap() {
                    var map = new google.maps.Map(document.getElementById('map'), {
                        zoom: 8,
                        center: {
                            lat: 7.868039,
                            lng: 80.646464
                        },
                        mapId: 'DEMO_MAP_ID',
                    });

                    var geocoder = new google.maps.Geocoder();

                    // List of city names
                    var cities = [];
                    var cities_json = <?php echo json_encode($today_tours)
                                        ?>;
                    console.log(cities_json);
                    cities_json.forEach(function(city) {
                        if (city.to_location != '') {
                            cities.push(city.to_location);
                        } else {
                            var transfer = city.transfer;
                            var last_word = transfer?.split(" ").pop();
                            cities.push(last_word + ", Sri Lanka");
                        }
                    });

                    //Geocode each city and add a marker
                    cities.forEach(function(city) {
                        geocodeCity(geocoder, map, city);
                    });
                }

                async function geocodeCity(geocoder, map, city) {
                    console.log('Geocoding city: ' + city);
                    const {
                        AdvancedMarkerElement
                    } = await google.maps.importLibrary("marker");
                    geocoder.geocode({
                        'address': city
                    }, function(results, status) {
                        if (status === 'OK') {
                            const marker = new AdvancedMarkerElement({
                                map: map,
                                position: results[0].geometry.location,
                                title: city,
                                label: {
                                    text: city,
                                    color: "black",
                                    fontWeight: "bold",
                                    fontSize: "14px" // You can specify font size as well
                                }
                            });
                            map.setCenter(results[0].geometry.location); //Optionally center the map at the last geocoded location
                        } else {
                            console.log('Geocode was not successful for the following reason: ' + status + ' for ' + city);
                        }
                    });
                }

                // async function geocodeCity(geocoder, map, city) {
                //     console.log('Geocoding city: ' + city);
                //     const {
                //         AdvancedMarkerElement
                //     } = await google.maps.importLibrary("marker");

                //     geocoder.geocode({
                //         'address': city
                //     }, function(results, status) {
                //         if (status === 'OK') {
                //             const position = results[0].geometry.location;

                //             // Create a custom label element
                //             const label = document.createElement('div');
                //             label.style.color = 'black';
                //             label.style.fontWeight = 'bold';
                //             label.style.fontSize = '14px';
                //             label.innerText = city;
                //             label.style.position = 'absolute'; // Ensure proper positioning
                //             label.style.transform = 'translate(-50%, -100%)'; // Center above the marker

                //             // Create the marker
                //             const marker = new AdvancedMarkerElement({
                //                 map: map,
                //                 position: position,
                //                 title: city,
                //                 content: label // Set the content directly
                //             });

                //             // Optionally center the map at the last geocoded location
                //             map.setCenter(position);
                //         } else {
                //             console.log('Geocode was not successful for the following reason: ' + status + ' for ' + city);
                //         }
                //     });
                // }



                initMap();

                //=====================================================================================

                $.ajax({
                    url: "<?= base_url() ?>dashboard/get_bank_balances",
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (response) {
                            var accounts = [];
                            var colors = [];
                            $("#bank_list").html("");
                            response.forEach(function(item) {
                                var bal = parseFloat(item.balance);
                                accounts.push(bal);
                                // a dark random color for each account in hex format
                                var color = "#" + Math.floor(Math.random() * 16777215).toString(16);
                                colors.push(color);
                                $("#bank_list").append(`
                                <div class="d-flex p-1 mb-1">
                                    <div class="d-flex align-items-center me-3">
                                        <i class="bi bi-bank fs-5" style="color: ${color}"></i>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-normal mb-2">${item.PName}</span>
                                        <span class="fw-normal text-muted" style="margin-top: -10px;">Balance : <span class="fw-bold text-black">${parseFloat(
                                            item.balance
                                        ).toFixed(2)}</span></span>
                                    </div>
                                </div>
                                `);
                            });
                            $("#bank_list").append(`
                            <a href="${base_url +"accounts/heading_accounts?BCode=1&TCode=6&HCode=8&path=Asset//Other%20Assets//Bank%20Accounts&codes=1//6//8"}" class="btn btn-dark p-2 fw-normal mt-2">+ New Bank Account</a>
                        `);
                        }
                    },
                });

                //=====================================================================================

                $.ajax({
                    url: "<?= base_url() ?>dashboard/get_agent_list",
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (response) {
                            var accounts = [];
                            var colors = [];
                            $("#agent_list").html("");
                            response.forEach(function(item) {
                                var bal = parseFloat(item.balance);
                                accounts.push(bal);
                                // a dark random color for each account in hex format
                                var color = "#" + Math.floor(Math.random() * 16777215).toString(16);
                                colors.push(color);
                                $("#agent_list").append(`
                                <div class="d-flex p-1 mb-1">
                                    <div class="d-flex align-items-center me-3">
                                        <i class="bi bi-person fs-5" style="color: ${color}"></i>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-normal mb-2">${item.agent_name}</span>
                                        <span class="fw-normal text-muted" style="margin-top: -10px;">Tours : <span class="fw-bold text-black">${item.agent_count}</span></span>
                                    </div>
                                </div>
                                `);
                            });

                        }
                    },
                });


                //=====================================================================================

                $.ajax({
                    url: "<?= base_url() ?>dashboard/get_transport_agent_list",
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (response) {
                            var accounts = [];
                            var colors = [];
                            $("#transport_agent_list").html("");
                            response.forEach(function(item) {
                                var bal = parseFloat(item.balance);
                                accounts.push(bal);
                                // a dark random color for each account in hex format
                                var color = "#" + Math.floor(Math.random() * 16777215).toString(16);
                                colors.push(color);

                                var viewToursBtn = '';
                                if (item.Name === 'NA') {
                                    // Show modal instead of redirecting
                                    viewToursBtn = `<button class="btn btn-sm btn-primary mt-1" onclick="$('#viewNAModal').modal('show')">View Tours</button>`;
                                }

                                $("#transport_agent_list").append(`
                                    <div class="d-flex p-1 mb-1">
                                        <div class="d-flex align-items-center me-3">
                                            <i class="bi bi-person fs-5" style="color: ${color}"></i>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="fw-normal mb-2">${item.Name}</span>
                                            <span class="fw-normal text-muted" style="margin-top: -10px;">Tours : <span class="fw-bold text-black">${item.transport_count}</span></span>
                                            ${viewToursBtn}
                                        </div>
                                    </div>
                                `);
                            });

                        }
                    },
                });



                $.ajax({
                    url: "<?= base_url() ?>dashboard/get_all_transport_agent_list",
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (response) {
                            var modalHtml = `
                            <div class="table-responsive">
                <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Transport Agent</th>
                            <th>Tour Count</th>
                        </tr>
                    </thead>
                    <tbody>`;

                            response.forEach(function(item, index) {
                                modalHtml += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.Name}</td>
                        <td>${item.transport_count}</td>
                    </tr>`;
                            });

                            modalHtml += `
                    </tbody>
                </table>
                </div>`;

                            // Set the generated HTML to the modal
                            $("#transport_agent_list_modal").html(modalHtml);
                        }
                    }
                });


                $.ajax({
                    url: "<?= base_url() ?>dashboard/get_all_NA_tours",
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (response) {
                            var modalHtmlNA = `
                            <div class="table-responsive">
                <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tour Id</th>
                            <th>Tour Created Details</th>
                            <th>T. Start Date - End Date</th>
                        </tr>
                    </thead>
                    <tbody>`;

                            response.forEach(function(item, index) {
                                modalHtmlNA += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.manual_id}</td>
                        <td>${item.create_user}</td>
                        <td>${item.start_date} - ${item.end_date}</td>
                    </tr>`;
                            });

                            modalHtmlNA += `
                    </tbody>
                </table>
                </div>`;

                            // Set the generated HTML to the modal
                            $("#tour_list_modal").html(modalHtmlNA);
                        }
                    }
                });

                //=====================================================================================

                // get the first date of this year
                var date = new Date();
                var firstDay = new Date(date.getFullYear(), 0, 1);
                // add 1 day to the first date of this year
                firstDay.setDate(firstDay.getDate() + 1);
                var firstDayString = firstDay.toISOString().split("T")[0];
                // get the last date of this month
                var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
                // add 1 day to the last date of this month
                lastDay.setDate(lastDay.getDate() + 1);
                var lastDayString = lastDay.toISOString().split("T")[0];
                $(".morris_dates").text(firstDayString + " - " + lastDayString);

                //=====================================================================================

                function getMorris2(element, _data) {
                    Morris.Bar({
                        element: element,
                        data: _data,
                        xkey: "name",
                        ykeys: ["total_dr"],
                        labels: ["Payed"],
                        barColors: ["#ff68b3"],
                    });
                }

                // get_liabilities
                $.ajax({
                    url: "<?= base_url() ?>dashboard/get_liabilities/" +
                        firstDayString +
                        "/" +
                        lastDayString,
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (response && response.months) {
                            // get_liabilities
                            getMorris2("bar_chart_2", response.months);
                        }
                    },
                });
            });
        </script>
    <?php } ?>
    <script>
        <?php if (session()->get('group_id') == 1) { ?>

            let currentYear = <?= $selected_year; ?>;

            function changeYear(direction) {
                currentYear += direction;
                // if (currentYear < (new Date().getFullYear() - 5)) {
                //     currentYear = new Date().getFullYear() - 5;
                // } else if (currentYear > new Date().getFullYear() + 5) {
                //     currentYear = new Date().getFullYear() + 5;
                // }
                if (currentYear < (new Date().getFullYear() - 5)) {
                    currentYear = new Date().getFullYear();
                } else if (currentYear > new Date().getFullYear() + 5) {
                    currentYear = new Date().getFullYear();
                }
                document.getElementById('yearOutput').innerText = currentYear;
                $('#monthlyData').html('<div class="d-flex flex-column justify-content-center"><span class="text-dark my-4">Loading...</span></div></div>');
                $.ajax({
                    url: '<?= base_url('dashboard/get_monthly_data'); ?>',
                    type: 'GET',
                    data: {
                        year: currentYear
                    },
                    dataType: 'html',
                    success: function(data) {
                        $('#monthlyData').html(data);
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
            }

            changeYear(2024);
        <?php } ?>
    </script>
</body>


</html>