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
                                        <h4 class="p-title">Voucher Audit Report</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">

                                                <br>
                                                <div class="table-responsive">
                                                    <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Tour ID</th>
                                                                <th>Manual Tour ID</th>
                                                                <th>Tour Name</th>
                                                                <th>Tour Agent</th>
                                                                <th>Total Amount</th>
                                                                <th>Voucher Count</th>
                                                                <th>Date</th>
                                                                <th>View Vouchers</th>
                                                                <th>View Tour</th>
                                                                <!-- <th>View</th> -->
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $x = 1;
                                                            foreach ($payments as $litem) {
                                                                ?>
                                                                <tr style="width: 100%;">
                                                                    <td><?php echo $x ?></td>
                                                                    <td><?php echo $litem['C_Number'] ?></td>
                                                                    <td><?php echo $litem['manual_id'] ?></td>
                                                                    <td><?php echo $litem['consignee'] ?></td>
                                                                    <td><?php echo $litem['agent_name'] ?></td>
                                                                    <td><?php echo $litem['tot_amount'] ?></td>
                                                                    <td><?php echo $litem['v_count'] ?></td>
                                                                    <td><?php echo $litem['W_Date'] ?></td>
                                                                    <td>
                                                                        <a href="<?= base_url('accounts/voucher_audit/auth?tour_id=' . $litem['C_Number']) ?>" class="btn btn-primary" target="_blank">
                                                                            <i class="bi bi-eye-fill"></i>
                                                                        </a>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <a href="<?= base_url('tour/tour_dashboard/monthly_dashboard?tour_id=' .$litem['C_Number']); ?>" class="btn btn-soft-success btn-sm" target="_blank" data-toggle="tooltip" title="Itinerary Print"><i class="bi bi-eye-fill"></i></a>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <?php
                                                                $x++;
                                                            }
                                                            ?>
                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <th colspan="5">Other</th>
                                                                <th align="canter"><?php echo $other_vcount['g_total'];  ?></th>
                                                                <th><?php echo $other_vcount['other_count']; ?></th>
                                                                <th></th>
                                                                <!-- <th align="canter">Other</th> -->
                                                                <th>
                                                                    <a href="<?= base_url('accounts/voucher_audit/auth?tour_id=other') ?>" class="btn btn-primary" target="_blank">
                                                                        <i class="bi bi-eye-fill"></i>
                                                                    </a>
                                                                </th>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                    <div class=" mt-3" style="float: right;" >
                                                        <a  class="btn btn-success btn_finalize btn-lg" id="finalizeButton">
                                                         <i class="bi bi-check-circle-fill"></i> Finalize
                                                     </a>
                                                 </div>
                                                 <!-- Finalize Button Outside the Table -->

                                             </div>


                                         </div>
                                     </div>
                                 </div>
                             </div>


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

        // document.addEventListener("DOMContentLoaded", function() {
        //     flatpickr("#drange", {
        //         mode: "range",
        //         dateFormat: "Y-m-d"
        //     });
        // });

        // $("#date_form").on('submit', function(e) {
        //     e.preventDefault();

        //     var date1 = $("#drange1").val();
        //     var date2 = $("#drange2").val();
        //     var cus = $("#customer_name").val();

        //     if (cus) {
        //         window.location.href = "<?php echo base_url(); ?>accounts/creditor_invoice/report?cus=" + cus;
        //         return false;
        //     }

        //     if (date1 == '' || date2 == '') {
        //         Swal.fire({
        //             icon: 'error',
        //             title: 'Oops...',
        //             text: 'Start Date and End Date are required!',
        //         });
        //     } else {
        //         window.location.href = "<?php echo base_url(); ?>accounts/creditor_invoice/report?startdate=" + date1 + "&enddate=" + date2;
        //     }

        // });


        $(".btn_finalize").click(function() {

    // Show a confirmation dialog using SweetAlert before proceeding
    Swal.fire({
        title: 'Are you sure?',
        text: "You want to finalize this action!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, finalize it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // If the user confirms, proceed with the action
            $(this).prop("disabled", true).text("Processing...").hide(); // Disable and hide the button

            $.post("<?php echo base_url() ?>accounts/voucher_audit/update", {
                update: "data",
            }, function(data) {
                if (data.status === 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated Successfully!',
                        showConfirmButton: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload(); // Reload the page on success
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
                // Reset the flag or perform any cleanup here if needed
            });
        }
    });
});


</script>



</body>

</html>