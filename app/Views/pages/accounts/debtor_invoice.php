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
                                    <div class="col-md-4 d-flex">
                                        <h4 class="p-title">Debtor Invoice</h4>

                                    </div>

                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-8">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                    <fieldset>


                                                        <div class="mb-3">
                                                            <label class="form-label" for="tour_id">Manual Tour ID <font color="#FF0000"><strong>*</strong></font></label>
                                                            <!-- <input class="form-control" type="text" id="tour_id" name="tour_id" placeholder="" required /> -->
                                                            <select name="tour_id" id="tour_id" class="js-example-basic-single form-control" style="width: 100%;" required>

                                                                <option value="">Select a Manual Tour ID...</option>
                                                                <?php
                                                                foreach ($consignee as $tour) {
                                                                    echo '<option value="' . $tour['tour_id'] . '" >' . ($tour['manual_id'] ?? $tour['tour_id']) . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="customer">Agent<font color="#FF0000"><strong>*</strong></font></label>
                                                            <!-- <input type="text" name="supplier" id="supplier" class="js-example-basic-single form-control" style="width: 100%;" required> -->
                                                            <select name="customer" id="customer" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                <!-- <select name="customer" id="customer" class="js-example-basic-single form-control" style="width: 100%;" required onchange="advance_payment(this.value)"> -->
                                                                <option value="">Select a Supplier...</option>
                                                                <?php
                                                                foreach ($customer as $id) {
                                                                    echo '<option value="' . $id['Sup_Acc_No'] . '" >' . $id['Name'] . '</option>';
                                                                }
                                                                ?>
                                                            </select>

                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="pay_type">Income Type<font color="#FF0000"><strong>*</strong></font></label>
                                                            <select name="pay_type" id="pay_type" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                <option value="">Select a Type...</option>
                                                                <?php
                                                                foreach ($methods as $id) {
                                                                    echo '<option value="' . $id['L_Code'] . '" >' . $id['PName'] . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="inv">Invoice No <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="inv" name="inv" placeholder="" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="date">Date <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="date" name="date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="description">Description<font color="#FF0000"><strong>*</strong></font></label>
                                                            <textarea name="description" id="description" class="form-control" required></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="advance">Advance Payment Balance</label>
                                                            <input class="form-control" type="text" id="advance" name="advance" placeholder="" readonly />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="drate">Doller Rate <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="drate" name="drate" placeholder="" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="amount">Doller Amount <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="amount" name="amount" placeholder="" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="credit_balance">LKR Credit balance <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="credit_balance" name="credit_balance" placeholder="" required readonly />
                                                        </div>
                                                        <br>
                                                        <button type="submit" class="btn btn-success w-100px me-5px">Save</button>
                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12 d-none" id="added_debtor_invoice">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>Consignee</th>
                                                                <th>Tour ID</th>
                                                                <th>Income Type</th>
                                                                <th>Invoice No</th>
                                                                <th>Date</th>
                                                                <th>Description</th>
                                                                <th>Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="debtor_invoice_tbl">

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
    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        var added_invoice = [];

        function populateAddedInvoice() {
            $('#debtor_invoice_tbl').html('');
            console.log(added_invoice);
            $.each(added_invoice, function(index, value) {
                $('#debtor_invoice_tbl').append(
                    '<tr>' +
                    '<td>' + value.customer + '</td>' +
                    '<td>' + value.tour + '</td>' +
                    '<td>' + value.payment_type + '</td>' +
                    '<td>' + value.inv + '</td>' +
                    '<td>' + value.date + '</td>' +
                    '<td>' + value.des + '</td>' +
                    '<td>' + value.amount + '</td>' +
                    '</tr>'
                );
            });
            if (added_invoice.length > 0) {
                $('#added_debtor_invoice').removeClass('d-none');
            } else {
                $('#added_debtor_invoice').addClass('d-none');
            }
        }

        // function advance_payment(cus_id) {

        //     $.ajax({
        //         url: "<?php echo base_url() ?>accounts/debtor_invoice/get_advance_payment",
        //         type: 'POST',
        //         data: {
        //             cus: cus_id
        //         },
        //         dataType: 'json',
        //         success: function(response) {
        //             if (response.status == 200) {

        //                 var transactions = response.data.transactions;
        //                 var tr = '';
        //                 var total_dr = 0;
        //                 var total_cr = 0;
        //                 $.each(transactions, function(index, item) {

        //                     total_dr += parseFloat(item.Dr);
        //                     total_cr += parseFloat(item.Cr);
        //                 });
        //                 var balance = total_cr - total_dr;
        //                 $("#advance").val(balance);
        //             } else if (response.status == 400) {
        //                 Swal.fire({
        //                     icon: 'error',
        //                     title: 'Error',
        //                     text: response.message,
        //                 });
        //             } else {
        //                 Swal.fire({
        //                     icon: 'error',
        //                     title: 'Error',
        //                     text: response.message,
        //                 });
        //             }
        //         },
        //         error: function(jqXHR, textStatus, errorThrown) {
        //             Swal.fire({
        //                 icon: 'error',
        //                 title: 'Oops...',
        //                 text: 'Something went wrong! Please try again later.',
        //             });

        //         }
        //     });
        // }

        // $("#amount").keyup(function() {
        //     var amount = $(this).val();
        //     var advance_pay = $('#advance').val();

        //     var amt = parseFloat(amount);
        //     var adv = parseFloat(advance_pay);

        //     var balance = amt - adv;
        //     var bal = parseFloat(balance);
        //     $('#credit_balance').val(bal);

        // });

        $("#amount").keyup(function() {
            var d_rate = parseFloat($('#drate').val());
            var dolleramount = parseFloat($(this).val());
            var result = d_rate * dolleramount;
            $('#credit_balance').val(result);
        });

        $(document).ready(function() {
            // Submit form data via Ajax
            $('#form_submit').submit(function(e) {
                e.preventDefault();

                $.ajax({
                    url: "<?php echo base_url() ?>accounts/debtor_invoice/complete_payment",
                    type: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    cache: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                            }).then((result) => {
                                added_invoice.push({
                                    'customer': $('#customer').find('option:selected').text(),
                                    'tour': $('#tour_id').val(),
                                    'payment_type': $('#pay_type').find('option:selected').text(),
                                    'inv': $('#inv').val(),
                                    'date': $('#date').val(),
                                    'des': $('#description').val(),
                                    'amount': $('#amount').val()
                                });
                                populateAddedInvoice();
                                // trigger chass_no change
                                $('#customer').change();
                                $('#tour_id').val("");
                                $('#pay_type').val("").change();
                                $('#inv').val("");
                                $('#date').val("");
                                $('#des').val("");
                                $('#amount').val("");
                            });
                        } else if (response.status == 400) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong! Please try again later.',
                        });

                    }
                });
            });

        });


        $('#tour_id').on('change', function() {
            var customer_id = $(this).val();

            if (customer_id != '') {
                $.ajax({
                    url: "<?php echo base_url() ?>accounts/debtor_invoice/get?id=" + customer_id,
                    type: "POST",
                    dataType: "json",
                    success: function(response) {
                        $('#customer').empty(); // Clear existing options

                        if (response) {
                            // Append the single supplier option
                            $('#customer').append('<option value="' + response.Sup_Acc_No + '">' + response.Name + '</option>');

                            // Set the supplier as selected
                            $('#customer').val(response.Sup_Acc_No);
                        } else {
                            $('#customer').append('<option value="">No suppliers available</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error);
                        // Handle the error appropriately
                    }
                });
            }
        });
    </script>
</body>

</html>