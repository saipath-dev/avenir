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
                                        <h4 class="p-title">Tour Amendment Approval</h4>
                                    </div>
                                </div>

                                <!-- <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form id="date_form" accept-charset="utf-8" action="javascript:void(0)">
                                                    <div class="row">
                                                        <div class="mb-3 col-md-4">
                                                            <label class="form-label" for="customer_name">Customer </label>
                                                            <select name="customer_name" id="customer_name" class="js-example-basic-single form-control" style="width: 100%;">
                                                                <option value="">Select a Customer...</option>
                                                                <?php
                                                                //foreach ($customers as $customer) {
                                                                    //$selected = '';
                                                                    //echo '<option value="' . $customer['Sup_Acc_No'] . '"' . $selected . '>' . $customer['Name'] . '</option>';
                                                                //}
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3 col-md-4">
                                                            <label class="form-label" for="drange1">Start Date</label>
                                                            <input class="form-control" type="text" id="drange1" name="drange1" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                                        </div>
                                                        <div class="mb-3 col-md-4">
                                                            <label class="form-label" for="drange2">End Date</label>
                                                            <input class="form-control" type="text" id="drange2" name="drange2" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
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
                                </div> -->

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
                                                                    <th>Tour ID </th>
                                                                    <th>Date</th>
                                                                    <th>Customer</th>
                                                                    <th>Invoice No</th>
                                                                    <th>Exchange Rate ($)</th>
                                                                    <th>$ Amount</th>
                                                                    <th class="text-end">LKR Amount</th>
                                                                    <th>Request User & Date</th>
                                                                    <th>Type</th>
                                                                    <th>Reason</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $x = 1;
                                                                $dollar_total = 0;
                                                                $rs_total = 0;
                                                                foreach ($payments as $litem) {
                                                                    $total_cr = +$litem['credit_balance'];
                                                                    $type = $litem['requested_flag'];
                                                                    $type = ($type == '2') ? 'Cancellation' : 'Amendment';
                                                                ?>
                                                                    <tr style="width: 100%;">
                                                                        <td><?php echo $x ?></td>
                                                                        <td><?php echo $litem['manual_id'] ?></td>
                                                                        <!-- <td><?php //echo $litem['abc']['Date'] 
                                                                                    ?></td> -->
                                                                        <td><?php echo $litem['date'] ?></td>
                                                                        <td><?php echo $litem['cus_name'] ?></td>
                                                                        <td><?php echo $litem['invoice_no'] ?></td>
                                                                        <td><?php echo $litem['doller_rate'] ?></td>
                                                                        <td><?php echo $litem['dollar_amount'] ?></td>
                                                                        <td class="text-end"><?php echo $total_cr ?></td>
                                                                        <td><?php echo $litem['requested_user'] . ' - ' . $litem['requested_date'] ?></td>
                                                                        <td><?php echo $type ?></td>
                                                                        <td><?php echo $litem['requested_reason'] ?></td>
                                                                        <td class="text-center">
                                                                            <button type="button" class="btn btn-success btn-sm btn-action" data-inv="<?php echo $litem['invoice_no']; ?>" data-status="accept" data-flag="1" data-type="<?php echo $litem['requested_flag']; ?>"><i class="bi bi-check"></i></button>
                                                                            <button type="button" class="btn btn-danger btn-sm btn-action" data-inv="<?php echo $litem['invoice_no']; ?>" data-status="reject" data-flag="3" data-type="<?php echo $litem['requested_flag']; ?>"><i class="bi bi-x"></i></button>
                                                                        </td>

                                                                    </tr>
                                                                <?php
                                                                    $dollar_total += $litem['dollar_amount'];
                                                                    $rs_total += $litem['credit_balance'];
                                                                    $x++;
                                                                }
                                                                ?>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr>
                                                                    <td colspan="5"></td>
                                                                    <td>Total</td>
                                                                    <td><?php echo $dollar_total ?></td>
                                                                    <td class="text-end"><?php echo $rs_total ?></td>
                                                                    <td></td>
                                                                </tr>
                                                            </tfoot>
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

    <!-- Cancellation Fee Modal -->
    <div class="modal fade" id="cancellation_fee_modal" tabindex="-1" aria-labelledby="cancellationFeeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancellationFeeModalLabel">Cancellation Fee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="cancellation_fee_form">
                        <div class="mb-3">
                            <label for="cancel_fee_dollar" class="form-label">Dollar Amount</label>
                            <input type="number" class="form-control" id="cancel_fee_dollar" name="cancel_fee_dollar" step="0.01" placeholder="Enter dollar amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="cancel_fee_rate" class="form-label">Dollar Rate</label>
                            <input type="number" class="form-control" id="cancel_fee_rate" name="cancel_fee_rate" step="0.01" placeholder="Enter dollar rate" required>
                        </div>
                        <div class="mb-3">
                            <label for="cancel_fee" class="form-label">Cancellation Fee Amount (LKR)</label>
                            <input type="number" class="form-control" id="cancel_fee" name="cancel_fee" step="0.01" placeholder="Enter cancellation fee" readonly>
                        </div>
                        <input type="hidden" id="modal_invoice_no" name="invoice_no">
                        <input type="hidden" id="modal_status" name="status">
                        <input type="hidden" id="modal_flag" name="flag">
                        <input type="hidden" id="modal_type" name="type">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submit_cancellation_fee">Submit</button>
                </div>
            </div>
        </div>
    </div>

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

        $("#date_form").on('submit', function(e) {
            e.preventDefault();

            var date1 = $("#drange1").val();
            var date2 = $("#drange2").val();
            var cus = $("#customer_name").val();

            // if (cus) {
            //     window.location.href = "<?php //echo base_url(); 
                                            ?>accounts/debtor_invoice/report?cus=" + cus;
            //     // return false;
            // }

            // if (date1 == '' || date2 == '') {
            //     Swal.fire({
            //         icon: 'error',
            //         title: 'Oops...',
            //         text: 'Start Date and End Date are required!',
            //     });
            // } else {
            //     if (cus) {
            //         window.location.href = "<?php //echo base_url(); 
                                                ?>accounts/debtor_invoice/report?startdate=" + date1 + "&enddate=" + date2 + "&cus=" + cus;
            //         // return false;
            //     } else {
            //         window.location.href = "<?php //echo base_url(); 
                                                ?>accounts/debtor_invoice/report?startdate=" + date1 + "&enddate=" + date2;
            //     }
            // }
            window.location.href = "<?php echo base_url(); ?>accounts/debtor_invoice/get_cancellation_request?startdate=" + date1 + "&enddate=" + date2 + "&cus=" + cus;
            return false;

        });

        $(document).on('click', '.btn-action', function() {
            var invoice_no = $(this).data('inv');
            var status = $(this).data('status');
            var flag = $(this).data('flag');
            var type = $(this).data('type');
            var flag = $(this).data('flag');

            if(type == 2 && flag == 1){
                // Cancellation Fee add model popup
                $('#modal_invoice_no').val(invoice_no);
                $('#modal_status').val(status);
                $('#modal_flag').val(flag);
                $('#modal_type').val(type);
                $('#cancel_fee_dollar').val('');
                $('#cancel_fee_rate').val('');
                $('#cancel_fee').val('');
                $('#cancellation_fee_modal').modal('show');
            }else{
                cancellation(invoice_no, status, flag, type, null);
            }
            // cancellation(invoice_no, status, flag, type);
        });

        // Auto-calculate LKR amount when dollar amount or rate changes
        $(document).on('input', '#cancel_fee_dollar, #cancel_fee_rate', function() {
            var dollarAmount = parseFloat($('#cancel_fee_dollar').val()) || 0;
            var dollarRate = parseFloat($('#cancel_fee_rate').val()) || 0;
            var lkrAmount = dollarAmount * dollarRate;
            $('#cancel_fee').val(lkrAmount.toFixed(2));
        });

        $(document).on('click', '#submit_cancellation_fee', function() {
            var cancel_fee_dollar = $('#cancel_fee_dollar').val();
            var cancel_fee_rate = $('#cancel_fee_rate').val();
            var cancel_fee = $('#cancel_fee').val();
            var invoice_no = $('#modal_invoice_no').val();
            var status = $('#modal_status').val();
            var flag = $('#modal_flag').val();
            var type = $('#modal_type').val();

            if(!cancel_fee_dollar || !cancel_fee_rate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Please enter dollar amount and dollar rate!',
                });
                return false;
            }

            $('#cancellation_fee_modal').modal('hide');
            cancellation(invoice_no, status, flag, type, cancel_fee, cancel_fee_dollar, cancel_fee_rate);
        });

        function cancellation(invoice_no, status, flag, type, cancel_fee, cancel_fee_dollar, cancel_fee_rate) {
            var id = invoice_no;
            var status = status;
            var flag = flag;
            var type = type;
            var fee = cancel_fee;
            var fee_dollar = cancel_fee_dollar || null;
            var fee_rate = cancel_fee_rate || null;

            if (flag == 1) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to accept this " + status + " request!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Do it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "POST", // tf-e8-access-r1: was GET
                            url: "<?php echo base_url(); ?>accounts/debtor_invoice/cancellation",
                            data: {
                                id: id,
                                status: status,
                                flag: flag,
                                type: type,
                                cancellation_fee: fee,
                                cancellation_fee_dollar: fee_dollar,
                                cancellation_fee_rate: fee_rate
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
                                            location.reload();
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
            } else {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to reject this " + status + " request!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Reject it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "POST", // tf-e8-access-r1: was GET
                            url: "<?php echo base_url(); ?>accounts/debtor_invoice/cancellation",
                            data: {
                                id: id,
                                status: status,
                                flag: flag,
                                type: type,
                                cancellation_fee: fee
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
                                            location.reload();
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
        }
    </script>



</body>

</html>