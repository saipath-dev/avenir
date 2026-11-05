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
                                        <h4 class="p-title">Advance Payment</h4>

                                    </div>

                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-5">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                    <fieldset>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="customer_name">Customer<font color="#FF0000"><strong>*</strong></font></label>
                                                            <!-- <input type="text" name="supplier" id="supplier" class="js-example-basic-single form-control" style="width: 100%;" required> -->
                                                            <select name="customer_name" id="customer_name" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                <option value="">Select a Supplier...</option>
                                                                <?php
                                                                foreach ($customers as $customer) {
                                                                    $selected = '';
                                                                    echo '<option value="' . $customer['Sup_Acc_No'] . '"' . $selected . '>' . $customer['Name'] . '</option>';
                                                                }
                                                                ?>
                                                            </select>

                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="bank_account">Payment Type<font color="#FF0000"><strong>*</strong></font></label>
                                                            <select name="bank_account" id="bank_account" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                <option value="">Select a Type...</option>
                                                                <?php
                                                                foreach ($methods as $method) {
                                                                    echo '<option value="' . $method['L_Code'] . '">' . $method['PName'] . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="pay_date">Date <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="pay_date" name="pay_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" required />
                                                        </div>
                                                        <!-- <div class="mb-3">
                                                            <label class="form-label" for="description">Description<font color="#FF0000"><strong>*</strong></font></label>
                                                            <textarea name="description" id="description" class="form-control" required></textarea>
                                                        </div> -->
                                                        <div class="mb-3">
                                                            <label class="form-label" for="cash">Amount <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="cash" name="cash" placeholder="" required />
                                                        </div>
                                                        <br>
                                                        <button type="submit" class="btn btn-success w-100px me-5px">Save</button>
                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-7">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table js-basic-example table-hover dataTable table-custom align-middle" id="transactions_table">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Date</th>
                                                                <th>Dr</th>
                                                                <th>Cr</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="p_history">

                                                        </tbody>
                                                    </table>
                                                    <hr>
                                                    <div class="d-flex">
                                                        <div class="me-auto">
                                                            <h5 class="fw-light">Balance</h5>
                                                        </div>
                                                        <div>
                                                            <h5 class="fw-medium text-success" id="balance">0.00</h5>
                                                        </div>
                                                    </div>
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

        $(document).ready(function() {
            setTimeout(function() {
                // get customer from the url
                var url_string = window.location.href;
                var url = new URL(url_string);
                var customer = url.searchParams.get("customer");
                if (customer != null) {
                    $('#customer_name').val(customer).change();
                }
            }, 1000);
        });

        // #customer_name on change
        $('#customer_name').on('change', function() {
            var customer_id = $(this).val();
            if (customer_id != '') {
                $.ajax({
                    url: "<?php echo base_url() ?>accounts/advance_payment/get?id=" + customer_id,
                    type: "POST",
                    dataType: "json",
                    success: function(response) {
                        if (response.status == 200) {
                            var transactions = response.data.transactions;
                            $("#p_history").html('');
                            var tr = '';
                            var total_dr = 0;
                            var total_cr = 0;
                            $.each(transactions, function(index, item) {
                                tr += "<tr>";
                                tr += "<td>" + (index + 1) + "</td>";
                                tr += "<td>" + (item.Date || '') + "</td>";
                                tr += "<td class='text-end'>" + (item.Dr || '0.00') + "</td>";
                                tr += "<td class='text-end'>" + (item.Cr || '0.00') + "</td>";
                                tr += "</tr>";
                                total_dr += parseFloat(item.Dr);
                                total_cr += parseFloat(item.Cr);
                            });
                            $("#p_history").html(tr);
                            var balance = total_cr - total_dr;
                            $("#balance").html(parseFloat(balance).toFixed(2));
                        } else {
                            $("#p_history").html('');
                            $("#balance").html('0.00');
                            Swal.fire({
                                title: 'Warning',
                                text: response.message,
                                icon: 'warning',
                                confirmButtonText: 'Ok'
                            });
                        }
                    }
                });
            }
        });

        /***************************show Amount***************************/

        $("#form_submit").on('submit', function(e) {
            e.preventDefault();

            var customer = $("#customer_name").val();
            var cash = $("#cash").val();
            var payment_date = $("#pay_date").val();
            var bank_account = $("#bank_account").val();

            if ((cash != '') && (payment_date != '') && (bank_account != '') && (customer != '')) {
                $.ajax({
                    url: "<?php echo base_url(); ?>accounts/advance_payment/add",
                    type: "POST",
                    data: {
                        customer: customer,
                        cash: cash,
                        payment_date: payment_date,
                        bank_account: bank_account
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
                                    window.location.href = "<?php echo base_url(); ?>accounts/advance_payment?customer=" + customer;
                                }
                            });

                        } else if (response.status == 400) {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'Ok'
                            });
                        } else {
                            Swal.fire({
                                title: '',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: false
                            });
                        }
                    }
                });
            }

        });

        $('#transactions_table').DataTable({
            "paging": false,
            "info": false,
            "searching": true,
            "ordering": true,
            "scrollY": "340px",
            "scrollCollapse": true,
            "scrollX": true,
            "autoWidth": false,
            "responsive": true,
            "dom": 'Bfrtip',
            "buttons": []
        });

        /*************************Calculate the Amount*************************/
        // function calculateAmount(inputId, inputId1, cashId) {
        //     var input1 = $("#" + inputId).val();
        //     var input2 = $("#" + inputId1).val();


        //     if (input1 && input2 && input1 != '' && input2 != '') {
        //         var r_amt = input1 * input2;
        //         $("#" + cashId).val(r_amt);
        //     }
        // }

        // $("#ex_rate").on('keyup input', function() {
        //     calculateAmount("ex_rate", "d_amt", "cash");
        // });

        // $("#d_amt").on('keyup input', function() {
        //     calculateAmount("d_amt", "ex_rate", "cash");
        // });
    </script>
</body>

</html>