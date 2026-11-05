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
                                        <h4 class="p-title">Creditor Payments</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-4">
                                                <form action="<?php echo base_url('accounts/creditor_payment') ?>" method="GET" accept-charset="utf-8">
                                                    <fieldset>
                                                        <div class="row clearfix mb-3">
                                                            <div class="col-sm-12">
                                                                <div class="form-group">
                                                                    <label class="form-label">Supplier<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <select name="auction" id="auction" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                        <option value="">Select a Supplier...</option>
                                                                        <?php
                                                                        $selected_auction = isset($auction) ? $auction : 0;
                                                                        $selected_action_name = '';
                                                                        foreach ($auctions as $auction) {
                                                                            $selected = '';
                                                                            if ($selected_auction == $auction['Sup_Acc_No']) {
                                                                                $selected = 'selected';
                                                                                $selected_action_name = $auction['Name'];
                                                                            }
                                                                            echo '<option value="' . $auction['Sup_Acc_No'] . '" ' . $selected . '>' . $auction['Name'] . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row clearfix mb-3">
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">From</label>
                                                                    <input class="form-control" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" class="form-control" id="from" name="from" value="<?php echo isset($from) ? $from : ""; ?>" />
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">To</label>
                                                                    <input class="form-control" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" class="form-control" id="to" name="to" value="<?php echo isset($to) ? $to : ""; ?>" />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <button type="submit" class="btn btn-success  me-5px">Generate</button>
                                                        <button type="reset" class="btn btn-default reset">Cancel</button>
                                                    </fieldset>
                                                </form>
                                                <div class="mt-4">
                                                    <?php if (isset($transactions) && !empty($transactions)) {
                                                    ?>
                                                        <div class="bg-dark text-light rounded w-100 p-2 mb-2">Transactions of <?= $selected_action_name; ?></div>
                                                        <table class="table table-striped" id="transactions_table">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 10%;" scope="col">#</th>
                                                                    <th style="width: 15%;" scope="col">Date</th>
                                                                    <th style="width: 15%;" scope="col">Invoice No</th>
                                                                    <th scope="col">Description</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">DR</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">CR</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $x = 1;
                                                                foreach ($transactions as $transaction) {
                                                                ?>
                                                                    <tr>
                                                                        <td scope="row"><?= $x++; ?></td>
                                                                        <td><?= $transaction['Date']; ?></td>
                                                                        <td><?= $transaction['Invoice_Num']; ?></td>
                                                                        <td><?= $transaction['Discription']; ?></td>
                                                                        <td class="text-end"><?= $transaction['Dr']; ?></td>
                                                                        <td class="text-end"><?= $transaction['Cr']; ?></td>
                                                                    </tr>
                                                                <?php
                                                                    $x++;
                                                                } ?>
                                                            </tbody>
                                                        </table>
                                                        <?php
                                                    } else {
                                                        if ($is_search) {
                                                        ?>
                                                            <div class="alert alert-warning mb-3" role="alert">
                                                                No transactions found for the selected supplier for the given date range.
                                                            </div>
                                                    <?php
                                                        }
                                                    } ?>
                                                    <?php if (isset($daterange_total) && $daterange_total > 0) {
                                                    ?>
                                                        <div class="px-2">
                                                            <div class="d-flex mt-3">
                                                                <div class="me-auto">
                                                                    <span>Date Range Total (<?= $from . ' - ' . $to ?>)</span>
                                                                </div>
                                                                <div class="fw-bold">
                                                                    <?= isset($daterange_total) ? $daterange_total : 0.00; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                    <?php if (isset($total) && $total > 0) {
                                                    ?>
                                                        <div class="px-2">
                                                            <div class="d-flex mt-3">
                                                                <div class="me-auto">
                                                                    <h5><strong>Total Payable</strong></h5>
                                                                </div>
                                                                <div class="pb-0">
                                                                    <h5><strong><code><?= isset($total) ? $total : 0.00; ?></strong></code></h5>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (isset($total) && $total > 0) {
                                ?>
                                    <div class="row clearfix mt-2">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <h6 class="mb-3"><strong>Payment Details</strong></h6>
                                                    <form id="form_submit" accept-charset="utf-8">
                                                        <fieldset>
                                                            <div class="row clearfix mb-3">
                                                                <div class="col-sm-4">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Tour ID</label>
                                                                        <input class="form-control txt_nav" type="text" id="tour_id" name="tour_id" value="" />
                                                                    </div>
                                                                </div>
                                                                <div class="col-sm-8">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                        <input class="form-control default-date txt_nav" data-provide="datepicker" data-date-autoclose="true" data-date-format="yyyy/mm/dd" class="form-control" id="payment_date" name="payment_date" value="<?php echo date('Y/m/d'); ?>" required />
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row clearfix mb-3">
                                                                <div class="col-sm-12">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Invoice No.</label>
                                                                        <input class="form-control txt_nav" type="text" id="inv" name="inv" value="" />
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row clearfix mb-3">
                                                                <div class="col-sm-12">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Reference</label>
                                                                        <input class="form-control txt_nav" type="text" id="ref" name="ref" value="" />
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row clearfix mb-3">
                                                                <div class="col-sm-12">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Payment Method<font color="#FF0000"><strong>*</strong></font></label>
                                                                        <select name="payment_method" id="payment_method" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                            <option value="0">Select a Method...</option>
                                                                            <?php
                                                                            foreach ($methods as $method) {
                                                                                echo '<option value="' . $method['L_Code'] . '">' . $method['PName'] . '</option>';
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row clearfix mb-3">
                                                                <div class="col-sm-12">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Amount<font color="#FF0000"><strong>*</strong></font></label>
                                                                        <input class="form-control txt_nav" type="text" id="amount" name="amount" value="" />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <br>
                                                            <button type="submit" class="btn btn-success  me-5px">Complete Payment</button>
                                                        </fieldset>
                                                    </form>
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
        // #form_submit submit
        $('#form_submit').submit(function(e) {
            e.preventDefault();
            var auction = $('#auction').val();
            var amount = $('#amount').val();
            var tour_id = $('#tour_id').val();
            var date = $('#payment_date').val();
            var payment_method = $('#payment_method').val();
            var description = $('#ref').val();
            var inv = $('#inv').val();

            if (auction == 0 || amount == '' || date == '' || payment_method == 0 || tour_id == '' || inv == '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please fill all the required fields',
                });
                return;
            }

            $.ajax({
                url: '<?php echo base_url('accounts/creditor_payment/complete_payment') ?>',
                type: 'POST',
                data: {
                    auction: auction,
                    amount: amount,
                    tour_id: tour_id,
                    date: date,
                    payment_method: payment_method,
                    description: description,
                    inv: inv
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                        }).then((result) => {
                            location.reload();
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

        // .reset click
        $('.reset').click(function() {
            window.location.href = '<?php echo base_url('accounts/creditor_payment') ?>';
        });

        // #transactions_table - loop through the each tbody tr and export to excel
        $('#transactions_table').DataTable({
            "paging": false,
            "info": false,
            "searching": false,
            "ordering": false,
            "scrollY": "300px",
            "scrollCollapse": true,
            "scrollX": true,
            "autoWidth": false,
            "responsive": true,
            "dom": 'Bfrtip',
            "buttons": [{
                extend: 'csv',
                text: '<i class="bi bi-file-earmark-spreadsheet-fill"></i> Export to CSV',
                className: 'btn btn-primary border-0'
            }]
        });
    </script>
</body>




</html>