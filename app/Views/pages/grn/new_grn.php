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

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0">AI GRN Reader</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="#">GRN</a></li>
                                        <li class="breadcrumb-item active">New GRN</li>
                                    </ol>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Supplier Details</h5>
                            </div>
                            <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="alert alert-warning" id="sup_status_waiting" role="alert">
                                                <i class="bi bi-hourglass-split me-3"></i> Waiting for supplier details.
                                            </div>
                                            <div class="alert alert-primary d-none" id="sup_status_validating" role="alert">
                                                <div class="d-flex align-items-center">
                                                    <div class="spinner-border text-primary" role="status" style="width: 20px; height: 20px;">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                    <span class="ms-3">Validating Supplier...</span>
                                                </div>
                                            </div>
                                            <div class="alert alert-success d-none" id="sup_status_success" role="alert">
                                                <i class="bi bi-check-circle-fill me-3"></i> Supplier found.
                                                <div id="sup_status_changes_found"></div>
                                            </div>
                                            <div class="alert alert-danger d-none" id="sup_status_not_found" role="alert">
                                                <i class="bi bi-exclamation-triangle-fill me-3"></i> Supplier not found. Do you want to add this as a new supplier?
                                                <br>
                                                <div class="form-check mt-2 ms-4">
                                                    <input class="form-check-input" type="checkbox" value="" id="newSupCheckbox" checked>
                                                    <label class="form-check-label" for="newSupCheckbox">Yes</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="mb-3">
                                                <label class="form-label">Supplier<span class="text-danger">*</span></label>
                                                <input type="text" name="supplier" id="supplier" class="form-control" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="mb-3">
                                                <label class="form-label">Telephone</label>
                                                <input type="text" name="telephone" id="telephone" class="form-control" />
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="text" name="email" id="email" class="form-control" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="mb-3">
                                                <label class="form-label">Address</label>
                                                <input type="text" name="address" id="address" class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">GRN Items</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="mainTable" class="table table-hover js-basic-example table-custom">
                                        <thead class="table-dark">
                                            <tr>
                                                <th style="width: 5%;">#</th>
                                                <th style="width: 12%;">Code</th>
                                                <th>Description</th>
                                                <th style="width: 10%;">Qty</th>
                                                <th style="width: 10%;">FOC Qty</th>
                                                <th>Unit Price</th>
                                                <th>Discount</th>
                                                <th>VAT</th>
                                                <th>Total</th>
                                                <th style="width: 5%;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- iterate through data array -->
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-end mt-3">
                                    <button type="button" id="btnSubmit" class="btn btn-primary d-none">
                                        <i class="bi bi-check-circle-fill me-2"></i>Submit
                                    </button>
                                </div>
                            </div>

                        </div>
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

    <!-- Loading Modal -->
    <div class="modal" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="width: 300px; height: 80px; margin-left: 100px; margin-bottom: 100px;">
                <div class="modal-body">
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <img src="<?php echo base_url() ?>assets/images/loading.gif" width="30px">
                        <label class="form-label">Just a moment...</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Javascript -->
    <?= view('layouts/scripts') ?>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        let jsonData;
        let training_id;

        // scan_document form submit
        $('#scan_document').submit(function(e) {
            e.preventDefault();
            // if auction not selected
            if ($('#auction option:selected').val() == 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Please select an Auction!',
                });
                return;
            }
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>grn/new/scan_document',
                type: 'POST',
                data: new FormData(this),
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loadingModal').modal('show');
                },
                success: function(data) {
                    $('#loadingModal').modal('hide');

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        // Clear the table body
                        $(".js-basic-example tbody").html('');
                        var tr = '';

                        function assignValue(id, value) {
                            $(`#${id}`).val(value || null).change();
                        }

                        let supplier = data.supplier || {};

                        assignValue('supplier', supplier.supplier_name?.text);
                        assignValue('telephone', supplier.supplier_phone?.text);
                        assignValue('email', supplier.supplier_email?.text);
                        assignValue('address', supplier.supplier_address?.text);

                        const tableBody = document.querySelector('#mainTable tbody');

                        let rowNumber = 1; // Initialize row number

                        data.tableData.forEach(item => {
                            const row = tableBody.insertRow();

                            // Add Row Number
                            const rowNumberCell = row.insertCell();
                            rowNumberCell.textContent = rowNumber;
                            rowNumber++;

                            // Create Text Boxes Inside Table Data Cells with "form-control" class
                            const createInput = (property, type) => {
                                const cell = row.insertCell();
                                const input = document.createElement('input');
                                input.type = type;
                                input.value = property ? (type === 'text' ? property.text : property.value) : "";
                                input.classList.add('form-control'); // Add the class

                                // Add Confidence Bar (underneath the text box)
                                if (property && property.ratio) {
                                    const confidenceBar = document.createElement('div');
                                    confidenceBar.classList.add('confidence-bar');
                                    const confidenceBarFill = document.createElement('div');
                                    confidenceBarFill.classList.add('confidence-bar-fill');
                                    confidenceBarFill.style.width = (property.ratio * 100) + '%';

                                    // Set Color Based on Ratio (Corrected Order)
                                    if (property.ratio > 0.80) {
                                        confidenceBarFill.classList.add('confidence-bar-fill-green');
                                    } else if (property.ratio >= 0.50) {
                                        confidenceBarFill.classList.add('confidence-bar-fill-yellow');
                                    } else {
                                        confidenceBarFill.classList.add('confidence-bar-fill-red');
                                    }

                                    confidenceBar.appendChild(confidenceBarFill); // Append after setting color
                                    cell.appendChild(input);
                                    cell.appendChild(confidenceBar);
                                } else {
                                    cell.appendChild(input);
                                }

                                // If the input is for the product code, check if it exists in the database
                                if (property && property.type === 'line_item/product_code') {
                                    checkProductCode(input.value, input);
                                }
                            };

                            createInput(item.properties.find(prop => prop.type === 'line_item/product_code'), 'text');
                            createInput(item.properties.find(prop => prop.type === 'line_item/description'), 'text');
                            createInput(item.properties.find(prop => prop.type === 'line_item/quantity'), 'number');

                            // Add FOC Quantity field (default to 0)
                            const focCell = row.insertCell();
                            const focInput = document.createElement('input');
                            focInput.type = 'number';
                            focInput.value = 0;
                            focInput.classList.add('form-control');
                            focCell.appendChild(focInput);

                            createInput(item.properties.find(prop => prop.type === 'line_item/unit_price'), 'text');
                            createInput(item.properties.find(prop => prop.type === 'line_item/vat'), 'number');
                            createInput(item.properties.find(prop => prop.type === 'line_item/discount'), 'number');
                            createInput(item.properties.find(prop => prop.type === 'line_item/amount'), 'text');

                            // Add Action Cell
                            const actionCell = row.insertCell();
                            actionCell.innerHTML = '<button class="btn btn-danger btn-sm delete-button"><i class="bi bi-trash-fill"></i></button>';
                        });

                        // Attach the delete button click handler after the table is updated
                        $(document).ready(function() {
                            $('.delete-button').click(function() {
                                $(this).closest('tr').remove();
                            });
                        });

                        // Display submit button
                        $('#btnSubmit').removeClass('d-none');

                    });
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $('#loadingModal').modal('hide');
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                }
            });

            function checkProductCode(productCode, inputElement) {
                $.ajax({
                    url: '<?php echo base_url(); ?>grn/new/check_product_code',
                    type: 'POST',
                    data: {
                        product_code: productCode
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.exists) {
                            const message = document.createElement('small');
                            message.classList.add('text-danger');
                            message.textContent = 'Already Exists';
                            inputElement.parentNode.appendChild(message);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Error checking product code:', textStatus, errorThrown);
                    }
                });
            }

        });

        // submit data when click on #btnSubmit
        $('#btnSubmit').click(function(e) {
            e.preventDefault();

            // Gather table data
            let tableData = [];
            $('#mainTable tbody tr').each(function() {
                let row = $(this);
                let rowData = {
                    product_code: row.find('input:eq(0)').val(),
                    description: row.find('input:eq(1)').val(),
                    quantity: row.find('input:eq(2)').val(),
                    foc_quantity: row.find('input:eq(3)').val(),
                    unit_price: row.find('input:eq(4)').val(),
                    vat: row.find('input:eq(5)').val(),
                    discount: row.find('input:eq(6)').val(),
                    amount: row.find('input:eq(7)').val()
                };
                tableData.push(rowData);
            });

            // Convert table data to JSON object
            let jsonData = JSON.stringify({
                tableData: tableData
            });

            // Send JSON data via AJAX
            $.ajax({
                url: '<?php echo base_url(); ?>grn/new/submit_table_data',
                type: 'POST',
                data: jsonData,
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        // Optionally reset the form and table
                        $('#scan_document')[0].reset();
                        $(".js-basic-example tbody").html('');
                        $('#btnSubmit').addClass('d-none');
                    });
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

        // #supplier on change, validate supplier
        $('#supplier').on('change', function() {
            var supplier = $(this).val();
            if (supplier != '') {

                $('#sup_status_waiting').addClass('d-none');
                $('#sup_status_validating').removeClass('d-none');

                setTimeout(function() {
                    $.ajax({
                        url: '<?php echo base_url(); ?>grn/new/validate_supplier',
                        type: 'GET',
                        data: {
                            supplier_name: supplier
                        },
                        dataType: 'json',
                        success: function(data) {
                            $('#sup_status_validating').addClass('d-none');
                            if (data.status == 200) {
                                $('#sup_status_success').removeClass('d-none');
                                var sup_telephone = data.supplier.TP;
                                var sup_email = data.supplier.email;
                                var sup_address = data.supplier.Address;
                                if (sup_telephone != $('#telephone').val() || sup_email != $('#email').val() || sup_address != $('#address').val()) {
                                    $('#sup_status_changes_found').html('<div class="form-check" style="margin-left: 35px;"><input class="form-check-input" type="checkbox" value="" id="updateSupCheckbox" checked><label class="form-check-label" for="updateSupCheckbox">We detect that some supplier details have changed. Do you want to update them? Check if YES.</label></div>');
                                }
                            } else if (data.status == 404) {
                                $('#sup_status_not_found').removeClass('d-none');
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Something went wrong! Please try again.',
                                });
                                $('#supplier').val('');
                                $('#telephone').val('');
                                $('#email').val('');
                                $('#address').val('');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $('#sup_status_validating').addClass('d-none');
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong! Please try again later.',
                            });
                        }
                    });
                }, 2000);
            }
        });
    </script>
</body>

</html>