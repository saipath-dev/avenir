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
                                                        <div class="row clearfix mb-4">
                                                            <div class="col-sm-4">
                                                                <div class="form-group">
                                                                    <label class="form-label">Rate ($)<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" placeholder="" class="form-control" id="rate" name="rate" required />
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <div class="form-group">
                                                                    <label class="form-label">Amount ($)<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" placeholder="" class="form-control" id="doller_amt" name="doller_amt" required />
                                                                    <small class="text-muted">
                                                                        Remaning Amount: <strong><code id="doller_amt_remain">0.00</code></strong>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <div class="form-group">
                                                                    <label class="form-label">Amount (LKR)<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" placeholder="" class="form-control" id="lkr_amt" name="lkr_amt" required />
                                                                    <small class="text-muted">
                                                                        Remaning Amount: <strong><code id="lkr_amt_remain">0.00</code></strong>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <table class="table table-striped" id="transactions_table">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 5%;" scope="col"></th>
                                                                    <th style="width: 15%;" scope="col">Date</th>
                                                                    <th style="width: 15%;" scope="col">Invoice No</th>
                                                                    <th scope="col" style="width: 25%;">Description</th>
                                                                    <th class="text-end" style="width: 10%;" scope="col">Due($)</th>
                                                                    <th class="text-end" style="width: 10%;" scope="col">Due(LKR)</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Payment Received Amount ($)</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Payment Received Amount (LKR)</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Exchange PNL</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                foreach ($transactions as $key => $transaction) {
                                                                    $b = $transaction['Dr'] - $transaction['Cr'];
                                                                ?>
                                                                    <tr class="tbl_tr">
                                                                        <th>
                                                                            <?php if ($b > 0) { ?>
                                                                                <input type="checkbox" class="check_no" data-index="<?= $key ?>" data-inv="<?= $transaction['Invoice_Num']; ?>" name="transaction_ids[]" value="<?= $key + 1; ?>">
                                                                            <?php } ?>
                                                                        </th>
                                                                        <th data-index="<?= $key ?>" class="t_date"><?= $transaction['Date']; ?></th>
                                                                        <td data-index="<?= $key ?>" class="t_inv"><?= $transaction['Invoice_Num']; ?></td>
                                                                        <td data-index="<?= $key ?>" class="t_description"><?= $transaction['Discription']; ?></td>
                                                                        <td data-index="<?= $key ?>" class="text-end due_doller">
                                                                            <?= $transaction['dollar'] > 0 ? $transaction['dollar'] : ''; ?>
                                                                        </td>
                                                                        <td data-index="<?= $key ?>" class="text-end dr">
                                                                            <?= $transaction['Dr'] > 0 ? $transaction['Dr'] : ''; ?>
                                                                        </td>
                                                                        <td class="text-end cr_doller" data-index="<?= $key ?>"></td>
                                                                        <td data-index="<?= $key ?>" class="text-end cr_rec">
                                                                            <?= $transaction['Cr'] > 0 ? $transaction['Cr'] : '<input type="text" class="form-control cr" data-inv=" ' . $transaction['Invoice_Num'] . '" name="cr" id="cr" data-index="' . $key . '">'; ?>
                                                                        </td>
                                                                        <td class="text-end">
                                                                            <div class="d-flex flex-column">
                                                                                <span data-index="<?= $key ?>" class="bal">
                                                                                    <?= $transaction['Dr'] - $transaction['Cr']; ?>
                                                                                </span>
                                                                                <strong class="status" data-index="<?= $key ?>"></strong>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                <?php
                                                                } ?>
                                                            </tbody>
                                                        </table>
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

        var total_dollar_amount = 0;
        var total_lkr_amount = 0;
        var finalize_ok = false;
        var transactions = [];

        function get_rate_amount(keyup) {
            var rate = $('#rate').val();
            var dollar_amt = $('#doller_amt').val();
            var lkr_amt = $('#lkr_amt').val();

            if (rate == '' || (dollar_amt == '' && lkr_amt == '')) {
                finalize_ok = false;
                return;
            }

            if (dollar_amt != '' && keyup == 'dollar_keyup') {
                if (isNaN(dollar_amt)) {
                    toastr.error('Please enter a valid amount');
                    return;
                }

                var amount = dollar_amt * rate;
                $('#lkr_amt').val(amount);

                $('#lkr_amt_remain').text(parseFloat(amount).toFixed(2));
                $('#doller_amt_remain').text(parseFloat(dollar_amt).toFixed(2));

                total_dollar_amount = dollar_amt;
                total_lkr_amount = amount;

                finalize_ok = true;

            } else if (lkr_amt != '' && keyup == 'lkr_keyup') {
                if (isNaN(lkr_amt)) {
                    toastr.error('Please enter a valid amount');
                    return;
                }

                var amount = lkr_amt / rate;
                $('#doller_amt').val(amount);

                $('#doller_amt_remain').text(parseFloat(amount).toFixed(2));
                $('#lkr_amt_remain').text(parseFloat(lkr_amt).toFixed(2));

                total_dollar_amount = lkr_amt / rate;
                total_lkr_amount = lkr_amt;

                finalize_ok = true;
            }
        }

        // #rate keyup
        $('#rate').keyup(function() {
            get_rate_amount('rate_keyup');
        });

        // #doller_amt keyup
        $('#doller_amt').keyup(function() {
            get_rate_amount('dollar_keyup');
        });

        // #lkr_amt keyup
        $('#lkr_amt').keyup(function() {
            get_rate_amount('lkr_keyup');
        });

        function calc_row_amounts(row_index, inv, checked, check_btn) {

            var rate = $('#rate').val();
            var due_dollar = parseFloat($('.due_doller[data-index="' + row_index + '"]').text());
            var due_lkr = parseFloat($('.dr[data-index="' + row_index + '"]').text());

            if (checked) {
                if (due_dollar <= total_dollar_amount) {

                    var cr_dollar = due_dollar;
                    var cr_lkr = cr_dollar * rate;

                    $('.cr[data-index="' + row_index + '"]').val(cr_lkr.toFixed(2));
                    $('.cr_doller[data-index="' + row_index + '"]').text(cr_dollar.toFixed(2));

                    total_dollar_amount -= parseFloat(cr_dollar);
                    $('#doller_amt_remain').text(total_dollar_amount.toFixed(2));
                    total_lkr_amount -= parseFloat(cr_lkr);
                    $('#lkr_amt_remain').text(total_lkr_amount.toFixed(2));

                    var profit = cr_lkr - due_lkr;
                    $('.bal[data-index="' + row_index + '"]').text(parseFloat(profit).toFixed(2));
                    if (profit > 0) {
                        $('.status[data-index="' + row_index + '"]').text('Profit');
                        $('.status[data-index="' + row_index + '"]').addClass('text-success');
                    } else {
                        $('.status[data-index="' + row_index + '"]').text('Loss');
                        $('.status[data-index="' + row_index + '"]').addClass('text-danger');
                    }

                    // push to transactions array
                    transactions.push({
                        id: row_index,
                        date: $('.t_date[data-index="' + row_index + '"]').text(),
                        invoice_no: inv,
                        description: $('.t_description[data-index="' + row_index + '"]').text(),
                        dr: due_dollar,
                        cr: cr_dollar,
                        bal: profit,
                        status: profit > 0 ? 'Profit' : 'Loss'
                    });

                    // alert('total_dollar_amount ' + total_dollar_amount);
                    // alert('total_lkr_amount ' + total_lkr_amount);
                } else {
                    $(check_btn).prop('checked', false);
                    toastr.error('Due amount exceeds the total amount available.');
                }

            } else {
                var cr_dollar = $('.cr_doller[data-index="' + row_index + '"]').text();
                var cr_lkr = $('.cr[data-index="' + row_index + '"]').val();

                // alert('cr_dollar ' + cr_dollar);
                // alert('cr_lkr ' + cr_lkr);

                if ((!isNaN(cr_dollar) && cr_dollar != '') && (!isNaN(cr_lkr) && cr_lkr != '')) {

                    $('.cr[data-index="' + row_index + '"]').val('');
                    $('.cr_doller[data-index="' + row_index + '"]').text('');

                    total_dollar_amount += parseFloat(cr_dollar);
                    $('#doller_amt_remain').text(total_dollar_amount.toFixed(2));
                    total_lkr_amount += parseFloat(cr_lkr);
                    $('#lkr_amt_remain').text(total_lkr_amount.toFixed(2));

                    $('.bal[data-index="' + row_index + '"]').text(due_lkr);
                    $('.status[data-index="' + row_index + '"]').text('');
                    $('.status[data-index="' + row_index + '"]').removeClass('text-success');

                    // remove from transactions array
                    transactions = transactions.filter(function(item) {
                        return item.id != row_index;
                    });

                } else {
                    if (cr_dollar != '' && cr_lkr != '') {
                        toastr.error('An error occured.. Invalid amounts.');
                    }
                }

                // alert('total_dollar_amount ' + total_dollar_amount);
                // alert('total_lkr_amount ' + total_lkr_amount);
            }
        }

        // .check_no change
        $('.check_no').change(function() {
            var index = $(this).data('index');
            var inv = $(this).data('inv');
            var checked = $(this).prop('checked');
            calc_row_amounts(index, inv, checked, this);
        });

        // #form_submit submit
        $('#form_submit').submit(function(e) {
            e.preventDefault();
            var customer = $('#customer_lcode').val();
            var amount = $('#amount').val();
            var date = $('#payment_date').val();
            var payment_method = $('#payment_method').val();
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

            // get transactions
            if (transactions.length == 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select at least one transaction',
                });
                return;
            }

            $.ajax({
                url: '<?php echo base_url('accounts/debtor_payment/complete_payment') ?>',
                type: 'POST',
                data: {
                    customer: customer,
                    amount: amount,
                    date: date,
                    payment_method: payment_method,
                    description: ref,
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
    </script>
</body>

</html>