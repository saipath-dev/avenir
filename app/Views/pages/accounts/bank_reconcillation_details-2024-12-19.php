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

        </style>

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
                                        <h4 class="p-title">Bank Reconciliation Summary Report</h4>
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
                                                                            <p> No. 94/2, Lauries Rd, <br>Colombo 4<br>
                                                                            Tel: +94-77-395-7956, +94 112 551 880</p>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-center pb-3">
                                                                            <h5 class="mt-3">Bank Reconciliation Summary</h5>
                                                                            <span></span>
                                                                        </td>
                                                                    </tr>
                                                                </table>

                                                                <!-- Reconciliation Summary -->
                                                                <table class="w-100 mt-4">
                                                                    <tr class="border-bottom border-primary fs-mons">
                                                                        <td><b class="text-primary fs-5">Opening Balance</b></td>
                                                                        <td class="text-end text-primary fs-5"><b><?php echo number_format($opening_balance,2) ?></b></td>
                                                                    </tr>
                                                                </table>


                                                                <!-- Reconciliation Summary -->
                                                                <table class="w-100 mt-4">
                                                                    <tr class=" fs-mons">
                                                                        <td><b class="text-primary fs-5">Cleared Transaction</b></td>
                                                                        <td class="text-end text-primary fs-5"><b></b></td>
                                                                    </tr>
                                                                </table>

                                                                <!-- Reconciliation Data -->
                                                                <table class="w-100 mt-4">


                                                                  <!-- Summary Row -->
                                                                  <tr >
                                                                    <td colspan="3" class="pt-3 fs-mons-bold">
                                                                        <b>Deposit & Credits - <?php echo $result['count_dr_positive'] ?></b>
                                                                    </td>
                                                                    <td class="text-end pt-3 fs-mons-bold fs-6"> <?php echo number_format($result['dr'],2) ?></td>
                                                                </tr>

                                                                <tr>
                                                                    <td colspan="3" class="pt-3 fs-mons-bold">
                                                                        <b> Checks and Payments - <?php echo $result['count_cr_positive'] ?></b>
                                                                    </td>
                                                                    <td class="text-end pt-3 fs-mons-bold fs-6"> <?php echo number_format($result['cr'],2) ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="3" class="pt-3 fs-mons-bold">
                                                                        <b>Total Cleared Transactions</b>
                                                                    </td>
                                                                    <?php 
                                                                    $cleared_balnce =  $result['dr'] - $result['cr'];
                                                                    $current_balance = $opening_balance+ $cleared_balnce;
                                                                    $uncleared_balane = $uncleared_entries['dr'] - $uncleared_entries['cr'];
                                                                    $final_balance = $opening_balance+ $cleared_balnce+$uncleared_balane;
                                                                    $ending_balnce = $current_balance +  $final_balance;


                                                                    ?>
                                                                    <td class="text-end pt-3 fs-mons-bold fs-6"><?php echo number_format($cleared_balnce,2) ?></td>
                                                                </tr>

                                                            </table>

                                                            <!-- Reconciliation Summary -->
                                                            <table class="w-100 mt-4">
                                                                <tr class="border-bottom border-primary fs-mons">
                                                                    <td><b class="text-primary fs-5">Cleared Balance</b></td>
                                                                    <td class="text-end text-primary fs-5"><b><?php echo number_format($current_balance,2) ?></b></td>
                                                                </tr>
                                                            </table>
                                                            <br>


                                                            <!-- Reconciliation Summary -->
                                                            <table class="w-100 mt-4">
                                                                <tr class=" fs-mons">
                                                                    <td><b class="text-primary fs-5">Uncleared Transaction</b></td>
                                                                    <td class="text-end text-primary fs-5"><b></b></td>
                                                                </tr>
                                                            </table>

                                                            <!-- Reconciliation Data -->
                                                            <table class="w-100 mt-4">
                                                                <tr>
                                                                    <td colspan="3" class="pt-3 fs-mons-bold">
                                                                        <b>Deposit & Credits - <?php echo $uncleared_entries['count_dr_positive'] ?></b>
                                                                    </td>
                                                                    <td class="text-end pt-3 fs-mons-bold fs-6"><?php echo number_format($uncleared_entries['dr'],2) ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="3" class="pt-3 fs-mons-bold">
                                                                        <b>Checks and Payments  - <?php echo $uncleared_entries['count_cr_positive'] ?></b>
                                                                    </td>
                                                                    <td class="text-end pt-3 fs-mons-bold fs-6"><?php echo number_format($uncleared_entries['cr'],2) ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="3" class="pt-3 fs-mons-bold">
                                                                        <b>Total Cleared Transactions</b>
                                                                    </td>
                                                                    <td class="text-end pt-3 fs-mons-bold fs-6"><?php echo number_format($uncleared_entries['dr'] - $uncleared_entries['cr'],2) ?></td>
                                                                </tr>

                                                            </table>
                                                            <!-- Reconciliation Summary -->
                                                            <table class="w-100 mt-4">
                                                                <tr class="border-bottom border-primary fs-mons">
                                                                    <td><b class="text-primary fs-5">Uncleared Balance</b></td>
                                                                    <td class="text-end text-primary fs-5"><b><?php echo number_format($final_balance,2) ?></b></td>
                                                                </tr>
                                                            </table>

                                                            <!-- Reconciliation Summary -->
                                                            <table class="w-100 mt-4">
                                                                <tr class=" fs-mons">
                                                                    <td><b class="text-primary fs-5">Register  Balance as of <?php echo $date; ?></b></td>
                                                                    <td class="text-end text-primary fs-5"><b><?php echo number_format($ending_balnce,2) ?></b></td>
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
