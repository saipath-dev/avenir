    <?php //= view('layouts/header'); ?>

    <style>
        .action-buttons {
            margin-top: 20px;
        }

        .action-buttons .btn {
            margin-right: 10px;
        }

    </style>

    <body class="theme-cyan">

        <!-- Page Loader -->
        <div class="page-loader-wrapper">
            <div class="loader">
                <div class="m-t-30 mb-3">
                    <h3 style="color: white;"><strong>Lion Royal Car</h3>
                </div>
                <p>Just a moment...</p>
            </div>
        </div>
        <!-- Overlay For Sidebars -->

        <div id="wrapper">

            <?php //= view('layouts/nav_bar'); ?>
            <?php //= view('layouts/sidebar'); ?>

            <div id="main-content">
                <div class="container-fluid">

                    <div class="row clearfix">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="mobile-left">
                                    <a class="btn btn-primary toggle-email-nav collapsed" data-toggle="collapse" href="#email-nav" role="button" aria-expanded="false" aria-controls="email-nav">
                                    <span class="btn-label">
                                        <i class="la la-bars"></i>
                                    </span>
                                        Menu
                                    </a>
                                </div>
                                <div class="mail-inbox">
                                    <div class="mail-left collapse" id="email-nav">
                                        <div class="mail-compose m-b-20">
                                            <a href="<?= base_url('mail/compose'); ?>" class="btn btn-danger btn-block">Compose</a>
                                        </div>
                                        <div class="mail-side">
                                            <ul class="nav">
                                                <li class="active"><a href="javascript:void(0);"><i class="icon-envelope"></i>Inbox<span class="badge badge-primary float-right">6</span></a></li>
                                                <li><a href="<?= base_url('mail/sent'); ?>"><i class="icon-cursor"></i>Sent</a></li>
                                                <li><a href="javascript:void(0);"><i class="icon-envelope-open"></i>Draft<span class="badge badge-info float-right">3</span></a></li>
                                                <li><a href="javascript:void(0);"><i class="icon-action-redo"></i>Outbox</a></li>
                                                <li><a href="javascript:void(0);"><i class="icon-star"></i>Starred<span class="badge badge-warning float-right">6</span></a></li>
                                                <li><a href="javascript:void(0);"><i class="icon-trash"></i>Trash<span class="badge badge-danger float-right">9</span></a></li>
                                            </ul>
                                            <h3 class="label">Labels <a href="#" class="float-right m-r-10" title="Add New Labels"><i class="icon-plus"></i></a></h3>
                                            <ul class="nav">
                                                <li class="active">
                                                    <a href="javascript:void(0);"><i class="fa fa-circle text-danger"></i>Web Design<span class="badge badge-primary float-right">5</span></a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);"><i class="fa fa-circle text-info"></i>Recharge</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);"><i class="fa fa-circle text-dark"></i>Paypal</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);"><i class="fa fa-circle text-primary"></i>Family</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="mail-right">
                                        <div class="header d-flex align-center">
                                            <h2>Inbox</h2>
                                            <form class="ml-auto">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" placeholder="Search Mail" aria-label="Search Mail" aria-describedby="search-mail">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text" id="search-mail"><i class="icon-magnifier"></i></span>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="mail-action clearfix">
                                            <div class="pull-left">
                                                <div class="fancy-checkbox d-inline-block">
                                                    <label>
                                                        <input class="select-all" type="checkbox" name="checkbox">
                                                        <span></span>
                                                    </label>
                                                </div>
                                                <div class="btn-group">
                                                    <a href="javascript:void(0);" class="btn btn-outline-secondary btn-sm hidden-sm">Refresh</a>
                                                    <a href="javascript:void(0);" class="btn btn-outline-secondary btn-sm hidden-sm">Archive</a>
                                                    <a href="javascript:void(0);" class="btn btn-outline-secondary btn-sm">Trash</a>
                                                </div>
                                                <div class="btn-group">
                                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Tags</button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="javascript:void(0);">Tag 1</a>
                                                        <a class="dropdown-item" href="javascript:void(0);">Tag 2</a>
                                                        <a class="dropdown-item" href="javascript:void(0);">Tag 3</a>
                                                    </div>
                                                </div>
                                                <div class="btn-group">
                                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">More</button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="javascript:void(0);">Mark as read</a>
                                                        <a class="dropdown-item" href="javascript:void(0);">Mark as unread</a>
                                                        <a class="dropdown-item" href="javascript:void(0);">Spam</a>
                                                        <div role="separator" class="dropdown-divider"></div>
                                                        <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="pull-right ml-auto">
                                                <div class="pagination-email d-flex">
                                                    <p>1-50/295</p>
                                                    <div class="btn-group m-l-20">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm"><i class="fa fa-angle-left"></i></button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm"><i class="fa fa-angle-right"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- ... -->

                                        <div class="mail-list">
                                            <ul class="list-unstyled">
                                                <?php foreach ($emails as $email): ?>
                                                    <li class="clearfix">
                                                        <div class="mail-detail-left">
                                                            <label class="fancy-checkbox">
                                                                <input type="checkbox" name="checkbox" class="checkbox-tick">
                                                                <span></span>
                                                            </label>
                                                            <a href="javascript:void(0);" class="mail-star">
                                                                <?php if ($email['seen']): ?>
                                                                    <i class="fa fa-star"></i>
                                                                <?php else: ?>
                                                                    <i class="fa fa-star-o"></i>
                                                                <?php endif; ?>
                                                            </a>
                                                        </div>
                                                        <div class="mail-detail-right">
                                                            <h6 class="sub">
                                                                <a href="javascript:void(0);" class="mail-detail-expand"
                                                                   data-email-uid="<?= $email['uid']; ?>">
                                                                    <?= htmlspecialchars($email['from'], ENT_QUOTES, 'UTF-8'); ?>
                                                                </a>
                                                            </h6>
                                                            <p class="dep">
                                                                <?= htmlspecialchars($email['subject'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </p>
                                                            <span class="time"><?= htmlspecialchars($email['date'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                        </div>
                                                        <div class="hover-action">
                                                            <button type="button" data-type="confirm" class="btn btn-danger js-sweetalert" title="Delete"><i class="fa fa-trash-o"></i></button>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>

                                            </ul>
                                        </div>

                                        <!-- ... -->

                                        <!-- ... -->

                                        <div class="mail-detail-full" id="mail-detail-open" style="display: none;">
                                            <div class="mail-action clearfix">
                                                <div class="pull-left">
                                                    <div class="fancy-checkbox d-inline-block">
                                                        <label>
                                                            <input class="select-all" type="checkbox" name="checkbox">
                                                            <span></span>
                                                        </label>
                                                    </div>
                                                    <div class="btn-group">
                                                        <a href="javascript:void(0);" class="btn btn-outline-secondary btn-sm hidden-sm">Refresh</a>
                                                        <a href="javascript:void(0);" class="btn btn-outline-secondary btn-sm hidden-sm">Archive</a>
                                                        <a href="javascript:void(0);" class="btn btn-outline-secondary btn-sm">Trash</a>
                                                    </div>
                                                    <div class="btn-group">
                                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Tags</button>
                                                        <div class="dropdown-menu">
                                                            <a class="dropdown-item" href="javascript:void(0);">Tag 1</a>
                                                            <a class="dropdown-item" href="javascript:void(0);">Tag 2</a>
                                                            <a class="dropdown-item" href="javascript:void(0);">Tag 3</a>
                                                        </div>
                                                    </div>
                                                    <div class="btn-group">
                                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">More</button>
                                                        <div class="dropdown-menu">
                                                            <a class="dropdown-item" href="javascript:void(0);">Mark as read</a>
                                                            <a class="dropdown-item" href="javascript:void(0);">Mark as unread</a>
                                                            <a class="dropdown-item" href="javascript:void(0);">Spam</a>
                                                            <div role="separator" class="dropdown-divider"></div>
                                                            <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="pull-right ml-auto">
                                                    <a href="javascript:void(0);" class="mail-back btn btn-outline-secondary btn-sm"><i class="fa fa-close"></i></a>
                                                </div>
                                            </div>
                                            <div class="detail-header">
                                                <div class="media">
                                                    <div class="float-left">
                                                        <div class="m-r-20"><img src="../assets/images/sm/avatar1.jpg" alt=""></div>
                                                    </div>
                                                    <div class="media-body">
                                                        <p class="mb-0"><strong class="text-muted m-r-5">From:</strong><a class="text-default" href="javascript:void(0);">info@thememakker.com</a> <span class="text-muted text-sm float-right">12:48, 23.06.2018</span></p>
                                                        <p class="mb-0"><strong class="text-muted m-r-5">To:</strong>Me <small class="text-muted float-right"><i class="zmdi zmdi-attachment m-r-5"></i>(2 files, 89.2 KB)</small></p>
                                                        <p class="mb-0"><strong class="text-muted m-r-5">CC:</strong><a class="text-default" href="javascript:void(0);">mail@thememakker.com</a></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mail-cnt">
                                                <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.</p>
                                                <p>printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrnturies, but also the leap into electronic typesetting, remaining essentially unchanged.</p>
                                                <hr>
                                                <strong>Click here to</strong>
                                                <a href="app-compose.html">Reply</a> or
                                                <a href="app-compose.html">Forward</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>


        <!-- Javascript -->
        <script src="<?php echo base_url(); ?>assets/vendor/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
        <script src="<?php echo base_url(); ?>assets/bundles/libscripts.bundle.js"></script>
        <script src="<?php echo base_url(); ?>assets/bundles/vendorscripts.bundle.js"></script>
        <script src="<?php echo base_url(); ?>assets/bundles/mainscripts.bundle.js"></script>
        <script src="<?php echo base_url(); ?>assets/bundles/morrisscripts.bundle.js"></script>

        <script src="<?php echo base_url(); ?>assets/bundles/knob.bundle.js"></script>
        <script src="<?php echo base_url(); ?>assets/vendor/toastr/toastr.js"></script>
        <script src="<?php echo base_url(); ?>assets/bundles/jvectormap.bundle.js"></script>
        <script src="assets/js/pages/ui/dialogs.js"></script>

        <!-- Include DOMPurify for sanitizing HTML -->
        <!-- Include DOMPurify for sanitizing HTML -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/2.3.4/purify.min.js"></script>

        <script>
            $(document).ready(function(){
                $(".mail-detail-expand").click(function(){
                    var emailUid = $(this).data('email-uid');

                    // Show a loader if desired

                    $.ajax({
                        url: '<?= base_url("mail/inbox/getEmailBody"); ?>/' + emailUid,
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            // Sanitize the email body
                            var emailBody = DOMPurify.sanitize(response.body);

                            // Populate the email details
                            $("#mail-detail-open .detail-header .media-body").html(
                                '<p class="mb-0"><strong class="text-muted m-r-5">From:</strong>' +
                                '<a class="text-default" href="javascript:void(0);">' + response.from + '</a>' +
                                '<span class="text-muted text-sm float-right">' + response.date + '</span></p>' +
                                '<p class="mb-0"><strong class="text-muted m-r-5">To:</strong>Me</p>' +
                                '<p class="mb-0"><strong class="text-muted m-r-5">Subject:</strong>' + response.subject + '</p>'
                            );
                            $("#mail-detail-open .mail-cnt").html(emailBody);

                            // Handle attachments
                            if (response.attachments && response.attachments.length > 0) {
                                var attachmentsHtml = '<h5>Attachments:</h5><ul>';
                                response.attachments.forEach(function(attachment) {
                                    var downloadUrl = '<?= base_url("mail/inbox/downloadAttachment"); ?>/' + emailUid + '/' + encodeURIComponent(attachment.part_number);
                                    attachmentsHtml += '<li><a href="' + downloadUrl + '">' + attachment.filename + '</a></li>';
                                });
                                attachmentsHtml += '</ul>';
                                $("#mail-detail-open .mail-cnt").append(attachmentsHtml);
                            }

                            $("#mail-detail-open .mail-cnt").append(
                                '<hr>' +
                                '<div class="action-buttons">' +
                                '<a href="<?= base_url('mail/inbox/reply/'); ?>' + emailUid + '" class="btn btn-primary reply-button">Reply</a>' +
                                '<a href="<?= base_url('mail/inbox/forward/'); ?>' + emailUid + '" class="btn btn-secondary forward-button">Forward</a>' +
                                '</div>'
                            );

                            $("#mail-detail-open").show();
                        },
                        error: function(xhr, status, error) {
                            // Handle error
                            alert('Failed to load email body.');
                        }
                    });
                });

                $(".mail-back").click(function(){
                    $("#mail-detail-open").hide();
                });
            });
        </script>

    </body>

    </html>