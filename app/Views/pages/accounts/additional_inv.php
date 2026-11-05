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
                                        <h4 class="p-title">Debtor Invoice Additional INV</h4>

                                    </div>

                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-8">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                    <fieldset>
                                                        <div class="row">
                                                            <div class="mb-3 col-md-6">
                                                                <label class="form-label" for="tour_id">Tour ID <font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="tour_id" name="tour_id" value="<?php echo $consignee['tour_id']; ?>" required readonly />
                                                                <!-- <input type="hidden" name="consignee" id="consignee" value="<?php //echo $consignee['consignee']; 
                                                                                                                                    ?>"> -->
                                                            </div>
                                                            <div class="mb-3 col-md-6">
                                                                <label class="form-label" for="tour_id">Manual Tour ID <font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="manu_tour_id" name="manu_tour_id" value="<?php echo $consignee['manual_id']; ?>" required />
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="mb-3 col-md-8">
                                                                <label class="form-label" for="tour_id">Tour Name <font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="consignee" name="consignee" value="<?php echo $consignee['consignee']; ?>" required readonly />
                                                            </div>
                                                            <div class="mb-3 col-md-4">
                                                                <label class="form-label" for="customer">Agent<font color="#FF0000"><strong>*</strong></font></label>
                                                                <select name="customer" id="customer" class="js-example-basic-single form-control" style="width: 100%;" required disabled>
                                                                    <option value="">Select a Supplier...</option>
                                                                    <?php
                                                                    foreach ($customer as $id) {
                                                                        $txt = ($consignee['agent'] == $id['Sup_Acc_No']) ? 'selected' : '';
                                                                        echo '<option value="' . $id['Sup_Acc_No'] . '" ' . $txt . '>' . $id['Name'] . '</option>';
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3" style="display: none;">
                                                            <label class="form-label" for="pay_type">Income Type<font color="#FF0000"><strong>*</strong></font></label>
                                                            <select name="pay_type" id="pay_type" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                <option value="">Select a Type...</option>
                                                                <?php
                                                                foreach ($methods as $id) {

                                                                    $txt = ($id['L_Code'] == '5-7-7-10388') ? 'selected' : '';

                                                                    echo '<option value="' . $id['L_Code']  . '" ' . $txt . '>' . $id['PName'] . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <!--     <div class="mb-3">
                                                        <label class="form-label" for="inv">Invoice No <font color="#FF0000"><strong>*</strong></font></label>
                                                        <input class="form-control" type="text" id="inv" name="inv" placeholder="" required />
                                                    </div> -->
                                                        <div class="mb-3">
                                                            <label class="form-label" for="date">Date <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="date" name="date" value="<?php echo date('Y-m-d') ?>" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" required />
                                                        </div>
                                                        <div class="mb-3" style="display: none;">
                                                            <label class="form-label" for="description">Description<font color="#FF0000"><strong>*</strong></font></label>
                                                            <textarea name="description" id="description" class="form-control"></textarea>
                                                        </div>
                                                        <div class="mb-3" hidden>
                                                            <label class="form-label" for="tr_lkr">Transport Fee (LKR) <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="tr_lkr" name="tr_lkr" value="<?php echo $consignee['total_transport_cost']; ?>" placeholder="" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="drate">Doller Rate <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="drate" name="drate" placeholder="" value="275" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="description">Details<font color="#FF0000"><strong>*</strong></font></label>
                                                            <table class="table table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Description</th>
                                                                        <th>Amount ($)</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td>
                                                                            <textarea name="description1" id="description1" class="form-control" required placeholder="Enter Description..."></textarea>
                                                                        </td>
                                                                        <td>
                                                                            <input type="text" class="form-control" id="amount1" name="amount1" value="0" placeholder="Enter first amount..." required>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <textarea name="description2" id="description2" class="form-control" required placeholder="Enter Description..."></textarea>
                                                                        </td>
                                                                        <td>
                                                                            <input type="text" class="form-control" id="amount2" name="amount2" value="0" placeholder="Enter second amount..." required>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <textarea name="description3" id="description3" class="form-control" required placeholder="Enter Description..."></textarea>
                                                                        </td>
                                                                        <td>
                                                                            <input type="text" class="form-control" id="amount3" name="amount3" value="0" placeholder="Enter third amount..." required>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <div class="mb-3" style="display: none;">
                                                            <label class="form-label" for="room_cost">Room Cost <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="room_cost" value="0" name="room_cost" placeholder="" required />
                                                        </div>
                                                        <!--       <div class="mb-3">
                                                    <label class="form-label" for="transport_cost">Transport Cost <font color="#FF0000"><strong>*</strong></font></label>
                                                    <input class="form-control" type="text" id="transport_cost" name="transport_cost" value="<?php //echo $consignee['total_transport_cost']; 
                                                                                                                                                ?>" placeholder=""  required/>
                                                </div> -->
                                                        <!--      <div class="mb-3">
                                                        <label class="form-label" for="hotel_cost">Transport Cost <font color="#FF0000"><strong>*</strong></font></label>
                                                        <input class="form-control" type="text" id="hotel_cost" name="hotel_cost" value="<?php //echo $consignee['total_transport_cost']; 
                                                                                                                                            ?>" placeholder=""  required/>
                                                    </div> -->
                                                        <!--      <div class="mb-3">
                                                        <label class="form-label" for="advance">Advance Payment Balance</label>
                                                        <input class="form-control" type="text" id="advance" name="advance" placeholder="" readonly />
                                                    </div> -->
                                                        <div class="mb-3">
                                                            <label class="form-label" for="gross_amount">Gross Amount ($) <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="gross_amount" name="gross_amount" placeholder="" required readonly />
                                                        </div>
                                                        <input type="hidden" id="margin" name="margin" value="<?php echo $customer_margin['margin']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label" for="amount">Net Amount ($) <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="amount" name="amount" placeholder="" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="credit_balance">Net Amount (LKR)<font color="#FF0000"><strong>*</strong></font></label>
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


        $("#drate").keyup(function() {
            // var d_rate = parseFloat($('#drate').val());
            // var dolleramount = parseFloat($(this).val());
            // // var transport = parseFloat($('#transport_cost').val());
            // var activity_amount = parseFloat($('#amount1').val());
            // var hotel_amount = parseFloat($('#amount2').val());
            // var tr_lkr = parseFloat($('#tr_lkr').val()); //transport LKR
            // var margin = parseFloat($('#margin').val()); //margin

            // if (margin == 0) {
            //     Swal.fire({
            //         icon: 'error',
            //         title: 'Error',
            //         text: 'Margin is not set for this Tour Agent',
            //     });
            //     return false;
            // }

            // var dollar_transport = Math.ceil(tr_lkr / d_rate); //transport fee dollar (rounded up)
            // $('#transport_cost').val(dollar_transport);

            // var final_amount = dollar_transport + activity_amount + hotel_amount;
            // var final_amoun = final_amount + (final_amount * margin / 100);
            // $('#amount').val(final_amoun);
            // $('#gross_amount').val(final_amount);

            // var result = d_rate * final_amount;
            // // var result = result + (result * margin / 100);
            // $('#credit_balance').val(result);
            calculation();
        });

        $("#amount").keyup(function() {
            calculation(true);
        });

        $("#amount1, #amount2, #amount3").keyup(function() {
            calculation();
        });

        // Calculation 
        function calculation1(amt = false) {
            var d_rate = parseFloat($('#drate').val());
            var dolleramount = parseFloat($('#drate').val());
            // var transport = parseFloat($('#transport_cost').val());
            var activity_amount = parseFloat($('#amount1').val());
            var hotel_amount = parseFloat($('#amount2').val());
            var tr_lkr = parseFloat($('#tr_lkr').val()); //transport LKR
            var margin = parseFloat($('#margin').val()); //margin
            var final_amount = parseFloat($('#amount').val());

            if (margin == 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Margin is not set for this Tour Agent',
                });
                return false;
            }

            var dollar_transport = Math.ceil(tr_lkr / d_rate); //transport fee dollar (rounded up)
            $('#transport_cost').val(dollar_transport);

            var gross_amount = dollar_transport + activity_amount + hotel_amount;
            $('#gross_amount').val(gross_amount);


            if (!amt) {
                final_amount = gross_amount + (gross_amount * margin / 100);
                // final_amount = parseFloat($('#amount').val());
                $('#amount').val(final_amount);
            }

            var result = d_rate * final_amount;
            $('#credit_balance').val(result);

        }

        function calculation(amt = false) {
            var d_rate = parseFloat($('#drate').val());
            var dolleramount = parseFloat($('#drate').val());
            var amount1 = parseFloat($('#amount1').val());
            var amount2 = parseFloat($('#amount2').val());
            var amount3 = parseFloat($('#amount3').val());
            var margin = parseFloat($('#margin').val());
            var final_amount = parseFloat($('#amount').val());

            console.log(d_rate + ' ' + dolleramount + ' ' + amount1 + ' ' + amount2 + ' ' + amount3 + ' ' + margin + ' ' + final_amount);

            if (margin == 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Margin is not set for this Tour Agent',
                });
                return false;
            }

    
            var gross_amount = amount1 + amount2 + amount3;
            $('#gross_amount').val(gross_amount);

            if (!amt) {
                final_amount = gross_amount + (gross_amount * margin / 100);
                // final_amount = parseFloat($('#amount').val());
                $('#amount').val(final_amount);
            }

            var result = d_rate * final_amount;
            $('#credit_balance').val(result);

        }

        $(document).ready(function() {

            calculation();

            // Submit form data via Ajax
            $('#form_submit').submit(function(e) {
                e.preventDefault();

                $.ajax({
                    url: "<?php echo base_url() ?>accounts/debtor_invoice_new/complete_payment",
                    type: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    cache: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {


                            window.open("<?php echo base_url(); ?>accounts/debtor_invoice_new/print?tour=" + response.tour, '_blank');
                            window.location.href = "<?php echo base_url(); ?>tour/tour_details";

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