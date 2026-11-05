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
                                        <h4 class="p-title">Trial Balance Report</h4>
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
                                                                                <h5 class="mt-3">TRIAL BALANCE REPORT</h5>
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


                                                                $income = $income_bal + $other_income_bal;

                                                                $expenses =   $expenses_bal + $other_income_bal;

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

                                                                            foreach ($grouped_array as $key => $array)
                                                                                if ($key == 'Equity') { {
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
                                                                                        <tr class="border-bottom">
                                                                                            <td class="pt-3 fs-mons-bold text-secondary"><b><?php echo $k; ?> </b></td>
                                                                                            <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Debit </b></td>
                                                                                            <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Credit </b></td>
                                                                                            <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Balance </b></td>
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
                                                                                            <tr>
                                                                                                <td class="fs-mons">
                                                                                                    <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                        <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                    </a>
                                                                                                </td>
                                                                                                <td class="text-end fs-mons"><?php echo  number_format($e['Dr'], 2); ?></td>
                                                                                                <td class="text-end fs-mons"><?php echo  number_format($e['Cr'], 2); ?></td>
                                                                                                <td class="text-end fs-mons"><?php echo  number_format($e['bal'], 2); ?></td>
                                                                                            </tr>

                                                                                            <?php
                                                                                        }
                                                                                        ?>
                                                                                        <tr class="border-top">
                                                                                            <td colspan="3"></td>
                                                                                            <td class="text-end fs-mons"><b><?php echo number_format($total, 2); ?><b></td>
                                                                                            </tr>
                                                                                            <?php
                                                                                            $tot = $tot + $total;
                                                                                        }
                                                                                    }
                                                                                    ?>
                                                                                    <tr>
                                                                                        <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                            <b>Total Equity</b>
                                                                                        </td>
                                                                                        <td class="text-end pt-3 fs-mons-bold fs-6"><?php echo number_format($tot, 2); ?></td>
                                                                                    </tr>
                                                                                    <?php

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

                                                                                foreach ($grouped_array as $key => $array)
                                                                                    if ($key == 'Asset') { {
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
                                                                                            <tr class="border-bottom">
                                                                                                <td class="pt-3 fs-mons-bold text-secondary"><b><?php echo $k; ?> </b></td>
                                                                                                <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Debit </b></td>
                                                                                                <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Credit </b></td>
                                                                                                <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Balance </b></td>
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
                                                                                                <tr>
                                                                                                    <td class="fs-mons">
                                                                                                        <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                            <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                        </a>
                                                                                                    </td>
                                                                                                    <td class="text-end fs-mons"><?php echo  number_format($e['Dr'], 2); ?></td>
                                                                                                    <td class="text-end fs-mons"><?php echo  number_format($e['Cr'], 2); ?></td>
                                                                                                    <td class="text-end fs-mons"><?php echo  number_format($e['bal'], 2); ?></td>
                                                                                                </tr>

                                                                                                <?php
                                                                                            }
                                                                                            ?>
                                                                                            <tr class="border-top">
                                                                                                <td colspan="3"></td>
                                                                                                <td class="text-end fs-mons"><b><?php echo number_format($total, 2); ?><b></td>
                                                                                                </tr>
                                                                                                <?php
                                                                                                $tot = $tot + $total;
                                                                                            }
                                                                                        }
                                                                                        ?>
                                                                                        <tr>
                                                                                            <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                                <b>Total Asset</b>
                                                                                            </td>
                                                                                            <td class="text-end pt-3 fs-mons-bold fs-6"><?php echo number_format($tot, 2); ?></td>
                                                                                        </tr>
                                                                                        <?php

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

                                                                                    foreach ($grouped_array as $key => $array)
                                                                                        if ($key == 'Cost Of Sale') { {
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
                                                                                                <tr class="border-bottom">
                                                                                                    <td class="pt-3 fs-mons-bold text-secondary"><b><?php echo $k; ?> </b></td>
                                                                                                    <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Debit </b></td>
                                                                                                    <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Credit </b></td>
                                                                                                    <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Balance </b></td>
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
                                                                                                    <tr>
                                                                                                        <td class="fs-mons">
                                                                                                            <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                                <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                            </a>
                                                                                                        </td>
                                                                                                        <td class="text-end fs-mons"><?php echo  number_format($e['Dr'], 2); ?></td>
                                                                                                        <td class="text-end fs-mons"><?php echo  number_format($e['Cr'], 2); ?></td>
                                                                                                        <td class="text-end fs-mons"><?php echo  number_format($e['bal'], 2); ?></td>
                                                                                                    </tr>

                                                                                                    <?php
                                                                                                }
                                                                                                ?>
                                                                                                <tr class="border-top">
                                                                                                    <td colspan="3"></td>
                                                                                                    <td class="text-end fs-mons"><b><?php echo number_format($total, 2); ?><b></td>
                                                                                                    </tr>
                                                                                                    <?php
                                                                                                    $tot = $tot + $total;
                                                                                                }
                                                                                            }
                                                                                            ?>
                                                                                            <tr>
                                                                                                <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                                    <b>Total Cost of Sale</b>
                                                                                                </td>
                                                                                                <td class="text-end pt-3 fs-mons-bold fs-6"><?php echo number_format($tot, 2); ?></td>
                                                                                            </tr>
                                                                                            <?php

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

                                                                                        foreach ($grouped_array as $key => $array)
                                                                                            if ($key == 'Income') { {
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
                                                                                                    <tr class="border-bottom">
                                                                                                        <td class="pt-3 fs-mons-bold text-secondary"><b><?php echo $k; ?> </b></td>
                                                                                                        <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Debit </b></td>
                                                                                                        <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Credit </b></td>
                                                                                                        <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Balance </b></td>
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
                                                                                                        <tr>
                                                                                                            <td class="fs-mons">
                                                                                                                <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                                    <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                                </a>
                                                                                                            </td>
                                                                                                            <td class="text-end fs-mons"><?php echo  number_format($e['Dr'], 2); ?></td>
                                                                                                            <td class="text-end fs-mons"><?php echo  number_format($e['Cr'], 2); ?></td>
                                                                                                            <td class="text-end fs-mons"><?php echo  number_format($e['bal'], 2); ?></td>
                                                                                                        </tr>

                                                                                                        <?php
                                                                                                    }
                                                                                                    ?>
                                                                                                    <tr class="border-top">
                                                                                                        <td colspan="3"></td>
                                                                                                        <td class="text-end fs-mons"><b><?php echo number_format($total, 2); ?><b></td>
                                                                                                        </tr>
                                                                                                        <?php
                                                                                                        $tot = $tot + $total;
                                                                                                    }
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

                                                                                            foreach ($grouped_array as $key => $array)
                                                                                                if ($key == 'Other Income') { {
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
                                                                                                        <tr class="border-bottom">
                                                                                                            <td class="pt-3 fs-mons-bold text-secondary"><b><?php echo $k; ?> </b></td>
                                                                                                            <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Debit </b></td>
                                                                                                            <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Credit </b></td>
                                                                                                            <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Balance </b></td>
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
                                                                                                            <tr>
                                                                                                                <td class="fs-mons">
                                                                                                                    <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                                        <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                                    </a>
                                                                                                                </td>
                                                                                                                <td class="text-end fs-mons"><?php echo  number_format($e['Dr'], 2); ?></td>
                                                                                                                <td class="text-end fs-mons"><?php echo  number_format($e['Cr'], 2); ?></td>
                                                                                                                <td class="text-end fs-mons"><?php echo  number_format($e['bal'], 2); ?></td>
                                                                                                            </tr>

                                                                                                            <?php
                                                                                                        }
                                                                                                        ?>
                                                                                                        <tr class="border-top">
                                                                                                            <td colspan="3"></td>
                                                                                                            <td class="text-end fs-mons"><b><?php echo number_format($total, 2); ?><b></td>
                                                                                                            </tr>
                                                                                                            <?php
                                                                                                            $tot = $tot + $total;
                                                                                                        }
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

                                                                                                foreach ($grouped_array as $key => $array)
                                                                                                    if ($key == 'Expenses') { {
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
                                                                                                            <tr class="border-bottom">
                                                                                                                <td class="pt-3 fs-mons-bold text-secondary"><b><?php echo $k; ?> </b></td>
                                                                                                                <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Debit </b></td>
                                                                                                                <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Credit </b></td>
                                                                                                                <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Balance </b></td>
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
                                                                                                                <tr>
                                                                                                                    <td class="fs-mons">
                                                                                                                        <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                                            <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                                        </a>
                                                                                                                    </td>
                                                                                                                    <td class="text-end fs-mons"><?php echo  number_format($e['Dr'], 2); ?></td>
                                                                                                                    <td class="text-end fs-mons"><?php echo  number_format($e['Cr'], 2); ?></td>
                                                                                                                    <td class="text-end fs-mons"><?php echo  number_format($e['bal'], 2); ?></td>
                                                                                                                </tr>

                                                                                                                <?php
                                                                                                            }
                                                                                                            ?>
                                                                                                            <tr class="border-top">
                                                                                                                <td colspan="3"></td>
                                                                                                                <td class="text-end fs-mons"><b><?php echo number_format($total, 2); ?><b></td>
                                                                                                                </tr>
                                                                                                                <?php
                                                                                                                $tot = $tot + $total;
                                                                                                            }
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

                                                                                                    foreach ($grouped_array as $key => $array)
                                                                                                        if ($key == 'Other Expenses') { {
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
                                                                                                                <tr class="border-bottom">
                                                                                                                    <td class="pt-3 fs-mons-bold text-secondary"><b><?php echo $k; ?> </b></td>
                                                                                                                    <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Debit </b></td>
                                                                                                                    <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Credit </b></td>
                                                                                                                    <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Balance </b></td>
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
                                                                                                                    <tr>
                                                                                                                        <td class="fs-mons">
                                                                                                                            <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                                                <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                                            </a>
                                                                                                                        </td>
                                                                                                                        <td class="text-end fs-mons"><?php echo  number_format($e['Dr'], 2); ?></td>
                                                                                                                        <td class="text-end fs-mons"><?php echo  number_format($e['Cr'], 2); ?></td>
                                                                                                                        <td class="text-end fs-mons"><?php echo  number_format($e['bal'], 2); ?></td>
                                                                                                                    </tr>

                                                                                                                    <?php
                                                                                                                }
                                                                                                                ?>
                                                                                                                <tr class="border-top">
                                                                                                                    <td colspan="3"></td>
                                                                                                                    <td class="text-end fs-mons"><b><?php echo number_format($total, 2); ?><b></td>
                                                                                                                    </tr>
                                                                                                                    <?php
                                                                                                                    $tot = $tot + $total;
                                                                                                                }
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

                                                                                                        foreach ($grouped_array as $key => $array)
                                                                                                            $tot2 = 0;

                                                                                                        if ($key == 'Liability') { {
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
                                                                                                                <tr class="border-bottom">
                                                                                                                    <td class="pt-3 fs-mons-bold text-secondary"><b><?php echo $k; ?> </b></td>
                                                                                                                    <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Debit </b></td>
                                                                                                                    <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Credit </b></td>
                                                                                                                    <td class="pt-3 text-end fs-mons-bold text-secondary"><b> Balance </b></td>
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
                                                                                                                    <tr>
                                                                                                                        <td class="fs-mons">
                                                                                                                            <a class="text-black h_transactions" href="#" data-hname="<?= $n ?>" data-date1="<?= $date1 ?>" data-date2="<?= $date2 ?>">
                                                                                                                                <i class="bi bi-plus-square"></i> <?php echo  $n; ?>
                                                                                                                            </a>
                                                                                                                        </td>
                                                                                                                        <td class="text-end fs-mons"><?php echo  number_format($e['Dr'], 2); ?></td>
                                                                                                                        <td class="text-end fs-mons"><?php echo  number_format($e['Cr'], 2); ?></td>
                                                                                                                        <td class="text-end fs-mons"><?php echo  number_format($e['bal'], 2); ?></td>
                                                                                                                    </tr>

                                                                                                                    <?php
                                                                                                                }
                                                                                                                ?>
                                                                                                                <tr class="border-top">
                                                                                                                    <td colspan="3"></td>
                                                                                                                    <td class="text-end fs-mons"><b><?php echo number_format($total, 2); ?><b></td>
                                                                                                                    </tr>
                                                                                                                    <?php
                                                                                                                    $tot = $tot + $total;
                                                                                                                }
                                                                                                            }
                                                                                                            ?>
                                                                                                            <tr>
                                                                                                                <td colspan="3" class="pt-3 fs-mons-bold">
                                                                                                                    <b>Total Liability</b>
                                                                                                                </td>
                                                                                                                <td class="text-end pt-3 fs-mons-bold fs-6"><?php echo number_format($tot, 2); ?></td>
                                                                                                            </tr>

                                                                                                            <?php
                                                                                                        }
                                                                                                        ?>

                                                                            <!-- <table border="0" width="100%">
                                                                <tr>
                                                                    <td><span class="pt-3 fs-6">Total(Equity+Liability+PNL)</span></td>
                                                                    <td class="text-end pt-3 fs-mons-bold fs-6"><b><?php echo number_format(($tot1 + $tot2 + $cal2), 2) ?></b></td>
                                                                </tr>
                                                            </table> -->

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
    </script>
    <?= view('pages/accounts/balance_details'); ?>
</body>




</html>