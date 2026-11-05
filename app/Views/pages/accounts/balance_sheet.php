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
                                        <h4 class="p-title">Balance Sheet</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-4">
                                                <form method="GET" accept-charset="utf-8" id="date_form">
                                                    <fieldset>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="date1">Start Date</label>
                                                                    <input class="form-control txt_nav date" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" data-date-autoclose="true" id="date1" name="date1" placeholder="Select Date" value="<?php

                                                                    use CodeIgniter\CLI\Console;

                                                                    echo !empty($date1) ? $date1 : date("Y-m-d"); ?>" />
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="date2">End Date</label>
                                                                    <input class="form-control txt_nav default-date" type="text" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd" id="date2" name="date2" placeholder="Select Date" value="<?php echo !empty($date2) ? $date2 : date('Y-m-d'); ?>" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!--  -->
                                                        <button type="submit" class="btn btn-primary  me-5px search_data" id="search_data">Generate</button>
                                                        <button type="reset" class="btn btn-default reset">Cancel</button>

                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <?php if (isset($load_data)) { ?>
                                            <div class="card">
                                                <div class="body">
                                                    <button id="print" class="btn btn-sm btn-success btn_print" onclick="printContent('print-data');" style="float: right;">
                                                        <i class="bi bi-print"></i> Print
                                                    </button>
                                                    <button id="downloadCsv" class="btn btn-sm btn-info me-2" onclick="downloadCsv('print-data');" style="float: right;">
                                                        <i class="bi bi-file-earmark-arrow-down"></i> CSV
                                                    </button>
                                                    <div class="row" id="print-data" style="padding:25px; background-color: white;">
                                                        <div class="col-md-12">
                                                            <div class="box">
                                                                <div class="box-header">
                                                                    <table width="100%">
                                                                        <tr>
                                                                            <td class="text-center">
                                                                                <h5 class="mt0"> <b>Avenir Holidays (Pvt) Ltd</b></h5>
                                                                                <p> No. 94/2, Lauries Rd, <br>Colombo 4<br>
                                                                                Tel: +94-77-395-7956, +94 112 551 880</p>
                                                                            </td>

                                                                        </tr>
                                                                        <tr>
                                                                            <td class="text-center">
                                                                                <h5 class="mt-3">BALANCE SHEET</h5>
                                                                                <span><?= $date1 . ' - ' . $date2 ?></span>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </div>
                                                                <br>


                                                                <?php
                                                                $key = "";
                                                                $tot1 = 0;
                                                                $tot2 = 0;

                                                                if (isset($income['bal'])) {
                                                                    $income_bal = $income['bal'];
                                                                } else {
                                                                    $income_bal = 0;
                                                                }


                                                                if (isset($other_income['bal'])) {
                                                                    $other_income_bal = $other_income['bal'];
                                                                } else {
                                                                    $other_income_bal = 0;
                                                                }

                                                                if (isset($expenses['bal'])) {
                                                                    $expenses_bal = $expenses['bal'];
                                                                } else {
                                                                    $expenses_bal = 0;
                                                                }

                                                                if (isset($cost_of_sale['bal'])) {
                                                                    $cost_of_sale_bal = $cost_of_sale['bal'];
                                                                } else {
                                                                    $cost_of_sale_bal = 0;
                                                                }

                                                                if(isset($other_expenses['bal'])) {
                                                                    $other_expenses_bal = $other_expenses['bal'];
                                                                } else {
                                                                    $other_expenses_bal = 0;
                                                                }


                                                                $income = $income_bal + $other_income_bal;

                                                                $expenses =   $expenses_bal + $other_expenses_bal;

                                                                $cal1 =  $income - $cost_of_sale_bal;
                                                                $cal2 =  $income - $expenses - $cost_of_sale_bal;

                                                                $cal3 = $cal1 + $cal2;


                                                                ?>

                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <table border="0" width="100%">

                                                                            <?php
                                                                            $grouped_array = array();
                                                                            $title_array = array();

                                                                            foreach ($load_data as $element) {

                                                                                $grouped_array[$element['BName']][] = $element;

                                                                                ?>


                                                                                <?php

                                                                            }
                                                                            ?>

                                                                            <?php

                                                                            foreach ($grouped_array as $key => $array) {
                                                                                if ($key == 'Equity') {
                                                                                    ?>
                                                                                    <tr class="border-bottom border-primary">
                                                                                        <td class="pt-4" colspan="2"><b class="fs-6 text-primary"><?php echo $key; ?> </b></td>
                                                                                    </tr>
                                                                                    <?php

                                                                                    foreach ($array as $type) {
                                                                                        $title_array[$type['TName']][] = $type;
                                                                                    }
                                                                                    $tot1 = 0;

                                                                                    foreach ($title_array as $k => $a) {

                                                                                        ?>
                                                                                        <tr>
                                                                                            <td class="pt-3"><b class="fs-mons-bold"><?php echo $k; ?> </b></td>
                                                                                        </tr>
                                                                                        <?php

                                                                                        $bal = 0;
                                                                                        $ts_by_url = array();
                                                                                        foreach ($a as $data) {

                                                                                            if ($key == $data['BName']) {

                                                                                                if (!array_key_exists($data['HName'], $ts_by_url))
                                                                                                    $ts_by_url[$data['HName']] = 0;
                                                                                                $ts_by_url[$data['HName']] += $data['bal'];
                                                                                            }
                                                                                        }
                                                                                        $total = 0;
                                                                                        foreach ($ts_by_url as $n => $e) {
                                                                                            $total = $total + $e;
                                                                                            ?>
                                                                                            <tr>
                                                                                                <td class="fs-mons">
                                                                                                    <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                        <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                    </a>
                                                                                                </td>
                                                                                                <td class="text-end fs-mons text-black"><?php echo number_format($e, 2); ?></td>
                                                                                            </tr>

                                                                                            <?php
                                                                                        }
                                                                                        ?>
                                                                                        <tr class="border-top">
                                                                                            <td></td>
                                                                                            <td class="text-end fs-mons-bold"><b><?php echo number_format($total, 2); ?><b></td>
                                                                                            </tr>
                                                                                            <?php
                                                                                            $tot1 = $tot1 + $total;
                                                                                        }
                                                                                        ?>
                                                                                        <tr>
                                                                                            <td class="fs-mons-bold pt-3">Total Equity</td>
                                                                                            <td class="text-end fs-mons-bold fs-6 pt-3"><b><?php echo number_format($tot1, 2); ?><b></td>
                                                                                            </tr>
                                                                                            <?php
                                                                                        }
                                                                                    }
                                                                                    ?>




                                                                                </table>
                                                                            </div>
                                                                            <br><br>

                                                                            <div class="col-md-12">
                                                                                <table border="0" width="100%">

                                                                                    <?php
                                                                                    $grouped_array = array();
                                                                                    $title_array = array();

                                                                                    foreach ($load_data as $element) {

                                                                                        $grouped_array[$element['BName']][] = $element;

                                                                                        ?>


                                                                                        <?php

                                                                                    }
                                                                                    ?>

                                                                                    <?php

                                                                                    foreach ($grouped_array as $key => $array) {
                                                                                        if ($key == 'Asset') {
                                                                                            ?>
                                                                                            <tr class="border-bottom border-primary">
                                                                                                <td class="pt-4" colspan="2"><b class="fs-6 text-primary"><?php echo $key; ?> </b></td>
                                                                                            </tr>
                                                                                            <?php

                                                                                            foreach ($array as $type) {
                                                                                                $title_array[$type['TName']][] = $type;
                                                                                            }
                                                                                            $tot = 0;

                                                                                            foreach ($title_array as $k => $a) {

                                                                                                ?>
                                                                                                <tr>
                                                                                                    <td class="pt-3"><b class="fs-mons-bold"><?php echo $k; ?> </b></td>
                                                                                                </tr>
                                                                                                <?php

                                                                                                $bal = 0;
                                                                                                $ts_by_url = array();
                                                                                                foreach ($a as $data) {

                                                                                                    if ($key == $data['BName']) {

                                                                                                        if (!array_key_exists($data['HName'], $ts_by_url)){
                                                                                                            $ts_by_url[$data['HName']] = 0;
                                                                                                        }
                                                                                                        $ts_by_url[$data['HName']] += $data['bal'];
                                                                                                        // if($data['HName'] == "Bank Accounts"){
                                                                                                        //     echo $data['bal'];
                                                                                                        // echo '<br>';
                                                                                                        // }
                                                                                                    }
                                                                                                }
                                                                                                $total = 0;
                                                                                                foreach ($ts_by_url as $n => $e) {
                                                                                                    $total = $total + $e;
                                                                                                    ?>
                                                                                                    <tr>
                                                                                                        <td class="fs-mons">
                                                                                                            <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                                <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                            </a>
                                                                                                        </td>
                                                                                                        <td class="text-end fs-mons text-black"><?php echo number_format($e, 2); ?></td>
                                                                                                    </tr>

                                                                                                    <?php
                                                                                                }
                                                                                                ?>
                                                                                                <tr class="border-top">
                                                                                                    <td></td>
                                                                                                    <td class="text-end fs-mons-bold"><b><?php echo number_format($total, 2); ?><b></td>
                                                                                                    </tr>
                                                                                                    <?php
                                                                                                    $tot = $tot + $total;
                                                                                                }
                                                                                                ?>
                                                                                                <tr>
                                                                                                    <td class="fs-mons-bold pt-3">Total Assets</td>
                                                                                                    <td class="text-end fs-mons-bold fs-6 pt-3"><b><?php echo number_format($tot, 2); ?><b></td>
                                                                                                    </tr>
                                                                                                    <?php
                                                                                                }
                                                                                            }
                                                                                            ?>




                                                                                        </table>
                                                                                    </div>
                                                                                    <br>
                                                                                    <br>

                                                                                    <div class="col-md-12">
                                                                                        <table border="0" width="100%">

                                                                                            <?php
                                                                                            $grouped_array = array();
                                                                                            $title_array = array();

                                                                                            foreach ($load_data as $element) {
                                                                                                $grouped_array[$element['BName']][] = $element;
                                                                                                ?>
                                                                                                <?php
                                                                                            }
                                                                                            ?>

                                                                                            <?php

                                                                                            $tot2 = 0;
                                                                                            foreach ($grouped_array as $key => $array) {

                                                                                                if ($key == 'Liability') {
                                                                                                    ?>
                                                                                                    <tr class="border-bottom border-primary">
                                                                                                        <td class="pt-4" colspan="2"><b class="fs-6 text-primary"><?php echo $key; ?> </b></td>
                                                                                                    </tr>
                                                                                                    <?php

                                                                                                    foreach ($array as $type) {
                                                                                                        $title_array[$type['TName']][] = $type;
                                                                                                    }
                                                                                                    $tot2 = 0;

                                                                                                    foreach ($title_array as $k => $a) {

                                                                                                        ?>
                                                                                                        <tr>
                                                                                                            <td class="pt-3"><b class="fs-mons-bold"><?php echo $k; ?> </b></td>
                                                                                                        </tr>
                                                                                                        <?php

                                                                                                        $bal = 0;
                                                                                                        $ts_by_url = array();
                                                                                                        foreach ($a as $data) {
                                                                                                            if ($key == $data['BName']) {
                                                                                                                if (!array_key_exists($data['HName'], $ts_by_url))
                                                                                                                    $ts_by_url[$data['HName']] = 0;
                                                                                                                $ts_by_url[$data['HName']] += $data['bal'];
                                                                                                            }
                                                                                                        }
                                                                                                        $total = 0;
                                                                                                        foreach ($ts_by_url as $n => $e) {
                                                                                                            $total = $total + $e;
                                                                                                            ?>
                                                                                                            <tr>
                                                                                                                <td class="fs-mons">
                                                                                                                    <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                                        <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                                    </a>
                                                                                                                </td>
                                                                                                                <td class="text-end fs-mons text-black"><?php echo number_format($e, 2); ?></td>
                                                                                                            </tr>

                                                                                                            <?php
                                                                                                        }
                                                                                                        ?>
                                                                                                        <tr class="border-top">
                                                                                                            <td></td>
                                                                                                            <td class="text-end fs-mons-bold"><b><?php echo number_format($total, 2); ?><b></td>
                                                                                                            </tr>
                                                                                                            <?php
                                                                                                            $tot2 = $tot2 + $total;
                                                                                        // $tot2 =  $total;
                                                                                                        }
                                                                                                        ?>
                                                                                                        <tr>
                                                                                                            <td class="fs-mons-bold pt-3">Total Liability</td>
                                                                                                            <td class="text-end fs-mons-bold fs-6 pt-3"><b><?php echo number_format($tot2, 2); ?><b></td>
                                                                                                            </tr>

                                                                                                            <?php
                                                                                                        }
                                                                                                    }
                                                                                                    ?>

                                                                                                    <table class="mt-4" width="100%">
                                                                                                        <tr class="border-top border-dark">
                                                                                                            <td class="pt-2 fs-mons-bold">Total ( Equity + Liability + PNL )</td>
                                                                                                            <td class="pt-2 text-end fs-mons-bold fs-6"><b><?php echo number_format(($tot1 + $tot2 + $cal2), 2) ?></b></td>
                                                                                                        </tr>
                                                                                                    </table>

                                                                                                </table>

                                                                                                <br>

                                                                                            </div>
                                                                                        </div>

                                                                                    </div>






                                                                                </div>

                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php } else {
                                                                    if (isset($is_search) && $is_search) { ?>
                                                                        <div class="alert alert-danger" role="alert">
                                                                            <strong>Sorry!</strong> No data found.
                                                                        </div>
                                                                    <?php }
                                                                } ?>
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
                            <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
                            <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
                            <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
                            <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
                            <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>

                            <script>
                                $('.js-example-basic-single').select2({
                                    placeholder: " Select",
                                    allowClear: true
                                });

                                function printContent(el) {
                                    var restorepage = $('body').html();
                                    var printcontent = $('#' + el).clone();
                                    $('body').empty().html(printcontent);
                                    window.print();
                                    $('body').html(restorepage);
                                }

        //.reset on click
        $('.reset').on('click', function() {
            window.location.href = "<?php echo base_url('accounts/balance_sheet'); ?>";
        });

        function downloadCsv(el) {
            // Extract the content to be converted into CSV
            var table = document.getElementById(el);
            var rows = table.querySelectorAll('tr');

            // Initialize CSV content
            let csvContent = '';

            // Loop through table rows and create CSV rows
            rows.forEach(row => {
                let rowData = [];
                let cols = row.querySelectorAll('td, th'); // Include table headers
                cols.forEach(col => {
                    let cellText = col.innerText.trim(); // Get text content

                    // Check if the cell contains a number with commas and format it
                    if (!isNaN(cellText.replace(/,/g, ''))) {
                        let number = parseFloat(cellText.replace(/,/g, '')); // Remove commas and parse as float
                        cellText = number.toFixed(2); // Keep two decimal places
                    }
                    // console.log(cellText);

                    rowData.push(cellText);
                });
                csvContent += rowData.join(',') + '\n'; // Join columns with commas
            });

            // Create a blob and download the CSV
            let blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            let url = URL.createObjectURL(blob);

            // Create a temporary link to trigger the download
            let link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', 'export_data.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link); // Clean up
        }


    </script>
    <?= view('pages/accounts/balance_details'); ?>
</body>




</html>