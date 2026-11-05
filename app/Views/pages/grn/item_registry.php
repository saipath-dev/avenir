<?= view('layouts/head') ?>

<body>

    <style>
        span.twitter-typeahead .tt-menu,
        span.twitter-typeahead .tt-dropdown-menu {
            cursor: pointer;
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            display: none;
            float: left;
            width: fit-content;
            min-width: 160px;
            padding: 5px 0;
            margin: 2px 0 0;
            list-style: none;
            font-size: 14px;
            text-align: left;
            background-color: #ffffff;
            border: 1px solid #cccccc;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 4px;
            -webkit-box-shadow: 0 6px 12px rgba(0, 0, 0, 0.175);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.175);
            background-clip: padding-box;
        }

        span.twitter-typeahead .tt-suggestion {
            display: block;
            padding: 3px 20px;
            clear: both;
            font-weight: normal;
            line-height: 1.42857143;
            color: #333333;
            white-space: nowrap;
        }

        span.twitter-typeahead .tt-suggestion.tt-cursor,
        span.twitter-typeahead .tt-suggestion:hover,
        span.twitter-typeahead .tt-suggestion:focus {
            color: #ffffff;
            text-decoration: none;
            outline: 0;
            background-color: #0069d9;
        }

        .input-group.input-group-lg span.twitter-typeahead .form-control {
            height: 46px;
            padding: 10px 16px;
            font-size: 18px;
            line-height: 1.3333333;
            border-radius: 6px;
        }

        .input-group.input-group-sm span.twitter-typeahead .form-control {
            height: 30px;
            padding: 5px 10px;
            font-size: 12px;
            line-height: 1.5;
            border-radius: 3px;
        }

        span.twitter-typeahead {
            width: 100%;
        }

        .input-group span.twitter-typeahead {
            display: block !important;
            height: 34px;
        }

        .input-group span.twitter-typeahead .tt-menu,
        .input-group span.twitter-typeahead .tt-dropdown-menu {
            top: 32px !important;
        }

        .input-group span.twitter-typeahead:not(:first-child):not(:last-child) .form-control {
            border-radius: 0;
        }

        .input-group span.twitter-typeahead:first-child .form-control {
            border-top-left-radius: 4px;
            border-bottom-left-radius: 4px;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group span.twitter-typeahead:last-child .form-control {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
        }

        .input-group.input-group-sm span.twitter-typeahead {
            height: 30px;
        }

        /* Custom autocomplete styles */
        .autocomplete-results {
            position: absolute;
            z-index: 1000;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            max-height: 200px;
            overflow-y: auto;
            width: calc(100% - 2px);
            margin-top: 2px;
        }

        .autocomplete-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }

        .autocomplete-item:hover {
            background-color: #0069d9;
            color: white;
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .input-group.input-group-sm span.twitter-typeahead .tt-menu,
        .input-group.input-group-sm span.twitter-typeahead .tt-dropdown-menu {
            top: 30px !important;
        }

        .input-group.input-group-lg span.twitter-typeahead {
            height: 46px;
        }

        .input-group.input-group-lg span.twitter-typeahead .tt-menu,
        .input-group.input-group-lg span.twitter-typeahead .tt-dropdown-menu {
            top: 46px !important;
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

                <!-- <?php //echo $block_header; 
                        ?> -->

                                <div class="row">
                                    <div class="col-md-10 d-flex">
                                        <h4 class="p-title">Item Registry</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="d-flex border-bottom pb-3">
                                                <div class="me-auto">
                                                    <div class="header">
                                                        <span class="badge bg-dark fs-6 mt-2"><?php echo count($items) . " items found." ?></span>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end align-items-center me-3">
                                                    <button data-bs-toggle="offcanvas" data-bs-target="#itemOffcanvas" aria-controls="itemOffcanvas" class="btn btn-primary" style="height: fit-content;">+ New Item</button>
                                                </div>
                                            </div>
                                            <div class="body">
                                                <div class="table-responsive mt-4">
                                                    <table class="table table-hover js-basic-example dataTable table-custom align-middle">
                                                        <thead class="thead-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Code</th>
                                                <th>Description</th>
                                                <!-- <th>Main Category</th> -->
                                                <!-- <th>Sub Category</th> -->
                                                <th>Purchase Price</th>
                                                <th>Linked Activity</th>
                                                <!-- <th>Selling Price</th> -->
                                                <!-- <th>W/Sale Price</th> -->
                                                <th>Reorder</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;
                                            foreach ($items as $item) {
                                            ?>
                                                <tr>
                                                    <td><?= $i++; ?></td>
                                                    <td><?= $item['Stock_Card']; ?></td>
                                                    <td>
                                                        <strong><?= $item['Descrip']; ?></strong>
                                                    </td>
                                                    <td ><?= number_format($item['pp'], 2); ?></td>
                                                    <td><?= !empty($item['entrance_fee_name']) ? $item['entrance_fee_name'] . ' (' . $item['entrance_fee_code'] . ')' : '-'; ?></td>
                                                    <td><?= $item['reorder']; ?></td>
                                                    <td style="width: 5%;">
                                                        <div class="dropup">
                                                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="bi bi-three-dots-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu" style="z-index: 99999;">
                                                                <li>
                                                                    <a href="#" class="dropdown-item update_btn" data-id="<?= $item['ID']; ?>">Edit</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
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

        <!-- Item Create offcanvas -->
        <div class="offcanvas offcanvas-end" id="itemOffcanvas" aria-labelledby="itemOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 id="itemOffcanvasLabel">Create New Item</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="">

                    <div class="">

                        <form id="item_insert_form">
                            <div class="">
                                <div class="">
                                    <div class="row clearfix">
                                        <div class="col-md-12">
                                            
                                    <input type="hidden" id="main_cat" name="main_cat" value="1" />
                                    <input type="hidden" id="sub_cat" name="sub_cat" value="1" />
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="name">Description<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="name" name="name" placeholder="" onkeyup="focus_to_next_input('name', event)" required />
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="code">Code<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="code" name="code" placeholder="" onkeyup="focus_to_next_input('code', event)" required />
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="reorder">Reorder Level<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="reorder" name="reorder" placeholder="" onkeyup="focus_to_next_input('reorder', event)" required />
                                        </div>
                                    </div>
                                    <div class="col-md-12 my-2 mt-4">
                                        <h5 class="text-dark">Pricing</h5>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="purchase_price">Purchase Price<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="purchase_price" name="purchase_price" placeholder="" onkeyup="focus_to_next_input('purchase_price', event)" required />
                                        </div>
                                    </div>
                                    <!-- <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label" for="selling_price">Selling Price<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="selling_price" name="selling_price" placeholder="" onkeyup="focus_to_next_input('selling_price', event)" required />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label" for="w_price">Whole-Sale Price<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="w_price" name="w_price" placeholder="" onkeyup="focus_to_next_input('w_price', event)" required />
                                        </div>
                                    </div> -->
                                    <div class="col-md-12 my-2 mt-4">
                                        <h5 class="text-dark">Tour </h5>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="entrance_fee_name">Link to Tour Activity</label>
                                            <input class="form-control" type="text" id="entrance_fee_name" name="entrance_fee_name" placeholder="Search tour activities..." />
                                            <input type="hidden" id="entrance_fee_id" name="entrance_fee_id" value="" />
                                            <small class="text-muted">Optional: Link this item to a tour activity (e.g., tickets, passes)</small>
                                        </div>
                                    </div>
                                            <div class="row clearfix">
                                                <div class="form-group">
                                                    <button type="submit" class="btn btn-primary my-3 save">Create Item</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        <!-- Item Edit offcanvas -->
        <div class="offcanvas offcanvas-end" id="itemEditOffcanvas" aria-labelledby="itemEditOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 id="itemEditOffcanvasLabel">Update Item Details</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="">

                    <div class="">

                        <form id="item_update_form">
                            <input type="hidden" id="e_id" name="id" value="" />
                            <div class="">
                                <div class="">
                                    <div class="row clearfix">
                                        <div class="col-md-12">
                                            <div class="col-md-12 my-2">
                                                <h5 class="text-dark">Update Details</h5>
                                            </div>
                                         
                                    <!-- <div class="col-md-12" style="display: none;">
                                        <div class="form-group">
                                            <label class="form-label" for="main_cat">Main Category<font color="#FF0000"><strong>*</strong></font></label>
                                            <div id="the-main-cats">
                                                <input class="form-control typeahead" type="text" id="e_main_cat" name="main_cat" placeholder="" onkeyup="focus_to_next_input('e_main_cat', event)" required />
                                            </div>
                                        </div>
                                    </div> -->
                                    <!-- <div class="col-md-12" style="display: none;">
                                        <div class="form-group">
                                            <label class="form-label" for="sub_cat">Sub Category<font color="#FF0000"><strong>*</strong></font></label>
                                            <div id="the-sub-cats">
                                                <input class="form-control typeahead" type="text" id="e_sub_cat" name="sub_cat" placeholder="" onkeyup="focus_to_next_input('e_sub_cat', event)" required />
                                            </div>
                                        </div>
                                    </div> -->
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="name">Description<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="e_name" name="name" placeholder="" onkeyup="focus_to_next_input('e_name', event)" required />
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="code">Code<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="e_code" name="code" placeholder="" onkeyup="focus_to_next_input('e_code', event)" required />
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="reorder">Reorder Level<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="e_reorder" name="reorder" placeholder="" onkeyup="focus_to_next_input('e_reorder', event)" required />
                                        </div>
                                    </div>
                                    <div class="col-md-12 my-2 mt-4">
                                        <h5 class="text-dark">Pricing</h5>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="purchase_price">Purchase Price<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="e_purchase_price" name="purchase_price" placeholder="" onkeyup="focus_to_next_input('e_purchase_price', event)" required />
                                        </div>
                                    </div>
                                    <!-- <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label" for="selling_price">Selling Price<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="e_selling_price" name="selling_price" placeholder="" onkeyup="focus_to_next_input('e_selling_price', event)" required />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label" for="w_price">Whole-Sale Price<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="e_w_price" name="w_price" placeholder="" onkeyup="focus_to_next_input('e_w_price', event)" required />
                                        </div>
                                    </div> -->
                                    <div class="col-md-12 my-2 mt-4">
                                        <h5 class="text-dark">Tour Activity Link (Optional)</h5>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="e_entrance_fee_name">Link to Tour Activity/Entrance Fee</label>
                                            <input class="form-control" type="text" id="e_entrance_fee_name" name="entrance_fee_name" placeholder="Search tour activities..." />
                                            <input type="hidden" id="e_entrance_fee_id" name="entrance_fee_id" value="" />
                                            <small class="text-muted">Optional: Link this item to a tour activity (e.g., tickets, passes)</small>
                                        </div>
                                    </div>
                                            <div class="row clearfix">
                                                <div class="form-group">
                                                    <button type="submit" class="btn btn-primary px-5 my-3 update_form"><i class="bi bi-check-circle-fill"></i>&nbsp;&nbsp;Update Item</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        var main_categories = [];
        var sub_categories = [];

        // get all main categories
        // $.ajax({
        //     type: "GET",
        //     url: "<?php echo base_url(); ?>inventory/main_category/all",
        //     dataType: "json",
        //     success: function(response) {
        //         $.each(response, function(index, value) {
        //             main_categories.push(value.Name);
        //         });
        //     },
        //     error: function(jqXHR, textStatus, errorThrown) {
        //         Swal.fire({
        //             icon: 'error',
        //             title: 'Oops...',
        //             text: 'Unable to fetch main categories!',
        //         });
        //     }
        // });

        // get all sub categories
        // $.ajax({
        //     type: "GET",
        //     url: "<?php echo base_url(); ?>inventory/sub_category/all",
        //     dataType: "json",
        //     success: function(response) {
        //         $.each(response, function(index, value) {
        //             sub_categories.push(value.Name);
        //         });
        //     },
        //     error: function(jqXHR, textStatus, errorThrown) {
        //         Swal.fire({
        //             icon: 'error',
        //             title: 'Oops...',
        //             text: 'Unable to fetch sub categories!',
        //         });
        //     }
        // });


        // var substringMatcher = function(strs) {
        //     return function findMatches(q, cb) {
        //         var matches, substringRegex;

        //         matches = [];
        //         substrRegex = new RegExp(q, 'i');

        //         $.each(strs, function(i, str) {
        //             if (substrRegex.test(str)) {
        //                 matches.push(str);
        //             }
        //         });

        //         cb(matches);
        //     };
        // };

        // $('#the-main-cats .typeahead').typeahead({
        //     hint: true,
        //     highlight: true,
        //     minLength: 1
        // }, {
        //     name: 'main_categories',
        //     source: substringMatcher(main_categories)
        // });

        // $('#the-sub-cats .typeahead').typeahead({
        //     hint: true,
        //     highlight: true,
        //     minLength: 1
        // }, {
        //     name: 'sub_categories',
        //     source: substringMatcher(sub_categories)
        // });

        function focus_to_next_input(id, event) {
            // check if enter key is pressed
            if (event.key == 'Enter') {
                // id
                var id = '#' + id;
                // get value
                var value = $(id).val();
                if (value == '') {
                    make_danger_border(id);
                    return;
                }
                // if main_cat is focused, focus to sub_cat
                switch (id) {
                    // case '#main_cat':
                    //     $('#sub_cat').focus();
                    //     break;
                    // case '#sub_cat':
                    //     $('#name').focus();
                    //     break;
                    case '#name':
                        $('#code').focus();
                        break;
                    case '#code':
                        $('#reorder').focus();
                        break;
                    case '#reorder':
                        $('#purchase_price').focus();
                        break;
                    case '#purchase_price':
                        $('#selling_price').focus();
                        break;
                    // case '#selling_price':
                    //     $('#w_price').focus();
                    //     break;
                    // case '#w_price':
                    //     $('.save').focus();
                    //     break;
                }

                make_default_border(id);
            } else if (id == 'selling_price') {
                $('#w_price').val($('#selling_price').val());
            }
        }

        function make_danger_border(id) {
            // make border red, border size 2px
            $(id).css('border-color', 'red');
            $(id).css('border-width', '2px');
        }

        function make_default_border(id) {
            // make border red, border size 2px
            $(id).css('border-color', '#ced4da');
            $(id).css('border-width', '1px');
        }

        // offcanvas shown, focus to main_cat
        $('#itemOffcanvas').on('shown.bs.offcanvas', function() {
            $('#main_cat').focus();
        });

        // Custom autocomplete for entrance fee (create form)
        let entranceFeeTimeout;
        $("#entrance_fee_name").on('keyup', function() {
            const searchTerm = $(this).val();
            const $input = $(this);

            if (searchTerm.length < 1) {
                $input.next('.autocomplete-results').remove();
                return;
            }

            clearTimeout(entranceFeeTimeout);
            entranceFeeTimeout = setTimeout(function() {
                $.ajax({
                    url: "<?php echo base_url(); ?>inventory/item_registry/get_entrance_fees",
                    type: "GET",
                    dataType: "json",
                    data: { term: searchTerm },
                    success: function(data) {
                        $input.next('.autocomplete-results').remove();

                        if (data.length > 0) {
                            const $results = $('<div class="autocomplete-results"></div>');

                            data.forEach(function(item) {
                                const $item = $('<div class="autocomplete-item"></div>')
                                    .text(item.label)
                                    .data('item', item)
                                    .on('click', function() {
                                        const selectedItem = $(this).data('item');
                                        $("#entrance_fee_name").val(selectedItem.label);
                                        $("#entrance_fee_id").val(selectedItem.id);
                                        $results.remove();
                                    });
                                $results.append($item);
                            });

                            $input.after($results);
                        }
                    }
                });
            }, 300);
        });

        // Custom autocomplete for entrance fee (edit form)
        let editEntranceFeeTimeout;
        $("#e_entrance_fee_name").on('keyup', function() {
            const searchTerm = $(this).val();
            const $input = $(this);

            if (searchTerm.length < 1) {
                $input.next('.autocomplete-results').remove();
                return;
            }

            clearTimeout(editEntranceFeeTimeout);
            editEntranceFeeTimeout = setTimeout(function() {
                $.ajax({
                    url: "<?php echo base_url(); ?>inventory/item_registry/get_entrance_fees",
                    type: "GET",
                    dataType: "json",
                    data: { term: searchTerm },
                    success: function(data) {
                        $input.next('.autocomplete-results').remove();

                        if (data.length > 0) {
                            const $results = $('<div class="autocomplete-results"></div>');

                            data.forEach(function(item) {
                                const $item = $('<div class="autocomplete-item"></div>')
                                    .text(item.label)
                                    .data('item', item)
                                    .on('click', function() {
                                        const selectedItem = $(this).data('item');
                                        $("#e_entrance_fee_name").val(selectedItem.label);
                                        $("#e_entrance_fee_id").val(selectedItem.id);
                                        $results.remove();
                                    });
                                $results.append($item);
                            });

                            $input.after($results);
                        }
                    }
                });
            }, 300);
        });

        // Close autocomplete when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#entrance_fee_name, #e_entrance_fee_name, .autocomplete-results').length) {
                $('.autocomplete-results').remove();
            }
        });

        // .save button clicked
        $('#item_insert_form').submit(function(e) {
            e.preventDefault();

            var main_cat = $('#main_cat').val();
            var sub_cat = $('#sub_cat').val();
            var name = $('#name').val();
            var code = $('#code').val();
            var reorder = $('#reorder').val();
            var purchase_price = $('#purchase_price').val();
            var entrance_fee_id = $('#entrance_fee_id').val();
            // var selling_price = $('#selling_price').val();
            // var w_price = $('#w_price').val();

            $.ajax({
            type: "POST",
            url: "<?php echo base_url(); ?>inventory/item_registry/create",
            data: {
                main_cat: main_cat,
                sub_cat: sub_cat,
                description: name,
                code: code,
                reorder: reorder,
                purchase_price: purchase_price,
                entrance_fee_id: entrance_fee_id,
                // sales_price: selling_price,
                // whole_sale_price: w_price
            },
            dataType: "json",
            success: function(response) {
                if (response.status == 201) {
                // hide offcanvas
                $('#itemOffcanvas').offcanvas('hide');
                $('#main_cat').val('1');
                $('#sub_cat').val('1');
                $('#name').val('');
                $('#code').val('');
                $('#reorder').val('');
                $('#purchase_price').val('');
                $('#entrance_fee_name').val('');
                $('#entrance_fee_id').val('');
                // $('#selling_price').val('');
                // $('#w_price').val('');
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
                    icon: 'warning',
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
        });

        // update button clicked
        $(document).on('click', '.update_btn', function() {
            var id = $(this).data('id');
            $.ajax({
                type: "GET",
                url: "<?php echo base_url(); ?>inventory/item_registry/get_item/" + id,
                dataType: "json",
                success: function(response) {
                    $('#e_id').val(response.ID);
                    $('#e_main_cat').val(response.main_cat);
                    $('#e_sub_cat').val(response.sub_cat);
                    $('#e_name').val(response.Descrip);
                    $('#e_code').val(response.Stock_Card);
                    $('#e_reorder').val(response.reorder);
                    $('#e_purchase_price').val(parseFloat(response.pp).toFixed(2));

                    // Set entrance fee if linked
                    if (response.entrance_fee_id) {
                        $('#e_entrance_fee_id').val(response.entrance_fee_id);
                        $('#e_entrance_fee_name').val(response.entrance_fee_name + ' (' + response.entrance_fee_code + ') - ' + response.entrance_fee_location);
                    } else {
                        $('#e_entrance_fee_id').val('');
                        $('#e_entrance_fee_name').val('');
                    }

                    $('#itemEditOffcanvas').offcanvas('show');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong!',
                    });
                }
            });
        });

        // .update_form button clicked
        $('#item_update_form').submit(function(e) {

            e.preventDefault();

            var id = $('#e_id').val();
            var main_cat = 1;
            var sub_cat = 1;
            var name = $('#e_name').val();
            var code = $('#e_code').val();
            var reorder = $('#e_reorder').val();
            var purchase_price = $('#e_purchase_price').val();
            var entrance_fee_id = $('#e_entrance_fee_id').val();

            $.ajax({
            type: "POST",
            url: "<?php echo base_url(); ?>inventory/item_registry/update",
            data: {
                id: id,
                main_cat: main_cat,
                sub_cat: sub_cat,
                description: name,
                code: code,
                reorder: reorder,
                purchase_price: purchase_price,
                entrance_fee_id: entrance_fee_id
            },
            dataType: "json",
            success: function(response) {
                if (response.status == 200) {
                // hide offcanvas
                $('#itemEditOffcanvas').offcanvas('hide');
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
                    icon: 'warning',
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
        });
    </script>

</body>

</html>