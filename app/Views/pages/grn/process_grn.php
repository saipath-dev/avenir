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
                                <div class="row">
                                    <div class="col-md-12 d-flex">
                                        <div class="me-auto">
                                            <h4 class="p-title">Process GRN</h4>
                                        </div>
                                        <div>
                                            <button class="btn btn-success px-3 finalize">Finalize</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-4">
                        <div class="card p-4">
                            <div class="body">
                                <h6 class="text-primary mb-3">Item Details</h6>
                                <form action="javascript:void(0);" id="frm_submit">
                                    <fieldset>
                                        <div class="mb-3">
                                            <label class="form-label" for="item_search">Select Item<font color="#FF0000"><strong>*</strong></font></label>
                                            <div id="the-item">
                                                <input class="form-control txt_nav typeahead" type="text" id="item_code" name="item_code_x" placeholder="" required />
                                            </div>
                                            <input type="hidden" id="itm" name="item_code">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="quantity">Quantity<font color="#FF0000"><strong>*</strong></font></label>
                                            <input id="quantity" name="quantity" placeholder="" type="text" class="form-control txt_nav" required onkeypress="return isNumberKey(event, this)" onchange="total_price(this.value)" />
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="foc_quantity">FOC Quantity</label>
                                            <input id="foc_quantity" name="foc_quantity" placeholder="0" type="text" class="form-control txt_nav" value="0" onkeypress="return isNumberKey(event, this)" />
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="p_price">Purchase Price<font color="#FF0000"><strong>*</strong></font></label>
                                            <input id="p_price" name="p_price" placeholder="" type="text" class="form-control txt_nav" required onkeypress="return isNumberKey(event, this)" onchange="total_cost(this.value)" />
                                        </div>

                                        <!-- <div class="mb-3">
                                            <label class="form-label" for="s_price">Selling Price<font color="#FF0000"><strong>*</strong></font></label>
                                            <input id="s_price" name="s_price" placeholder="" type="text" class="form-control txt_nav" required onkeypress="return isNumberKey(event, this)" />
                                        </div>

                                        <div class="mb-3">
                                            <label class=" form-label" for="w_price">Wholesale Price<font color="#FF0000"><strong>*</strong></font></label>
                                            <input id="w_price" name="w_price" placeholder="" type="text" class="form-control txt_nav" required onkeypress="return isNumberKey(event, this)" />
                                        </div> -->
                                        <div class="mb-3">
                                            <label class="form-label" for="total_u_cost">Total Units Cost<font color="#FF0000"><strong>*</strong></font></label>
                                            <input id="total_u_cost" name="total_u_cost" placeholder="" type="text" class="form-control" readonly required onkeypress="return isNumberKey(event, this)" />
                                        </div>

                                        <input id="tkn_no" name="tkn_no" placeholder="" type="hidden" class="form-control" value="<?php echo  $tkn; ?>" />
                                        <input id="supplier" name="supplier" placeholder="" type="hidden" class="form-control" value="<?php echo  $sup; ?>" />
                                        <input id="inv_no" name="inv_no" placeholder="" type="hidden" class="form-control" value="<?php echo  $inv; ?>" />
                                        <!--  -->
                                        <button type="submit" class="btn btn-primary  me-5px" id="submit_btn">Add Item</button>
                                        <button type="reset" class="btn btn-default ">Cancel</button>

                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card p-4">
                            <div class="body">
                                <h6 class="text-primary mb-3">Item List</h6>
                                <div class="d-flex flex-column">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-secondary">
                                                <tr>
                                                    <td>#</td>
                                                    <td>Code</td>
                                                    <td style="width: 10%;">Name</td>
                                                    <td style="width: 50px;">QTY</td>
                                                    <td style="width: 50px;">FOC</td>
                                                    <td class="text-end">U. Cost</td>
                                                    <!-- <td class="text-end">S. Price</td> -->
                                                    <!-- <td class="text-end">W. Price</td> -->
                                                    <td class="text-end">T. Cost</td>
                                                    <td>Remove</td>
                                                </tr>
                                            </thead>
                                            <tbody id="grn_items"></tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex my-2">
                                        <div class="me-auto">
                                            <h5 class="fw-light">Total Cost</h5>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold" id="total_cost"></h5>
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
        $('#supp_id').select2({
            placeholder: " Select",
            allowClear: true
        });
        
        // Initialize typeahead for item search
        var load_items = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                url: '<?php echo base_url(); ?>inventory/manual_grn/getSearch?term=%QUERY',
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
            name: 'load_items',
            display: 'label',
            source: load_items,
            limit: 10,
            templates: {
                suggestion: Handlebars.compile('<div class="row"><div class="col-md-12" style="font-size:14px;">{{label}}</div></div>')
            },
            highlight: true
        })
        .on("typeahead:selected", function(e, datum) {
            $('#itm').val(datum.value);
            get_DataSet(datum.value);
        });
    </script>
    <script>
        // Handle typeahead keydown events
        // $('.typeahead').keydown(function(e) {
        //     if (e.keyCode == 13) {
        //         var inputs = $(this).closest('form').find(':input:visible');
        //         inputs.eq(inputs.index(this) + 1).focus();
        //         e.preventDefault();
        //     }
        // });

        // Manual item code change handler (in case user types directly)
        // $('#item_code').on('blur', function() {
        //     var value = $(this).val();
        //     if (value) {
        //         // Try to extract item code from the text
        //         var code = value.split(' ')[0];
        //         $('#itm').val(code);
        //         get_DataSet(code);
        //     }
        // });

        function isNumberKey(evt, obj) {
            var charCode = (evt.which) ? evt.which : event.keyCode
            var value = obj.value;
            var dotcontains = value.indexOf(".") != -1;
            if (dotcontains)
                if (charCode == 46) return false;
            if (charCode == 46) return true;
            if (charCode > 31 && (charCode < 48 || charCode > 57))
                return false;
            return true;
        }

        $(function() {
            $(':input[required=""],:input[required]').bind('focusout', function() {
                if ($(this).val() == "") {
                    $(this).css("border-color", "red");
                } else {
                    $(this).css("border-color", "#ccc");
                }
            });

            $('.txt_nav:first').focus();
            var $inp = $('.txt_nav');
            $inp.bind('keydown', function(e) {
                var n = $inp.length;
                var key = e.which;
                if (key == 13) {
                    e.preventDefault();
                    var nxtIdx = $inp.index(this) + 1;
                    if (nxtIdx < n) {
                        $('.txt_nav')[nxtIdx].focus();
                    } else {
                        $('.txt_nav')[nxtIdx - 1].blur();
                        $('#submit_btn').focus();
                    }
                }
            });

        });

        function req_input_check() {
            $(':input[required=""],:input[required]').bind('keypress', function(event) {
                if (event.which == 13) {
                    if ($(this).val() == "") {
                        $(this).css("border-color", "red");
                    } else {
                        $(this).css("border-color", "#ccc");
                        $('#frm_submit').submit();
                    }
                }
            });
        }

        // s_price on chnage make w_price same
        // $("#s_price").keyup(function() {
        //     $("#w_price").val($(this).val());
        // });

        $("#submit_btn").keypress(function(event) {
            if (event.which == 13) {
                req_input_check();
            }
        });

        function get_DataSet(item) {

            $.ajax({
                url: "<?php echo base_url() ?>inventory/manual_grn/get_dataset",
                method: "GET",
                data: {
                    item: item
                },
                dataType: "json",
                success: function(response) {
                    if (response.status == 200) {
                        $("#p_price").val(parseFloat(response.data[0].pp, 2).toFixed(2));
                        // $("#s_price").val(parseFloat(response.data[0].Sales_P_Max, 2).toFixed(2));
                        // $("#w_price").val(parseFloat(response.data[0].wPrice, 2).toFixed(2));

                        // $("#p_price").keyup(function() {
                        //     $("#s_price").val($(this).val());
                        // });

                        $('#qty').focus();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: response.message,
                        });
                    }
                }
            });
        }

        function total_price(qty) {
            var price = $("#p_price").val();
            var units_cost = qty * price;
            $("#total_u_cost").val(parseFloat(units_cost).toFixed(2));
        }

        function total_cost(price) {
            var qty = $("#quantity").val();
            var units_cost = qty * price;
            $("#total_u_cost").val(parseFloat(units_cost).toFixed(2));
        }

        $("#frm_submit").on('submit', (function(e) {
            e.preventDefault();

            var item_code = $("#itm").val();

            if (item_code != null) {

                // post request
                $.ajax({
                    url: "<?php echo base_url() ?>inventory/manual_grn/add_itm",
                    method: "POST",
                    data: $(this).serialize(),
                    dataType: "json",
                    success: function(response) {
                        console.log(response);
                        if (response.status == 200) {
                            toastr.success(response.message);
                            populate_grn_items();
                            // focus on the first input
                            $('.txt_nav:first').focus();
                        } else if (response.status == 400) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: response.message,
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong!',
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
        }));

        function populate_grn_items() {

            var url = window.location.href;
            url = new URL(url);

            var inv = url.searchParams.get("inv");
            var sup = url.searchParams.get("sup");
            var tkn = url.searchParams.get("tkn");

            if (inv != null && sup != null && tkn != null) {
                $.ajax({
                    url: "<?php echo base_url() ?>inventory/manual_grn/get_grn_items_by_params",
                    method: "GET",
                    data: {
                        tkn: tkn,
                        sup: sup,
                        inv: inv
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.status == 200) {
                            $("#grn_items").html('');
                            if (response.data.length > 0) {
                                var t_cost = 0;
                                $.each(response.data, function(index, value) {
                                    var row = '<tr>';
                                    row += '<td>' + (index + 1) + '</td>';
                                    row += '<td>' + value.Stock_Card + '</td>';
                                    row += '<td><p class="text-wrap">' + value.Descrip + '</p></td>';
                                    row += '<td><input style="width: 50px;" data-sup="' + value.sup + '" data-inv="' + value.inv + '" data-tkn="' + value.tkn + '" data-stock-card="' + value.stock_card + '" type="text" class="form-control grn-qty" value="' + value.qty + '"/></td>';
                                    row += '<td><input style="width: 50px;" data-sup="' + value.sup + '" data-inv="' + value.inv + '" data-tkn="' + value.tkn + '" data-stock-card="' + value.stock_card + '" type="text" class="form-control grn-foc-qty" value="' + (value.foc || 0) + '"/></td>';
                                    row += '<td class="text-end">' + (parseFloat(value.ucost).toFixed(2)) + '</td>';
                                    // row += '<td class="text-end">' + (parseFloat(value.rtl_max).toFixed(2)) + '</td>';
                                    // row += '<td class="text-end">' + (parseFloat(value.ws_max).toFixed(2)) + '</td>';
                                    row += '<td class="text-end">' + (parseFloat((value.ucost * value.qty)).toFixed(2)) + '</td>';
                                    row += '<td><button data-sup="' + value.sup + '" data-inv="' + value.inv + '" data-tkn="' + value.tkn + '" data-stock-card="' + value.stock_card + '" class="btn btn-sm btn-danger remove"><i class="bi bi-trash-fill"></i></button></td>';
                                    row += '</tr>';
                                    $("#grn_items").append(row);
                                    t_cost += (value.ucost * value.qty);
                                });
                                $("#total_cost").html(parseFloat(t_cost).toFixed(2));
                            } else {
                                // no items in the list tr
                                var row = '<tr>';
                                row += '<td colspan="9" class="text-center">No items in the list</td>';
                                row += '</tr>';
                                $("#grn_items").append(row);
                            }

                            // reset form
                            $('#frm_submit').trigger("reset");
                            setTimeout(function() {
                                $('#item_code').focus();
                            }, 300);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: response.message,
                            });
                        }
                    }
                });
            }
        }

        $(document).ready(function() {
            populate_grn_items();

            // .remove button click
            $(document).on('click', '.remove', function() {
                // confirm swal
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to remove this item from the list!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, remove it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var sup = $(this).data('sup');
                        var inv = $(this).data('inv');
                        var tkn = $(this).data('tkn');
                        var stock_card = $(this).data('stock-card');

                        $.ajax({
                            url: "<?php echo base_url() ?>inventory/manual_grn/remove_item",
                            method: "POST",
                            data: {
                                sup: sup,
                                inv: inv,
                                tkn: tkn,
                                stock_card: stock_card
                            },
                            dataType: "json",
                            success: function(response) {
                                if (response.status == 200) {
                                    toastr.success(response.message);
                                    populate_grn_items();
                                    // focus on the first input
                                    $('.txt_nav:first').focus();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Oops...',
                                        text: response.message,
                                    });
                                }
                            }
                        });
                    }
                });
            });

            // .grn-qty keyup
            $(document).on('keyup', '.grn-qty', function() {
                // if enter key pressed
                if (event.keyCode == 13) {
                    var qty = $(this).val();
                    var sup = $(this).data('sup');
                    var inv = $(this).data('inv');
                    var tkn = $(this).data('tkn');
                    var stock_card = $(this).data('stock-card');

                    if (qty >= 0 && !isNaN(qty)) {
                        $.ajax({
                            url: "<?php echo base_url() ?>inventory/manual_grn/update_qty",
                            method: "POST",
                            data: {
                                sup: sup,
                                inv: inv,
                                tkn: tkn,
                                stock_card: stock_card,
                                qty: qty
                            },
                            dataType: "json",
                            success: function(response) {
                                if (response.status == 200) {
                                    toastr.success(response.message);
                                    populate_grn_items();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Oops...',
                                        text: response.message,
                                    });
                                }
                            }
                        });
                    }
                }
            });

            // .grn-foc-qty keyup
            $(document).on('keyup', '.grn-foc-qty', function() {
                // if enter key pressed
                if (event.keyCode == 13) {
                    var foc_qty = $(this).val();
                    var sup = $(this).data('sup');
                    var inv = $(this).data('inv');
                    var tkn = $(this).data('tkn');
                    var stock_card = $(this).data('stock-card');

                    if (foc_qty >= 0 && !isNaN(foc_qty)) {
                        $.ajax({
                            url: "<?php echo base_url() ?>inventory/manual_grn/update_foc_qty",
                            method: "POST",
                            data: {
                                sup: sup,
                                inv: inv,
                                tkn: tkn,
                                stock_card: stock_card,
                                foc_qty: foc_qty
                            },
                            dataType: "json",
                            success: function(response) {
                                if (response.status == 200) {
                                    toastr.success(response.message);
                                    populate_grn_items();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Oops...',
                                        text: response.message,
                                    });
                                }
                            }
                        });
                    }
                }
            });

            // .finalize grn
            $('.finalize').click(function() {
                var url = window.location.href;
                url = new URL(url);

                var inv = url.searchParams.get("inv");
                var sup = url.searchParams.get("sup");
                var tkn = url.searchParams.get("tkn");

                if (inv != null && sup != null && tkn != null) {
                    $.ajax({
                        url: "<?php echo base_url() ?>inventory/manual_grn/finalize_grn_by_params",
                        method: "POST",
                        data: {
                            tkn: tkn,
                            sup: sup,
                            inv: inv
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
                                        window.location.href = "<?php echo base_url() ?>inventory/manual_grn";
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
        });
    </script>
</body>

</html>