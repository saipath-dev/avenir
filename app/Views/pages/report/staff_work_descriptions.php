<?= view('layouts/head') ?>

<body>
    <div id="layout-wrapper">
        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8 d-flex align-items-center">
                            <h4 class="p-title mb-0">Staff Work Descriptions</h4>
                        </div>
                        <div class="col-md-4 text-md-end mt-2 mt-md-0">
                            <a class="btn btn-light btn-sm" href="<?= esc($export_url) ?>">Export CSV</a>
                        </div>
                    </div>
                    <p class="text-muted small mt-2 mb-3">Restricted to the System Administrator unless access is opened on the Staff Access Control Panel. A job title, group, work description or computer account shown here grants no access by itself.</p>
                    <div class="card p-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="thead-dark">
                                    <tr><th style="width:7%">Staff no</th><th>Employee name</th><th>Job title</th><th>Group</th><th>Work description</th><th>Computer account</th></tr>
                                </thead>
                                <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="6" class="text-muted">No active staff.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><code><strong><?= esc($r['staff_no']) ?></strong></code></td>
                                        <td><?= esc($r['name']) ?></td>
                                        <td><?= esc($r['title'] ?: '—') ?></td>
                                        <td><?= esc($r['group'] ?: '—') ?></td>
                                        <td><?= esc($r['duties'] ?: '—') ?></td>
                                        <td><?= $r['account'] ? esc($r['account']) . ' <span class="badge ' . ((int) $r['account_enabled'] === 1 ? 'bg-success' : 'bg-secondary') . '">' . ((int) $r['account_enabled'] === 1 ? 'enabled' : 'disabled') . '</span>' : '<span class="text-muted">none</span>' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?= view('layouts/inpage_footer') ?>
        </div>
    </div>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>
</body>
</html>
