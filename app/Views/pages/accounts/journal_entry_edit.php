<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

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
                                        <h4 class="p-title">Edit Journal Entry</h4>
                                    </div>
                                </div>


                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="<?php echo base_url('accounts/journal_entry/edit_report') ?>" method="GET" accept-charset="utf-8">
                                                    <fieldset>



                                                        <div class="row clearfix">
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">From</label>
                                                                    <input type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" class="form-control" id="from" name="from" value="<?php echo isset($date1) ? $date1 : ""; ?>" />
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">To</label>
                                                                    <input type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" class="form-control" id="to" name="to" value="<?php echo isset($date2) ? $date2 : ""; ?>" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <br>

                                                        <button type="submit" class="btn btn-success  me-5px">Generate</button>
                                                        <button type="reset" class="btn btn-default reset">Cancel</button>
                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($is_search) { ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <?php if ($in_detail) { ?>
                                                        <div class="table-responsive">
                                                            <table id="mainTable" class="table table-hover">
                                                                <thead class="thead-dark">
                                                                    <tr>
                                                                        <th style="width: 10%;" scope="col">#</th>
                                                                        <th style="width: 10%;" scope="col">Date</th>
                                                                        <th style="width: 15%;" scope="col">Cashier</th>
                                                                        <th style="width: 15%;" scope="col">Account</th>
                                                                        <th scope="col">Description</th>
                                                                        <th class="text-end" style="width: 10%;" scope="col">DR</th>
                                                                        <th class="text-end" style="width: 10%;" scope="col">CR</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>



                                                                    <?php
                                                                    $x = 1;
                                                                    $daterange_total = 0;
                                                                    $totDr = $totCr = $totBal = 0;
                                                                    foreach ($transactions as $transaction) {

                                                                        $bal = $transaction['Dr'] - $transaction['Cr'];
                                                                        $daterange_total +=  $bal;
                                                                        $dr = number_format($transaction['Dr'], 2, '.', '');
                                                                        $cr = number_format($transaction['Cr'], 2, '.', '');

                                                                        ?>
                                                                        <tr>
                                                                            <td scope="row"><?= $x++; ?></td>
                                                                            <td><?= $transaction['Date']; ?></td>
                                                                            <td><?= $transaction['Cashier']; ?></td>
                                                                            <td><?php echo $transaction['PName'] ? $transaction['PName'] : 'N/A'; ?></td>
                                                                            <td><?= $transaction['Discription']; ?></td>
                                                                            <td class="text-end"><?php echo $dr; ?></td>
                                                                            <td class="text-end"><?php echo $cr; ?></td>

                                                                            <!-- <td class="text-end"><?= $bal; ?></td> -->

                                                                        </tr>
                                                                        <?php
                                                                        $totDr += $dr;
                                                                        $totCr += $cr;
                                                                        $totBal =  ($totDr - $totCr);
                                                                        $x++;
                                                                    } ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="alert alert-secondary bg-body-tertiary mt-3">
                                                            <div class="d-flex flex-column">
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="fw-normal">Total DR</span>
                                                                    <span class="fw-bold"><?= number_format($totDr, 2) ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="fw-normal">Total CR</span>
                                                                    <span class="fw-bold"><?= number_format($totCr, 2) ?></span>
                                                                </div>
                                                                <hr class="border-dark">
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="fw-normal">Balance</span>
                                                                    <span class="fw-bold"><?= number_format($totBal, 2) ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="table-responsive">
                                                            <table id="mainTable" class="table table-hover">
                                                                <thead class="thead-dark">
                                                                    <tr>
                                                                        <th>#</th>
                                                                        <th>Date</th>
                                                                        <th>Cashier</th>
                                                                        <th>Description</th>
                                                                        <th  class="text-end">Total Debit </th>
                                                                        <th  class="text-end">Total Cr</th>

                                                                        <th  class="text-center">View</th>
                                                                        <th  class="text-center">cancel</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    $x = 1;
                                                                    $daterange_total = 0;
                                                                    foreach ($transactions as $transaction) {

                                                                        $bal = $transaction['Dr'] - $transaction['Cr'];
                                                                        $daterange_total +=  $bal;
                                                                        ?>
                                                                        <tr>
                                                                         <td scope="row"><?= $x++; ?></td>
                                                                         <td><?= $transaction['Date']; ?></td>
                                                                         <td><?= $transaction['Cashier']; ?></td>

                                                                         <td><?= $transaction['Discription']; ?></td>
                                                                         <td class="text-end"><?= $transaction['Dr']; ?></td>
                                                                         <td class="text-end"><?= $transaction['Cr']; ?></td>
                                                                         <td class="text-center">
                                                                            <a class="btn btn-sm btn-success" href="<?= base_url('accounts/journal_entry/edit_report?from=' . $from . '&to=' . $to . '&id=' . $transaction['JE_INV']) ?>">
                                                                                <i class="bi bi-arrow-down-up"></i>
                                                                            </a>
                                                                        </td>
                                                                        <td class="text-center"><button class="btn btn-warning btn_cancel" data-date="<?php echo $transaction['Date']; ?>" value="<?php echo $transaction['JE_INV'] ?>" data-sup="<?php echo $transaction['JE_INV'] ?>" ><i class="bi bi-pencil-fill"></i></button></td>
                                                                    </tr>
                                                                    <?php
                                                                    $x++;
                                                                } ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } else {
                                ?>
                                <?php if ($is_search) { ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="alert alert-warning" role="alert">
                                                <strong>Warning!</strong> No data found.
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div> <!-- end .h-100-->

                    </div> <!-- end col -->
                </div>
                <!-- Modal -->
                <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="cancelModalLabel">Edit Transaction Date</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to edit the transaction on <span id="transactionDate"></span>?</p>
                                <div class="mb-3">
                                    <label for="cancelDate" class="form-label">Enter Date:</label>
                                    <input type="date" class="form-control" id="cancelDate">
                                </div>
                                <div class="mb-3"><?php /* tf-e8-access-r1: reason required for corrections */ ?>
                                    <label for="tfReasonText" class="form-label">Reason (required):</label>
                                    <input type="text" class="form-control" id="tfReasonText" maxlength="300" autocomplete="off" placeholder="Why is this needed? (at least 5 characters)">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-danger" id="confirm">Confirm</button>
                            </div>
                        </div>
                    </div>
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

<script>
    $('.js-example-basic-single').select2({
        placeholder: " Select",
        allowClear: true
    });

        // document.addEventListener("DOMContentLoaded", function() {
        //     flatpickr("#drange", {
        //         mode: "range",
        //         dateFormat: "Y-m-d"
        //     });
        // });

        $("#date_form").on('submit', function(e) {
            e.preventDefault();

            var date1 = $("#drange1").val();
            var date2 = $("#drange2").val();
            var cus = $("#customer_name").val();

            if (cus) {
                window.location.href = "<?php echo base_url(); ?>accounts/creditor_invoice/expenses_report?cus=" + cus;
                return false;
            }

            if (date1 == '' || date2 == '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Start Date and End Date are required!',
                });
            } else {
                window.location.href = "<?php echo base_url(); ?>accounts/creditor_invoice/expenses_report?startdate=" + date1 + "&enddate=" + date2;
            }

        });




        document.addEventListener('DOMContentLoaded', function() {
    // Get all cancel buttons
    const cancelButtons = document.querySelectorAll('.btn_cancel');

    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const transactionId = this.value; // Get the transaction ID
            const transactionDate = this.getAttribute('data-date'); // Get the date attribute
            // alert(transactionId);

            // Set the transaction date in the modal
            document.getElementById('transactionDate').innerText = transactionDate;
            $('#cancelDate').val(transactionDate);


            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
            modal.show();

            // Optional: Handle the confirmation button to perform the cancel action
            document.getElementById('confirm').onclick = function() {
              var id = transactionId;


              var menu = $("#menu").val();
              var new_date =  $('#cancelDate').val();
               // alert(new_date);
               // alert(transactionDate);

               if(new_date>=transactionDate){
                 var tfReason = tfReasonFrom('#tfReasonText'); if (tfReason === null) { return; } // tf-e8-access-r1

                 $.post("<?php echo base_url() ?>accounts/journal_entry/edit_date", {
                    reverse: "data",
                    id: id,
                    transactionDate: new_date,
                    reason: tfReason, // tf-e8-access-r1
                }, function(data) {
                    if (data.status === 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Successfully Canceled!',
                            showConfirmButton: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                if (status == 1) {
                                 
                                } else {
                                    location.reload();
                                }

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

             }else {
                alert('Invalid Date!');
            }



        };
    });
    });
});


</script>



</body>

</html>