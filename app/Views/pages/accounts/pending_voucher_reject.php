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
                    <h4 class="p-title">Pending Vouchers</h4><br>

                  </div>
                </div>

                <div class="row clearfix mt-3">
                  <div class="col-md-12">
                    <div class="card">
                      <div class="body p-4">
                        <div>
                          <h4 style="font-family: sans-serif;">
                            <?php if (!empty($tour_details)) { ?>
                              Tour Name - <?php echo $tour_details['consignee']; ?>  

                              <a href="<?= base_url('tour/print/itinerary?tour_id=' .$tour_details['tour_id']); ?>" class="btn btn-soft-success btn-md" target="_blank" data-toggle="tooltip" title="Itinerary Print"><i class="bi bi-eye-fill"></i></a>
                              <br>
                              Tour ID - <?php echo $tour_details['manual_id']; ?>

                              
                              <?php

                              $t_id =$tour_details['tour_id'];


                            }else{
                              $t_id =0;

                            } ?>
                          </h4>
                        </div>
                        <div class="table-responsive">
                          <table class="table table-hover js-basic-example dataTable table-custom">
                            <thead class="thead-dark">
                              <tr>
                                <th>Voucher Date</th>
                                <th>Voucher Number</th>
                                <th>Type</th>
                                <th>Division</th>
                                <th>Pay</th>
                                <th>Bank</th>
                                <!-- <th>Cheque Date</th> -->
                                <th>Reference Number</th>
                                <th>Amount</th>
                                <!-- <th>Invoice</th> -->
                                <!-- <th>Description</th> -->
                                <th>Approval Request From</th>
                                <th>Reject</th>
                              </tr>
                            </thead>
                            <tbody>
                              <!-- itertate through $admin array -->
                              <?php
                              $count = 1;
                              foreach ($voucher_request as $value) {

                                // $dateTime = new DateTime($value->C_Date);
                                $dateTime = new DateTime($value->W_Date);
                                $date = $dateTime->format('Y-m-d');
                                ?>
                                <tr class="gradeX">
                                  <td> <?php echo $date; ?></td>
                                  <td> <?php echo $value->Voucher; ?></td>
                                  <td> <?php echo $value->Pay_Type; ?></td>
                                  <td> <?php echo $value->dv; ?></td>
                                  <td> <?php echo $value->Pay; ?></td>
                                  <td> <?php echo $value->Bank; ?></td>
                                  <!-- <td> <?php //echo $date; 
                                  ?></td> -->
                                  <td> <?php echo $value->Chq_No; ?></td>
                                  <td> <?php echo $value->Amount; ?></td>
                                  <!-- <td> <?php //echo $value->Invoice; ?></td> -->
                                  <!-- <td> <?php //echo $value->Description; ?></td> -->
                                  <td> <?php echo $value->login; ?></td>
                                  <td align="center">
                                
                                    <button class="btn btn-sm btn-danger mb10 btn_update" data-status="2" data-sup="<?php echo $value->sup ?>" data-bank="<?php echo $value->Bank_acc ?>" data-pay="<?php echo $value->Pay ?>" data-amnt="<?php echo round($value->Amount);  ?>" data-type="<?php echo $value->Pay_Type ?>" value="<?php echo $value->id; ?>" data-tkn="<?php echo $value->Voucher; ?>" data-tourid="<?php echo $t_id; ?>">Reject</button>

                                  </td>
                                </tr>
                                <?php
                                $count++;
                              }
                              ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div> <!-- end .h-100-->

            </div> <!-- end col -->
          </div>

        </div>

        <!-- Modal -->
        <div class="modal fade" id="view_details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Cheque Details</h5>
                <button type="button" class="btn btn-secondary close modal_close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <!-- Table Content -->
                <table class="table table-bordered" id="inv_dtl">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Supplier</th>
                      <th>Amount</th>
                      <!-- <th>Description</th> -->
                    </tr>
                  </thead>
                  <tbody>

                  </tbody>
                </table>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal_close" data-dismiss="modal">Close</button>
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


  <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
  <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
  <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
  <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
  <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>

  <script>
    $('.js-example-basic-single').select2({
      placeholder: " Select",
      allowClear: true
    });


    $(".btn_update").click(function() {
      var id = $(this).val();
      var status = $(this).attr('data-status');
      var bank_acc = $(this).attr('data-bank');
      var type = $(this).attr('data-type');
      var amount = $(this).attr('data-amnt');
      var sup = $(this).attr('data-sup');
      var payee = $(this).attr('data-pay');
      var v_token = $(this).attr('data-tkn');
      var tourid = $(this).attr('data-tourid');
      var menu = $("#menu").val();

      var url = "<?php echo base_url() ?>accounts/voucher_print/" + v_token +
      "?id=" + encodeURIComponent(id) +
      "&status=" + encodeURIComponent(status) +
      "&bank_acc=" + encodeURIComponent(bank_acc) +
      "&type=" + encodeURIComponent(type) +
      "&amount=" + encodeURIComponent(amount) +
      "&sup=" + encodeURIComponent(sup) +
      "&payee=" + encodeURIComponent(payee) +
      "&tourid=" + encodeURIComponent(tourid);

      window.location.href = url;

    });

    $(".btn_details").click(function() {
      var voucher_num = $(this).val();

      $.get('<?php echo base_url() ?>accounts/voucher_auth/getVoucherDetails', {
        v_num: voucher_num
      }, function(data) {
        try {
          // Parse JSON data
          var parsedData = JSON.parse(data);
        } catch (e) {
          console.error('Failed to parse JSON data:', e);
          return;
        }

        var tableData = '';

        if (parsedData && Array.isArray(parsedData) && parsedData.length > 0) {
          $.each(parsedData, function(index, item) {
            tableData += "<tr>";
            tableData += "<td>" + (index + 1) + "</td>";
            tableData += "<td>" + (item.employee || 'N/A') + "</td>";
            tableData += "<td>" + (item.Amount || 'N/A') + "</td>";
            // tableData += "<td>" + (item.Description || 'N/A') + "</td>";
            tableData += "</tr>";
          });
        } else if (parsedData.error) {
          // Handle errors from server-side
          tableData += "<tr><td colspan='4'>" + parsedData.error + "</td></tr>";
        } else {
          tableData += "<tr><td colspan='4'>No data available</td></tr>";
        }

        $('#inv_dtl tbody').html(tableData);

      }, 'text'); // Changed from 'json' to 'text' and manually parse JSON
    });

    $(".modal_close").click(function() {
      $('#view_details').modal('hide');
      // $('#chq_dtlz').modal('hide');
      $(':input[type="submit"]').prop('disabled', false);
    });
  </script>
</body>




</html>