<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher Payment Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #222; background: #fff; }

        .print-container { max-width: 1100px; margin: 10px auto; padding: 20px; }

        /* Header */
        .report-header { background-color: #1a3a5c; color: #fff; text-align: center; padding: 14px; font-size: 18px; font-weight: bold; letter-spacing: 1px; margin-bottom: 15px; }

        /* Tour info card */
        .tour-info { width: 100%; border: 1px solid #d0d0d0; border-collapse: collapse; margin-bottom: 15px; }
        .tour-info td { padding: 6px 10px; border: 1px solid #d0d0d0; }
        .tour-info .label { font-weight: bold; color: #444; background: #f5f7fa; width: 15%; }
        .tour-info .value { color: #222; width: 35%; }

        /* Data table */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .data-table thead tr { background-color: #1a3a5c; color: #fff; font-weight: bold; font-size: 11px; }
        .data-table th, .data-table td { padding: 6px 8px; border: 1px solid #ccc; }
        .data-table th { text-align: center; }
        .data-table tbody tr:nth-child(even) { background-color: #f9fbfd; }
        .data-table .text-right { text-align: right; }
        .data-table .text-center { text-align: center; }

        /* Status colors */
        .st-approved { color: #1b7a3d; font-weight: bold; }
        .st-rejected { color: #c47d15; font-weight: bold; }
        .st-canceled { color: #c0392b; font-weight: bold; }

        /* Total row */
        .total-row { background-color: #1a3a5c !important; color: #fff; font-weight: bold; font-size: 12px; }
        .total-row td { border-color: #1a3a5c; }

        /* Footer */
        .report-footer { text-align: center; color: #999; font-style: italic; font-size: 10px; margin-top: 15px; }

        /* Print button */
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn-print { background: #1a3a5c; color: #fff; border: none; padding: 10px 30px; font-size: 14px; cursor: pointer; border-radius: 4px; }
        .btn-print:hover { background: #2c5f8a; }

        @media print {
            .no-print { display: none !important; }
            body { font-size: 10px; }
            .print-container { padding: 0; max-width: 90%; }
            @page { size: portrait; margin-top: 10mm; }
        }
    </style>
</head>
<body>

<div class="print-container">

    <div class="no-print">
        <button class="btn-print" onclick="window.print()"><i class="bi bi-printer"></i> Print Report</button>
    </div>

    <div class="report-header">Voucher Payment Report</div>

    <?php if (!empty($tourInfo)): ?>
    <table class="tour-info">
        <tr>
            <td class="label">Tour Name</td>
            <td class="value"><?= htmlspecialchars($tourInfo['consignee'] ?? '-') ?></td>
            <td class="label">Manual ID</td>
            <td class="value"><?= htmlspecialchars($tourInfo['manual_id'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">Agent Name</td>
            <td class="value"><?= htmlspecialchars($tourInfo['agent_name'] ?? '-') ?></td>
            <td class="label">Start Date</td>
            <td class="value"><?= $tourInfo['start_date'] ?? '-' ?></td>
        </tr>
        <tr>
            <td class="label"></td>
            <td class="value"></td>
            <td class="label">End Date</td>
            <td class="value"><?= $tourInfo['end_date'] ?? '-' ?></td>
        </tr>
    </table>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 11%;">Type</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 9%;">Voucher No</th>
                <th style="width: 13%;">Amount (LKR)</th>
                <th style="width: 18%;">Bank</th>
                <th style="width: 22%;">Account</th>
                <th style="width: 13%;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $count = 0;
            $totalAmount = 0;
            foreach ($resultlist as $value):
                $count++;
                $acc_type = ($value['sup'] == 1) ? 'Supplier Account' : 'Voucher Payment';
                $totalAmount += $value['Amount'];
                $dateOnly = !empty($value['W_Date']) ? date('Y-m-d', strtotime($value['W_Date'])) : '-';

                if ($value['Aflag'] == 1) { $st = 'Approved'; $stClass = 'st-approved'; }
                elseif ($value['Aflag'] == 2) { $st = 'Rejected'; $stClass = 'st-rejected'; }
                elseif ($value['Aflag'] == 3) { $st = 'Canceled'; $stClass = 'st-canceled'; }
                else { $st = '-'; $stClass = ''; }
            ?>
            <tr>
                <td class="text-center"><?= $count ?></td>
                <td><?= $acc_type ?></td>
                <td class="text-center"><?= $dateOnly ?></td>
                <td class="text-center"><?= htmlspecialchars($value['Vnum'] ?? '') ?></td>
                <td class="text-right"><?= number_format($value['Amount'], 2) ?></td>
                <td><?= htmlspecialchars($value['Bank'] ?? '') ?></td>
                <td><?= htmlspecialchars($value['Pay'] ?? '') ?></td>
                <td class="text-center <?= $stClass ?>"><?= $st ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right">Total</td>
                <td class="text-right"><?= number_format($totalAmount, 2) ?></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="report-footer">Avenir Holidays (Pvt) Ltd - System Generated Report</div>

</div>

<script>
    window.onload = function() {
        window.print();
    };
</script>

</body>
</html>
