<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>
        <style type="text/css">
            .pull-right {
                float: right;
            }

            .typeahead,
            .typeahead2,
            .tt-query,
            .tt-hint {
                display: block;
                width: 290%;
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
                width: 100;
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
                                        <h4 class="p-title">Account Filter</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-8">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="<?php echo base_url('accounts/account_filter') ?>" method="GET" accept-charset="utf-8">
                                                    <fieldset>
                                                        <div class="row clearfix">
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Select Account</label>
                                                                    <input type="text" name="item_search" class="form-control  item_search typeahead txt_nav" id="item_search" placeholder="" required />

                                                                </div>
                                                                <input class="form-control" type="hidden" id="item_code" name="item_code" />
                                                                <input type="hidden" name="acc" class="form-control" id="acc">

                                                            </div>
                                                            <div class="col-sm-6"></div>

                                                        </div>
                                                        <div class="row clearfix">
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Start Date</label>
                                                                    <input class="form-control" data-provide="datepicker" data-provide="datepicker" data-date-format="yyyy-mm-dd" id="date1" name="date1" value="" />
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">End Date</label>
                                                                    <input data-provide="datepicker" data-date-format="yyyy-mm-dd" class="form-control" id="date2" name="date2" value="" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <br>
                                                        <button type="submit" class="btn btn-success  me-5px">Search</button>

                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                if (isset($load_data) && !empty($load_data)) {
                                ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <div class="table-responsive">
                                                        <table id="mainTable" class="table table-hover">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>Date</th>
                                                                    <th>Tour</th>
                                                                    <th>Description</th>
                                                                    <th>Voucher Number</th>
                                                                    <th class="text-end">Debit</th>
                                                                    <th class="text-end">Credit</th>
                                                                    <th class="text-end">Accumulated Balance</th>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="5" class="text-center">
                                                                        <strong>Opening Balance</strong>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <?= $balance ?>
                                                                    </td>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                
                                                                <?php
                                                                $x = 1;
                                                                $Dr_total = 0;
                                                                $Cr_total = 0;
                                                                $accumulation_total = $balance;
                                                                foreach ($load_data as $list) {
                                                                    $Dr_total += $list['Dr'];
                                                                    $Cr_total += $list['Cr'];

                                                                    if($type == 1){
                                                                        $accumulation_total += $list['Dr'] - $list['Cr'];
                                                                    }else{
                                                                        $accumulation_total += $list['Cr'] - $list['Dr'];
                                                                    }
                                                                  
                                                                   

                                                                ?>
                                                                    <tr>
                                                                        <td class="fs-mons text-black pe-2"><?= $list['Date']; ?></td>
                                                                        <td class="fs-mons text-black pe-2"><?= $list['TourId']; ?></td>
                                                                        <td class="fs-mons text-secondary"><?= $list['Discription']; ?></td>
                                                                        <td class="fs-mons text-secondary"><?= $list['Recpt_No']; ?></td>
                                                                        <td class="text-end fs-mons text-black"><?= $list['Dr']; ?></td>
                                                                        <td class="text-end fs-mons text-black"><?= $list['Cr']; ?></td>
                                                                        <td class="text-end fs-mons text-black"><?= number_format((float)$accumulation_total, 2, '.', '')?></td>
                                                                    </tr>
                                                                <?php
                                                                    $x++;
                                                                } ?>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr>

                                                                    <td colspan="4" class="fs-mons pt-3 text-black"><strong>Total</strong></td>
                                                                    <td class="text-end fs-mons-bold pt-3" style="background-color: #f8f9fa;"><strong><?= number_format((float)$Dr_total, 2, '.', ''); ?></strong></td>
                                                                    <td class="text-end fs-mons-bold pt-3" style="background-color: #f8f9fa;"><strong><?= number_format((float)$Cr_total, 2, '.', ''); ?></strong></td>
                                                                    <td class="text-end fs-mons-bold pt-3" style="background-color: #f8f9fa;"><strong><?= number_format((float)$accumulation_total, 2, '.', ''); ?></strong></td>
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
        var report_title = 'Accounts Filter Report';
        $('#mainTable').DataTable({
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
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    titleAttr: 'PDF',
                    title: report_title,
                    orientation: 'landscape', // Set orientation to landscape
                    pageSize: 'A4', // Set page size to A4
                    footer: true,
                    exportOptions: {
                        columns: ':not(:hidden)' // Export all columns, including hidden ones
                    },
                    customize: function(win) {}
                },
                {
                    extend: 'print',
                    text: 'Print',
                    titleAttr: 'Print',
                    title: report_title,
                    footer: true,
                    exportOptions: {
                        columns: ':not(:hidden)' // Export all columns, including hidden ones
                    },
                    customize: function(win) {
                        var style = '<style type="text/css" media="print">@page { size: landscape; }</style>';
                        $(win.document.body).append(style);
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
        $(document).ready(function() {
            var load_data = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                remote: {
                    url: '<?php echo base_url(); ?>accounts/account_filter/fetch/%QUERY',
                    wildcard: '%QUERY',
                    transform: function(response) {
                        return response; // Transform the response if needed
                    },
                    replace: function(url, uriEncodedQuery) {
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
                    suggestion: Handlebars.compile('<div class="row"><div class="col-md-5" style="font-size:14px;">{{PName}}</div><div class="col-md-7">{{BName}} → {{TName}} → {{HName}}</div></div>')
                },
                highlight: true
            }).on("typeahead:selected", function(e, datum) {
                $("form [name=acc]").val(datum.PName);
                $("form [name=item_code]").val(datum.L_Code);
            });
        });
    </script>