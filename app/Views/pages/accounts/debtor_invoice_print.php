<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
<?= view('layouts/tf_csrf') ?><?php /* tf-e8-access-r1: token for the receipt POST */ ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Print <?php echo $invoice_details['invoice_no']; ?></title>
    <style>
       /* @media print {
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

            #generate-pdf {
                display: none;
            }
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
        } */
    </style>

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
            /* font-size: medium; */
            /* font-weight: 400; */
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
            /* text-transform: uppercase; */
            /* font-size: larger; */
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
                        <p>No.980/4A,4TH floor,Wickramasinghe place,Ethulkotte, Sri Lanka</p>
                        <p>Email: info@avenirholidays.com</p>

                    </div>

                    <!-- Invoice Details -->
                    <div class="card">
                        <div class="details-header">Invoice
                            <?php if ($invoice_details['agent_ref']) { ?>
                                <span style="float: right;">Agent Reference:<?php echo $invoice_details['agent_ref']; ?></span>
                            <?php } ?>
                        </div>
                        <table class="invoice-details">
                            <tr>
                                <th>Invoice No</th>
                                <td><?php echo $invoice_details['invoice_no']; ?></td>
                                <th>Date</th>
                                <td><?php echo $invoice_details['date']; ?></td>
                                <th>Invoiced Customer</th>
                            </tr>
                            <tr>
                                <th colspan="4">Tour Id - <?php echo $invoice_details['manual_id']; ?><br>Sri Lanka Tour
                                    Arrangement for <?php echo $invoice_details['consignee']; ?></th>
                                <td colspan="4"><?php echo $consignee_details['long_name']; ?>
                                    <br><?php echo $consignee_details['Address']; ?>
                                </td>
                            </tr>
                            <!-- <tr>
                                <th colspan="8">Tour Id - <?php //echo $invoice_details['manual_id'];  
                                                            ?></th>
                            </tr> -->
                        </table>
                        <br>
                        <!-- Invoice Items -->
                        <div class="tour-details-header">Tour Details</div>
                        <table class="invoice-details">
                            <thead>
                                <tr>
                                    <th>Details</th>
                                    <th class="amount-cell">Amount (US$)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo $invoice_details['hotel_des']; ?></td>
                                    <td class="amount-cell"><?php //echo $invoice_details['hotel_amount']+$invoice_details['transport_cost'];
                                                            ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo $invoice_details['activity_des']; ?></td>
                                    <td class="amount-cell"><?php //echo $invoice_details['activity_amount'];
                                                            ?></td>
                                </tr>
                                <tr class="total-row">
                                    <th style="text-align: center;">TOTAL</th>
                                    <th class="amount-cell"><?php echo " USD " . number_format($invoice_details['dollar_amount'], 2); ?></th>
                                </tr>
                                <tr class="total-row">
                                    <?php
                                    $total = $invoice_details['dollar_amount'];
                                    $formatter = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                                    $totalInWords = $formatter->format($total);
                                    ?>
                                    <th colspan="2"><?php echo "AMOUNT - USD  " . ucfirst($totalInWords) . " only"; ?></th>
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
                            <p style="margin: 0;"><span>A/C Name:</span><span style="margin-left: 35px;">Avenir Holidays (Pvt) Ltd</span>
                            </p>
                            <p style="margin: 0;"><span>A/C Type:</span><span style="margin-left: 42px;">Savings A/C</span>
                            </p>
                            <p style="margin: 0;"><span>A/C No:</span><span style="margin-left: 57px;">5000028818</span></p>
                            <p style="margin: 0;"><span>Bank:</span><span style="margin-left: 70px;">Commercial Bank</span>
                            </p>
                            <p style="margin: 0;"><span>Branch:</span><span style="margin-left: 55px;">Ward Place / Branch code - 145</span>
                            </p>
                            <p style="margin: 0;"><span>Bank Code:</span><span style="margin-left: 26px;">7056</span></p>
                            <p style="margin: 0;"><span>Swift Code:</span><span style="margin-left: 26px;">CCEYKLX</span>
                            </p>
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

    <!-- </div> -->
    <!-- Include html2pdf.js via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.js"></script>


    <!-- <button id="generate-pdf" class="btn btn-primary" style="margin: 20px; padding: 10px 20px; font-size: 16px; background-color: #007bff; color: white; border-radius: 10px; box-shadow: none;">Send Email</button> -->

    <script>
        //print page when loaded

        // window.addEventListener('load', function() {
        //     const urlParams = new URLSearchParams(window.location.search);
        //     const customerId = urlParams.get('customer_id');
        //     if (!customerId) {
        //         document.getElementById('generate-pdf').style.display = 'none';
        //         window.print();
        //     }


        // });
    </script>

    <script>
        // Function to generate PDF and send to backend
        document.getElementById('generate-pdf').addEventListener('click', function() {

            // Hide the print button
            document.getElementById('generate-pdf').style.display = 'none';

            // Use html2pdf to generate the PDF from the HTML content
            var element = document.body; // You can specify the container you want to print (e.g., a div)

            // Options for generating the PDF
            var opt = {
                margin: 1,
                filename: 'invoice.pdf',
                html2canvas: {
                    dpi: 300,
                    letterRendering: true,
                    scale: 3
                }, // Increase dpi and scale for better quality
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };

            // Generate PDF and then send it to the backend
            html2pdf().from(element).set(opt).toPdf().get('pdf').then(function(pdf) {
                var pdfData = pdf.output('blob'); // Get the PDF as a blob

                // Create a FormData object to send the PDF data
                var formData = new FormData();
                formData.append('pdf', pdfData, 'invoice.pdf'); // Add the PDF blob to the form data
                const urlParams = new URLSearchParams(window.location.search);

                const customerId = urlParams.get('customer_id');
                formData.append('customer_id', customerId);
                formData.append('cc', urlParams.get('cc'));
                // append Invoice number, manual id, tour id 
                formData.append('invoice_no', '<?= $invoice_details['invoice_no'] ?>');
                formData.append('manual_id', '<?= $invoice_details['manual_id'] ?>');
                formData.append('tour_id', '<?= $invoice_details['tour_id'] ?>');
                // Send the PDF to the backend using Fetch API or AJAX
                fetch('<?= base_url('/accounts/debtor_invoice_new/send_receipt') ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json()) // Assuming the backend returns JSON
                    .then(data => {
                        // Handle the backend response here
                        if (data.status === 200) {
                            alert('Receipt sent successfully');
                            // refresh this page 'tour/tour_accounts/inv_send_report'
                            window.opener.location.href = '<?= base_url('tour/tour_accounts/inv_send_report') ?>';
                            window.close();
                        } else {
                            alert('Failed to send email');
                        }
                        // Show the print button again after the PDF is generated
                        document.getElementById('generate-pdf').style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to send email');
                        // Show the print button again if there's an error
                        document.getElementById('generate-pdf').style.display = 'block';
                    });
            });
        });
    </script>


</body>

</html>