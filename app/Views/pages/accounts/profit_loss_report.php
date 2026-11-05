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
                                        <h4 class="p-title">Profit and Loss Report</h4>
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
                                                    <button id="print" class="btn btn-sm btn-success btn_print me-2" onclick="printContent('print-data');" style="float: right;">
                                                        <i class="bi bi-print"></i> Print
                                                    </button>
                                                    <button id="downloadCsv" class="btn btn-sm btn-info me-2" onclick="downloadCsv('print-data');" style="float: right;">
                                                        <i class="bi bi-file-earmark-arrow-down"></i> CSV
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
                                                                                <h5 class="mt-3">PROFIT & LOSS REPORT</h5>
                                                                                <span><?= $date1 . ' - ' . $date2 ?></span>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                    <?php if (isset($load_data)) { ?>
                                                                        <!-- Income -->
                                                                        <table class="w-100">
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
                                                                            $income_available = false;
                                                                            $total_income = 0;
                                                                            foreach ($grouped_array as $key => $array) {
                                                                                if ($key == 'Income') {
                                                                                    $income_available = true;
                                                                            ?>
                                                                                    <!-- <tr class="border-bottom border-dark">
                                                                        <td colspan="2"><span class="text-dark"><?php echo $key; ?> </span></td>
                                                                    </tr> -->
                                                                                    <?php

                                                                                    foreach ($array as $type) {
                                                                                        $title_array[$type['TName']][] = $type;
                                                                                    }
                                                                                    $tot = 0;

                                                                                    foreach ($title_array as $k => $a) {
                                                                                    ?>
                                                                                        <tr class="border-bottom w-100">
                                                                                            <td class="pt-3 fs-mons-bold text-secondary"><b><?php echo $k; ?> </b></td>
                                                                                            <td></td>
                                                                                            <td></td>
                                                                                            <td class="pt-3 text-end fs-mons-bold text-secondary"></td>
                                                                                        </tr>
                                                                                        <?php

                                                                                        $bal = 0;
                                                                                        $ts_by_url = array();
                                                                                        foreach ($a as $data) {
                                                                                            if ($key == $data['BName']) {

                                                                                                if (!array_key_exists($data['HName'], $ts_by_url))
                                                                                                    $ts_by_url[$data['HName']] = 0;
                                                                                                $ts_by_url[$data['HName']] = [
                                                                                                    'bal' => $data['bal'],
                                                                                                    'Dr' => $data['DR'],
                                                                                                    'Cr' => $data['CR']
                                                                                                ];
                                                                                            }
                                                                                        }
                                                                                        $total = 0;
                                                                                        foreach ($ts_by_url as $n => $e) {
                                                                                            $total = $total + $e['bal'];
                                                                                        ?>
                                                                                            <tr class="w-100">
                                                                                                <td class="fs-mons">
                                                                                                    <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                        <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                    </a>
                                                                                                </td>
                                                                                                <td></td>
                                                                                                <td></td>
                                                                                                <td class="text-end fs-mons-bold"><?php echo number_format($e['bal'], 2); ?></td>
                                                                                            </tr>

                                                                                        <?php
                                                                                        }
                                                                                        ?>
                                                                                    <?php
                                                                                        $tot = $tot + $total;
                                                                                        $total_income = $tot;
                                                                                    }
                                                                                    ?>
                                                                                    <tr>
                                                                                        <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                            <b>Total Income</b>
                                                                                        </td>
                                                                                        <td class="text-end pt-3 fs-mons-bold fs-6"><?php echo number_format($tot, 2); ?></td>
                                                                                    </tr>
                                                                                <?php
                                                                                }
                                                                            }
                                                                            if (!$income_available) {
                                                                                ?>
                                                                                <!-- <tr class="border-bottom border-dark">
                                                                    <td colspan="2"><b class="fs-6 text-dark">Income</b></td>
                                                                </tr> -->
                                                                                <tr>
                                                                                    <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                        <b>Total Income</b>
                                                                                    </td>
                                                                                    <td class="text-end pt-3 fs-mons-bold fs-6">0.00</td>
                                                                                </tr>
                                                                            <?php
                                                                            }
                                                                            ?>
                                                                        </table>
                                                                        <!-- Cost Of Sale -->
                                                                        <table class="w-100 mt-4">
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
                                                                            $cost_of_sale_available = false;
                                                                            $total_cost_of_sale = 0;
                                                                            foreach ($grouped_array as $key => $array) {
                                                                                if ($key == 'Cost Of Sale') {
                                                                                    $cost_of_sale_available = true;
                                                                            ?>
                                                                                    <!-- <tr class="border-bottom border-dark">
                                                                        <td colspan="2"><span class="text-dark"><?php echo $key; ?> </span></td>
                                                                    </tr> -->
                                                                                    <?php

                                                                                    foreach ($array as $type) {
                                                                                        $title_array[$type['TName']][] = $type;
                                                                                    }
                                                                                    $tot = 0;

                                                                                    foreach ($title_array as $k => $a) {
                                                                                    ?>
                                                                                        <tr class="border-bottom w-100">
                                                                                            <td class="pt-3 fs-mons-bold text-secondary"><b><?php echo $k; ?> </b></td>
                                                                                            <td></td>
                                                                                            <td></td>
                                                                                            <td class="pt-3 text-end fs-mons-bold text-secondary"></td>
                                                                                        </tr>
                                                                                        <?php

                                                                                        $bal = 0;
                                                                                        $ts_by_url = array();
                                                                                        foreach ($a as $data) {
                                                                                            if ($key == $data['BName']) {

                                                                                                if (!array_key_exists($data['HName'], $ts_by_url))
                                                                                                    $ts_by_url[$data['HName']] = 0;
                                                                                                $ts_by_url[$data['HName']] = [
                                                                                                    'bal' => $data['bal'],
                                                                                                    'Dr' => $data['DR'],
                                                                                                    'Cr' => $data['CR']
                                                                                                ];
                                                                                            }
                                                                                        }
                                                                                        $total = 0;
                                                                                        foreach ($ts_by_url as $n => $e) {
                                                                                            $total = $total + $e['bal'];
                                                                                        ?>
                                                                                            <tr class="w-100">
                                                                                                <td class="fs-mons">
                                                                                                    <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                        <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                    </a>
                                                                                                </td>
                                                                                                <td></td>
                                                                                                <td></td>
                                                                                                <td class="text-end fs-mons-bold"><?php echo number_format($e['bal'], 2); ?></td>
                                                                                            </tr>

                                                                                        <?php
                                                                                        }
                                                                                        ?>
                                                                                    <?php
                                                                                        $tot = $tot + $total;
                                                                                        $total_cost_of_sale = $tot;
                                                                                    }
                                                                                    ?>
                                                                                    <tr>
                                                                                        <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                            <b>Total Cost Of Sale</b>
                                                                                        </td>
                                                                                        <td class="text-end pt-3 fs-mons-bold fs-6"><?php echo number_format($tot, 2); ?></td>
                                                                                    </tr>
                                                                                <?php
                                                                                }
                                                                            }
                                                                            if (!$cost_of_sale_available) {
                                                                                ?>
                                                                                <!-- <tr class="border-bottom border-dark">
                                                                    <td colspan="2"><span class="text-dark">Cost Of Sale</span></td>
                                                                </tr> -->
                                                                                <tr>
                                                                                    <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                        <b>Total Cost Of Sale</b>
                                                                                    </td>
                                                                                    <td class="text-end pt-3 fs-mons-bold fs-6">0.00</td>
                                                                                </tr>
                                                                            <?php
                                                                            }
                                                                            ?>
                                                                        </table>
                                                                        <!-- Expenses -->
                                                                        <table class="w-100 mt-4">
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
                                                                            $expenses_available = false;
                                                                            $total_expenses = 0;
                                                                            foreach ($grouped_array as $key => $array) {
                                                                                if ($key == 'Expenses') {
                                                                                    $expenses_available = true;
                                                                            ?>
                                                                                    <!-- <tr class="border-bottom border-dark">
                                                                        <td colspan="2"><span class="text-dark"><?php echo $key; ?> </span></td>
                                                                    </tr> -->
                                                                                    <?php

                                                                                    foreach ($array as $type) {
                                                                                        $title_array[$type['TName']][] = $type;
                                                                                    }
                                                                                    $tot = 0;

                                                                                    foreach ($title_array as $k => $a) {
                                                                                    ?>
                                                                                        <tr class="border-bottom w-100">
                                                                                            <td class="pt-3 fs-mons-bold text-secondary"><b><?php echo $k; ?> </b></td>
                                                                                            <td></td>
                                                                                            <td></td>
                                                                                            <td class="pt-3 text-end fs-mons-bold text-secondary"></td>
                                                                                        </tr>
                                                                                        <?php

                                                                                        $bal = 0;
                                                                                        $ts_by_url = array();
                                                                                        foreach ($a as $data) {
                                                                                            if ($key == $data['BName']) {

                                                                                                if (!array_key_exists($data['HName'], $ts_by_url))
                                                                                                    $ts_by_url[$data['HName']] = 0;
                                                                                                $ts_by_url[$data['HName']] = [
                                                                                                    'bal' => $data['bal'],
                                                                                                    'Dr' => $data['DR'],
                                                                                                    'Cr' => $data['CR']
                                                                                                ];
                                                                                            }
                                                                                        }
                                                                                        $total = 0;
                                                                                        foreach ($ts_by_url as $n => $e) {
                                                                                            $total = $total + $e['bal'];
                                                                                        ?>
                                                                                            <tr class="w-100">
                                                                                                <td class="fs-mons">
                                                                                                    <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                        <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                    </a>
                                                                                                </td>
                                                                                                <td></td>
                                                                                                <td></td>
                                                                                                <td class="text-end fs-mons-bold"><?php echo number_format($e['bal'], 2); ?></td>
                                                                                            </tr>

                                                                                        <?php
                                                                                        }
                                                                                        ?>
                                                                                    <?php
                                                                                        $tot = $tot + $total;
                                                                                        $total_expenses = $tot;
                                                                                    }
                                                                                    ?>
                                                                                    <tr>
                                                                                        <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                            <b>Total Expenses</b>
                                                                                        </td>
                                                                                        <td class="text-end pt-3 fs-mons-bold fs-6"><?php echo number_format($tot, 2); ?></td>
                                                                                    </tr>
                                                                                <?php
                                                                                }
                                                                            }
                                                                            if (!$expenses_available) {
                                                                                ?>
                                                                                <!-- <tr class="border-bottom border-dark">
                                                                    <td colspan="2"><span class="text-dark">Expenses</span></td>
                                                                </tr> -->
                                                                                <tr>
                                                                                    <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                        <b>Total Expenses</b>
                                                                                    </td>
                                                                                    <td class="text-end pt-3 fs-mons-bold fs-6">0.00</td>
                                                                                </tr>
                                                                            <?php
                                                                            }
                                                                            ?>
                                                                        </table>
                                                                        <!-- GROSS PROFIT -->
                                                                        <table class="w-100 mt-4">
                                                                            <tr class="border-bottom border-primary fs-mons">
                                                                                <?php
                                                                                $gross_profit = ($total_income - $total_cost_of_sale) - $total_expenses;
                                                                                ?>
                                                                                <td><b class="text-primary fs-5">Gross Profit</b></td>
                                                                                <td class="text-end text-primary fs-5"><b><?php echo number_format($gross_profit, 2); ?></b></td>
                                                                            </tr>
                                                                        </table>
                                                                        <!-- Other Income -->
                                                                        <table class="w-100 mt-4">
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
                                                                            $other_income_available = false;
                                                                            $total_other_income = 0;
                                                                            foreach ($grouped_array as $key => $array) {
                                                                                if ($key == 'Other Income') {
                                                                                    $other_income_available = true;
                                                                            ?>
                                                                                    <!-- <tr class="border-bottom border-dark">
                                                                        <td colspan="2"><span class="text-dark"><?php echo $key; ?> </span></td>
                                                                    </tr> -->
                                                                                    <?php

                                                                                    foreach ($array as $type) {
                                                                                        $title_array[$type['TName']][] = $type;
                                                                                    }
                                                                                    $tot = 0;

                                                                                    foreach ($title_array as $k => $a) {
                                                                                    ?>
                                                                                        <tr class="border-bottom w-100">
                                                                                            <td class="pt-3 fs-mons-bold text-secondary"><b><?php echo $k; ?> </b></td>
                                                                                            <td></td>
                                                                                            <td></td>
                                                                                            <td class="pt-3 text-end fs-mons-bold text-secondary"></td>
                                                                                        </tr>
                                                                                        <?php

                                                                                        $bal = 0;
                                                                                        $ts_by_url = array();
                                                                                        foreach ($a as $data) {
                                                                                            if ($key == $data['BName']) {

                                                                                                if (!array_key_exists($data['HName'], $ts_by_url))
                                                                                                    $ts_by_url[$data['HName']] = 0;
                                                                                                $ts_by_url[$data['HName']] = [
                                                                                                    'bal' => $data['bal'],
                                                                                                    'Dr' => $data['DR'],
                                                                                                    'Cr' => $data['CR']
                                                                                                ];
                                                                                            }
                                                                                        }
                                                                                        $total = 0;
                                                                                        foreach ($ts_by_url as $n => $e) {
                                                                                            $total = $total + $e['bal'];
                                                                                        ?>
                                                                                            <tr class="w-100">
                                                                                                <td class="fs-mons">
                                                                                                    <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                        <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                    </a>
                                                                                                </td>
                                                                                                <td></td>
                                                                                                <td></td>
                                                                                                <td class="text-end fs-mons-bold"><?php echo number_format($e['bal'], 2); ?></td>
                                                                                            </tr>

                                                                                        <?php
                                                                                        }
                                                                                        ?>
                                                                                    <?php
                                                                                        $tot = $tot + $total;
                                                                                        $total_other_income = $tot;
                                                                                    }
                                                                                    ?>
                                                                                    <tr>
                                                                                        <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                            <b>Total Other Income</b>
                                                                                        </td>
                                                                                        <td class="text-end pt-3 fs-mons-bold fs-6"><?php echo number_format($tot, 2); ?></td>
                                                                                    </tr>
                                                                                <?php
                                                                                }
                                                                            }
                                                                            if (!$other_income_available) {
                                                                                ?>
                                                                                <!-- <tr class="border-bottom border-dark">
                                                                    <td colspan="2"><span class="text-dark">Other Income</span></td>
                                                                </tr> -->
                                                                                <tr>
                                                                                    <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                        <b>Total Other Income</b>
                                                                                    </td>
                                                                                    <td class="text-end pt-3 fs-mons-bold fs-6">0.00</td>
                                                                                </tr>
                                                                            <?php
                                                                            }
                                                                            ?>
                                                                        </table>
                                                                        <!-- Other Expenses -->
                                                                        <table class="w-100 mt-4">
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
                                                                            $other_expenses_available = false;
                                                                            $total_other_expenses = 0;
                                                                            foreach ($grouped_array as $key => $array) {
                                                                                if ($key == 'Other Expenses') {
                                                                                    $other_expenses_available = true;
                                                                            ?>
                                                                                    <!-- <tr class="border-bottom border-dark">
                                                                        <td colspan="2"><span class="text-dark"><?php echo $key; ?> </span></td>
                                                                    </tr> -->
                                                                                    <?php

                                                                                    foreach ($array as $type) {
                                                                                        $title_array[$type['TName']][] = $type;
                                                                                    }
                                                                                    $tot = 0;

                                                                                    foreach ($title_array as $k => $a) {
                                                                                    ?>
                                                                                        <tr class="border-bottom w-100">
                                                                                            <td class="pt-3 fs-mons-bold text-secondary"><b><?php echo $k; ?> </b></td>
                                                                                            <td></td>
                                                                                            <td></td>
                                                                                            <td class="pt-3 text-end fs-mons-bold text-secondary"></td>
                                                                                        </tr>
                                                                                        <?php

                                                                                        $bal = 0;
                                                                                        $ts_by_url = array();
                                                                                        foreach ($a as $data) {
                                                                                            if ($key == $data['BName']) {

                                                                                                if (!array_key_exists($data['HName'], $ts_by_url))
                                                                                                    $ts_by_url[$data['HName']] = 0;
                                                                                                $ts_by_url[$data['HName']] = [
                                                                                                    'bal' => $data['bal'],
                                                                                                    'Dr' => $data['DR'],
                                                                                                    'Cr' => $data['CR']
                                                                                                ];
                                                                                            }
                                                                                        }
                                                                                        $total = 0;
                                                                                        foreach ($ts_by_url as $n => $e) {
                                                                                            $total = $total + $e['bal'];
                                                                                        ?>
                                                                                            <tr class="w-100">
                                                                                                <td class="fs-mons">
                                                                                                    <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                        <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                    </a>
                                                                                                </td>
                                                                                                <td></td>
                                                                                                <td></td>
                                                                                                <td class="text-end fs-mons-bold"><?php echo number_format($e['bal'], 2); ?></td>
                                                                                            </tr>

                                                                                        <?php
                                                                                        }
                                                                                        ?>
                                                                                    <?php
                                                                                        $tot = $tot + $total;
                                                                                        $total_other_expenses = $tot;
                                                                                    }
                                                                                    ?>
                                                                                    <tr>
                                                                                        <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                            <b>Total Other Expenses</b>
                                                                                        </td>
                                                                                        <td class="text-end pt-3 fs-mons-bold fs-6"><?php echo number_format($tot, 2); ?></td>
                                                                                    </tr>
                                                                                <?php
                                                                                }
                                                                            }
                                                                            if (!$other_expenses_available) {
                                                                                ?>
                                                                                <!-- <tr class="border-bottom border-dark">
                                                                    <td colspan="2"><span class="text-dark">Other Expenses</span></td>
                                                                </tr> -->
                                                                                <tr>
                                                                                    <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                        <b>Total Other Expenses</b>
                                                                                    </td>
                                                                                    <td class="text-end pt-3 fs-mons-bold fs-6">0.00</td>
                                                                                </tr>
                                                                            <?php
                                                                            }
                                                                            ?>
                                                                        </table>
                                                                        <!-- NET PROFIT -->
                                                                        <table class="w-100 mt-4">
                                                                            <tr class="border-bottom border-primary fs-mons">
                                                                                <?php
                                                                                $net_profit = ($gross_profit + $total_other_income) - $total_other_expenses;
                                                                                ?>
                                                                                <td><b class="text-primary fs-5">Net Profit</b></td>
                                                                                <td class="text-end text-primary fs-5"><b><?php echo number_format($net_profit, 2); ?></b></td>
                                                                            </tr>
                                                                        </table>
                                                                    <?php } ?>
                                                                </div>
                                                                <br>
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
            window.location.href = "<?php echo base_url('accounts/profit_loss'); ?>";
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