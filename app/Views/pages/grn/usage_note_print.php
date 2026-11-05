<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Avenir Holidays :: Consumption Note</title>
  <link rel="icon" href="<?php echo base_url(); ?>assets/images/Logo-new.png">
  <style>
    @page {
      size: A4;
      margin: 15mm;
    }

    body {
      font-family: "Times New Roman", Times, serif;
      font-size: 12px;
      line-height: 1.5;
      color: #333;
      background-color: #fff;
      margin: 0;
      padding: 0;
    }

    .content {
      width: 100%;
      position: relative;
      padding: 10px;
      box-sizing: border-box;
      margin: 10px auto;
      max-width: 800px;
    }

    .invoice-header {
      text-align: center;
      border-bottom: 1px solid #000;
      margin-bottom: 20px;
      padding-bottom: 10px;
    }

    .invoice-header h3 {
      margin: 0;
      font-size: 22px;
      text-transform: uppercase;
      font-weight: bold;
    }

    .invoice-header p {
      font-size: 14px;
      margin: 5px 0;
    }

    .invoice-logo {
      float: left;
      margin-right: 20px;
    }

    .invoice-logo img {
      height: 60px;
    }

    .header-info {
      text-align: center;
      margin: 0 auto 15px;
      font-size: 15px;
      color: #555;
    }

    .line {
      border: none;
      height: 1px;
      background: linear-gradient(to right, transparent, #666, transparent);
      margin: 10px auto;
      width: 90%;
    }

    .usage-note-title {
      text-align: center;
      font-weight: bold;
      font-size: 16px;
      margin: 15px 0;
      text-transform: uppercase;
      color: #204080;
      letter-spacing: 1px;
    }

    .details-container {
      width: 100%;
      margin: 20px 0;
      border: 1px solid #ddd;
      padding: 15px;
      background-color: #f9f9f9;
    }

    .invoice-table {
      width: 100%;
      border-collapse: collapse;
    }

    .invoice-table td {
      padding: 10px;
      vertical-align: top;
      border: 1px solid #ddd;
    }

    .label {
      font-weight: bold;
      color: #333;
      background-color: #f2f2f2;
    }

    .value {
      color: #333;
    }

    .details-header {
      font-weight: bold;
      margin-bottom: 10px;
      text-transform: uppercase;
      font-size: 14px;
      color: #333;
    }

    .container-moreDetail {
      width: 100%;
      margin: 15px 0;
      padding: 0px;
    }

    .invoice-table-new {
      width: 100%;
      border-collapse: collapse;
    }

    .invoice-table-new th,
    .invoice-table-new td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: center;
    }

    .invoice-table-new th {
      background-color: #f2f2f2;
      color: #333;
      font-weight: bold;
      text-align: center;
    }

    .invoice-table-new tbody tr:nth-child(even) {
      background-color: #fafafa;
    }

    .invoice-table-new td:first-child {
      width: 50px;
    }

    .invoice-table-new td:nth-child(2) {
      text-align: left;
    }

    .invoice-table-new td:nth-child(3) {
      width: 100px;
    }

    .invoice-table-new td:nth-child(4) {
      text-align: left;
    }

    .invoice-table-new td:last-child {
      width: 100px;
      font-weight: bold;
    }

    .total-section {
      width: 100%;
      margin: 15px 0;
      display: flex;
      justify-content: flex-end;
    }

    .total-box {
      width: 300px;
      text-align: right;
      padding: 10px;
      font-weight: bold;
      font-size: 14px;
      border: 1px solid #ddd;
      background-color: #f2f2f2;
    }

    .signature-section {
      width: 100%;
      margin: 40px 0 10px;
      display: flex;
      justify-content: space-between;
    }

    .signature-box {
      text-align: center;
      width: 30%;
    }

    .signature-line {
      border-top: 2px solid #333;
      padding-top: 5px;
      margin-top: 50px;
      font-weight: bold;
      color: #333;
    }

    .signature-line-2 {
      border-top: 2px solid #333;
      padding-top: 5px;
      margin-top: 5px;
      font-weight: bold;
      color: #333;
    }

    .footer {
      text-align: center;
      font-size: 10px;
      color: #666;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #ddd;
    }

    .status-indicator {
      padding: 10px;
      margin: 10px 0;
      text-align: center;
      font-weight: bold;
      border-radius: 5px;
      font-size: 14px;
    }
    .status-pending {
      background-color: #fff3cd;
      border: 2px solid #ffc107;
      color: #856404;
    }
    .status-approved {
      background-color: #d4edda;
      border: 2px solid #28a745;
      color: #155724;
    }
    .status-rejected {
      background-color: #f8d7da;
      border: 2px solid #dc3545;
      color: #721c24;
    }
    .watermark {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-45deg);
      opacity: 0.1;
      font-size: 120px;
      color: #ff9800;
      font-weight: bold;
      z-index: -1;
      pointer-events: none;
    }
    @media print {
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>

<body>
  <?php if ($status === 'pending'): ?>
  <div class="watermark">PENDING APPROVAL</div>
  <?php endif; ?>

  <div class="content">
    <!-- Invoice Header -->
    <div class="invoice-header">
      <div class="invoice-logo">
        <img src="<?= base_url() ?>assets/images/Logo-new.png" alt="Logo">
      </div>
      <h3>AVENIR HOLIDAYS (PVT) LTD</h3>
      <p>NO. 126/1, Park Road, Colombo -5, SRI LANKA</p>
      <p>Email: info@avenirholidays.com</p>
    </div>

    <h2 class="usage-note-title">Consumption Note</h2>

    <!-- Status Indicator -->
    <?php if ($status === 'pending'): ?>
      <div class="status-indicator status-pending">
        ⏳ PENDING AUTHORIZATION - This usage note is awaiting approval. Stock has not been updated.
      </div>
    <?php elseif ($status === 'approved'): ?>
      <div class="status-indicator status-approved">
        ✓ APPROVED - Approved by <?= esc($approved_by) ?> on <?= date('Y-m-d H:i', strtotime($approved_at)) ?>
      </div>
    <?php elseif ($status === 'rejected'): ?>
      <div class="status-indicator status-rejected">
        ✗ REJECTED - <?= esc($rejection_reason) ?>
      </div>
    <?php endif; ?>

    <!-- Details Container -->
    <div class="details-container">
      <table class="invoice-table">
        <tbody>
          <tr>
            <td width="15%"><span class="label">Date:</span></td>
            <td width="35%"><span class="value"><?php echo date('Y-m-d', strtotime($date)) ?></span></td>
            <td width="25%"><span class="label">Consumption Note No:</span></td>
            <td width="15%"><span class="value"><?php echo $usage_note_no ?></span></td>
          </tr>
          <tr>
            <td width="15%"><span class="label">Tour ID:</span></td>
            <td width="35%"><span class="value"><?php echo $tour['tour_id'] ?? 'N/A'; ?></span></td>
            <td width="25%"><span class="label">Manual ID:</span></td>
            <td width="15%"><span class="value"><?php echo $tour['manual_id'] ?? 'N/A'; ?></span></td>
          </tr>
          <tr>
            <td width="15%"><span class="label">Tour Name:</span></td>
            <td colspan="3"><span class="value"><?php echo $tour['consignee'] ?? 'N/A'; ?></span></td>
          </tr>
          <tr>
            <td width="15%"><span class="label">Tour Dates:</span></td>
            <td width="35%"><span class="value"><?php echo $tour['start_date'] ?? 'N/A'; ?> to <?php echo $tour['end_date'] ?? 'N/A'; ?></span></td>
            <td width="25%"><span class="label">Pax:</span></td>
            <td width="15%"><span class="value"><?php echo $tour['passenger_count'] ?? 'N/A'; ?></span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <br>
    <div class="container-moreDetail">
      <div class="table-responsive">
        <table class="invoice-table-new">
          <thead>
            <tr>
              <th>#</th>
              <th>Activity</th>
              <th>Item Code</th>
              <th>Item Description</th>
              <th>Quantity</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $total_quantity = 0;
            $serial_no = 1;
            $stockCardModel = new \App\Models\Grn\StockCardModel();
            $entranceFeeModel = new \App\Models\EntranceFeeModel();

            foreach ($grouped_items as $activityKey => $activityData) {
              $activity_name = 'N/A';
              if ($activityData['activity_id']) {
                $tourActivityModel = new \App\Models\TourActivityModel();
                $activity = $tourActivityModel->find($activityData['activity_id']);
                if ($activity) {
                  $entranceFee = $entranceFeeModel->find($activity['name']);
                  $activity_name = ($entranceFee['name'] ?? 'Unknown') . ' (' . ($activity['code'] ?? '') . ')';
                }
              }

              foreach ($activityData['items'] as $item) {
                $total_quantity += $item['quantity'];
                $itemDetails = $stockCardModel->where('Stock_Card', $item['item_code'])->first();
            ?>
              <tr>
                <td><?= $serial_no++; ?></td>
                <td><?= $activity_name; ?></td>
                <td><?= $item['item_code']; ?></td>
                <td style="text-align: left; padding-left: 10px;">
                  <?= $itemDetails['Descrip'] ?? $item['item_code']; ?>
                </td>
                <td><?= number_format($item['quantity'], 2); ?></td>
              </tr>
            <?php
              }
            }
            ?>
            <tr style="background-color: #f2f2f2; font-weight: bold;">
              <td colspan="4" style="text-align: right; padding-right: 15px;">TOTAL QUANTITY:</td>
              <td><?= number_format($total_quantity, 2); ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Signature Section -->
    <!-- <div class="signature-section">
      <div class="signature-box">
        <div style="margin-bottom: 0px; font-weight: normal; color: #666;"><?php echo $created_by ?></div>
        <div class="signature-line-2">Prepared By</div>
      </div>
      <div class="signature-box">
        
        <div class="signature-line">Approved By</div>
      </div>
      <div class="signature-box">
        <div class="signature-line">Received By</div>
      </div>
    </div> -->

    <!-- Footer -->
    <!-- <div class="footer">
      <p>Thank you for choosing Avenir Holidays!</p>
      <p>Printed on: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div> -->
  </div>

  <script>
    window.onload = function() {
      window.print();
    }
  </script>
</body>

</html>