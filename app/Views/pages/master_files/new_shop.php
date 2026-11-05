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
                                        <h4 class="p-title"><?php echo isset($customer) && !empty($customer) ? 'Edit Shop Details' : 'Add Shop'; ?></h4>
                                    </div>
                                    <div style="text-align: right;">
                                        <a href="<?php echo base_url() ?>master_files/shop_registration/all" class="btn btn-primary"> View All Shops </a>
                                    </div>
                                </div>

                                <?php
                                $category = '';
                                $dealer = '';
                                $name = '';
                                $address = '';
                                $country = '';
                                $nic = '';
                                $email = '';
                                $tel_mobile = '';
                                $tel_home = '';
                                $zip_code = '';
                                $t_code_1 = '';
                                $t_code_2 = '';
                                $port = '';
                                $margin = '';
                                $long_name = '';
                                $per_tel_mobile = '';
                                $per_tel_home = '';
                                $person_name = '';
                                $per_t_code_1 = '';
                                $per_t_code_2 = '';
                                if (isset($customer) && !empty($customer)) {
                                    $category = $customer['sup_cat'];
                                    $dealer = $customer['dealer_acc'];
                                    $name = $customer['Name'];
                                    $address = $customer['Address'];
                                    $country = $customer['country'];
                                    $nic = $customer['NIC'];
                                    $email = $customer['email'];
                                    $tel_mobile = $customer['Mobile'];
                                    $tel_home = $customer['TP'];
                                    $zip_code = $customer['zipcode'];
                                    $t_code_1 = $customer['t_code_1'];
                                    $t_code_2 = $customer['t_code_2'];
                                    $t_code_2 = $customer['t_code_2'];
                                    $margin = $customer['margin'];
                                    $long_name = $customer['long_name'];
                                    $person_name = $customer['contact_person'] ?? '';
                                    $per_tel_mobile = $customer['per_mobile'] ?? '';
                                    $per_tel_home = $customer['per_tp'] ?? '';
                                    $per_t_code_1 = $customer['per_t_code_1'] ?? '';
                                    $per_t_code_2 = $customer['per_t_code_2'] ?? '';
                                }
                                ?>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-4">
                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                    <fieldset>

                                                        <!-- <h6 class="fw-bold">General Details</h6>
                                                        <hr class="border-dark"> -->
                                                        <div class="mb-3">
                                                            <label class="form-label" for="supplier_name">Shop Name<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control ui-autocomplete-input" type="text" id="supplier_name" name="supplier_name" placeholder="" value="<?php echo $name; ?>" required />
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="address">Location / Address<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="address" name="address" placeholder="" value="<?php echo $address; ?>" required />
                                                        </div>

                                                        <!-- <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Port<font color="#FF0000"><strong>*</strong></font></label>
                                                                        <div id="the-port">
                                                                            <input class="form-control txt_nav typeahead" type="text" id="port" name="port" placeholder="" value="<?php //echo $port; 
                                                                                                                                                                                    ?>" />
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="zip_code">Zip Code</label>
                                                                    <input class="form-control" type="text" id="zip_code" name="zip_code" placeholder="" value="<?php //echo $zip_code; 
                                                                                                                                                                ?>" />
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="nic">NIC/Br No</label>
                                                                    <input class="form-control" type="text" id="nic" name="nic" value="<?php //echo $nic; 
                                                                                                                                        ?>" placeholder="" />
                                                                </div>
                                                            </div>
                                                        </div> -->
                                                        <h6 class="fw-bold mt-4">Shop Contact Details</h6>
                                                        <hr class="border-dark">
                                                        <!-- <div class="mb-3">
                                                            <label class="form-label" for="email">Email</label>
                                                            <input class="form-control" type="Email" id="email" name="email" value="<?php //echo $email; 
                                                                                                                                    ?>" placeholder="" />
                                                        </div> -->

                                                        <div class="row mb-3">
                                                            <div class="col-md-4">
                                                                <label class="form-label" for="email">Email</label>
                                                                <input class="form-control" type="Email" id="email" name="email" value="<?php echo $email; ?>" placeholder="" />
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Mobile No. 1<font color="#FF0000"><strong>*</strong></font></label>
                                                                <div class="input-group w-100">
                                                                    <select name="t_code_1" id="t_code_1" class="form-control" required>
                                                                        <option value="">Code</option>
                                                                        <?php
                                                                        foreach ($countries as $t_code) {
                                                                            $selected = $t_code['phonecode'] == 94 ? 'selected' : '';
                                                                            if (!empty($t_code_1)) {
                                                                                $selected = $t_code['phonecode'] == $t_code_1 ? 'selected' : '';
                                                                            }
                                                                            echo '<option value="' . $t_code['phonecode'] . '"' . $selected . '>' .  '+' . $t_code['phonecode'] . ' - ' . $t_code['nicename'] . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                    <input type="text" class="form-control" placeholder="" id="tel_mobile" name="tel_mobile" value="<?php echo $tel_mobile; ?>" required />
                                                                </div>

                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Mobile No. 2</label>
                                                                <div class="input-group w-100">
                                                                    <select name="t_code_2" id="t_code_2" class="form-control" required>
                                                                        <option value="">Code</option>
                                                                        <?php
                                                                        foreach ($countries as $t_code) {
                                                                            $selected = $t_code['phonecode'] == 94 ? 'selected' : '';
                                                                            if (!empty($t_code_2)) {
                                                                                $selected = $t_code['phonecode'] == $t_code_2 ? 'selected' : '';
                                                                            }
                                                                            echo '<option value="' . $t_code['phonecode'] . '"' . $selected . '>' .  '+' . $t_code['phonecode'] . ' - ' . $t_code['nicename'] . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                    <input type="text" class="form-control" placeholder="" id="tel_home" name="tel_home" value="<?php echo $tel_home; ?>" />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <h6 class="fw-bold mt-4">Contact Person Details</h6>
                                                        <hr class="border-dark">
                                                        <!-- <div class="mb-3">
                                                            <label class="form-label" for="person_name">Contact Person Name<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="person_name" name="person_name" value="<?php //echo $person_name; ?>" placeholder="" required />
                                                        </div> -->

                                                        <div class="row mb-3">
                                                            <div class="col-md-4">
                                                                <label class="form-label" for="person_name">Person Name<font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="person_name" name="person_name" value="<?php echo $person_name; ?>" placeholder="" required />
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Mobile No. 1<font color="#FF0000"><strong>*</strong></font></label>
                                                                <div class="input-group w-100">
                                                                    <select name="per_t_code_1" id="per_t_code_1" class="form-control" required>
                                                                        <option value="">Code</option>
                                                                        <?php
                                                                        foreach ($countries as $t_code) {
                                                                            $selected = $t_code['phonecode'] == 94 ? 'selected' : '';
                                                                            if (!empty($per_t_code_1)) {
                                                                                $selected = $t_code['phonecode'] == $per_t_code_1 ? 'selected' : '';
                                                                            }
                                                                            echo '<option value="' . $t_code['phonecode'] . '"' . $selected . '>' .  '+' . $t_code['phonecode'] . ' - ' . $t_code['nicename'] . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                    <input type="text" class="form-control" placeholder="" id="per_tel_mobile" name="per_tel_mobile" value="<?php echo $per_tel_mobile; ?>" required />
                                                                </div>

                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Mobile No. 2</label>
                                                                <div class="input-group w-100">
                                                                    <select name="per_t_code_2" id="per_t_code_2" class="form-control" required>
                                                                        <option value="">Code</option>
                                                                        <?php
                                                                        foreach ($countries as $t_code) {
                                                                            $selected = $t_code['phonecode'] == 94 ? 'selected' : '';
                                                                            if (!empty($per_t_code_2)) {
                                                                                $selected = $t_code['phonecode'] == $per_t_code_2 ? 'selected' : '';
                                                                            }
                                                                            echo '<option value="' . $t_code['phonecode'] . '"' . $selected . '>' .  '+' . $t_code['phonecode'] . ' - ' . $t_code['nicename'] . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                    <input type="text" class="form-control" placeholder="" id="per_tel_home" name="per_tel_home" value="<?php echo $per_tel_home; ?>" />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <button type="submit" class="btn btn-success mt-4">Save</button>
                                                        <button type="reset" onclick="location.reload()" class="btn btn-default  mt-4">Cancel</button>
                                                    </fieldset>
                                                </form>
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

    <script src="<?php echo base_url(); ?>assets/js/typeahead.bundle.js"></script>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        // after document is ready, trigger country change event after 500ms
        $(document).ready(function() {
            setTimeout(function() {
                $('#country').change();
            }, 500);
        });

        // var ports = [];

        // $.ajax({
        //     type: "GET",
        //     url: "<?php //echo base_url(); 
                        ?>master_files/customer/get_all_ports",
        //     dataType: "json",
        //     success: function(response) {

        //         $.each(response, function(index, value) {
        //             ports.push(value.port_name);
        //         });
        //     },
        //     error: function(jqXHR, textStatus, errorThrown) {
        //         Swal.fire({
        //             icon: 'error',
        //             title: 'Oops...',
        //             text: 'Unable to fetch ports!',
        //         });
        //     }
        // });


        var substringMatcher = function(strs) {
            return function findMatches(q, cb) {
                var matches, substringRegex;

                matches = [];
                substrRegex = new RegExp(q, 'i');

                $.each(strs, function(i, str) {
                    if (substrRegex.test(str)) {
                        matches.push(str);
                    }
                });

                cb(matches);
            };
        };

        // $('#the-port .typeahead').typeahead({
        //     hint: true,
        //     highlight: true,
        //     minLength: 1
        // }, {
        //     name: 'ports',
        //     source: substringMatcher(ports)
        // });

        // #country on change event
        // change the country code of the mobile number t_code_1 and t_code_2
        $('#country').on('change', function() {
            var country = $(this).val();
            var country_name = $(this).find('option:selected').text();
            // foreach t_code in t_code_1 get country name seperated by '-'
            $('#t_code_1 option').each(function() {
                var t_code = $(this).text();
                var t_code_country = t_code.split(' - ');
                if (t_code_country[1] == country_name) {
                    // remove + sign from the t_code_country[0]
                    t_code_country[0] = t_code_country[0].replace('+', '');
                    $('#t_code_1').val(t_code_country[0]).change();
                }
            });
            // foreach t_code in t_code_2 get country name seperated by '-'
            $('#t_code_2 option').each(function() {
                var t_code = $(this).text();
                var t_code_country = t_code.split(' - ');
                if (t_code_country[1] == country_name) {
                    // remove + sign from the t_code_country[0]
                    t_code_country[0] = t_code_country[0].replace('+', '');
                    $('#t_code_2').val(t_code_country[0]).change();
                }
            });
        });

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


            $("#submit_btn").keypress(function(event) {
                if (event.which == 13) {
                    req_input_check();
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
                        $('#form_submit').submit();
                    }
                }
            });
        }

        $('#exampleCheck').change(function() {
            if ($(this).is(':checked')) {
                $("#Services").removeAttr("hidden");
                $("#Services1").removeAttr("hidden");
            } else {
                $("#Services").attr('hidden', true);
                $("#Services1").attr('hidden', true);
            }
        });

        function cus_type(type) {
            var type_code = type.value;
            // alert(type_code);

            if (type_code == 1) {
                $('#dealer').show('slow');
            } else if (type_code == 2) {
                $('#dealer').hide('slow');
            }

        }

        /**************************** INSERT  ****************************/

        $("#form_submit").on('submit', (function(e) {

            e.preventDefault();

            var cat = $("#sup_cat").val();
            var dealer = $("#dealer_name").val();

            if (cat == 1 && dealer == '') {
                $('#dealer_name').focus();
                return;
            }

            if (cat == 2) $("#dealer_name").val('');

            var endpoint = "<?php echo base_url('master_files/shop_registration/create'); ?>";
            var id = "<?php echo isset($customer) && !empty($customer) ? $customer['ID'] : ''; ?>";
            var acc_no = "<?php echo isset($customer) && !empty($customer) ? $customer['Sup_Acc_No'] : ''; ?>";
            if (id != '') {
                endpoint = "<?php echo base_url('master_files/shop_registration/update/'); ?>" + id + '/' + acc_no;
            }

            var url = window.location.href;
            url = new URL(url);
            var pro = url.searchParams.get("pro");

            $.ajax({
                url: endpoint,
                type: "POST",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {},
                success: function(response) {
                    if (response.status == 201 || response.status == 200) {
                        // confirmation Swal alert with cancel and ok button
                        Swal.fire({
                            title: 'Success',
                            text: response.message,
                            icon: 'success',
                            showCancelButton: true
                        }).then((result) => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                        });
                    }
                },
                error: function(e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: e.responseJSON.message ? e.responseJSON.message : 'An error occurred!',
                    });
                }
            });
        }));
    </script>
</body>




</html>