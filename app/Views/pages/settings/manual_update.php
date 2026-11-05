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
                                        <h4 class="p-title">Updates</h4>
                                    </div>
                                </div>

                                <!-- <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary" id="manual_update">
                                            Manual Update
                                        </button>
                                    </div>
                                </div> -->
                                <!-- <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary" id="des_update">
                                            Transport Description Update
                                        </button>
                                    </div>
                                </div> -->

                                <!-- <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary" id="des_other_update">
                                            Other Expenses Description Update
                                        </button>
                                    </div>
                                </div> -->

                                <!-- <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary" id="hotel_voucher_accept">
                                            Voucher Accept
                                        </button>
                                    </div>
                                </div> -->

                                <!-- <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary" id="entrance_update">
                                            Entrance Voucher Record Update
                                        </button>
                                    </div>
                                </div> -->

                                <!-- <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary" id="other_update">
                                            Other Voucher Record Update
                                        </button>
                                    </div>
                                </div> -->

                                <!-- <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary" id="transport_update">
                                            Transport Voucher Record Update
                                        </button>
                                    </div>
                                </div> -->

                                <!-- <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary" id="voucher_date_update">
                                            Voucher Date Update
                                        </button>
                                    </div>
                                </div> -->

                                <!-- <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary" id="verify_tour">
                                            Verify Tours
                                        </button>
                                    </div>
                                </div> -->

                                <!-- <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary" id="receipt_generate">
                                            Debtor Receipt Gen
                                        </button>
                                    </div>
                                </div> -->
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <!-- tf-legacy-retire-r1 (12 Oct 2026): the "Bank Not Reconciliation Record Insert" button is retired and replaced by this notice -->
                                        <div class="alert alert-secondary mb-0" id="bank_recon_record_insert_retired" role="note" data-csrf-name="<?= esc(csrf_token()) ?>" data-csrf-hash="<?= esc(csrf_hash()) ?>">
                                            <strong>Retired:</strong> Bank Not Reconciliation Record Insert. This one-off was completed on 21 October 2025 (entries 15947–16232) and can no longer be run.
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
        $(document).ready(function() {
            // $('#manual_update').click(function() {
            //     $.ajax({
            //         url: '<?= base_url() ?>settings/manual_update/update',
            //         type: 'POST',
            //         success: function(response) {
            //             if (response.status == 200) {
            //                 alert(response.message);
            //             } else {
            //                 alert(response.message);
            //             }
            //         }
            //     });
            // });
            $('#des_update').click(function() {
                $.ajax({
                    url: '<?= base_url() ?>settings/manual_update/des_update',
                    type: 'POST',
                    success: function(response) {
                        if (response.status == 200) {
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });
            $('#des_other_update').click(function() {
                $.ajax({
                    url: '<?= base_url() ?>settings/manual_update/des_other_update',
                    type: 'POST',
                    success: function(response) {
                        if (response.status == 200) {
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });
            $('#hotel_voucher_accept').click(function() {
                $.ajax({
                    url: '<?= base_url() ?>settings/manual_update/hotel_voucher_accept',
                    type: 'POST',
                    success: function(response) {
                        if (response.status == 200) {
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });
            $('#entrance_update').click(function() {
                $.ajax({
                    url: '<?= base_url() ?>settings/manual_update/entrance_update',
                    type: 'POST',
                    success: function(response) {
                        if (response.status == 200) {
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });
            $('#other_update').click(function() {
                $.ajax({
                    url: '<?= base_url() ?>settings/manual_update/other_update',
                    type: 'POST',
                    success: function(response) {
                        if (response.status == 200) {
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });
            $('#transport_update').click(function() {
                $.ajax({
                    url: '<?= base_url() ?>settings/manual_update/transport_update',
                    type: 'POST',
                    success: function(response) {
                        if (response.status == 200) {
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });
            $('#voucher_date_update').click(function() {
                $.ajax({
                    url: '<?= base_url() ?>settings/manual_update/voucher_date_update',
                    type: 'POST',
                    success: function(response) {
                        if (response.status == 200) {
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });
            $('#verify_tour').click(function() {
                $.ajax({
                    url: '<?= base_url() ?>settings/manual_update/verify_tour',
                    type: 'POST',
                    success: function(response) {
                        if (response.status == 200) {
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });
            $('#receipt_generate').click(function() {
                $.ajax({
                    url: '<?= base_url() ?>settings/manual_update/debtor_receipt_generate',
                    type: 'POST',
                    success: function(response) {
                        if (response.status == 200) {
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });
            $('#bank_recon_record_insert').click(function() {
                $.ajax({
                    url: '<?= base_url() ?>settings/manual_update/bank_recon_record_insert',
                    type: 'POST',
                    success: function(response) {
                        if (response.status == 200) {
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });
        });
    </script>
</body>




</html>