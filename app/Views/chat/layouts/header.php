<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>:: Avenir Chat ::</title>
    <link rel="shortcut icon" href="<?= base_url() ?>assets/images/favicon.png">
    <link href="<?= base_url() ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/bootstrap-icons.css" rel="stylesheet" type="text/css">
    <link href="<?= base_url() ?>assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/select2/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <link rel="stylesheet" href="<?= base_url() ?>assets/vendor/toastr/toastr.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/vendor/bootstrap-datepicker/css/bootstrap-datepicker3.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/vendor/summernote/dist/summernote-lite.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,100..900;1,100..900&display=swap');

        :root {
            --primary-color: #580ab8;
            --primary-hover-color: #6a0ddb;
            --secondary-color: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #333;
            /* background-image: linear-gradient(180deg, rgba(245, 245, 255, 0.8) 0%, rgba(250, 250, 255, 0.8) 100%); */
            background-color: #fff;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Inter', sans-serif;
        }

        .form-control {
            border: 1px solid #e8e8e2;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #f4ecf7 !important;
            color: #3c3c3c;
        }

        .conversation-card {
            background-color: #fff;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #f2f2f4;
        }

        .conversation-card:hover {
            background-color: #f2f2f4;
            border: 1px solid #e8e8f2;
        }

        #conversation_ul {
            overflow-y: auto;
            height: calc(100vh - 480px);
        }

        .chat {
            width: 70%;
            background-color: white;
        }

        /* Quotation Format Styling */
        .quotation-message {
            background-color: #f8f9fa;
            border-left: 4px solid #78b848;
            padding: 15px;
            margin: 10px 0;
            border-radius: 0 8px 8px 0;
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
        }

        .quotation-message .quotation-section {
            margin-bottom: 15px;
        }

        .quotation-message .quotation-section:last-child {
            margin-bottom: 0;
        }

        .quotation-message .section-title {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 8px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 4px;
            display: block;
        }

        .quotation-message .section-content {
            margin-left: 0;
            color: #495057;
            font-size: 13px;
        }

        .quotation-message .section-content ul {
            margin: 0;
            padding-left: 20px;
        }

        .quotation-message .section-content li {
            margin-bottom: 4px;
            list-style-type: disc;
        }

        .quotation-message .contact-info {
            background-color: #e3f2fd;
            padding: 10px;
            border-radius: 6px;
            border-left: 3px solid #2196f3;
        }

        .quotation-message .additional-notes {
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 6px;
            border-left: 3px solid #ffc107;
            font-style: italic;
        }

        .quotation-message .pax-highlight {
            background-color: #d4edda;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 500;
            color: #155724;
        }

        /* Regular chat message styling */
        .chat-message {
            margin: 10px 0;
            padding: 10px 15px;
            border-radius: 8px;
            background-color: #ffffff;
            border: 1px solid #e9ecef;
            word-wrap: break-word;
            white-space: pre-wrap;
        }

        .system-message {
            background-color: #f1f3f4;
            border-left: 4px solid #6c757d;
        }

        .user-message {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
    </style>
</head>

<body>