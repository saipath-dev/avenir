<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <style type="text/css">
            .pull-right {
                float: right;
            }

            .typeahead,
            .typeahead2,
            .tt-query,
            .tt-hint {
                display: block;
                width: 230%;
                padding: 0.4375rem 0.75rem;
                font-size: 13px;
                font-weight: 500;
                line-height: 1.5;
                text-transform: uppercase;
                /*margin-bottom: 0.9375rem !important;*/
            }

            .tt-menu {
                width: 600px;
                /*font-weight: bold;*/
                line-height: 2;
                font-size: 13px;
                text-transform: uppercase;


                /*      margin: 12px 0;

      padding: 0.4375rem 0.15rem;
      font-size: .75rem;
      font-weight: 500;
      
      color: #000;*/
                /*color: var(--app-component-color);*/
                background-color: #fff;
                background-clip: padding-box;
                border: 1px solid #d3d3d3;
                appearance: none;
                border-radius: 4px;
                transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
            }

            .tt-suggestion {
                /*padding: 3px 20px;*/
                font-size: 13px;
                width: 100 text-transform: uppercase;
                margin-left: 0px;
            }

            .tt-suggestion:hover {
                cursor: pointer;
                color: #fff;
                background-color: #5897fb;
                width: 600px;
                margin-left: 0px;

                line-height: 2;
                text-transform: uppercase;

            }

            .tt-suggestion.tt-cursor {
                color: #fff;
                background-color: #5897fb;
                /*width: 100%;*/
                width: 600px;
                line-height: 2;
                margin-left: 0px;
                text-transform: uppercase;

            }

            /*  .tt-suggestion p {
      margin: 0;
      }*/

            .gist {
                font-size: 14px;
            }
        </style>
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
                                        <h4 class="p-title">Debit Note</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-4">
                                                <form action="<?php echo base_url('accounts/debit_note') ?>" method="GET" accept-charset="utf-8">
                                                    <fieldset>
                                                        <div class="row clearfix mb-3">
                                                            <div class="col-sm-12">
                                                                <div class="form-group">
                                                                    <label class="form-label">Supplier<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <select name="supplier" id="supplier" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                        <option value="">Select a Supplier...</option>
                                                                        <?php
                                                                        $selected_supplier = isset($supplier) ? $supplier : 0;
                                                                        $selected_action_name = '';
                                                                        foreach ($suppliers as $supplier) {
                                                                            $selected = '';
                                                                            if ($selected_supplier == $supplier['L_Code']) {
                                                                                $selected = 'selected';
                                                                                $selected_action_name = $supplier['PName'];
                                                                            }
                                                                            echo '<option value="' . $supplier['L_Code'] . '" ' . $selected . '>' . $supplier['PName'] . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>


                                                        <!-- <div class="row clearfix mb-3">
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">From</label>
                                                                    <input class="form-control" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" class="form-control" id="from" name="from" value="<?php //echo isset($from) ? $from : ""; 
                                                                                                                                                                                                                            ?>" />
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">To</label>
                                                                    <input class="form-control" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" class="form-control" id="to" name="to" value="<?php //echo isset($to) ? $to : ""; 
                                                                                                                                                                                                                        ?>" />
                                                                </div>
                                                            </div>
                                                        </div> -->

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
                                                                    <th style="width: 15%;" scope="col">Tour ID</th>
                                                                    <th style="width: 15%;" scope="col">Invoice No</th>
                                                                    <th scope="col" style="width: 25%;">Description</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">Due</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col">DN Amount</th>
                                                                    <th class="text-end" style="width: 15%;" scope="col"></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                foreach ($transactions as $key => $transaction) {

                                                                ?>
                                                                    <tr>
                                                                        <th><?= $transaction['date']; ?></th>
                                                                        <td><?= $transaction['manual_id']; ?></td>
                                                                        <td><?= $transaction['Invoice_Num'] ?></td>
                                                                        <td><?= $transaction['Discription']; ?></td>
                                                                        <td class="text-end dr"><?= $transaction['cr'] - $transaction['dr'] > 0 ? $transaction['cr'] - $transaction['dr'] : ''; ?></td>
                                                                        <td class="text-end cr_rec"><?= $transaction['dr'] - $transaction['cr'] > 0 ? $transaction['dr'] - $transaction['cr'] : '<input type="text" class="form-control cr" name="cr" id="cr" data-index="' . $key . '">'; ?>
                                                                        </td>
                                                                        <!-- <td class="text-end"><span class="bal"> <? //= $transaction['cr'] - $transaction['dr']; 
                                                                                                                        ?></span>
                                                                            <strong><span class="status" data-index="<?php //$key 
                                                                                                                        ?>"></span></strong>
                                                                        </td> -->
                                                                        <td class="text-end" style="display: none;"><?= $transaction['index_no']; ?></td>
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
                                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentDetailsModal">Finalize</button>

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

        <div class="modal fade" id="paymentDetailsModal" tabindex="-1" role="dialog" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentDetailsModalLabel">Payment Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="form_submit" method="post" action="<?php echo base_url('accounts/debit_note/complete_payment') ?>">
                            <input type="hidden" name="supplier" id="supplier" value="<?= $selected_action_name; ?>">
                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label pull-right" for="item_search">Select Account : </label>
                                    </div>
                                    <div class="col-md-7 ">
                                        <input type="text" name="item_search" class="form-control  item_search typeahead txt_nav" id="item_search" placeholder="" required />
                                        <input class="form-control" type="hidden" id="item_code" name="item_code" /> <br>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="hidden" name="acc" class="form-control" id="acc">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="hidden" name="acc_name" class="form-control" id="acc_name">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <button type="button" class="btn btn-secondary me-2 reset">Reset</button>
                                <button type="button" class="btn btn-success" id="payment_finalize">Finalize Payment</button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>
    <script src="https://twitter.github.io/typeahead.js/js/handlebars.js"></script>
    <script src="https://twitter.github.io/typeahead.js/releases/latest/typeahead.bundle.js"></script>


    <script>
        $(document).ready(function() {
            // $('#v_type').focus();
            // var type = $('#v_type').val();
            // var vtp = $('#payment_type').val();

            var load_data = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: '<?php echo base_url(); ?>accounts/debit_note/fetch',
                remote: {
                    url: '<?php echo base_url(); ?>accounts/debit_note/fetch/%QUERY',
                    wildcard: '%QUERY',
                    transform: function(response) {
                        return response;
                    },
                    replace: function(url, uriEncodedQuery) {

                        return url.replace('%QUERY', encodeURIComponent(uriEncodedQuery));
                    }
                }
            });


            $('.typeahead').typeahead(null, {

                    name: 'load_data',
                    display: 'PName',
                    source: load_data,
                    limit: 10,
                    templates: {
                        suggestion: Handlebars.compile('<div class="row"><div class="col-md-5" style="font-size:14px;"  >{{PName}}</div><div class="col-md-7" >{{BName}} → {{TName}} → {{HName}}</div></div>')
                    },
                    highlight: true
                })
                .on("typeahead:selected", function(e, datum) {

                    $("form [name=acc_name]").val(datum.PName);
                    $("form [name=acc]").val(datum.L_Code);

                });

        });

        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });
        // #form_submit submit
        $(document).on('click', '#payment_finalize', function(e) {
            e.preventDefault();
            var supplier = $('#supplier').val();
            var pay_acc = $('#acc').val();

            if (supplier == '' || pay_acc == '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select a supplier and payment account',
                });
                return;
            }

            // get transactions_table values and send to the server
            var transactions = [];
            $('#transactions_table tbody tr').each(function() {
                var date = $(this).find('th').text();
                var tour_id_manu = $(this).find('td').eq(0).text();
                var invoice_no = $(this).find('td').eq(1).text();
                var description = $(this).find('td').eq(2).text();
                var due = $(this).find('.dr').text();
                var payment_received = $(this).find('.cr').val() ? $(this).find('.cr').val() : 0;
                var tour_id = $(this).find('td').eq(5).text();

                transactions.push({
                    date: date,
                    tour_id_manu: tour_id_manu,
                    invoice_no: invoice_no,
                    description: description,
                    due: due,
                    payment_received: payment_received,
                    tour_id: tour_id
                });
            });

            $.ajax({
                url: '<?php echo base_url('accounts/debit_note/complete_payment') ?>',
                type: 'POST',
                data: {
                    supplier: supplier,
                    transactions: transactions,
                    pay_acc: pay_acc
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
            window.location.href = '<?php echo base_url('accounts/debit_note') ?>';
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