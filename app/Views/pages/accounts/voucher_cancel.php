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
                    <h4 class="p-title">Delete Voucher Payments</h4><br>

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
                              Tour ID - <?php echo $tour_details['tour_id']; ?>
                            <?php } ?>
                          </h4>
                        </div>
                        <div class="table-responsive">
                          <table class="table table-hover js-basic-example dataTable table-custom">
                            <thead class="thead-dark">
                              <tr>
                                <th>Voucher Date</th>
                                <th>Voucher Number</th>
                                <th>Type</th>

                                <th>Pay</th>
                                <th>Bank</th>
                                <!-- <th>Cheque Date</th> -->
                                <th>Refference Number</th>
                                <th>Amount</th>
                                <th>Invoice</th>
                                <th>Description</th>
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

                                  <td> <?php echo $value->Pay; ?></td>
                                  <td> <?php echo $value->Bank; ?></td>
                                  <!-- <td> <?php //echo $date; 
                                  ?></td> -->
                                  <td> <?php echo $value->Chq_No; ?></td>
                                  <td> <?php echo $value->Amount; ?></td>
                                  <td> <?php echo $value->Invoice; ?></td>
                                  <td> <?php echo $value->Description; ?></td>
                                  <td> <?php echo $value->login; ?></td>
                                  <td align="center">
                                    <button class="btn btn-sm btn-success mb10 btn_update"  value="<?php echo $value->id; ?>" data-tkn="<?php echo $value->V_tkn; ?>"><i class="bi bi-x-square-fill"></i></button>


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

  // Show confirmation dialog
  Swal.fire({
    title: 'Are you sure?',
    text: 'This action cannot be undone!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'No, cancel!'
  }).then((result) => {
    if (result.isConfirmed) {
      // Proceed with the delete operation
      $.post("<?php echo base_url() ?>accounts/voucher_payments/voucher_delete", {
        voucher_delete: "data",
        id: id
      }, function(data) {
        if (data.status === 200) {
          Swal.fire({
            icon: 'success',
            title: 'Updated Successfully!',
            showConfirmButton: true
          }).then(() => {
            location.reload();
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




      $(".modal_close").click(function() {
        $('#view_details').modal('hide');
      // $('#chq_dtlz').modal('hide');
      $(':input[type="submit"]').prop('disabled', false);
    });
  </script>
</body>




</html>