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
                                        <h4 class="p-title">Quotation Report</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="<?php echo base_url(); ?>tour/tour_details" method="GET">
                                                    <div class="row">
                                                        <!-- <div class="mb-3 col-md-6">
                                                            <label class="form-label" for="consignee">Pax Name</label>
                                                            <input class="form-control" type="text" id="consignee" name="consignee" />
                                                        </div> -->
                                                        <!-- <div class="mb-3 col-md-4">
                                                            <label class="form-label" for="email">email</label>
                                                            <input class="form-control" type="text" id="email" name="email" />
                                                        </div> -->
                                                        <div class="mb-3 col-md-12">
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

                                <?php
                                if (!empty($tour_details)) {
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
                                                                    <th>Q. Id</th>
                                                                    <th>Tour Name</th>
                                                                    <th>Agent Name</th>
                                                                    <th>Agent Contact No</th>
                                                                    <th>Pax Count</th>
                                                                    <th>Pax Name</th>
                                                                    <th>Vehicle's Rate</th>
                                                                    <th>Tour KM</th>
                                                                    <th>Extra KM</th>
                                                                    <th>Total Distance Fee</th>
                                                                    <th>Extra Activity Fee</th>
                                                                    <th>Start Date</th>
                                                                    <th>End Date</th>
                                                                    <th>T. Added By</th>
                                                                    <th>T. Added Date</th>
                                                                    <th>Status</th>
                                                                    <th>Finalize</th>
                                                                    <th>Action</th>
                                                                    <th>Quatation Print</th>
                                                                    <th>Itinerary Print</th>
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
                                                                        <td><?= $row->consignee; ?></td>
                                                                        <td><?= $row->agent_name; ?></td>
                                                                        <td><?= $row->mobile; ?></td>
                                                                        <td><?= $row->passenger_count; ?></td>
                                                                        <td>
                                                                            <?php
                                                                            $pax_names = array();
                                                                            foreach ($pax_details as $row1) {
                                                                                if ($row1['tour_id'] == $row->tour_id) {
                                                                                    $pax_names[] = $row1['pass_name'];
                                                                                }
                                                                            }
                                                                            echo implode('<br>', $pax_names);
                                                                            ?>
                                                                        </td>
                                                                        <td><?= $row->vehicle_rate; ?></td>
                                                                        <td><?= $row->fullkm; ?></td>
                                                                        <td><?= $row->extra_km; ?></td>
                                                                        <td><?= number_format((($row->extra_km + $row->fullkm) * $row->vehicle_rate), 2); ?></td>
                                                                        <td><?= $row->activity_fee; ?></td>
                                                                        <td><?= $row->start_date; ?></td>
                                                                        <td><?= $row->end_date; ?></td>
                                                                        <td><?= $row->create_user; ?></td>
                                                                        <td><?= $row->create_time; ?></td>
                                                                        <td>
                                                                            <?php if ($row->Confirm == 1) { ?>
                                                                                <h5><code>Confirmed</code></h5>
                                                                            <?php } else { ?>
                                                                                <h5><code>Not Confirmed</code></h5>
                                                                            <?php } ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php if ($row->Finalize == 1) { ?>
                                                                                <h5><code>Finalized</code></h5>
                                                                            <?php } else { ?>
                                                                                <h5><code>Not Finalized</code></h5>
                                                                            <?php } ?>
                                                                        </td>

                                                                        <td>
                                                                            <a href="<?= base_url('tour/tour_details/show_details?id=' . $row->tour_id . '&start=' . $row->start_date . '&end=' . $row->end_date); ?>" class="btn btn-warning btn-sm" target="_blank" data-toggle="tooltip" title="View"><i class="bi bi-eye-fill"></i></a>
                                                                            <a href="<?= base_url('tour/tour?tour_id=' . $row->tour_id . '&start=' . $row->start_date . '&end=' . $row->end_date); ?>" class="btn btn-secondary btn-sm" target="_blank" data-toggle="tooltip" title="Edit"><i class="bi bi-pencil"></i></a>
                                                                            <?php if ($row->Confirm == 1) { ?>
                                                                                <button type="button" class="btn btn-primary btn-sm tour_advance" title="Advance Payment" onClick='addPayment(<?= $row->tour_id; ?>, "<?= $row->consignee; ?>")' data-bs-toggle='modal' data-bs-target='#paymentModal'><i class="bi bi-badge-ad-fill"></i></button>
                                                                            <?php } else { ?>
                                                                                <button type="button" class="btn btn-success btn-sm tour_confirm" title="Tour Confirm" data-tour="<?= $row->tour_id; ?>" data-start="<?= $row->start_date; ?>" data-end="<?= $row->end_date; ?>"><i class="bi bi-check-circle-fill"></i></button>
                                                                            <?php } ?>

                                                                        </td>
                                                                        <td>
                                                                            <?php if ($row->Finalize == 1) { ?>
                                                                                <a href="<?= base_url('tour/print?tour_id=' . $row->tour_id); ?>" class="btn btn-dark btn-sm" target="_blank" data-toggle="tooltip" title="Print"><i class="bi bi-printer"></i></a>
                                                                            <?php } ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php if ($row->Finalize == 1) { ?>
                                                                                <a href="<?= base_url('tour/print/itinerary?tour_id=' . $row->tour_id); ?>" class="btn btn-dark btn-sm" target="_blank" data-toggle="tooltip" title="Print"><i class="bi bi-printer"></i></a>
                                                                            <?php } ?>
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


        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="paymentModalLabel">Add Payment</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                <input type="hidden" name="order_id" id="order_id">
                                <input type="hidden" name="customer" id="customer">
                                <div class="mb-4">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Method<font color="#FF0000"><strong>*</strong></font></label>
                                        <select name="bank_account" id="bank_account" class="js-example-basic-single form-control" style="width: 100%;" required>
                                            <option value="">Select a Method...</option>
                                            <?php
                                            foreach ($methods as $method) {
                                                echo '<option value="' . $method['L_Code'] . '">' . $method['PName'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="cash">Amount<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="cash" name="cash" placeholder="" required />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="pay_date">Payment Date<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="pay_date" name="pay_date" data-provide="flatpickr" data-date-autoclose="true" data-date-format="yyyy-mm-dd" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" placeholder="" required />
                                    </div>

                                    <button type="submit" class="btn btn-success mt-2" id="add_amount">Save</button>
                                </div>
                            </form>
                            <div class="px-3">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Date</th>
                                            <th scope="col" class='text-end'>Amount</th>
                                            <th scope="col" class='text-end'>Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody id="payments_details">

                                    </tbody>
                                </table>
                                <div class="mt-4">
                                    <h6 class="fw-normal">Total: <strong id="total"></strong></h6>
                                </div>
                            </div>
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
        document.addEventListener("DOMContentLoaded", function() {
            flatpickr("#drange", {
                mode: "range",
                dateFormat: "Y-m-d"
            });
        });

        // $(document).ready(function() {
        $(".tour_confirm").on('click', function(e) {
            // alert('test');
            e.preventDefault();

            var tour_id = $(this).data('tour');
            // var start = $(this).data('start');
            // var end = $(this).data('end');

            // $.ajax({
            //     url: '<?php echo base_url('tour/tour_details/confirm_tour') ?>',
            //     type: 'GET',
            //     data: {
            //         tour_id: tour_id,
            //     },
            //     dataType: 'json',
            //     success: function(data) {

            //         if (data.status == 200) {
            //             // Swal.fire({
            //             //     icon: 'success',
            //             //     title: 'Success',
            //             //     text: data.message,
            //             // }).then((result) => {
            //             //     if (result.isConfirmed) {
            //             //         location.reload();
            //             //     }
            //             // });

            window.location.href = "<?php echo base_url(); ?>accounts/debtor_invoice_new?tour_id=" + tour_id;
            //             } else {
            //                 Swal.fire({
            //                     icon: 'error',
            //                     title: 'Error',
            //                     text: data.message ? data.message : 'An error occured while processing the request',
            //                 });
            //             }
            //         },
            //         error: function() {

            //             Swal.fire({
            //                 icon: 'error',
            //                 title: 'Error',
            //                 text: 'An error occured while processing the request',
            //             });
            //         }
            //     });
        });
        // });

        function addPayment(index, name) {

            $("#order_id").val(index);
            $("#customer").val(name);
            advance_details(index);
            $("#paymentModal").show();
        }

        $("#form_submit").on('submit', function(e) {
            var tour = $("#order_id").val();
            $.ajax({
                url: "<?php echo base_url(); ?>tour/tour_details/payment_details?tour_id=" + tour,
                type: "POST",
                dataType: "json",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                success: function(data) {

                    if (data.status == 200) {
                        advance_details(data.tour);
                        $("#form_submit")[0].reset();
                        // $("#payments_details").html('');
                        // var tr = '';

                        // var total = 0;
                        // $.each(data, function(index, item) {

                        //     // Create a new table row
                        //     tr += "<tr id='" + item.ID + "'>";
                        //     tr += "<td>" + (index + 1) + "</td>";
                        //     tr += "<td>" + (item.Date || '') + "</td>";
                        //     tr += "<td class='text-end'>" + (item.Cr || '') + "</td>";
                        //     tr += "</tr>";

                        //     total += parseFloat(item.Cr);

                        // });

                        // // Append the row to the table body
                        // $("#payments_details").html(tr);
                        // $("#total").html(parseFloat(total).toFixed(2));
                    } else {

                    }
                }
            });
        });

        function advance_details(tour) {
            $.ajax({
                url: "<?php echo base_url() ?>tour/tour_details/advance_details/" + tour,
                type: "get",
                dataType: "json",
                success: function(data) {
                    if (data.status == 200) {
                        $("#payments_details").html('');
                        var tr = '';

                        var total = 0;
                        $.each(data.data, function(index, item) {

                            // Create a new table row
                            tr += "<tr id='" + item.ID + "'>";
                            tr += "<td>" + (index + 1) + "</td>";
                            tr += "<td>" + (item.Date || '') + "</td>";
                            tr += "<td class='text-end'>" + (item.Cr || '') + "</td>";
                            tr += "<td><button type='button' class='btn btn-sm btn-danger' onClick='delete_advance(" + item.ID + ")'><i class='bi bi-trash'></i></button></td>";
                            tr += "</tr>";

                            total += parseFloat(item.Cr);

                        });

                        // Append the row to the table body
                        $("#payments_details").html(tr);
                        $("#total").html(parseFloat(total).toFixed(2));
                    }
                }
            });
        }

        function delete_advance(ID) {
            var tour = $("#order_id").val();

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this Advance Payment!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "get",
                        url: "<?php echo base_url(); ?>tour/tour_details/advance_delete",
                        data: {
                            id: ID
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
                                        // load_activity(activity_tour_id, activity_row, activity_date);
                                        // $('#placetovisit').empty();
                                        // $('#activity_rate').val('');
                                        advance_details(tour)
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
    </script>



</body>

</html>