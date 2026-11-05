<?= view('layouts/head') ?>


<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <style type="text/css">
            .pull-right {
                float: right;
            }

            .typeahead,
            .typeahead2,
            .typeahead3,
            .tt-query,
            .tt-hint {
                display: block;
                width: 100%;
                padding: 0.4375rem 0.75rem;
                font-size: 13px;
                font-weight: 500;
                line-height: 1.5;
                text-transform: uppercase;
            }

            .tt-menu {
                width: 100%;
                max-width: 600px;
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
                font-size: 13px;
                width: 100%;
                text-transform: uppercase;
                margin-left: 0px;
                padding: 5px 10px;
            }

            .tt-suggestion:hover {
                cursor: pointer;
                color: #fff;
                background-color: #5897fb;
                width: 100%;
                margin-left: 0px;
                line-height: 2;
                text-transform: uppercase;
            }

            .tt-suggestion.tt-cursor {
                color: #fff;
                background-color: #5897fb;
                width: 100%;
                line-height: 2;
                margin-left: 0px;
                text-transform: uppercase;
            }

            .gist {
                font-size: 14px;
            }

            /* Mobile Responsive Styles */
            @media (max-width: 768px) {
                .pull-right {
                    float: none !important;
                    text-align: left;
                }

                .mb-3 .row {
                    margin-bottom: 0.5rem;
                }

                .p-title {
                    font-size: 1.2rem;
                }

                .table-responsive {
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                }

                #inv_dtl {
                    font-size: 0.85rem;
                }

                #inv_dtl th,
                #inv_dtl td {
                    padding: 0.5rem 0.25rem;
                    white-space: nowrap;
                }

                .modal-content {
                    width: 100% !important;
                    margin: 0 !important;
                }

                .panel-body {
                    max-height: 300px !important;
                }

                .btn {
                    font-size: 0.875rem;
                    padding: 0.5rem 1rem;
                }
            }

            /* Stack columns on mobile */
            @media (max-width: 767px) {

                .col-md-3,
                .col-md-7,
                .col-md-9 {
                    margin-bottom: 0.5rem;
                }
            }

            /* Previous Expenses Section Styles */
            .sticky-top {
                position: sticky;
                top: 0;
                z-index: 10;
            }

            .card-header.bg-primary {
                background-color: #5897fb !important;
            }

            @media (max-width: 768px) {
                .card-body {
                    max-height: 350px !important;
                }

                table.table-sm th,
                table.table-sm td {
                    font-size: 0.8rem;
                    padding: 0.4rem 0.3rem;
                }

                .badge {
                    font-size: 0.7rem;
                }

                .btn-sm {
                    padding: 0.25rem 0.4rem;
                    font-size: 0.75rem;
                }
            }
        </style>

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
                                            <h4 class="p-title">Entertainment Expenses Invoice</h4>
                                        </div>
                                        <div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-lg-6 col-md-12 mb-3">
                                        <div class="col-12" id="consignee_form">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <form action="javascript:void(0);" id="tracking_form" accept-charset="utf-8">
                                                        <fieldset>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                </div>
                                                            </div>


                                                            <div class="mb-3">
                                                                <div class="row">
                                                                    <div class="col-12">
                                                                        <!-- <hr> -->

                                                                        <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                                            <fieldset>
                                                                                <div class="mb-3">
                                                                                    <div class="row">
                                                                                        <div class="col-sm-3 col-12">
                                                                                            <label class="form-label" for="date">Date : </label>
                                                                                        </div>
                                                                                        <div class="col-sm-9 col-12">
                                                                                            <input class="form-control date txt_nav" type="date" id="date" name="date" value="<?= date('Y-m-d') ?>" autocomplete="off" />
                                                                                        </div>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="mb-3" id="des">
                                                                                    <div class="row">
                                                                                        <div class="col-sm-3 col-12">
                                                                                            <label class="form-label" for="desc">Description : </label>
                                                                                        </div>
                                                                                        <div class="col-sm-9 col-12">
                                                                                            <input class="form-control txt_nav" type="text" id="desc" name="desc" autocomplete="off" />
                                                                                        </div>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="mb-3">
                                                                                    <div class="row">
                                                                                        <div class="col-sm-3 col-12">
                                                                                            <label class="form-label" for="amount">Amount : </label>
                                                                                        </div>
                                                                                        <div class="col-sm-9 col-12">
                                                                                            <div class="row">
                                                                                                <div class="col-sm-7 col-12">
                                                                                                    <input class="form-control" type="text" id="amount" name="amount" />
                                                                                                </div>
                                                                                                <div class="col-sm-5 col-12">
                                                                                                    <select class="form-control" id="currency" name="currency">
                                                                                                        <option value="JPY">¥ JPY</option>
                                                                                                        <option value="USD">$ USD</option>
                                                                                                        <option value="LKR">₨ LKR</option>
                                                                                                    </select>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="mb-3">
                                                                                    <div class="row">
                                                                                        <div class="col-sm-3 col-12">
                                                                                            <label class="form-label" for="invoice_file">Upload Invoice: </label>
                                                                                        </div>
                                                                                        <div class="col-sm-9 col-12">
                                                                                            <input class="form-control txt_nav" type="file" id="invoice_file" name="invoice_file" accept="image/jpeg,image/jpg,image/png,application/pdf" />
                                                                                            <small class="text-muted">Allowed file types: JPG, PNG, PDF. Maximum size: 5MB</small>
                                                                                            <div id="upload_status" class="mt-2"></div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="modal-footer">
                                                                                    <button type="submit" id="btn_add" class="btn btn-primary me-2 ms-2 mb-2 mt-2 btn_add pull-right txt_nav">ADD INVOICE</button>

                                                                                </div>
                                                                            </fieldset>
                                                                        </form>
                                                                        <hr>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="table-responsive">
                                                                <table id="inv_dtl" class="table table-striped table-bordered align-middle">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>#</th>
                                                                            <th>Date</th>
                                                                            <!-- <th>Invoice No</th> -->
                                                                            <th>Description</th>
                                                                            <th>Amount</th>
                                                                            <th>Currency</th>
                                                                            <th>Document</th>
                                                                            <th>Remove</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>

                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                            <div class="mb-3">
                                                                <div class="row">
                                                                    <div class="col-sm-3 col-12">
                                                                        <label class="form-label" for="tot_amt">Grand Total : </label>
                                                                    </div>
                                                                    <div class="col-sm-9 col-12">
                                                                        <input class="form-control" type="text" id="tot_amt" name="tot_amt" readonly autocomplete="off" />
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <hr>
                                                            <div class="mb-3">
                                                                <div class="row">
                                                                    <div class="col-md-4">
                                                                        <label class="form-label pull-right" for="p_method">Payment Method :</label>
                                                                    </div>
                                                                    <div class="col-md-5">
                                                                        <input type="radio" class="minimal txt_nav" name="p_method" value="BT" checked required> <label> &nbsp;&nbsp;BT</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                        <input type="radio" class="minimal txt_nav" name="p_method" value="Cash" required> <label> &nbsp;&nbsp;Cash</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <hr>
                                                            <div class="col-md-12">

                                                             

                                                            </div>

                                                            <div class="text-center">
                                                                <button type="submit" class="btn btn-success me-2 txt_nav" id="save">Save</button>
                                                                <button type="reset" class="btn btn-default">Cancel</button>
                                                            </div>

                                                        </fieldset>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-12 mb-3">
                                        <div class="card">
                                            <div class="card-header bg-primary text-white">
                                                <h5 class="card-title mb-0">Previous Entertainment Expenses</h5>
                                            </div>
                                            <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                                                <div id="previousExpenses">
                                                    <?php if (!empty($entertainment)): ?>
                                                        <div class="table-responsive">
                                                            <table class="table table-hover table-sm mb-0">
                                                                <thead class="table-light sticky-top">
                                                                    <tr>
                                                                        <th class="text-nowrap">Date</th>
                                                                        <th class="text-nowrap">Description</th>
                                                                        <th class="text-nowrap text-end">Amount</th>
                                                                        <th class="text-nowrap">Paid By</th>
                                                                        <th class="text-nowrap text-center">Document</th>
                                                                        <th class="text-nowrap text-center">Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($entertainment as $expense): ?>
                                                                        <tr data-expense-id="<?= $expense['id'] ?? '' ?>">
                                                                            <td class="text-nowrap align-middle"><?= date('M d, Y', strtotime($expense['Date'])) ?></td>
                                                                            <td class="align-middle">
                                                                                <div style="max-width: 200px; white-space: normal;">
                                                                                    <?= htmlspecialchars($expense['Description']) ?>
                                                                                </div>
                                                                            </td>
                                                                            <td class="text-end align-middle">
                                                                                <strong class="text-success"><?= htmlspecialchars($expense['currency']) ?> <?= number_format($expense['Amount'], 2) ?></strong>
                                                                            </td>
                                                                            <td class="align-middle">
                                                                                <span class="badge bg-info"><?= htmlspecialchars($expense['PaidBy'] ?? $expense['paid_by'] ?? 'N/A') ?></span>
                                                                            </td>
                                                                            <td class="text-center align-middle">
                                                                                <?php if (!empty($expense['document_path']) || !empty($expense['DocumentPath'])):
                                                                                    $docPath = $expense['document_path'] ?? $expense['DocumentPath'];
                                                                                    $fileExt = strtolower(pathinfo($docPath, PATHINFO_EXTENSION));
                                                                                    $docUrl = base_url() . $docPath;
                                                                                ?>
                                                                                    <?php if ($fileExt === 'pdf'): ?>
                                                                                        <a href="<?= $docUrl ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="View PDF">
                                                                                            <i class="bi bi-file-earmark-pdf"></i>
                                                                                        </a>
                                                                                    <?php else: ?>
                                                                                        <a href="<?= $docUrl ?>" target="_blank" class="btn btn-sm btn-outline-primary view-image-btn" title="View Image" data-image="<?= $docUrl ?>">
                                                                                            <i class="bi bi-image"></i>
                                                                                        </a>
                                                                                    <?php endif; ?>
                                                                                <?php else: ?>
                                                                                    <span class="text-muted">-</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td class="text-center align-middle">
                                                                                <button class="btn btn-primary btn-sm edit-expense me-1"
                                                                                    data-id="<?= $expense['id'] ?? '' ?>"
                                                                                    data-date="<?= $expense['Date'] ?? '' ?>"
                                                                                    data-description="<?= htmlspecialchars($expense['Description'] ?? '') ?>"
                                                                                    data-amount="<?= $expense['Amount'] ?? '' ?>"
                                                                                    data-paidby="<?= htmlspecialchars($expense['PaidBy'] ?? $expense['paid_by'] ?? '') ?>"
                                                                                    data-document="<?= htmlspecialchars($expense['document_path'] ?? $expense['DocumentPath'] ?? '') ?>"
                                                                                    data-currency="<?= htmlspecialchars($expense['currency'] ?? '') ?>"
                                                                                    title="Edit">
                                                                                    <i class="bi bi-pencil"></i>
                                                                                </button>
                                                                                <button class="btn btn-danger btn-sm delete-expense" data-id="<?= $expense['id'] ?? '' ?>" title="Delete">
                                                                                    <i class="bi bi-trash"></i>
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="p-4 text-center">
                                                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                                            <p class="text-muted mt-2">No previous entertainment expenses found.</p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--   -->


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
    <!-- Loading Modal -->


    </div>

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
    <!-- end main content-->

    <!-- Document Upload Modal -->
    <div class="modal fade" id="documentUploadModal" tabindex="-1" aria-labelledby="documentUploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="documentUploadModalLabel">Upload Invoice Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="document_upload_form" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="upload_file" class="form-label">Select Document</label>
                            <input class="form-control" type="file" id="upload_file" name="upload_file" accept="image/*,.pdf" />
                            <small class="text-muted">Allowed file types: JPG, PNG, PDF. Maximum size: 5MB</small>
                        </div>
                        <div class="progress mb-3" style="display:none;">
                            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                        <div id="upload_status" class="mb-3"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="upload_document_btn" class="btn btn-primary">Upload</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imagePreviewModalLabel">Document Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="previewImage" src="" class="img-fluid" style="max-height: 70vh;" alt="Document Preview">
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Expense Modal -->
    <div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editExpenseModalLabel">Edit Entertainment Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="edit_expense_form" enctype="multipart/form-data">
                        <input type="hidden" id="edit_expense_id" name="expense_id">
                        <input type="hidden" id="edit_current_document" name="current_document">
                        <div class="mb-3">
                            <label for="edit_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="edit_date" name="date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="edit_description" name="description" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_amount" class="form-label">Amount</label>
                            <div class="row">
                                <div class="col-sm-7 col-12">
                                    <input type="text" class="form-control" id="edit_amount" name="amount" required>
                                </div>
                                <div class="col-sm-5 col-12">
                                    <select class="form-control" id="edit_currency" name="currency">
                                        <option value="JPY">¥ JPY</option>
                                        <option value="USD">$ USD</option>
                                        <option value="LKR">₨ LKR</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_paidby" class="form-label">Paid By</label>
                            <select class="form-control" id="edit_paidby" name="paidby" required>
                                <option></option>
                                <?php foreach ($paidto as $paid) { ?>
                                    <option value="<?php echo $paid['card_name'] ?>"><?php echo $paid['card_name'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_invoice_file" class="form-label">Update Document (Optional)</label>
                            <input class="form-control" type="file" id="edit_invoice_file" name="edit_invoice_file" accept="image/jpeg,image/jpg,image/png,application/pdf" />
                            <small class="text-muted">Allowed: JPG, PNG, PDF. Max size: 5MB. Leave empty to keep current document.</small>
                            <div id="edit_current_document_display" class="mt-2"></div>
                            <div id="edit_upload_preview" class="mt-2"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="save_edit_expense">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>
    <script src="https://twitter.github.io/typeahead.js/js/handlebars.js"></script>
    <script src="https://twitter.github.io/typeahead.js/releases/latest/typeahead.bundle.js"></script>

    <script>

    </script>
    <script>
        const d = new Date();
        var text = d.toLocaleDateString("en-CA");
        $("#date").val(text);
        $("#date1").val(text);

        $(document).ready(function() {


            $(".js-example-tags").select2({
                tags: true,

            });


            $("input[name='p_method']").change(function() {
                var selectedValue = $(this).val();
                if (selectedValue == 'Cheque') {

                    $('#ref').text('Cheque No : ');
                    $('#bank').closest('.col-md-12').show();
                } else if (selectedValue == 'BT') {

                    $('#ref').text('Reference No : ');
                    $('#bank').closest('.col-md-12').show();
                } else if (selectedValue == 'Cash') {

                    $('#ref').text('Reference No : ');
                    $('#bank').closest('.col-md-12').hide();
                } else if (selectedValue == 'Petty Cash') {

                    $('#ref').text('Reference No : ');
                    $('#bank').closest('.col-md-12').show();
                } else if (selectedValue == 'Debit') {

                    $('#ref').text('Reference No : ');
                    $('#bank').closest('.col-md-12').show();
                }
            });


        });


        $("#btn_inv").keyup(function(event) {
            if (event.which == 13) {
                event.preventDefault();
                $('#date').focus();
                $('#payment_add').modal('show');
            }
        });


        $("#btn_inv").click(function() {
            $('#payment_add').modal('show');
            $('#inv_no').focus();
        });


        $(".modal_close").click(function() {
            $('#payment_add').modal('hide');
            $('#chq_dtlz').modal('hide');
            $(':input[type="submit"]').prop('disabled', false);
        });

        var itemArray = [];
        $(document).on('click', '.btn_add', function(e) {
            invoice_add();
        });

        function invoice_add() {

            var date = $('#date').val();
            // var inv_no = $('#inv_no').val();
            var desc = $('#desc').val();
            var amount = $('#amount').val();
            var currency = $('#currency').val();

            if (date == '' || amount == '' || currency == '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops...',
                    text: 'Please fill all fields!',
                    showConfirmButton: false,
                    timer: 1500
                });
                return false;
            }

            // Create item data object with document path if available
            var documentData = {
                'date': date,

                'desc': desc,
                'amount': amount,
                'currency': currency

            };

            // Add document path if available
            if (window.uploadedDocumentPath) {
                documentData.document = window.uploadedDocumentName;
                documentData.document_path = window.uploadedDocumentPath;
            }

            itemArray.push(documentData);
            loadCheckOutTbl(itemArray);

            $("#date").val('');
            $("#inv_no").val('');
            $("#desc").val('');
            $("#amount").val('');
            $('#payment_add').modal('hide');

            // Reset document path after adding to item
            window.uploadedDocumentPath = null;
            window.uploadedDocumentName = null;
        }

        function loadCheckOutTbl(itemArray) {

            var type = $('#v_type').val();
            $(".divIDClass").show();
            var tableData = '';
            $('#inv_dtl tbody').html('').append(tableData);

            var l = itemArray.length;
            var tot = 0;
            for (var i = 0; i < l; i++) {
                var index = (i + 1);
                tot = parseFloat(tot) + parseFloat(itemArray[i]['amount']);
                tableData += "<tr>";
                tableData += "<td>" + index + "</td>";
                tableData += "<td>" + itemArray[i]['date'] + "</td>";
                // tableData += "<td>" + itemArray[i]['inv_no'] + "</td>";
                tableData += "<td>" + itemArray[i]['desc'] + "</td>";

                if (type == 2) {
                    tableData += "<td><input type='text' value='" + Math.round(itemArray[i]['amount']) + "' data-index='" + i + "' class='form-control edit_amnt' /></td>";
                } else {
                    tableData += "<td>" + Math.round(itemArray[i]['amount']) + "</td>";
                }
                tableData += "<td>" + itemArray[i]['currency'] + "</td>";

                // Display document if available
                if (itemArray[i]['document_path']) {
                    var fileExt = itemArray[i]['document_path'].split('.').pop().toLowerCase();
                    if (fileExt === 'pdf') {
                        tableData += "<td><a href='" + "<?php echo base_url(); ?>" + itemArray[i]['document_path'] + "' target='_blank'><i class='bi bi-file-earmark-pdf' style='font-size:1.5rem;'></i> " + (itemArray[i]['document'] || 'View PDF') + "</a></td>";
                    } else {
                        tableData += "<td><a href='" + "<?php echo base_url(); ?>" + itemArray[i]['document_path'] + "' target='_blank'><i class='bi bi-file-earmark-image' style='font-size:1.5rem;'></i> " + (itemArray[i]['document'] || 'View Image') + "</a></td>";
                    }
                } else {
                    tableData += "<td>No document</td>";
                }

                tableData += "<td align='center'><button value='" + (i + 1) + "' class='btn btn-danger btn-sm btn_remove'><span class='bi bi-trash'></span></button></td>";
                tableData += "</tr>";
            }

            $('#inv_dtl tbody').html('').append(tableData);
            $("#tot_amt").val(tot);

            // Event delegation for keyup event
            $('#inv_dtl').on('keyup', '.edit_amnt', function() {
                var amount = parseFloat($(this).val()); // Parse amount as float
                var index = $(this).data('index');

                if (!isNaN(amount)) { // Check if amount is a valid number
                    itemArray[index]['amount'] = amount;
                    updateTotalAmount(itemArray); // Call function to update total amount
                }
            });

            // Function to update total amount
            function updateTotalAmount(itemArray) {
                var total = 0;
                for (var i = 0; i < itemArray.length; i++) {
                    total += parseFloat(itemArray[i]['amount']);
                }
                $("#tot_amt").val(total);
            }
        }

        $(document).on('click', '.btn_remove', function(e) {
            var code = $(this).val();
            var removeIndex = itemArray.map(function(item) {
                return item.inv_no;
            }).indexOf(code);

            itemArray.splice(removeIndex, 1);

            loadCheckOutTbl(itemArray);
        });


        /******************************** SAVE ***************************/
        $("#save").click(function() {

            if (itemArray.length == 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops...',
                    text: 'Please add invoice!',
                    showConfirmButton: false,
                    timer: 1500
                });
                return false;
            }

            save_inv();

            // $('#pay_type').modal('show');
        });

        $("#save").keyup(function(event) {
            if (event.which == 13) {
                event.preventDefault();

                if (itemArray.length == 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Oops...',
                        text: 'Please add invoice!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    return false;
                }

                save_inv();

            }
        });


        itemArray_inv = [];

        function save_inv() {

            var grnd_tot = $("#tot_amt").val();
            var bank = $("#bank").val();
            var bank_name = $("#bank option:selected").text();
            var p_method = $("input[name='p_method']:checked").val();

            $.post("<?php echo base_url() ?>accounts/utility_bill_entertainment/save", {
                    finalize: "data",
                    itemArray: itemArray,

                    grnd_tot: grnd_tot,
                    bank: bank,
                    bank_name: bank_name,
                    p_method: p_method

                }, function(data) {
                    if (data.status === 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Added Successfully!',
                            showConfirmButton: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();


                            }
                        });
                    } else if ($.trim(data.status) === 'Error') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Error!',
                            text: 'Error occurred.',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else if (data.status === 500) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Oops...',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                }, "json")
                .fail(function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to communicate with server.',
                        showConfirmButton: false,
                        timer: 1500
                    });
                });
        }
    </script>

    <script>
        $(document).ready(function() {

            $("#btn_upload").click(function() {
                $('#documentUploadModal').modal('show');
            });
        });

        // Handle document upload
        $("#upload_document_btn").click(function() {
            var fileInput = document.getElementById('upload_file');

            if (!fileInput.files.length) {
                $('#upload_status').html('<div class="alert alert-warning">Please select a file</div>');
                return;
            }

            var file = fileInput.files[0];

            // File type validation
            var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                $('#upload_status').html('<div class="alert alert-danger">Invalid file type. Only JPG, PNG, and PDF files are allowed</div>');
                return;
            }

            // File size validation (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                $('#upload_status').html('<div class="alert alert-danger">File size exceeds limit. Maximum 5MB allowed</div>');
                return;
            }

            var formData = new FormData();
            formData.append('invoice_file', file);

            $('.progress').show();

            $.ajax({
                url: "<?php echo base_url(); ?>accounts/utility_bill_entertainment/upload_invoice",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                            $('.progress-bar').width(percentComplete + '%').html(percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(data) {
                    var response = JSON.parse(data);
                    if (response.status == 200) {
                        $('#upload_status').html('<div class="alert alert-success">' + response.message + '</div>');

                        // Store the uploaded file path for the next invoice entry
                        window.uploadedDocumentPath = response.file_path;
                        window.uploadedDocumentName = response.file_name;

                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Document uploaded successfully. It will be attached to your next invoice entry.',
                            timer: 2000
                        });

                        // Reset file input
                        $('#invoice_file').val('');
                    } else {
                        $('#upload_status').html('<div class="alert alert-danger">' + response.message + '</div>');
                        $('.progress').hide();
                    }
                },
                error: function() {
                    $('#upload_status').html('<div class="alert alert-danger">Upload failed. Please try again</div>');
                    $('.progress').hide();
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Handle file input change
            $("#invoice_file").change(function() {
                var fileInput = document.getElementById('invoice_file');

                if (!fileInput.files.length) {
                    return;
                }

                var file = fileInput.files[0];

                // File type validation
                var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    $('#upload_status').html('<div class="alert alert-danger">Invalid file type. Only JPG, PNG, and PDF files are allowed</div>');
                    fileInput.value = "";
                    return;
                }

                // File size validation (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    $('#upload_status').html('<div class="alert alert-danger">File size exceeds limit. Maximum 5MB allowed</div>');
                    fileInput.value = "";
                    return;
                }

                // Clear previous messages
                $('#upload_status').html('');

                // Create a preview if it's an image
                if (file.type.startsWith('image/')) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#upload_status').html('<div class="mt-2"><img src="' + e.target.result + '" class="img-thumbnail" style="max-height: 150px"></div>');
                    }
                    reader.readAsDataURL(file);
                } else {
                    // Show PDF icon for PDFs
                    $('#upload_status').html('<div class="mt-2"><i class="bi bi-file-earmark-pdf" style="font-size:2rem;"></i> ' + file.name + '</div>');
                }

                // Process upload automatically
                uploadInvoiceFile(file);
            });

            function uploadInvoiceFile(file) {
                var formData = new FormData();
                formData.append('invoice_file', file);

                $.ajax({
                    url: "<?php echo base_url(); ?>accounts/utility_bill_entertainment/upload_invoice",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(data) {
                        var response = JSON.parse(data);
                        if (response.status == 200) {
                            // Store the uploaded file path for the invoice entry
                            window.uploadedDocumentPath = response.file_path;
                            window.uploadedDocumentName = response.file_name;

                            $('#upload_status').append('<div class="alert alert-success mt-2">File uploaded successfully</div>');
                        } else {
                            $('#upload_status').html('<div class="alert alert-danger">' + response.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#upload_status').html('<div class="alert alert-danger">Upload failed. Please try again</div>');
                    }
                });
            }
        });
    </script>
    <script>
        // Image preview functionality
        $(document).on('click', '.view-image-btn', function(e) {
            e.preventDefault();
            var imageUrl = $(this).data('image');
            $('#previewImage').attr('src', imageUrl);
            $('#imagePreviewModal').modal('show');
        });

        // Edit expense functionality
        $(document).on('click', '.edit-expense', function() {
            var expenseId = $(this).data('id');
            var date = $(this).data('date');
            var description = $(this).data('description');
            var amount = $(this).data('amount');
            var currency = $(this).data('currency');
            var paidby = $(this).data('paidby');
            var document = $(this).data('document');

            // Format date to YYYY-MM-DD for input[type="date"]
            var formattedDate = new Date(date).toISOString().split('T')[0];

            // Populate modal fields
            $('#edit_expense_id').val(expenseId);
            $('#edit_date').val(formattedDate);
            $('#edit_description').val(description);
            $('#edit_amount').val(amount);
            $('#edit_currency').val(currency);
            $('#edit_paidby').val(paidby);
            $('#edit_current_document').val(document);
            $('#edit_invoice_file').val('');
            $('#edit_upload_preview').html('');

            // Display current document
            if (document) {
                var fileExt = document.split('.').pop().toLowerCase();
                var baseUrl = "<?php echo base_url(); ?>";
                var docUrl = baseUrl + document;
                var displayHtml = '<div class="alert alert-info">Current Document: ';

                if (fileExt === 'pdf') {
                    displayHtml += '<a href="' + docUrl + '" target="_blank"><i class="bi bi-file-earmark-pdf"></i> View PDF</a>';
                } else {
                    displayHtml += '<a href="' + docUrl + '" target="_blank"><i class="bi bi-image"></i> View Image</a>';
                }
                displayHtml += '</div>';
                $('#edit_current_document_display').html(displayHtml);
            } else {
                $('#edit_current_document_display').html('<div class="text-muted">No document attached</div>');
            }

            // Show modal
            $('#editExpenseModal').modal('show');
        });

        // Preview file on edit modal
        $('#edit_invoice_file').change(function() {
            var fileInput = this;
            if (!fileInput.files.length) {
                $('#edit_upload_preview').html('');
                return;
            }

            var file = fileInput.files[0];
            var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

            if (!allowedTypes.includes(file.type)) {
                $('#edit_upload_preview').html('<div class="alert alert-danger">Invalid file type. Only JPG, PNG, and PDF files are allowed</div>');
                fileInput.value = "";
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                $('#edit_upload_preview').html('<div class="alert alert-danger">File size exceeds limit. Maximum 5MB allowed</div>');
                fileInput.value = "";
                return;
            }

            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#edit_upload_preview').html('<div class="alert alert-success">New file selected: <img src="' + e.target.result + '" class="img-thumbnail mt-2" style="max-height: 100px"></div>');
                }
                reader.readAsDataURL(file);
            } else {
                $('#edit_upload_preview').html('<div class="alert alert-success">New file selected: <i class="bi bi-file-earmark-pdf"></i> ' + file.name + '</div>');
            }
        });

        // Save edited expense
        $('#save_edit_expense').click(function() {
            var expenseId = $('#edit_expense_id').val();
            var date = $('#edit_date').val();
            var description = $('#edit_description').val();
            var amount = $('#edit_amount').val();
            var currency = $('#edit_currency').val();
            var paidby = $('#edit_paidby').val();

            if (!date || !description || !amount || !paidby || !currency) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Warning',
                    text: 'Please fill all fields',
                    showConfirmButton: false,
                    timer: 1500
                });
                return;
            }

            // Create FormData to handle file upload
            var formData = new FormData();
            formData.append('expense_id', expenseId);
            formData.append('date', date);
            formData.append('description', description);
            formData.append('amount', amount);
            formData.append('currency', currency);
            formData.append('paidby', paidby);

            // Add file if selected
            var fileInput = document.getElementById('edit_invoice_file');
            if (fileInput.files.length > 0) {
                formData.append('invoice_file', fileInput.files[0]);
            }

            $.ajax({
                url: "<?php echo base_url(); ?>accounts/utility_bill_entertainment/update_expense",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: 'Expense has been updated.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function() {
                            $('#editExpenseModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to update expense',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to communicate with server',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            });
        });

        // Delete expense functionality
        $(document).on('click', '.delete-expense', function() {
            var expenseId = $(this).data('id');
            var row = $(this).closest('tr');

            if (!expenseId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Invalid expense ID',
                    showConfirmButton: false,
                    timer: 1500
                });
                return;
            }

            tfConfirmReason({ // tf-e8-access-r1: reason required
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "<?php echo base_url(); ?>accounts/utility_bill_entertainment/delete_expense",
                        type: "POST",
                        data: {
                            expense_id: expenseId,
                            reason: result.value // tf-e8-access-r1
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'Expense has been deleted.',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                                row.fadeOut(300, function() {
                                    $(this).remove();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'Failed to delete expense',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to communicate with server',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>