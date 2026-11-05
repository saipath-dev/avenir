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
                                        <h4 class="p-title">Profit & Loss </h4>
                                    </div>
                                </div>

                                <?php
                                if (isset($expenses) || isset($income)) {
                                ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <!-- Additional Details Section -->
                                                    <div class="mb-4">
                                                        <!-- <h4>Tour Details</h4> -->
                                                        <p><strong>Consignee:</strong> <?php echo $tour_name['consignee'];  ?></p>
                                                        <p><strong>Tour ID:</strong> <?php echo $tour_id;  ?></p>
                                                        <p><strong>Tour Manual ID:</strong> <?php echo $tour_name['manual_id'];  ?></p>

                                                    </div>

                                                    <!-- Table Section -->
                                                    <div class="table-responsive">
                                                        <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Expenses Type</th>
                                                                    <th>Expenses Account</th>
                                                                    <th>Description</th>
                                                                    <th>Invoice No</th>
                                                                    <th>Date</th>
                                                                    <th>Amount</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $x = 1;
                                                                $totalAmount = 0;
                                                                foreach ($expenses as $litem) {
                                                                    $job = $litem['job'];

                                                                    if ($job == '4-8-106-10386') {
                                                                        $payment_type = 'Hotel';
                                                                    } else if ($job == '4-8-106-10385') {
                                                                        $payment_type = 'Transport';
                                                                    } else if ($job == '6-9-23-10640') {
                                                                        $payment_type = 'Entrance Fee';
                                                                    } else if ($job == '6-9-23-10641') {
                                                                        $payment_type = 'Other';
                                                                    } else {
                                                                        $payment_type = '';
                                                                    }
                                                                ?>
                                                                    <tr>
                                                                        <td><?php echo $x ?></td>
                                                                        <td><?php echo htmlspecialchars($payment_type); ?></td>
                                                                        <td><?php echo htmlspecialchars($litem['Name'] ?? $litem['name'] ?? $litem['payee']); ?></td>
                                                                        <td><?php echo htmlspecialchars($litem['Discription']); ?></td>
                                                                        <td><?php echo htmlspecialchars($litem['Invoice_Num']); ?></td>
                                                                        <td><?php echo htmlspecialchars($litem['Date']); ?></td>
                                                                        <td><?php echo number_format($tot_amt = $litem['Dr'] - $litem['Cr'], 2); ?></td>
                                                                    </tr>
                                                                <?php
                                                                    $x++;
                                                                    $totalAmount += $tot_amt;
                                                                }
                                                                ?>
                                                                <?php
                                                                if (isset($other_expenses) && !empty($other_expenses)) {
                                                                    foreach ($other_expenses as $oitem) {
                                                                ?>
                                                                    <tr>
                                                                        <td><?php echo $x ?></td>
                                                                        <td><?php echo htmlspecialchars($oitem['Expenses_Type'] ?? 'Other'); ?></td>
                                                                        <td><?php echo htmlspecialchars($oitem['Name'] ?? $oitem['name'] ?? $oitem['payee']); ?></td>
                                                                        <td><?php echo htmlspecialchars($oitem['Discription']); ?></td>
                                                                        <td><?php echo htmlspecialchars($oitem['Invoice_Num']); ?></td>
                                                                        <td><?php echo htmlspecialchars($oitem['Date']); ?></td>
                                                                        <td><?php echo number_format($ot_amt = $oitem['Cr'] - $oitem['Dr'], 2); ?></td>
                                                                    </tr>
                                                                <?php
                                                                        $x++;
                                                                        $totalAmount += $ot_amt;
                                                                    }
                                                                }
                                                                ?>
                                                                <tr>
                                                                    <td colspan="6" class="text-left"><b>Total Expenses</b></td>
                                                                    <td><b><?php echo number_format($totalAmount, 2); ?></b></td>
                                                                </tr>
                                                                <tr>
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th>#</th>
                                                                            <th colspan="4">Invoice No</th>
                                                                            <th>Date</th>
                                                                            <th>Income</th>

                                                                        </tr>
                                                                    </thead>
                                                                </tr>

                                                                <tr>
                                                                    <!-- <td colspan="4" class="text-left"><b>Total Income</b></td> -->
                                                                    <?php
                                                                    $xx = 1;
                                                                    // $totalAmount = 0;
                                                                    $totIncome = 0;
                                                                    foreach ($income as $inc) { ?>

                                                                        <td class="text-left"><b><?php echo $xx; ?></b></td>
                                                                        <td colspan="3" class="text-left"><b><?php echo $inc['Invoice_Num'] ?></b></td>
                                                                        <td class="text-left"><b><?php echo $inc['date'] ?></b></td>
                                                                        <td><b><?php echo number_format($inc['income'], 2); ?></b></td>
                                                                    <?php
                                                                        $xx++;
                                                                        $totIncome += $inc['income'];
                                                                    } ?>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="6" class="text-left"><b>Total Income</b></td>
                                                                    <td><b><?php echo number_format($totIncome, 2); ?></b></td>
                                                                </tr>
                                                                <tr class="table-danger">
                                                                    <td colspan="6" class="text-left"><b>Profit</b></td>
                                                                    <td><b><?php echo number_format($totIncome - $totalAmount, 2); ?></b></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional CSS for improved styling -->
                                    <style>
                                        .thead-dark th {
                                            background-color: #343a40;
                                            color: #fff;
                                        }

                                        .table-custom th,
                                        .table-custom td {
                                            padding: 1rem;
                                            border: 1px solid #dee2e6;
                                        }

                                        .table-custom {
                                            margin-top: 1rem;
                                            border-collapse: collapse;
                                            width: 100%;
                                        }

                                        .text-left {
                                            text-align: left;
                                        }
                                    </style>

                                <?php } else {
                                ?> <div class="alert alert-info"> No data available for the selected tour. </div> <?php } ?>
                            </div>
                        </div>
                    </div>
                </div> <!-- container-fluid -->
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