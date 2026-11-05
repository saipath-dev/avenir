<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Print</title>
    <!-- Invoice Header -->
    <style>
        .invoice-header {
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .invoice-title,
        .invoice-logo {
            box-sizing: border-box;
            padding: 10px;
        }

        .invoice-title {
            flex: 1 1 50%;
            min-width: 300px;
        }

        .invoice-logo {
            flex: 1 1 50%;
            min-width: 300px;
            padding-left: 10px;
            text-align: left;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
        }

        .invoice-address {
            border: 1px solid black;
            padding: 5px;
            width: 100%;
            max-width: 200px;
            font-size: 14px;
            background: #f9f9f9;
        }

        .invoice-address2 {
            padding: 10px;
            width: 90%;
            max-width: 150px;
            font-size: 14px;
            background:rgb(255, 255, 255);
        }

        .invoice-title h4 {
            margin-bottom: 10px;
            font-weight: bold;
        }

        .invoice-table th,
        .invoice-table td {
            border: 1px solid black !important;
            padding: 4px 8px !important;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .invoice-header {
                flex-direction: column;
            }

            .invoice-title,
            .invoice-logo {
                text-align: left !important;
                align-items: flex-start !important;
            }

            .invoice-logo img {
                margin-bottom: 10px;
            }
        }

        @media print {
            body * {
                visibility: hidden !important;
            }

            #invoice-content,
            #invoice-content * {
                visibility: visible !important;
            }

            #invoice-content {
                position: absolute !important;
                left: 0;
                top: 0;
                width: 100%;
            }
        }

        .export-buttons .export-btn:hover {
            opacity: 0.85;
        }

        @media print {
            .export-buttons {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <!-- Export PDF and Print Buttons -->
    <div class="text-end my-4 export-buttons" style="text-align: center; margin-bottom: 24px;">
        <button onclick="exportPDF()" class="btn btn-primary me-2 export-btn" style="background: #007bff; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; margin-right: 8px; font-size: 15px; cursor: pointer;">
            <i class="bi bi-file-earmark-pdf"></i> Export as PDF
        </button>
        <button onclick="printInvoice()" class="btn btn-secondary export-btn" style="background: #6c757d; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; font-size: 15px; cursor: pointer;">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>


    <!-- Begin page -->
    <div id="layout-wrapper">

        <!-- Invoice Content -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid" id="invoice-content" style="max-width: 185mm; margin: 0 auto; border: 1px solid black; padding: 0; background: #fff;">

                    <div class="row invoice-header">
                        <div class="invoice-title">
                            <h4>AVENIR HOLIDAYS (PVT) LTD<br>INVOICE</h4>
                            <table class="table table-bordered table-sm mb-2 invoice-table" style="border-collapse: collapse; max-width: 350px;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Invoice</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?= $date2 ?></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="invoice-address">
                                <p style="margin: 0;">AVENIR HOLIDAYS (PVT) LTD</p>
                                <p style="margin: 0;">No. 126/1, Park Road,</p>
                                <p style="margin: 0;">Colombo 05, Sri Lanka</p>
                                <p style="margin: 0;">Email: info@avenireholidays.com</p>
                            </div>
                            <div>
                                <?php
                                    $month = date('F', strtotime($date2));
                                    $year = date('Y', strtotime($date2));
                                ?>
                                <p><i>Sri Lanka Tour Arrangement for the month of <?= $month ?> <?= $year ?></i></p>
                            </div>
                        </div>
                        <div class="invoice-logo"  >
                            <img src="<?= base_url() ?>assets/images/Logo-new.png" alt="Logo" width="180" style="margin-bottom: 10px; background:#fff;">
                            <div class="invoice-address2">
                                <p style="margin: 0;">THE ACCOUNTANT</p>
                                <p style="margin: 0;">LION ROYAL LTD</p>
                                <p style="margin: 0;">1-10-19 Horidome-cho</p>
                                <p style="margin: 0;">Nihonbashi, Chuo-ku</p>
                                <p style="margin: 0;">Tokyo</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <table class="table table-bordered table-sm mb-2 invoice-table" style="border-collapse: collapse; width: 98%;margin: 5px;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid black; padding: 4px 8px;">Details</th>
                                    <th style="border: 1px solid black; padding: 4px 8px;">invoice_no</th>
                                    <th style="border: 1px solid black; padding: 4px 8px;">Amount US$</th>
                                    <th style="border: 1px solid black; padding: 4px 8px;">Received</th>
                                    <th style="border: 1px solid black; padding: 4px 8px;">Due Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_amount = 0;
                                foreach ($payments as $litem) {
                                    $total_amount += floatval($litem['dollar_amount']);
                                ?>
                                    <tr>
                                        <td><?php echo $litem['start_date'] . " - " . $litem['manual_id'] . "  "  . $litem['consignee'] ?></td>
                                        <td><?php echo $litem['invoice_no'] ?></td>
                                        <td><?php echo $litem['dollar_amount'] ?></td>
                                        <td><?php echo !empty($litem['Cr_doller']) ? $litem['Cr_doller'] : '-'; ?></td>
                                        <td>-</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th style="text-align: right;">Total</th>
                                    <th>-</th>
                                    <th>-</th>
                                    <th><?php echo number_format($total_amount, 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="row">
                        <div class="col-md-12" style="padding: 5px;">
                            <p style="font-size: 14px; margin: 0;"><b>AMOUNT</b> - U.S.D Two Thounsand Ninty Five Only.</p><br>
                            <p style="font-size: 14px; margin: 0;"><u>Bank Details</u></p>
                            <div style="display: flex; flex-wrap: wrap; gap: 40px;">
                                <div style="flex: 1 1 200px;">
                                    <p style="font-size: 14px; margin: 0;">A/C Name:</p>
                                    <p style="font-size: 14px; margin: 0;">A/C Type:</p>
                                    <p style="font-size: 14px; margin: 0;">Account Number:</p>
                                    <p style="font-size: 14px; margin: 0;">Bank:</p>
                                    <p style="font-size: 14px; margin: 0;">Branch:</p>
                                    <p style="font-size: 14px; margin: 0;">Bank/ Branch code:</p>
                                    <p style="font-size: 14px; margin: 0;">Swift Code:</p>
                                </div>
                                <div style="flex: 1 1 200px;">
                                    <p style="font-size: 14px; margin: 0;">Avenir Holidays (Pvt) Ltd</p>
                                    <p style="font-size: 14px; margin: 0;">Saving A/C</p>
                                    <p style="font-size: 14px; margin: 0;">5000028818</p>
                                    <p style="font-size: 14px; margin: 0;">Commercial</p>
                                    <p style="font-size: 14px; margin: 0;">Ward Place</p>
                                    <p style="font-size: 14px; margin: 0;">7056/145</p>
                                    <p style="font-size: 14px; margin: 0;">CCEYKLKX</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- End Invoice Content -->

    <!-- Include html2pdf.js via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.js"></script>
    <script>
        function exportPDF() {
            var element = document.getElementById('invoice-content');
            var opt = {
                margin: 0.2,
                filename: 'invoice-report.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2
                },
                jsPDF: {
                    unit: 'in',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };
            html2pdf().set(opt).from(element).save();
        }

        function printInvoice() {
            window.print();
        }
    </script>
</body>

</html>