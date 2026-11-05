<?= view('layouts/head') ?>


<body>

  <!-- Begin page -->
  <div id="layout-wrapper">

   <style type="text/css">
    .pull-right {
      float: right;
    }

    .typeahead,
    .typeahead2,
    .tt-query,
    .tt-hint {
      display: block;
      width:230%;
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
      width: 100
      text-transform: uppercase;
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
                  <div class="col-md-12 d-flex">
                    <div class="me-auto">
                      <h4 class="p-title">Voucher Payment</h4>
                    </div>
                    <div>
                                            <!-- <button id="finalize_btn" class="btn btn-success waves-effect waves-light fw-bold"> Finalize
                                            </button> -->
                                          </div>
                                        </div>
                                      </div>

                                      <div class="row clearfix mt-3">
                                        <div class="col-md-8">
                                          <div class="col-md-12" id="consignee_form">
                                            <div class="card p-4">
                                              <div class="body">
                                                <form action="javascript:void(0);" id="tracking_form" accept-charset="utf-8">
                                                  <fieldset>
                                                    <div class="row">
                                                      <div class="col-md-12">
                                                        <h6 class="text-primary">Voucher Request</h6>
                                                      </div>
                                                    </div>

                                                    <div class="mb-3">
                                                      <div class="row">
                                                        <div class="col-md-3">
                                                          <label class="form-label pull-right" for="v_type">Voucher Type : </label>
                                                        </div>
                                                        <div class="col-md-7">
                                                          <select class="form-control default-select2 txt_nav" name="v_type" id="v_type" required>

                                                            <option value="1" <?php  if($payment_type == '1'){ echo "selected"; }  ?> >Voucher Payment</option>
                                                            <option value="2" <?php  if($payment_type == '2'){ echo "selected"; }  ?> >Supplier Account</option>

                                                          </select>
                                                        </div>
                                                      </div>
                                                    </div>
                                                    <div class="mb-3">
                                                      <div class="row">
                                                        <div class="col-md-3">
                                                          <label class="form-label pull-right" for="ref_no">Reference No : </label>
                                                        </div>
                                                        <div class="col-md-7">
                                                          <input class="form-control txt_nav" type="text" id="ref_no" name="ref_no" required autocomplete="off" />
                                                        </div>
                                                      </div>
                                                    </div>
                                                    <div class="mb-3">
                                                      <div class="row">
                                                        <div class="col-md-3">
                                                          <label class="form-label pull-right" for="p_method">Payment Method :</label>
                                                        </div>
                                                        <div class="col-md-7">
                                                          <input type="radio" class="minimal txt_nav" name="p_method" value="BT" checked required> <label> &nbsp;&nbsp;BT</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                          <input type="radio" class="minimal txt_nav" name="p_method" value="Cheque"  required> <label> &nbsp;&nbsp;Cheque</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                          <input type="radio" class="minimal txt_nav" name="p_method" value="Cash" required> <label> &nbsp;&nbsp;Cash</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                          <input type="radio" class="minimal txt_nav" name="p_method" value="Petty Cash" required> <label> &nbsp;&nbsp;Petty Cash</label>
                                                        </div>
                                                      </div>
                                                    </div>

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

                                                    <div class="mb-3">
                                                      <div class="row">
                                                        <div class="col-md-3">
                                                          <button type="submit" id="btn_inv" data-bs-target="#categoryOffcanvas" aria-controls="categoryOffcanvas" class="btn btn-primary pull-right txt_nav" <?php if($payment_type==2){ echo "disabled";} ?> >ADD INVOICE</button>
                                                        </div>
                                                      </div>
                                                    </div>
                                                    <table id="inv_dtl" class="table table-striped table-bordered align-middle">
                                                      <thead>
                                                        <tr>
                                                          <th>#</th>
                                                          <th>Date</th>
                                                          <th>Invoice No</th>
                                                          <th>Description</th>
                                                          <th>Units</th>
                                                          <th>Rate</th>
                                                          <?php  if($payment_type == '2'){ ?>
                                                            <th>Amount</th>
                                                          <?php } ?>

                                                          <th>Edit Amount</th>
                                                          <th>Remove</th>
                                                        </tr>
                                                      </thead>
                                                      <tbody>

                                                      </tbody>
                                                    </table>

                                                    <div class="mb-3">
                                                      <div class="row">
                                                        <div class="col-md-3">
                                                          <label class="form-label pull-right" for="tot_amt">Grand Total : </label>
                                                        </div>
                                                        <div class="col-md-7">
                                                          <input class="form-control" type="text" id="tot_amt" name="tot_amt" readonly autocomplete="off" />
                                                          <input type="hidden" name="invoice_no"  id="invoice_no">
                                                        </div>
                                                      </div>
                                                    </div>

                                                    <div class="mb-3">
                                                      <div class="row">
                                                        <div class="col-md-3">
                                                          <label class="form-label pull-right" for="divi">Division : </label>
                                                        </div>
                                                        <div class="col-md-7">
                                                          <select autofocus id="divi" name="divi" class="form-control txt_nav">

                                                            <option value="">Select</option>
                                                            <option value="1">General</option>

                                                          </select>
                                                        </div>
                                                      </div>
                                                    </div>

                                                    <div class="mb-3" style="display: none;">
                                                      <div class="row">
                                                        <div class="col-md-3">
                                                          <label class="form-label pull-right" for="req">Approval Request From : </label>
                                                        </div>
                                                        <div class="col-md-7">
                                                          <select autofocus id="req" name="req" class="form-control txt_nav" required>
                                                            <?php foreach ($req_user as $load_users) {
                                                               if($load_users['acc_no'] == '4-8-9-9631'){
                                                                $txt = "selected";
                                                               } else{
                                                                $txt="";
                                                               }

                                                             ?>
                                                              <option value="<?php echo $load_users['acc_no'] ?>" <?php echo $txt ?>><?php echo $load_users['user'] ?></option>
                                                            <?php } ?>
                                                          </select>
                                                        </div>
                                                      </div>
                                                    </div>

                                                    <div align="center">
                                                      <button type="submit" class="btn btn-success w-100px me-5px txt_nav" id="save">Save</button>
                                                      <button type="reset" class="btn btn-default w-100px">Cancel</button>
                                                    </div>

                                                    <!--  -->
                                                    <!-- <button type="submit" class="btn btn-primary  me-5px search_data" id="search_data">Track</button> -->
                                                    <!-- <button type="reset" class="btn btn-default ">Cancel</button> -->

                                                  </fieldset>
                                                </form>
                                              </div>
                                            </div>
                                          </div>
                                        </div>


                                      </div>
                                      <!--   -->


                                      <!-- Modal -->
                                      <div class="modal fade" id="chq_dtlz" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                          <div class="modal-content">
                                            <div class="modal-header">
                                              <h5 class="modal-title" id="exampleModalLabel">Cheque Details</h5>
                                              <button type="button" class="btn btn-secondary close modal_close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                              </button>
                                            </div>
                                            <div class="modal-body">

                                              <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                <fieldset>
                                                  <div class="mb-3">
                                                    <div class="row">
                                                      <div class="col-md-3">
                                                        <label class="form-label pull-right" for="bank">Bank : </label>
                                                      </div>
                                                      <div class="col-md-7">
                                                        <select class="form-control default-select2 txt_nav" name="bank" id="bank" style="width: 100%;" required>
                                                          <option></option>
                                                          <?php foreach ($load_bank as $bank) { ?>
                                                            <option value="<?php echo $bank['L_Code'] ?>"><?php echo $bank['PName'] ?></option>
                                                          <?php } ?>

                                                        </select>
                                                      </div>
                                                    </div>
                                                  </div>
                                                  <div class="mb-3">
                                                    <div class="row">
                                                      <div class="col-md-3">
                                                        <label class="form-label pull-right" for="chq_date">Payment Date : </label>
                                                      </div>
                                                      <div class="col-md-7">
                                                        <input class="form-control date txt_nav" type="date" id="chq_date" name="chq_date" autocomplete="off" />
                                                      </div>
                                                    </div>
                                                  </div>
                                                  <div class="mb-3">
                                                    <div class="row">
                                                      <div class="col-md-3">
                                                        <label class="form-label pull-right" for="chq_no">Ref No : </label>
                                                      </div>
                                                      <div class="col-md-7">
                                                        <input class="form-control txt_nav" type="text" id="chq_no" name="chq_no" required autocomplete="off" />
                                                      </div>
                                                    </div>

                                                    <button type="submit" id="btn_chq" class="btn btn-success  me-5px btn_chq pull-right txt_nav">Save</button>
                                                  </fieldset>
                                                </form>

                                              </div>
                                              <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary modal_close" data-dismiss="modal">Close</button>
                                              </div>
                                            </div>
                                          </div>
                                        </div>

                                        <!-- Modal -->
                                        <div class="modal fade" id="payment_add" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                          <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                              <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel">Invoice Details</h5>
                                                <button type="button" class="btn btn-secondary close modal_close" data-dismiss="modal" aria-label="Close">
                                                  <span aria-hidden="true">&times;</span>
                                                </button>
                                              </div>
                                              <div class="modal-body">

                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                  <fieldset>
                                                    <div class="mb-3">
                                                      <div class="row">
                                                        <div class="col-md-3">
                                                          <label class="form-label pull-right" for="date">Date : </label>
                                                        </div>
                                                        <div class="col-md-7">
                                                          <input class="form-control date txt_nav" type="date" id="date" name="date" autocomplete="off" />
                                                        </div>
                                                      </div>
                                                    </div>
                                                    <div class="mb-3">
                                                      <div class="row">
                                                        <div class="col-md-3">
                                                          <label class="form-label pull-right" for="inv_no">Invoice No : </label>
                                                        </div>
                                                        <div class="col-md-7">
                                                          <input class="form-control txt_nav" type="text" id="inv_no" name="inv_no" required autocomplete="off" />
                                                        </div>
                                                      </div>
                                                    </div>
                                                    <div class="mb-3">
                                                      <div class="row">
                                                        <div class="col-md-3">
                                                          <label class="form-label pull-right" for="desc">Description : </label>
                                                        </div>
                                                        <div class="col-md-7">
                                                          <input class="form-control txt_nav" type="text" id="desc" name="desc" required autocomplete="off" />
                                                        </div>
                                                      </div>
                                                    </div>
                                                    <div class="mb-3">
                                                      <div class="row">
                                                        <div class="col-md-3">
                                                          <label class="form-label pull-right" for="units">No. Of Units : </label>
                                                        </div>
                                                        <div class="col-md-7">
                                                          <input class="form-control txt_nav" type="text" id="units" name="units" required autocomplete="off" />
                                                        </div>
                                                      </div>
                                                    </div>
                                                    <div class="mb-3">
                                                      <div class="row">
                                                        <div class="col-md-3">
                                                          <label class="form-label pull-right" for="rate">Rate : </label>
                                                        </div>
                                                        <div class="col-md-7">
                                                          <input class="form-control txt_nav" type="text" id="rate" name="rate" required autocomplete="off" />
                                                        </div>
                                                      </div>
                                                    </div>
                                                    <div class="mb-3">
                                                      <div class="row">
                                                        <div class="col-md-3">
                                                          <label class="form-label pull-right" for="amount">Amount : </label>
                                                        </div>
                                                        <div class="col-md-7">
                                                          <input class="form-control" type="hidden" id="amount" name="amount" required />
                                                          <label class="form-control"> <span id="amount_txt">0.00</span></label>
                                                        </div>
                                                      </div>
                                                    </div>

                                                    <button type="submit" id="btn_add" class="btn btn-success  me-5px btn_add pull-right txt_nav">Save</button>
                                                  </fieldset>
                                                </form>

                                              </div>
                                              <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary modal_close" data-dismiss="modal">Close</button>
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
                    <!-- Loading Modal -->


                  </div>

                  <div class="modal" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content" style="width: 300px; height: 80px; margin-left: 100px; margin-bottom: 100px;">
                        <div class="modal-body">
                          <div class="d-flex flex-column align-items-center justify-content-center">
                            <img src="<?php echo base_url() ?>assets/images/loading.gif" width="30px">
                            <label class="form-label">Just a moment...</label>
                          </div>
                        </div>
                      </div>
                    </div>
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

                <script>

                </script>
                <script>
                  const d = new Date();
                  var text = d.toLocaleDateString("en-CA");
                  $("#date").val(text);
                  $("#date1").val(text);
                  $(document).ready(function() {
                    $('#v_type').focus();
                    var type = $('#v_type').val();
                    var vtp = $('#payment_type').val();

                    var load_data = new Bloodhound({
                      datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                      queryTokenizer: Bloodhound.tokenizers.whitespace,
                      prefetch: '<?php echo base_url(); ?>accounts/voucher_payments/fetch',
                      remote: {
                        url: '<?php echo base_url(); ?>accounts/voucher_payments/fetch/' + type + '/%QUERY',
                        wildcard: '%QUERY',
                        transform: function (response) {
                          return response; 
                        },
                        replace: function (url, uriEncodedQuery) {

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


                      var v_type = $('#v_type').val();

                      if(v_type == 2){
                       load_supplier_details(datum.L_Code); 
                     }

                   });

                  });


                  function load_supplier_details(l_code){

                    $.get('<?php echo base_url() ?>accounts/voucher_payments/getSupplierDetails', {
                      l_code: l_code
                    }, function(data) {
                      let tot = 0;
                      let currentDate = new Date();

                      let year = currentDate.getFullYear();
                      let month = String(currentDate.getMonth() + 1).padStart(2, '0');
                      let day = String(currentDate.getDate()).padStart(2, '0');

                      let formattedDate = `${year}-${month}-${day}`;

                      if (data && data.length > 0) {
                        data.forEach(item => {
                          tot += parseFloat(item.bal); 
                          let acc_name = item.supplier_name; 
                          let desc = 'Supplier Payment - ' + acc_name;
                          let amount = item.bal; 
                          let lcode = item.lcode;
                          $('#description').val(desc);

                          if(item.bal>0){

                            itemArray.push({
                              'date': formattedDate,
                              'inv_no': item.Invoice_Num || '0',
                              'desc': desc,
                              'units': '0',
                              'rate': '0',
                              'amount': amount,
                              'sup_lcode': lcode,
                              'supplier_name': acc_name
                            });
                          }
                        });

                        if (itemArray.length > 1) {
                          $('#description').val('Supplier Payments for Multiple Suppliers');
                        }

                        loadCheckOutTbl(itemArray);
                        $('#tot_amt').val(tot);

                      }
                    }, 'json');


                  }



                  $("#btn_inv").keyup(function(event) {
                    if (event.which == 13) {
                      event.preventDefault();
                      $('#date').focus();
                      $('#payment_add').modal('show');
                    }
                  });


                  $("#btn_inv").click(function() {
                    $('#payment_add').modal('show');
                    $('#inv_no').focus();
                  });


                  $(".modal_close").click(function() {
                    $('#payment_add').modal('hide');
                    $('#chq_dtlz').modal('hide');
                    $(':input[type="submit"]').prop('disabled', false);
                  });





                  $("#units").keyup(function(event) {
                    var units = $('#units').val();
                    var rate = $('#rate').val();

                    var amnt = units * rate;
                    $('#amount_txt').text(amnt.toFixed(2));
                    $('#amount').val(amnt.toFixed(2));
                  });

                  $("#rate").keyup(function(event) {
                    var units = $('#units').val();
                    var rate = $('#rate').val();

                    var amnt = units * rate;
                    $('#amount_txt').text(amnt.toFixed(2));
                    $('#amount').val(amnt.toFixed(2));
                  });

                  var itemArray = [];
                  var itemCopy = [];
                  var newitemArray = [];
                  $(document).on('click', '.btn_add', function(e) {
                    invoice_add();
                  });

                  function invoice_add() {
                    var date = $('#date').val();
                    var inv_no = $('#inv_no').val();
                    var desc = $('#desc').val();
                    var units = $('#units').val();
                    var rate = $('#rate').val();
                    var amount = $('#amount').val();
                    var item_code = $('#acc').val();
                    var item_search = $('#item_search').text();



                    itemArray.push({
                      'date': date,
                      'inv_no': inv_no,
                      'desc': desc,
                      'units': units,
                      'rate': rate,
                      'amount': amount,
                      'sup_lcode':item_code,
                      'supplier_name':item_search
                    });

                    loadCheckOutTbl(itemArray);

                    $("#date").val('');
                    $("#inv_no").val('');
                    $("#desc").val('');
                    $("#units").val('');
                    $("#rate").val('');
                    $("#amount").val('');
                    $('#payment_add').modal('hide');
                  }
                  function loadCheckOutTbl(itemArray) {
                    var type = $('#v_type').val();
                    $(".divIDClass").show();
                    var tableData = '';
                    $('#inv_dtl tbody').html('').append(tableData);

                    var l = itemArray.length;
                    var tot = 0;
                    for (var i = 0; i < l; i++) {
                      var index = (i + 1);
                      tot = parseFloat(tot) + parseFloat(itemArray[i]['amount']);
                      tableData += "<tr data-index='" + i + "'>"; 
                      tableData += "<td>" + index + "</td>";
                      tableData += "<td>" + itemArray[i]['date'] + "</td>";
                      tableData += "<td>" + itemArray[i]['inv_no'] + "</td>";
                      tableData += "<td>" + itemArray[i]['desc'] + "</td>";
                      tableData += "<td>" + itemArray[i]['units'] + "</td>";
                      tableData += "<td>" + itemArray[i]['rate'] + "</td>";

                      if (type == 2) {
                        tableData += "<td>" + Math.round(itemArray[i]['amount']) + "</td>";
                        tableData += "<td><input type='text' value='" + Math.round(itemArray[i]['amount']) + "' data-index='" + i + "' class='form-control edit_amnt' /></td>";
                        
                      } else {
                        tableData += "<td>" + Math.round(itemArray[i]['amount']) + "</td>";
                      }

        // Add checkbox only if there's more than one item
        if (l > 1) {
          tableData += "<td align='center'><button value='" + (i + 1) + "' class='btn btn-danger btn-sm btn_remove'><span class='bi bi-trash'></span></button></td>";
          tableData += "<td><input type='checkbox' class='row-checkbox btn_chck' data-amount='" + Math.round(itemArray[i]['amount']) + "' data-inv='" + itemArray[i]['inv_no'] + "' data-index='" + i + "' /></td>";
        } else {
          tableData += "<td align='center'><button value='" + (i + 1) + "' class='btn btn-danger btn-sm btn_remove'><span class='bi bi-trash'></span></button></td>";
            tableData += "<td></td>"; // No checkbox for single item
          }

          tableData += "</tr>";
        }

        $('#inv_dtl tbody').html('').append(tableData);
        $("#tot_amt").val(tot);

        $('#inv_dtl').on('keyup', '.edit_amnt', function() {
          var amount = parseFloat($(this).val());
          var index = $(this).data('index');
        });

    // Only enable the click event if there is more than one item
    if (l > 1) {
      $('#inv_dtl tbody').on('click', 'tr', function() {
        var rowIndex = $(this).data('index');
        var selectedItem = itemArray[rowIndex];
            itemCopy = [JSON.parse(JSON.stringify(selectedItem))]; // Create a copy
            console.log(itemCopy);
          });
    }else {
          itemCopy = itemArray; // Directly assign itemArray to itemCopy
          console.log(itemCopy);

        }
      }



      $(document).on('click', '.btn_chck', function(e) {
        var amount = $(this).attr('data-amount');
        var invoice_no = $(this).attr('data-inv');
        $("#tot_amt").val(amount);
        $("#invoice_no").val(invoice_no);

      });






      $(document).on('click', '.btn_remove', function(e) {
        var code = $(this).val();
        var removeIndex = itemArray.map(function(item) {
          return item.inv_no;
        }).indexOf(code);

        itemArray.splice(removeIndex, 1);

        loadCheckOutTbl(itemArray);
      });


      /******************************** SAVE ***************************/
      $("#save").click(function() {
        var p_method = $("input[name='p_method']:checked").val();

        var ref = $('#ref_no').val();
        $('#chq_no').val(ref);
        $('#bank').focus();
        $('#chq_dtlz').modal('show');


      });


      /******************************** CHANGE ***************************/

      $("#v_type").change(function() {

        var type = $("#v_type").val();


        window.location.href = "<?php echo base_url() ?>accounts/voucher_payments/" + type;
      });





      $("#save").keyup(function(event) {
        if (event.which == 13) {
          event.preventDefault();
          var p_method = $("input[name='p_method']:checked").val();
          if (p_method == 'BT') {
            $('#bank').focus();
            $('#chq_dtlz').modal('show');

          } else {
            save_inv();
          }
        }
      });

      $("#btn_chq").click(function() {
        save_inv();
      });

      $("#btn_chq").keyup(function(event) {
        if (event.which == 13) {
          event.preventDefault();
          save_inv();
        }
      });

      function save_inv() {

        var menu = $("#menu").val();
        var acc = $("#acc").val();
        var acc_name = $("#acc_name").val();
        var acc_name = $("#acc_name").val();
        var p_method = $("input[name='p_method']:checked").val()
        var v_type = $("#v_type").val();
        var ref_no = $("#ref_no").val();
        var grnd_tot = $("#tot_amt").val();
        var div = $("#divi").val();
        var req = $("#req").val();
        var bank = $("#bank").val();
        var bank_name = $("#bank option:selected").text();
        var chq_date = $("#chq_date").val();
        var chq_no = $("#chq_no").val();
        var date1 = $("#date1").val();
        var inv_date = $("#date").val();
        var ref_supplier = $("#refference").val();
        var description = $("#desc").val();
        var invoice_no = $("#invoice_no").val();

                    // if(v_type = 1){
                    // newitemArray = itemArray; 
                    // }else {
                    //  newitemArray = itemCopy;
                    // }


                    if((p_method =='BT') && (bank == '')){
                      alert('Please Select the Bank');
                    }else{
                      $.post("<?php echo base_url() ?>accounts/voucher_payments/save", {
                        finalize: "data",
                        itemArray: itemCopy,
                        acc: acc,
                        acc_name: acc_name,
                        p_method: p_method,
                        ref_no: ref_no,
                        grnd_tot: grnd_tot,
                        div: div,
                        req: req,
                        bank: bank,
                        bank_name: bank_name,
                        chq_date: chq_date,
                        date1: date1,
                        chq_no: chq_no,
                        inv_date:inv_date,
                        ref_supplier:ref_supplier,
                        description:description,
                        v_type:v_type,
                        invoice_no:invoice_no
                      }, function(data) {
                        if (data.status === 200) {
                          Swal.fire({
                            icon: 'success',
                            title: 'Added Successfully!',
                            showConfirmButton: true
                          }).then((result) => {
                            if (result.isConfirmed) {

                              location.reload();
                            }
                          });
                        } else if ($.trim(data.status) === 'Error') {
                          Swal.fire({
                            icon: 'warning',
                            title: 'Error!',
                            text: 'Error occurred.',
                            showConfirmButton: false,
                            timer: 1500
                          });
                        } else {
                          Swal.fire({
                            icon: 'warning',
                            title: 'Oops...',
                            text: 'Something went wrong!',
                            showConfirmButton: false,
                            timer: 1500
                          });
                        }
                      }, "json")
                      .fail(function(jqXHR, textStatus, errorThrown) {
                    // Error handling for the AJAX request itself (like network error)
                    Swal.fire({
                      icon: 'error',
                      title: 'Error',
                      text: 'Failed to communicate with server.',
                      showConfirmButton: false,
                      timer: 1500
                    });
                  });
                    }




                  }
                </script>

              </body>

              </html>