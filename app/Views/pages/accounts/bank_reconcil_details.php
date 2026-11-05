<?= view('layouts/head') ?>


<body>

  <!-- Begin page -->
  <div id="layout-wrapper">

    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <style type="text/css">
      .table-responsive {
        max-height: 400px;
        /* Set the max height for your table */
        overflow-y: auto;
        /* Enable vertical scrolling */
      }

      .table-responsive::-webkit-scrollbar {
        width: 8px;
        /* Width of the scrollbar */
      }

      .table-responsive::-webkit-scrollbar-thumb {
        background: #888;
        /* Color of the scrollbar */
        border-radius: 4px;
        /* Rounded corners for the scrollbar */
      }

      .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #555;
        /* Darker scrollbar on hover */
      }


      .modal-content {
        border-radius: 10px;
        /* Rounded corners */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        /* Subtle shadow */
      }


      .table th,
      .table td {
        vertical-align: middle;
        /* Center content vertically */
      }

      .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.1);
        /* Light blue on hover */
      }

      .modal-header {
        background-color: #f8f9fa;
        /* Light background for header */
        border-bottom: 1px solid #dee2e6;
        /* Light border at bottom */
      }
    </style>

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
                    <h4 class="p-title">Bank Reconcillation Details</h4>
                  </div>
                </div>

                <div class="row clearfix mt-3">
                  <div class="col-md-12">
                    <div class="card p-4">
                      <div class="body">
                        <form action="<?= base_url('accounts/bank_reconcil/details') ?>" id="form_submit" accept-charset="utf-8" method="get">
                          <div class="row">

                           <div class="col-md-6">
                            <div class="mb-3">
                              <label class="form-label" for="drange2">End Date</label>
                              <input class="form-control txt_nav date" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" id="drange2" name="drange2" placeholder="Select Date" 
                              value="<?php 
                              if (!empty($date2)) {
                                echo $date2;
                                } else {
                // Calculate the last date of the current month
                                  echo (new DateTime('last day of this month'))->format('Y-m-d');
                                } 
                                ?>" />
                              </div>
                            </div>

                            <div class="col-md-6">
                              <div class="mb-3">
                                <label class="form-label" for="supplier_name">Bank<font color="#FF0000"><strong>*</strong></font></label>
                                <select name="bank" id="bank" class="js-example-basic-single form-control" style="width: 100%;" required>
                                  <option value="">Select...</option>
                                  <?php
                                  foreach ($load_bank as $bank) {
                                    $selected = $bank['L_Code'] == $bank_lcode ? 'selected' : '';
                                    // $selected = '';
                                    echo '<option value="' . $bank['L_Code'] . '"' . $selected . '>' . $bank['PName'] . '</option>';
                                  }
                                  ?>
                                </select>
                              </div>

                            </div>
                            <div class="mb-3" style="padding-top: 10px; float:right;">
                              <button  type="submit" class="btn btn-success w-100px me-5px btn_search"><i class="bi bi-search"></i>Search
                              </button>

                            </div>
                          </form>

                        </div>
                      </div>
                    </div>
                  </div>


                  <?php if (!empty($result)){ ?>

                    <div class="row clearfix mt-3">
                      <div class="col-md-12">
                        <div class="card">
                          <div class="body">

                            <div class="row" id="print-data" style="padding:25px; background-color: white;">
                              <div class="col-md-12">
                                <div class="box">
                                 <div class="box-header">
                                  <table class="w-100">
                                    <tr>
                                      <td class="text-center pb-3">
                                        <h5 class="mt0"> <b>Avenir Holidays (Pvt) Ltd</b></h5>
                                        <p>No. 94/2, Lauries Rd,<br>Colombo 4<br>Tel: +94-77-395-7956, +94 112 551 880</p>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td class="text-center pb-3">
                                        <h5 class="mt-3">Bank Reconciliation Details</h5>
                                      </td>
                                    </tr>
                                  </table>

                                  <!-- Reconciliation Summary -->
                                  <table class="w-100 mt-4">
                                    <tr class="border-bottom table-header fs-mons">
                                      <td><b>Beginning Balance</b></td>
                                      <td class="text-end"><b><?php echo number_format($opening_balance, 2); ?></b></td>
                                    </tr>
                                  </table>
                                  <!-- Uncleared Transactions -->
                                  <table class="w-100 mt-4">
                                    <tr class="fs-mons">
                                      <td><b>Cleared Transaction</b></td>
                                      <td class="text-end"><b></b></td>
                                    </tr>
                                  </table>

                                  <!-- Reconciliation Data -->
                                  <table class="w-100 mt-4">
                                    <tr>
                                      <td colspan="6" class="pt-3 fs-mons-bold">
                                        <b><h6>Deposit & Credits - <?php echo $result['count_dr_positive']; ?> items</h6></b>
                                      </td>
                                    </tr>
                                    <?php 
                                    $cleared_deposit =0;
                                    $cleared_dr_total =0;
                                    $ending_balnce_cleared =0;
                                    foreach ($details as $value) {
                                      if ($value->Dr > 0) { 

                                        $cleared_deposit += $value->Dr;
                                        $cleared_dr_total += $value->Dr;


                                        ?>
                                        <tr>
                                          <td class="pt-3 fs-mons-bold"><b><?php echo $value->PName; ?></b></td>
                                          <td class="pt-3 fs-mons-bold"><b><?php echo $value->Date; ?></b></td>
                                          <td class="pt-3 fs-mons-bold"><b><?php echo $value->Discription; ?></b></td>
                                          <td style="align-content: center;"><b><i class="bi bi-check-circle-fill"></i></b></td>
                                          <td class="pt-3 fs-mons-bold text-end "><b><?php echo $value->Dr; ?></b></td>
                                          <td class="text-end pt-3 fs-mons-bold"><?php echo number_format($cleared_deposit, 2); ?></td>
                                        </tr>
                                      <?php }
                                    } ?>
                                    <br>
                                    <tr class="table-footer">
                                      <td colspan="4"><b>Total Deposits</b></td>
                                      <td class="text-end"><?php echo number_format($cleared_deposit, 2); ?></td>
                                      <td class="text-end"><?php echo number_format($cleared_dr_total, 2); ?></td>
                                    </tr>
                                    <br><br>

                                    <tr>
                                      <td colspan="6" class="pt-3 fs-mons-bold">
                                        <b><h6>Checks and Payments - <?php echo $result['count_cr_positive']; ?> items</h6></b>
                                      </td>
                                    </tr>
                                    <?php
                                                                    // $cleared_payments =0;
                                    $cleared_cr_total =0;
                                    foreach ($details as $value) {
                                      if ($value->Cr > 0) {
                                        $cleared_cr_total += $value->Cr;

                                        ?>
                                        <tr>
                                          <td class="pt-3 fs-mons-bold"><b><?php echo $value->PName; ?></b></td>
                                          <td class="pt-3 fs-mons-bold"><b><?php echo $value->Date; ?></b></td>
                                          <td class="pt-3 fs-mons-bold"><b><?php echo $value->Discription; ?></b></td>
                                          <td style="align-content: center;"><b><i class="bi bi-check-circle-fill"></i></b></td>
                                          <td class="pt-3 fs-mons-bold text-end"><b><?php echo $value->Cr; ?></b></td>
                                          <td class="text-end pt-3 fs-mons-bold"><?php echo number_format($cleared_cr_total, 2); ?></td>
                                        </tr>
                                      <?php }
                                    } ?>
                                    <tr class="table-footer">
                                      <td colspan="4"><b>Total Payments</b></td>
                                      <td class="text-end"><?php echo number_format($cleared_cr_total, 2); ?></td>
                                      <td class="text-end"><?php echo number_format($cleared_cr_total, 2); ?></td>
                                    </tr>
                                    <?php

                                    $cleared_balnce =  $result['dr'] - $result['cr'];
                                    $current_balance = $opening_balance+ $cleared_balnce;
                                    $uncleared_balane = $uncleared_entries['dr'] - $uncleared_entries['cr'];
                                    $ending_balnce_cleared = $cleared_cr_total+$cleared_dr_total+$opening_balance;
                                                                    // $final_balance =  $ending_balnce_cleared;

                                    ?>

                                  </table>

                                  <!-- Cleared Balance -->
                                  <table class="w-100 mt-4">
                                    <tr class="border-bottom table-header fs-mons">
                                      <td><b>Cleared Balance</b></td>
                                      <td class="text-end"><b><?php echo number_format(($cleared_dr_total-$cleared_cr_total)+$opening_balance, 2); ?></b></td>
                                    </tr>
                                  </table>
                                  <br>

                                  <!-- Uncleared Transactions -->
                                  <table class="w-100 mt-4">
                                    <tr class="fs-mons">
                                      <td><b>Uncleared Transaction</b></td>
                                      <td class="text-end"><b></b></td>
                                    </tr>
                                  </table>

                                  <table class="w-100 mt-4">
                                    <tr>
                                      <td colspan="6" class="pt-3 fs-mons-bold">
                                        <b><h6>Deposit & Credits - <?php echo $uncleared_entries['count_dr_positive']; ?></h6></b>
                                      </td>
                                    </tr>
                                    <?php
                                    $uncleared_deposit =0;
                                    $uncleared_dr_total =0;
                                    $uncleared_dr_total =0;

                                    foreach ($uncleared_details as $undt) {
                                      if ($undt->Dr > 0) { 
                                       $uncleared_deposit += $undt->Dr;
                                       $uncleared_dr_total += $undt->Dr;


                                       ?>
                                       <tr>
                                        <td class="pt-3 fs-mons-bold"><b><?php echo $undt->PName; ?></b></td>
                                        <td class="pt-3 fs-mons-bold"><b><?php echo $undt->Date; ?></b></td>
                                        <td class="pt-3 fs-mons-bold"><b><?php echo $undt->Discription; ?></b></td>
                                        <td style="align-content: center;"><b></b></td>
                                        <td class="pt-3 fs-mons-bold text-end "><b><?php echo $undt->Dr; ?></b></td>
                                        <td class="text-end pt-3 fs-mons-bold"><?php echo number_format($uncleared_dr_total, 2); ?></td>
                                      </tr>
                                    <?php }
                                  } ?>

                                  <br>
                                  <tr class="table-footer">
                                    <td colspan="4"><b>Total Deposits</b></td>
                                    <td class="text-end"><?php echo number_format($uncleared_deposit, 2); ?></td>
                                    <td class="text-end"><?php echo number_format($uncleared_dr_total, 2); ?></td>
                                  </tr>
                                  <tr>
                                    <td colspan="6" class="pt-3 fs-mons-bold">
                                      <b><h6>Checks and Payments - <?php echo $uncleared_entries['count_cr_positive']; ?> items</h6></b>
                                    </td>
                                  </tr>
                                  <?php 
                                  $uncleared_cr_total = 0;
                                  foreach ($uncleared_details as $undt) {
                                    if ($undt->Cr > 0) { 
                                     $uncleared_cr_total += $undt->Cr;

                                     ?>
                                     <tr>
                                      <td class="pt-3 fs-mons-bold"><b><?php echo $undt->PName; ?></b></td>
                                      <td class="pt-3 fs-mons-bold"><b><?php echo $undt->Date; ?></b></td>
                                      <td class="pt-3 fs-mons-bold"><b><?php echo $undt->Discription; ?></b></td>
                                      <td style="align-content: center;"><b></b></td>
                                      <td class="pt-3 fs-mons-bold text-end "><b><?php echo $undt->Cr; ?></b></td>
                                      <td class="text-end pt-3 fs-mons-bold"><?php echo number_format($uncleared_cr_total, 2); ?></td>
                                    </tr>
                                  <?php }
                                } ?>
                                <tr class="table-footer">
                                  <td colspan="4"><b>Total Payments</b></td>
                                  <td class="text-end"><?php echo number_format($uncleared_cr_total, 2); ?></td>
                                  <td class="text-end"><?php echo number_format($uncleared_cr_total, 2); ?></td>
                                </tr>

                              </table>

                              <!-- Uncleared Balance -->
                              <table class="w-100 mt-4">
                                <tr class="border-bottom table-header fs-mons">
                                  <td><b>Uncleared Balance</b></td>
                                  <td class="text-end"><b><?php echo number_format($uncleared_dr_total-$uncleared_cr_total, 2); ?></b></td>
                                </tr>
                              </table>

                              <!-- Register Balance -->
                              <table class="w-100 mt-4">
                                <tr class="fs-mons">
                                  <td><b>Register Balance as of <?php echo $date; ?></b></td>
                                  <td class="text-end"><b><?php echo number_format(($ending_balnce_cleared+($uncleared_dr_total-$uncleared_cr_total)), 2); ?></b></td>
                                </tr>
                              </table>

                            </div>
                            <br>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            <?php } ?>

          </div> <!-- end .h-100-->

        </div> <!-- end col -->
      </div>

    </div>
    <!-- container-fluid -->
  </div>
  <!-- End Page-content -->

  <?= view('layouts/brand') ?>

</div>
<!-- end main content-->

</div>


<div class="modal fade" id="hotelreservationModal" tabindex="-1" aria-labelledby="hotelreservationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"> <!-- Increase width using modal-lg -->
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="hotelreservationModalLabel">Details</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Table Section -->
        <table class="table table-striped table-hover mt-3"> <!-- Added table-hover for better visual feedback -->
          <thead>
            <tr>
              <th>ID</th>
              <th>Description</th>
              <th>Date</th>
              <th>Amount</th>
            </tr>
          </thead>
          <tbody id="table_body">
            <!-- Dynamic rows will be added here -->
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-info btn-sm repost"><i class="bi bi-reply"></i> Repost</button>
      </div>
    </div>
  </div>
</div>

<!-- END layout-wrapper -->

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>

<script>

  $(document).ready(function() {
    var menu = $("#menu").val();
    $(".default-daterange").daterangepicker({
      opens: "right",
      format: "YYYY-MM-DD",
      separator: " to ",
      startDate: moment(),
      endDate: moment(),
    }, function(start, end) {
      var branch_id = $("#branch_id").val();
      $(".default-daterange input").val(start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD"));
      var date = start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD");

    });
  });



    //   $(".btn_search").click(function() {
    //     // var menu = $("#menu").val();
    //     // var date1 = $("#drange1").val();
    //     var date2 = $("#drange2").val();
    //     var bank = $("#bank").val();
    //   // var stmnt_val = $("#stmnt_val").val();
    //   // var lcode = $('#bank option:selected').attr('data-lcode');


    //   window.open("<?php echo base_url() ?>accounts/bank_reconcil/summary?date2=" + date2 + "&bank=" + bank);



    // });

  </script>


</body>

</html>