<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="img-1" data-preloader="disable">

<head>

    <meta charset="utf-8" />
    <title>:: Avenir ::</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= view('layouts/tf_csrf') ?><?php /* tf-e8-access-r1: security token for every change; reason prompt */ ?>
    <!-- App favicon -->
    <link rel="shortcut icon" href="<?= base_url() ?>assets/images/favicon.png">

    <!-- jsvectormap css -->
    <link href="<?= base_url() ?>assets/libs/jsvectormap/css/jsvectormap.min.css" rel="stylesheet" type="text/css" />

    <!--Swiper slider css-->
    <link href="<?= base_url() ?>assets/libs/swiper/swiper-bundle.min.css" rel="stylesheet" type="text/css" />
    <!-- Sweet Alert css-->
    <link href="<?= base_url() ?>assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <!-- dataTable css -->
    <link rel="stylesheet" href="<?= base_url(); ?>assets/libs/jquery-datatable/dataTables.bootstrap4.min.css">
    <!-- Select2 css -->
    <!-- <link rel="stylesheet" href="<?= base_url(); ?>assets/select2/select2.min.css"> -->
    <link href="<?= base_url(); ?>assets/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/vendor/bootstrap-datepicker/css/bootstrap-datepicker3.min.css">
    <!-- Tree View -->
    <link rel="stylesheet" href="<?= base_url(); ?>assets/vendor/bootstrap-treeview/bootstrap-treeview.min.css">
    <link rel="stylesheet" href="<?= base_url(); ?>assets/vendor/jstree/themes/default/style.min.css">

    <!-- Layout config Js -->
    <script src="<?= base_url() ?>assets/js/layout.js"></script>
    <!-- Bootstrap Css -->
    <link href="<?= base_url() ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/bootstrap-icons.css" rel="stylesheet" type="text/css">
    <!-- Icons Css -->
    <link href="<?= base_url() ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="<?= base_url() ?>assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="<?= base_url() ?>assets/css/custom.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/jquery-ui.css">

    <link rel="stylesheet" href="<?= base_url() ?>assets/calender/zabuto_calendar.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>assets/fullcalendar/dist/fullcalendar.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/vendor/toastr/toastr.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/vendor/summernote/dist/summernote-lite.min.css">
    <!-- <link rel="stylesheet" href="<?//= base_url() ?>assets/fullcalendar/dist/fullcalendar.print.min.css"> -->
    

    <style>
        .debug {
            border: 1px solid red;
        }

        .select2-container--open .select2-dropdown--below{
            border: 1px solid gainsboro;
            box-shadow: 0 0 10px rgba(0,0,0,.1);
        }

         .select2-container--default .select2-selection--single .select2-selection__clear {
            margin-right: 20px !important;
        }

        .datepicker{
            padding: 10px;
            border: 1px solid lightgray;
            z-index: 9999;
        }
    </style>



    <?php /* tf-uniform-r1: the Avenir design uniform, loaded last; remove this one line to switch it off */ ?><link href="<?= base_url('assets/css/avenir-uniform.css') ?>?v=<?= @filemtime(FCPATH . 'assets/css/avenir-uniform.css') ?: 1 ?>" rel="stylesheet" type="text/css" />
</head>