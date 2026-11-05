<?= view('layouts/head') ?>

<body>
    <style type="text/css">
        .pull-right {
            float: right;
        }

        .typeahead,
        .typeahead2,
        .tt-query,
        .tt-hint {
            display: block;
            width: 750px;
            padding: 0.4375rem 0.75rem;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.5;
            text-transform: uppercase;
            /*margin-bottom: 0.9375rem !important;*/
        }

        .tt-menu {
            width: 750px;
            /*font-weight: bold;*/
            line-height: 2;
            font-size: 13px;
            text-transform: uppercase;
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
            width: 100;
            text-transform: uppercase;
            margin-left: 0px;
        }

        .tt-suggestion:hover {
            cursor: pointer;
            color: #fff;
            background-color: #5897fb;
            width: 750px;
            margin-left: 0px;

            line-height: 2;
            text-transform: uppercase;

        }

        .tt-suggestion.tt-cursor {
            color: #fff;
            background-color: #5897fb;
            /*width: 100%;*/
            width: 750px;
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

        .form-label {
            color: #333;
            /* Darker color for better readability */
        }

        .form-check-input {
            width: 1.5em;
            /* Increase size of radio buttons */
            height: 1.5em;
            /* Increase size of radio buttons */
        }

        .form-check-label {
            margin-left: 10px;
            /* Spacing between label and radio button */
        }
    </style>
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
                                        <h4 class="p-title">Journal Entry</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-4">
                                                <form id="date_form" accept-charset="utf-8">
                                                    <fieldset>
                                                        <div class="row mb-3">
                                                            <!-- Radio Buttons for Dr and Cr -->
                                                            <div class="col-md-6">
                                                                <label class="form-label">Transaction Type<font color="#FF0000"><strong>*</strong></font></label><br>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="transaction_type" id="dr" value="dr" required>
                                                                    <label class="form-check-label" for="dr">Debit</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="transaction_type" id="cr" value="cr" required>
                                                                    <label class="form-check-label" for="cr">Credit</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-7">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="item_search">Account<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input type="text" name="item_search" class="form-control item_search typeahead custom-input" id="item_search" placeholder="" required />
                                                                    <input type="hidden" class="form-control" id="item_code" name="item_code" />
                                                                    <input type="hidden" name="debit_acc" id="debit_acc_name" class="form-control" readonly />
                                                                    <input type="hidden" name="bname" id="bname" class="form-control" readonly />
                                                                    <input type="hidden" name="tname" id="tname" class="form-control" readonly />
                                                                    <input type="hidden" name="hname" id="hname" class="form-control" readonly />
                                                                </div>
                                                            </div>

                                                            <div class="col-md-5">
                                                                <!-- <div class="mb-3"> -->
                                                                <div id="accountPath" style="background-color: #f9f9f9; padding: 10px; font-weight: bold;margin-left: 0;"></div>
                                                                <!-- </div> -->
                                                            </div>
                                                        </div>



                                                        <!--        <div class="row">

                                                        <div class="col-md-6 text-center">
                                                            <div class="mb-3">
                                                                <input type="checkbox" id="lock" name="lock" value="1">
                                                                <label for="lock">Lock</label><br>
                                                            </div>
                                                        </div>
                                                    </div> -->

                                                        <!-- Hidden Debit Account Field -->
                                                        <input type="hidden" name="debit_acc" id="debit_acc" class="form-control" readonly />




                                                        <hr style="border-color: black;">

                                                        <br>

                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <label class="form-label" for="app_date">Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control default-date" type="text" id="date" name="date" data-provide="datepicker" data-date-format="yyyy-mm-dd" value="<?php echo date('Y-m-d') ?>" placeholder="" required />
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label" for="b_name">Division<font color="#FF0000"><strong>*</strong></font></label>
                                                                <div class="mb-3">
                                                                    <select class="form-control" name="dv" id="dv" style="width: 100%;" required>
                                                                        <option value="1">General</option>
                                                                    </select>
                                                                </div>

                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label" for="b_name">Invoice No<font color="#FF0000"><strong>*</strong></font></label>
                                                                <div class="mb-3">
                                                                    <input type="text" name="inv_no" id="inv_no" class="form-control" required />
                                                                </div>

                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label" for="b_name">Supplier Inv Number<font color="#FF0000"><strong>*</strong></font></label>
                                                                <div class="mb-3">
                                                                    <input type="text" name="sup_inv" id="sup_inv" class="form-control" required />
                                                                </div>

                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label" for="b_name">Descripion<font color="#FF0000"><strong>*</strong></font></label>
                                                                <div class="mb-3">
                                                                    <input type="text" name="descrip" id="descrip" class="form-control" required />
                                                                </div>

                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label" for="b_name">Amount<font color="#FF0000"><strong>*</strong></font></label>
                                                                <div class="mb-3">
                                                                    <input type="text" name="amount" id="amount" class="form-control" required />
                                                                </div>

                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12 text-right">
                                                                    <div class="form-group pull-right"><br>
                                                                        <button class="btn btn-primary btn_add">ADD</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row clearfix mt-2">
                                    <div class="col-md-12" style="display: none;" id="data_tbl">
                                        <div class="card">
                                            <div class="body p-4">
                                                <div class="panel panel-inverse">
                                                    <div class="panel-heading">
                                                        <h4 class="panel-title"></h4>
                                                    </div>
                                                    <div class="panel-body">
                                                        <table class="table table-striped table-bordered align-middle">
                                                            <thead>
                                                                <tr>
                                                                    <th width="10%">ID</th>
                                                                    <!-- <th width="10%">Credit Account</th> -->
                                                                    <th width="20%"> Account</th>
                                                                    <th width="10%">Date</th>
                                                                    <!-- <th width="10%">Devision</th> -->
                                                                    <th width="10%">Inv No</th>
                                                                    <th width="10%">Description</th>
                                                                    <th width="10%">Sup Inv</th>
                                                                    <th width="10%">Debit</th>
                                                                    <th width="10%">Credit</th>

                                                                    <th width="10%">remove</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="pay_tbl">
                                                            </tbody>
                                                        </table>
                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-md-12 text-right">
                                                                <div class="form-group pull-right"><br>
                                                                    <button class="btn btn-primary " data-toggle="modal" data-target="#finishFeesModal" id="btnFinish" style="display: none"> FINISH</button>
                                                                </div>
                                                            </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>
    <script src="https://twitter.github.io/typeahead.js/js/handlebars.js"></script>
    <script src="https://twitter.github.io/typeahead.js/releases/latest/typeahead.bundle.js"></script>

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

        $(document).ready(function() {
            $('#v_type').focus();
            var type = $('#v_type').val();

            var load_data = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: '<?php echo base_url(); ?>accounts/journal_entry/fetch',
                remote: {
                    url: '<?php echo base_url(); ?>accounts/journal_entry/fetch/' + '%QUERY',
                    wildcard: '%QUERY'
                },

            });

            $('.typeahead').typeahead(null, {
                    name: 'load_data',
                    display: 'PName',
                    source: load_data,
                    limit: 10,
                    templates: {
                        suggestion: Handlebars.compile(`
                    <div class="row">
                    <div class="col-md-5" style="font-size: 14px;">{{PName}}</div>
                    <div class="col-md-7" style="font-size: 0.8em;">
                    {{BName}} → {{TName}} → {{HName}}
                    </div>
                    </div>
                    `)

                    },
                    highlight: true
                })
                .on("typeahead:selected", function(e, datum) {
                    $("form [name=acc_name]").val(datum.PName);
                    $("form [name=acc]").val(datum.L_Code);
                    $('#debit_acc').val(datum.L_Code);
                    $('#debit_acc_name').val(datum.PName);
                    $('#item_search').val(datum.PName);
                    $('#bname').val(datum.BName);
                    $('#tname').val(datum.TName);
                    $('#hname').val(datum.HName);

                    let accountPath = [datum.BName, datum.TName, datum.HName, datum.PName].filter(Boolean).join(' -> ');

                    $('#accountPath').text(accountPath);
                });

        });






        function get_DataSet(item) {
            $("#preloader").show();
            $.post('<?php echo base_url() ?>grn/Con_grn/get_dataset', {
                'get_item_details': 'data',
                itemName: item
            }, function(data) {
                if (data.length == 0) {
                    $("#preloader").fadeOut();
                } else {
                    $.each(data, function(index, data) {



                        $("#u_cost").val(data.price);


                    });
                    $('#qty').focus();
                }
            }, 'json');
        }
        /**************************** ADD ITEMS  ***********************/

        var itemArray = [];

        $('.btn_add').click(function() {

            var qty = "";
            var price = "";
            var total = "";
            var item = "";



            var credit = $('#credit_acc').val();
            var debit = $('#debit_acc').val();
            var debit_acc_name = $('#debit_acc_name').val();
            var date = $('#date').val();
            var dv = $('#dv option:selected').val();
            var inv_no = $('#inv_no').val();
            var descrip = $('#descrip').val();
            var sup_inv = $('#sup_inv').val();
            var amount = $('#amount').val();
            var transaction_type = $('input[name="transaction_type"]:checked').val();
            // alert(transaction_type);

            // Date validation: cannot be less than 2000-01-01
            if (date < '2000-01-01') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Date',
                    text: 'Date cannot be less than 2000-01-01!',
                    showConfirmButton: true
                });
                return;
            }

            if (debit === '' || typeof transaction_type === 'undefined' || transaction_type === '') {
                swal('Account details missing!!');
            } else {

                itemArray.push({
                    'debit': debit,
                    'debit_acc_name': debit_acc_name,
                    'date': date,
                    'dv': dv,
                    'inv_no': inv_no,
                    'descrip': descrip,
                    'sup_inv': sup_inv,
                    'amount': amount,
                    'transaction_type': transaction_type
                });

                loadPaymentTbl(itemArray);
                $('#date').prop('disabled', true);


                // $('#inv_no').val('');
                // $('#sup_inv').val('');
                $('#item_search').val('');
                $('#amount').val('');
                $('#accountPath').val('');


            }

            var l = itemArray.length;

            if (l > 0) {
                $('#data_tbl').show();
            } else {
                $('#data_tbl').hide();
            }


        });


        function loadPaymentTbl(itemArray) {

            var tableData = '';
            $('#pay_tbl ').html('').append(tableData);


            var l = itemArray.length;
            var totalDr = 0;
            var totalCr = 0;

            for (var i = 0; i < l; i++) {
                tableData += "<tr>";
                tableData += "<td>" + (i + 1) + "</td>";
                tableData += "<td>" + itemArray[i]['debit'] + " - " + itemArray[i]['debit_acc_name'] + "</td>";

                // var dr = 0, cr = 0;
                var dr = 0,
                    cr = 0;
                if (itemArray[i]['transaction_type'] == 'dr') {
                    dr = Number(itemArray[i]['amount']); // Convert to number
                    totalDr += dr;
                } else if (itemArray[i]['transaction_type'] == 'cr') {
                    cr = Number(itemArray[i]['amount']); // Convert to number
                    totalCr += cr;

                }


                tableData += "<td>" + itemArray[i]['date'] + "</td>";
                tableData += "<td>" + itemArray[i]['inv_no'] + "</td>";
                tableData += "<td>" + itemArray[i]['descrip'] + "</td>";
                tableData += "<td>" + itemArray[i]['sup_inv'] + "</td>";
                tableData += "<td>" + dr + "</td>";
                tableData += "<td>" + cr + "</td>";
                tableData += "<td class='text text-right'><button class='btn btn-sm btn-danger btn_remove' value='" + i + "'><i class='bi bi-trash'></i></button></td>";
                tableData += "</tr>";
            }

            // After the loop, add a row for totals
            tableData += "<tr class='total-row'>";
            tableData += "<td colspan='2'><b>Total</b></td>";
            tableData += "<td colspan='4'></td>"; // Adjust columns as needed for your layout
            tableData += "<td><b>" + totalDr.toFixed(2) + "</b></td>";
            tableData += "<td><b>" + totalCr.toFixed(2) + "</b></td>";
            tableData += "<td></td>"; // For the button column
            tableData += "</tr>";

            $('#pay_tbl ').html('').append(tableData);


            if (l > 0) {
                $('#btnFinish').show();
            } else {
                $('#btnFinish').hide();
            }
        }


        $(document).on('click', '.btn_remove', function(e) {
            var index = $(this).val();

            itemArray.splice(index, 1);

            loadPaymentTbl(itemArray);
        });

        $(document).on('click', '#btnFinish', function(e) {
            if (itemArray.length > 0) {
                // Calculate sums for transaction types
                let sumDr = 0;
                let sumCr = 0;

                itemArray.forEach(item => {
                    if (item.transaction_type === 'dr') {
                        sumDr += parseFloat(item.amount) || 0; // Handle NaN if amount is not a number
                    } else if (item.transaction_type === 'cr') {
                        sumCr += parseFloat(item.amount) || 0;
                    }
                });

                // Check if the sums are equal
            const epsilon = 0.001; // Adjust precision tolerance as needed
if (Math.abs(sumDr - sumCr) > epsilon) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Error',
                        text: 'The Total of debits must equal the Total of credits!',
                        showConfirmButton: true
                    });
                    return; // Prevent submission if not equal
                }

                const AjaxURL = '<?php echo base_url(); ?>accounts/journal_entry/save';
                const DataSet = {
                    itemArray: itemArray
                };

                $.ajax({
                    url: AjaxURL,
                    type: 'post',
                    data: DataSet,
                    dataType: 'json',
                    success: function(response) {
                        console.log(response);

                        if (response.status == 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Added Successfully!',
                                showConfirmButton: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload(); // Refresh the page or redirect
                                }
                            });
                        } else if (response.status == 400) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Error!',
                                text: 'Error occurred.',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong! Please try again later.',
                        });
                    }
                });
            }
        });
    </script>
</body>




</html>