<?= view('layouts/header'); ?>

<body class="theme-cyan">
<?= view('layouts/nav_bar'); ?>
<?= view('layouts/sidebar'); ?>

<div id="main-content">
    <div class="container-fluid">
        <!-- Compose form -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="body">
                        <?php if(session()->getFlashdata('error')): ?>
                            <div class="alert alert-danger"><?= session()->getFlashdata('error'); ?></div>
                        <?php endif; ?>
                        <?php if(session()->getFlashdata('success')): ?>
                            <div class="alert alert-success"><?= session()->getFlashdata('success'); ?></div>
                        <?php endif; ?>

                        <form method="post" action="<?= base_url('mail/compose/send'); ?>" enctype="multipart/form-data">
                            <?= csrf_field(); ?>

                            <div class="form-group">
                                <input type="email" class="form-control" name="to"
                                       placeholder="To"
                                       value="<?= isset($to) ? htmlspecialchars($to, ENT_QUOTES, 'UTF-8') : ''; ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <input type="text" class="form-control" name="subject"
                                       placeholder="Subject"
                                       value="<?= isset($subject) ? htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') : ''; ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                    <textarea class="form-control summernote" name="body"
                                              placeholder="Write your message here..." style="min-height: 500px"><?= isset($body) ? htmlspecialchars($body, ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <input type="file" class="form-control" name="attachments[]" multiple>
                            </div>

                            <div class="m-t-30">
                                <button type="submit" class="btn btn-success">Send Message</button>
                                <button type="button" class="btn btn-secondary">Draft</button>
                                <a href="<?= base_url('mail/inbox'); ?>" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Javascript -->
<script src="<?= base_url(); ?>assets/bundles/libscripts.bundle.js"></script>
<script src="<?= base_url(); ?>assets/bundles/vendorscripts.bundle.js"></script>
<script src="<?= base_url(); ?>assets/bundles/mainscripts.bundle.js"></script>
<script src="<?= base_url(); ?>assets/vendor/summernote/dist/summernote.js"></script>

<script>
    // $(document).ready(function() {
    //     $('.summernote').summernote();
    // });
</script>

</body>
</html>
