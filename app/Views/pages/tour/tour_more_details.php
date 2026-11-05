<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>


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
                                        <h4 class="p-title">Accommodation status</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <style>
                                                        td,
                                                        th {
                                                            font-size: 14px;
                                                            border-bottom: 0;
                                                        }
                                                    </style>
                                                    <table class="table align-middle js-basic-example table-hover dataTable table-custom">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th style="width: 5%;">#</th>
                                                                <th style="width: 10%;">Meal Code</th>
                                                                <th style="width: 10%;">Date</th>
                                                                <th style="width: 15%;">Hotel Name</th>
                                                                <th style="width: 10%;">Room Type</th>
                                                                <th style="width: 10%;">Start Date</th>
                                                                <th style="width: 10%;">End Date</th>
                                                                <th style="width: 10%;">SGL</th>
                                                                <th style="width: 10%;">DBL</th>
                                                                <th style="width: 10%;">TPL</th>
                                                                <th style="width: 10%;">Tour Km</th>
                                                                <th style="width: 10%;">Extra Km</th>
                                                                <th style="width: 10%;">From Location</th>
                                                                <th style="width: 10%;">To Location</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="accordionExample">
                                                            <?php
                                                            $i = 1;
                                                            foreach ($tour_details as $row) {
                                                            ?>
                                                                <tr data-toggle="collapse" data-target="#collapse<?= $i ?>" aria-expanded="true" aria-controls="collapse<?= $i ?>" style="cursor: pointer;">
                                                                    <td class="align-middle"><?= $i ?></td>
                                                                    <td class="align-middle"><?= strtoupper($row['meal_code']) ?></td>
                                                                    <td class="align-middle"><?= $row['tour_date'] ?></td>
                                                                    <td class="align-middle"><?= $row['hotel_name'] ?></td>
                                                                    <td class="align-middle"><?= $row['rtype'] ?></td>
                                                                    <td class="align-middle"><?= $start_date ?></td>
                                                                    <td class="align-middle"><?= $end_date ?></td>
                                                                    <td class="align-middle"><?= $row['sgl'] ?></td>
                                                                    <td class="align-middle"><?= $row['dbl'] ?></td>
                                                                    <td class="align-middle"><?= $row['tpl'] ?></td>
                                                                    <td class="align-middle"><?= $row['distance'] ?></td>
                                                                    <td class="align-middle"><?= $row['extra_kilo'] ?></td>
                                                                    <td class="align-middle"><?= $row['from_location'] ?></td>
                                                                    <td class="align-middle"><?= $row['to_location'] ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="6" class="p-0">
                                                                        <div id="collapse<?= $i ?>" class="collapse" aria-labelledby="heading<?= $i ?>" data-parent="#accordionExample">
                                                                            <div class="card-body ps-5">

                                                                                <style>
                                                                                    .table-hover tbody tr:hover {
                                                                                        background-color: transparent;
                                                                                    }
                                                                                </style>

                                                                                <?php if (!empty($row['activity'])) : ?>
                                                                                    <div class="alert alert-secondary bg-body-tertiary">
                                                                                        <table id="sub">
                                                                                            <div class="mb-3">
                                                                                                <span class="fw-bold">Extra Activities</span>
                                                                                                <tr>
                                                                                                    <th style="width: 20%;">#</th>
                                                                                                    <th style="width: 40%;">Activity Name</th>
                                                                                                    <th style="width: 20%;">Activity Code</th>
                                                                                                    <th style="width: 20%;">Fee</th>
                                                                                                </tr>
                                                                                            </div>
                                                                                            <?php
                                                                                            $totalFee = 0;
                                                                                            foreach ($row['activity'] as $key => $act) :
                                                                                            ?>
                                                                                                <tr>
                                                                                                    <td style="width: 20%;" class="align-middle"><?= $key + 1 . '. ' ?></td>
                                                                                                    <td style="width: 40%;" class="align-middle"><?= htmlspecialchars($act['name']) ?></td>
                                                                                                    <td style="width: 20%;" class="align-middle"><?= htmlspecialchars($act['code']) ?></td>
                                                                                                    <td style="width: 20%;" class="align-middle"><?= htmlspecialchars($act['fee']) ?></td>
                                                                                                </tr>
                                                                                            <?php
                                                                                                $totalFee += $act['fee'];
                                                                                            endforeach;
                                                                                            ?>
                                                                                            <tr>
                                                                                                <td colspan="2" class="align-middle text-end"><strong>Total</strong></td>
                                                                                                <td></td>
                                                                                                <td class="align-middle"><?= $totalFee ?></td>
                                                                                            </tr>

                                                                                        </table>
                                                                                    </div>

                                                                                <?php else : ?>
                                                                                    <div class="alert alert-secondary bg-body-tertiary" style="width: fit-content;">
                                                                                        No additional Activity information available.
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td colspan="7" class="p-0">
                                                                        <div id="collapse<?= $i ?>" class="collapse" aria-labelledby="heading<?= $i ?>" data-parent="#accordionExample">
                                                                            <div class="card-body ps-5">

                                                                                <style>
                                                                                    .table-hover tbody tr:hover {
                                                                                        background-color: transparent;
                                                                                    }
                                                                                </style>

                                                                                <?php if (!empty($row['sub_location'])) : ?>
                                                                                    <div class="alert alert-secondary bg-body-tertiary">
                                                                                        <table id="sub">
                                                                                            <div class="mb-3">
                                                                                                <span class="fw-bold">Sub Location</span>
                                                                                                <tr>
                                                                                                    <th style="width: 20%;">#</th>
                                                                                                    <th style="width: 40%;">From</th>
                                                                                                    <th style="width: 20%;">To</th>
                                                                                                    <th style="width: 20%;">KM</th>
                                                                                                </tr>
                                                                                            </div>
                                                                                            <?php
                                                                                            $totalkm =  0;
                                                                                            foreach ($row['sub_location'] as $key => $sub) :
                                                                                            ?>
                                                                                                <tr>
                                                                                                    <td style="width: 20%;" class="align-middle"><?= $key + 1 . '. ' ?></td>
                                                                                                    <td style="width: 40%;" class="align-middle"><?= htmlspecialchars($sub['from']) ?></td>
                                                                                                    <td style="width: 20%;" class="align-middle"><?= htmlspecialchars($sub['to']) ?></td>
                                                                                                    <td style="width: 20%;" class="align-middle"><?= htmlspecialchars($sub['km']) ?></td>
                                                                                                </tr>
                                                                                            <?php
                                                                                                $totalkm += $sub['km'];
                                                                                            endforeach;
                                                                                            ?>
                                                                                            <tr>
                                                                                                <td colspan="2" class="align-middle text-end"><strong>Total</strong></td>
                                                                                                <td></td>
                                                                                                <td class="align-middle"><?= $totalkm ?></td>
                                                                                            </tr>

                                                                                        </table>
                                                                                    </div>

                                                                                <?php else : ?>
                                                                                    <div class="alert alert-secondary bg-body-tertiary" style="width: fit-content;">
                                                                                        No additional Sub Location information available.
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            <?php
                                                                $i++;
                                                            }
                                                            ?>
                                                        </tbody>
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
        // $(document).ready(function() {
        //     $("tr").click(function() {
        //         $(this).next(".accordion-content").slideToggle();
        //     });
        // });
    </script>

</body>




</html>