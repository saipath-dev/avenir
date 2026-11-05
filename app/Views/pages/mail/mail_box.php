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
            width: 600px;
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
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="">Debit Account<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input type="text" name="item_search" class="form-control  item_search typeahead " id="item_search" placeholder="" required />
                                                                    <input class="form-control" type="hidden" id="item_code" name="item_code" />
                                                                </div>
                                                            </div>
                                                            <!-- <div class="col-md-2"></div> -->
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="">Credit Account<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control item_search typeahead2" type="text" id="item_search2" name="item_search2" placeholder="" required />
                                                                    <input class="form-control" type="hidden" id="item_code" name="item_code" placeholder="" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-3">
                                                                <div class="mb-3">
                                                                    <input type="text" name="debit_acc" id="debit_acc" class="form-control" readonly />
                                                                </div>

                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="mb-3">
                                                                    <input type="text" name="debit_acc" id="debit_acc_name" class="form-control" readonly />
                                                                </div>

                                                            </div>

                                                            <div class="col-md-3">
                                                                <div class="mb-3">
                                                                    <input type="text" name="credit_acc" id="credit_acc" class="form-control" readonly />
                                                                </div>

                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="mb-3">
                                                                    <input type="text" name="credit_acc" id="credit_acc_name" class="form-control" readonly />
                                                                </div>

                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-2">
                                                                <div class="mb-3">
                                                                    <input type="text" name="bname" id="bname" class="form-control" readonly />
                                                                </div>

                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="mb-3">
                                                                    <input type="text" name="tname" id="tname" class="form-control" readonly />
                                                                </div>

                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="mb-3">
                                                                    <input type="text" name="hname" id="hname" class="form-control" readonly />
                                                                </div>

                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="mb-3">
                                                                    <input type="text" name="bname2" id="bname2" class="form-control" readonly />
                                                                </div>

                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="mb-3">
                                                                    <input type="text" name="tname2" id="tname2" class="form-control" readonly />
                                                                </div>

                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="mb-3">
                                                                    <input type="text" name="hname2" id="hname2" class="form-control" readonly />
                                                                </div>

                                                            </div>

                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6 text-center">
                                                                <input type="checkbox" id="lock" name="lock" value="1">
                                                                <label for="vehicle1">Lock</label><br>
                                                            </div>
                                                            <div class="col-md-6 text-center">
                                                                <input type="checkbox" id="lock1" name="lock1" value="2">
                                                                <label for="vehicle1">Lock</label><br>
                                                            </div>
                                                        </div>

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
                                                                    <th width="10%">Credit Account</th>
                                                                    <th width="20%">Debit Account</th>
                                                                    <th width="10%">Date</th>
                                                                    <th width="10%">Devision</th>
                                                                    <th width="10%">Inv No</th>
                                                                    <th width="10%">Description</th>
                                                                    <th width="10%">Sup Inv</th>
                                                                    <th width="10%">Amount</th>
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
                        suggestion: Handlebars.compile('<div class="row"><div class="col-md-5" style="padding-right:5px; padding-left:5px; border-right: 1px solid #fff; border-bottom: 1px solid #fff;">{{PName}}</div><div class="col-md-2" style="padding-right:5px; padding-left:5px; border-right: 1px solid #fff; border-bottom: 1px solid #fff;">{{BName}}</div><div class="col-md-2" style="padding-right:5px; padding-left:5px; border-right: 1px solid #fff; border-bottom: 1px solid #fff;">{{TName}}</div><div class="col-md-3" style="padding-right:5px; padding-left:5px;  border-bottom: 1px solid #fff;">{{HName}}</div></div>')
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
                });

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

            $('.typeahead2').typeahead(null, {
                    name: 'load_data',
                    display: 'PName',
                    source: load_data,
                    limit: 10,
                    templates: {
                        suggestion: Handlebars.compile('<div class="row"><div class="col-md-5" style="padding-right:5px; padding-left:5px; border-right: 1px solid #fff; border-bottom: 1px solid #fff;">{{PName}}</div><div class="col-md-2" style="padding-right:5px; padding-left:5px; border-right: 1px solid #fff; border-bottom: 1px solid #fff;">{{BName}}</div><div class="col-md-2" style="padding-right:5px; padding-left:5px; border-right: 1px solid #fff; border-bottom: 1px solid #fff;">{{TName}}</div><div class="col-md-3" style="padding-right:5px; padding-left:5px;  border-bottom: 1px solid #fff;">{{HName}}</div></div>')
                    },
                    highlight: true
                })
                .on("typeahead:selected", function(e, datum) {

                    $("form [name=acc_name]").val(datum.PName);
                    $("form [name=acc]").val(datum.L_Code);
                    $('#credit_acc').val(datum.L_Code);
                    $('#credit_acc_name').val(datum.PName);
                    $('#item_search2').val(datum.PName);
                    $('#bname2').val(datum.BName);
                    $('#tname2').val(datum.TName);
                    $('#hname2').val(datum.HName);
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
            var date = $('#date').val();
            var dv = $('#dv option:selected').val();
            var inv_no = $('#inv_no').val();
            var descrip = $('#descrip').val();
            var sup_inv = $('#sup_inv').val();
            var amount = $('#amount').val();


            if ((credit == '') || (debit == '')) {
                swal('Account details missing!!');
            } else {


                itemArray.push({
                    'credit': credit,
                    'debit': debit,
                    'date': date,
                    'dv': dv,
                    'inv_no': inv_no,
                    'descrip': descrip,
                    'sup_inv': sup_inv,
                    'amount': amount
                });

                loadPaymentTbl(itemArray);
                if ($('#lock').prop('checked')) {

                } else {
                    $('#debit_acc').val('');
                    $('#debit_acc_name').val('');
                    $('#item_search').val('');
                }

                if ($('#lock1').prop('checked')) {

                } else {
                    $('#credit_acc').val('');
                    $('#credit_acc_name').val('');
                    $('#item_search2').val('');
                }

                $('#date').val('');
                // $('#dv').val('');
                $('#inv_no').val('');
                // $('#descrip').val('');
                $('#sup_inv').val('');
                $('#amount').val('');


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
            for (var i = 0; i < l; i++) {

                tableData += "<tr>";
                tableData += "<td>" + (i + 1) + "</td>";
                tableData += "<td>" + itemArray[i]['credit'] + "</td>";
                tableData += "<td>" + itemArray[i]['debit'] + "</td>";
                tableData += "<td>" + itemArray[i]['date'] + "</td>";
                tableData += "<td>" + itemArray[i]['dv'] + "</td>";
                tableData += "<td>" + itemArray[i]['inv_no'] + "</td>";
                tableData += "<td>" + itemArray[i]['descrip'] + "</td>";
                tableData += "<td>" + itemArray[i]['sup_inv'] + "</td>";
                tableData += "<td>" + itemArray[i]['amount'] + "</td>";

                tableData += "<td class='text text-right'><button class='btn btn-sm btn-danger btn_remove' value='" + itemArray[i]['credit'] + "'><i class='bi bi-trash'></i></button></td>";
                tableData += "</tr>";

            }


            $('#pay_tbl ').html('').append(tableData);


            if (l > 0) {
                $('#btnFinish').show();
            } else {
                $('#btnFinish').hide();
            }
        }


        $(document).on('click', '.btn_remove', function(e) {
            var code = $(this).val();
            var removeIndex = itemArray.map(function(item) {
                return item.item;
            }).indexOf(code);

            // remove object
            itemArray.splice(removeIndex, 1);

            loadPaymentTbl(itemArray);
        });


        $(document).on('click', '#btnFinish', function(e) {
            // var je_inv=$('#JE_inv').val();

            if (itemArray.length > 0) {

                AjaxURL = '<?php echo base_url(); ?>accounts/journal_entry/save',
                    DataSet = {
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
                                    // window.location.href = "<?php //echo base_url() 
                                                                ?>payments/Voucher/index/" + menu;
                                    location.reload();
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
                        $(this).button('reset');
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong! Please try again later.',
                        });
                    }
                });
            }
            // }

        });
    </script>
</body>




</html>