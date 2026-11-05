<?= view('layouts/head') ?>

<body>
    <?php //if ($is_permission == 1) { 
    ?>

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
                                    <div class="col-md-4 d-flex">
                                        <h4 class="p-title">Service Provider</h4>

                                    </div>

                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-5">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                    <input type="hidden" name="inv" id="inv" value="<?php echo $inv_id; ?>">
                                                    <fieldset>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="pay_type">Income Type<font color="#FF0000"><strong>*</strong></font></label>
                                                            <select name="pay_type" id="pay_type" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                <option value="">Select a Type...</option>
                                                                <?php
                                                                foreach ($methods as $id) {

                                                                    $txt = ($id['L_Code'] == '5-7-7-10389') ? 'selected' : '';

                                                                    echo '<option value="' . $id['L_Code']  . '" ' . $txt . '>' . $id['PName'] . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="inv">Invoice No <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="inv" name="inv" placeholder="" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="amount_jpy">Amount (JPY) <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="number" step="0.01" id="amount_jpy" name="amount_jpy" placeholder="Enter amount in JPY" style="max-width: 250px;" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="conversion_rate">Conversion Rate (JPY to LKR) <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="number" step="0.0001" id="conversion_rate" name="conversion_rate" placeholder="e.g., 0.5678" style="max-width: 250px;" required />
                                                            <small class="form-text text-muted">Enter the JPY to LKR conversion rate</small>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="amount_lkr">Amount (LKR)</label>
                                                            <input class="form-control" type="number" step="0.01" id="amount_lkr" name="amount_lkr" placeholder="Auto-calculated" style="max-width: 250px; background-color: #e9ecef;" readonly />
                                                            <small class="form-text text-muted">Automatically calculated: JPY × Rate</small>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="date">Date <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="date" name="date" value="<?php echo date('Y-m-d') ?>" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Staff Invoice</label>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" id="is_staff_invoice" name="is_staff_invoice" value="1">
                                                                <label class="form-check-label" for="is_staff_invoice">
                                                                    Yes (LION TOUR LTD)
                                                                </label>
                                                            </div>
                                                            <small class="form-text text-muted">Toggle for staff invoice format</small>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="description">Description<font color="#FF0000"><strong>*</strong></font></label>
                                                            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                                                            <small class="form-text text-muted" id="description_help">For staff invoices, description is auto-generated</small>
                                                        </div>
                                                        <br>
                                                        <button type="submit" class="btn btn-success w-100px me-5px">Save</button>
                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7">

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

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        // Auto-calculate LKR amount when JPY or conversion rate changes
        function calculateLKR() {
            const amountJPY = parseFloat($('#amount_jpy').val()) || 0;
            const conversionRate = parseFloat($('#conversion_rate').val()) || 0;

            if (amountJPY > 0 && conversionRate > 0) {
                const amountLKR = amountJPY * conversionRate;
                $('#amount_lkr').val(amountLKR.toFixed(2));
            } else {
                $('#amount_lkr').val('');
            }
        }

        // Attach event handlers for real-time calculation
        $('#amount_jpy, #conversion_rate').on('input change', function() {
            calculateLKR();
        });

        // Handle Staff Invoice toggle
        function updateDescriptionField() {
            if ($('#is_staff_invoice').is(':checked')) {
                // Auto-populate description for staff invoice
                const description = "Office and Other Facilities - Three Staff\r\n(One Manager and Two Staff)";
                $('#description').val(description);
                $('#description').prop('readonly', true);
                $('#description').css('background-color', '#e9ecef');
            } else {
                // Allow manual entry for regular invoice
                $('#description').val('');
                $('#description').prop('readonly', false);
                $('#description').css('background-color', '#ffffff');
            }
        }

        // Attach event handler for staff invoice checkbox
        $('#is_staff_invoice').on('change', function() {
            updateDescriptionField();
        });

        $(document).ready(function() {
            // Initialize description field state on page load
            updateDescriptionField();

            // block enter key for form submission
            $(window).keydown(function(event) {
                if (event.keyCode == 13) {
                    event.preventDefault();
                    return false;
                }
            });

            // Submit form data via Ajax
            $('#form_submit').submit(function(e) {
                e.preventDefault();

                // disable the submit button.
                $('button[type="submit"]').prop('disabled', true);

                $.ajax({
                    url: "<?php echo base_url() ?>accounts/other_invoice/add_invoice",
                    type: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    cache: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {


                            window.open("<?php echo base_url(); ?>accounts/other_invoice/print?inv=" + response.inv,);
                            // window.location.href = "<?php //echo base_url(); ?>tour/tour_details";

                        } else if (response.status == 400) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong! Please try again later.',
                        });

                    }
                });
            });

        });
    </script>
</body>

</html>