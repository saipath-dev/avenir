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
                                        <h4 class="p-title">Credit Note</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-4">
                                                <form action="<?php echo base_url('accounts/credit_note') ?>" method="GET" accept-charset="utf-8">
                                                    <fieldset>
                                                        <div class="row clearfix mb-3">
                                                            <div class="col-sm-12">
                                                                <div class="form-group">
                                                                    <label class="form-label">Agent<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <select name="customer" id="customer" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                        <option value="">Select a Agent...</option>
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
                                                        <table class="table table-striped" id="transactions_table">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 15%;" scope="col">Date</th>
                                                                    <th style="width: 15%;" scope="col">Tour Id</th>
                                                                    <th style="width: 15%;" scope="col">Tour Name</th>
                                                                    <th style="width: 15%;" scope="col">Invoice No</th>
                                                                    <th scope="col" style="width: 25%;">Description</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Due</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Amount</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Balance</th>
                                                                    <th class="hidden"></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                foreach ($transactions as $key => $transaction) {

                                                                ?>
                                                                    <tr>
                                                                        <th><?= $transaction['Date']; ?></th>
                                                                        <td><?= $transaction['manual_id']; ?></td>
                                                                        <td><?= $transaction['consignee']; ?></td>
                                                                        <td><?= $transaction['Invoice_Num']; ?></td>
                                                                        <td><?= $transaction['Discription']; ?></td>
                                                                        <td class="text-end dr"><?= $transaction['Cr'] > 0 ? $transaction['Cr'] : ''; ?></td>
                                                                        <td class="text-end cr_rec"><?= $transaction['Dr'] > 0 ? $transaction['Dr'] : '<input type="text" class="form-control cr" name="cr" id="cr" data-index="' . $key . '">'; ?>
                                                                        </td>
                                                                        <td class="text-end"><span class="bal"> <?= $transaction['Cr'] - $transaction['Dr']; ?></span>
                                                                            <!-- <strong><span class="status" data-index="<? //php $key 
                                                                                                                            ?>"></span></strong> -->
                                                                            <!-- <strong><span class="status" data-index="<?php //$key 
                                                                                                                            ?>"></span></strong> -->
                                                                        </td>
                                                                        <td class="hidden"><input type="hidden" name="tour_id" id="tour_id" value="<?= $transaction['index_no']; ?>"></td>
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
                                                            <button type="button" class="btn btn-success payment_finalize" id="payment_finalize">Finalize</button>

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
        // #form_submit submit
        $(document).on('click', '#payment_finalize', function(e) {
            e.preventDefault();
            var supplier = $('#supplier').val();

            // get transactions_table values and send to the server
            var transactions = [];
            $('#transactions_table tbody tr').each(function() {
                var date = $(this).find('th').text();
                var tour_id_manu = $(this).find('td').eq(0).text();
                var tour_name = $(this).find('td').eq(1).text();
                var invoice_no = $(this).find('td').eq(2).text();
                var description = $(this).find('td').eq(3).text();
                var dr = $(this).find('.dr').text();
                var cr = $(this).find('.cr_rec').find('input.cr').val() || $(this).find('.cr_rec').text();
                var bal = $(this).find('.bal').text();
                var status = $(this).find('.status').text();
                var tour_id = $(this).find('input#tour_id').val();

                // alert(cr);

                transactions.push({
                    date: date,
                    tour_id_manu: tour_id_manu,
                    tour_name: tour_name,
                    invoice_no: invoice_no,
                    description: description,
                    dr: dr,
                    cr: cr,
                    bal: bal,
                    status: status,
                    tour_id: tour_id
                });

            });

            $.ajax({
                url: '<?php echo base_url('accounts/credit_note/complete_payment') ?>',
                type: 'POST',
                data: {
                    supplier: supplier,
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
            window.location.href = '<?php echo base_url('accounts/credit_note') ?>';
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


            if (balance < 0) {
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
    </script>
</body>




</html>