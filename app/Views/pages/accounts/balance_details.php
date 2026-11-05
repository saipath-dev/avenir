<div class="modal fade" id="transactionsModal" tabindex="-1" aria-labelledby="transactionsModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h1 class="modal-title fs-5" id="transactionsModalLabel"></h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modal_body">
        <div id="h_trans_div" class="d-flex flex-column">
          <table id="h_trans_table">
            <thead>
              <tr>
                <th class="fw-normal fs-mons pb-2">Account Name</th>
                <th class="fw-normal fs-mons text-end pb-2">Debit</th>
                <th class="fw-normal fs-mons text-end pb-2">Credit</th>
                <th class="fw-normal fs-mons text-end pb-2">Balance</th>
              </tr>
            </thead>
            <tbody id="h_trans_tbody"></tbody>
          </table>
        </div>
        <div id="p_trans_div" class="d-flex flex-column">
          <a class="fs-mons text-primary fw-bold h_transactions mb-3" id="p_trans_back" href="#" data-hname="" data-date1="" data-date2="">
          </a>
            <table id="p_trans_table" class="d-none table table-striped table-bordered">
            <thead>
              <tr>
                <th class="fw-normal fs-mons pb-2">Date</th>
                <th class="fw-normal fs-mons pb-2">Tour</th>
                <th class="fw-normal fs-mons pb-2">Description</th>
                <th class="fw-normal fs-mons text-end pb-2">Debit</th>
                <th class="fw-normal fs-mons text-end pb-2">Credit</th>
                <th class="fw-normal fs-mons text-end pb-2">Balance</th>
              </tr>
            </thead>
            <tbody id="p_trans_tbody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" data-type="" class="btn btn-dark print_btn" onclick="print_h_transactions()">Print</button>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  var selected_transactions = [];

  // function to print the transactions
  function print_h_transactions() {
    if (!selected_transactions || selected_transactions.length == 0) {
      alert("No data found");
      return;
    }

    var type = $(".print_btn").data("type");

    if (type == "h") {
      var HName = selected_transactions[0]["HName"];
      var balance = 0;
      // create a dynamic table with the selected transactions
      var table = '<table style="width: 100%">';
      table += "<thead>";
      table += "<tr>";
      table += '<th style="text-align: start;">Account Name</th>';
      table += '<th style="text-align: end;">Debit</th>';
      table += '<th style="text-align: end;">Credit</th>';
      // table += '<th style="text-align: end;">Balance</th>';
      table += "</tr>";
      table += "</thead>";
      table += "<tbody>";
      selected_transactions.forEach(function(item) {
        balance += parseFloat(item["bal"]);
        table += "<tr>";
        table += '<td style="text-align: start;">' + item["PName"] + "</td>";
        table += '<td style="text-align: end;">' + item["DR"] + "</td>";
        table += '<td style="text-align: end;">' + item["CR"] + "</td>";
        // table += '<td style="text-align: end;">' + item["bal"] + "</td>";
        table += "</tr>";
      });
      table += "</tbody>";
      table += "</table>";

      // print the table
      var printWindow = window.open("", "", "height=400,width=800");
      printWindow.document.write("<html><head><title>Transactions</title>");
      printWindow.document.write("</head><body>");
      printWindow.document.write("<h3>" + HName + "</h3>");

      printWindow.document.write(table);

      // balance
      // printWindow.document.write("<hr>");
      // printWindow.document.write('<table style="width: 100%">');
      // printWindow.document.write("<tr>");
      // printWindow.document.write('<td style="text-align: start;">Total</td>');
      // printWindow.document.write(
      //   '<td style="text-align: end;">' + balance.toFixed(2) + "</td>"
      // );
      // printWindow.document.write("</tr>");
      // printWindow.document.write("</table>");
      printWindow.document.write("</body></html>");
      printWindow.document.close();

      printWindow.print();
    } else if (type == "p") {
      var PName = selected_transactions[0]["PName"];
      var balance = 0;
      // create a dynamic table with the selected transactions
      var table = '<table style="width: 100%">';
      table += "<thead>";
      table += "<tr>";
      table += '<th style="text-align: start;">ate</th>';
      table += '<th style="text-align: start;">Tour</th>';
      table += '<th style="text-align: start;">Description</th>';
      table += '<th style="text-align: end;">Debit</th>';
      table += '<th style="text-align: end;">Credit</th>';
      table += "</tr>";
      table += "</thead>";
      table += "<tbody>";
      selected_transactions.forEach(function(item) {
        balance += parseFloat(item["Dr"]) - parseFloat(item["Cr"]);
        table += "<tr>";
        table += '<td style="text-align: start;">' + item["Date"] + "</td>";
        table += '<td style="text-align: start;">' + item["TourId"] + "</td>";
        table +=
          '<td style="text-align: start;">' + item["Discription"] + "</td>";
        table += '<td style="text-align: end;">' + item["Dr"] + "</td>";
        table += '<td style="text-align: end;">' + item["Cr"] + "</td>";
        table += '<td style="text-align: end;">' + item["bal"] + "</td>";
        table += "</tr>";
      });
      table += "</tbody>";
      table += "</table>";

      // print the table
      var printWindow = window.open("", "", "height=400,width=800");
      printWindow.document.write("<html><head><title>Transactions</title>");
      printWindow.document.write("</head><body>");
      printWindow.document.write("<h3>" + PName + "</h3>");

      printWindow.document.write(table);

      // balance
      printWindow.document.write("<hr>");
      printWindow.document.write('<table style="width: 100%">');

      // DR total
      var Dr_total = 0;
      selected_transactions.forEach(function(item) {
        Dr_total += parseFloat(item["Dr"]);
      });

      // CR total
      var Cr_total = 0;
      selected_transactions.forEach(function(item) {
        Cr_total += parseFloat(item["Cr"]);
      });

      printWindow.document.write("<tr>");
      
      printWindow.document.write('<td style="text-align: start;">Total</td>');
      printWindow.document.write('<td></td>');
      printWindow.document.write('<td></td>');
      printWindow.document.write(
        '<td style="text-align: end;">' + Dr_total.toFixed(2) + "</td>"
      );
      printWindow.document.write(
        '<td style="text-align: end;">' + Cr_total.toFixed(2) + "</td>"
      );
      printWindow.document.write("</tr>");

      // printWindow.document.write("<tr>");
      // printWindow.document.write(
      //   '<td colspan="2" style="text-align: start;">Total</td>'
      // );
      // printWindow.document.write(
      //   '<td style="text-align: end;">' + balance.toFixed(2) + "</td>"
      // );
      // printWindow.document.write("</tr>");
      printWindow.document.write("</table>");
      printWindow.document.write("</body></html>");
      printWindow.document.close();

      printWindow.print();
    }
  }

  function populate_h_tbody(data) {
    var html = "";
    var balance = 0;
    var date1 = "<?= $date1 ?? '' ?>";
    var date2 = "<?= $date2 ?? '' ?>";

    data.forEach(function(item) {
      item["bal"] = parseFloat(item["bal"]).toFixed(2);
      item["DR"] = parseFloat(item["DR"]).toFixed(2);
      item["CR"] = parseFloat(item["CR"]).toFixed(2);

      balance += parseFloat(item["bal"]);

      var tr = "<tr>";
      tr += `
        <td class="fs-mons text-black">
        <a href="#" class="p_transactions text-black" data-hname="${item["HName"]}" data-pname="${item["PName"]}" data-date1="${date1}" data-date2="${date2}">
        <i class="bi bi-plus-square"></i>
        ${item["PName"]}
        </a>
        </td>
        `;
      tr += '<td class="text-end fs-mons text-black">' + item["DR"] + "</td>";
      tr += '<td class="text-end fs-mons text-black">' + item["CR"] + "</td>";
      tr += '<td class="text-end fs-mons text-black">' + item["bal"] + "</td>";
      tr += "</tr>";
      html += tr;
    });
    if (!data || data.length == 0) {
      html = '<tr><td colspan="4" class="text-center">No data found</td></tr>';
    } else {
      // html += '<tr class="border-top">';
      // html += '<td colspan="3" class="fs-mons pt-2">Total</td>';
      // html +=
      //   '<td class="text-end fs-mons-bold pt-2">' + balance.toFixed(2) + "</td>";
      // html += "</tr>";
    }

    // #h_trans_table show and #p_trans_table hide
    $("#h_trans_table").removeClass("d-none");
    $("#p_trans_table").addClass("d-none");
    $("#p_trans_back").addClass("d-none");
    $(".print_btn").data("type", "h");

    selected_transactions = data;
    return html;
  }

  function populate_p_tbody(data) {
    var html = "";
    var Dr_total = 0;
    var Cr_total = 0;
    var balance = 0;
    data.forEach(function(item) {
      item["Dr"] = parseFloat(item["Dr"]).toFixed(2);
      item["Cr"] = parseFloat(item["Cr"]).toFixed(2);

      Dr_total += parseFloat(item["Dr"]);
      Cr_total += parseFloat(item["Cr"]);
      // balance += parseFloat(item["Dr"]) - parseFloat(item["Cr"]);

      var tr = "<tr>";
      tr += '<td class="fs-mons text-black pe-2">' + item["Date"] + "</td>";
      tr += '<td class="fs-mons text-black pe-2">' + item["TourId"] + "</td>";
      tr += '<td class="fs-mons text-secondary">' + item["Discription"] + "</td>";
      tr += '<td class="text-end fs-mons text-black">' + item["Dr"] + "</td>";
      tr += '<td class="text-end fs-mons text-black">' + item["Cr"] + "</td>";
      tr += '<td class="text-end fs-mons text-black">' + item["bal"] + "</td>";
      tr += "</tr>";
      html += tr;
    });
    if (!data || data.length == 0) {
      html = '<tr><td colspan="5" class="text-center">No data found</td></tr>';
    } else {
      html += '<tr class="border-top">';
      html += '<td colspan="3" class="fs-mons pt-3">Total</td>';
      html +=
        '<td class="text-end fs-mons-bold pt-3">' + Dr_total.toFixed(2) + "</td>";
      html +=
        '<td class="text-end fs-mons-bold pt-3">' + Cr_total.toFixed(2) + "</td>";
      html += "</tr>";

      // html += "<tr>";
      // html += '<td colspan="3" class="fs-mons-bold pt-2">Balance</td>';
      // html +=
      //   '<td class="text-end fs-mons-bold pt-3">' + balance.toFixed(2) + "</td>";
      // html += "</tr>";
    }

    $("#h_trans_table").addClass("d-none");
    $("#p_trans_table").removeClass("d-none");
    $("#p_trans_back").removeClass("d-none");
    $(".print_btn").data("type", "p");

    selected_transactions = data;
    return html;
  }

  // .h_transactions on click
  $(".h_transactions").on("click", function() {
    var hname = $(this).data("hname");
    var date1 = $(this).data("date1");
    var date2 = $(this).data("date2");
    $.ajax({
      url: "<?php echo base_url('accounts/heading_acc_transactions'); ?>",
      type: "GET",
      data: {
        HName: hname,
        date1: date1,
        date2: date2,
      },
      dataType: "json",
      success: function(data) {
        var html = populate_h_tbody(data);
        $("#h_trans_tbody").html(html);
        $("#transactionsModalLabel").html(hname);
        $("#transactionsModal").modal("show");
      },
    });
  });

  // .p_transactions on click
  $(document).on("click", ".p_transactions", function() {
    var pname = $(this).data("pname");
    var hname = $(this).data("hname");
    var date1 = $(this).data("date1");
    var date2 = $(this).data("date2");
    $.ajax({
      url: "<?php echo base_url('accounts/posting_acc_transactions'); ?>",
      type: "GET",
      data: {
        PName: pname,
        date1: date1,
        date2: date2,
      },
      dataType: "json",
      success: function(data) {
        var html = populate_p_tbody(data);
        $("#p_trans_tbody").html(html);
        $("#transactionsModalLabel").html(pname);

        $("#p_trans_back").html(
          '<i class="bi bi-caret-left-fill"></i> Back to ' + hname
        );
        $("#p_trans_back").data("hname", hname);
        $("#p_trans_back").data("date1", date1);
        $("#p_trans_back").data("date2", date2);

        $("#transactionsModal").modal("show");
      },
    });
  });
</script>