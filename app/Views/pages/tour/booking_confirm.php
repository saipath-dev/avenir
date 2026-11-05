<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <style>
            /* .table-responsive {
                overflow-x: auto;
            } */

            .table-wrapper {
                position: relative;
                overflow-x: auto;
            }

            .table {
                /* width: 100%; */
                /* border-collapse: collapse; */
            }

            .table th.fixed-column,
            .table td.fixed-column {
                position: sticky;
                left: 0;
                background-color: #fff !important;
                /* Match the table background */
                z-index: 1;
                /* Ensure it stays above other cells */
            }

            .table th,
            .table td {
                padding: 10px;
                border: 0px solid #ddd;
            }
        </style>
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
                                        <h4 class="p-title">Tour Management</h4>
                                    </div>
                                </div>
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="<?php echo base_url(); ?>tour/booking_confirm" method="GET">
                                                    <div class="row">
                                                        <div class="mb-3 col-md-4">
                                                            <label class="form-label" for="consignee">Tour Name</label>
                                                            <input class="form-control" type="text" id="consignee" name="consignee" />
                                                        </div>
                                                        <div class="mb-3 col-md-4">
                                                            <label class="form-label" for="email">email</label>
                                                            <input class="form-control" type="text" id="email" name="email" />
                                                        </div>
                                                        <div class="mb-3 col-md-4">
                                                            <label class="form-label" for="drange">Daterange</label>
                                                            <input class="form-control" type="text" id="drange" name="drange" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" style="padding-top: 10px; float:right;">
                                                        <button type="submit" class="btn btn-success w-100px me-5px"><i class="bi bi-search"></i>Search
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table js-basic-example table-hover dataTable table-custom align-middle table-fixed-header">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Tour Id</th>
                                                                <th class="fixed-column">Tour Manual Id</th>
                                                                <th>Tour Name</th>
                                                                <th>Contact No</th>
                                                                <th>Email</th>
                                                                <th>Pax Count</th>
                                                                <th>Rated Vehicle</th>
                                                                <th>Start Date</th>
                                                                <th>End Date</th>
                                                                <!-- <th>Status</th> -->
                                                                <th>Hotel Management</th>
                                                                <th>Transport Management</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $i = 1;
                                                            foreach ($tour_details as $row) {
                                                            ?>
                                                                <tr>
                                                                    <td><?= $i++; ?></td>
                                                                    <td><?= $row->tour_id; ?></td>
                                                                    <td class="fixed-column"><?= $row->manual_id; ?></td>
                                                                    <td><?= $row->consignee; ?></td>
                                                                    <td><?= $row->mobile; ?></td>
                                                                    <td><?= $row->email; ?></td>
                                                                    <td><?= $row->passenger_count; ?></td>
                                                                    <td><?= $row->vehicle_name; ?></td>
                                                                    <td><?= $row->start_date; ?></td>
                                                                    <td><?= $row->end_date; ?></td>
                                                                    <td align="center">
                                                                        <a href="<?= base_url('tour/booking_confirm/show_details?id=' . $row->tour_id . '&start=' . $row->start_date . '&end=' . $row->end_date); ?>" class="btn btn-warning btn-sm" target="_blank" data-toggle="tooltip" title="View"><i class="bi bi-eye-fill"></i></a>
                                                                    </td>
                                                                    <!-- <td><button type="button" class="btn btn-primary btn-sm tour_transport" data-bs-toggle='modal' data-bs-target='#paymentModal' data-tour="<?//= $row->tour_id ?>" data-tourame="<?//= $row->consignee ?>" data-pax="<?//= $row->passenger_count ?>">Tranport</button></td> -->
                                                                    <td>
                                                                        <a href="<?= base_url('tour/booking_confirm/show_transport?id=' . $row->tour_id . '&start=' . $row->start_date . '&end=' . $row->end_date . '&consignee=' . $row->consignee . '&paxcount=' . $row->passenger_count); ?>" class="btn btn-primary btn-sm" target="_blank" data-toggle="tooltip" title="Transport"><i class="bi bi-truck"></i></a>
                                                                    </td>


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


        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="paymentModalLabel">Confirm Transport</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="order_id">
                        <input type="hidden" name="pax_count" id="pax_count">
                        <input type="hidden" name="tour_name" id="tour_name">
                        <div class="row">
                            <div class="mb-4">

                                <div class="mb-3">
                                    <label class="form-label">Transport Agent<font color="#FF0000"><strong>*</strong></font></label>

                                    <select name="agent" id="agent" class="js-example-basic-single_m form-control" style="width: 100%;" required>
                                        <?php
                                        echo "<option>Select a Agent</option>";
                                        foreach ($transport_agent as $agent) {
                                            echo "<option value='" . $agent['Sup_Acc_No'] . "'>" . $agent['Name'] . "</option>";
                                        }
                                        ?>
                                    </select>

                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Bank Account<font color="#FF0000"><strong>*</strong></font></label>

                                    <select name="bank_acc" id="bank_acc" class="js-example-basic-single_m form-control" style="width: 100%;" required>
                                        <?php echo "<option>Select a Bank Account</option>";
                                        foreach ($bank_acc as $bank) {
                                            echo "<option value='" . $bank['L_Code'] . "'>" . $bank['PName'] . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="ref">Refference No</label>
                                    <input class="form-control txt_nav" type="text" id="ref" name="ref" placeholder="" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="payment">Advance Payment</label>
                                    <input class="form-control txt_nav" type="text" id="payment" name="payment" placeholder="" />
                                </div>

                                <button type="button" class="btn btn-success mt-2" id="add_transport" style="float: right;">Confirm</button>
                            </div>

                        </div>
                    </div>
                    <!-- <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div> -->
                </div>
            </div>
        </div>

    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            flatpickr("#drange", {
                mode: "range",
                dateFormat: "Y-m-d"
            });
        });

        $('.js-example-basic-single_m').select2({
            placeholder: " Select",
            allowClear: true,
            dropdownParent: $("#paymentModal")
        });

        $(document).ready(function() {
            $(".tour_confirm").on('click', function(e) {
                e.preventDefault();

                var tour_id = $(this).data('tour');
                var start = $(this).data('start');
                var end = $(this).data('end');

                $.ajax({
                    url: '<?php echo base_url('tour/tour_details/confirm_tour') ?>',
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

                            // window.location.href = "<?php //echo base_url(); ?>tour/tour_details";
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
        });

        $(".tour_transport").on('click', function(e) {

            e.preventDefault();
            $("#paymentModal").modal('show');

            var tour = $(this).data('tour');
            $("#order_id").val(tour);
            var tourname = $(this).data('tourame');
            $("#tour_name").val(tourname);
            var pax = $(this).data('pax');
            $("#pax_count").val(pax);

        });

        // $("#agent").on('change', function() {
        //     var agent = $(this).val();
        //     var paxcount = $('#pax_count').val();
        //     $.ajax({
        //         url: '<?php //echo base_url('tour/booking_confirm/get_agent_vehicle') 
                            ?>',
        //         type: 'POST',
        //         data: {
        //             agent: agent,
        //         },
        //         dataType: 'json',
        //         success: function(data) {
        //             if (data.status == 200) {
        //                 // $("#payment").val(data.payment);

        //                 $("#v_number").empty(); // Clear the select options
        //                 $.each(data.vehicles, function(index, vehicle) {
        //                     $("#v_number").append('<option value="' + vehicle.Vehicle_Number + '">' + vehicle.Vehicle_Number + '</option>');
        //                 });

        //             } else {
        //                 // $("#payment").val('');
        //             }

        //         },
        //         error: function() {
        //             $("#payment").val('');
        //         }
        //     });
        // });

        $("#add_transport").on('click', function(e) {
            e.preventDefault();

            var agent = $("#agent").val();
            var agent_name = $("#agent option:selected").text();
            var ref_no = $("#ref").val();
            var tour_id = $("#order_id").val();
            var payment = $("#payment").val();
            var bank_acc = $("#bank_acc").val();
            var bank_name = $("#bank_acc option:selected").text();
            var tour_name = $("#tour_name").val();

            $.ajax({
                url: '<?php echo base_url('tour/booking_confirm/tour_transport') ?>',
                type: 'POST',
                data: {
                    tour_id: tour_id,
                    ref_no: ref_no,
                    agent: agent,
                    agent_name: agent_name,
                    payment: payment,
                    bank_acc: bank_acc,
                    bank_name: bank_name,
                    tour_name: tour_name
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

                        // window.location.href = "<?php //echo base_url(); 
                                                    ?>tour/tour_details";
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