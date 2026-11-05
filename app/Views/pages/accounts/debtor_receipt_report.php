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
                                        <h4 class="p-title">Debtor Receipt Report </h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form id="date_form" accept-charset="utf-8" action="javascript:void(0)">
                                                    <div class="row">
                                                        <div class="mb-3 col-md-12">
                                                            <label class="form-label" for="customer_name">Customer </label>
                                                            <select name="customer_name" id="customer_name" class="js-example-basic-single form-control" style="width: 100%;">
                                                                <option value="">Select a Customer...</option>
                                                                <?php
                                                                foreach ($customers as $customer) {
                                                                    $selected = '';
                                                                    echo '<option value="' . $customer['Sup_Acc_No'] . '"' . $selected . '>' . $customer['Name'] . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3 col-md-4" style="display: none;">
                                                            <label class="form-label" for="drange1">Start Date</label>
                                                            <input class="form-control" type="text" id="drange1" name="drange1" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                                        </div>
                                                        <div class="mb-3 col-md-4" style="display: none;">
                                                            <label class="form-label" for="drange2">End Date</label>
                                                            <input class="form-control" type="text" id="drange2" name="drange2" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" style="padding-top: 10px; float:right;">
                                                        <button type="submit" class="btn btn-success w-100px me-5px"><i class="bi bi-search"></i>Search
                                                        </button>
                                                    </div>
                                                </form>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                 
                                <?php
                                if (isset($payments) && !empty($payments) && $is_search) {
                                ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <!-- <div class="mb-3">
                                                        <button type="button" class="btn btn-primary" onclick="printReport()">
                                                            <i class="bi bi-printer"></i> Print
                                                        </button>
                                                    </div> -->
                                                    <div class="table-responsive">
                                                        <!-- <div class="mb-3">
                                                            <button type="button" class="btn btn-success btn-export"><i class="bi bi-file-earmark-excel"></i> Export</button>
                                                        </div> -->
                                                        <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Tour ID</th>
                                                                    <th>Invoiced Date</th>
                                                                    <th>Customer</th>
                                                                    <th>Invoice No</th>
                                                                    <th>Receipt No</th>
                                                                    <th>Amount LKR$</th>
                                                                    <!-- <th class="text-end">LKR Amount</th> -->
                                                                    <!-- <th>Tour Cancellation</th> -->
                                                                    <!-- <th>Amendments</th> -->
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $x = 1;
                                                                $dollar_total = 0;
                                                                $rs_total = 0;
                                                                foreach ($payments as $litem) {
                                                                    // $total_cr = +$litem['bank_amount'];
                                                                ?>
                                                                    <tr style="width: 100%;">
                                                                        <td><?php echo $x ?></td>
                                                                        <td><?php echo $litem['booking_no'] ?></td>
                                                                        <td><?php echo $litem['payment_date'] ?></td>
                                                                        <td><?php echo $litem['cus_name'] ?></td>
                                                                        <td><?php echo $litem['slip_no'] ?></td>
                                                                        <td><?php echo $litem['receipt_no'] ?></td>
                                                                        <td class="text-end"><?php echo $litem['bank_amount'] ?></td>
                                                                        <!-- <td class="text-end"><?php //echo $total_cr ?></td> -->
                                                                        <!-- <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-cancel" data-inv="<?php //echo $litem['invoice_no']; ?>" data-status="<?php //echo $litem['cancel']; ?>" data-flag="2"><i class="bi bi-trash"></i></button></td>
                                                                        <td class="text-center"><button type="button" class="btn btn-sm btn-info btn-cancel" data-inv="<?php //echo $litem['invoice_no']; ?>" data-status="<?php //echo $litem['cancel']; ?>" data-flag="5"><i class="bi bi-trash"></i></button></td> -->

                                                                    </tr>
                                                                <?php
                                                                    // $dollar_total += $litem['dollar_amount'];
                                                                    $rs_total += $litem['bank_amount'];
                                                                    $x++;
                                                                }
                                                                ?>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr>
                                                                    <td colspan="4"></td>
                                                                    <td>Total</td>
                                                                    <!-- <td><?php //echo $dollar_total ?></td> -->
                                                                    <td class="text-end"><?php echo $rs_total ?></td>
                                                                    <td></td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } else {
                                ?>
                                    <?php if ($is_search) { ?>
                                        <div class="row clearfix mt-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-warning" role="alert">
                                                    <strong>Warning!</strong> No data found.
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });
 

        $("#date_form").on('submit', function(e) {
            e.preventDefault();

            var date1 = $("#drange1").val();
            var date2 = $("#drange2").val();
            var cus = $("#customer_name").val();
 
            window.location.href = "<?php echo base_url(); ?>accounts/debtor_payment/report?startdate=" + date1 + "&enddate=" + date2 + "&cus=" + cus;
            return false;

        });

        $(document).on('click', '.btn-cancel', function() {
            var invoice_no = $(this).data('inv');
            var status = $(this).data('status');
            var flag = $(this).data('flag');
            cancellation(invoice_no, status, flag);
        });

        function cancellation(invoice_no, status, flag) {
            var id = invoice_no;
            var status = status;
            var flag = flag;
            // alert(id);

            Swal.fire({
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
                        url: "<?php echo base_url(); ?>accounts/debtor_invoice/cancellation_request",
                        data: {
                            id: id,
                            status: status,
                            type: flag
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
                                        location.reload();
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
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong!',
                            });
                        }
                    });
                }
            });
        }

         

        var report_title = 'Debtor Invoice Report';
        $('.js-basic-example').DataTable({
            "aaSorting": [],

            rowReorder: {
                selector: 'td:nth-child(2)'
            },
            responsive: 'true',
            dom: "Bfrtip",
            buttons: [{
                    extend: 'csvHtml5',
                    text: 'CSV',
                    titleAttr: 'CSV',
                    title: report_title,
                    footer: true,
                    exportOptions: {
                        columns: '1,6'
                    }
                },
               
                {
                    extend: 'print',
                    text: 'Print',
                    titleAttr: 'Print',
                    title: '',
                    footer: true,
                    exportOptions: {
                        columns: '1,6'
                    },
                    customize: function(win) {
                        var style = '<style type="text/css" media="print">' +
                                'body { background: #fff !important; margin:0; }' +
                                '@page { size: landscape; background: #fff; margin: 10px; }' +
                                'body:before { content:""; display:block; position:fixed; top:0; left:0; right:0; bottom:0; border:1px solid #000; pointer-events:none; z-index:9999; }' +
                                '.print-header { width: 100%; display: flex; justify-content: space-between; align-items: center; margin-bottom:5px; background: #fff; }' +
                                '.print-header-left { text-align: left; font-size: 13px; background: #fff; }' +
                                '.print-header-center { text-align: center; background: #fff; }' +
                                '.print-header-right { text-align: right; font-size: 13px; background: #fff; }' +
                                '.print-logo { height: 60px; margin: 0 20px; background: #fff; }' +
                                '.print-title { font-size: 16px; font-weight: bold; margin: 5px 0; background: #fff; }' +
                                '.print-params { font-size: 20px; margin-bottom: 5px; background: #fff; }' +
                                '.print-footer { text-align: center; position: fixed; bottom: 0; width: 100%; font-size: 12px; border-top: 1px solid #ddd; padding-top: 2px; background: #fff; }' +
                                'table { border-collapse: collapse; width: 100%; background: #fff; border-spacing: 0 !important; font-size:8px; border:1px solid #000 !important; }' +
                                'th, td { border: 1px solid #000 !important; padding:1px; background: #fff; font-size:12px; }' +
                                'th { background-color:white !important; }' +
                                '</style>';

                        // var now = new Date();
                        // var jsDate = now.getDate() + '/' + (now.getMonth() + 1) + '/' + now.getFullYear() + ' ' + now.getHours() + ':' + now.getMinutes() + ':' + now.getSeconds();

                        // var customer = $('#customer_name option:selected').text() || 'All Customers';
                        // var date1 = $('#drange1').val() || '';
                        // var date2 = $('#drange2').val() || '';
                        // var dateRange = (date1 && date2) ? (date1 + ' to ' + date2) : (date1 || date2 || 'All Dates');
                        var companyAddress = "No. 126/1 , Park Road , Colombo - 5, Sri Lanka";

                        // Get the first "Invoiced Date" from the table (index 2), use its month and year
                        // var $table = $(win.document.body).find('table').first();
                        // var firstDate = '';
                        // $table.find('tbody tr').each(function() {
                        //     var dateVal = $(this).find('td').eq(2).text().trim();
                        //     if (dateVal && /^\d{4}-\d{2}-\d{2}$/.test(dateVal)) {
                        //         firstDate = dateVal;
                        //         return false; // break loop
                        //     }
                        // });
                        // var printMonthYear = '';
                        // if (firstDate) {
                        //     var parts = firstDate.split('-');
                        //     var dt = new Date(parts[0], parts[1] - 1, 1);
                        //     printMonthYear = dt.toLocaleString('default', { month: 'long', year: 'numeric' });
                        // } else {
                        //     printMonthYear = dateRange;
                        // }

                        var header = '<div class="print-header">' +
                            '<div class="print-header-left">' +
                                '<div style="font-size:20px;padding-left:5px;"><b>AVENIR HOLIDAYS (PVT) LTD</b></div>' +
                                '<div style="font-size:20px;padding-left:5px;"><b>INVOICE</b></div>' +
                                '<table>' +
                                '<tr>' +
                                '<th>Date</th>'+
                                '<th>Invoice</th>'+
                                '</tr>' +
                                '<tr>'+
                                '<td> </td>' +
                                '<td>-</td>' +
                                '</tr>' +
                                '</table>' +
                                '<div style="border:1px solid black;">'+
                                '<div>AVENIR HOLIDAYS (PVT) LTD</div>' +
                                '<div>' + companyAddress + '</div>' +
                                '<div>info@avenirholidays.com</div>' +
                                '</div><br>' +
                                '<div style="padding-left:5px;"> <i>Sri Lanka Tour Arrangement for the month of August 2024</i></div>' +
                            '</div>' +
                            '<div class="print-header-center">' +
                               
                            '</div>' +
                            '<div class="print-header-right">' +
                            '<div style="text-align:left;">' +
                                '<img src="<?= base_url(); ?>assets/images/Logo-new.png" class="print-logo" style="display:block; margin-left:0; object-fit:contain; box-shadow:none; background:none;" />' +
                                '<div>THE ACCOUNTANT</div>' +
                                '<div>LION ROYAL LTD</div>' +
                                '<div>1-10-19 Horidome-cho,</div>' +
                                '<div>Nihonbashi, Chuo-ku,</div>' +
                                '<div>Tokyo</div>' +
                            '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="print-params"></div>';
                        var footer = '<div class="print-footer" style="position:relative; left:20; bottom:5; text-align:left; font-size:11px;">' +
                            '<div>Amount : </div>' +
                            '<div><u>Bank Details</u></div>' +
                            '<div>A/C Name - Avenir Holidays (Pvt) Ltd</div>' +
                            '<div>A/C Type - Savings A/C</div>' +
                            '<div>A/C No - 5000028818 (USD)</div>' +
                            '<div>Bank - Commercial Bank</div>' +
                            '<div>Branch - Ward Place</div>' +
                            '<div>Bank/Branch code - 7056/145</div>' +
                            '<div>Swift Code - CCEYLKLX</div>' +
                            
                        '</div>';

                        $(win.document.body).prepend(header);
                        $(win.document.body).append(footer);
                        $(win.document.body).append(style);

                        setTimeout(function() {
                            var pageCount = $(win.document.body).find('table').first().find('tr').length;
                            $(win.document.body).find('.totalPages').text(Math.ceil(pageCount / 20));
                            $(win.document.body).find('.pageNumber').text('1');
                        }, 1000);
                    }
                },
                {
                    extend: 'colvis',
                    text: 'Visibility',
                    titleAttr: 'Columns',
                    title: report_title,
                    footer: true,
                    postfixButtons: ['colvisRestore']
                },
            ]
        });

        // function printReport() {
        //     window.location.href = "<?= base_url(); ?>accounts/debtor_invoice/print_deptor_invoice_report";
        // }

    </script>



</body>

</html>