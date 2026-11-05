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
                                        <h4 class="p-title">Debtor Payments</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-4">
                                                <form action="<?php echo base_url('accounts/debtor_payment') ?>" method="GET" accept-charset="utf-8">
                                                    <fieldset>
                                                        <div class="row clearfix mb-3">
                                                            <div class="col-sm-12">
                                                                <div class="form-group">
                                                                    <label class="form-label">Consignee<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <select name="customer" id="customer" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                        <option value="">Select a Consignee...</option>
                                                                        <?php
                                                                        $selected_customer = isset($customer) ? $customer : 0;
                                                                        $selected_action_name = '';
                                                                        foreach ($customers as $customer) {
                                                                            $selected = '';
                                                                            if ($selected_customer == $customer['L_Code']) {
                                                                                $selected = 'selected';
                                                                                $selected_action_name = $customer['PName'];
                                                                            }
                                                                            echo '<option value="' . $customer['L_Code'] . '" ' . $selected . '>' . $customer['PName'] . '</option>';
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
                                                                    <input class="form-control default-date txt_nav" data-provide="datepicker" data-date-autoclose="true" data-date-format="yyyy/mm/dd" class="form-control" id="from" name="from" value="<?php echo isset($from) ? $from : ""; ?>" />
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">To</label>
                                                                    <input class="form-control default-date txt_nav" data-provide="datepicker" data-date-autoclose="true" data-date-format="yyyy/mm/dd" class="form-control" id="to" name="to" value="<?php echo isset($to) ? $to : ""; ?>" />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <button type="submit" class="btn btn-success  me-5px">Generate</button>
                                                        <button type="reset" class="btn btn-default reset">Cancel</button>
                                                    </fieldset>
                                                </form>

                                                <div class="mt-4">
                                                    <?php
                                                    if (isset($transactions) && !empty($transactions)) {
                                                        $regularTx = array_filter($transactions, fn($t) => strpos($t['Invoice_Num'], 'AH-OI-') !== 0);
                                                        $otherTx   = array_filter($transactions, fn($t) => strpos($t['Invoice_Num'], 'AH-OI-') === 0);
                                                    ?>

                                                        <?php if (!empty($regularTx)): ?>
                                                        <div class="bg-dark text-light rounded w-100 p-2 mb-2">Transactions of <?= $selected_action_name; ?></div>
                                                        <table class="table table-striped" id="transactions_table">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 15%;" scope="col">Date</th>
                                                                    <th style="width: 15%;" scope="col">Tour Id</th>
                                                                    <th style="width: 15%;" scope="col">Invoice No</th>
                                                                    <th scope="col" style="width: 25%;">Description</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Dollarrate & Dollar Count</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Due ($)</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Due or Balance (LKR)</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Dollar Amount</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Dollar Rate</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Payment Received Amount</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Exchange PNL</th>
                                                                    <th></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($regularTx as $key => $transaction):
                                                                    $lkrBal = $transaction['Dr'] - $transaction['Cr'];
                                                                ?>
                                                                    <tr>
                                                                        <th><?= $transaction['FirstOfDate']; ?></th>
                                                                        <td><?= $transaction['manual_id']; ?></td>
                                                                        <td><?= $transaction['Invoice_Num']; ?></td>
                                                                        <td><?= $transaction['Discription']; ?></td>
                                                                        <td class="text-end"><?= $transaction['dollarrate'] . ' x ' . $transaction['dollar'] . ' = ' . ($transaction['dollarrate'] * $transaction['dollar']); ?></td>
                                                                        <td class="text-end"><?= $transaction['DBal']; ?></td>
                                                                        <td class="text-end dr"><?= $lkrBal ?></td>
                                                                        <td class="text-end"><input type="text" class="form-control d_amt" name="d_amt" data-index="<?= $key ?>"></td>
                                                                        <td class="text-end"><input type="text" class="form-control d_rate" name="d_rate" data-index="<?= $key ?>"></td>
                                                                        <td class="text-end cr_rec"><input type="text" class="form-control cr" name="cr" data-index="<?= $key ?>"></td>
                                                                        <td class="text-end"><span class="bal"><?= $lkrBal; ?></span>
                                                                            <strong><span class="status" data-index="<?= $key ?>"></span></strong>
                                                                        </td>
                                                                        <td style="display: none;"><?= $transaction['index_no']; ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                        <?php endif; ?>

                                                        <!-- <?php //if (!empty($otherTx)): ?>
                                                        <div class="bg-secondary text-light rounded w-100 p-2 mb-2 mt-3">Other Invoices of <?//= $selected_action_name; ?></div>
                                                        <table class="table table-striped" id="other_transactions_table">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 10%;" scope="col">Date</th>
                                                                    <th style="width: 10%;" scope="col">Invoice No</th>
                                                                    <th scope="col" style="width: 15%;">Description</th>
                                                                    <th class="text-end" style="width: 10%;" scope="col">JPY Rate & JPY Count</th>
                                                                    <th class="text-end" style="width: 10%;" scope="col">Due (¥)</th>
                                                                    <th class="text-end" style="width: 10%;" scope="col">Due or Balance (LKR)</th>
                                                                    <th class="text-end" style="width: 10%;" scope="col">JPY Amount</th>
                                                                    <th class="text-end" style="width: 10%;" scope="col">JPY Rate</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Payment Received Amount</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Exchange PNL</th>
                                                                    <th></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php //foreach ($otherTx as $key => $transaction):
                                                                    //$lkrBal = $transaction['Dr'] - $transaction['Cr'];
                                                                ?>
                                                                    <tr>
                                                                        <th><?//= $transaction['FirstOfDate']; ?></th>
                                                                        <td><?//= $transaction['Invoice_Num']; ?></td>
                                                                        <td><?//= $transaction['Discription']; ?></td>
                                                                        <td class="text-end"><?//= $transaction['oi_conversion_rate'] . ' x ' . $transaction['oi_amount_jpy'] . ' = ' . $transaction['oi_amount_lkr']; ?></td>
                                                                        <td class="text-end"><?//= $transaction['oi_amount_jpy']; ?></td>
                                                                        <td class="text-end dr"><?//= $lkrBal ?></td>
                                                                        <td class="text-end"><input type="text" class="form-control d_amt" name="d_amt" data-index="<?//= $key ?>" value="<?//= $transaction['oi_amount_jpy'] ?>" readonly style="background:#e9ecef;"></td>
                                                                        <td class="text-end"><input type="text" class="form-control d_rate" name="d_rate" data-index="<?//= $key ?>" value="<?//= $transaction['oi_conversion_rate'] ?>" readonly style="background:#e9ecef;"></td>
                                                                        <td class="text-end cr_rec"><input type="text" class="form-control cr" name="cr" data-index="<?//= $key ?>" value="<?//= $lkrBal ?>"></td>
                                                                        <td class="text-end"><span class="bal"><?//= $lkrBal; ?></span>
                                                                            <strong><span class="status" data-index="<?//= $key ?>"></span></strong>
                                                                        </td>
                                                                        <td style="display: none;"><?//= $transaction['index_no']; ?></td>
                                                                    </tr>
                                                                <?php //endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                        <?php //endif; ?> -->
                                                        <?php
                                                    } else {
                                                        if ($is_search) {
                                                        ?>
                                                            <div class="alert alert-warning mb-3" role="alert">
                                                                No transactions found for the selected consignee for the given date range.
                                                            </div>
                                                        <?php
                                                        }
                                                        if (isset($total) && $total <= 0) {
                                                        ?>
                                                            <div class="alert alert-warning" role="alert">
                                                                No payable amount found for the selected consignee.
                                                            </div>
                                                    <?php
                                                        }
                                                    } ?>
                                                    <?php if (isset($total) && $total > 0) {
                                                    ?>
                                                        <div class="px-2">
                                                            <div class="d-flex mt-3">
                                                                <div class="me-auto">
                                                                    <!-- <h4><strong>Total Payable</strong></h4> -->
                                                                    <h4><strong>Total Received Amount</strong></h4>
                                                                </div>
                                                                <div class="pb-0">
                                                                    <h4><strong><code id="total_pay"><?= 0.00; ?></strong></code></h4>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                    <?php if (isset($transactions) && !empty($transactions)) {
                                                    ?>
                                                        <div class="d-flex justify-content-end mt-3">
                                                            <button type="button" class="btn btn-success pay_details" data-bs-toggle="modal" data-bs-target="#paymentDetailsModal">Add Payment Details</button>

                                                            <!-- Payment Details Modal -->
                                                            <div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
                                                                <div class="modal-dialog modal-lg">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="paymentDetailsModalLabel">Payment Details</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <form id="form_submit" accept-charset="utf-8">
                                                                                <input type="hidden" name="customer_lcode" id="customer_lcode" value="<?php echo $selected_customer; ?>">
                                                                                <!-- <input type="text" name="total" id="total" value="<?php //echo $total; 
                                                                                                                                        ?>"> -->

                                                                                <fieldset>
                                                                                    <div class="row clearfix">
                                                                                        <div class="col-sm-12">
                                                                                            <div class="form-group">
                                                                                                <label class="form-label">Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                                                <input class="form-control default-date txt_nav" data-provide="datepicker" data-date-autoclose="true" data-date-format="yyyy/mm/dd" class="form-control" id="payment_date" name="payment_date" value="<?php echo date('Y/m/d'); ?>" required />
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>

                                                                                    <div class="row clearfix">
                                                                                        <div class="col-sm-12">
                                                                                            <div class="form-group">
                                                                                                <label class="form-label">Reference</label>
                                                                                                <input class="form-control txt_nav" type="text" id="ref" name="ref" value="" />
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>

                                                                                    <div class="row clearfix">
                                                                                        <div class="col-sm-12">
                                                                                            <div class="form-group">
                                                                                                <label class="form-label">LKR Payment Method<font color="#FF0000"><strong>*</strong></font></label>
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

                                                                                    <div class="row clearfix mt-2">
                                                                                        <div class="col-sm-12">
                                                                                            <div class="form-group">
                                                                                                <label class="form-label">USD Bank Account</label>
                                                                                                <select name="usd_payment_method" id="usd_payment_method" class="js-example-basic-single form-control" style="width: 100%;">
                                                                                                    <option value="0">Select USD Bank...</option>
                                                                                                    <?php
                                                                                                    foreach ($usd_methods as $method) {
                                                                                                        echo '<option value="' . $method['L_Code'] . '">' . $method['PName'] . '</option>';
                                                                                                    }
                                                                                                    ?>
                                                                                                </select>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>

                                                                                    <div class="row clearfix">
                                                                                        <div class="col-sm-12">
                                                                                            <div class="form-group">
                                                                                                <label class="form-label">Amount<font color="#FF0000"><strong>*</strong></font></label>
                                                                                                <input class="form-control txt_nav" type="text" id="amount" name="amount" value="" readonly />
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
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- <?php //if (isset($total) && $total > 0) {
                                        ?>
                                    <div class="row clearfix mt-2">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <h6 class="mb-3"><strong>Payment Details</strong></h6>
                                                    <form id="form_submit" accept-charset="utf-8">
                                                        <fieldset>
                                                            <div class="row clearfix">
                                                                <div class="col-sm-12">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                        <input class="form-control default-date txt_nav" data-provide="datepicker" data-date-autoclose="true" data-date-format="yyyy/mm/dd" class="form-control" id="payment_date" name="payment_date" value="<?php //echo date('Y/m/d'); 
                                                                                                                                                                                                                                                                                ?>" required />
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row clearfix">
                                                                <div class="col-sm-12">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Reference</label>
                                                                        <input class="form-control txt_nav" type="text" id="ref" name="ref" value="" />
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row clearfix">
                                                                <div class="col-sm-12">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Payment Method<font color="#FF0000"><strong>*</strong></font></label>
                                                                        <select name="payment_method" id="payment_method" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                            <option value="0">Select a Method...</option>
                                                                            <?php
                                                                            //foreach ($methods as $method) {
                                                                            //echo '<option value="' . $method['L_Code'] . '">' . $method['PName'] . '</option>';
                                                                            //}
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row clearfix">
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

                                <?php //} 
                                ?> -->
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
            var customer = $('#customer_lcode').val();
            var amount = $('#amount').val();
            var date = $('#payment_date').val();
            var payment_method = $('#payment_method').val();
            var usd_payment_method = $('#usd_payment_method').val();
            var ref = $('#ref').val();

            if (amount == 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please fill all the required fields',
                });
                return;
            }

            if (customer == '' || amount == '' || date == '' || payment_method == '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please fill all the required fields',
                });
                return;
            }

            // get transactions_table values and send to the server
            var transactions = [];
            $('#transactions_table tbody tr').each(function() {
                var cr = $(this).find('.cr').val();
                if (cr && cr != '' && parseFloat(cr) > 0) {
                    var date = $(this).find('th').text();
                    var tour_manu = $(this).find('td').eq(0).text();
                    var invoice_no = $(this).find('td').eq(1).text();
                    var dollarrate_dollarcnt = $(this).find('td').eq(2).text();
                    var dr = $(this).find('.dr').text();
                    var d_amt = $(this).find('.d_amt').val();
                    var d_rate = $(this).find('.d_rate').val();
                    var bal = $(this).find('.bal').text();
                    var status = $(this).find('.status').text();
                    var tour_id = $(this).find('td:last').text();

                    transactions.push({
                        date: date,
                        tour_manu: tour_manu,
                        invoice_no: invoice_no,
                        description: dollarrate_dollarcnt,
                        dr: dr,
                        cr: cr,
                        d_amt: d_amt,
                        d_rate: d_rate,
                        bal: bal,
                        status: status,
                        tour_id: tour_id
                    });
                }
            });

            // collect other invoices (JPY) table
            $('#other_transactions_table tbody tr').each(function() {
                var cr = $(this).find('.cr').val();
                if (cr && cr != '' && parseFloat(cr) > 0) {
                    var date = $(this).find('th').text();
                    var invoice_no = $(this).find('td').eq(0).text();
                    var description = $(this).find('td').eq(1).text();
                    var dr = $(this).find('.dr').text();
                    var d_amt = $(this).find('.d_amt').val();
                    var d_rate = $(this).find('.d_rate').val();
                    var bal = $(this).find('.bal').text();
                    var status = $(this).find('.status').text();
                    var tour_id = $(this).find('td:last').text();

                    transactions.push({
                        date: date,
                        tour_manu: '',
                        invoice_no: invoice_no,
                        description: description,
                        dr: dr,
                        cr: cr,
                        d_amt: d_amt,
                        d_rate: d_rate,
                        bal: bal,
                        status: status,
                        tour_id: tour_id
                    });
                }
            });

            // console.log(transactions);

            $.ajax({
                url: '<?php echo base_url('accounts/debtor_payment/complete_payment') ?>',
                type: 'POST',
                data: {
                    customer: customer,
                    amount: amount,
                    date: date,
                    payment_method: payment_method,
                    usd_payment_method: usd_payment_method,
                    ref: ref,
                    transactions: transactions
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
            window.location.href = '<?php echo base_url('accounts/debtor_payment') ?>';
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

        // #cr keyup
        $(document).on('keyup', '.cr', function() {

            var total = 0;
            var cr_val = $(this).val();
            var index = $(this).data('index');
            if (cr_val == '') {
                // set .bal to dr value
                $(this).closest('tr').find('.bal').text($(this).closest('tr').find('.dr').text());
                return;
            }
            var cr = parseFloat(cr_val).toFixed(2);
            var dr_val = $(this).closest('tr').find('.dr').text();
            // dr_val convert parseFloat and fixed to 2 decimal points
            var dr = parseFloat(dr_val).toFixed(2);
            var bal = parseFloat(dr) - parseFloat(cr);
            var balance = bal.toFixed(2);

            if (balance == 0) {
                $(this).closest('tr').find('.status').text('Settled');
                // change color to black
                $(this).closest('tr').find('.status').css('color', 'black');
            } else if (balance < 0) {
                $(this).closest('tr').find('.status').text('Profit');
                balance = balance.toString().replace('-', '');
                // change color to red
                $(this).closest('tr').find('.status').css('color', 'green');
            } else {
                $(this).closest('tr').find('.status').text('Loss');
                // change color to green
                $(this).closest('tr').find('.status').css('color', 'red');
            }
            $(this).closest('tr').find('.bal').text(balance);

            total_paybal_balance();

        });

        // total paybal balance
        function total_paybal_balance() {
            var total = 0;
            // get total of all .cr
            $('.cr').each(function() {
                var cr_val = $(this).val();
                if (cr_val != '') {
                    total += parseFloat(cr_val);
                }
            });
            $('#total_pay').text(total.toFixed(2));
            $('#amount').val(total.toFixed(2));
        }

        // #paymentDetailsModal - show.bs.modal
        $('#paymentDetailsModal').on('show.bs.modal', function(e) {
            // get total of all .cr
            total_paybal_balance();
        });

        // Autofill CR (Payment Received Amount) when Doller Amount or Doller Rate changes
        $(document).on('keyup', '.d_amt, .d_rate', function() {
            if ($(this).prop('readonly')) return;
            var index = $(this).data('index');
            var d_amt = parseFloat($('.d_amt[data-index="' + index + '"]').val()) || 0;
            var d_rate = parseFloat($('.d_rate[data-index="' + index + '"]').val()) || 0;

            if (d_amt > 0 && d_rate > 0) {
                var cr_value = (d_amt * d_rate).toFixed(2);
                $('.cr[data-index="' + index + '"]').val(cr_value);

                // Trigger the keyup event on cr to calculate balance
                $('.cr[data-index="' + index + '"]').trigger('keyup');
            }
        });
    </script>
</body>




</html>