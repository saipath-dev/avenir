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
                    <h4 class="p-title">Bank Reconcillation</h4>
                  </div>
                </div>

                <div class="row clearfix mt-3">
                  <div class="col-md-12">
                    <div class="card p-4">
                      <div class="body">
                        <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                          <div class="row">
                            <div class="col-md-4">
                              <div class="mb-3">
                                <label class="form-label" for="drange1">Start Date</label>
                                <input class="form-control txt_nav date" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" id="drange1" name="drange1" placeholder="Select Date" value="<?php if (!empty($date1)) {
                                                                                                                                                                                                            echo $date1;
                                                                                                                                                                                                          } else {
                                                                                                                                                                                                            echo date("Y-m-d");
                                                                                                                                                                                                          } ?>" />
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="mb-3">
                                <label class="form-label" for="drange2">End Date</label>
                                <input class="form-control txt_nav date" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" id="drange2" name="drange2" placeholder="Select Date" value="<?php if (!empty($date2)) {
                                                                                                                                                                                                            echo $date2;
                                                                                                                                                                                                          } else {
                                                                                                                                                                                                            echo date("Y-m-d");
                                                                                                                                                                                                          } ?>" />
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="mb-3">
                                <label class="form-label" for="supplier_name">Bank<font color="#FF0000"><strong>*</strong></font></label>
                                <select name="bank" id="bank" class="js-example-basic-single form-control" style="width: 100%;" required>
                                  <option value="">Select...</option>
                                  <?php
                                  foreach ($load_bank as $bank) {
                                    // $selected = $customer['ID'] == $ID ? 'selected' : '';
                                    $selected = '';
                                    echo '<option value="' . $bank['L_Code'] . '"' . $selected . '>' . $bank['PName'] . '</option>';
                                  }
                                  ?>
                                </select>
                              </div>

                            </div>
                            <div class="mb-3" style="padding-top: 10px; float:right;">
                              <button name="search" value="search_filter" class="btn btn-success w-100px me-5px btn_s"><i class="bi bi-search"></i>Search
                              </button>
                            </div>
                        </form>
                        <div style="float: left;">
                          <input type="checkbox" value="1" name="planned_checked" id="planned_checked">
                          <label>View Realised Entries</label>
                        </div>

                      </div>
                    </div>
                  </div>
                </div>


                <div class="row clearfix mt-3">
                  <div class="col-md-12">
                    <div class="card p-4">
                      <div class="body">
                        <div class="table-responsive">
                          <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                            <thead class="thead-dark">
                              <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Account Name</th>
                                <th id="rel"></th>
                              </tr>
                            </thead>
                            <tbody id="bank_tbl">

                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-xl-12">

                    <div class="panel panel-inverse" data-sortable-id="form-stuff-10">


                      <div class="panel-body">
                        <div class="row">
                          <div class="col-md-12">
                            <input type="hidden" name="menu" id="menu" value="">
                            <input type="hidden" name="date" id="date" value="">


                            <fieldset>
                              <div class="row" style="border: 1px solid black">

                                <div class="col-md-6 row" style="margin-bottom: 20px;margin-top: 10px;">
                                  <h4 class="text-center">Unrealised Value</h4>
                                  <div class="col-md-6">
                                    <label>Debit</label>
                                    <input type="text" name="unrel_dr_sum" id="unrel_dr_sum" class="form-control">
                                  </div>
                                  <div class="col-md-6">
                                    <label>Credit</label>
                                    <input type="text" name="unrel_cr_sum" id="unrel_cr_sum" class="form-control">
                                  </div>
                                </div>

                                <div class="col-md-6 row" style="margin-bottom: 20px;margin-top: 10px;">
                                  <h4 class="text-center">Realised Value</h4>
                                  <div class="col-md-6">
                                    <label>Debit</label>
                                    <input type="text" name="rel_cr_sum" id="rel_dr_sum" class="form-control">
                                  </div>
                                  <div class="col-md-6">
                                    <label>Credit</label>
                                    <input type="text" name="rel_cr_sum" id="rel_cr_sum" class="form-control">
                                  </div>
                                </div>



                              </div>
                              <br>

                              <div class="row" style="border: 1px solid black">

                                <div class="col-md-6 row" style="margin-bottom: 20px;margin-top: 10px;">

                                  <div class="col-md-6">
                                    <label>Unrealised Value(Debit - Credit)</label>
                                    <input type="text" name="unrel_dif" id="unrel_dif" class="form-control">
                                  </div>
                                  <div class="col-md-6">
                                    <label>Realised Value(Debit-Credit)</label>
                                    <input type="text" name="rel_dif" id="rel_dif" class="form-control">
                                  </div>
                                </div>

                                <div class="col-md-6 row" style="margin-bottom: 20px;margin-top: 10px;">

                                  <div class="col-md-6">
                                    <label>Statement Value</label>
                                    <input type="text" name="stmnt_val" id="stmnt_val" value="0" class="form-control">
                                  </div>
                                  <div class="col-md-6">
                                    <label>Difference(Statement-Realised)</label>
                                    <input type="text" name="dif" id="dif" class="form-control">
                                  </div>
                                </div>


                              </div>



                            </fieldset>
                            <button name="search" value="search_filter" class="btn btn-primary btn-sm checkbox-toggle btn_post" style="float: right; margin-top: 20px;display: none;"><i class="bi bi-reply"></i> Post Unrealised Entries</button>
                            <button class="btn btn-success btn-sm checkbox-toggle btn_print" style="float: right; margin-top: 20px; margin-right: 10px;display: none;"><i class="bi bi-printer"></i> Print</button>
                            <br>
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
    var branch_id = $("#branch_id").val();
    $('#branch').text($("#branch_id").val());

    $(document).ready(function() {
      var menu = $("#menu").val();
      $(".default-daterange").daterangepicker({
        opens: "right",
        format: "YYYY-MM-DD",
        separator: " to ",
        startDate: moment(),
        endDate: moment(),
      }, function(start, end) {
        var branch_id = $("#branch_id").val();
        $(".default-daterange input").val(start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD"));
        var date = start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD");

      });
    });




    var itemArray = [];
    var itemArray2 = [];

    $(".btn_s").click(function() {
      var menu = $("#menu").val();
      var date1 = $("#drange1").val();
      var date2 = $("#drange2").val();
      var bank = $("#bank").val();

      get_requested_item(date1, date2, bank, menu, flag = 0);




      get_realised_sum(date1, date2, bank, menu, flag = 0);
      get_unrealised_sum(date1, date2, bank, menu, flag = 1);


    });

    //  $(".btn_repost").click(function(){
    //   var menu = $("#menu").val();
    //   var date1 = $("#drange1").val();
    //   var date2 = $("#drange2").val();
    //   var bank = $("#bank").val();
    //   var lcode = $('#bank option:selected').attr('data-lcode');


    //   window.open("<?php echo base_url() ?>accounts/Con_bank_reconsilation/load_repost_data/" + menu + "/" + date1+"/"+date2+ "/" + bank+"/"+lcode,'_blank');


    // });


    $(".btn_print").click(function() {
      var menu = $("#menu").val();
      var date1 = $("#drange1").val();
      var date2 = $("#drange2").val();
      var bank = $("#bank").val();
      var stmnt_val = $("#stmnt_val").val();
      var lcode = $('#bank option:selected').attr('data-lcode');


      window.open("<?php echo base_url() ?>accounts/Con_bank_reconsilation/load_printview/" + menu + "/" + date1 + "/" + date2 + "/" + bank + "/" + lcode + "/" + stmnt_val, '_blank');


    });

    $(".btn_post").click(function() {
      var date1 = $("#drange1").val();
      var date2 = $("#drange2").val();
      var bank = $("#bank").val();
      var menu = $("#menu").val();

      if (itemArray.length > 0) {

      }

      AjaxURL = '<?php echo site_url("accounts/bank_reconcil/post_unrealised_entries") ?>';
      DataSet = {
        itemArray: itemArray,


      };

      $.ajax({
        url: AjaxURL,
        type: 'post',
        data: DataSet,
        dataType: 'json',
        success: function(data) {


          if (data.status == 'success') {


            window.location.href = "<?php echo base_url() ?>accounts/Con_bank_reconsilation/index/" + menu;

          } else if (data.status == 'data exists') {




          } else {

          }
        },
        error: function() {

          swal("", "Something went wrong! Please Try Again!", "warning");
        }
      });


    });


    $("#planned_checked").change(function() {
      if ($(this).prop('checked')) {

        var menu = $("#menu").val();
        var date1 = $("#drange1").val();
        var date2 = $("#drange2").val();
        var bank = $("#bank").val();

        get_requested_item_realised(date1, date2, bank, menu, flag = 1);
      } else {

        var menu = $("#menu").val();
        var date1 = $("#drange1").val();
        var date2 = $("#drange2").val();

        var bank = $("#bank").val();

        get_requested_item(date1, date2, bank, menu, flag = 0);

      }
    });


    function get_realised_sum(date1, date2, bank, menu, flag) {
      // alert('test');



      var tableData = '';
      var postData = {
        get_req_items: 'data',
        date1: date1,
        date2: date2,
        bank: bank,
        menu: menu,
        flag: flag
      };
      $.post("<?php echo base_url() ?>accounts/bank_reconcil/get_realised_sum", postData, function(data) {

        var x = 0;
        // $('#rel_dif').val('');


        $.each(data.result, function(index, data) {
          x++;




          $('#unrel_cr_sum').val(data.cr);
          $('#unrel_dr_sum').val(data.dr);


          var rel_val = data.dr - data.cr;
          $('#rel_dif').val(rel_val);



        });
      }, "json");

    }


    function get_unrealised_sum(date1, date2, bank, menu, flag) {
      // alert('test un');




      var tableData = '';
      var postData = {
        get_req_items: 'data',
        date1: date1,
        date2: date2,
        bank: bank,
        menu: menu,
        flag: flag
      };
      $.post("<?php echo base_url() ?>accounts/bank_reconcil/get_realised_sum", postData, function(data) {

        var x = 0;


        $.each(data.result, function(index, data) {
          x++;




          $('#rel_dr_sum').val(data.dr);
          $('#rel_cr_sum').val(data.cr);

          var unrel_val = data.dr - data.cr;

          $('#unrel_dif').val(unrel_val);


        });
      }, "json");

    }







    function get_requested_item(date1, date2, bank, menu, flag) {
      var tableData = '';
      var postData = {
        get_req_items: 'data',
        date1: date1,
        date2: date2,
        bank: bank,
        menu: menu,
        flag: flag
      };

      $.post("<?php echo base_url() ?>accounts/bank_reconcil/get_req_items", postData, function(data) {
        var itemArray = [];

        // Loop through the data result and push items into itemArray
        $.each(data.result, function(index, data) {
          itemArray.push({
            'ID': data.ID,
            'Date': data.Date,
            'Discription': data.Discription,
            'Dr': data.Dr,
            'Cr': data.Cr,
            'PName': data.PName,
            'lcode': data.L_Code
          });
        });

        // Call loadPaymentTbl after all items have been added
        loadPaymentTbl(itemArray, 0); // Use flag as the second argument

      }, "json");
    }


    function get_requested_item_realised(date1, date2, bank, menu, flag) {

      var itemArray2 = [];

      var tableData = '';
      var postData = {
        get_req_items: 'data',
        date1: date1,
        date2: date2,
        bank: bank,
        menu: menu,
        flag: flag
      };
      $.post("<?php echo base_url() ?>accounts/bank_reconcil/get_req_items", postData, function(data) {

        var x = 0;


        $.each(data.result, function(index, data) {
          x++;

          itemArray2.push({
            'ID': data.ID,
            'Date': data.Date,
            'Discription': data.Discription,
            'Dr': data.Dr,
            'Cr': data.Cr,
            'PName': data.PName,
            'lcode': data.L_Code
          });



        });
        loadPaymentTbl(itemArray2, flag = 1);
      }, "json");

    }



    function loadPaymentTbl(itemArray, flag) {
      // alert(flag);


      var tableData = '';
      $('#bank_tbl ').html('').append(tableData);

      if (flag == 1) {
        $('#rel ').text('Unrealise');
      } else {
        $('#rel ').text('Realise');
      }


      var l = itemArray.length;
      // alert(l);
      for (var i = 0; i < l; i++) {

        tableData += "<tr>";

        tableData += "<td>" + itemArray[i]['ID'] + "</td>";
        tableData += "<td>" + itemArray[i]['Date'] + "</td>";
        tableData += "<td>" + itemArray[i]['Discription'] + "</td>";
        tableData += "<td>" + itemArray[i]['Dr'] + "</td>";
        tableData += "<td>" + itemArray[i]['Cr'] + "</td>";
        tableData += "<td>" + itemArray[i]['PName'] + "</td>";
        tableData += "<td class='text text-right'><button class='btn btn-sm btn-success btn_realize' data-index='" + i + "' data-flag='" + flag + "' data-lcode='" + itemArray[i]['lcode'] + "' value='" + itemArray[i]['ID'] + "'><i class='bi bi-check'></i></button></td>";
        tableData += "</tr>";


      }


      $('#bank_tbl ').html('').append(tableData);




    }

    $(document).on('click', '.btn_realize', function(e) {
      e.preventDefault();

      var id = $(this).val();
      var lcode = $(this).attr('data-lcode');
      var flag = $(this).attr('data-flag');
      var index = $(this).attr('data-index');






      AjaxURL = '<?php echo base_url("accounts/bank_reconcil/update") ?>';
      DataSet = {
        id: id,
        flag: flag

      };

      $.ajax({
        url: AjaxURL,
        type: 'post',
        data: DataSet,
        dataType: 'json',
        success: function(response) {


          if (response.status == 200) {


            var menu = $("#menu").val();
            var date1 = $("#drange1").val();
            var date2 = $("#drange2").val();
            var bank = $("#bank").val();



            if (flag == 0) {
              itemArray.splice(index, 1);
              console.log(itemArray);
              loadPaymentTbl(itemArray, flag);
            } else if (flag == 1) {
              itemArray2.splice(index, 1);
              loadPaymentTbl(itemArray2, flag);
            }

            // alert(itemArray.length);





            // loadPaymentTbl(itemArray);
            get_realised_sum(date1, date2, bank, menu, flag = 0);
            get_unrealised_sum(date1, date2, bank, menu, flag = 1);

          } else if (response.status == 'data exists') {




          } else {

          }
        },
        error: function() {

          swal("", "Something went wrong! Please Try Again!", "warning");
        }
      });











    });






    $(".modal_close").click(function() {
      $('#view_data').modal('hide');
      $(':input[type="submit"]').prop('disabled', false);
    });
  </script>


</body>

</html>