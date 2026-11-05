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
                                        <h4 class="p-title">Tour Amendment Finalize</h4>
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
                                                                    <th>Tour Start Date</th>
                                                                    <th>Tour End Date</th>
                                                                    <!-- <th>Action</th> -->
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
                                                                        <td><?php echo !empty($litem['tour_start_date']) ? date('d M Y', strtotime($litem['tour_start_date'])) : '-' ?></td>
                                                                        <td><?php echo !empty($litem['tour_end_date']) ? date('d M Y', strtotime($litem['tour_end_date'])) : '-' ?></td>
                                                                        <!-- <td class="text-center">
                                                                            <button type="button" class="btn btn-success btn-sm btn-action" data-inv="<?php //echo $litem['invoice_no']; ?>" data-status="accept" data-flag="1" data-type="<?php //echo $litem['requested_flag']; ?>"><i class="bi bi-check"></i></button>
                                                                            <button type="button" class="btn btn-danger btn-sm btn-action" data-inv="<?php //echo $litem['invoice_no']; ?>" data-status="reject" data-flag="3" data-type="<?php //echo $litem['requested_flag']; ?>"><i class="bi bi-x"></i></button>
                                                                        </td> -->

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
                                                                    <td></td>
                                                                    <td></td>
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

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $(document).on('click', '.btn-action', function() {
            var invoice_no = $(this).data('inv');
            var status = $(this).data('status');
            var flag = $(this).data('flag');
            var type = $(this).data('type');
            cancellation(invoice_no, status, flag, type);
        });

        function cancellation(invoice_no, status, flag, type) {
            var id = invoice_no;
            var status = status;
            var flag = flag;
            var type = type;
            // alert(id);

            if (flag == 1) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to accept this request!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "POST", // tf-e8-access-r1: was GET
                            url: "<?php echo base_url(); ?>accounts/debtor_invoice/cancellation",
                            data: {
                                id: id,
                                status: status,
                                flag: flag,
                                type: type
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
                    text: "You want to reject this request!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "POST", // tf-e8-access-r1: was GET
                            url: "<?php echo base_url(); ?>accounts/debtor_invoice/cancellation",
                            data: {
                                id: id,
                                status: status,
                                flag: flag,
                                type: type
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