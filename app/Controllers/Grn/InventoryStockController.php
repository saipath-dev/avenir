<?php

namespace App\Controllers\Grn;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;

class InventoryStockController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/inventory/stock/
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Get all inventory items from the view
        $db = \Config\Database::connect();
        $query = $db->query("SELECT * FROM inventory_stock_view ORDER BY Stock_Card");
        $data['inventory_items'] = $query->getResultArray();

        // Calculate summary statistics
        $data['total_items'] = count($data['inventory_items']);
        $data['in_stock_count'] = 0;
        $data['low_stock_count'] = 0;
        $data['out_of_stock_count'] = 0;
        $data['total_value'] = 0;

        foreach ($data['inventory_items'] as $item) {
            if ($item['stock_status'] === 'In Stock') {
                $data['in_stock_count']++;
            } elseif ($item['stock_status'] === 'Low Stock') {
                $data['low_stock_count']++;
            } elseif ($item['stock_status'] === 'Out of Stock') {
                $data['out_of_stock_count']++;
            }
            $data['total_value'] += $item['current_balance'] * $item['avg_cost'];
        }

        return view('pages/grn/inventory_stock', $data);
    }

    // route : base_url/inventory/stock/low_stock
    public function low_stock()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Get only low stock and out of stock items
        $db = \Config\Database::connect();
        $query = $db->query("
            SELECT * FROM inventory_stock_view
            WHERE stock_status IN ('Low Stock', 'Out of Stock')
            ORDER BY current_balance ASC, Stock_Card
        ");
        $data['inventory_items'] = $query->getResultArray();

        // Calculate summary statistics
        $data['total_items'] = count($data['inventory_items']);
        $data['low_stock_count'] = 0;
        $data['out_of_stock_count'] = 0;

        foreach ($data['inventory_items'] as $item) {
            if ($item['stock_status'] === 'Low Stock') {
                $data['low_stock_count']++;
            } elseif ($item['stock_status'] === 'Out of Stock') {
                $data['out_of_stock_count']++;
            }
        }

        return view('pages/grn/inventory_low_stock', $data);
    }

    // route : base_url/inventory/stock/search
    public function search()
    {
        $searchTerm = $this->request->getGet('term');

        if (empty($searchTerm)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Search term is required',
                'data' => []
            ]);
        }

        $db = \Config\Database::connect();

        // Search in Stock_Card and item_description
        $searchTerm = $db->escapeLikeString($searchTerm);
        $query = $db->query("
            SELECT * FROM inventory_stock_view
            WHERE Stock_Card LIKE ? OR item_description LIKE ?
            ORDER BY Stock_Card
            LIMIT 50
        ", ["%{$searchTerm}%", "%{$searchTerm}%"]);

        $results = $query->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Search completed',
            'count' => count($results),
            'data' => $results
        ]);
    }

    // route : base_url/inventory/stock/header_stock_data
    public function getHeaderStockData()
    {
        try {
            $db = \Config\Database::connect();

            // Get all stock items from the view
            $query = $db->query("
                SELECT
                    Stock_Card,
                    item_description,
                    current_balance,
                    reorder_level,
                    stock_status,
                    purchase_price
                FROM inventory_stock_view
                ORDER BY Stock_Card ASC
            ");
            
            // log_message('critical', json_encode($query->getResultArray()));

            $items = $query->getResultArray();

            // Return JSON response matching driver notifications pattern
            return $this->response->setJSON([
                'status' => 200,
                'count' => count($items),
                'items' => $items
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Error fetching stock data: ' . $e->getMessage(),
                'count' => 0,
                'items' => []
            ]);
        }
    }

    // route : base_url/inventory/stock/process_return
    public function process_return()
    {
        try {
            // Get POST data
            $stockCard = $this->request->getPost('stock_card');
            $quantity = $this->request->getPost('quantity');
            $returnDate = $this->request->getPost('return_date');
            $reason = $this->request->getPost('reason');
            $notes = $this->request->getPost('notes');
            $avgCost = $this->request->getPost('avg_cost');

            // Validation
            if (empty($stockCard) || empty($quantity) || empty($returnDate) || empty($reason)) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'All required fields must be provided'
                ]);
            }

            if ($quantity <= 0) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'Return quantity must be greater than zero'
                ]);
            }

            $db = \Config\Database::connect();

            // Check current stock balance
            $query = $db->query("
                SELECT SUM(Dr_Qty) - SUM(Cr_Qty) AS current_balance
                FROM stock
                WHERE Stock_Card = ?
            ", [$stockCard]);

            $stockBalance = $query->getRowArray();
            $currentBalance = $stockBalance['current_balance'] ?? 0;

            if ($quantity > $currentBalance) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'Return quantity (' . $quantity . ') exceeds current stock (' . $currentBalance . ')'
                ]);
            }

            // Generate reference numbers
            $invoiceNumberModel = new \App\Models\InvoiceNumberModel();
            $stockRef = $invoiceNumberModel->incrementStockNo();

            // Build return description
            $description = 'Stock Return - ' . $stockCard . ' - ' . $reason;
            if (!empty($notes)) {
                $description .= ' - ' . $notes;
            }

            // Calculate return value
            $returnValue = $quantity * $avgCost;

            // Get username
            $username = session()->get('username') ?? 'system';

            // Return reference number
            $returnRef = 'RTN-' . $stockRef;

            // 1. Insert stock return record (Cr_Qty = outbound)
            $stockModel = new \App\Models\Grn\StockModel();
            $stockData = [
                'Stock_Card' => $stockCard,
                'Ref' => $stockRef,
                'Unit_Cost' => $avgCost,
                'Dr_Qty' => 0,                    // No receipt
                'Cr_Qty' => $quantity,            // CREDIT: Stock reduction
                'Status' => 'P',                  // Posted immediately
                'returned_date' => $returnDate,
                'tym' => $returnDate,
                'V_Num' => $returnRef,
                'Descrip' => $description,
                'foc' => 0,
                'pPrice' => $avgCost,
            ];

            if (!$stockModel->insert($stockData)) {
                throw new \Exception('Failed to create stock return record');
            }

            // 2. Insert into stock_return table for tracking
            $stockReturnModel = new \App\Models\Grn\StockReturnModel();
            $returnData = [
                'return_ref' => $returnRef,
                'stock_card' => $stockCard,
                'return_date' => $returnDate,
                'quantity' => $quantity,
                'unit_cost' => $avgCost,
                'total_value' => $returnValue,
                'reason' => $reason,
                'notes' => $notes,
                'stock_ref' => $stockRef,
                'created_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'processed'
            ];

            if (!$stockReturnModel->insert($returnData)) {
                throw new \Exception('Failed to create stock return tracking record');
            }

            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Stock return processed successfully. Reference: ' . $returnRef,
                'reference' => $returnRef,
                'quantity_returned' => $quantity,
                'value' => $returnValue
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Stock return error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Error processing stock return: ' . $e->getMessage()
            ]);
        }
    }

    // route : base_url/inventory/stock/return_report
    public function return_report()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $db = \Config\Database::connect();

        // Get all stock items for dropdown
        $stockQuery = $db->query("SELECT Stock_Card, Descrip FROM stock_card ORDER BY Descrip");
        $data['stock_items'] = $stockQuery->getResultArray();

        // Check if form was submitted
        $data['is_search'] = $this->request->getGet('submit') !== null;

        // Get filter parameters
        $data['startdate'] = $this->request->getGet('startdate') ?? '';
        $data['enddate'] = $this->request->getGet('enddate') ?? '';
        $data['item_keyword'] = $this->request->getGet('item_keyword') ?? '';
        $data['reason'] = $this->request->getGet('reason') ?? '';
        $data['status_filter'] = $this->request->getGet('status_filter') ?? '';

        $data['resultlist'] = [];
        $data['summary'] = [
            'total_returns' => 0,
            'total_quantity' => 0,
            'total_value' => 0
        ];

        if ($data['is_search']) {
            try {
                // Build query
                $query = "
                    SELECT
                        sr.*,
                        sc.Descrip as item_description,
                        sc.pp as unit_of_issue
                    FROM stock_return sr
                    LEFT JOIN stock_card sc ON sr.stock_card = sc.Stock_Card
                    WHERE 1=1
                ";

                $params = [];

                // Date filters
                if (!empty($data['startdate'])) {
                    $query .= " AND sr.return_date >= ?";
                    $params[] = $data['startdate'];
                }
                if (!empty($data['enddate'])) {
                    $query .= " AND sr.return_date <= ?";
                    $params[] = $data['enddate'];
                }

                // Item keyword filter (exact match for stock_card)
                if (!empty($data['item_keyword'])) {
                    $query .= " AND sr.stock_card = ?";
                    $params[] = $data['item_keyword'];
                }

                // Reason filter
                if (!empty($data['reason'])) {
                    $query .= " AND sr.reason = ?";
                    $params[] = $data['reason'];
                }

                // Status filter
                if (!empty($data['status_filter'])) {
                    $query .= " AND sr.status = ?";
                    $params[] = $data['status_filter'];
                }

                $query .= " ORDER BY sr.return_date DESC, sr.id DESC LIMIT 1000";

                log_message('info', 'Stock return report query: ' . $query);
                log_message('info', 'Stock return report params: ' . json_encode($params));

                $result = $db->query($query, $params);

                if ($result === false) {
                    log_message('error', 'Stock return report query failed: ' . $db->error());
                    $data['resultlist'] = [];
                } else {
                    $data['resultlist'] = $result->getResultArray();

                    // Calculate summary
                    foreach ($data['resultlist'] as $row) {
                        $data['summary']['total_quantity'] += $row['quantity'];
                        $data['summary']['total_value'] += $row['total_value'];
                    }
                    $data['summary']['total_returns'] = count($data['resultlist']);
                }

                log_message('info', 'Stock return report completed. Records: ' . count($data['resultlist']));

            } catch (\Exception $e) {
                log_message('error', 'Stock return report error: ' . $e->getMessage());
                $data['resultlist'] = [];
                $data['error_message'] = 'Error loading report: ' . $e->getMessage();
            }
        }

        return view('pages/grn/stock_return_report', $data);
    }

    // route : base_url/inventory/stock/summary_report
    public function summary_report()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $db = \Config\Database::connect();

        $stockQuery = $db->query("SELECT Stock_Card, Descrip FROM stock_card ORDER BY Descrip");
        $data['stock_items'] = $stockQuery->getResultArray();

        $data['is_search'] = $this->request->getGet('submit') !== null;
        $data['startdate'] = $this->request->getGet('startdate') ?? '';
        $data['enddate'] = $this->request->getGet('enddate') ?? '';
        $data['item_keyword'] = $this->request->getGet('item_keyword') ?? '';

        $data['resultlist'] = [];
        $data['totals'] = [
            'total_debit' => 0,
            'total_credit' => 0,
            'total_opening' => 0,
            'total_difference' => 0,
            'grand_closing_balance' => 0,
            'row_count' => 0,
            'item_count' => 0,
        ];
        $data['error_message'] = '';

        if ($data['is_search']) {
            try {
                $model = new \App\Models\Grn\StockSummaryModel();

                $filters = [
                    'stock_card' => $data['item_keyword'],
                    'startdate' => $data['startdate'],
                    'enddate' => !empty($data['enddate']) ? ($data['enddate'] . ' 23:59:59') : '',
                ];

                $result = $model->getLedger($filters);
                $data['resultlist'] = $result['rows'];
                $data['totals'] = $result['totals'];

            } catch (\Exception $e) {
                log_message('error', 'Stock summary report error: ' . $e->getMessage());
                $data['resultlist'] = [];
                $data['error_message'] = 'Error loading report: ' . $e->getMessage();
            }
        }

        return view('pages/grn/stock_summary_report', $data);
    }

    // route : base_url/inventory/stock/summary_report_export_excel
    public function summary_report_export_excel()
    {
        $startdate = $this->request->getGet('startdate') ?? '';
        $enddate = $this->request->getGet('enddate') ?? '';
        $item_keyword = $this->request->getGet('item_keyword') ?? '';

        $model = new \App\Models\Grn\StockSummaryModel();
        $filters = [
            'stock_card' => $item_keyword,
            'startdate' => $startdate,
            'enddate' => !empty($enddate) ? ($enddate . ' 23:59:59') : '',
        ];

        $result = $model->getLedger($filters);
        $rows = $result['rows'];
        $totals = $result['totals'];

        $filename = 'stock_summary_report_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Item Code', 'Date', 'Tour', 'Description', 'Debit', 'Credit', 'Balance']);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['Stock_Card'] ?? '',
                $row['txn_date'] ?? '',
                $row['tour'],
                $row['description'],
                $row['debit'],
                $row['credit'],
                $row['running_balance'],
            ]);
        }

        fputcsv($output, []);
        fputcsv($output, ['', '', '', 'TOTAL', $totals['total_debit'], $totals['total_credit'], $totals['grand_closing_balance']]);
        fputcsv($output, ['', '', '', 'DIFFERENCE (Debit - Credit)', '', '', $totals['total_difference']]);

        fclose($output);
        exit;
    }
}
