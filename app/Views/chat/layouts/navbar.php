<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid py-1 px-3">
        <a class="navbar-brand fw-bolder fs-6" href="<?= base_url() ?>">
            <i class="fas fa-comments me-2"></i> Chat
        </a>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            </ul>
            <?php if (session()->get('group_id') != 14) { ?>
                <a href="<?= base_url('dashboard') ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            <?php } else { ?>
                <button class="btn btn-dark logout">Logout</button>
            <?php } ?>
        </div>
    </div>
</nav>