<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancellation Fee Invoice <?php echo $cancellation_details['Invoice_Num'] ?? ''; ?></title>
    <style>
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

        .invoice-details th,
        .invoice-details td {
            text-align: left;
            padding: 10px;
            border: 1px solid #ddd;
        }

        .invoice-details th {
            background-color: #f2f2f2;
        }

        .invoice-main-details th,
        .invoice-main-details td {
            background-color: #f2f2f2;
            text-align: center;
            border: 1px solid #000;
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
            margin-bottom: 5px;
            text-transform: uppercase;
            font-size: larger;
        }

        .tour-details-header {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .amount-cell {
            text-align: right;
        }

        .total-row th,
        .total-row td {
            font-weight: bold;
            font-size: 16px;
            margin: 0px;
        }

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

        .cancellation-badge {
            background-color: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .print-btn {
                display: none !important;
            }
        }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .print-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>

    <button class="print-btn no-print" onclick="window.print()">Print Invoice</button>

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
                        <p>No.980/4A,4TH floor,Wickramasinghe place,Ethulkotte, Sri Lanka</p>
                        <p>Email: info@avenirholidays.com</p>

                    </div>

                    <!-- Invoice Details -->
                    <div class="card">
                        <div class="cancellation-badge">CANCELLATION FEE INVOICE</div>
                        <div class="details-header">Invoice</div>
                        <table class="invoice-details">
                            <tr>
                                <th>Invoice No</th>
                                <td><?php echo $cancellation_details['Invoice_Num'] ?? '-'; ?></td>
                                <th>Date</th>
                                <td><?php echo $cancellation_details['Date'] ?? '-'; ?></td>
                                <th>Invoiced Customer</th>
                            </tr>
                            <tr>
                                <th colspan="4">
                                    Tour Id - <?php echo $cancellation_details['manual_id'] ?? '-'; ?><br>
                                    Sri Lanka Tour Arrangement for <?php echo $cancellation_details['tour_name'] ?? '-'; ?>
                                    <br><br>
                                    <small>Original Invoice: <?php echo $cancellation_details['InvCancel'] ?? '-'; ?></small>
                                </th>
                                <td colspan="4">
                                    <?php echo $consignee_details['long_name'] ?? $consignee_details['Name'] ?? '-'; ?>
                                    <br><?php echo $consignee_details['Address'] ?? '-'; ?>
                                </td>
                            </tr>
                        </table>
                        <br>

                        <!-- Tour Details -->
                        <?php if (!empty($cancellation_details['start_date']) && !empty($cancellation_details['end_date'])): ?>
                        <div class="tour-details-header">Tour Period</div>
                        <table class="invoice-details">
                            <tr>
                                <th>Start Date</th>
                                <td><?php echo date('Y-m-d', strtotime($cancellation_details['start_date'])); ?></td>
                                <th>End Date</th>
                                <td><?php echo date('Y-m-d', strtotime($cancellation_details['end_date'])); ?></td>
                            </tr>
                        </table>
                        <br>
                        <?php endif; ?>

                        <!-- Cancellation Fee Details -->
                        <div class="tour-details-header">Cancellation Fee Details</div>
                        <table class="invoice-details">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="amount-cell">Amount (US$)</th>
                                    <th class="amount-cell">Rate</th>
                                    <th class="amount-cell">Amount (LKR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo $cancellation_details['Discription'] ?? 'Cancellation Fee'; ?></td>
                                    <td class="amount-cell"><?php echo number_format($cancellation_details['dollar'] ?? 0, 2); ?></td>
                                    <td class="amount-cell"><?php echo number_format($cancellation_details['dollarrate'] ?? 0, 2); ?></td>
                                    <td class="amount-cell"><?php echo number_format($cancellation_details['Dr'] ?? 0, 2); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <th style="text-align: center;">TOTAL</th>
                                    <th class="amount-cell" colspan="3">
                                        <?php echo " USD " . number_format($cancellation_details['dollar'] ?? 0, 2); ?>
                                        (LKR <?php echo number_format($cancellation_details['Dr'] ?? 0, 2); ?>)
                                    </th>
                                </tr>
                                <tr class="total-row">
                                    <?php
                                    $total = $cancellation_details['dollar'] ?? 0;
                                    $formatter = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                                    $totalInWords = $formatter->format($total);
                                    ?>
                                    <th colspan="4"><?php echo "AMOUNT - USD  " . ucfirst($totalInWords) . " only"; ?></th>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bank Details -->
                    <div class="bank-details">
                        <div class="tour-details-header"
                            style="text-decoration: underline; text-align: left; margin-bottom: 2px;">Bank Details
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-start;">
                            <p style="margin: 0;"><span>A/C Name:</span><span style="margin-left: 35px;">Avenir Holidays (Pvt) Ltd</span></p>
                            <p style="margin: 0;"><span>A/C Type:</span><span style="margin-left: 42px;">Savings A/C</span></p>
                            <p style="margin: 0;"><span>A/C No:</span><span style="margin-left: 57px;">5000028818</span></p>
                            <p style="margin: 0;"><span>Bank:</span><span style="margin-left: 70px;">Commercial Bank</span></p>
                            <p style="margin: 0;"><span>Branch:</span><span style="margin-left: 55px;">Ward Place / Branch code - 145</span></p>
                            <p style="margin: 0;"><span>Bank Code:</span><span style="margin-left: 26px;">7056</span></p>
                            <p style="margin: 0;"><span>Swift Code:</span><span style="margin-left: 26px;">CCEYKLX</span></p>
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

    <script>
        // Auto print when page loads (optional - uncomment if needed)
        // window.onload = function() {
        //     window.print();
        // };
    </script>

</body>

</html>
