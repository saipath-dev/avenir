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
                                        <h4 class="p-title">Tour Monthly Dashboard Templates</h4>
                                    </div>
                                </div>

                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <div class="table-responsive">
                                                        <table class="table js-basic-example table-hover  table-custom align-middle">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th  class="text-center align-middle">Tour ID</th>
                                                                    <th  class="text-center align-middle">Manual ID</th>
                                                                    <th  class="text-center align-middle">Tour Details</th>
                                                                    <th  class="text-center align-middle">Action</th>
                                                                    <th  class="text-center align-middle">Print</th>
                                                                </tr>
                                                               
                                                            </thead>
                                                            <tbody>
                                                                <?php 
                                                                    if (isset($tour_details) && !empty($tour_details)) {
                                                                        foreach ($tour_details as $row) {

                                                                        
                                                                            ?>
                                                                            <tr>
                                                                                <td>
                                                                                    <?= $row->tour_id; ?>
                                                                                </td>
                                                                                <td>
                                                                                    <?php if (isset($row->manual_id) && !empty($row->manual_id)) { ?>
                                                                                        <?= $row->manual_id; ?>
                                                                                    <?php }else{ ?>
                                                                                        <button type="button" class="btn btn-success btn-sm manual_id" title="Add Manual ID" onClick='addManualID("<?= $row->tour_id; ?>")' data-bs-toggle='modal' data-bs-target='#manualIdModal' ><i class="bi bi-plus-circle"></i></button>
                                                                                    <?php } ?>
                                                                                </td>
                                                                                <td class="text-secondary">
                                                                                    <?= date('d-F Y', strtotime($row->start_date)) . '&nbsp;&nbsp;'; ?>
                                                                                    <?php
                                                                                        $pax_names = array();
                                                                                        foreach ($pax_details as $row1) {
                                                                                            if ($row1['tour_id'] == $row->tour_id) {
                                                                                                $pax_names[] = $row1['pass_name'];
                                                                                            }
                                                                                        }
                                                                                        echo implode(', &nbsp;', $pax_names);
                                                                                        ?>
                                                                                </td>
                                                                                
                                                                                <td>

                                                                                    <div class="d-flex align-items-center">
                                                                                        
                                                                                        <a href="<?= base_url('tour/tour_details/show_details?id=' . $row->tour_id . '&start=' . $row->start_date . '&end=' . $row->end_date); ?>" class="btn btn-warning btn-sm me-2" target="_blank" data-toggle="tooltip" title="View"><i class="bi bi-eye-fill"></i></a>
                                                                                        <a href="<?= base_url('tour/quotation/timeline/' . $row->tour_id); ?>" class="btn btn-secondary btn-sm me-2" target="_blank" data-toggle="tooltip" title="Edit"><i class="bi bi-pencil"></i></a>
                                                                                        
                                                                                        <!-- <button type="button" class="btn btn-danger btn-sm delete-btn me-2" title="Delete" data-tour="<?= $row->tour_id; ?>"><i class="bi bi-trash"></i></button> -->
                                                                                        
                                                                                        
                                                                                    </div> 
                                                                                </td>
                                                                                <td>
                                                                                    <div class="d-flex align-items-center">
                                                                                        <div class="dropdown">
                                                                                                <button class="btn btn-dark btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                                                                    <i class="bi bi-three-dots-vertical"></i>
                                                                                                </button>
                                                                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                                                                    <li><a href="<?= base_url('tour/print/print_quotation_with_amounts?tour_id=' . $row->tour_id); ?>" class="dropdown-item" target="_blank" >Reservation Card Print</a></li>
                                                                                                    <li><a class="dropdown-item" href="<?= base_url('tour/print?tour_id=' . $row->tour_id); ?>" target="_blank">Print Without Amounts</a></li>
                                                                                                    <li><a class="dropdown-item" href="<?= base_url('tour/print/itinerary?tour_id=' . $row->tour_id); ?>" target="_blank" >Itinerary Print</a></li>
                                                                                                </ul>
                                                                                        </div>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                            <?php
                                                                        }   
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
            </div>
        </div> 
        <!-- end main content-->
         <div class="modal fade" id="manualIdModal" tabindex="-1" aria-labelledby="manualIdModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="manualIdModalLabel">Enter Manual ID</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <form action="" id="form_submit" accept-charset="utf-8">
                                <input type="hidden" name="order_id" id="order_id">
                                
                                <div class="mb-4">
                                    <div class="mb-3">
                                        <label class="form-label">Manual ID<font color="#FF0000"><strong>*</strong></font></label>
                                        <input type="text" name="manual_id" id="manual_id" class="form-control txt_nav" placeholder="" required />
                                    </div>
                                   

                                    <button type="submit" class="btn btn-success mt-2" id="add_amount">Save</button>
                                </div>
                            </form>
                            
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
     <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        
          

           $(".delete-btn").on('click', function(e) {
                e.preventDefault();

                var tour_id = $(this).data('tour');
                 $.ajax({
                    url: '<?php echo base_url('tour/tour_dashboard/delete') ?>',
                    type: 'GET',
                    data: {
                        tour_id: tour_id,
                    },
                    dataType: 'json',
                    success: function(data) {

                        if (data.status == 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload();
                                }
                            });
                            
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

            function addManualID(index) {
                
                $("#order_id").val(index);
                $("#manualIdModal").show();
            }

            $(".btn-close").on('click', function() {
                $("#manualIdModal").hide();
                $("#manualIdModal input[type='text']").val('');
            });


            $("#form_submit").on('submit', function(e) {
                e.preventDefault();

                var order_id = $("#order_id").val();
                var manual_id = $("#manual_id").val();

                $.ajax({
                    url: '<?php echo base_url('tour/tour_dashboard/add_manual_id') ?>',
                    type: 'POST',
                    data: {
                        order_id: order_id,
                        manual_id: manual_id,
                    },
                    dataType: 'json',
                    success: function(data) {

                        if (data.status == 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload();
                                }
                            });
                            
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

            
    </script>

</body>

</html>
