    <!-- JAVASCRIPT -->
    <script src="<?= base_url() ?>assets/js/jquery.min.js"></script>

    <script src="<?= base_url() ?>assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Bootstrap 5 jQuery modal compatibility shim -->
    <script>
    (function ($) {
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
        $.fn.modal = function (action) {
            return this.each(function () {
                if (action === 'show') {
                    var m = bootstrap.Modal.getInstance(this) || new bootstrap.Modal(this);
                    m.show();
                } else if (action === 'hide') {
                    var m = bootstrap.Modal.getInstance(this);
                    if (m) m.hide();
                } else if (action === 'toggle') {
                    var m = bootstrap.Modal.getInstance(this) || new bootstrap.Modal(this);
                    m.toggle();
                }
            });
        };
    }(jQuery));
    </script>

    <script src="<?= base_url() ?>assets/libs/simplebar/simplebar.min.js"></script>
    <script src="<?= base_url() ?>assets/libs/node-waves/waves.min.js"></script>
    <script src="<?= base_url() ?>assets/libs/feather-icons/feather.min.js"></script>
    <script src="<?= base_url() ?>assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="<?= base_url() ?>assets/js/plugins.js"></script>
    <script src="<?php echo base_url(); ?>assets/vendor/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script><!-- bootstrap datepicker Plugin Js -->

    <!-- apexcharts -->
    <script src="<?= base_url() ?>assets/libs/apexcharts/apexcharts.min.js"></script>

    <!-- Vector map-->
    <script src="<?= base_url() ?>assets/libs/jsvectormap/js/jsvectormap.min.js"></script>
    <script src="<?= base_url() ?>assets/libs/jsvectormap/maps/world-merc.js"></script>

    <!--Swiper slider js-->
    <script src="<?= base_url() ?>assets/libs/swiper/swiper-bundle.min.js"></script>

    <!-- Dashboard init -->
    <script src="<?= base_url() ?>assets/js/pages/dashboard-ecommerce.init.js"></script>

    <!-- Sweet Alerts js -->
    <script src="<?= base_url() ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>

    <!-- dataTable js -->
    <script src="<?= base_url() ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="<?= base_url() ?>assets/libs/tables/jquery-datatable.js"></script>

    <!-- Select 2 js -->
    <!-- <script src="<?//= base_url() ?>assets/select2/select2.full.min.js"></script> -->
    <script src="<?= base_url() ?>assets/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <!-- aos js -->
    <script src="<?= base_url() ?>assets/libs/aos/aos.js"></script>
    <!-- prismjs plugin -->
    <script src="<?= base_url() ?>assets/libs/prismjs/prism.js"></script>
    <!-- animation init -->
    <script src="<?= base_url() ?>assets/js/pages/animation-aos.init.js"></script>

    <!-- echarts js -->
    <script src="<?= base_url() ?>assets/libs/echarts/echarts.min.js"></script>

    <!-- echarts init -->
    <script src="<?= base_url() ?>assets/js/pages/echarts.init.js"></script>
      <!-- TYPEHEAD -->
    <script src="<?= base_url() ?>assets/js/pages/bootstrap3-typeahead.min.js"></script>
     <script src="<?= base_url() ?>assets/js/pages/handlebars.js"></script>
      <script src="<?= base_url() ?>assets/js/pages/typeahead.bundle.js"></script>

    <!-- App js -->
    <script src="<?= base_url() ?>assets/js/app.js"></script>


    <script src="<?= base_url() ?>assets/libs/flatpickr/flatpickr.min.js"></script>

    <script src="<?= base_url() ?>assets/moment/min/moment.min.js"></script>

    <script src="<?= base_url() ?>assets/fullcalendar/dist/fullcalendar.js"></script>
    <script src="<?= base_url() ?>assets/calender/zabuto_calendar.min.js"></script>
    <script src="<?= base_url() ?>assets/vendor/toastr/toastr.js"></script>

    <!-- jQuery UI commented out - using custom autocomplete to avoid timeout -->
    <!-- <script src="https://code.jquery.com/ui/1.14.0/jquery-ui.min.js"></script> -->

    <script src="<?= base_url() ?>assets/vendor/summernote/dist/summernote-lite.min.js"></script>

    <script>
      // $.fn.select2.defaults.set('selectionCssClass', ':all:');

    </script>