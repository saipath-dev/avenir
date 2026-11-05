<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <style>
            .fs-mons {
                font-size: 12px;
                font-family: 'Courier New', Courier, monospace;
                font-weight: 600;
                color: rgb(100, 100, 100);
            }

            .fs-mons-bold {
                font-size: 12px;
                font-family: 'Courier New', Courier, monospace;
                font-weight: 800;
                color: rgb(0, 0, 0);
            }

            td a:hover {
                text-decoration: underline;
                color: dodgerblue !important;
            }

            .detail-row {
                background-color: #f9f9f9; /* Light background for detail rows */
            }

            .table-header {
                background-color: #f9f9f9;
                /*color: white;*/
            }

            .table-footer {
                font-weight: bold;
                background-color: #e9ecef;
            }

            .text-primary {
                color: #f9f9f9;
            }

            .text-end {
                text-align: end;
            }

            .mt-3 {
                margin-top: 1rem;
            }

            .mb-3 {
                margin-bottom: 1rem;
            }

            .pt-3 {
                padding-top: 1rem;
            }

            .pb-3 {
                padding-bottom: 1rem;
            }

            .text-center {
                text-align: center;
            }

            .w-100 {
                width: 100%;
            }

            .h-100 {
                height: 100%;
            }

            .container-fluid {
                padding: 20px;
            }

        </style>

        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col">

                            <div class="h-100">
                                <div class="row mb-3">
                                    <div class="col-md-10 d-flex">
                                        <h4 class="p-title">Bank Reconciliation Detailed Report</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body">
                                                <button id="print" class="btn btn-sm btn-success btn_print" onclick="printContent('print-data');" style="float: right;">
                                                    <i class="bi bi-print"></i> Print
                                                </button>
                                                <div class="row" id="print-data" style="padding:25px; background-color: white;">
                                                    <div class="col-md-12">
                                                        <div class="box">
                                                            <div class="box-header">
                                                                <table class="w-100">
                                                                    <tr>
                                                                        <td class="text-center pb-3">
                                                                            <h5 class="mt0"> <b>Avenir Holidays (Pvt) Ltd</b></h5>
                                                                            <p>No. 94/2, Lauries Rd,<br>Colombo 4<br>Tel: +94-77-395-7956, +94 112 551 880</p>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-center pb-3">
                                                                            <h5 class="mt-3">Bank Reconciliation Details</h5>
                                                                        </td>
                                                                    </tr>
                                                                </table>

                                                                <!-- Reconciliation Summary -->
                                                                <table class="w-100 mt-4">
                                                                    <tr class="border-bottom table-header fs-mons">
                                                                        <td><b>Beginning Balance</b></td>
                                                                        <td class="text-end"><b><?php echo number_format($opening_balance, 2); ?></b></td>
                                                                    </tr>
                                                                </table>
                                                                <!-- Uncleared Transactions -->
                                                                <table class="w-100 mt-4">
                                                                    <tr class="fs-mons">
                                                                        <td><b>Cleared Transaction</b></td>
                                                                        <td class="text-end"><b></b></td>
                                                                    </tr>
                                                                </table>

                                                                <!-- Reconciliation Data -->
                                                                <table class="w-100 mt-4">
                                                                    <tr>
                                                                        <td colspan="6" class="pt-3 fs-mons-bold">
                                                                            <b><h6>Deposit & Credits - <?php echo $result['count_dr_positive']; ?> items</h6></b>
                                                                        </td>
                                                                    </tr>
                                                                    <?php 
                                                                    $cleared_deposit =0;
                                                                    $cleared_dr_total =0;
                                                                    $ending_balnce_cleared =0;
                                                                    foreach ($details as $value) {
                                                                        if ($value->Dr > 0) { 

                                                                            $cleared_deposit += $value->Dr;
                                                                            $cleared_dr_total += $value->Dr;


                                                                            ?>
                                                                            <tr>
                                                                                <td class="pt-3 fs-mons-bold"><b><?php echo $value->PName; ?></b></td>
                                                                                <td class="pt-3 fs-mons-bold"><b><?php echo $value->Date; ?></b></td>
                                                                                <td class="pt-3 fs-mons-bold"><b><?php echo $value->Discription; ?></b></td>
                                                                                <td style="align-content: center;"><b><i class="bi bi-check-circle-fill"></i></b></td>
                                                                                <td class="pt-3 fs-mons-bold text-end "><b><?php echo $value->Dr; ?></b></td>
                                                                                <td class="text-end pt-3 fs-mons-bold"><?php echo number_format($cleared_deposit, 2); ?></td>
                                                                            </tr>
                                                                        <?php }
                                                                    } ?>
                                                                    <br>
                                                                    <tr class="table-footer">
                                                                        <td colspan="4"><b>Total Deposits</b></td>
                                                                        <td class="text-end"><?php echo number_format($cleared_deposit, 2); ?></td>
                                                                        <td class="text-end"><?php echo number_format($cleared_dr_total, 2); ?></td>
                                                                    </tr>
                                                                    <br><br>

                                                                    <tr>
                                                                        <td colspan="6" class="pt-3 fs-mons-bold">
                                                                            <b><h6>Checks and Payments - <?php echo $result['count_cr_positive']; ?> items</h6></b>
                                                                        </td>
                                                                    </tr>
                                                                    <?php
                                                                    // $cleared_payments =0;
                                                                    $cleared_cr_total =0;
                                                                    foreach ($details as $value) {
                                                                        if ($value->Cr > 0) {
                                                                            $cleared_cr_total += $value->Cr;

                                                                            ?>
                                                                            <tr>
                                                                                <td class="pt-3 fs-mons-bold"><b><?php echo $value->PName; ?></b></td>
                                                                                <td class="pt-3 fs-mons-bold"><b><?php echo $value->Date; ?></b></td>
                                                                                <td class="pt-3 fs-mons-bold"><b><?php echo $value->Discription; ?></b></td>
                                                                                <td style="align-content: center;"><b><i class="bi bi-check-circle-fill"></i></b></td>
                                                                                <td class="pt-3 fs-mons-bold text-end"><b><?php echo $value->Cr; ?></b></td>
                                                                                <td class="text-end pt-3 fs-mons-bold"><?php echo number_format($cleared_cr_total, 2); ?></td>
                                                                            </tr>
                                                                        <?php }
                                                                    } ?>
                                                                    <tr class="table-footer">
                                                                        <td colspan="4"><b>Total Payments</b></td>
                                                                        <td class="text-end"><?php echo number_format($cleared_cr_total, 2); ?></td>
                                                                        <td class="text-end"><?php echo number_format($cleared_cr_total, 2); ?></td>
                                                                    </tr>
                                                                    <?php

                                                                    $cleared_balnce =  $result['dr'] - $result['cr'];
                                                                    $current_balance = $opening_balance+ $cleared_balnce;
                                                                    $uncleared_balane = $uncleared_entries['dr'] - $uncleared_entries['cr'];
                                                                    $ending_balnce_cleared = $cleared_cr_total+$cleared_dr_total+$opening_balance;
                                                                    // $final_balance =  $ending_balnce_cleared;

                                                                    ?>

                                                                </table>

                                                                <!-- Cleared Balance -->
                                                                <table class="w-100 mt-4">
                                                                    <tr class="border-bottom table-header fs-mons">
                                                                        <td><b>Cleared Balance</b></td>
                                                                        <td class="text-end"><b><?php echo number_format(($cleared_dr_total-$cleared_cr_total)+$opening_balance, 2); ?></b></td>
                                                                    </tr>
                                                                </table>
                                                                <br>

                                                                <!-- Uncleared Transactions -->
                                                                <table class="w-100 mt-4">
                                                                    <tr class="fs-mons">
                                                                        <td><b>Uncleared Transaction</b></td>
                                                                        <td class="text-end"><b></b></td>
                                                                    </tr>
                                                                </table>

                                                                <table class="w-100 mt-4">
                                                                    <tr>
                                                                        <td colspan="6" class="pt-3 fs-mons-bold">
                                                                            <b><h6>Deposit & Credits - <?php echo $uncleared_entries['count_dr_positive']; ?></h6></b>
                                                                        </td>
                                                                    </tr>
                                                                    <?php
                                                                    $uncleared_deposit =0;
                                                                    $uncleared_dr_total =0;
                                                                    $uncleared_dr_total =0;

                                                                    foreach ($uncleared_details as $undt) {
                                                                        if ($undt->Dr > 0) { 
                                                                           $uncleared_deposit += $undt->Dr;
                                                                           $uncleared_dr_total += $undt->Dr;


                                                                           ?>
                                                                           <tr>
                                                                            <td class="pt-3 fs-mons-bold"><b><?php echo $undt->PName; ?></b></td>
                                                                            <td class="pt-3 fs-mons-bold"><b><?php echo $undt->Date; ?></b></td>
                                                                            <td class="pt-3 fs-mons-bold"><b><?php echo $undt->Discription; ?></b></td>
                                                                            <td style="align-content: center;"><b></b></td>
                                                                            <td class="pt-3 fs-mons-bold text-end "><b><?php echo $undt->Dr; ?></b></td>
                                                                            <td class="text-end pt-3 fs-mons-bold"><?php echo number_format($uncleared_dr_total, 2); ?></td>
                                                                        </tr>
                                                                    <?php }
                                                                } ?>

                                                                <br>
                                                                <tr class="table-footer">
                                                                    <td colspan="4"><b>Total Deposits</b></td>
                                                                    <td class="text-end"><?php echo number_format($uncleared_deposit, 2); ?></td>
                                                                    <td class="text-end"><?php echo number_format($uncleared_dr_total, 2); ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="6" class="pt-3 fs-mons-bold">
                                                                        <b><h6>Checks and Payments - <?php echo $uncleared_entries['count_cr_positive']; ?> items</h6></b>
                                                                    </td>
                                                                </tr>
                                                                <?php 
                                                                $uncleared_cr_total = 0;
                                                                foreach ($uncleared_details as $undt) {
                                                                    if ($undt->Cr > 0) { 
                                                                       $uncleared_cr_total += $undt->Cr;

                                                                       ?>
                                                                       <tr>
                                                                        <td class="pt-3 fs-mons-bold"><b><?php echo $undt->PName; ?></b></td>
                                                                        <td class="pt-3 fs-mons-bold"><b><?php echo $undt->Date; ?></b></td>
                                                                        <td class="pt-3 fs-mons-bold"><b><?php echo $undt->Discription; ?></b></td>
                                                                        <td style="align-content: center;"><b></b></td>
                                                                        <td class="pt-3 fs-mons-bold text-end "><b><?php echo $undt->Cr; ?></b></td>
                                                                        <td class="text-end pt-3 fs-mons-bold"><?php echo number_format($uncleared_cr_total, 2); ?></td>
                                                                    </tr>
                                                                <?php }
                                                            } ?>
                                                            <tr class="table-footer">
                                                                <td colspan="4"><b>Total Payments</b></td>
                                                                <td class="text-end"><?php echo number_format($uncleared_cr_total, 2); ?></td>
                                                                <td class="text-end"><?php echo number_format($uncleared_cr_total, 2); ?></td>
                                                            </tr>
                                                       
                                                        </table>

                                                        <!-- Uncleared Balance -->
                                                        <table class="w-100 mt-4">
                                                            <tr class="border-bottom table-header fs-mons">
                                                                <td><b>Uncleared Balance</b></td>
                                                                <td class="text-end"><b><?php echo number_format($uncleared_dr_total-$uncleared_cr_total, 2); ?></b></td>
                                                            </tr>
                                                        </table>

                                                        <!-- Register Balance -->
                                                        <table class="w-100 mt-4">
                                                            <tr class="fs-mons">
                                                                <td><b>Register Balance as of <?php echo $date; ?></b></td>
                                                                <td class="text-end"><b><?php echo number_format(($ending_balnce_cleared+($uncleared_dr_total-$uncleared_cr_total)), 2); ?></b></td>
                                                            </tr>
                                                        </table>

                                                    </div>
                                                    <br>
                                                </div>
                                            </div>
                                        </div>
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
<script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
<script>
    function printContent(el) {
        var restorepage = $('body').html();
        var printcontent = $('#' + el).clone();
        $('body').empty().html(printcontent);
        window.print();
        $('body').html(restorepage);
    }

    // Reset on click
    $('.reset').on('click', function() {
        window.location.href = "<?php echo base_url('accounts/bank_reconciliation'); ?>";
    });

    function toggleDetails(row) {
        var nextRow = row.nextElementSibling; // Get the next row
        if (nextRow.style.display === "none" || nextRow.style.display === "") {
            nextRow.style.display = "table-row"; // Show the detail row
        } else {
            nextRow.style.display = "none"; // Hide the detail row
        }
    }
</script>
<?= view('pages/accounts/balance_details'); ?>
</body>
</html>
