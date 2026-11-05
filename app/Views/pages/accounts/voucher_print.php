<!DOCTYPE html>
<html lang="en">
<?= view('layouts/head') ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Voucher</title>
    <link href="<?php echo base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <style>

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }
        .content {
            padding: 20px;
            border-radius: 8px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            /*text-align: left;*/
            margin-bottom: 30px;
        }
        .header img {
            height: 100px;
        }
        .voucher-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        .company-info {
            /*text-align: center;*/
            margin-top: 20px;
            /*align-content: left;*/
            /*margin-left: 0;*/

        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 5px;
        }
        .table-bordered td, .table-bordered th {
            border: 1px solid #ddd;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
        }
        .signature-table td {
            text-align: center;
            padding: 10px 10px;
        }
        .col-md-4, .col-md-6, .col-md-2 {
            flex: 1; /* Ensure each column takes equal space */
        }

        /* Print styles */
        @media print {

            body {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 12px;
            }
            .content {
                padding: 20px;
                border-radius: 8px;
                background-color: white;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                max-width: 800px;
                margin: 0 auto;
            }
            .header {
                text-align: left;
                margin-bottom: 30px;
            }
            .header img {
                height: 100px;
            }
            .voucher-title {
                font-size: 18px;
                font-weight: bold;
                margin-top: 10px;
            }
            .company-info {
                /*text-align: center;*/
                margin-top: 20px;
                align-content: center;
                /*margin-left: 0;*/

            }
            .info-table {
                width: 100%;
                margin-bottom: 20px;
            }
            .info-table td {
                padding: 5px;
            }
            .table-bordered td, .table-bordered th {
                border: 1px solid #ddd;
            }
            .signature-table {
                width: 100%;
                margin-top: 30px;
            }
            .signature-table td {
                text-align: center;
                padding: 10px 10px;
            }
            .col-md-4, .col-md-6, .col-md-2 {
                flex: 1; /* Ensure each column takes equal space */
            }
            .bank-details {
              margin-bottom: 20px;
          }
          .bank-details div {
              margin-bottom: 5px;
          }
          .bank-details div span {
              font-weight: bold;
          }

          /* You can hide elements that shouldn't appear in print */
          /* For example: */
          /* .header img { display: none; } */
      }
  </style>


</head>

<body>
    <div class="content">
     <div class="button-container">
        <!-- <button class="btn btn-primary" onclick="window.print()">Print</button> &nbsp; -->
        <?php if($id !=null){ 

            if($status == 1){

                ?>
                <button class="btn btn-success ml-2 btn_update" id="confirmButton">Confirm</button> &nbsp;&nbsp;&nbsp;
            <?php }else {  ?>
                <button class="btn btn-danger ml-2 btn_update" id="rejectbutton">Reject</button>
            <?php }} ?>
            <button class="btn btn-primary ml-2" id="backbutton" onclick="window.location.href = '<?= base_url() ?>dashboard2/voucher_auth?tour_id=<?= $tourid ?>'">Back</button>
        </div>
        <div class="header row" >
            <div class="logo col-md-4">
                <img src="<?php echo base_url(); ?>assets/images/logo-light.png" alt="Logo" style="height: 80px;">
            </div>
            <div class="company-info col-md-6">
                <span  style="font-size: 16px; font-weight: bold; font-family: 'Times-New-Roman', sans-serif; line-height: 1.6;">AVENIR HOLIDAYS PVT LTD</span><br><span></span>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;NO 126/1 Park Road, Colombo 05 <br> <span  style="font-size: 18px; font-weight: bold; font-family: 'Times-New-Roman', sans-serif; line-height: 1.6;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <u>Voucher Payment </u></span>
            </div>
            <div class="col-md-2" style="align-content: center;">
                <?php if ($voucher_details) { ?>

                    <?php if ($voucher_details['Aflag'] == 3) { ?>
                        <img src="<?php echo base_url(); ?>assets/img/voucher_cancelled.jpg" alt="Voucher Cancelled" style="max-width: 100%; margin-top: 10px;">
                    <?php } else if ($voucher_details['Aflag'] == 1) { ?>
                        <img src="<?php echo base_url(); ?>assets/img/voucher_approved.jpg" alt="Voucher Approved" style="max-width: 100%; margin-top: 10px;">
                    <?php } ?>

                </div>

            </div>
            <input type="hidden" name="id" id="id" value="<?php echo $id ?>">
            <input type="hidden" name="status" id="status" value="<?php echo $status ?>">
            <input type="hidden" name="bank_acc" id="bank_acc" value="<?php echo $bank_acc ?>">
            <input type="hidden" name="type" id="type" value="<?php echo $type ?>">
            <input type="hidden" name="amount" id="amount" value="<?php echo $amount ?>">
            <input type="hidden" name="sup" id="sup" value="<?php echo $sup ?>">
            <input type="hidden" name="payee" id="payee" value="<?php echo $payee ?>">
            <input type="hidden" name="voucher_no" id="voucher_no" value="<?php echo $voucher_details['Vnum'] ?>">



            <table class="info-table">
                <tr>
                    <td>Date: <?php echo $voucher_details['C_Date'] ?></td>
                    <td>Payment Date: <?php
                    $dateString = $voucher_details['W_Date'];
                    $date = new DateTime($dateString);
                    $formattedDate = $date->format('Y-m-d');
                    echo $formattedDate;
                    ?></td>
                </tr>
                <tr>
                    <td>Manual Reference No: <?php echo $voucher_details['ref_po'] ?></td>
                    <td>Payee's Name: <?php echo $voucher_details['Pay'] ?></td>
                </tr>
                <tr>
                    <td>Voucher No: <?php echo $voucher_details['Vnum'] ?></td>
                    <?php if (!empty($tour_details)) { ?>     
                        <td>Tour Name - <?php echo $tour_details['consignee']; ?></td>
                    <?php } ?>
                </tr>

                <?php if (!empty($tour_details)) {
                    $startdate = $tour_details['start_date'];
                    $enddate = $tour_details['end_date'];

                    $start = new DateTime($startdate);
                    $end = new DateTime($enddate);
                    $interval = $start->diff($end);
                    $numberOfDays = $interval->days;
                    ?>
                    <tr>
                        <td>No of Days: <?php echo $numberOfDays ?></td>    
                        <td>Passenger Count - <?php echo $tour_details['passenger_count']; ?></td>
                    </tr>
                    <tr>
                        <td>Tour ID: <?php echo $tour_details['manual_id'] ?></td>    
                        <td>Arrival Date - <?php echo $tour_details['start_date'] . ' ' . $tour_details['arrival_flight_time']; ?></td>
                    </tr>
                <?php } ?>


            </table>
    
            <!-- <br> -->



            <table class="table table-bordered">
              <?php
              $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
              $words = $formatter->format($voucher_details['Amount']);
              ?>
              <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($voucher_data as $voucher_data): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($voucher_data['Description']); ?></td>
                        <td><?php echo htmlspecialchars($voucher_data['Amount']); ?></td>
                    </tr>
                <?php endforeach; ?>
                
            </tbody>
        </table>

        <br>
        <p><strong>Total amount in words:</strong>  <?php echo $words . " " . "Rupees only" ?></p>
    <?php } ?>
            <div class="bank-details" style="float: center;">
                <h6>Bank Details</h6>
                <div><b><span>Name of the Bank:</span><?php echo !empty($voucher_details['bank_name']) ? $voucher_details['bank_name'] : ''; ?></b></div>
                <div><b><span>Branch Name:</span><?php echo !empty($voucher_details['branch_name']) ? $voucher_details['branch_name'] : ''; ?></b></div>
                <div><b><span>Account No.:</span> <?php echo !empty($voucher_details['sup_bank_acc']) ? $voucher_details['sup_bank_acc'] : ''; ?></b></div>
            </div>
            <br>

    <table class="signature-table">
        <tr>
            <td>Prepared by:</td>
            <td>Authorized by:</td>
            <td>Receiver's Signature:</td>
        </tr>
        <tr>
            <td><i><?php echo !empty($voucher_details['login']) ? $voucher_details['login'] : ''; ?></i></td>
            <td><i><?php echo !empty($voucher_details['Aut_user']) ? $voucher_details['Aut_user'] : ''; ?></i></td>
            <td>______________________</td>
        </tr>
    </table>
<!--     <table class="info-table" style="margin-top: 20px; border: 1px solid #ddd;">
        <thead>
            <tr>
                <th colspan="2" style="text-align: left; font-weight: bold;">Bank Details</th>
            </tr>
        </thead>
        <tbody>
            <?php //echo $voucher_details['sup_bank_acc']; ?><br><?php //echo $voucher_details['bank_name']; ?><br><?php //echo $voucher_details['branch_name']; ?>

        </tbody>
    </table>
-->


</div>

<!-- Bank Date Modal -->
<div class="modal fade" id="bankDateModal" tabindex="-1" aria-labelledby="bankDateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title" id="bankDateModalLabel"><i class="bi bi-calendar-check me-1"></i>Enter Bank Date</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold">Bank Date</label>
                <input type="date" id="bankDateInput" class="form-control" value="<?= date('Y-m-d') ?>">
                <div id="bankDateError" class="text-danger small mt-1 d-none">Please select a bank date.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" id="confirmFinalizeBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<?= view('layouts/scripts') ?>


<script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>


<script>
     // $(document).ready(function() {
        // var id = "<?php //echo $id; ?>";


        // <?php //if ($id == null):
     
            // echo 'window.print();';
        // endif; ?>
    // });


    let isSubmitting = false;

    function goBackAfterFinalize() {
        // Check if user is group_id 1 (only group 1 goes back to voucher_auth)
        var group_id = "<?php echo session()->get('group_id'); ?>";
        var tourid = "<?php echo $tourid; ?>";
        if (group_id == "1") {
            window.location.href = "<?php echo base_url() ?>dashboard2/voucher_auth?tour_id=" + tourid;
        } else {
            // All other users go to creditor_invoice/voucher_authentication
            window.location.href = "<?php echo base_url() ?>accounts/creditor_invoice/voucher_authentication";
        }
    }

    $("#confirmFinalizeBtn").on('click', function() {
        var bankDate = $('#bankDateInput').val();
        if (!bankDate) {
            $('#bankDateError').removeClass('d-none');
            return;
        }
        $('#bankDateError').addClass('d-none');
        $('#bankDateModal').modal('hide');

        var voucher_no = $('#voucher_no').val();

        $.post("<?php echo base_url() ?>report/bank_payment_check/mark_finalized", {
            pr_id: voucher_no,
            bank_date: bankDate,
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'   // tf-paydesk-r7e: this page's security token
        }, function(data) {
            if (data.status === 200) {
                Swal.fire({ icon: 'success', title: 'Finalized!', showConfirmButton: false, timer: 1200 })
                    .then(() => goBackAfterFinalize());
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                isSubmitting = false;
            }
        }, 'json').fail(function(x) {
            Swal.fire({ icon: 'error', title: 'Error', text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong!' });   // tf-paydesk-r7e: the server's reason
            isSubmitting = false;
        });
    });

    $(".btn_update").click(function() {
    // Check if a submission is already in progress
    if (isSubmitting) return;

    var status = $('#status').val();

    if (status == 1) {
        isSubmitting = true;
        $('#bankDateError').addClass('d-none');
        $('#bankDateInput').val('<?= date('Y-m-d') ?>');
        $('#bankDateModal').modal('show');
        return;
    }

    // Reject flow — remark required
    var btn = $(this);

    Swal.fire({
        title: 'Reject this voucher?',
        html: '<div class="text-start"><label class="form-label fw-semibold mb-1">Remark <span class="text-danger">*</span></label>' +
            '<textarea id="rejectRemarkInput" class="form-control" rows="3" placeholder="Reason for rejecting this voucher…"></textarea>' +
            '<div id="rejectRemarkError" class="text-danger small mt-1 d-none">A remark is required to reject a voucher.</div></div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Reject',
        focusConfirm: false,
        preConfirm: () => {
            var remark = document.getElementById('rejectRemarkInput').value.trim();
            if (!remark) {
                document.getElementById('rejectRemarkError').classList.remove('d-none');
                return false;
            }
            return remark;
        }
    }).then((result) => {
        if (!result.isConfirmed) return;
        var remark = result.value;

        isSubmitting = true;
        btn.prop("disabled", true).text("Processing...").hide(); // Disable and hide the button

        var id = $('#id').val();
        var bank_acc = $('#bank_acc').val();
        var type = $('#type').val();
        var amount = $('#amount').val();
        var sup = $('#sup').val();
        var payee = $('#payee').val();
        var voucher_no = $('#voucher_no').val();

        $.post("<?php echo base_url() ?>accounts/voucher_auth/update", { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
            update: "data",
            id: id,
            status: status,
            bank_acc: bank_acc,
            type: type,
            amount: amount,
            payee: payee,
            voucher_no: voucher_no,
            sup: sup,
            remark: remark,
        }, function(data) {
        if (data.status === 200) {
            Swal.fire({
                icon: 'success',
                title: 'Updated Successfully!',
                showConfirmButton: true
            }).then((result) => {
                if (result.isConfirmed) {
                    goBackAfterFinalize();
                }
            });
        } else if (data.status === 409 || data.status === 500) {
            // tf-payguard-r1: the authorisation guard's refusal or undo message, shown in full
            Swal.fire({
                icon: 'warning',
                title: data.status === 409 ? 'Not posted' : 'Posting failed and was undone',
                text: data.message || 'The voucher was not posted.',
                showConfirmButton: true
            });
        } else if ($.trim(data.status) === 400) {
            Swal.fire({
                icon: 'warning',
                title: 'Error!',
                text: data.message || 'Error occurred.',
                showConfirmButton: true
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Oops...',
                text: data.message || 'Something went wrong!',
                showConfirmButton: true
            });
        }
        }, "json").fail(function (x) {   // tf-paydesk-r7f: a refused request shows the server's reason
            Swal.fire({ icon: 'error', title: 'Not done', text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong. Please try again.' });
        }).always(() => {
            // Reset the flag to allow further submissions if needed
            isSubmitting = false;
        });
    });
});


</script>
</body>
</html>
