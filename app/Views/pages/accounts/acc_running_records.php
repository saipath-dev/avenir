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

    <style>
        .fs-mons {
            font-size: 12px;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 600;
            color: rgb(100, 100, 100);
        }

        .fs-mons-bold {
            font-size: 12px;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 800;
            color: rgb(0, 0, 0);
        }

        td a:hover {
            text-decoration: underline;
            color: dodgerblue !important;
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
                                        <h4 class="p-title">ACC Records</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-4">
                                                <form id="date_form" accept-charset="utf-8">
                                                    <fieldset>

                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label" for="app_date">From Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control default-date" type="text" id="date1" name="date1" data-provide="datepicker" data-date-format="yyyy-mm-dd" value="<?php echo date('Y-m-d') ?>" placeholder="" required />
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label" for="app_date">From Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control default-date" type="text" id="date2" name="date2" data-provide="datepicker" data-date-format="yyyy-mm-dd" value="<?php echo date('Y-m-d') ?>" placeholder="" required />
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="d-flex flex-column">
                                                                    <label class="form-label" for="item_search">Account<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input type="text" name="item_search" class="form-control item_search typeahead custom-input w-100" id="item_search" placeholder="" required />
                                                                </div>
                                                            </div>


                                                            <input type="hidden" name="posting_acc" id="posting_acc_name" class="form-control" readonly />
                                                            <input type="hidden" name="bname" id="bname" class="form-control" readonly />
                                                            <input type="hidden" name="balflag" id="balflag" class="form-control" readonly />
                                                            <input type="hidden" name="tname" id="tname" class="form-control" readonly />
                                                            <input type="hidden" name="hname" id="hname" class="form-control" readonly />
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-12 text-right">
                                                                <div class="form-group pull-right"><br>
                                                                    <button type="button" class="btn btn-primary btn_add">Search</button>
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
                                    <div class="col-md-12 d-none" id="data_tbl">
                                        <div class="card">
                                            <div class="body p-4">
                                                <div class="d-flex justify-content-between">
                                                    <div class="d-flex flex-column">
                                                        <span id="acc_path"></span>
                                                        <h4 class="badge text-bg-info py-2 my-2 fs-6" id="acc_name" style="width: fit-content;"></h4>
                                                    </div>
                                                    <div class="d-flex flex-column justify-content-end text-end">
                                                        <h6 class="text-end" id="posting_acc" style="width: fit-content;"></h6>
                                                        <h6 class="mt-2">
                                                            From : <strong class="mx-2" id="pdate1"></strong> To : <strong class="mx-2" id="pdate2"></strong>
                                                        </h6>
                                                    </div>
                                                </div>
                                                <table id="p_trans_table" class="table table-striped table-bordered mt-4">
                                                    <thead>
                                                        <tr>
                                                            <th class="fw-normal fs-mons pb-2">Date</th>
                                                            <th class="fw-normal fs-mons pb-2">Tour</th>
                                                            <th class="fw-normal fs-mons pb-2">Description</th>
                                                            <th class="fw-normal fs-mons text-end pb-2">Debit</th>
                                                            <th class="fw-normal fs-mons text-end pb-2">Credit</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="p_trans_tbody"></tbody>
                                                </table>
                                                <button type="button" class="btn btn-dark print_btn" onclick="print_transactions()">Print</button>
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

        var selected_transactions = [];

        // function to print the transactions
        function print_transactions() {
            if (!selected_transactions || selected_transactions.length == 0) {
                alert("No data found");
                return;
            }

            var PName = selected_transactions[0]["PName"];
            var balance = 0;
            // create a dynamic table with the selected transactions
            var table = '<table style="width: 100%">';
            table += "<thead>";
            table += "<tr>";
            table += '<th style="text-align: start;">Date</th>';
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
                table += "</tr>";
            });
            table += "</tbody>";
            table += "</table>";

            // print the table
            var printWindow = window.open("", "", "height=400,width=800");
            printWindow.document.write("<html><head><title>Transactions</title>");
            printWindow.document.write("</head><body>");
            printWindow.document.write("<span>" + $("#acc_path").text() + "</span>");
            printWindow.document.write("<h3>" + PName + "</h3>");
            printWindow.document.write("<hr />");

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

        function populate_p_tbody(data, balflag) {
            var html = "";
            var Dr_total = 0;
            var Cr_total = 0;
            var balance = 0;
            data.forEach(function(item) {
                item["Dr"] = parseFloat(item["Dr"]).toFixed(2);
                item["Cr"] = parseFloat(item["Cr"]).toFixed(2);

                Dr_total += parseFloat(item["Dr"]);
                Cr_total += parseFloat(item["Cr"]);
                if (balflag == 1) {
                    balance += parseFloat(item["Dr"]) - parseFloat(item["Cr"]);
                } else {
                    balance += parseFloat(item["Cr"]) - parseFloat(item["Dr"]);
                }

                var tr = "<tr>";
                tr += '<td class="fs-mons text-black pe-2">' + item["Date"] + "</td>";
                tr += '<td class="fs-mons text-black pe-2">' + item["TourId"] + "</td>";
                tr += '<td class="fs-mons text-secondary">' + item["Discription"] + "</td>";
                tr += '<td class="text-end fs-mons text-black">' + item["Dr"] + "</td>";
                tr += '<td class="text-end fs-mons text-black">' + item["Cr"] + "</td>";
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

                html += "<tr class='table-warning'>";
                html += '<td colspan="4" class="fs-mons-bold align-middle pt-2">Balance</td>';
                html +=
                    '<td class="text-end fs-mons-bold pt-3">' + balance.toFixed(2) + "</td>";
                html += "</tr>";
            }

            selected_transactions = data;
            return html;
        }

        function get_transactions(date1, date2, pname, l_code, bname, tname, hname, balflag) {
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
                    let accountPath = [bname, tname, hname].filter(Boolean).join(' -> ');
                    var html = populate_p_tbody(data, balflag);
                    $("#p_trans_tbody").html(html);
                    $("#acc_path").html(accountPath);
                    $("#acc_name").html(pname);
                    $("#pdate1").html(date1);
                    $("#pdate2").html(date2);
                    $("#posting_acc").html(`L-Code: <strong>${l_code}</strong>`);
                    $("#data_tbl").removeClass("d-none");
                },
            });
        }

        $(document).ready(function() {
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
                    $('#posting_acc').val(datum.L_Code);
                    $('#posting_acc_name').val(datum.PName);
                    $('#item_search').val(datum.PName);
                    $('#bname').val(datum.BName);
                    $('#balflag').val(datum.balflag);
                    $('#tname').val(datum.TName);
                    $('#hname').val(datum.HName);
                });

            $('.btn_add').click(function(e) {
                e.preventDefault();
                var date1 = $('#date1').val();
                var date2 = $('#date2').val();
                var posting_acc = $('#posting_acc').val();
                var posting_acc_name = $('#posting_acc_name').val();
                var bname = $('#bname').val();
                var balflag = $('#balflag').val();
                var tname = $('#tname').val();
                var hname = $('#hname').val();

                if (date1 != '' && date2 != '' && posting_acc_name != '') {
                    get_transactions(date1, date2, posting_acc_name, posting_acc, bname, tname, hname, balflag);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Please fill all the fields!',
                    });
                }
            });
        });
    </script>
</body>




</html>