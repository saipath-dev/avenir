<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Print</title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }

            body {
                font-family: 'Times New Roman', serif;
                color: #333;
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .main-content {
                width: 100%;
            }

            .container-fluid {
                padding: 20px;
            }

            .invoice-header {
                text-align: center;
                border-bottom: 1px solid #000;
                margin-bottom: 20px;
                padding-bottom: 10px;
                margin-right: 50px;
            }

            .invoice-header h3 {
                margin: 0;
                font-size: 22px;
                text-transform: uppercase;
                font-weight: bold;
            }

            .invoice-header p {
                font-size: 14px;
                margin: 5px 0;
            }

            .invoice-logo img {
                height: 60px;
                float: left;
            }

            .invoice-details {
                width: 100%;
                margin-bottom: 20px;
                border-collapse: collapse;
            }

            .invoice-details th, .invoice-details td {
                text-align: left;
                padding: 10px;
                border: 1px solid #ddd;
            }

            .invoice-details th {
                background-color: #f2f2f2;
            }

            .invoice-footer {
                margin-top: 30px;
                font-size: 14px;
                text-align: center;
                border-top: 1px solid #000;
                padding-top: 10px;
            }

            .details-header {
                font-weight: bold;
                margin-bottom: 10px;
                text-transform: uppercase;
                font-size: larger;
            }

            .amount-cell {
                text-align: right;
            }

            .total-row th, .total-row td {
                font-weight: bold;
                font-size: 16px;
            }
        }

        /* Add some styles for non-print preview */
        body {
            font-family: Arial, sans-serif;
            color: #333;
        }

        .card {
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <!-- Invoice Content -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- Invoice Header -->

                    <div class="invoice-header">
                     <div class="invoice-logo">
                        <img src="<?= base_url() ?>assets/images/Logo-new.png" alt="Logo">
                    </div>

                    <h3>AVENIR HOLIDAYS (PVT) LTD</h3>
                    <p>NO. 126/1, Park Road, Colombo -5, SRI LANKA</p>
                    <p>Email: info@avenirholidays.com</p>

                </div>

                <!-- Invoice Details -->
                <div class="card">
                    <div class="details-header">Invoice</div>
                    <table class="invoice-details">
                        <tr>
                            <th>Invoice No</th>
                            <td><?php echo $invoice_details['invoice_no']; ?></td>
                            <th>Date</th>
                            <td><?php echo $invoice_details['date']; ?></td>
                        </tr>
                        <tr>
                            <th colspan="4">Sri Lanka Tour Arrangement for <?php echo $invoice_details['consignee'];  ?></th>
                        </tr>
                    </table>

                    <!-- Invoice Items -->
                    <div class="details-header">Tour Details</div>
                    <table class="invoice-details">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="amount-cell">Amount (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $invoice_details['hotel_des']; ?></td>
                                <td class="amount-cell"><?php //echo $invoice_details['hotel_amount']+$invoice_details['transport_cost']; ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $invoice_details['activity_des']; ?></td>
                                <td class="amount-cell"><?php //echo $invoice_details['activity_amount']; ?></td>
                            </tr>
                       <!--      <tr>
                                <td>Full Day Safari in Yala National Park</td>
                                <td class="amount-cell">500.00</td>
                            </tr> -->
                            <tr class="total-row">
                                <th>Total </th>
                                <td class="amount-cell"><?php echo $invoice_details['dollar_amount']; ?></td>
                            </tr>
                            <tr class="total-row">
                                <?php

                                $total = $invoice_details['dollar_amount'];


                                $formatter = new NumberFormatter("en", NumberFormatter::SPELLOUT);


                                $totalInWords = $formatter->format($total);


                               
                                ?>

                                <th colspan="2"><?php  echo  "AMOUNT - &nbsp;&nbsp; U.S &nbsp;&nbsp;".ucfirst($totalInWords) . "&nbsp; Only"; ?></th>
                                <!-- <td class="amount-cell"></td> -->
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Bank Details -->
                <div class="bank-details">
                    <h4>Bank Details</h4>
                    <div style="display: flex; flex-direction: column; align-items: flex-start;">
                        <p style="margin: 0;"><strong>A/C Name: </strong><span style="margin-left: 10px;">Avenir Holidays (Pvt) Ltd</span></p>
                        <p style="margin: 0;"><strong>A/C Type: </strong><span style="margin-left: 10px;">Savings A/C</span></p>
                        <p style="margin: 0;"><strong>A/C No: </strong><span style="margin-left: 10px;">5000028818</span></p>
                        <p style="margin: 0;"><strong>Bank: </strong><span style="margin-left: 10px;">Commercial Bank</span></p>
                        <p style="margin: 0;"><strong>Branch: </strong><span style="margin-left: 10px;">Ward Place / Branch code - 145</span></p>
                        <p style="margin: 0;"><strong>Bank Code: </strong><span style="margin-left: 10px;">7056</span></p>
                        <p style="margin: 0;"><strong>Swift Code: </strong><span style="margin-left: 10px;">CCEYKLX</span></p>
                    </div>
                </div>

            </div>

            <!-- Invoice Footer -->
            <div class="invoice-footer">
                Thank you for choosing Avenir Holidays! <br>
                For any inquiries, please contact us at: info@avenirholidays.com
            </div>

        </div>
    </div>
</div>
<!-- End Invoice Content -->

</div>

<script>
        // Automatically print the page when loaded
        window.onload = function() {
            window.print();
        };
    </script>

</body>

</html>
