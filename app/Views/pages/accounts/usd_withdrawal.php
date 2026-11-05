<?= view('layouts/head') ?>

<body>

    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col">
                            <div class="h-100">

                                <div class="row">
                                    <div class="col-md-10 d-flex">
                                        <h4 class="p-title">USD Withdrawal</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="body p-4">
                                                <form id="usd_withdrawal_form" accept-charset="utf-8">

                                                    <div class="mb-3">
                                                        <label class="form-label">Date <font color="#FF0000"><strong>*</strong></font></label>
                                                        <input type="text" class="form-control" id="date" name="date"
                                                            data-provide="datepicker"
                                                            data-date-autoclose="true"
                                                            data-date-format="yyyy/mm/dd"
                                                            value="<?= date('Y/m/d') ?>" required />
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">USD Account (From) <font color="#FF0000"><strong>*</strong></font></label>
                                                        <select id="usd_account" name="usd_account" class="js-example-basic-single form-control" style="width:100%;" required>
                                                            <option value="">Select USD Account...</option>
                                                            <?php foreach ($usd_accounts as $acc): ?>
                                                                <option value="<?= $acc['L_Code'] ?>"><?= $acc['PName'] ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">LKR Account (To) <font color="#FF0000"><strong>*</strong></font></label>
                                                        <select id="lkr_account" name="lkr_account" class="js-example-basic-single form-control" style="width:100%;" required>
                                                            <option value="">Select LKR Account...</option>
                                                            <?php foreach ($lkr_accounts as $acc): ?>
                                                                <option value="<?= $acc['L_Code'] ?>"><?= $acc['PName'] ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">USD Amount <font color="#FF0000"><strong>*</strong></font></label>
                                                        <input type="number" step="0.01" min="0" class="form-control" id="usd_amount" name="usd_amount" placeholder="0.00" required />
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Exchange Rate <font color="#FF0000"><strong>*</strong></font></label>
                                                        <input type="number" step="0.01" min="0" class="form-control" id="exchange_rate" name="exchange_rate" placeholder="0.00" required />
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">LKR Amount</label>
                                                        <input type="text" class="form-control" id="lkr_amount" readonly style="background:#e9ecef;" placeholder="0.00" />
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Reference </label>
                                                        <input type="text" class="form-control" id="description" name="description" placeholder="Optional reference" />
                                                    </div>

                                                    <button type="submit" class="btn btn-success">Process Withdrawal</button>
                                                    <button type="reset" class="btn btn-default ms-2">Clear</button>

                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?= view('layouts/brand') ?>
        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: 'Select',
            allowClear: true
        });

        // Auto-calculate LKR amount
        $(document).on('keyup change', '#usd_amount, #exchange_rate', function () {
            var usd = parseFloat($('#usd_amount').val()) || 0;
            var rate = parseFloat($('#exchange_rate').val()) || 0;
            if (usd > 0 && rate > 0) {
                $('#lkr_amount').val((usd * rate).toFixed(2));
            } else {
                $('#lkr_amount').val('');
            }
        });

        $('#usd_withdrawal_form').submit(function (e) {
            e.preventDefault();

            var usd_account   = $('#usd_account').val();
            var lkr_account   = $('#lkr_account').val();
            var usd_amount    = $('#usd_amount').val();
            var exchange_rate = $('#exchange_rate').val();
            var date          = $('#date').val();

            if (!usd_account || !lkr_account || !usd_amount || !exchange_rate || !date) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please fill all required fields' });
                return;
            }

            $.ajax({
                url: '<?= base_url('accounts/usd_withdrawal/process') ?>',
                type: 'POST',
                data: {
                    usd_account:   usd_account,
                    lkr_account:   lkr_account,
                    usd_amount:    usd_amount,
                    exchange_rate: exchange_rate,
                    date:          date,
                    description:   $('#description').val()
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status === 200) {
                        Swal.fire({ icon: 'success', title: 'Success', text: res.message })
                            .then(() => {
                                var p = res.print_data;
                                var url = '<?= base_url('accounts/usd_withdrawal/print') ?>'
                                    + '?usd_account='   + encodeURIComponent(p.usd_account)
                                    + '&lkr_account='   + encodeURIComponent(p.lkr_account)
                                    + '&usd_amount='    + encodeURIComponent(p.usd_amount)
                                    + '&exchange_rate=' + encodeURIComponent(p.exchange_rate)
                                    + '&lkr_amount='    + encodeURIComponent(p.lkr_amount)
                                    + '&date='          + encodeURIComponent(p.date)
                                    + '&description='   + encodeURIComponent(p.description || '');
                                window.open(url, '_blank');
                                $('#usd_withdrawal_form')[0].reset();
                                $('.js-example-basic-single').val(null).trigger('change');
                                $('#lkr_amount').val('');
                            });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                    }
                },
                error: function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while processing the request' });
                }
            });
        });
    </script>

</body>
</html>
