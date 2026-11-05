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
                                        <h4 class="p-title">New Creditor Invoice</h4>

                                    </div>

                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-3">
                                            <div class="body">
                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                    <fieldset>
                                                        <h5 class="mt-2 text-primary">Invoice Details</h5>
                                                        <div class="row">
                                                            <div class="mb-3 col-md-3">
                                                                <label class="form-label" for="tour_id">Tour ID <font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="tour_id" name="tour_id" placeholder="" required readonly/>
                                                                <input type="hidden" id="tour_id_hidden" name="tour_id_hidden" value="<?php echo $tour_id ?>" readonly/>
                                                            </div>
                                                            <div class="mb-3 col-md-3">
                                                                <label class="form-label" for="tour_id">Tour Manual ID <font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="tour_manu_id" name="tour_manu_id" placeholder="" required readonly/>
                                                            </div>
                                                            <div class="mb-3 col-md-6">
                                                                <label class="form-label" for="description">Tour Name <font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="description" name="description" placeholder="" required readonly/>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="mb-3 col-md-6">
                                                                <label class="form-label" for="agent">Select Agent<font color="#FF0000"><strong>*</strong></font></label>
                                                                <select name="agent" id="agent" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                    <option value="">Select a Agent...</option>
                                                                    <?php
                                                                    foreach ($agent as $id) {
                                                                        echo '<option value="' . $id['Sup_Acc_No'] . '" >' . $id['Name'] . '</option>';
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3 col-md-6">
                                                                <label class="form-label">Bank Account<font color="#FF0000"><strong>*</strong></font></label>

                                                                <select name="bank_acc" id="bank_acc" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                    <option value="">Select a Bank...</option>
                                                                    <?php
                                                                    foreach ($bank_acc as $bank) {
                                                                        echo "<option value='" . $bank['L_Code'] . "'>" . $bank['PName'] . "</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <h5 class="mt-2">Expenses Details</h5>
                                                        <div class="row">
                                                            <div class="mb-3 col-md-6">
                                                                <label class="form-label" for="category">Expenses Type<font color="#FF0000"><strong>*</strong></font></label>
                                                                <select name="category" id="category" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                    <option value="">Select a Category...</option>
                                                                    <option value="4-8-106-10386">Hotel</option>
                                                                    <option value="4-8-106-10385">Transport</option>
                                                                    <option value="6-9-23-10640">Entrance Fee</option>
                                                                    <option value="6-9-23-10641">Other</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3 col-md-6">
                                                                <label class="form-label" for="supplier">Expenses Acc<font color="#FF0000"><strong>*</strong></font></label>
                                                                <select name="supplier" id="supplier" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                    <option value="">Select a Supplier...</option>

                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row">

                                                            <div class="mb-3 col-md-6">
                                                                <label class="form-label" for="inv">Invoice No <font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="inv" name="inv" placeholder="" required />
                                                            </div>
                                                            <div class="mb-3 col-md-6">
                                                                <label class="form-label" for="date">Date <font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="date" name="date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" required />
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="mb-3 col-md-4">
                                                                <label class="form-label" for="dollerrate" id="ddlabel">Doller Rate <font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="dollerrate" name="dollerrate" placeholder="" required />
                                                            </div>
                                                            <div class="mb-3 col-md-4">
                                                                <label class="form-label" for="amount" id="ddalabel">$ Amount <font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="amount" name="amount" placeholder="" required />
                                                            </div>

                                                            <div class="mb-3 col-md-4">
                                                                <label class="form-label" for="rsamount">LKR Amount <font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="rsamount" name="rsamount" placeholder="" required readonly />
                                                            </div>
                                                        </div>
                                                        <br>
                                                        <button type="submit" class="btn btn-success w-100px me-5px">Save</button>
                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- <div class="col-md-5">
                                        <div class="card p-3">
                                            <div class="body">
                                                <h5 class="mt-2 text-primary">Supplier Payment History</h5>
                                                <div class="d-flex justify-content-between my-2">
                                                    <div>
                                                        <h6 class="mb-0">Supplier : <span id="sup_name"></span></h6>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">Tour ID : <span id="h_tour_id"></span></h6>
                                                    </div>
                                                </div>
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Date</th>
                                                            <th>Acc</th>
                                                            <th>Inv.</th>
                                                            <th>Description</th>
                                                            <th>Dr</th>
                                                            <th>Cr</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="p_history_div"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div> -->

                                    <!-- <div class="col-md-12 d-none" id="added_creditor_invoice">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>Tour</th>
                                                                <th>Tour Name</th>
                                                                <th>Agent Name</th>
                                                                <th>Bank </th>
                                                                <th>Expenses Type</th>
                                                                <th>Supplier</th>
                                                                <th>Invoice No</th>
                                                                <th>Date</th>
                                                                <th>LKR Amount</th>
                                                                <th>Remove</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="creditor_invoice_tbl">

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div> -->
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
        // document ready
        $(document).ready(function() {
            var tour_id = $('#tour_id_hidden').val();
            if (tour_id != '') {
                populateAddedInvoice(tour_id);
            }
        });

        $("#amount").keyup(function() {
            var d_rate = parseFloat($('#dollerrate').val());
            var dolleramount = parseFloat($(this).val());
            var result = d_rate * dolleramount;
            $('#rsamount').val(result)
        });

        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        $('#category').change(function() {
            var category = $(this).val();
            $.ajax({
                url: "<?php echo base_url() ?>accounts/new_creditor_invoice/get_details",
                type: 'POST',
                data: {
                    category: category
                },
                dataType: 'json',
                success: function(response) {
                    // Handle the response data here
                    $('#supplier').empty();
                    $.each(response.data, function(index, value) {

                        $('#supplier').append('<option value=""></option>');
                        $('#supplier').append('<option value="' + value.L_Code + '">' + value.PName + '</option>');
                    });
                },
                error: function(xhr, status, error) {
                    // Handle any errors here
                }
            });

            if (category == '4-8-106-10385') {
                $('#ddlabel').text('KM *');
                $('#ddalabel').text('Per KM Rate');
            } else {
                $('#ddlabel').text('Doller Rate *');
                $('#ddalabel').text('$ Amount');
            }

        });

        var added_invoice = [];

        function populateAddedInvoice(tour_id) {
            // $('#creditor_invoice_tbl').html('');
            // console.log(added_invoice);
            // var totalAmount1 = 0;
            // var total1 = 0;
            // $.each(added_invoice, function(index, value) {
            //     $('#creditor_invoice_tbl').append(
            //         '<tr>' +
            //         '<td>' + value.tour + '</td>' +
            //         '<td>' + value.des + '</td>' +
            //         '<td>' + value.agent + '</td>' +
            //         '<td>' + value.bank + '</td>' +
            //         '<td>' + value.payment_type + '</td>' +
            //         '<td>' + value.supplier + '</td>' +
            //         '<td>' + value.inv + '</td>' +
            //         '<td>' + value.date + '</td>' +
            //         '<td>' + value.amount + '</td>' +
            //         '</tr>'
            //     );
            // });

            // $.each(added_invoice, function(index, value) {
            //     totalAmount1 += parseFloat(value.amount);
            //     total1 = totalAmount1;
            // });
            // $('#creditor_invoice_tbl').append(
            //     '<tr>' +
            //     '<td colspan="8" class="text-right">Total Amount:</td>' +
            //     '<td>' + parseFloat(total1).toFixed(2) + '</td>' +
            //     '</tr>'
            // );

            // if (added_invoice.length > 0) {
            //     $('#added_creditor_invoice').removeClass('d-none');
            // } else {
            //     $('#added_creditor_invoice').addClass('d-none');
            // }

            $.ajax({
                url: "<?php echo base_url() ?>accounts/new_creditor_invoice/get_tour_details",
                type: 'POST',
                data: {
                    tour_id: tour_id
                },
                dataType: 'json',
                success: function(response) {
                    // Handle the response data here
                    var totalAmount = 0;
                    var total = 0;
                    $('#creditor_invoice_tbl').html('');
                    // $.each(response.data, function(index, value) {
                        // var job = value.job;
                        // var flag = value.Aflag;
                        $('#tour_id').val(response.data.tour_id);
                        $('#tour_manu_id').val(response.data.manual_id);
                        $('#description').val(response.data.consignee);
                        $('#agent').val(response.data.agent).trigger('change');
                        $('#bank_acc').val(response.data.bank).trigger('change');

                        // if (job == '4-8-106-10386') {
                        //     payment_type = 'Hotel';
                        // } else if (job == '4-8-106-10385') {
                        //     payment_type = 'Transport';
                        // } else if (job == '6-9-23-10640') {
                        //     payment_type = 'Entrance Fee';
                        // } else if (job == '6-9-23-10641') {
                        //     payment_type = 'Other';
                        // }

                        // if (flag == 1) {
                        //     batch = 'Authorized';
                        //     bg = 'bg-success';
                        // } else if (flag == 2) {
                        //     batch = 'Failed';
                        //     bg = 'bg-danger';
                        // } else {
                        //     batch = 'Pending';
                        //     bg = 'bg-info';
                        // }

                        // $('#creditor_invoice_tbl').append(
                        //     '<tr>' +
                        //     '<td>' + value.tour_id + '</td>' +
                        //     '<td>' + value.consignee + '</td>' +
                        //     '<td>' + value.agent_name + '</td>' +
                        //     '<td>' + value.bank_name + '</td>' +
                        //     '<td>' + payment_type + '</td>' +
                        //     '<td>' + (value.Name || value.name) + '</td>' +
                        //     '<td>' + value.Invoice_Num + '</td>' +
                        //     '<td>' + value.Date + '</td>' +
                        //     '<td><span class="badge ' + bg + '">' + batch + '</span>' + value.Cr + '</td>' +
                        //     '<td><button type="button" class="btn btn-sm btn-danger" onClick="delete_expenses(' + value.ID + ')"><i class="bi bi-trash"></i></td>' +
                        //     '</tr>'
                        // );
                    // });
                    // $.each(response.data, function(index, value) {
                    //     totalAmount += parseFloat(value.Cr);
                    //     total = totalAmount;
                    // });
                    // $('#creditor_invoice_tbl').append(
                    //     '<tr>' +
                    //     '<td colspan="8" class="text-right">Total Amount:</td>' +
                    //     '<td>' + parseFloat(total).toFixed(2) + '</td>' +
                    //     '</tr>'
                    // );

                    // console.log(response.data.length);
                    // if (response.data.length > 0) {
                    //     $('#added_creditor_invoice').removeClass('d-none');
                    // } else {
                    //     $('#added_creditor_invoice').addClass('d-none');
                    // }
                },
                error: function(xhr, status, error) {
                    // Handle any errors here
                }
            });
        }

        

        function delete_expenses(id) {

            var id = id;

            tfConfirmReason({ // tf-e8-access-r1: reason required
                title: 'Are you sure?',
                text: "You want to delete this Expenses!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST", // tf-e8-access-r1: was GET
                        url: "<?php echo base_url(); ?>accounts/new_creditor_invoice/expenses_delete",
                        data: {
                            id: id,
                            reason: result.value // tf-e8-access-r1
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
                                        populateAddedInvoice(id)
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

                        }
                    });
                }
            });
        }

        // $('#tour_id').focusout(function() {
        //     var tour_id = $(this).val();
        //     populateAddedInvoice(tour_id);
        // });


        $(document).ready(function() {
            function get_payment_history(supplier, supplier_name, tour_id) {
                $.ajax({
                    url: "<?php echo base_url() ?>accounts/new_creditor_invoice/get_transaction_history",
                    type: 'GET',
                    data: {
                        supplier: supplier,
                        tour_id: tour_id
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Handle the response data here
                        $('#p_history_div').html('');
                        if (response.transactions && response.transactions.length > 0) {
                            $('#sup_name').text(supplier_name);
                            $('#h_tour_id').text(response.tour_id);
                            $.each(response.transactions, function(index, value) {
                                $('#p_history_div').append(
                                    '<tr>' +
                                    '<td>' + (index + 1) + '</td>' +
                                    '<td>' + value.Date + '</td>' +
                                    '<td>' + value.Account + '</td>' +
                                    '<td>' + value.Invoice_Num + '</td>' +
                                    '<td>' + value.Discription + '</td>' +
                                    '<td>' + value.Dr + '</td>' +
                                    '<td>' + value.Cr + '</td>' +
                                    '</tr>'
                                );
                            });
                        } else {
                            $('#sup_name').text(supplier_name);
                            $('#h_tour_id').text(tour_id);
                            $('#p_history_div').html('<tr><td colspan="6" class="text-center">No data found</td></tr>');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle any errors here
                    }
                });
            }

            // Submit form data via Ajax
            $('#form_submit').submit(function(e) {
                e.preventDefault();

                var tour_id = $('#tour_id').val();
                var formData = new FormData(this);
                formData.append('agent_name', $('#agent option:selected').text());
                formData.append('bank_name', $('#bank_acc option:selected').text());
                formData.append('supplier_name', $('#supplier option:selected').text());

                $.ajax({
                    url: "<?php echo base_url() ?>accounts/new_creditor_invoice/complete_payment",
                    type: 'POST',
                    data: formData,
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
                                // added_invoice.push({
                                //     'tour': $('#tour_id').val(),
                                //     'des': $('#description').val(),
                                //     'agent': $('#agent').find('option:selected').text(),
                                //     'bank': $('#bank_acc').find('option:selected').text(),
                                //     'payment_type': $('#category').find('option:selected').text(),
                                //     'supplier': $('#supplier').find('option:selected').text(),
                                //     'inv': $('#inv').val(),
                                //     'date': $('#date').val(),
                                //     'amount': $('#rsamount').val()
                                // });
                                // populateAddedInvoice(tour_id);
                                get_payment_history(0, 0, tour_id);
                                // trigger chass_no change
                                $('#category').val("").change();
                                $('#supplier').val("").change();
                                $('#inv').val("");
                                // $('#date').val("");
                                $('#des').val("");
                                $('#amount').val("");
                                $('#rsamount').val("");
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

            // #tour_id change
            $('#tour_id').change(function() {
                var tour_id = $('#tour_id').val();
                if (tour_id != '') {
                    get_payment_history(0, 0, tour_id);
                }
            });

        });
    </script>
</body>

</html>