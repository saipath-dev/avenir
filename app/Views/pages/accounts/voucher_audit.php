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
                    <h4 class="p-title">Voucher Payment Audit</h4><br>

                  </div>
                </div>

                <div class="row clearfix mt-3">
                  <div class="col-md-12">
                    <div class="card">
                      <div class="body p-4"> 
                        <div>
                          <?php if (!empty($tour_details)) { ?>
                            <input type="hidden" name="tour_id" id="tour_id" value="<?php echo $tour_details['tour_id'] ?>">
                          <?php } ?>
                          <h4 style="font-family: sans-serif;">
                            <?php if (!empty($tour_details)) { ?>
                              Tour Name - <?php echo $tour_details['consignee']; ?>  

                              <a href="<?= base_url('tour/tour_dashboard/monthly_dashboard?tour_id=' .$tour_details['tour_id']); ?>" class="btn btn-soft-success btn-md" target="_blank" data-toggle="tooltip" title="Itinerary Print"><i class="bi bi-eye-fill"></i></a>
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
                                <th>Refference Number</th>
                                <th>Amount</th>
                                <!-- <th>Invoice</th> -->
                                <!-- <th>Description</th> -->
                                <th>Approval Request From</th>
                                <th>Action</th>
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
                                    <button class="btn btn-sm btn-danger mb10 btn_cancel" data-status="2" data-sup="<?php echo $value->sup ?>" data-bank="<?php echo $value->Bank_acc ?>" data-pay="<?php echo $value->Pay ?>" data-amnt="<?php echo round($value->Amount);  ?>" data-type="<?php echo $value->Pay_Type ?>" value="<?php echo $value->Voucher; ?>" data-tkn="<?php echo $value->Voucher; ?>" data-tourid="<?php echo $t_id; ?>">Cancel</button>

                                  </td>
                                </tr>
                                <?php
                                $count++;
                              }
                              ?>
                            </tbody>
                          </table>
                          <div class=" mt-3" style="float: right;margin-right;" >
                            <a  class="btn btn-success btn_finalize btn-lg" id="finalizeButton">
                             <i class="bi bi-check-circle-fill"></i> Finalize
                           </a>
                         </div>
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



  $(".btn_finalize").click(function() {

    // Show confirmation dialog before proceeding
    Swal.fire({
      title: 'Are you sure?',
      text: "You want to finalize this tour update!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, finalize it!'
    }).then((result) => {
      if (result.isConfirmed) {
            var tour_id = $('#tour_id').val(); // Get the tour_id from the input field

            if(tour_id=='' ||tour_id==undefined){
              tour_id = 'other';
            }

            // Disable and hide the button
            $(this).prop("disabled", true).text("Processing...").hide(); 

            // Send the AJAX request
            $.post("<?php echo base_url() ?>accounts/voucher_audit/update_tour", {
              update: "data",
              tour_id: tour_id
            }, function(data) {
              if (data.status === 200) {
                Swal.fire({
                  icon: 'success',
                  title: 'Updated Successfully!',
                  showConfirmButton: true
                }).then((result) => {
                  if (result.isConfirmed) {
                            // window.location.href = "<?php echo base_url() ?>accounts/voucher_audit"; // Redirect on success
                            location.reload();
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
            }, "json").always(() => {
                // Any cleanup after request (if needed)
              });
          }
        });
  });







  $(".btn_cancel").click(function() {
    Swal.fire({
      title: 'Are you sure?',
      text: "You want to cancel this voucher!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {

        var id = $(this).val();
                        // var status = $(this).attr('data-status');
                        var supplier = $(this).attr('data-sup');

                        var menu = $("#menu").val();

                        $.post("<?php echo base_url() ?>report/voucher_report/update", {
                          update: "data",
                          id: id,
                            // status: status,
                            supplier: supplier
                          }, function(data) {
                            if (data.status === 200) {
                              Swal.fire({
                                icon: 'success',
                                title: 'Successfully Canceled!',
                                showConfirmButton: true
                              }).then((result) => {
                                if (result.isConfirmed) {
                                  location.reload();

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
                      }

                    });
  });

</script>
</body>




</html>