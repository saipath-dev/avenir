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
                    <div class="profile-foreground position-relative mx-n4 mt-n4">
                        <!-- <div class="profile-wid-bg" style="height:380px">
                            <img src="assets/images/profile-bg.jpg" alt="" class="profile-wid-img" />
                        </div> -->
                    </div>
                    <div class="pt-4 mb-4 mb-lg-3 pb-lg-4 profile-wrapper pt-5">
                        <div class="row row-cols-xxl-5 row-cols-lg-3 row-cols-md-2 row-cols-1">
                            <div class="col">
                                <div class="card">
                                    <div class="card-body text-center py-4">
                                        <!-- <lord-icon src="https://cdn.lordicon.com/xulniijg.json" trigger="hover" colors="primary:#405189" target="div" style="width:50px;height:50px"></lord-icon> -->
                                        <i class="ri-settings-line" style="width:50px;height:50px"></i>
                                        <a href="<?= base_url() . 'tour/tour_arrival'; ?>" class="stretched-link" >
                                            <h5 class="mt-4">Arrival Notification</h5>
                                        </a>
                                        <!-- <p class="text-muted mb-0">102 Position</p> -->
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card">
                                    <div class="card-body text-center py-4">
                                        <!-- <lord-icon src="https://cdn.lordicon.com/oclwxpmm.json" trigger="hover" colors="primary:#405189" target="div" style="width:50px;height:50px"></lord-icon> -->
                                        <i class="ri-settings-line" style="width:50px;height:50px"></i>
                                        <a href="<?= base_url() . 'master_files/supplier/all_drivers'; ?>" class="stretched-link">
                                            <h5 class="mt-4">Driver and Guide</h5>
                                        </a>
                                        <!-- <p class="text-muted mb-0">64 Position</p> -->
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card">
                                    <div class="card-body text-center py-4">
                                        <!-- <lord-icon src="https://cdn.lordicon.com/dklbhvrt.json" trigger="hover" colors="primary:#405189" target="div" style="width:50px;height:50px"></lord-icon> -->
                                        <i class="ri-settings-line" style="width:50px;height:50px"></i>
                                        <a href="<?= base_url() . 'tour/tour_details_search'; ?>" class="stretched-link">
                                            <h5 class="mt-4">Tour search</h5>
                                        </a>
                                        <!-- <p class="text-muted mb-0">35 Position</p> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end row-->

                </div><!-- container-fluid -->
            </div><!-- End Page-content -->
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

    </script>
</body>

</html>