<?php helper('number'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Print <?php echo $invoice_details['invoice_no']; ?></title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            font-family: 'Times New Roman', serif;
            color: #000;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #000;
            padding: 10px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .header-left {
            flex: 1;
        }

        .header-left h1 {
            margin: 0 0 5px 0;
            font-size: 20px;
            font-weight: bold;
        }

        .header-left h2 {
            margin: 0 0 10px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .left-box-container {
            border: 1px solid #000;
            max-width: 300px;
            padding: 0;
        }

        .date-invoice-box {
            padding: 0;
            margin: 0;
            /* border-bottom: 2px solid #000; */
        }

        .date-invoice-box table {
            border-collapse: collapse;
            margin: 0;
            width: 100%;
        }

        .date-invoice-box td {
            padding: 5px 15px;
            border: 0.5px solid #000;
            border-top: none;
            font-weight: normal;
            font-size: 15px;
        }

        .company-info-box {
            padding: 10px;
            border: none;
        }

        .company-info-box p {
            margin: 2px 0;
            font-size: 15px;
        }

        .header-right {
            text-align: right;
        }

        .header-right img {
            max-height: 80px;
            margin-bottom: 20px;
        }

        .recipient-box {
            text-align: left;
       
            font-size: 18px;
        }

        .recipient-box p {
            margin: 2px 0;
        }

        .payment-details {
            margin: 30px 0;
        }

        .payment-details h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .payment-table th,
        .payment-table td {
            border: 1px solid #000;
            border-left:none;
            padding: 8px 12px;
            text-align: left;
        }

        .payment-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .payment-table .amount-cell {
            text-align: right;
            font-weight: bold;
        }

        .payment-table .total-row {
            background-color: #f0f0f0;
        }

        .payment-table .total-row th,
        .payment-table .total-row td {
            font-weight: bold;
            text-align: center;
        }

        .payment-table .total-row .amount-cell {
            text-align: right;
        }

        .bank-details {
            margin: 30px 0;
        }

        .bank-details h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
        }

        .bank-info {
            font-size: 15px;
            line-height: 1.6;
        }

        .bank-info p {
            margin: 3px 0;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #000;
            font-size: 15px;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-left">
                <h1>AVENIR HOLIDAYS (PVT) LTD</h1>
                <h2>INVOICE</h2>

                <div class="left-box-container">
                    <div class="date-invoice-box">
                        <table>
                            <tr>
                                <td>Date</td>
                                <td>INVOICE</td>
                            </tr>
                            <tr>
                                <td><?php echo date('d.m.Y', strtotime($invoice_details['date'])); ?></td>
                                <td><?php echo $invoice_details['invoice_no']; ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="company-info-box">
                        <p><strong>AVENIR HOLIDAYS (PVT) LTD,</strong></p>
                        <p>No. 126/1 , Park Road ,</p>
                        <p>Colombo - 5 , SRI LANKA</p>
                        <p><a href="mailto:info@avenirholidays.com">info@avenirholidays.com</a></p>
                    </div>
                </div>
            </div>

            <div class="header-right">
                <img src="<?= base_url() ?>assets/images/Logo-new.png" alt="Logo">

                <div class="recipient-box">
                    <p>THE ACCOUNTANT</p>
                    <p><?php
                        echo isset($invoice_details['is_staff_invoice']) && $invoice_details['is_staff_invoice'] == 1
                            ? 'LION TOUR LTD'
                            : 'LION ROYAL LTD';
                    ?></p>
                    <p>Japan</p>
                </div>
            </div>
        </div>

        <!-- Payment Details Section -->
        <div class="payment-details">
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>Details</th>
                        <th style="width: 200px;">Amount JPY</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $invoice_details['description']; ?></td>
                        <td class="amount-cell">JPY <?php echo number_format($invoice_details['amount_jpy'], 2); ?></td>
                    </tr>

                    <?php if (isset($invoice_details['is_staff_invoice']) && $invoice_details['is_staff_invoice'] == 1): ?>
                        <!-- <tr>
                            <td>(<?php //echo $invoice_details['description']; ?>)</td>
                            <td class="amount-cell"></td>
                        </tr> -->
                        <tr>
                            <td>Month of <?php echo date('F Y', strtotime($invoice_details['date'])); ?></td>
                            <td class="amount-cell"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="amount-cell"></td>
                        </tr>
                        <tr>
                            <td>Exchange Rate - JPY 1 - LKR <?php echo number_format($invoice_details['conversion_rate'], 2); ?></td>
                            <td class="amount-cell"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="amount-cell"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="amount-cell"></td>
                        </tr>
                    <?php endif; ?>

                    <tr class="total-row">
                        <th>TOTAL</th>
                        <td class="amount-cell">JPY <?php echo number_format($invoice_details['amount_jpy'], 2); ?></td>
                    </tr>

                    <?php if (isset($invoice_details['is_staff_invoice']) && $invoice_details['is_staff_invoice'] == 1): ?>
                        <tr>
                            <td colspan="2"><strong>The amount payable is subject to change due to currency fluctuations in the Yen</strong></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="2"><strong>AMOUNT - JPY <?php echo strtoupper(numberToWords($invoice_details['amount_jpy'])); ?> ONLY</strong></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Bank Details Section -->
        <div class="bank-details">
            <h3>Bank Details</h3>
            <div class="bank-info">
                <p><strong>A/C Name -</strong> Avenir Holidays (Pvt) Ltd</p>
                <p><strong>A/C Type -</strong> Savings A/C</p>
                <p><strong>A/C No -</strong> 5000028818 (USD)</p>
                <p><strong>Bank -</strong> Commercial Bank</p>
                <p><strong>Branch -</strong> Ward Place</p>
                <p><strong>Bank/ Branch code - 731</strong> 7056/145</p>
                <p><strong>Swift Code -</strong> CCEYLKLX</p>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>
</html>
