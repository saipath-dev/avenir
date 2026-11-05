<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>USD Transfer Request</title>
    <style>
        @page {
            size: A4;
            margin: 20mm 20mm 20mm 25mm;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13pt;
            color: #000;
            background: #fff;
        }

        .no-print {
            text-align: center;
            margin: 20px 0;
        }

        .no-print button {
            padding: 8px 24px;
            font-size: 14px;
            cursor: pointer;
            margin: 0 6px;
            border: 1px solid #333;
            background: #fff;
            border-radius: 4px;
        }

        .no-print button.print-btn {
            background: #198754;
            color: #fff;
            border-color: #198754;
        }

        @media print {
            .no-print { display: none !important; }
        }

        .page {
            width: 100%;
            padding: 10px 0;
        }

        /* Header */
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .date-block {
            font-size: 13pt;
            line-height: 1.8;
        }

        .logo-block {
            text-align: right;
        }

        .logo-block img {
            height: 70px;
            display: block;
            margin-left: auto;
        }

        /* Addressee */
        .addressee {
            margin-bottom: 20px;
            line-height: 1.8;
            font-size: 13pt;
        }

        /* Salutation */
        .salutation {
            margin-bottom: 16px;
            font-size: 13pt;
        }

        /* Account info */
        .account-info {
            margin-bottom: 20px;
            font-size: 13pt;
            line-height: 2;
        }

        .account-info strong {
            display: inline-block;
            min-width: 220px;
        }

        /* Subject */
        .subject {
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 20px;
        }

        /* Body */
        .body-text {
            font-size: 13pt;
            line-height: 1.9;
            margin-bottom: 16px;
            text-align: justify;
        }

        /* Signature */
        .signature {
            margin-top: 50px;
            font-size: 13pt;
            line-height: 1.8;
        }

        .signature-gap {
            height: 55px;
        }

        /* Footer */
        .footer {
            margin-top: 60px;
            text-align: center;
            font-size: 9pt;
            border-top: 1px solid #000;
            padding-top: 8px;
            color: #333;
            line-height: 1.7;
        }
    </style>
</head>
<body>

<div class="no-print">
    <button class="print-btn" onclick="window.print()">Print</button>
    <button onclick="window.close()">Close</button>
</div>

<div class="page">

    <!-- Header -->
    <div class="header-row">
        <div class="date-block">
            <?= date('d/m/Y', strtotime($date)) ?>
        </div>
        <div class="logo-block">
            <img src="<?= base_url('assets/images/Logo-new.png') ?>" alt="Avenir Holidays">
        </div>
    </div>

    <!-- Addressee -->
    <div class="addressee">
        The Manager<br>
        Commercial Bank PLC<br>
        Ward Place Branch<br>
        Colombo 7
    </div>

    <!-- Salutation -->
    <div class="salutation">Dear Sir/Madam</div>

    <!-- Account Numbers -->
    <div class="account-info">
        <strong>Current A/C No (LKR)</strong> : <?= esc($lkr_acc_name) ?><br>
        <strong>Saving A/C No &nbsp;(USD)</strong> : <?= esc($usd_acc_name) ?>
    </div>

    <!-- Subject -->
    <div class="subject">Request for USD Transfer to LKR Account with Special Exchange Rate</div>

    <!-- Body -->
    <p class="body-text">
        I am writing to request the transfer of
        <strong>USD <?= number_format($usd_amount, 2) ?></strong>
        from our company's USD account
        <strong><?= esc($usd_acc_name) ?></strong>
        to our LKR account
        <strong><?= esc($lkr_acc_name) ?></strong>
        held with Commercial Bank.
    </p>

    <p class="body-text">
        The agreed exchange rate for this transfer is
        <strong>LKR <?= number_format($exchange_rate, 2) ?></strong> per USD,
        resulting in a total LKR amount of
        <strong>LKR <?= number_format($lkr_amount, 2) ?></strong>.
        <?php if ($description): ?>
            Reference: <?= esc($description) ?>.
        <?php endif; ?>
    </p>

    <p class="body-text">
        Please let me know if you require any further information or documentation to process
        this request. Your assistance in facilitating this transfer with the preferred rate is
        highly appreciated.
    </p>

    <!-- Signature -->
    <div class="signature">
        Thank you,<br>
        Yours faithfully,
        <div class="signature-gap"></div>
        C.S.S. Wickramasinghe<br>
        Director
    </div>

    <!-- Footer -->
    <div class="footer">
        <strong>Avenir Holidays (Pvt) Ltd.</strong><br>
        Head Office &ndash; No. 565, Sinharamulla Road, Kelaniya, Sri Lanka<br>
        Tel &ndash; 94 112 551880 / 94 112 551660 &nbsp;|&nbsp; E-Mail &ndash; info@avenirholidays.com &nbsp;|&nbsp; Web &ndash; www.avenirholidays.com
    </div>

</div>

</body>
</html>
