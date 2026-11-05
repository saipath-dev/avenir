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
                                        <h4 class="p-title">Manual GRN</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-8">
                                        <div class="card p-4">
                                            <div class="body">
                                                <h6 class="text-primary mb-3">GRN Details</h6>
                                <input type="hidden" name="menu" id="menu" value="<?php //echo  $this->uri->segment(4); 
                                                                                    ?>">
                                <form action="javascript:void(0);" id="frm_submit" accept-charset="utf-8">
                                    <fieldset>
                                        <div class="mb-3">
                                            <label class="form-label" for="supp_id">Select Supplier/Auction<font color="#FF0000"><strong>*</strong></font></label>
                                            <select class="form-control txt_nav" name="user_id" id="supp_id" required>
                                                <option value=""></option>
                                                <?php foreach ($load_supplier as $value) { ?>
                                                    <option value="<?php echo $value['Sup_Acc_No']; ?>"><?php echo $value['Name']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="mb-3 pending_inv" style="display: none;">
                                            <label class="form-label" for="p_invoice">Pending Invoice<font color="#FF0000"><strong>*</strong></font></label>
                                            <select class="form-control" name="p_invoice" id="p_invoice"></select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="inv_no">Invoice No<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control txt_nav" type="text" id="inv_no" name="inv_no" placeholder="" required />
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="app_date">Invoice Date<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control txt_nav" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" data-date-autoclose="true" id="app_date" name="app_date" value="<?php echo date('Y-m-d'); ?>" required />
                                        </div>
                                        <!--  -->
                                        <button type="submit" class="btn btn-success me-2">Create GRN</button>
                                        <button type="reset" class="btn btn-default">Cancel</button>

                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-12 mt-3" id="pending_grns" style="display: none;">
                        <div class="card p-4">
                            <div class="body">
                                <h6 class="text-primary mb-3">Recent GRNs</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>#</th>
                                                <th>Supplier</th>
                                                <th>Invoice No</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="grn_list">
                                            <!-- Dynamic content will be loaded here -->
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
    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $('#supp_id').select2({
            placeholder: " Select",
            allowClear: true
        });
    </script>
    <script>
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
                        $('#frm_submit').submit();
                    }
                }
            });
        }

        $("#supp_id").change(function() {
            var supp = $(this).val();
            $.ajax({
                url: "<?php echo base_url() ?>inventory/manual_grn/get_pending",
                method: "GET",
                data: {
                    supp: supp
                },
                dataType: "json",
                success: function(data) {
                    if (data.length > 0) {
                        $(".pending_inv").show();
                        $("#p_invoice").html('');
                        $("#p_invoice").append('<option value=""></option>');
                        $.each(data, function(key, value) {
                            $("#p_invoice").append('<option value="' + value.inv_no + '">' + value.order_no + '</option>');
                        });
                    } else {
                        $(".pending_inv").hide();
                    }
                }
            });
        });

        $("#p_invoice").change(function() {
            var inv = $(this).val();
            if (inv != '') {
                window.location.href = "<?php echo base_url() ?>inventory/manual_grn?inv=" + $(this).val() + "&sup=" + $("#supp_id").val() + "&tkn=" + $('#p_invoice option:selected').text();
            }
        });

        $("#frm_submit").on('submit', (function(e) {
            e.preventDefault();
            
            // Show loading state
            var submitBtn = $(this).find('button[type="submit"]');
            var originalText = submitBtn.text();
            submitBtn.prop('disabled', true).text('Creating...');
            
            $.ajax({
                url: "<?php echo base_url() ?>inventory/manual_grn/addGrnInvoice",
                type: "POST",
                dataType: 'json',
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                success: function(data) {
                    submitBtn.prop('disabled', false).text(originalText);
                    
                    if (data.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'GRN created successfully!',
                            showConfirmButton: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                var url = "<?php echo base_url() ?>inventory/manual_grn?inv=" + data.tkn_no + "&sup=" + data.sup + "&tkn=" + $("#inv_no").val();
                                window.location.href = url;
                            }
                        });
                    } else if (data.status == 409) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Duplicate Entry',
                            text: 'Invoice already exists!',
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Something went wrong!',
                        });
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    submitBtn.prop('disabled', false).text(originalText);
                    
                    var errorMessage = 'An error occurred!';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage,
                    });
                }
            });
        }));
        
        // Load recent GRNs on page load
        $(document).ready(function() {
            loadRecentGRNs();
        });
        
        function loadRecentGRNs() {
            $.ajax({
                url: "<?php echo base_url() ?>inventory/manual_grn/getRecentGRNs",
                method: "GET",
                dataType: "json",
                success: function(data) {
                    if (data && data.length > 0) {
                        $('#pending_grns').show();
                        $('#grn_list').empty();
                        
                        $.each(data, function(index, grn) {
                            var statusBadge = grn.status == 'pending' ? 
                                '<span class="badge bg-warning">Pending</span>' : 
                                '<span class="badge bg-success">Completed</span>';
                                
                            var actionBtn = grn.status == 'pending' ? 
                                '<button class="btn btn-sm btn-primary continue-grn" data-inv="' + grn.inv_no + '" data-sup="' + grn.sup_id + '" data-tkn="' + grn.order_no + '">Continue</button>' :
                                '<button class="btn btn-sm btn-info view-grn" data-inv="' + grn.inv_no + '">View</button>';
                                
                            $('#grn_list').append(
                                '<tr>' +
                                '<td>' + (index + 1) + '</td>' +
                                '<td>' + grn.supplier_name + '</td>' +
                                '<td>' + grn.order_no + '</td>' +
                                '<td>' + grn.date + '</td>' +
                                '<td>' + statusBadge + '</td>' +
                                '<td>' + actionBtn + '</td>' +
                                '</tr>'
                            );
                        });
                    }
                }
            });
        }
        
        // Handle continue GRN button
        $(document).on('click', '.continue-grn', function() {
            var inv = $(this).data('inv');
            var sup = $(this).data('sup');
            var tkn = $(this).data('tkn');
            
            var url = "<?php echo base_url() ?>inventory/manual_grn?inv=" + inv + "&sup=" + sup + "&tkn=" + tkn;
            window.location.href = url;
        });
    </script>
</body>

</html>