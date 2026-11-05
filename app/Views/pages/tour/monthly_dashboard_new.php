<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>
        <script>
            $(document).ready(function() {
                $('.js-basic-example').DataTable({
                    "columnDefs": [{
                            "orderable": false,
                            "targets": 0
                        } // Disable sorting on the first column
                    ],
                    "order": [] // Ensure no default sorting
                });
            });
        </script>



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
                                        <h4 class="p-title">Working Tour Dashboard</h4>
                                    </div>
                                </div>


                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table js-basic-example table-hover  table-custom align-middle ">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th rowspan="3" class="text-center align-middle">Tour ID</th>
                                                                <th rowspan="3" class="text-center align-middle">Manual ID</th>
                                                                <th rowspan="3" class="text-center align-middle">Tour Details<br>
                                                                    (S.Date, T.Name, T.C.User)
                                                                </th>
                                                                <?php if (isset($differecePassengerCount) && !empty($differecePassengerCount)) { ?>
                                                                    <th rowspan="3" class="text-center align-middle">Passengers</th>
                                                                <?php } ?>
                                                                <th colspan="5" class="text-center">Status Summary</th>
                                                                <th rowspan="3" class="text-center align-middle">Action</th>
                                                                <th rowspan="3" class="text-center align-middle">Override Tour</th>
                                                            </tr>
                                                            <tr>
                                                                <th colspan="5" class="text-center">Pre Status</th>
                                                                <!-- <th colspan="2" class="text-center">Post Status</th> -->
                                                            </tr>
                                                            <tr>
                                                                <th>Reservation Card</th>
                                                                <th>Itinerary Print</th>
                                                                <th>Q Mail</th>
                                                                <th>Booking</th>
                                                                <th>Invoice</th>
                                                                <!-- <th>Hotel Reservation</th>
                                                                <th>Transport</th>
                                                                <th>Tour Expenses</th>
                                                                <th>Receipting</th> -->

                                                            </tr>

                                                        </thead>
                                                        <tbody>


                                                            <?php
                                                            if (isset($tour_details) && !empty($tour_details)) {

                                                                foreach ($tour_details as $row) {


                                                                    if ($row->Finalize != 1) {
                                                                        $bg = 'background-color: #d648f03d;';
                                                                    } else {
                                                                        $bg = '';
                                                                    }
                                                            ?>
                                                                    <tr style="<?= $bg; ?>">
                                                                        <td>
                                                                            <?= $row->tour_id; ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php if (isset($row->manual_id) && !empty($row->manual_id)) { ?>
                                                                                <div class="row">
                                                                                    <!-- <div class="col-md-7"> -->
                                                                                    <div class="manual-id-button">
                                                                                        <?= $row->manual_id; ?>
                                                                                    </div>
                                                                                    <!-- </div> -->
                                                                                    <div class="col-md-3">
                                                                                        <div class="manual-id-button">
                                                                                            <button type="button" class="btn btn-success btn-sm manual_id_edit" title="Edit Manual ID" onClick='addManualID("<?= $row->tour_id; ?>")' data-bs-toggle='modal' data-bs-target='#manualIdModal' data-tour="<?= $row->tour_id; ?>" data-edit="1"><i class="bi bi-pencil"></i></button>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            <?php } else { ?>
                                                                                <div class="manual-id-button">
                                                                                    <button type="button" class="btn btn-success btn-sm manual_id" title="Add Manual ID" onClick='addManualID("<?= $row->tour_id; ?>")' data-bs-toggle='modal' data-bs-target='#manualIdModal'><i class="bi bi-plus-circle"></i></button>
                                                                                </div>
                                                                            <?php } ?>
                                                                        </td>
                                                                        <td class="text-secondary">
                                                                            <?= date('d-F Y', strtotime($row->start_date)) . '&nbsp;&nbsp;'; ?> <br>
                                                                            <code><?= $row->consignee; ?></code><br>
                                                                            <code style="color: green;"><?= $row->create_user; ?></code>
                                                                            <?php //echo $row->consignee;
                                                                            // $pax_names = array();
                                                                            // foreach ($pax_details as $row1) {
                                                                            //     if ($row1['tour_id'] == $row->tour_id) {
                                                                            //         $pax_names[] = $row1['pass_name'];
                                                                            //     }
                                                                            // }
                                                                            // echo implode(', &nbsp;', $pax_names);
                                                                            ?>
                                                                        </td>
                                                                        <?php if (isset($differecePassengerCount) && !empty($differecePassengerCount)) { ?>
                                                                            <td>
                                                                                <?php
                                                                                foreach ($differecePassengerCount as $value) {
                                                                                    if ($value['tour_id'] == $row->tour_id) {

                                                                                        echo '<button type="button" class="btn btn-soft-warning btn-sm" title="Add Passenger" data-bs-toggle="modal" data-bs-target="#AddmemberModal" onClick="addPassenger(' . $value['tour_id'] . ',' . $value['pax_count'] . ')"><i class="bi bi-plus-circle-fill"></i></button>';
                                                                                        break;
                                                                                    } else {
                                                                                    }
                                                                                }
                                                                                ?>
                                                                            </td>
                                                                        <?php } else { ?>
                                                                            <?php $pax_names = array();
                                                                            foreach ($pax_details as $row1) {
                                                                                if ($row1['tour_id'] == $row->tour_id) {
                                                                                    $pax_names[] = $row1['pass_name'];
                                                                                }
                                                                            }
                                                                            //echo implode(', &nbsp;', $pax_names);
                                                                            ?>
                                                                        <?php } ?>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php if (isset($row->Finalize) && $row->Finalize == 1) { ?>
                                                                                    <a href="<?= base_url('tour/print/print_quotation_with_amounts?tour_id=' . $row->tour_id); ?>" class="btn btn-dark btn-sm me-2" target="_blank" data-toggle="tooltip" title="Reservation Card Print"><i class="bi bi-printer"></i></a>
                                                                                    <a href="<?= base_url('tour/print?tour_id=' . $row->tour_id); ?>" class="btn btn-info btn-sm" target="_blank" data-toggle="tooltip" title="Print Without Amounts"><i class="bi bi-printer"></i></a>
                                                                                <?php } else { ?>
                                                                                    <img src="<?php echo base_url(); ?>assets/images/close.png" alt="..." width="20px" class="me-2">
                                                                                <?php } ?>
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">

                                                                                <a href="<?= base_url('tour/print/itinerary?tour_id=' . $row->tour_id); ?>" class="btn btn-soft-success btn-sm" target="_blank" data-toggle="tooltip" title="Itinerary Print"><i class="bi bi-printer-fill"></i></a>

                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <button type="button" class="btn btn-primary btn-sm send-quotation-mail"
                                                                                    data-tour="<?= $row->tour_id; ?>">
                                                                                    <i class="bi bi-envelope-fill"></i>
                                                                                </button>
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">

                                                                                <?php if (isset($row->Confirm) && $row->Confirm == 0 && isset($row->Finalize) && $row->Finalize == 1) { ?>
                                                                                    <button type="button" class="btn btn-success btn-sm tour_confirm" title="Booking Status" data-tour="<?= $row->tour_id; ?>">Book Now</button>
                                                                                <?php } else if (isset($row->Finalize) && $row->Finalize == 1 && isset($row->Confirm) && $row->Confirm == 1) { ?>
                                                                                    <img src="<?php echo base_url(); ?>assets/images/ok.png" alt="..." width="20px" class="me-2">
                                                                                <?php } else if (isset($row->Finalize) && $row->Finalize != 1 && isset($row->Confirm) && $row->Confirm == 1) { ?>
                                                                                    <img src="<?php echo base_url(); ?>assets/images/close.png" alt="..." width="20px" class="me-2">
                                                                                <?php } else if (isset($row->Finalize) && $row->Finalize != 1 && isset($row->Confirm) && $row->Confirm != 1) { ?>
                                                                                    <img src="<?php echo base_url(); ?>assets/images/close.png" alt="..." width="20px" class="me-2">
                                                                                <?php } else if (isset($row->Finalize) && $row->Finalize == 1 && isset($row->Confirm) && $row->Confirm == 4) { ?>
                                                                                    <button type="button" class="btn btn-success btn-sm tour_confirm_amd" title="Booking Status" data-tour="<?= $row->tour_id; ?>" data-inv="<?= $row->amend_inv; ?>">Amend Invoice</button>
                                                                                <?php }
                                                                                if (isset($row->Confirm) && $row->Confirm == 3 && isset($row->Finalize) && $row->Finalize == 1) { ?>
                                                                                    <button type="button" class="btn btn-danger btn-sm" title="Booking Status" data-tour="<?= $row->tour_id; ?>">Tour Cancelled</button>
                                                                                <?php } ?>
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <img src="<?php echo base_url(); ?>assets/images/<?php echo (isset($row->Confirm) && $row->Confirm == 1) ? "ok" : "close"; ?>.png" alt="..." width="20px" class="me-2">
                                                                                <?php if (isset($row->Confirm) && $row->Confirm == 1) { ?>
                                                                                    <button class="btn btn-primary btn-sm invoice_print" title="Invoice Reprint" data-tour="<?= $row->tour_id; ?>"><i class="bi bi-printer"></i></button>
                                                                                <?php } ?>
                                                                            </div>
                                                                        </td>
                                                                        <!-- <td>
                                                                            <div class="d-flex align-items-center"> -->
                                                                        <?php
                                                                        // $set_flag_booking = $set_flag_confirm =  $set_flag_payment = 0;


                                                                        // $ok_img = '<img src="' . base_url() . 'assets/images/ok.png" alt="..." width="20px" class="me-2">';

                                                                        // if (isset($row->quotation_days)) {
                                                                        //     if (isset($row->hotel_booking_cnt) && $row->quotation_days == $row->hotel_booking_cnt) {
                                                                        //         $set_flag_booking = 1;
                                                                        //         echo $ok_img;
                                                                        //     } else {
                                                                        //         $set_flag_booking = 0;
                                                                        //         echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $row->tour_id . ',\' ' . addslashes($row->start_date) . '\',\' ' . addslashes($row->end_date) . '\', 1)"><i class="bi bi-x-circle" title="Hotel Booking Not Completed" ></i></button>';
                                                                        //     }

                                                                        //     if (isset($row->booking_confirmed_cnt) && $row->quotation_days == $row->booking_confirmed_cnt) {
                                                                        //         $set_flag_confirm = 1;
                                                                        //         echo $ok_img;
                                                                        //     } else {
                                                                        //         $set_flag_confirm = 0;
                                                                        //         echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $row->tour_id . ', \' ' . addslashes($row->start_date) . '\',\' ' . addslashes($row->end_date) . '\', 2)"><i class="bi bi-x-circle" title="Booking Confirmed Not Completed" ></i></button>';
                                                                        //     }

                                                                        //     if (isset($row->hotel_payment_cnt) && $row->quotation_days == $row->hotel_payment_cnt) {
                                                                        //         $set_flag_payment = 1;
                                                                        //         echo $ok_img;
                                                                        //     } else {
                                                                        //         $set_flag_payment = 0;
                                                                        //         echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $row->tour_id . ' ,\' ' . addslashes($row->start_date) . '\',\' ' . addslashes($row->end_date) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                        //     }
                                                                        // }
                                                                        ?>

                                                                        <!-- </div>
                                                                        </td> -->
                                                                        <!-- <td>
                                                                            <div class="d-flex align-items-center"> -->
                                                                        <?php
                                                                        // $trns_advance = $trns_expenses = $trns_settled = 0;

                                                                        // if (isset($row->transport_advance) && $row->transport_advance == 1) {
                                                                        //     $trns_advance = 1;
                                                                        //     echo $ok_img;
                                                                        // } else {
                                                                        //     $trns_advance = 0;
                                                                        //     echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                        //         $row->tour_id . ', \'' . addslashes($row->start_date) . '\', \'' .
                                                                        //         addslashes($row->end_date) . '\', \'' .
                                                                        //         addslashes($row->consignee) . '\', ' .
                                                                        //         $row->pax_count . ', 1)"><i class="bi bi-x-circle-fill" title="Transport Advance Not Completed"></i></button>';
                                                                        // }

                                                                        // // if (isset($row->transport_advance) && $row->transport_advance == 1) {
                                                                        // //     $trns_advance = 1;
                                                                        // //     echo $ok_img;
                                                                        // // } else {
                                                                        // $trns_expenses = 0;
                                                                        // echo '<button class="btn btn-sm btn-soft-info me-2" onclick="transportPayment(' .
                                                                        //     $row->tour_id . ', \'' . addslashes($row->start_date) . '\', \'' .
                                                                        //     addslashes($row->end_date) . '\', \'' .
                                                                        //     addslashes($row->consignee) . '\', ' .
                                                                        //     $row->pax_count . ', 2)"><i class="bi bi-check-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                        // // }

                                                                        // if (isset($row->transport_settled) && $row->transport_settled == 1) {
                                                                        //     $trns_settled = 1;
                                                                        //     echo $ok_img;
                                                                        // } else {
                                                                        //     $trns_settled = 0;
                                                                        //     echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                        //         $row->tour_id . ', \'' . addslashes($row->start_date) . '\', \'' .
                                                                        //         addslashes($row->end_date) . '\', \'' .
                                                                        //         addslashes($row->consignee) . '\', ' .
                                                                        //         $row->pax_count . ', 3)"><i class="bi bi-x-circle-fill" title="Transport Settled Not Completed"></i></button>';
                                                                        // }
                                                                        ?>

                                                                        <!-- </div>
                                                                        </td> -->

                                                                        <!-- <td></td>
                                                                        <td></td> -->
                                                                        <td>

                                                                            <div class="d-flex align-items-center">
                                                                                <?php
                                                                                if ($row->Confirm == 3) {
                                                                                } else {
                                                                                    if (isset($row->Finalize) && $row->Finalize == 1) {
                                                                                ?>
                                                                                        <a href="<?= base_url('tour/tour_details/show_details?id=' . $row->tour_id . '&start=' . $row->start_date . '&end=' . $row->end_date); ?>" class="btn btn-warning btn-sm me-2" target="_blank" data-toggle="tooltip" title="View"><i class="bi bi-eye-fill"></i></a>
                                                                                        <?php if (!(isset($row->Confirm) && $row->Confirm == 1)) { ?>
                                                                                            <a href="<?= base_url('tour/quotation/timeline/' . $row->tour_id . '?edit=2'); ?>" class="btn btn-secondary btn-sm" target="_blank" data-toggle="tooltip" title="Edit"><i class="bi bi-pencil"></i></a>
                                                                                        <?php } ?>
                                                                                    <?php } else { ?>
                                                                                        <a href="<?= base_url('tour/quotation/timeline/' . $row->tour_id . '?edit=2'); ?>" class="btn btn-secondary btn-sm" target="_blank" data-toggle="tooltip" title="Edit"><i class="bi bi-pencil"></i></a>
                                                                                        <button type="button" class="btn btn-danger btn-sm delete-btn" title="Delete" data-tour="<?= $row->tour_id; ?>"><i class="bi bi-trash"></i></button>
                                                                                    <?php } ?>
                                                                                    <a href="<?= base_url('chat/open/' . $row->tour_id . '/' . $row->agent); ?>" class="btn btn-dark btn-sm ms-1" target="_blank" data-toggle="tooltip" title="Open Chat for this Tour">
                                                                                        <i class="bi bi-chat-dots-fill"></i>
                                                                                    </a>
                                                                                <?php } ?>
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php if ($row->verify == 1) {
                                                                                    // if($row->verify == 1){
                                                                                    //     echo '<img src="' . base_url() . 'assets/images/ok.png" alt="..." width="20px" class="me-2">';
                                                                                    // }else{
                                                                                    //     echo '<img src="' . base_url() . 'assets/images/close.png" alt="..." width="20px" class="me-2">';
                                                                                    // }
                                                                                ?>

                                                                                <?php } else { ?>
                                                                                    <button type="button" class="btn btn-soft-dark btn-sm me-2" title="Override" onClick="overrideTour(<?= $row->tour_id; ?>)"><i class="bi bi-arrow-counterclockwise"></i></button>
                                                                                    <button type="button" class="btn btn-info btn-sm " title="Final Verify" onClick="verifyTour(<?= $row->tour_id; ?>)">Verify</button>
                                                                                <?php } ?>
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

        <!-- Manual ID Modal -->
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
                                <input type="hidden" name="status" id="status" value="0">

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
        <!-- End Manual ID Modal -->

        <!-- Passenger Modal -->
        <div class="modal fade" id="AddmemberModal" tabindex="-1" aria-labelledby="AddmemberModalLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="AddmemberModalLabel">Add Pax Details</h1>
                        <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                        <input type="hidden" name="modal_tour_id" id="modal_tour_id">
                        <input type="hidden" name="pax_cc" id="pax_cc">
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="mb-4">
                                <label class="form-label" for="passenger">Passenger Name<font color="#FF0000"><strong>*</strong></font></label>
                                <input class="form-control txt_nav" type="text" id="passenger" name="passenger" placeholder="" required />
                                <button type="button" class="btn btn-success mt-2" id="add_passenger">Save</button>
                            </div>
                            <div class="px-3">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Passenger Name</th>
                                            <!-- <th scope="col">Action</th> -->
                                        </tr>
                                    </thead>
                                    <tbody id="passenger_details">

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-info" data-bs-dismiss="modal" id="btn-ok">Ok</button>
                        <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END layout-wrapper -->

    <!-- Hotel Booking Email Modal -->
    <div class="modal fade" id="reservationEmailModal" tabindex="-1" aria-labelledby="reservationEmailModalLabel"
        aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="reservationEmailModalLabel">Send Email</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea id="res_body" name="res_body"></textarea>
                </div>
            </div>
        </div>
        <!-- end main content-->
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $('#res_body').summernote({
            height: 400,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', []]
            ],
        });

        $(".tour_confirm").on('click', function(e) {
            e.preventDefault();

            var tour_id = $(this).data('tour');

            window.location.href = "<?php echo base_url(); ?>accounts/debtor_invoice_new?tour_id=" + tour_id;

        });

        $(".tour_confirm_amd").on('click', function(e) {
            e.preventDefault();

            var tour_id = $(this).data('tour');
            var inv_id = $(this).data('inv');

            window.location.href = "<?php echo base_url(); ?>accounts/debtor_invoice_new?tour_id=" + tour_id + "&inv_id=" + inv_id;

        });

        $(".invoice_print").on('click', function(e) {
            e.preventDefault();

            var tour_id = $(this).data('tour');
            $.ajax({
                url: '<?php echo base_url('tour/tour_dashboard/search_debtor_invoice') ?>',
                type: 'POST',
                data: {
                    tour_id: tour_id,
                },
                dataType: 'json',
                success: function(data) {

                    if (data.status == 200) {

                        window.open("<?php echo base_url(); ?>accounts/debtor_invoice_new/print?tour=" + tour_id, '_blank');

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

        function addPassenger(index, pax_count) {

            $("#modal_tour_id").val(index);
            $("#pax_cc").val(pax_count);
            $("#AddmemberModal").show();
            load_passenger(index);

        }

        $(".btn-close").on('click', function() {
            $("#manualIdModal").hide();
            $("#manualIdModal input[type='text']").val('');
            $("#AddmemberModal").hide();
            location.reload();
        });

        // load manual id edit model
        $(".manual_id_edit").on('click', function() {
            var tour_id = $(this).data('tour');
            var edit = $(this).data('edit');
            $.ajax({
                url: '<?php echo base_url('tour/tour_dashboard/get_manual_id') ?>',
                type: 'POST',
                data: {
                    tour_id: tour_id,
                },
                dataType: 'json',
                success: function(data) {

                    if (data.status == 200) {
                        $("#order_id").val(tour_id);
                        $("#manual_id").val(data.manual_id);
                        $("#status").val(edit);
                        $("#manualIdModal").show();
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

        $("#form_submit").on('submit', function(e) {
            e.preventDefault();

            var order_id = $("#order_id").val();
            var manual_id = $("#manual_id").val();
            var status = $("#status").val();

            if (status == 0) {
                $url = '<?php echo base_url('tour/tour_dashboard/add_manual_id') ?>';
            } else {
                $url = '<?php echo base_url('tour/tour_dashboard/edit_manual_id') ?>';
            }

            $.ajax({
                url: $url,
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


        /*******************Hoterl Reservation Functions************************/

        function hotelReserve(tour_id, start_date, end_date, type) {

            if (end_date >= start_date) {
                window.open("<?php echo base_url(); ?>tour/booking_confirm/show_details?id=" + tour_id + "&start=" + start_date + "&end=" + end_date + "&type=" + type, '_blank');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    html: 'The end date must be greater than the start date. Please adjust the dates to proceed with the tour.<br><br><b>Start Date: </b>' + start_date + '<br><b>End Date: </b>' + end_date,
                });

            }


        }

        /************************Tranport payment***********************************/
        function transportPayment(tour_id, start_date, end_date, consignee, pax_count, type) {

            if (end_date >= start_date) {
                window.open("<?php echo base_url(); ?>tour/booking_confirm/show_transport?id=" + tour_id + "&start=" + start_date + "&end=" + end_date + "&consignee=" + consignee + "&pax_count=" + pax_count + "&type=" + type, '_blank');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    html: 'The end date must be greater than the start date. Please adjust the dates to proceed with the tour.<br><br><b>Start Date: </b>' + start_date + '<br><b>End Date: </b>' + end_date,
                });

            }
        }

        /*****************passengers***********************************/
        function load_passenger(modal_tour_id) {

            $.ajax({
                type: "GET",
                url: "<?php echo base_url(); ?>tour/tour/pax_all/" + modal_tour_id,
                dataType: "json",
                success: function(response) {

                    $('#passenger_details').empty();
                    $.each(response, function(index, value) {
                        $('#passenger_details').append(
                            '<tr>' +
                            '<th scope="row">' + (index + 1) + '</th>' +
                            '<td>' + value.pass_name + '</td>' +
                            '<td style="width: 25%;">' +
                            '<button type="button" class="btn btn-sm btn-danger" onClick="delete_pax(' + value.id + ')"><i class="bi bi-trash"></i></button>' +

                            '</td>' +
                            '</tr>'
                        );

                    });
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong!',
                    });
                }
            });

        }

        function delete_pax(id) {

            var pax_id = id;
            var modal_tour_id = $('#modal_tour_id').val();

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this Passenger!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "get",
                        url: "<?php echo base_url(); ?>tour/tour/pax_delete",
                        data: {
                            id: id
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        load_passenger(modal_tour_id);

                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: response.message,
                                });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong!',
                            });
                        }
                    });
                }
            });
        }

        $('#passenger').keydown(function(e) {
            if (e.keyCode == 13) {
                $('#add_passenger').click();
            }
        });

        // add_passenger button
        $('#add_passenger').on('click', function() {
            var passenger = $('#passenger').val();
            var modal_tour_id = $('#modal_tour_id').val();
            var pax_coun = $('#pax_cc').val();

            if (passenger == '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Name is required!',
                });
            } else {
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>tour/tour/pax_create",
                    data: {
                        passenger: passenger,
                        modal_tour_id: modal_tour_id,
                        pax_coun: pax_coun
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.status == 201) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    load_passenger(modal_tour_id);
                                    $('#passenger').val('');
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: response.message,
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong!',
                        });
                    }
                });
            }
        });

        $('#btn-ok').on('click', function() {
            location.reload();
        });



        function overrideTour(tour_id) {

            $.ajax({
                url: '<?php echo base_url('tour/tour_details/override_tour') ?>',
                type: 'POST',
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
        }

        function verifyTour(tour_id) {

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to verify this Tour!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Verify it!'
            }).then((result) => {
                if (result.isConfirmed) {

                    $.ajax({
                        url: '<?php echo base_url('tour/tour_details/verify_tour') ?>',
                        type: 'POST',
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
                                });
                                setTimeout(() => {
                                    location.reload();
                                }, 2000);

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
                }
            });

        }

        function get_dates_between(from, to) {
            var dates = [];
            var currentDate = new Date(from);
            var endDate = new Date(to);
            while (currentDate <= endDate) {
                dates.push(new Date(currentDate));
                currentDate.setDate(currentDate.getDate() + 1);
            }
            return dates;
        }

        // function get_passengers() {
        //     try {
        //         var passengers = '<?php //echo json_encode($passengers) 
                                        ?>';
        //         passengers = JSON.parse(passengers);
        //         var passengerNames = '';
        //         if (passengers?.length > 0) {
        //             passengers.forEach(function(passenger) {
        //                 passengerNames += passenger.pass_name + ' , ';
        //             });
        //         }
        //         if (passengerNames.endsWith(', ')) {
        //             passengerNames = passengerNames.slice(0, -2);
        //         }

        //         console.log(passengerNames);

        //         return passengerNames;
        //     } catch (e) {
        //         console.log(e);
        //         return [];
        //     }
        // }

        $(document).on('click', '.send-quotation-mail', function() {
            const tour_id = $(this).data('tour');

            $.ajax({
                url: '<?= base_url('tour/print/q_email/') ?>' + tour_id + '/1',
                type: 'get',
                dataType: 'json',
                success: function(data) {
                    console.log(data);
                    if (data && data?.tour_details) {

                        $('#reservationEmailModal').modal('show');

                        const tour_details = data?.tour_details;
                        const activity_details = data?.activity_details;
                        const sub_locations = data?.sub_locations;
                        const consignee = data?.consignee_details[0];

                        let html = `
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <td colspan="5" class="text-muted">Consignee Details</td>
                                        </tr>
                                        <tr>
                                            <td colspan="5">${(consignee.consignee + ' / ' + consignee.agent_name) || ''}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">#</th>
                                            <th class="text-muted">Date</th>
                                            <th class="text-muted">Hotel</th>
                                            <th class="text-muted">Location</th>
                                            <th class="text-muted">Activities</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `
                        let i = 1;

                        tour_details?.forEach(row => {
                            let meal = '';
                            switch (row.meal_code) {
                                case 'fb':
                                    meal = 'FB';
                                    break;
                                case 'hb':
                                    meal = 'HB';
                                    break;
                                case 'bb':
                                    meal = 'BB';
                                    break;
                                default:
                                    meal = '';
                            }

                            // Assuming 'consignee' and 'twintype' are defined in your scope
                            let sgl = (!consignee.sgl || consignee.sgl === '0') ? '' : consignee.sgl;
                            let dbl = (!consignee.dbl || consignee.dbl === '0') ? '' : consignee.dbl;
                            let tpl = (!consignee.tpl || consignee.tpl === '0') ? '' : consignee.tpl;

                            let loc = sub_locations
                                .filter(row1 => row1.date_id === row.tour_date)
                                .map(row1 => `* ${row1.from} <span class="text-muted"> to </span> ${row1.to}`)
                                .join('<br> ');

                            let activities = activity_details
                                .filter(activity => activity.tour_date === row.tour_date)
                                .map(activity => `* ${activity.locatonname}`)
                                .join('<br>');

                            html += `
                                    <tr>
                                        <td>${i++}</td>
                                        <td>${row.tour_date}</td>
                                        <td>${row.hotel_name || ''}<br>
                                            <p style="font-size: small; color:#457900;">${row.rtype || ''}</p>
                                        </td>
                                        <td>${loc}</td>
                                        <td>${activities}</td>
                                    </tr>
                                    `;
                        });

                        html += `
                                        <tr>
                                            <td colspan="5" class="text-muted">Per Person Cost</td>
                                        </tr>
                                        <tr>
                                            <td colspan="5">${parseFloat(consignee.per_person_cost || '0.00').toFixed(2)}</td>
                                        </tr>
                                    </tbody>
                                </table>
                        `;

                        $('#res_body').summernote('code', html);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No data found for the selected tour.',
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