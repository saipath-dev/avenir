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
                width: 100%;
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
                                    <div class="col-md-12 d-flex">
                                        <h4 class="p-title">Year End Process</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-6">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                    <fieldset>
                                                        <input type="hidden" id="t_id">
                                                        <div class="mb-3" style="display: none;">
                                                            <label class="form-label" for="base_account">Base Account 1<font color="#FF0000"><strong>*</strong></font></label>
                                                            <select class="form-control" id="base_account" name="base_account" >
                                                                <option value="">Select Base Account</option>
                                                                <?php foreach ($basement_accounts as $account) : ?>
                                                                    <option value="<?= $account['B_Code'] ?>"><?= $account['BName'] ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="base_account_last">Base Account 2<font color="#FF0000"><strong>*</strong></font></label>
                                                            <select class="form-control" id="base_account_last" name="base_account_last">
                                                                <option value="">Select Base Account</option>
                                                                <?php foreach ($basement_accounts_last as $accounts_last) : ?>
                                                                    <option value="<?= $accounts_last['B_Code'] ?>"><?= $accounts_last['BName'] ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="transfer_account">Transfer Account<font color="#FF0000"><strong>*</strong></font></label>
                                                            <select class="form-control" id="transfer_account" name="transfer_account" style="display: none;">


                                                            </select>

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
                                                        <div class="mb-3">
                                                            <label class="form-label" for="date">Selected Record Date<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="date" id="date" name="date" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="date">Date<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="date" id="date2" name="date2" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="description">Description<font color="#FF0000"><strong>*</strong></font></label>
                                                            <textarea class="form-control" id="description" name="description" rows="2" placeholder="Enter Description" required></textarea>
                                                        </div>
                                                        <br>
                                                        <button type="submit" class="btn btn-success w-100px me-5px">Save</button>
                                                        <button type="button" id="cancel_btn" class="btn btn-default w-100px me-5px">Cancel</button>
                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table table-hover js-basic-example dataTable table-custom align-middle">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>District / City</th>
                                                                <th>Kilometres</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                         
                                                            <?php
                                                            $x = 1;

                                                            ?>
                                                            <tr style="width: 100%;">
                                                                <td style="width: 5%;"><?php echo $x; ?></td>
                                                                <td></td>
                                                                <td></td>

                                                                <td style="width: 5%;">
                                                                    <div class="dropup">
                                                                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                            <i class="bi bi-three-dots-vertical"></i>
                                                                        </button>
                                                                        <ul class="dropdown-menu" style="z-index: 99999;">
                                                                            <li>
                                                                                <a href="#" class="dropdown-item edit-item-btn" data-item-id="">Edit</a>
                                                                            </li>
                                                                            <li>
                                                                                <a class="dropdown-item text-danger delete-item-btn" href="#" data-item-id="">Delete</a>
                                                                            </li>
                                                                        </ul>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <?php

                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div> -->
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

    <script>
        $(document).ready(function() {

            var load_data = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: '<?php echo base_url(); ?>settings/year_end_process/fetch',
                remote: {
                    url: '<?php echo base_url(); ?>settings/year_end_process/fetch/%QUERY',
                    wildcard: '%QUERY',
                    transform: function(response) {
                        return response;
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
                        suggestion: Handlebars.compile('<div class="row"><div class="col-md-5" style="font-size:14px;"  >{{PName}}</div><div class="col-md-7" >{{BName}} → {{TName}} → {{HName}}</div></div>')
                    },
                    highlight: true
                })
                .on("typeahead:selected", function(e, datum) {

                    $("form [name=acc_name]").val(datum.PName);
                    $("form [name=acc]").val(datum.L_Code);

                });

        });


        $(document).ready(function() {
            // Submit form data via Ajax
            $('#form_submit').submit(function(e) {
                alert('test');
                e.preventDefault();

                // confirmation alert with Base Account and Transfer Account and Date
                var baseAccount = $('#base_account').val();
                var baseAccount_name = $('#base_account option:selected').text();

                var baseAccount_last = $('#base_account_last').val();
                var baseAccount_last_name = $('#base_account_last option:selected').text();

                if (baseAccount != '' && baseAccount_name != '') {

                    var transferAccount = $('#acc').val();
                    var transferAccount_name = $('#acc_name').val();
                    var date = $('#date').val();
                    Swal.fire({
                        title: 'Confirm Submission',
                        text: "Base Account: " + baseAccount_name + "\nTransfer Account: " + transferAccount_name + "\nDate: " + date,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, submit!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Proceed with form submission
                            submitForm();
                        }
                    });

                    function submitForm() {
                        // Perform the AJAX request to submit the form data
                        var url = '<?php echo base_url(); ?>settings/year_end_process/submit';
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: $('#form_submit').serialize(),
                            dataType: 'json',
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
                                        title: 'Error',
                                        text: response.message,
                                    });
                                }
                            }
                        });
                    }

                } else if (baseAccount_last != '' && baseAccount_last_name != '') {

                    var transferAccount = $('#acc').val();
                    var transferAccount_name = $('#acc_name').val();
                    var date = $('#date').val();
                    Swal.fire({
                        title: 'Confirm Submission',
                        text: "Base Account: " + baseAccount_last_name + "\nTransfer Account: " + transferAccount_name + "\nDate: " + date,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, submit!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Proceed with form submission
                            submitForm();
                        }
                    });

                    function submitForm() {
                        // Perform the AJAX request to submit the form data
                        var url = '<?php echo base_url(); ?>settings/year_end_process/submit_last';
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: $('#form_submit').serialize(),
                            dataType: 'json',
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
                                        title: 'Error',
                                        text: response.message,
                                    });
                                }
                            }
                        });
                    }

                }
            });
        });


        // .cancel-btn click
        $('#cancel_btn').click(function() {
            // refresh page
            window.location.href = "<?php echo base_url(); ?>settings/year_end_process";
        });
    </script>
</body>


</html>