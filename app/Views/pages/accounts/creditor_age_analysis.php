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
                    <h4 class="p-title">Creditor Age Analysis</h4>
                  </div>
                </div>



                <?php
                if (isset($payments) && !empty($payments) && $is_search) {
                  ?>
                  <div class="row clearfix mt-3">
                    <div class="col-md-12">
                      <div class="card p-4">
                        <div class="body">
                          <div class="table-responsive">
                            <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                              <thead class="thead-dark">

                                <tr>
                                  <th>#</th>
                                  <th>Creditor</th>
                                  <th>Tour ID</th>
                                  <!-- <th>Discription</th> -->
                                  <th>Date</th>
                                  <th>0-30 Days</th>
                                  <th>31-60 Days</th>
                                  <th>61-90 Days</th>
                                  <th>90+ Days</th>
                                  <!-- <th>View</th> -->

                                </tr>
                              </thead>
                              <tbody>

                                <?php
                                $count = 0;

                                foreach ($payments as $value) {
                                  $count++;


                                  $today = date('Y-m-d');
                                  $startDateTime = new DateTime($today);
                                  $endDateTime = new DateTime($value['FirstOfDate']);
                                  $interval = $startDateTime->diff($endDateTime);
                                  $numberOfDays = $interval->days;
                                  ?>
                                  <tr>
                                    <td><?php echo $count; ?></td>

                                    <td><?php echo $value['PName']; ?></td>
                                    <td><?php echo $value['index_no']; ?></td>
                                    <td><?php echo $value['FirstOfDate']; ?></td>
                                    <td><?php echo number_format($value['Balance_30_Days'],2); ?></td>
                                    <td><?php echo number_format($value['Balance_60_Days'],2); ?></td>
                                    <td><?php echo number_format($value['Balance_90_Days'],2); ?></td>
                                    <td><?php echo number_format($value['Balance_Over_90_Days'],2); ?></td>



                                  </tr>
                                  <?php
                                }
                                ?>
                              </tbody>
                            </table>
                          </div>
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


    $("#date_form").on('submit', function(e) {
      e.preventDefault();


      var cus = $("#customer_name").val();

      if (cus) {
        window.location.href = "<?php echo base_url(); ?>accounts/age_analysis?cus=" + cus;
        return false;
      }



    });


  </script>



</body>

</html>