<?= view('layouts/head') ?>
<style type="text/css">
    body {

        background-color: #f8f9fa;
    }

    .card-header {
        /*background: linear-gradient(to right, #007bff, #6610f2);*/
        color: white;
        font-weight: bold;
        border-bottom: 2px solid #f8f9fa;
    }

    .table {
        border-collapse: collapse;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.1);
        cursor: pointer;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #f9f9f9;
    }

    .btn {
        transition: 0.3s ease;
    }

    .btn:hover {
        background-color: #0056b3;
        color: white;
    }
</style>

<body class="bg-light">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <!-- Start right Content here -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="row mb-4">
                        <div class="col-md-9">
                            <h4 class="text-primary font-weight-bold">Accepted Invoices</h4>
                        </div>
                        <div class="col-md-3 text-end">
                            <h6 class="text-secondary"><?= date('Y-m-d') ?></h6>
                        </div>
                    </div>


                    <!-- Table -->
                    <div class="container-fluid py-4">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card shadow-lg border-0 rounded">

                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover align-middle" id="mainTable">
                                                <thead class="bg-light text-primary">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Manual ID</th>
                                                        <th>Invoice Number</th>
                                                        <th>Accepted Date</th>
                                                        <th>Accepted User</th>
                                                        <th>Send</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($acceptedinvoices)) : ?>
                                                        <?php $i = 1; ?>
                                                        <?php foreach ($acceptedinvoices as $invoice) :

                                                        ?>
                                                            <tr style="background-color: #eafff5ff;">
                                                                <td class="font-italic text-danger"><?= $i; ?></td>
                                                                <td class="font-italic"><?= $invoice['manual_id']; ?></td>
                                                                <td class="font-italic"><?= $invoice['amend_inv']; ?></td>
                                                                <td><?= $invoice['accepted_date']; ?></td>
                                                                <td><?= $invoice['accepted_user']; ?></td>
                                                                <td>
                                                                    <button class="btn btn-primary btn-sm open_em_modal"
                                                                        title="Send Invoice"
                                                                        data-tour="<?= $invoice['tour_id']; ?>">
                                                                        <i class="bi bi-send ml-2"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>

                                                            <?php $i++; ?>

                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>



                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>

            <?= view('layouts/brand') ?>

        </div>
    </div>

    <div class="modal fade" id="hotelBookingEmailModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">

            <style>
                /*@media print {*/
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

                /*}*/

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

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Invoice Details & Email Form</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body" id="invoiceModalContent">
                    <!-- Form Section -->
                    <form action="javascript:void(0);" class="hotel_booking_email_form" id="hotel_booking_email_form">
                        <div class="row">
                            <div class="mb-4">
                                <input type="hidden" name="tour_id" id="e_tour_id">
                                <input type="hidden" name="dates" id="e_dates">
                                <input type="hidden" name="id" id="e_id">
                                <div class="mb-3">
                                    <label class="form-label" for="em_to">To</label>
                                    <select name="em_to" id="em_to" class="form-control">
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?= $customer['Sup_Acc_No']; ?>" data-id="<?= $customer['Sup_Acc_No']; ?>">
                                                <?= $customer['Name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="em_cc">CC</label>
                                    <select name="em_cc[]" id="em_cc" class="form-control em_cc"></select>
                                    <small>
                                        All emails are CC to <i>accounts@avenirholidays.com</i>,
                                        <i>nf@avenirholidays.com</i> by default.
                                    </small>
                                </div>

                            </div>
                        </div>
                    </form>

                    <!-- Invoice Content (Dynamically Added) -->
                    <div id="invoiceSection" class="invoice-section">
                        <!-- The invoice content will be dynamically inserted here -->
                    </div>
                </div>

                <!-- Modal Footer with Print Button -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                    <button class="btn btn-primary invoice_print me-2"
                        id="mail_tour_id"
                        title="Invoice Reprint"
                        data-tour=""
                        data-customer_id="">
                        Send Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/scripts') ?>

    <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        $('.em_cc').select2({
            placeholder: " Select",
            allowClear: true,
            dropdownParent: $("#hotelBookingEmailModal"),
            multiple: true,
            tags: true
        });
        //change data-customer_id when onchange on em_to
        $('#em_to').on('change', function() {
            $('#mail_tour_id').attr('data-customer_id', $(this).val());
        });

        document.getElementById('mail_tour_id').addEventListener('click', function() {
            var tourId = this.getAttribute('data-tour');
            var customerId = this.getAttribute('data-customer_id');
            var ccEmails = $('#em_cc').val(); // Get the selected CC emails

            // Example: Open a new window with the tour, consignee, and CC details
            window.open("<?php echo base_url(); ?>accounts/debtor_invoice_new/print?tour=" + tourId + "&customer_id=" + customerId + "&cc=" + ccEmails.join(','));
        });

        $(".invoice_print").on('click', function(e) {
            e.preventDefault();

            var tour_id = $(this).data('tour');
            $.ajax({
                url: '<?php echo base_url('tour/tour_dashboard/search_debtor_invoice') ?>',
                type: 'POST',
                data: {
                    tour_id: tour_id,
                },
                dataType: 'json',
                success: function(data) {

                    if (data.status == 200) {

                        window.open("<?php echo base_url(); ?>accounts/debtor_invoice_new/print?tour=" + tour_id, '_blank');

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message ? data.message : 'An error occured while processing the request',
                        });
                    }
                },
                error: function() {

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occured while processing the request',
                    });
                }
            });
        });

        // .open_em_modal click
        $(document).on('click', '.open_em_modal', function() {

            var tour_id = $(this).data('tour');

            //set mail_tour_id
            $('#mail_tour_id').attr('data-tour', tour_id);


            $.ajax({
                url: '<?php echo base_url('accounts/debtor_invoice_new/sent_mail') ?>',
                type: 'GET',
                data: {
                    tour_id: tour_id,
                },
                dataType: 'json',
                success: function(data) {

                    if (data) {
                        let invoiceDetails = data.invoice_details; // Use snake_case
                        let consigneeDetails = data.consignee_details; // For customer details


                        // Check if the ID exists in the options
                        let customerIdToSelect = invoiceDetails.agent; // Adjust the property name
                        if ($('#em_to option[value="' + customerIdToSelect + '"]').length > 0) {
                            $('#em_to').val(customerIdToSelect); // Set the dropdown value

                            $('#mail_tour_id').attr('data-customer_id', customerIdToSelect);

                        } else {
                            console.error('Customer ID not found in dropdown');
                        }



                        // Inject the dynamically generated content into the modal
                        const modalContent = `
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
                        <div class="details-header">Invoice</div>
                        <table class="invoice-details">
                        <tr>
                        <th>Invoice No</th>
                        <td>${invoiceDetails.invoice_no}</td>
                        <th>Date</th>
                        <td>${invoiceDetails.date}</td>
                        <th>Invoiced Customer</th>
                        </tr>
                        <tr>
                        <th colspan="4">
                        Tour Id - ${invoiceDetails.manual_id}<br>
                        Sri Lanka Tour Arrangement for ${invoiceDetails.consignee}
                        </th>
                        <td colspan="4">
                        ${consigneeDetails.long_name}<br>
                        ${consigneeDetails.Address}
                        </td>
                        </tr>
                        </table>
                        <br>

                        <!-- Tour Details -->
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
                        <td>${invoiceDetails.hotel_des}</td>
                        <td class="amount-cell">${invoiceDetails.hotel_amount || 0}</td>
                        </tr>
                        <tr>
                        <td>${invoiceDetails.activity_des || 'N/A'}</td>
                        <td class="amount-cell">${invoiceDetails.activity_amount || 0}</td>
                        </tr>
                        <tr class="total-row">
                        <th style="text-align: center;">TOTAL</th>
                        <th class="amount-cell">USD ${parseFloat(invoiceDetails.dollar_amount).toFixed(2)}</th>
                        </tr>
                        <tr class="total-row">
                        <th colspan="2">
                        ${invoiceDetails.dollar_amount} only
                        </th>
                        </tr>
                        </tbody>
                        </table>
                        </div>

                        <!-- Bank Details -->
                        <div class="bank-details">
                        <div class="tour-details-header" style="text-decoration: underline; text-align: left; margin-bottom: 2px;">Bank Details</div>
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
                        `;

                        // Inject into the modal or target section
                        $('#invoiceSection').html(modalContent);

                        // Show modal
                        $('#hotelBookingEmailModal').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message ? data.message : 'An error occurred while processing the request',
                        });
                    }
                },


                error: function() {

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occured while processing the request',
                    });
                }
            });


        });
    </script>

</body>

</html>