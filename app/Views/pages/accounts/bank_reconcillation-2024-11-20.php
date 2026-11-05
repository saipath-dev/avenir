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
                                <h5 class="mb-3">Payments </h5>
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
                                <h5 class="mb-3">Deposit </h5>
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
      var itemArray2 = [];
      var depositArray = [];
      var opening_balance = 0;
      var closing_balance = 0;
      var currentBalance = 0;

      $(".btn_s").click(function() {
        var menu = $("#menu").val();
        var date1 = $("#drange1").val();
        var date2 = $("#drange2").val();
        var bank = $("#bank").val();

        get_requested_item(date1, date2, bank, menu, flag = 0);

        get_realised_sum(date1, date2, bank, menu, flag = 0);
        get_unrealised_sum(date1, date2, bank, menu, flag = 1);


      });




      $(".btn_print").click(function() {
        var menu = $("#menu").val();
        var date1 = $("#drange1").val();
        var date2 = $("#drange2").val();
        var bank = $("#bank").val();
      // var stmnt_val = $("#stmnt_val").val();
      // var lcode = $('#bank option:selected').attr('data-lcode');


      window.open("<?php echo base_url() ?>accounts/bank_reconcil/load_printview?date1=" + date1 + "&date2=" + date2 + "&bank=" + bank, '_blank');



    });


      $(".btn_print_summary").click(function() {
        var menu = $("#menu").val();
        var date1 = $("#drange1").val();
        var date2 = $("#drange2").val();
        var bank = $("#bank").val();
      // var stmnt_val = $("#stmnt_val").val();
      // var lcode = $('#bank option:selected').attr('data-lcode');


      window.open("<?php echo base_url() ?>accounts/bank_reconcil/load_details_print?date1=" + date1 + "&date2=" + date2 + "&bank=" + bank, '_blank');



    });


      function get_realised_sum(date1, date2, bank, menu, flag) {


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




            $('#unrel_cr_sum').val(data.cr);
            $('#unrel_dr_sum').val(data.dr);


            var rel_val = data.dr - data.cr;
            $('#unrel_dif').val(rel_val);



          });
        }, "json");

      }


      function get_unrealised_sum(date1, date2, bank, menu, flag) {


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

            $('#rel_dif').val(unrel_val);


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
                'flag': data.bankst_flag
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
                'flag': data.bankst_flag
              });

            }


          });


          loadPaymentTbl(itemArray, 0);
          loadPaymentTbl2(depositArray, 0);

        }, "json");

        $.post("<?php echo base_url() ?>accounts/bank_reconcil/get_opening_balance", postData, function(data) {
          if (data.opening_balance !== undefined) {
          // $('#unrel_dif').val(data.opening_balance);
          // alert(data.opening_balance);
          opening_balance = data.opening_balance;
        } else {
          console.error("No opening balance data received.");
        }
      }, "json");

      }





      function loadPaymentTbl2(depositArray, flag) {
        var tableData = '';
      $('#bank_tbl2').html(''); // Clear the table

      var currentBalance = opening_balance; // Initialize current balance

      tableData += "<tr style='font-weight: bold;'>";
      tableData += "<td colspan='4' class='text-bold'>Opening Balance</td>";
      tableData += "<td class='text text-right'>" + opening_balance + "</td>";
      tableData += "<td></td>"; // Placeholder for the last column
      tableData += "</tr>";

      var l = depositArray.length;

      for (var i = 0; i < l; i++) {
        // Subtract Cr from the current balance for this row
        currentBalance -= Number(depositArray[i]['Cr']);
        var rowColor = (depositArray[i]['flag'] == 1) ? "style='background-color: #34b30a3d;'" : "";

        tableData += "<tr " + rowColor + ">";
        tableData += "<td>" + depositArray[i]['ID'] + "</td>";
        tableData += "<td>" + depositArray[i]['Date'] + "</td>";
        tableData += "<td>" + depositArray[i]['Discription'] + "</td>";
        tableData += "<td>" + depositArray[i]['Cr'] + "</td>";
        tableData += "<td class='text text-right'>" + currentBalance.toFixed(2) + "</td>"; // Display the current balance
        tableData += "<td class='text text-right'><button class='btn btn-sm btn-success btn_realize' data-index='" + i + "' data-flag='" + depositArray[i]['flag'] + "' data-lcode='" + depositArray[i]['lcode'] + "' value='" + depositArray[i]['ID'] + "'><i class='bi bi-check'></i></button></td>";
        tableData += "</tr>";
      }

      $('#bank_tbl2').html('').append(tableData); // Append all table data to the table body

      return currentBalance; // Return the current balance
    }

    function loadPaymentTbl(itemArray, flag) {
      var tableData = '';
      $('#bank_tbl').html(''); // Clear the table

      var closing_balance = 0; // Initialize closing balance
      var l = itemArray.length;
      var rowColor = "";

      // Call loadPaymentTbl2 to get currentBalance
      var currentBalance = loadPaymentTbl2(depositArray, flag); // Assuming depositArray is accessible here

      for (var i = 0; i < l; i++) {
        closing_balance += Number(itemArray[i]['Dr']);

        
        rowColor = (itemArray[i]['flag'] == 1) ? "style='background-color: #34b30a3d;'" : "";

        tableData += "<tr " + rowColor + ">";
        tableData += "<td>" + itemArray[i]['ID'] + "</td>";
        tableData += "<td>" + itemArray[i]['Date'] + "</td>";
        tableData += "<td>" + itemArray[i]['Discription'] + "</td>";
        tableData += "<td>" + itemArray[i]['Dr'] + "</td>";
        tableData += "<td class='text text-right'>" + closing_balance.toFixed(2) + "</td>";

        tableData += "<td class='text text-right'><button class='btn btn-sm btn-success btn_realize' data-index='" + i + "' data-flag='" + itemArray[i]['flag'] + "' data-lcode='" + itemArray[i]['lcode'] + "' value='" + itemArray[i]['ID'] + "'><i class='bi bi-check'></i></button></td>";
        tableData += "</tr>";
      }

      // Add a closing balance row at the end of the table
      tableData += "<tr style='font-weight: bold;'>";
      tableData += "<td colspan='3' class='text-right'>Closing Balance</td>";
      tableData += "<td></td>"; // Placeholder for the 'Dr' column
      tableData += "<td class='text text-right'>" + ((Number(currentBalance) + Number(closing_balance)).toFixed(2)) + "</td>";
      // Display the closing balance
      tableData += "<td></td>"; // Placeholder for the button column
      tableData += "</tr>";

      $('#bank_tbl').html('').append(tableData); // Append all table data to the table body
    }




    $(document).on('click', '.btn_realize', function(e) {
      e.preventDefault();

      var id = $(this).val();
      var lcode = $(this).attr('data-lcode');
      var flag = $(this).attr('data-flag');
      var index = $(this).attr('data-index');
      var $button = $(this);

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

            // $button.closest('tr').css('background-color', 'lightgreen');
            var menu = $("#menu").val();
            var date1 = $("#drange1").val();
            var date2 = $("#drange2").val();
            var bank = $("#bank").val();
            // Toggle the data-flag attribute value
            var newFlag = (flag === '1') ? '0' : '1'; // Check if the current value is '1' or '0'
            $button.attr('data-flag', newFlag);

            // Change the background color based on the new flag value
            if (newFlag === '1') {
              $button.closest('tr').css('background-color', '#34b30a3d'); // Set to green
            } else {
              $button.closest('tr').css('background-color', ''); // Reset to default color
            }



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
      var date1 = $("#drange1").val();
      var date2 = $("#drange2").val();
      var bank = $("#bank").val();
      var menu = $("#menu").val();


      var AjaxURL = '<?php echo base_url("accounts/bank_reconcil/post_unrealised_entries") ?>';
      var DataSet = {
        itemArray: itemArray,
        depositArray: depositArray
      };
      console.log(DataSet);


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
      var date1 = $("#drange1").val();
      var date2 = $("#drange2").val();
      var bank = $("#bank").val();
      var menu = $("#menu").val();

      var postData = {
        get_post_data: 'data',
        date1: date1,
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
        $('#dif').val(difference);

      });
    });
  </script>


</body>

</html>