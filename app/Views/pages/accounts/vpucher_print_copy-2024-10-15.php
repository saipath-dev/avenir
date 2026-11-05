<!DOCTYPE html>
<html lang="en">
<?= view('layouts/head') ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avenir Holidays :: <?= !empty($invoice) ? $invoice['invoice_id'] : "" ?></title>
    <link href="<?php echo base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        .content {
            width: 100%;
            height: 100%;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            height: 100px;
            width: auto;
        }

        .company-info {
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .button-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
    </style>

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
        </div>
        <div class="header">
            <img src="<?php echo base_url(); ?>assets/images/logo-light.png" alt="Logo">
            <?php if ($voucher_details) { ?>
           
                <?php if ($voucher_details->Aflag == 3) { ?>
                    <img src="<?php echo base_url(); ?>assets/img/voucher_cancelled.jpg" alt="Voucher Cancelled" style="max-width: 100%; margin-top: 10px;">
                <?php } else if ($voucher_details->Aflag == 1) { ?>
                    <img src="<?php echo base_url(); ?>assets/img/voucher_approved.jpg" alt="Voucher Approved" style="max-width: 100%; margin-top: 10px;">
                <?php } ?>

            </div>


            <div class="company-info">
                <strong>Avenir Holidays (Pvt) Ltd</strong><br>
                No. 94/2 Lauries Rd, Colombo 4<br>
                TEL: +94 77 395 7956 | URL: www.avenirholidays.com | E-Mail: info@avenirholidays.com
            </div>
            <input type="hidden" name="id" id="id" value="<?php echo $id ?>">
            <input type="hidden" name="status" id="status" value="<?php echo $status ?>">
            <input type="hidden" name="bank_acc" id="bank_acc" value="<?php echo $bank_acc ?>">
            <input type="hidden" name="type" id="type" value="<?php echo $type ?>">
            <input type="hidden" name="amount" id="amount" value="<?php echo $amount ?>">
            <input type="hidden" name="sup" id="sup" value="<?php echo $sup ?>">
            <input type="hidden" name="payee" id="payee" value="<?php echo $payee ?>">
            <input type="hidden" name="menu" id="menu" value="<?php echo $menu ?>">

            <table width="100%" border="0" style="margin-left: 20px;">
                <tbody>
                    <tr style="border-bottom: none" class="cell">
                        <td width="15%">Date :</td>
                        <td width="45%"> <?php echo $voucher_details->INV_Date ?></td>
                        <td width="15%">Voucher No:</td>
                        <td width="25%"><?php echo $voucher_details->C_Number ?></td>
                    </tr>
                    <tr style="border-bottom: none" class="cell">
                        <td>Payment Date:</td>
                        <td><?php  
                        $dateString = $voucher_details->C_Date;
                        $date = new DateTime($dateString);
                        $formattedDate = $date->format('Y-m-d'); 
                        echo $formattedDate; 
                        ?></td>
                        <td>Ref No:</td>
                        <td><?php echo $voucher_details->ref_po ?></td>
                    </tr>
                    <tr style="border-bottom: none" class="cell">
                        <td>Payee</td>
                        <td><?php echo $voucher_details->Payee ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr style="border-bottom: none" class="cell">
                        <td>Account</td>
                        <td>IOU Advance</td>
                        <td>Amount</td>
                        <td></td>
                    </tr>
                    <tr style="border-bottom: none" class="cell">
                        <td>Description</td>
                        <td><?php echo $voucher_details->Description ?> </td>
                        <td>Ref Account</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            <br>
            <table width="100%" style="margin-left: 20px;">
                <?php
                $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
                $words = $formatter->format($voucher_details->Amount);
                ?>
                <tr>
                    <td width="60%"> <?php echo $words . " " . "only" ?></td>
                    <td width="40%">Total Payment:<u>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $voucher_details->Amount ?>&nbsp;&nbsp;&nbsp;&nbsp;</u></td>
                </tr>

            <?php } else { ?>
                <p>No voucher details available.</p>
            <?php } ?>
        </table>
        <br><br><br>

        <table width="100%" border="0" class="table" style="line-height: 2">
            <tr align="center">
                <td><br>.................................. <br>Prepared By</td>
                <td><br>.................................. <br>Checked By</td>
                <td><br>.................................. <br>Approved By</td>
                <td><br>.................................. <br>Recieved By</td>
            </tr>
        </table>
        <div class="d-flex flex-column w-100">
            <span class="text-center mb-4">50% deposit of CIF price required within (5) days and if not your order will be automatically cancelled.</span>
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


     $(".btn_update").click(function() {
      var id = $('#id').val();
      var status = $('#status').val();
      var bank_acc = $('#bank_acc').val();
      var type = $('#type').val();
      var amount = $('#amount').val();
      var sup = $('#sup').val();
      var payee = $('#payee').val();


      $.post("<?php echo base_url() ?>accounts/voucher_auth/update", {
        update: "data",
        id: id,
        status: status,
        bank_acc: bank_acc,
        type: type,
        amount:amount,
        payee:payee,
        sup:sup,
    }, function(data) {
        if (data.status === 200) {
          Swal.fire({
            icon: 'success',
            title: 'Updated Successfully!',
            showConfirmButton: true
        }).then((result) => {
            if (result.isConfirmed) {
              // if(status==1){
                window.location.href = "<?php echo base_url() ?>accounts/creditor_invoice/voucher_authentication";
            // }else{
            //     location.reload();
            // }

              // location.reload();
          }
      });
    } else if ($.trim(data.status) === 400) {
      Swal.fire({
        icon: 'warning',
        title: 'Error!',
        text: 'Error occurred.',
        showConfirmButton: false,
        timer: 1500
    });
  } else {
      Swal.fire({
        icon: 'warning',
        title: 'Oops...',
        text: 'Something went wrong!',
        showConfirmButton: false,
        timer: 1500
    });
  }
}, "json");
  });
</script>
</body>

</html>
