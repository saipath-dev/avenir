<?= view('layouts/head') ?>

<body>
    <div id="layout-wrapper">
        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <style>
            body {
                font-family: 'Courier New', Courier, monospace;
                font-size: 12px;
                margin: 0;
                padding: 0;
                background-color: white;
                color: #000;
            }

            .content-wrapper {
                width: 100%;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                background-color: white;
            }

            .text-center {
                text-align: center;
            }

            .text-right {
                text-align: right;
            }

            .text-left {
                text-align: left;
            }

            .font-bold {
                font-weight: bold;
                font-family: 'Courier New', Courier, monospace;

            }

            .pb-2 {
                padding-bottom: 10px;
            }

            .pb-3 {
                padding-bottom: 15px;
            }

            .border-top {
                border-top: 1px solid #000;
            }

            .border-bottom {
                border-bottom: 1px solid #000;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            table td {
                padding: 5px;
            }

            .signature {
                margin-top: 50px;
                display: flex;
                justify-content: space-between;
            }

            .signature-line {
                border-top: 1px solid #000;
                width: 40%;
            }
        </style>

        <div class="content-wrapper" id="print-area">
            <div class="text-center pb-3">
                <!-- <h5 class="font-bold">Avenir Holidays (Pvt) Ltd</h5> -->
                <p>No 126/1, Park Road, Colombo 05</p>
                <h5 class="font-bold">Bank Reconciliation Summary</h5>
                <h4 class="font-bold">Avenir Holidays (Pvt) Ltd<br>No 126/1 , Park Road Colombo 05<br>Bank Reconciliation Summary<br>Commercial Bank, Period Ending 30/04/2024</h4>
            </div>

            <table>
                <tr>
                    <td class="text-left pb-2 font-bold"><h5 class="font-bold">Beginning GL Balance <?php echo $first_date; ?></h5></td>
                    <td class="text-right pb-2"><h5 class="font-bold"><?php echo number_format($opening_balance,2) ?></h5></td>
                </tr>
                <tr>
                    <td class="text-left font-bold"><h5 class="font-bold">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Checks and Payments</h5></td>
                    <td class="text-right"><h5 class="font-bold">-<?php echo $result['count_cr_positive'] ?></h5></td>
                </tr>
                <tr>
                    <td class="text-left font-bold"><h5 class="font-bold">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Deposits and Credits</h5></td>
                    <td class="text-right"><h5 class="font-bold">-<?php echo $result['count_dr_positive'] ?></h5></td>
                </tr>
                <tr class="border-bottom">
                    <td class="text-left font-bold"><h5 class="font-bold">Ending GL Balance</h5></td>
                    <td class="text-right font-bold"><h5 class="font-bold"><?php echo number_format(($opening_balance-$result['count_cr_positive']+$result['count_dr_positive']),2) ?></h5></td>
                </tr>
                <tr>
                    <td class="text-left pb-2"><h5 class="font-bold">Ending Bank Balance <?php echo $date; ?></h5></td>
                    <td class="text-right pb-2"><h5 class="font-bold"></h5><?php echo number_format($ending_balance,2) ?></td>
                </tr>
                <tr>
                    <td class="text-left"><h5 class="font-bold">(Add back) Deposit in Transit</h5></td>
                    <td class="text-right">-<?php echo $uncleared_entries['count_dr_positive'] ?></td>
                </tr>
                <tr>
                    <td class="text-left"><h5 class="font-bold">(Less) Outstanding Cheques</h5></td>
                    <td class="text-right"> -<?php echo $uncleared_entries['count_cr_positive'] ?></td>
                </tr>
                <tr class="border-top font-bold">
                    <td class="text-left font-bold"><h5 class="font-bold">Ending GL Balance 30.04.2024</h5></td>
                    <td class="text-right font-bold"></td>
                </tr>
            </table>

            <div class="signature">
                <div class="signature-line">Prepared by</div>
                <div class="signature-line">Checked by</div>
            </div>
        </div>

        <?= view('layouts/scripts') ?>

        <script>
            function printContent(id) {
                var restorePage = document.body.innerHTML;
                var printContent = document.getElementById(id).innerHTML;
                document.body.innerHTML = printContent;
                window.print();
                document.body.innerHTML = restorePage;
            }
        </script>
    </div>
</body>
</html>
