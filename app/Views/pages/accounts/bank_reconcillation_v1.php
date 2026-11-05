<?= view('layouts/head') ?>


<body>

  <!-- Begin page -->
  <div id="layout-wrapper">

    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <style type="text/css">
      .table-responsive {
        max-height: 400px;
        /* Set the max height for your table */
        overflow-y: auto;
        /* Enable vertical scrolling */
      }

      .table-responsive::-webkit-scrollbar {
        width: 8px;
        /* Width of the scrollbar */
      }

      .table-responsive::-webkit-scrollbar-thumb {
        background: #888;
        /* Color of the scrollbar */
        border-radius: 4px;
        /* Rounded corners for the scrollbar */
      }

      .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #555;
        /* Darker scrollbar on hover */
      }


      .modal-content {
        border-radius: 10px;
        /* Rounded corners */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        /* Subtle shadow */
      }


      .table th,
      .table td {
        vertical-align: middle;
        /* Center content vertically */
      }

      .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.1);
        /* Light blue on hover */
      }

      .modal-header {
        background-color: #f8f9fa;
        /* Light background for header */
        border-bottom: 1px solid #dee2e6;
        /* Light border at bottom */
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
                    <h4 class="p-title">Bank Reconcillation</h4>
                  </div>
                </div>

                <div class="row clearfix mt-3">
                  <div class="col-md-12">
                    <div class="card p-4">
                      <div class="body">
                        <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                          <div class="row">

                            <div class="col-md-6">
                              <div class="mb-3">
                                <label class="form-label" for="drange2">End Date</label>
                                <input class="form-control txt_nav date" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" id="drange2" name="drange2" placeholder="Select Date"
                                  value="<?php
                                          if (!empty($date2)) {
                                            echo $date2;
                                          } else {
                                            // Calculate the last date of the current month
                                            echo (new DateTime('last day of this month'))->format('Y-m-d');
                                          }
                                          ?>" />
                              </div>
                            </div>

                            <div class="col-md-6">
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
                              <button type="button" class="btn btn btn-info w-100px me-5px btn_repost" data-bs-toggle='modal' data-bs-target='#hotelreservationModal'><i class="bi bi-reply"></i> View Posted Unrealized Entries</button>
                            </div>
                        </form>
                        <div style="float: left;display: none;">
                          <input type="checkbox" value="1" name="planned_checked" id="planned_checked">
                          <label>View Realised Entries</label>
                        </div>

                      </div>
                    </div>
                  </div>
                </div>

                <div class="row clearfix mt-3">
                  <div class="col-md-6">
                    <div class="card p-4">
                      <div class="body">
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                          <div class="d-flex justify-content-end mb-3">
                            <input type="text" id="searchInput2" placeholder="Search..." class="form-control w-25" onkeyup="searchTable2()">
                          </div>
                          <h5 class="mb-3">Payments <span id="payments_count" class="badge bg-primary">0</span></h5>
                          <div class="d-flex justify-content-end mb-3">
                            <div class="form-check form-switch">
                              <input class="form-check-input toggleSwitch" type="checkbox" id="toggleSwitch" name="toggleSwitch" value="0">
                            </div>
                          </div>
                          <table class="table table-hover dataTable table-custom align-middle">
                            <thead class="thead-dark">
                              <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Description</th>
                                <!-- <th>Debit</th> -->
                                <th>Credit</th>
                                <th>Balance</th>
                                <!-- <th>Account Name</th> -->
                                <th id="rel"></th>
                              </tr>
                            </thead>
                            <tbody id="bank_tbl2">
                              <!-- Table data will be inserted here -->
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="card p-4">
                      <div class="body">
                        <div class="table-responsive">
                          <div class="d-flex justify-content-end mb-3">
                            <input type="text" id="searchInput" placeholder="Search..." class="form-control w-25" onkeyup="searchTable()">
                          </div>
                          <h5 class="mb-3">Deposit <span id="deposits_count" class="badge bg-primary">0</span></h5>
                          <div class="d-flex justify-content-end mb-3">
                            <div class="form-check form-switch">
                              <input class="form-check-input toggleSwitch" type="checkbox" id="toggleSwitch1" name="toggleSwitch1" value="0">
                            </div>
                          </div>
                          <table class="table table-hover dataTable table-custom align-middle">
                            <thead class="thead-dark">
                              <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Debit</th>
                                <th>Balance</th>
                                <!-- <th>Credit</th> -->
                                <!-- <th>Account Name</th> -->
                                <th id="rel"></th>
                              </tr>
                            </thead>
                            <tbody id="bank_tbl">
                              <!-- Table data will be inserted here -->
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
                                  <div class="col-md-4">
                                    <label>Debit</label>
                                    <input type="text" name="unrel_dr_sum" id="unrel_dr_sum" class="form-control">
                                  </div>
                                  <div class="col-md-4">
                                    <label>Credit</label>
                                    <input type="text" name="unrel_cr_sum" id="unrel_cr_sum" class="form-control">
                                  </div>

                                  <div class="col-md-4">
                                    <label>Unrealised Balance</label>
                                    <input type="text" name="unrel_dif" id="unrel_dif" class="form-control">
                                  </div>
                                </div>

                                <div class="col-md-6 row" style="margin-bottom: 20px;margin-top: 10px;">
                                  <h4 class="text-center">Realised Value</h4>
                                  <div class="col-md-4">
                                    <label>Debit</label>
                                    <input type="text" name="rel_cr_sum" id="rel_dr_sum" class="form-control">
                                  </div>
                                  <div class="col-md-4">
                                    <label>Credit</label>
                                    <input type="text" name="rel_cr_sum" id="rel_cr_sum" class="form-control">
                                  </div>
                                  <div class="col-md-4">
                                    <label>Realised Balance</label>
                                    <input type="text" name="rel_dif" id="rel_dif" class="form-control">
                                  </div>
                                </div>



                              </div>
                              <br>

                              <div class="row" style="border: 1px solid black">
                                <!-- 
                                <div class="col-md-6 row" style="margin-bottom: 20px;margin-top: 10px;">

                                  <div class="col-md-6">
                                    <label>Unrealised Balance(Debit - Credit)</label>
                                    <input type="text" name="unrel_dif" id="unrel_dif" class="form-control">
                                  </div>
                                  <div class="col-md-6">
                                    <label>Realised Balance(Debit-Credit)</label>
                                    <input type="text" name="rel_dif" id="rel_dif" class="form-control">
                                  </div>
                                </div> -->

                                <div class="col-md-12 row" style="margin-bottom: 20px;margin-top: 10px;">

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
                            <br>
                            <!-- <div class="row"> -->
                            <button name="search" value="search_filter" class="btn btn-primary btn-sm checkbox-toggle btn_post" style="float: right;"><i class="bi bi-reply"></i> Post Unrealised Entries</button> &nbsp;&nbsp;&nbsp; <button class="btn btn-success btn-sm checkbox-toggle btn_print" style="float: right; margin-right: 10px;"><i class="bi bi-printer"></i> Print Details</button>&nbsp;&nbsp;&nbsp; <button class="btn btn-success btn-sm checkbox-toggle btn_print_summary" style="float: right; margin-right: 10px;"><i class="bi bi-printer"></i> Print Summary</button>
                            <button class="btn btn-warning btn-sm checkbox-toggle btn_reconcil" style="float: right; margin-right: 10px;"><i class="bi bi-journal-check"></i> Reconciliation</button>
                            <button class="btn btn-info btn-sm checkbox-toggle btn_export_excel" style="float: right; margin-right: 10px;"><i class="bi bi-file-earmark-excel"></i> Export to Excel</button>
                            <!-- <br> -->
                            <!-- <br> -->
                            <!--          <button   type="button" class="btn btn btn-info btn-sm btn_repost" data-bs-toggle='modal' data-bs-target='#hotelreservationModal'  style="float: right;"  ><i class="bi bi-reply"></i> Repost</button> -->


                            <!-- </div> -->
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


  <div class="modal fade" id="hotelreservationModal" tabindex="-1" aria-labelledby="hotelreservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- Increase width using modal-lg -->
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="hotelreservationModalLabel">Details</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Table Section -->
          <table class="table table-striped table-hover mt-3"> <!-- Added table-hover for better visual feedback -->
            <thead>
              <tr>
                <th>ID</th>
                <th>Description</th>
                <th>Date</th>
                <th>Amount</th>
              </tr>
            </thead>
            <tbody id="table_body">
              <!-- Dynamic rows will be added here -->
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-info btn-sm repost"><i class="bi bi-reply"></i> Repost</button>
        </div>
      </div>
    </div>
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
    var depositArray = [];
    var reconsilArray = []; // chethana
    var opening_balance = 0;
    var closing_balance = 0;
    var currentBalance = 0;

    $(".btn_s").click(function() {
      var menu = $("#menu").val();
      // var date1 = $("#drange1").val();
      var date2 = $("#drange2").val();
      var bank = $("#bank").val();

      get_requested_item(date2, bank, menu, flag = 0);

      get_realised_sum(date2, bank, menu, flag = 0);
      get_unrealised_sum(date2, bank, menu, flag = 1);


    });

    $(document).on('change', '#toggleSwitch', function() {
      let val = $(this).val();
      let new_val = val == 1 ? 0 : 1;

      $("#toggleSwitch").val(new_val);

      depositArray.sort((a, b) => {
        // Convert `bank` properties to strings for comparison
        let bankA = a.bank ? String(a.bank) : '';
        let bankB = b.bank ? String(b.bank) : '';

        if (new_val == 0) {
          // Ascending order
          return bankA.localeCompare(bankB);
        } else {
          // Descending order
          return bankB.localeCompare(bankA);
        }
      });

      loadPaymentTbl2(depositArray, 0);


    });

    $(document).on('change', '#toggleSwitch1', function() {
      let val = $(this).val();
      let new_val = val == 1 ? 0 : 1;

      $("#toggleSwitch1").val(new_val);

      itemArray.sort((a, b) => {
        // Convert `bank` properties to strings for comparison
        let bankA = a.bank ? String(a.bank) : '';
        let bankB = b.bank ? String(b.bank) : '';

        if (new_val == 0) {
          // Ascending order
          return bankA.localeCompare(bankB);
        } else {
          // Descending order
          return bankB.localeCompare(bankA);
        }
      });

      loadPaymentTbl(itemArray, 0);

    });




    $(".btn_print").click(function() {
      var menu = $("#menu").val();
      var date1 = $("#drange1").val();
      var date2 = $("#drange2").val();
      var bank = $("#bank").val();
      // var stmnt_val = $("#stmnt_val").val();
      // var lcode = $('#bank option:selected').attr('data-lcode');


      window.open("<?php echo base_url() ?>accounts/bank_reconcil/load_printview?date2=" + date2 + "&bank=" + bank, '_blank');



    });


    $(".btn_print_summary").click(function() {
      var menu = $("#menu").val();
      var date1 = $("#drange1").val();
      var date2 = $("#drange2").val();
      var bank = $("#bank").val();
      // var stmnt_val = $("#stmnt_val").val();
      // var lcode = $('#bank option:selected').attr('data-lcode');


      window.open("<?php echo base_url() ?>accounts/bank_reconcil/load_details_print?date2=" + date2 + "&bank=" + bank, '_blank');



    });


    function get_realised_sum(date2, bank, menu, flag) {


      var tableData = '';
      var postData = {
        get_req_items: 'data',
        // date1: date1,
        date2: date2,
        bank: bank,
        menu: menu,
        flag: flag
      };
      $.post("<?php echo base_url() ?>accounts/bank_reconcil/get_realised_sum", postData, function(data) {

        var x = 0;

        $.each(data.result, function(index, data) {
          x++;



          $('#unrel_cr_sum').val(parseFloat(data.cr).toFixed(2));
          $('#unrel_dr_sum').val(parseFloat(data.dr).toFixed(2));



          var rel_val = data.dr - data.cr;
          $('#unrel_dif').val(parseFloat(rel_val).toFixed(2));




        });
      }, "json");

    }


    function get_unrealised_sum(date2, bank, menu, flag) {


      var tableData = '';
      var postData = {
        get_req_items: 'data',
        // date1: date1,
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

          $('#rel_dif').val(unrel_val.toFixed(2));


        });
      }, "json");

    }





    function get_requested_item(date2, bank, menu, flag) {

      var postData2 = {
        get_opening_balance: 'data',
        // date1: date1,
        date2: date2,
        bank: bank

      };


      $.post("<?php echo base_url() ?>accounts/bank_reconcil/get_opening_balance", postData2, function(data) {
        if (data.opening_balance !== undefined) {
          // $('#unrel_dif').val(data.opening_balance);
          // alert(data.opening_balance);
          opening_balance = data.opening_balance;
        } else {
          console.error("No opening balance data received.");
        }
      }, "json");


      var tableData = '';
      var postData = {
        get_req_items: 'data',
        // date1: date1,
        date2: date2,
        bank: bank,
        menu: menu,
        flag: flag,

      };

      $.post("<?php echo base_url() ?>accounts/bank_reconcil/get_req_items", postData, function(data) {
        itemArray = [];
        depositArray = [];


        $.each(data.result, function(index, data) {


          if (data.Dr > 0) {
            itemArray.push({
              'ID': data.ID,
              'Date': data.Date,
              'Discription': data.Discription,
              'Dr': data.Dr,
              'Cr': data.Cr,
              'PName': data.PName,
              'lcode': data.L_Code,
              'flag': data.bankst_flag,
              'bank': data.bank_rec, //chethana

            });

          } else if (data.Cr > 0) {
            depositArray.push({
              'ID': data.ID,
              'Date': data.Date,
              'Discription': data.Discription,
              'Dr': data.Dr,
              'Cr': data.Cr,
              'PName': data.PName,
              'lcode': data.L_Code,
              'flag': data.bankst_flag,
              'bank': data.bank_rec, //chethana

            });

          }


        });


        loadPaymentTbl(itemArray, 0);
        loadPaymentTbl2(depositArray, 0);

      }, "json");





    }





    // Update table rendering to include checkboxes
    function loadPaymentTbl2(depositArray, flag) {
      var tableData = '';
      $('#bank_tbl2').html(''); // Clear the table
      var currentBalance = opening_balance; // Initialize current balance
      var l = depositArray.length;

      for (var i = 0; i < l; i++) {
        currentBalance -= Number(depositArray[i]['Cr']);
        var rowColor = (depositArray[i]['bank'] == 1) ? "style='background-color: #34b30a3d;'" : "";
        tableData += "<tr " + rowColor + ">";
        tableData += "<td>" + depositArray[i]['ID'] + "</td>";
        tableData += "<td>" + depositArray[i]['Date'] + "</td>";
        tableData += "<td>" + depositArray[i]['Discription'] + "</td>";
        tableData += "<td>" + depositArray[i]['Cr'] + "</td>";
        tableData += "<td class='text text-right'>" + currentBalance.toFixed(2) + "</td>";
        tableData += "<td class='text text-right'><button class='btn btn-sm btn-success btn_realize' data-index='" + i + "' data-flag='" + depositArray[i]['flag'] + "' data-lcode='" + depositArray[i]['lcode'] + "' value='" + depositArray[i]['ID'] + "' data-bank='" + depositArray[i]['bank'] + "'><i class='bi bi-check'></i></button></td>";
        tableData += "</tr>";
      }

      $('#bank_tbl2').html('').append(tableData);
      $('#payments_count').text(l);
      return currentBalance;
    }

    function loadPaymentTbl(itemArray, flag) {
      var tableData = '';
      $('#bank_tbl').html('');
      var closing_balance = 0;
      var l = itemArray.length;
      var rowColor = "";
      var currentBalance = loadPaymentTbl2(depositArray, flag);

      for (var i = 0; i < l; i++) {
        closing_balance += Number(itemArray[i]['Dr']);
        rowColor = (itemArray[i]['bank'] == 1) ? "style='background-color: #34b30a3d;'" : "";
        tableData += "<tr " + rowColor + ">";
        tableData += "<td>" + itemArray[i]['ID'] + "</td>";
        tableData += "<td>" + itemArray[i]['Date'] + "</td>";
        tableData += "<td>" + itemArray[i]['Discription'] + "</td>";
        tableData += "<td>" + itemArray[i]['Dr'] + "</td>";
        tableData += "<td class='text text-right'>" + closing_balance.toFixed(2) + "</td>";
        tableData += "<td class='text text-right'><button class='btn btn-sm btn-success btn_realize' data-index='" + i + "' data-flag='" + itemArray[i]['flag'] + "' data-lcode='" + itemArray[i]['lcode'] + "' value='" + itemArray[i]['ID'] + "' data-bank='" + itemArray[i]['bank'] + "'><i class='bi bi-check'></i></button></td>";
        tableData += "</tr>";
      }

      tableData += "<tr style='font-weight: bold;'>";
      tableData += "<td colspan='3' class='text-right'>Ending Balance</td>";
      tableData += "<td class='text text-right'>" + opening_balance + "</td>";
      tableData += "<td></td><td></td>";
      tableData += "</tr>";

      $('#bank_tbl').html('').append(tableData);
      $('#deposits_count').text(l);
    }

    // Single record finalize logic
    $(document).on('click', '.btn_realize', function(e) {
      e.preventDefault();

      var id = $(this).val();
      var bank_flg = $(this).attr('data-bank');
      var flag = $(this).attr('data-flag');
      var lcode = $(this).attr('data-lcode');

      var index = $(this).attr('data-index');
      var $button = $(this);


      AjaxURL = '<?php echo base_url("accounts/bank_reconcil/update") ?>';
      DataSet = {
        id: id,
        flag: flag,
        bank: bank_flg
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

            var newFlag = (bank_flg === '1') ? '0' : '1';
            $button.attr('data-bank', newFlag);

            if (newFlag === '1') {
              $button.closest('tr').css('background-color', '#34b30a3d');
            } else {
              $button.closest('tr').css('background-color', '');
            }

            get_realised_sum(date2, bank, menu, flag = 0);
            get_unrealised_sum(date2, bank, menu, flag = 1);
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




    function searchTable() {
      // Get the input value and convert it to lowercase for case-insensitive search
      var input = document.getElementById("searchInput").value.toLowerCase();
      var table = document.getElementById("bank_tbl");
      var rows = table.getElementsByTagName("tr");

      // Loop through all table rows, and hide those that don't match the search query
      for (var i = 0; i < rows.length; i++) {
        var cells = rows[i].getElementsByTagName("td");
        var found = false;

        // Loop through each cell in the row
        for (var j = 0; j < cells.length; j++) {
          if (cells[j]) {
            // Check if the cell text includes the search query
            if (cells[j].textContent.toLowerCase().indexOf(input) > -1) {
              found = true;
              break; // Stop searching in this row
            }
          }
        }

        // Show or hide the row based on the search result
        rows[i].style.display = found ? "" : "none";
      }
    }

    function searchTable2() {
      // Get the input value and convert it to lowercase for case-insensitive search
      var input = document.getElementById("searchInput2").value.toLowerCase();
      var table = document.getElementById("bank_tbl2");
      var rows = table.getElementsByTagName("tr");

      // Loop through all table rows, and hide those that don't match the search query
      for (var i = 0; i < rows.length; i++) {
        var cells = rows[i].getElementsByTagName("td");
        var found = false;

        // Loop through each cell in the row
        for (var j = 0; j < cells.length; j++) {
          if (cells[j]) {
            // Check if the cell text includes the search query
            if (cells[j].textContent.toLowerCase().indexOf(input) > -1) {
              found = true;
              break; // Stop searching in this row
            }
          }
        }

        // Show or hide the row based on the search result
        rows[i].style.display = found ? "" : "none";
      }
    }




    $(".btn_post").click(function() {
      // var date1 = $("#drange1").val();
      var date2 = $("#drange2").val();
      var bank = $("#bank").val();
      var menu = $("#menu").val();


      var AjaxURL = '<?php echo base_url("accounts/bank_reconcil/post_unrealised_entries") ?>';
      var DataSet = {
        itemArray: itemArray,
        depositArray: depositArray
      };
      console.log(itemArray);
      console.log(depositArray);


      $.ajax({
        url: AjaxURL,
        type: 'post',
        data: DataSet,
        dataType: 'json',
        success: function(data) {
          if (data.status == '200') {
            Swal.fire({
              icon: 'success',
              title: 'Success',
              text: data.message,
            }).then((result) => {
              if (result.isConfirmed) {
                location.reload();
              }
            });

          } else if (data.status == 'data exists') {
            swal("", "Data already exists!", "warning");
          } else {
            swal("", "Unexpected error occurred!", "error");
          }
        },
        error: function() {
          swal("", "Something went wrong! Please Try Again!", "warning");
        }
      });
    });

    $(".repost").click(function() {
      var date1 = $("#drange1").val();
      var date2 = $("#drange2").val();
      var bank = $("#bank").val();
      var menu = $("#menu").val();


      var AjaxURL = '<?php echo base_url("accounts/bank_reconcil/repost_unrealised_entries") ?>';
      var DataSet = {
        date1: date1,
        date2: date2,
        bank: bank,
        // menu: menu
      };
      // console.log(DataSet);


      $.ajax({
        url: AjaxURL,
        type: 'post',
        data: DataSet,
        dataType: 'json',
        success: function(data) {
          if (data.status == '200') {
            // window.location.href = "<?php echo base_url() ?>accounts/Con_bank_reconsilation/index/" + menu;
            Swal.fire({
              icon: 'success',
              title: 'Success',
              text: data.message,
            }).then((result) => {
              if (result.isConfirmed) {
                location.reload();
              }
            });
          } else if (data.status == 'data exists') {
            swal("", "Data already exists!", "warning");
          } else {
            swal("", "Unexpected error occurred!", "error");
          }
        },
        error: function() {
          swal("", "Something went wrong! Please Try Again!", "warning");
        }
      });
    });


    $(".btn_repost").click(function() {
      // var date1 = $("#drange1").val();
      var date2 = $("#drange2").val();
      var bank = $("#bank").val();
      var menu = $("#menu").val();

      var postData = {
        get_post_data: 'data',
        // date1: date1,
        date2: date2,
        bank: bank,
        menu: menu
      };

      $.post("<?php echo base_url() ?>accounts/bank_reconcil/get_post_data", postData, function(data) {
        var tableData = '';
        $('#table_body').html('').append(tableData);

        $.each(data.result, function(index, item) {
          tableData += "<tr class='clickable-row' data-id='" + item.ID + "'>";
          tableData += "<td>" + item.ID + "</td>";
          tableData += "<td>" + item.Date + "</td>";
          tableData += "<td>" + item.Discription + "</td>";
          tableData += "<td>" + item.Cr + "</td>";
          tableData += "<td>" + item.Dr + "</td>";
          tableData += "</tr>";
        });

        $('#table_body').html('').append(tableData);

        // Add click event listener for rows
        $('.clickable-row').click(function() {
          var rowId = $(this).data('id');
          // alert(rowId);


          var AjaxURL = '<?php echo base_url("accounts/bank_reconcil/repost_single_entry") ?>';
          var DataSet = {
            rowId: rowId
            // menu: menu
          };
          $.ajax({
            url: AjaxURL,
            type: 'post',
            data: DataSet,
            dataType: 'json',
            success: function(data) {
              if (data.status == '200') {
                Swal.fire({
                  icon: 'success',
                  title: 'Success',
                  text: data.message,
                }).then((result) => {
                  if (result.isConfirmed) {
                    location.reload();
                  }
                });

              } else if (data.status == 'data exists') {
                swal("", "Data already exists!", "warning");
              } else {
                swal("", "Unexpected error occurred!", "error");
              }
            },
            error: function() {
              swal("", "Something went wrong! Please Try Again!", "warning");
            }
          });
          // alert(rowId);

        });

      }, "json");
    });



    $(document).ready(function() {
      // Initialize the variable
      var stmnt_val = '';

      // Function to handle input changes
      $('#stmnt_val').on('input', function() {
        stmnt_val = $(this).val(); // Update stmnt_val with the current input value
        realised_bal = $('#rel_dif').val(); // Update stmnt_val with the current input value
        // alert(stmnt_val);// Log the updated value (for debugging)
        difference = stmnt_val - realised_bal;
        $('#dif').val(parseFloat(difference).toFixed(2));


      });
    });

    $(".btn_reconcil").click(function() {
      var date2 = $("#drange2").val();
      var bank = $("#bank").val();
      var menu = $("#menu").val();



      $.ajax({
        url: '<?php echo base_url(); ?>accounts/bank_reconcil/reconciliation',
        type: 'post',
        data: {
          date2: date2,
          bank: bank
        },
        dataType: 'json',
        success: function(data) {
          if (data.status == '200') {
            Swal.fire({
              icon: 'success',
              title: 'Success',
              text: data.message,
            }).then((result) => {
              if (result.isConfirmed) {
                get_requested_item(date2, bank, menu, flag = 0);

                get_realised_sum(date2, bank, menu, flag = 0);
                get_unrealised_sum(date2, bank, menu, flag = 1);
              }
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: JSON.stringify(data.errors),
            })
          }
        },
        error: function() {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: "Something went wrong! Please Try Again!",
          })
        }
      });



    });


    // Export to Excel function
    $(".btn_export_excel").click(function() {
      var date2 = $("#drange2").val();
      var bank = $("#bank option:selected").text();

      if (itemArray.length === 0 && depositArray.length === 0) {
        Swal.fire('No Data', 'Please search for records first before exporting.', 'warning');
        return;
      }

      // Create Excel HTML
      var excelContent = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
      excelContent += '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Bank Reconciliation</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
      excelContent += '<style>table {border-collapse: collapse;} th, td {border: 1px solid black; padding: 8px; text-align: left;} th {background-color: #4472C4; color: white; font-weight: bold;} .total-row {background-color: #D9E1F2; font-weight: bold;} .header-section {background-color: #E7E6E6; font-weight: bold;}</style>';
      excelContent += '</head><body>';

      // Title
      excelContent += '<h2>Bank Reconciliation Report</h2>';
      excelContent += '<p><strong>Bank:</strong> ' + bank + '</p>';
      excelContent += '<p><strong>End Date:</strong> ' + date2 + '</p>';
      excelContent += '<p><strong>Opening Balance:</strong> ' + parseFloat(opening_balance).toFixed(2) + '</p>';
      excelContent += '<br>';

      // Deposits Table
      excelContent += '<h3>Deposits</h3>';
      excelContent += '<table>';
      excelContent += '<thead><tr><th>ID</th><th>Date</th><th>Description</th><th>Debit</th><th>Balance</th><th>Status</th></tr></thead>';
      excelContent += '<tbody>';

      var depositTotal = 0;
      var depositBalance = parseFloat(opening_balance);

      for (var i = 0; i < itemArray.length; i++) {
        depositBalance += parseFloat(itemArray[i].Dr);
        var status = (itemArray[i].bank == 1) ? 'Realized' : 'Unrealized';
        excelContent += '<tr>';
        excelContent += '<td>' + itemArray[i].ID + '</td>';
        excelContent += '<td>' + itemArray[i].Date + '</td>';
        excelContent += '<td>' + itemArray[i].Discription + '</td>';
        excelContent += '<td style="text-align: right;">' + parseFloat(itemArray[i].Dr).toFixed(2) + '</td>';
        excelContent += '<td style="text-align: right;">' + depositBalance.toFixed(2) + '</td>';
        excelContent += '<td>' + status + '</td>';
        excelContent += '</tr>';
        depositTotal += parseFloat(itemArray[i].Dr);
      }

      excelContent += '<tr class="total-row">';
      excelContent += '<td colspan="3" style="text-align: right;"><strong>Total Deposits:</strong></td>';
      excelContent += '<td style="text-align: right;"><strong>' + depositTotal.toFixed(2) + '</strong></td>';
      excelContent += '<td colspan="2"></td>';
      excelContent += '</tr>';
      excelContent += '</tbody></table><br><br>';

      // Payments Table
      excelContent += '<h3>Payments</h3>';
      excelContent += '<table>';
      excelContent += '<thead><tr><th>ID</th><th>Date</th><th>Description</th><th>Credit</th><th>Balance</th><th>Status</th></tr></thead>';
      excelContent += '<tbody>';

      var paymentTotal = 0;
      var paymentBalance = parseFloat(opening_balance);

      for (var i = 0; i < depositArray.length; i++) {
        paymentBalance -= parseFloat(depositArray[i].Cr);
        var status = (depositArray[i].bank == 1) ? 'Realized' : 'Unrealized';
        excelContent += '<tr>';
        excelContent += '<td>' + depositArray[i].ID + '</td>';
        excelContent += '<td>' + depositArray[i].Date + '</td>';
        excelContent += '<td>' + depositArray[i].Discription + '</td>';
        excelContent += '<td style="text-align: right;">' + parseFloat(depositArray[i].Cr).toFixed(2) + '</td>';
        excelContent += '<td style="text-align: right;">' + paymentBalance.toFixed(2) + '</td>';
        excelContent += '<td>' + status + '</td>';
        excelContent += '</tr>';
        paymentTotal += parseFloat(depositArray[i].Cr);
      }

      excelContent += '<tr class="total-row">';
      excelContent += '<td colspan="3" style="text-align: right;"><strong>Total Payments:</strong></td>';
      excelContent += '<td style="text-align: right;"><strong>' + paymentTotal.toFixed(2) + '</strong></td>';
      excelContent += '<td colspan="2"></td>';
      excelContent += '</tr>';
      excelContent += '</tbody></table><br><br>';

      // Summary Section
      excelContent += '<h3>Summary</h3>';
      excelContent += '<table style="width: 50%;">';

      var unrel_dr = parseFloat($('#unrel_dr_sum').val() || 0);
      var unrel_cr = parseFloat($('#unrel_cr_sum').val() || 0);
      var unrel_dif = parseFloat($('#unrel_dif').val() || 0);

      var rel_dr = parseFloat($('#rel_dr_sum').val() || 0);
      var rel_cr = parseFloat($('#rel_cr_sum').val() || 0);
      var rel_dif = parseFloat($('#rel_dif').val() || 0);

      excelContent += '<tr class="header-section"><td colspan="2">Unrealized Value</td></tr>';
      excelContent += '<tr><td><strong>Debit:</strong></td><td style="text-align: right;">' + unrel_dr.toFixed(2) + '</td></tr>';
      excelContent += '<tr><td><strong>Credit:</strong></td><td style="text-align: right;">' + unrel_cr.toFixed(2) + '</td></tr>';
      excelContent += '<tr><td><strong>Unrealized Balance:</strong></td><td style="text-align: right;">' + unrel_dif.toFixed(2) + '</td></tr>';

      excelContent += '<tr class="header-section"><td colspan="2">Realized Value</td></tr>';
      excelContent += '<tr><td><strong>Debit:</strong></td><td style="text-align: right;">' + rel_dr.toFixed(2) + '</td></tr>';
      excelContent += '<tr><td><strong>Credit:</strong></td><td style="text-align: right;">' + rel_cr.toFixed(2) + '</td></tr>';
      excelContent += '<tr><td><strong>Realized Balance:</strong></td><td style="text-align: right;">' + rel_dif.toFixed(2) + '</td></tr>';

      var stmnt_val = parseFloat($('#stmnt_val').val() || 0);
      var dif = parseFloat($('#dif').val() || 0);

      excelContent += '<tr class="header-section"><td colspan="2">Statement</td></tr>';
      excelContent += '<tr><td><strong>Statement Value:</strong></td><td style="text-align: right;">' + stmnt_val.toFixed(2) + '</td></tr>';
      excelContent += '<tr><td><strong>Difference (Statement - Realized):</strong></td><td style="text-align: right;">' + dif.toFixed(2) + '</td></tr>';

      excelContent += '</table>';
      excelContent += '</body></html>';

      // Create download link
      var blob = new Blob([excelContent], {
        type: 'application/vnd.ms-excel'
      });

      var downloadLink = document.createElement("a");
      var url = URL.createObjectURL(blob);
      downloadLink.href = url;
      downloadLink.download = 'Bank_Reconciliation_' + bank.replace(/\s+/g, '_') + '_' + date2 + '.xls';

      document.body.appendChild(downloadLink);
      downloadLink.click();
      document.body.removeChild(downloadLink);
      URL.revokeObjectURL(url);

      Swal.fire('Success', 'Excel file has been downloaded successfully!', 'success');
    });
  </script>


</body>

</html>