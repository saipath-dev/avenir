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
                                        <h4 class="p-title">Tour Search</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="<?php echo base_url(); ?>tour/tour_details_search" method="GET">
                                                    <div class="row clearfix">
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label class="form-label">Keyword</label>
                                                                <input type="text" class="form-control" id="keyword" name="keyword" placeholder="Search by Manual ID or Tour Name" value="<?= isset($keyword) ? esc($keyword, 'attr') : '' ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-3">
                                                            <div class="form-group">
                                                                <label class="form-label">From</label>
                                                                <input type="date" class="form-control" id="from" name="from" value="<?= isset($date1) ? esc($date1, 'attr') : '' ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-3">
                                                            <div class="form-group">
                                                                <label class="form-label">To</label>
                                                                <input type="date" class="form-control" id="to" name="to" value="<?= isset($date2) ? esc($date2, 'attr') : '' ?>" />
                                                            </div>
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
                                                                    <th>Manual Id</th>
                                                                    <th>Tour Start & End Date</th>
                                                                    <th>Tour Name</th>
                                                                    <th>Agent Name</th>
                                                                    <th>Pax Count</th>
                                                                    <th>Tour Created By</th>
                                                                    <th>Status</th>
                                                                    <th>Finalize</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $i = 1;
                                                                foreach ($tour_details as $row) {
                                                                    $start_date = $row->start_date;
                                                                    // get $start_date year 
                                                                    $start_date_year = date('Y', strtotime($start_date));
                                                                    // get $start_date month
                                                                    $start_date_month = date('m', strtotime($start_date));
                                                                ?>
                                                                    <tr>
                                                                        <td><?= $i++; ?></td>
                                                                        <td><?= $row->manual_id; ?></td>
                                                                        <td><?= date('Y-m-d', strtotime($row->start_date)) . ' to ' . date('Y-m-d', strtotime($row->end_date)); ?></td>
                                                                        <td><?= $row->consignee; ?></td>
                                                                        <td><?= $row->agent_name; ?></td>
                                                                        <td><?= $row->passenger_count; ?></td>
                                                                        <!-- <td>
                                                                            <?php
                                                                            //$pax_names = array();
                                                                            //foreach ($pax_details as $row1) {
                                                                            //if ($row1['tour_id'] == $row->tour_id) {
                                                                            //$pax_names[] = $row1['pass_name'];
                                                                            //}
                                                                            //}
                                                                            //echo implode('<br>', $pax_names);
                                                                            ?>
                                                                        </td> -->
                                                                        <td><?= $row->create_user; ?></td>

                                                                        <td>
                                                                            <?php if ($row->Confirm == 1) { ?>
                                                                                <h5><code>Confirmed</code></h5>
                                                                            <?php } else if ($row->Confirm == 3) { ?>
                                                                                <h5><code>Tour Cancel</code></h5>
                                                                            <?php } else if ($row->Confirm == 4) { ?>
                                                                                <h5><code>Amend Inv</code></h5>
                                                                            <?php } else if ($row->Confirm == 0) { ?>
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
                                                                            <button type="button" class="btn btn-success btn-sm view" title="Tour Dashboard" data-tour_id="<?= $row->tour_id; ?>" data-year="<?= $start_date_year; ?>" data-month="<?= $start_date_month; ?>" data-confirm="<?= $row->Confirm; ?>"><i class="bi bi-eye-fill"></i></button>
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
                                <?php } else { ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <div class="alert alert-danger" role="alert">
                                                        No Records Found!
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
        $(".view").on('click', function() {
            // alert('test');
            var year = $(this).data('year');
            var month = $(this).data('month');
            var tour = $(this).data('tour_id');
            var confirm = $(this).data('confirm');
            if (confirm == 0 || confirm == 4) {
                window.open('<?= base_url(); ?>tour/tour_details/monthly_dashboard?year=' + year + '&month=' + month + '&tour_id=' + tour, '_blank');
            } else {
                window.open('<?= base_url(); ?>tour/tour_dashboard/monthly_dashboard?year=' + year + '&month=' + month + '&tour_id=' + tour, '_blank');
            }
        });
    </script>



</body>

</html>